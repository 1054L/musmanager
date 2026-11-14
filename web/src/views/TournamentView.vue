<script setup>
import { ref, onMounted, onUnmounted, computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { tournamentService } from '../services/api'
import { useI18n } from 'vue-i18n'
import { useMercure } from '../composables/useMercure'
import Card from 'primevue/card'
import Tag from 'primevue/tag'
import ProgressBar from 'primevue/progressbar'
import Button from 'primevue/button'
import Timeline from 'primevue/timeline'
import Dialog from 'primevue/dialog'
import GoogleAd from '../components/GoogleAd.vue'
import MusLoader from '../components/MusLoader.vue'
import { useToast } from 'primevue/usetoast'

const normalizeStageKey = (stage) => {
  if (!stage) return 'general'
  const s = stage.toLowerCase().trim()

  if (s.startsWith('grupo') || s.startsWith('mesa') || s.startsWith('group') || s.startsWith('talde') || s.startsWith('mahaia')) {
    return stage.trim()
  }
  
  const rondaMatch = s.match(/(?:ronda|round)\s*(\d+)/i)
  if (rondaMatch) {
    const roundNumber = Number(rondaMatch[1])
    const roundMap = ['final', 't4', 't8', 't16', 't32', 't64']
    return roundMap[roundNumber - 1] || stage.toLowerCase().replace(/\s+/g, '_')
  }

  // Check for 64 (Round of 64 / Treintadosavos)
  if (s.includes('treintadosavos') || s.includes('treintaidosavos') || s.includes('trintadosavos') || s.includes('64') || s.includes('hirurogei')) return 't64'
  
  // Check for 32 (Round of 32 / Dieciseisavos)
  if (s.includes('dieciseisavos') || s.includes('32') || s.includes('hamasei')) return 't32'
  
  // Check for 16 (Round of 16 / Octavos)
  if (s.includes('octavos') || s.includes('16') || s.includes('zortzi')) return 't16'
  
  // Check for 8 (Quarter-finals / Cuartos)
  if (s.includes('cuartos') || s.includes('quarter') || s.includes('8') || s.includes('laurden')) return 't8'
  
  // Check for 3rd place (Check this before t4/semi because it might contain '4')
  if (s.includes('3º') || s.includes('3er') || s.includes('tercer') || s.includes('3/') || s.includes('hirugarren')) return 'third_place'
  
  // Check for 4 (Semi-finals)
  if (s.includes('semi') || s.includes('4') || s.includes('erdi')) return 't4'
  
  // Final only if it's exactly "final" or common standalone terms
  if (s === 'final' || s === 'finala' || s === 'la final' || s === 'gran final') return 'final'
  
  // Fallback for slugs or unknown stages
  return stage.toLowerCase().replace(/\s+/g, '_')
}

const formatMesa = (mesa) => {
  if (!mesa) return ''
  if (mesa.toLowerCase().startsWith('mesa')) {
    const number = mesa.match(/\d+/)
    return number ? `${t('common.table')} ${number[0]}` : t('common.table')
  }
  // Basque fallback "Mahaia X"
  if (mesa.toLowerCase().startsWith('mahaia')) {
    const number = mesa.match(/\d+/)
    return number ? `${t('common.table')} ${number[0]}` : t('common.table')
  }
  const groupMatch = mesa.trim().match(/^(?:grupo|group|talde)\s+([a-z0-9]+)$/i)
  if (groupMatch) {
    return t('common.group_name', { name: groupMatch[1].toUpperCase() })
  }
  return mesa
}

const translateStage = (stageName) => {
  const key = normalizeStageKey(stageName)
  const translation = t('stages.' + key)
  // If key not found in i18n, return original
  return translation.includes('stages.') ? stageName : translation
}

const { t, locale } = useI18n()

const getStageLabel = (stage) => {
  if (stage.startsWith('matchDay_')) {
    const day = stage.split('_')[1]
    const matchDayLabel = t('tournament_view.rounds.matchDay')
    if (!day) return matchDayLabel

    return locale.value === 'eu'
      ? `${day}. ${matchDayLabel}`
      : `${matchDayLabel} ${day}`
  }

  const groupMatch = stage.trim().match(/^(?:grupo|group|talde)\s+([a-z0-9]+)$/i)
  if (groupMatch) {
    return t('common.group_name', { name: groupMatch[1].toUpperCase() })
  }

  const normalizedStage = normalizeStageKey(stage)
  const knockoutKey = `tournament_view.knockout.${normalizedStage}`
  const knockoutLabel = t(knockoutKey)
  if (knockoutLabel !== knockoutKey) return knockoutLabel

  const roundKey = `tournament_view.rounds.${normalizedStage}`
  const roundLabel = t(roundKey)
  if (roundLabel !== roundKey) return roundLabel

  return stage
}

const route = useRoute()
const router = useRouter()
const toast = useToast()
const { subscribe } = useMercure()
const activeTab = ref('matches')
const classification = ref({})
const normalizedClassification = computed(() => {
  if (!classification.value) return {};
  // If it's already an object with keys (grouped by groups/tables)
  if (!Array.isArray(classification.value)) {
    return classification.value;
  }
  // If it's a flat array (League mode), wrap it in a 'General' group
  return { [t('stages.general')]: classification.value };
});
const tournament = ref(null)
const loading = ref(true)
const error = ref(null)
const matches = ref([])
const showActivity = ref(false)
const isSavingResult = ref(false)
const showPosterDialog = ref(false)
const isManager = computed(() => tournament.value?.isManager || false)

const teamsPerGroup = computed(() => {
  const cfg = tournament.value?.leagueMatchDays
  if (cfg && typeof cfg === 'object' && !Array.isArray(cfg) && cfg.teamsPerGroup) {
    return Number(cfg.teamsPerGroup)
  }
  return 2
})

const formatDate = (dateStr) => {
  if (!dateStr) return ''
  const date = new Date(dateStr)
  
  // Special handling for Basque (manual mapping to ensure correctness)
  if (locale.value === 'eu') {
    const monthsEU = ['URT', 'OTS', 'MAR', 'API', 'MAI', 'EKA', 'UZT', 'ABU', 'IRA', 'URR', 'AZA', 'ABE']
    return `${date.getDate().toString().padStart(2, '0')} ${monthsEU[date.getMonth()]}`
  }

  const localeMap = { 'es': 'es-ES', 'en': 'en-GB' }
  const currentLocale = localeMap[locale.value] || 'es-ES'
  return date.toLocaleDateString(currentLocale, { day: '2-digit', month: 'short' }).toUpperCase()
}

const formatFullDate = (dateStr) => {
  if (!dateStr) return ''
  const date = new Date(dateStr)
  const localeMap = { 'eu': 'eu-ES', 'es': 'es-ES', 'en': 'en-GB' }
  const currentLocale = localeMap[locale.value] || 'es-ES'
  return date.toLocaleDateString(currentLocale)
}
const uuid = route.params.uuid

const bracketContainer = ref(null);
const isFullscreen = ref(false);
const zoomLevel = ref(1);
const highlightTiebreaker = ref(false);

const scrollToTiebreaker = () => {
  const el = document.getElementById('tiebreaker-info');
  if (el) {
    el.scrollIntoView({ behavior: 'smooth', block: 'center' });
    highlightTiebreaker.value = true;
    setTimeout(() => {
      highlightTiebreaker.value = false;
    }, 2200);
  }
};

const toggleFullscreen = () => {
  if (!bracketContainer.value) return;
  if (!document.fullscreenElement) {
    bracketContainer.value.requestFullscreen().catch(err => {
      console.error(err);
    });
  } else {
    document.exitFullscreen();
  }
};


const activeStageTab = ref(null);

const knockoutStages = [
  t('tournament_view.knockout.t32'),
  t('tournament_view.knockout.t16'),
  t('tournament_view.knockout.t8'),
  t('tournament_view.knockout.t4'), 
  t('tournament_view.knockout.final'), 
  t('tournament_view.knockout.third_place'),
  '3º y 4º puesto'
];
const groupedMatches = computed(() => {
  const groups = {}
  if (!matches.value || matches.value.length === 0) return []
  
  matches.value.forEach(match => {
    let groupKey = ''
    if (tournament.value?.type === 'league') {
      const stageName = match.stage || ''
      const day = match.matchDay || (stageName.toLowerCase().includes('jornada') || stageName.toLowerCase().includes('jardunaldia') ? stageName.match(/\d+/)?.[0] : null)
      groupKey = day ? `matchDay_${day}` : 'general'
    } else {
      groupKey = normalizeStageKey(match.stage || 'general')
    }

    if (!groups[groupKey]) groups[groupKey] = []
    groups[groupKey].push(match)
  })
  
  let entries = Object.entries(groups)
  
  // Sort entries numerically for league match days or by group/knockout order
  const stageOrder = ['t64', 't32', 't16', 't8', 't4', 'final', 'third_place']
  entries.sort((a, b) => {
    if (tournament.value?.type === 'league') {
      const dayA = parseInt(a[0].split('_')[1] || 0)
      const dayB = parseInt(b[0].split('_')[1] || 0)
      return dayA - dayB
    }
    const idxA = stageOrder.indexOf(a[0])
    const idxB = stageOrder.indexOf(b[0])
    if (idxA !== -1 && idxB !== -1) return idxA - idxB
    if (idxA === -1 && idxB !== -1) return -1
    if (idxA !== -1 && idxB === -1) return 1
    return a[0].localeCompare(b[0], undefined, { numeric: true })
  })

  return entries
});

// Calculate resting teams for each match day (for odd number of teams)
const restingTeamsByDay = computed(() => {
  if (!tournament.value?.teams || !matches.value) return {};
  
  const allTeams = tournament.value.teams;
  const resting = {};
  
  groupedMatches.value.forEach(([stage, stageMatches]) => {
    // Only for match days/league rounds
    if (stage.startsWith('matchDay_')) {
      const day = stage.split('_')[1];
      const teamsInMatches = new Set();
      
      stageMatches.forEach(m => {
        if (m.teamA?.id) teamsInMatches.add(m.teamA.id);
        if (m.teamB?.id) teamsInMatches.add(m.teamB.id);
      });
      
      const restingThisDay = allTeams.filter(t => !teamsInMatches.has(t.id));
      if (restingThisDay.length > 0) {
        resting[stage] = restingThisDay;
      }
    }
  });
  
  return resting;
});

// Auto-select first stage tab when matches load
watch(groupedMatches, (newGroups) => {
  if (newGroups.length > 0 && !activeStageTab.value) {
    activeStageTab.value = newGroups[0][0];
  }
}, { immediate: true });

const bracketMatches = computed(() => {
  const acc = {};
  matches.value.forEach(m => {
    if (m.bracketRound && m.bracketRound >= 1) {
      if (!acc[m.bracketRound]) acc[m.bracketRound] = { 
        round: m.bracketRound, 
        stageName: m.stage, 
        matches: [] 
      };
      acc[m.bracketRound].matches.push(m);
    }
  });

  // Sort by bracketRound descending (e.g. 4, 3, 2, 1)
  return Object.values(acc).sort((a, b) => b.round - a.round);
});

const splitBracketMatches = computed(() => {
  const left = {};
  const right = {};
  const final = [];

  bracketMatches.value.forEach(roundData => {
    // Round 1 is the final round.
    if (roundData.round === 1) {
      // Find the final match (position 0)
      const fm = roundData.matches.find(m => m.bracketPosition === 0);
      if (fm) final.push(fm);
      
      // Also find 3rd place match (position 1) if it exists in this round data
      const tpm = roundData.matches.find(m => m.bracketPosition === 1);
      if (tpm) final.push(tpm);
    } else {
      const half = Math.ceil(roundData.matches.length / 2);
      left[roundData.round] = { ...roundData, matches: roundData.matches.slice(0, half) };
      right[roundData.round] = { ...roundData, matches: roundData.matches.slice(half) };
    }
  });

  // Make sure 3rd place match is included even if not in Round 1 data structure (fallback)
  const thirdMatch = matches.value.find(x => normalizeStageKey(x.stage) === 'third_place');
  if (thirdMatch && !final.find(m => m.id === thirdMatch.id)) {
    final.push(thirdMatch);
  }

  return {
    left: Object.values(left).sort((a, b) => b.matches.length - a.matches.length),
    right: Object.values(right).sort((a, b) => a.matches.length - b.matches.length),
    final: final.sort((a, b) => (a.bracketPosition || 0) - (b.bracketPosition || 0))
  };
});

const publicUrl = computed(() => window.location.origin + route.path);
const qrCodeUrl = computed(() => `https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=${encodeURIComponent(publicUrl.value)}&color=e9c349&bgcolor=111111`);

const thirdPlaceMatch = computed(() => matches.value.find(x => normalizeStageKey(x.stage) === 'third_place'))

const hasActiveTabContent = computed(() => {
  if (loading.value || error.value || !tournament.value) return false
  if (activeTab.value === 'matches') return matches.value.length > 0
  if (activeTab.value === 'standings') {
    return Object.keys(normalizedClassification.value).length > 0 &&
           Object.values(normalizedClassification.value).flat().length > 0
  }
  if (activeTab.value === 'bracket') return bracketMatches.value.length > 0
  if (activeTab.value === 'teams') return (tournament.value.teams?.length || 0) > 0
  return false
})

const handleTabChange = (tab) => {
  console.log('Changing tab to:', tab)
  activeTab.value = tab
}


const fetchTournament = async () => {
  loading.value = true
  error.value = null
  try {
    const data = await tournamentService.getTournament(route.params.uuid)
    tournament.value = data
    if (data.matches) {
      matches.value = data.matches.map(m => ({
        id: m.id,
        status: m.status || (m.scoreA > 0 || m.scoreB > 0 ? 'finished' : 'pending'),
        teamA: m.teamA,
        scoreA: m.scoreA,
        teamB: m.teamB,
        scoreB: m.scoreB,
        stage: m.stage,
        bracketRound: m.bracketRound,
        bracketPosition: m.bracketPosition,
        matchDay: m.matchDay,
        deadlineDate: m.deadlineDate,
        mesa: m.mesa,
        games: m.games || []
      }))
    }
    
    if (data.type === 'groups' || data.type === 'league') {
       classification.value = await tournamentService.getClassification(route.params.uuid)
    }

    // Set default tab for different tournament types
    if (data.type === 'eliminatory' && activeTab.value === 'matches') {
       activeTab.value = 'bracket'
    } else if ((data.type === 'league' || data.type === 'groups') && activeTab.value === 'matches') {
       activeTab.value = 'standings'
    }
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchTournament();
  
  // Subscribe to Mercure real-time updates
  subscribe(`tournament/${route.params.uuid}`, async (data) => {
    if (data.matchId) {
      const matchId = Number(data.matchId);
      const match = matches.value.find(m => Number(m.id) === matchId);
      if (match) {
        match.scoreA = data.score1;
        match.scoreB = data.score2;
        match.status = data.finished ? 'finished' : 'pending';
        match.games = data.games || [];
      }
      // Refresh tournament data silently if bracket advancement or standings changed
      if (data.bracketRound || tournament.value?.type === 'eliminatory') {
        const updated = await tournamentService.getTournament(route.params.uuid);
        if (updated?.matches) {
          matches.value = updated.matches.map(m => ({
            id: m.id,
            status: m.status || (m.scoreA > 0 || m.scoreB > 0 ? 'finished' : 'pending'),
            teamA: m.teamA,
            scoreA: m.scoreA,
            teamB: m.teamB,
            scoreB: m.scoreB,
            stage: m.stage,
            bracketRound: m.bracketRound,
            bracketPosition: m.bracketPosition,
            matchDay: m.matchDay,
            deadlineDate: m.deadlineDate,
            mesa: m.mesa,
            games: m.games || []
          }));
        }
      }
      if (tournament.value?.type === 'groups' || tournament.value?.type === 'league') {
        classification.value = await tournamentService.getClassification(route.params.uuid);
      }
    }
  });

  document.addEventListener('fullscreenchange', () => {
    isFullscreen.value = !!document.fullscreenElement;
  });
});

// Administrative functions removed - moved to TournamentAdminView.vue

const openPoster = () => {
  const path = tournament.value?.posterPath || '/vertical.png';
  if (path.toLowerCase().endsWith('.pdf')) {
    window.open(path, '_blank');
  } else {
    showPosterDialog.value = true;
  }
}

const editingMatch = ref(null);
const editScore1 = ref(0);
const editScore2 = ref(0);

const openEditModal = (match) => {
  editingMatch.value = match;
  editScore1.value = match.scoreA || 0;
  editScore2.value = match.scoreB || 0;
};

const adjustScore = (team, delta) => {
  if (team === 1) {
    editScore1.value = Math.max(0, Math.min(tournament.value.ruleGames || 40, editScore1.value + delta));
  } else {
    editScore2.value = Math.max(0, Math.min(tournament.value.ruleGames || 40, editScore2.value + delta));
  }
};

const saveMatchResult = async () => {
  if (!editingMatch.value) return;
  isSavingResult.value = true;
  try {
    await tournamentService.updateMatch(editingMatch.value.id, {
      score1: editScore1.value,
      score2: editScore2.value
    });
    // Update local state
    editingMatch.value.scoreA = editScore1.value;
    editingMatch.value.scoreB = editScore2.value;
    editingMatch.value.status = (editScore1.value > 0 || editScore2.value > 0) ? t('tournament_view.match_status.finished') : t('tournament_view.match_status.pending');
    
    // Refresh classification if needed
    if (tournament.value.type === 'groups' || tournament.value.type === 'league') {
       classification.value = await tournamentService.getClassification(route.params.uuid);
    }
    
    editingMatch.value = null;
    toast.add({ severity: 'success', summary: t('common.success'), detail: t('tournament_view.match_edit.save_success'), life: 3000 });
    await fetchTournament();
  } catch (e) {
    console.error('Error saving result:', e);
  } finally {
    isSavingResult.value = false;
  }
};
</script>

<template>
  <div class="grid">
    <!-- Loading State -->
    <div v-if="loading" class="col-12 py-8">
       <MusLoader />
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="col-12 max-w-2xl mx-auto mt-8">
       <Card class="mus-glass border-rose-500/10 text-center p-8">
          <template #title>
             <h2 class="text-3xl font-black text-main italic uppercase tracking-tight">{{ t('tournament_view.restricted_access') }}</h2>
          </template>
          <template #content>
             <p class="text-slate-500 font-medium mb-6">{{ error }}</p>
             <Button :label="t('nav.dashboard')" icon="pi pi-arrow-left" @click="router.push('/dashboard')" class="p-button-secondary" />
          </template>
       </Card>
    </div>

    <!-- Main Tournament View -->
    <template v-else-if="tournament">
      <!-- Tournament Header Card -->
      <div class="col-12 mb-6">
        <div class="card p-4 md:p-5 mus-glass border-white/5 relative overflow-hidden">
          <!-- Draft Banner -->
          <div v-if="tournament.status === 'draft'" class="absolute inset-0 z-0 flex items-center justify-center pointer-events-none opacity-5 overflow-hidden">
             <div class="rotate-[-15deg] text-[150px] font-black whitespace-nowrap uppercase tracking-[0.2em]">
                {{ t('tournament_view.draft_mode') }}
             </div>
          </div>
          <div class="absolute top-0 right-0 w-1/2 h-full bg-gradient-to-l from-[#e9c349]/5 to-transparent"></div>
          
          <div class="relative z-10 flex flex-column md:flex-row justify-content-between align-items-center gap-6">
            <div class="flex-1">
              <div class="flex align-items-center gap-2 mb-2">
                <Tag :value="t('tournament_form.statuses.' + tournament.status)" severity="success" class="italic font-black uppercase text-[8px] tracking-widest" />
                <Tag v-if="tournament.private" :value="t('tournament_form.labels.private')" severity="danger" class="italic font-black uppercase text-[8px] tracking-widest" />
              </div>
              <h1 class="text-3xl md:text-4xl font-black text-main tracking-tight italic uppercase leading-tight m-0">{{ tournament.name }}</h1>
              <p class="text-slate-500 text-xs font-bold uppercase tracking-widest m-0 flex flex-wrap align-items-center gap-4">
                <span @click="openPoster" class="cursor-pointer hover:text-main transition-colors flex align-items-center gap-2">
                  <i class="pi pi-eye text-[#e9c349]"></i>
                  {{ t('dashboard.poster') }}
                </span>
                <router-link v-if="isManager" :to="`/admin/tournament/${uuid}/edit`" class="cursor-pointer hover:text-main transition-colors flex align-items-center gap-2 no-underline text-slate-500">
                  <i class="pi pi-pencil text-[#e9c349]"></i>
                  {{ t('common.edit') }}
                </router-link>
                <span><i class="pi pi-user mr-2 text-[#e9c349]"></i>{{ t('tournament_view.pairs_count', { count: tournament.teamsCount || 0 }) }}</span>
                <span><i class="pi pi-calendar mr-2 text-[#e9c349]"></i>{{ tournament.startDate ? new Date(tournament.startDate).toLocaleDateString() : '...' }} - {{ tournament.endDate ? new Date(tournament.endDate).toLocaleDateString() : '...' }}</span>
                <span v-if="tournament.location"><i class="pi pi-map-marker mr-2 text-[#e9c349]"></i>{{ tournament.location }}</span>
              </p>
            </div>

            <div class="flex flex-column md:flex-row align-items-center gap-4">
              <div class="flex gap-4 align-items-center bg-white/5 p-3 rounded-2xl border border-white/5">
                  <div class="rule-icon-item" v-tooltip.top="t('tournament_form.labels.ruleKings') + ': ' + tournament.ruleKings">
                    <i class="pi pi-crown text-xl text-[#e9c349]"></i>
                    <span class="text-xs font-black ml-2">{{ tournament.ruleKings }}</span>
                  </div>
                  <div class="rule-icon-item border-l border-white/10 pl-4" v-tooltip.top="t('tournament_form.labels.rulePoints') + ': ' + (tournament?.rulePoints || 40)">
                    <i class="pi pi-hashtag text-xl text-[#e9c349]"></i>
                    <span class="text-xs font-black ml-2">{{ tournament?.rulePoints || 40 }}</span>
                  </div>
                  <div class="rule-icon-item border-l border-white/10 pl-4" v-tooltip.top="t('tournament_form.labels.ruleGames') + ': ' + (tournament?.ruleGames || 3)">
                    <i class="pi pi-bolt text-xl text-[#e9c349]"></i>
                    <span class="text-xs font-black ml-2">{{ tournament?.ruleGames || 3 }}</span>
                  </div>
              
              <button v-if="isManager" @click="router.push(`/tournament/${uuid}/manage`)" 
                      class="mus-btn-gold px-4 py-2 flex align-items-center gap-2">
                <i class="pi pi-cog"></i>
                <span class="text-[10px] font-black uppercase tracking-widest">{{ t('dashboard.manage') }}</span>
              </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Google Ad Slot (Top) -->
      <div v-if="hasActiveTabContent" class="col-12 px-4">
        <GoogleAd :key="'top-' + activeTab" />
      </div>

      <!-- Main Content Container -->
      <div :class="showActivity ? 'col-12 lg:col-8' : 'col-12'">
        <div class="card p-3 md:p-6 mus-glass border-white/5">
           <div class="flex justify-content-between align-items-center mb-2 border-b border-white/5 pb-4">
              <div class="flex gap-4">
                <button v-if="tournament.type === 'league' || tournament.type === 'groups'" @click="activeTab = 'standings'" 
                        class="mus-tab-btn" :class="{ active: activeTab === 'standings' }">
                  {{ t('tournament_view.tabs.standings') }}
                </button>
                <button @click="activeTab = 'matches'" 
                        class="mus-tab-btn" :class="{ active: activeTab === 'matches' }">
                  {{ t('tournament_view.tabs.matches') }}
                </button>
                <button v-if="tournament.type === 'eliminatory' || tournament.type === 'groups'" @click="activeTab = 'bracket'" 
                        class="mus-tab-btn" :class="{ active: activeTab === 'bracket' }">
                  {{ t('tournament_view.tabs.bracket') }}
                </button>
                <button @click="activeTab = 'teams'" 
                        class="mus-tab-btn" :class="{ active: activeTab === 'teams' }">
                  {{ t('tournament_view.tabs.teams') }}
                </button>
              </div>

              <!-- Controls Button at tab level -->
              <div class="flex align-items-center gap-3">
                <button v-if="activeTab === 'standings' && (tournament.type === 'league' || tournament.type === 'groups') && Object.keys(normalizedClassification).length > 0"
                        type="button"
                        @click="scrollToTiebreaker"
                        v-tooltip.top="t('tournament_view.classification.tiebreaker.tooltip')"
                        :title="t('tournament_view.classification.tiebreaker.tooltip')"
                        style="width: 30px; height: 30px; min-width: 30px; border-radius: 50%;"
                        class="bg-[#e9c349]/15 hover:bg-[#e9c349]/30 border border-[#e9c349]/40 text-[#e9c349] flex align-items-center justify-content-center transition-all cursor-pointer p-0">
                  <i class="pi pi-info-circle text-sm"></i>
                </button>
                <div v-if="activeTab === 'bracket' && bracketMatches.length > 0" class="flex align-items-center gap-2 mr-2">
                   <i class="pi pi-search-minus text-slate-500 text-[10px]"></i>
                   <input type="range" v-model="zoomLevel" min="0.1" max="1.5" step="0.05" class="w-20 accent-secondary" />
                   <i class="pi pi-search-plus text-slate-500 text-[10px]"></i>
                   <span class="text-[9px] font-bold text-secondary ml-1">{{ Math.round(zoomLevel * 100) }}%</span>
                </div>
                <button v-if="activeTab === 'bracket' && bracketMatches.length > 0" 
                        @click="toggleFullscreen" 
                        class="mus-btn-gold px-3 py-1.5 flex align-items-center gap-2">
                  <i class="pi" :class="isFullscreen ? 'pi-window-minimize' : 'pi-external-link'"></i>
                  <span class="text-[9px] font-black uppercase">{{ isFullscreen ? $t('tournament_view.exit') : $t('tournament_view.tv_mode') }}</span>
                </button>
              </div>
           </div>

           <div v-if="activeTab === 'matches'" class="matches-container">
              <div class="grid">
                <!-- Highlighted Final Section (Horizontal) -->
                <div v-if="tournament.type === 'eliminatory' && (splitBracketMatches.final.length > 0 || thirdPlaceMatch)" class="col-12 mb-10">
                   <div class="grid">
                       <!-- Final Match -->
                       <div v-for="match in splitBracketMatches.final.filter(m => m.bracketPosition === 0)" :key="'final-'+match.id" 
                            :class="tournament.hasThirdPlace ? 'col-12 md:col-6 mb-4' : 'col-12 mb-4'">
                          <div class="mus-table-wrapper h-full rounded-2xl overflow-hidden border border-[#e9c349]/30 bg-[#e9c349]/5 p-1 shadow-[0_0_30px_rgba(233,195,73,0.1)]">
                             <table class="w-full h-full text-left border-collapse bg-slate-900/80 rounded-xl">
                                <tbody>
                                   <tr class="group">
                                      <td class="p-4 w-7/12">
                                         <div class="flex flex-column gap-3">
                                            <div class="flex align-items-center gap-3">
                                               <div class="w-7 h-7 flex-shrink-0 rounded-full bg-secondary border border-white/20 flex align-items-center justify-center text-[10px] font-black text-black">1</div>
                                               <span class="text-main font-black italic text-xl truncate tracking-tight uppercase">{{ match.teamA?.name || match.teamA || t('tournament_admin.matches.tbd') }}</span>
                                            </div>
                                            <div class="flex align-items-center gap-3">
                                               <div class="w-7 h-7 flex-shrink-0 rounded-full bg-secondary border border-white/20 flex align-items-center justify-center text-[10px] font-black text-black">2</div>
                                               <span class="text-main font-black italic text-xl truncate tracking-tight uppercase">{{ match.teamB?.name || match.teamB || t('tournament_admin.matches.tbd') }}</span>
                                            </div>
                                         </div>
                                      </td>
                                      <td class="p-4 w-2/12 border-l border-white/5 align-middle text-center">
                                         <div class="flex flex-column gap-3">
                                            <span class="text-3xl font-black italic" :class="match.scoreA > match.scoreB ? 'text-secondary' : 'text-slate-600'">{{ match.scoreA }}</span>
                                            <span class="text-3xl font-black italic" :class="match.scoreB > match.scoreA ? 'text-secondary' : 'text-slate-600'">{{ match.scoreB }}</span>
                                         </div>
                                      </td>
                                      <td class="p-4 w-3/12 border-l border-white/5 align-middle text-center">
                                         <div class="flex flex-column align-items-center justify-center gap-4">
                                            <Tag :value="$t('tournament_view.grand_final')" severity="warning" class="text-[10px] font-black italic uppercase tracking-widest px-4 py-2" />
                                            <button v-if="tournament.isManager" @click="router.push(`/tournament/${route.params.uuid}/manage`)" class="text-[9px] font-black text-secondary uppercase border border-secondary/30 px-3 py-1.5 rounded bg-secondary/5 hover:bg-secondary/20 transition-all">
                                               {{ t('dashboard.manage') }}
                                            </button>
                                         </div>
                                      </td>
                                   </tr>
                                </tbody>
                             </table>
                          </div>
                       </div>

                       <!-- 3rd Place -->
                       <div v-for="match in matches.filter(m => normalizeStageKey(m.stage) === 'third_place')" :key="'third-'+match.id" 
                            :class="tournament.hasThirdPlace ? 'col-12 md:col-6 mb-4' : 'col-12 mb-4'">
                          <div class="mus-table-wrapper h-full rounded-2xl overflow-hidden border border-white/10 bg-white/5 p-1">
                             <table class="w-full h-full text-left border-collapse bg-slate-900/50 rounded-xl">
                                <tbody>
                                   <tr class="group">
                                      <td class="p-4 w-7/12">
                                         <div class="flex flex-column gap-3">
                                            <div class="flex align-items-center gap-3">
                                               <div class="w-5 h-5 flex-shrink-0 rounded bg-slate-800 border border-white/10 flex align-items-center justify-center text-[8px] font-black">A</div>
                                               <span class="text-main font-black italic text-lg truncate uppercase">{{ match.teamA?.name || match.teamA || t('tournament_admin.matches.tbd') }}</span>
                                            </div>
                                            <div class="flex align-items-center gap-3">
                                               <div class="w-5 h-5 flex-shrink-0 rounded bg-slate-800 border border-white/10 flex align-items-center justify-center text-[8px] font-black">B</div>
                                               <span class="text-main font-black italic text-lg truncate uppercase">{{ match.teamB?.name || match.teamB || t('tournament_admin.matches.tbd') }}</span>
                                            </div>
                                         </div>
                                      </td>
                                      <td class="p-4 w-2/12 border-l border-white/5 align-middle text-center">
                                         <div class="flex flex-column gap-3">
                                            <span class="text-2xl font-black italic" :class="match.scoreA > match.scoreB ? 'text-main' : 'text-slate-600'">{{ match.scoreA }}</span>
                                            <span class="text-2xl font-black italic" :class="match.scoreB > match.scoreA ? 'text-main' : 'text-slate-600'">{{ match.scoreB }}</span>
                                         </div>
                                      </td>
                                      <td class="p-4 w-3/12 border-l border-white/5 align-middle text-center">
                                         <div class="flex flex-column align-items-center justify-center gap-3">
                                            <Tag :value="t('tournament_view.knockout.third_place')" severity="secondary" class="text-[9px] font-black uppercase" />
                                            <button v-if="tournament.isManager" @click="router.push(`/tournament/${route.params.uuid}/manage`)" class="text-[9px] font-black text-slate-400 uppercase border border-white/10 px-3 py-1 rounded bg-white/5 hover:bg-white/10 transition-all">
                                               {{ t('dashboard.manage') }}
                                            </button>
                                         </div>
                                      </td>
                                   </tr>
                                </tbody>
                             </table>
                          </div>
                       </div>
                    </div>
                </div>

                <!-- Regular Stage Matches -->
                 <div v-for="([stage, stageMatches]) in groupedMatches" :key="stage" class="col-12 mb-8">
                     <div class="flex align-items-center justify-content-between mb-2">
                        <h4 class="text-[#e9c349] font-black italic uppercase tracking-[0.2em] m-0 flex align-items-center gap-2 text-xs">
                           <i class="pi pi-calendar"></i>
                           <template v-if="stage.startsWith('matchDay_')">
                             <span>
                               {{ getStageLabel(stage) }}
                             </span>
                             <span v-if="stageMatches[0]?.deadlineDate" class="ml-2 text-slate-500 font-bold tracking-normal lowercase opacity-70">
                               - [{{ formatFullDate(stageMatches[0].deadlineDate) }}]
                             </span>
                           </template>
                           <template v-else>
                             {{ getStageLabel(stage) }}
                           </template>
                        </h4>
                        <div v-if="stageMatches[0]?.deadlineDate" class="flex align-items-center gap-2 bg-rose-500/10 border border-rose-500/20 px-3 py-1 rounded-full">
                           <span class="text-[9px] font-black text-rose-400 uppercase tracking-widest">{{ t('common.deadline') }}</span>
                           <span class="text-[10px] font-black text-white italic">{{ formatDate(stageMatches[0].deadlineDate) }}</span>
                        </div>
                     </div>

                    <!-- Resting Teams Info -->
                    <div v-if="restingTeamsByDay[stage]" class="flex flex-wrap gap-2 mb-4">
                       <div v-for="team in restingTeamsByDay[stage]" :key="'rest-'+team.id" 
                            class="bg-[#e9c349]/10 border border-[#e9c349]/20 rounded-xl px-3 py-2 flex align-items-center gap-2">
                          <i class="pi pi-info-circle text-[#e9c349] text-xs"></i>
                          <span class="text-[9px] font-black text-slate-400 uppercase tracking-wider">{{ t('tournament_view.match_status.resting') || 'Descansa' }}:</span>
                          <span class="text-[10px] font-black text-white uppercase italic">{{ team.name }}</span>
                       </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1rem;" class="mt-4">
                       <div v-for="(match, i) in stageMatches" :key="i">
                         <div class="bg-white/5 border border-white/10 rounded-2xl p-3 hover:bg-white/10 transition-all h-full">
                            <!-- Mini Header for Status -->
                            <div class="flex justify-content-end mb-2 border-bottom border-white/5 pb-1">
                               <Tag :value="t('tournament_view.match_status.' + (match.status || 'pending'))" 
                                    :severity="match.status === 'finished' ? 'success' : 'secondary'" 
                                    class="text-[7px] font-black uppercase whitespace-nowrap px-2 py-0.5" />
                            </div>

                            <div class="flex flex-column gap-2">
                               <!-- Team A -->
                               <div class="flex align-items-center gap-2">
                                  <div style="width: 18px; height: 18px; flex-shrink: 0;" class="rounded bg-slate-800 border border-white/10 flex align-items-center justify-center text-[7px] font-black opacity-30">A</div>
                                  <span class="text-main font-black italic text-sm truncate uppercase flex-1" :class="{'opacity-50': match.status === 'finished' && match.scoreB > match.scoreA}">
                                     {{ match.teamA?.name || match.teamA || t('tournament_admin.matches.tbd') }}
                                  </span>
                                  <span class="text-lg font-black italic ml-2" :class="match.scoreA > match.scoreB ? 'text-[#e9c349]' : 'text-slate-600'">{{ match.scoreA }}</span>
                               </div>
                               <!-- Team B -->
                               <div class="flex align-items-center gap-2">
                                  <div style="width: 18px; height: 18px; flex-shrink: 0;" class="rounded bg-slate-800 border border-white/10 flex align-items-center justify-center text-[7px] font-black opacity-30">B</div>
                                  <span class="text-main font-black italic text-sm truncate uppercase flex-1" :class="{'opacity-50': match.status === 'finished' && (match.scoreA > match.scoreB || !match.teamB)}">
                                     {{ match.teamB?.name || match.teamB || (match.teamA && match.status === 'finished' ? 'BYE' : t('tournament_admin.matches.tbd')) }}
                                  </span>
                                  <span class="text-lg font-black italic ml-2" :class="match.scoreB > match.scoreA ? 'text-[#e9c349]' : 'text-slate-600'">{{ match.scoreB }}</span>
                               </div>
                            </div>
                         </div>
                       </div>
                    </div>
                 </div>
               </div>
              <!-- Inner Ad -->
              <GoogleAd v-if="matches.length > 0" :key="'inner-' + activeTab" />
           </div>

           <div v-else-if="activeTab === 'standings'" class="classification-container">
              <!-- Qualification Legend Banner for Groups Tournaments -->
              <div v-if="tournament.type === 'groups' && Object.keys(normalizedClassification).length > 0"
                   class="mb-6 p-4 rounded-2xl bg-emerald-500/[0.07] border border-emerald-500/25 flex flex-wrap align-items-center justify-content-between gap-3">
                <div class="flex align-items-center gap-3">
                  <span style="width: 10px; height: 10px; min-width: 10px; border-radius: 50%;" class="bg-emerald-400 shadow-[0_0_10px_#34d399] inline-block"></span>
                  <span class="text-xs font-black uppercase tracking-wider text-white">
                    {{ t('tournament_view.classification.qual_zone_label') }}
                    <span class="text-emerald-400">
                      {{ teamsPerGroup === 1 ? t('tournament_view.classification.qual_zone_single') : t('tournament_view.classification.qual_zone_multi', { count: teamsPerGroup }) }}
                    </span>
                  </span>
                </div>
                <span class="px-3 py-1 rounded-full bg-emerald-500/20 border border-emerald-500/30 text-emerald-300 text-[10px] font-black uppercase tracking-widest">
                  {{ t('tournament_view.classification.total_final_bracket', { count: Object.keys(classification || {}).length * teamsPerGroup }) }}
                </span>
              </div>

              <div v-for="(teams, mesaName) in (tournament.type === 'league' ? { 'General': Object.values(classification).flat() } : classification)" :key="mesaName" class="mb-10">
                <div class="flex align-items-center justify-content-between mb-4">
                  <div class="flex align-items-center gap-2">
                    <h4 class="text-[#e9c349] font-black italic uppercase tracking-widest m-0 flex items-center gap-2">
                      <i class="pi pi-table"></i> {{ mesaName === 'General' ? t('tournament_view.rounds.general') : getStageLabel(mesaName) }}
                    </h4>
                    <button type="button"
                            @click="scrollToTiebreaker"
                            v-tooltip.top="t('tournament_view.classification.tiebreaker.tooltip')"
                            :title="t('tournament_view.classification.tiebreaker.tooltip')"
                            style="width: 24px; height: 24px; min-width: 24px; border-radius: 50%;"
                            class="bg-[#e9c349]/15 hover:bg-[#e9c349]/30 border border-[#e9c349]/40 text-[#e9c349] inline-flex align-items-center justify-content-center transition-all cursor-pointer p-0">
                      <i class="pi pi-info-circle text-xs"></i>
                    </button>
                  </div>
                  <span v-if="tournament.type === 'groups'"
                        class="px-3 py-1 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-[10px] font-black uppercase tracking-widest flex align-items-center gap-2">
                    <i class="pi pi-check-circle text-[10px]"></i>
                    {{ teamsPerGroup === 1 ? t('tournament_view.classification.group_qual_single') : t('tournament_view.classification.group_qual_multi', { count: teamsPerGroup }) }}
                  </span>
                </div>
                <div class="mus-table-wrapper rounded-2xl overflow-hidden border border-white/5 bg-white/5 overflow-x-auto">
                  <table class="w-full text-left border-collapse min-w-[800px]">
                    <thead>
                      <tr class="bg-white/5 text-[10px] uppercase font-black tracking-widest text-slate-500">
                        <th class="p-4 w-[60px] text-center border-r border-white/5">#</th>
                        <th class="p-4 min-w-[200px]">{{ t('tournament_view.classification.team') }}</th>
                        <th class="p-4 w-[60px] text-center border-l border-white/5" :title="t('tournament_view.classification.played_title')">{{ t('tournament_view.classification.played') }}</th>
                        <th class="p-4 w-[60px] text-center" :title="t('tournament_view.classification.mw_title')">{{ t('tournament_view.classification.mw') }}</th>
                        <th class="p-4 w-[60px] text-center" :title="t('tournament_view.classification.ml_title')">{{ t('tournament_view.classification.ml') }}</th>
                        <th class="p-4 w-[60px] text-center bg-white/[0.03]" :title="t('tournament_view.classification.vw_title')">{{ t('tournament_view.classification.vw') }}</th>
                        <th class="p-4 w-[60px] text-center bg-white/[0.03]" :title="t('tournament_view.classification.vl_title')">{{ t('tournament_view.classification.vl') }}</th>
                        <th class="p-4 w-[80px] text-center italic" :title="t('tournament_view.classification.vdiff_title')">{{ t('tournament_view.classification.vdiff') }}</th>
                        <th class="p-4 w-[80px] text-center" :title="t('tournament_view.classification.tf_title')">{{ t('tournament_view.classification.tf') }}</th>
                        <th class="p-4 w-[80px] text-center" :title="t('tournament_view.classification.tc_title')">{{ t('tournament_view.classification.tc') }}</th>
                        <th class="p-4 w-[80px] text-center italic" :title="t('tournament_view.classification.tdiff_title')">{{ t('tournament_view.classification.tdiff') }}</th>
                        <th class="p-4 w-[80px] text-center text-main bg-white/[0.05]" :title="t('tournament_view.classification.pts_title')">{{ t('tournament_view.classification.pts') }}</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="(team, index) in teams" :key="team.teamName"
                          class="border-t border-white/5 transition-colors"
                          :class="[
                            tournament.type === 'groups' && index < teamsPerGroup
                              ? 'bg-emerald-500/[0.09] hover:bg-emerald-500/[0.15] border-l-4 border-l-emerald-400'
                              : 'hover:bg-white/[0.03]',
                            tournament.type === 'groups' && index === teamsPerGroup - 1
                              ? 'border-b-2 border-b-emerald-500/40'
                              : ''
                          ]">
                        <td class="p-4 w-[60px] text-center border-r border-white/5">
                           <span v-if="tournament.type === 'groups'"
                                 style="width: 28px; height: 28px; min-width: 28px; border-radius: 50%;"
                                 class="inline-flex align-items-center justify-content-center text-xs font-black"
                                 :class="index < teamsPerGroup ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/40' : 'text-slate-500'">
                             {{ index + 1 }}
                           </span>
                           <span v-else class="text-xs font-black" :class="index < 3 ? 'text-[#e9c349]' : 'text-slate-500'">{{ index + 1 }}</span>
                        </td>
                        <td class="p-4 min-w-[200px] font-black uppercase italic text-sm">
                          <div class="flex align-items-center justify-content-between gap-2">
                            <span :class="tournament.type === 'groups' && index < teamsPerGroup ? 'text-emerald-300' : 'text-main'">
                              {{ team.teamName }}
                            </span>
                            <span v-if="tournament.type === 'groups' && index < teamsPerGroup"
                                  class="px-2 py-0.5 rounded-md bg-emerald-500/20 border border-emerald-500/30 text-emerald-300 text-[9px] font-black uppercase tracking-wider not-italic whitespace-nowrap">
                              {{ t('tournament_view.classification.qualified_badge') }}
                            </span>
                          </div>
                        </td>
                        <td class="p-4 w-[60px] text-center text-slate-500 font-bold border-l border-white/5">{{ team.matchesPlayed }}</td>
                        <td class="p-4 w-[60px] text-center font-black text-emerald-400">{{ team.matchesWon }}</td>
                        <td class="p-4 w-[60px] text-center font-bold text-rose-400/50">{{ team.matchesLost }}</td>
                        <td class="p-4 w-[60px] text-center font-bold text-emerald-400/70 bg-white/[0.01]">{{ team.vacasWon }}</td>
                        <td class="p-4 w-[60px] text-center font-bold text-rose-400/70 bg-white/[0.01]">{{ team.vacasLost }}</td>
                        <td class="p-4 w-[80px] text-center font-black italic" :class="team.vacasDiff >= 0 ? 'text-emerald-500' : 'text-rose-500'">{{ team.vacasDiff > 0 ? '+' : '' }}{{ team.vacasDiff }}</td>
                        <td class="p-4 w-[80px] text-center font-bold text-slate-400">{{ team.tantosFavor }}</td>
                        <td class="p-4 w-[80px] text-center font-bold text-slate-600">{{ team.tantosContra }}</td>
                        <td class="p-4 w-[80px] text-center font-black italic" :class="team.tantosDiff >= 0 ? 'text-emerald-400' : 'text-rose-400'">{{ team.tantosDiff > 0 ? '+' : '' }}{{ team.tantosDiff }}</td>
                        <td class="p-4 w-[80px] text-center font-black text-[#e9c349] bg-white/[0.02]">{{ team.matchesWon }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
              <div v-if="Object.keys(normalizedClassification).length === 0" class="text-center py-10 opacity-30">
                <p class="font-bold">{{ t('tournament_view.classification.empty') }}</p>
              </div>

              <!-- Tie-breaker & Ranking Criteria Info Box (for League and Groups tournaments) -->
              <div v-if="(tournament.type === 'league' || tournament.type === 'groups') && Object.keys(normalizedClassification).length > 0"
                   id="tiebreaker-info"
                   class="mt-8 p-6 rounded-2xl transition-all duration-500"
                   :class="highlightTiebreaker ? 'bg-[#e9c349]/10 border-2 border-[#e9c349] shadow-[0_0_25px_rgba(233,195,73,0.25)]' : 'bg-white/[0.03] border border-white/10'">
                <div class="flex align-items-center gap-3 mb-2">
                  <div style="width: 32px; height: 32px; min-width: 32px; border-radius: 50%;"
                       class="bg-[#e9c349]/15 border border-[#e9c349]/30 flex align-items-center justify-content-center text-[#e9c349]">
                    <i class="pi pi-info-circle text-sm"></i>
                  </div>
                  <div>
                    <h5 class="m-0 text-sm font-black italic uppercase tracking-wider text-[#e9c349]">
                      {{ t('tournament_view.classification.tiebreaker.title') }}
                    </h5>
                    <p class="m-0 text-[11px] text-slate-400 font-bold">
                      {{ t('tournament_view.classification.tiebreaker.subtitle') }}
                    </p>
                  </div>
                </div>

                <div class="grid mt-3">
                  <div v-for="step in [1, 2, 3, 4, 5, 6]" :key="'tb-' + step" class="col-12 md:col-6 lg:col-4 p-2">
                    <div class="p-3 rounded-xl bg-black/30 border border-white/5 h-full flex align-items-start gap-3">
                      <span style="width: 24px; height: 24px; min-width: 24px; border-radius: 50%;"
                            class="bg-[#e9c349]/20 border border-[#e9c349]/40 text-[#e9c349] text-[11px] font-black flex align-items-center justify-content-center mt-0.5">
                        {{ step }}
                      </span>
                      <div>
                        <div class="text-xs font-black text-white uppercase italic mb-1">
                          {{ t(`tournament_view.classification.tiebreaker.rule${step}_title`) }}
                        </div>
                        <p class="m-0 text-[11px] text-slate-400 font-medium leading-relaxed">
                          {{ t(`tournament_view.classification.tiebreaker.rule${step}_desc`) }}
                        </p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
           </div>

            <div v-else-if="activeTab === 'bracket'" class="py-8">
               <div v-if="bracketMatches.length === 0" class="text-center py-20 opacity-30">
                 <div class="w-24 h-24 bg-white/5 rounded-full flex items-center justify-center mx-auto mb-6 border border-white/10">
                   <i class="pi pi-sitemap text-5xl"></i>
                 </div>
                 <h3 class="text-2xl font-black italic uppercase tracking-tighter">{{ t('tournament_view.bracket.title') }}</h3>
                 <p class="text-slate-500 font-bold uppercase text-[10px] tracking-[0.3em]">{{ t('tournament_view.bracket.empty_desc') }}</p>
               </div>
               
               <div v-else class="bracket-viewport" ref="bracketContainer" :class="{ 'is-fullscreen': isFullscreen }">
                  <!-- Fullscreen Header (Only visible in FS) -->
                  <div v-if="isFullscreen" class="flex justify-content-between align-items-center mb-10 px-4 fs-controls">
                     <div class="flex align-items-center gap-5">
                        <img src="/logo.png" class="h-14" alt="Logo" />
                         <div>
                            <h2 class="text-4xl font-black text-main italic uppercase tracking-tighter m-0">{{ tournament.name }}</h2>
                            <div class="text-[12px] font-bold text-secondary uppercase tracking-[0.4em] mt-1">{{ t('tournament_view.bracket.live_subtitle') }}</div>
                         </div>

                         <!-- QR Code for Public Access -->
                         <div class="flex align-items-center gap-4 ml-12 bg-white/5 p-3 rounded-2xl border border-white/10">
                            <div class="text-right">
                               <div class="text-[9px] font-black text-secondary uppercase tracking-[0.2em] mb-1">{{ t('tournament_view.bracket.follow_tournament') }}</div>
                               <div class="text-[8px] font-bold text-slate-500 uppercase">{{ t('tournament_view.bracket.live_results') }}</div>
                            </div>
                            <div class="relative p-1 bg-white rounded-lg shadow-xl">
                               <img :src="qrCodeUrl" class="w-14 h-14 block" alt="QR Code" />
                            </div>
                         </div>
                     </div>
                      <div class="flex align-items-center gap-6">
                        <div class="flex align-items-center gap-3 bg-white/5 px-4 py-2 rounded-full border border-white/10">
                           <i class="pi pi-search-minus text-slate-400 text-xs"></i>
                           <input type="range" v-model="zoomLevel" min="0.1" max="1.5" step="0.05" class="w-32 accent-secondary" />
                           <i class="pi pi-search-plus text-slate-400 text-xs"></i>
                           <span class="text-xs font-bold text-secondary ml-1 min-w-[45px]">{{ Math.round(zoomLevel * 100) }}%</span>
                        </div>
                        <div class="text-right">
                           <div class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">{{ t('tournament_view.bracket.close_esc') }}</div>
                           <button @click="toggleFullscreen" class="mus-btn-secondary px-4 py-2 text-[10px] font-black uppercase">{{ t('tournament_view.exit') }}</button>
                        </div>
                     </div>
                  </div>

                  <div class="bracket-wrapper px-4 overflow-auto custom-scrollbar pb-10" 
                       :style="{ height: isFullscreen ? 'calc(100vh - 150px)' : '750px' }">
                    <!-- Symmetrical Championship Layout (Always Active) -->
                    <div class="flex transition-transform origin-top-left align-items-stretch" 
                         :style="{ transform: `scale(${zoomLevel * 0.9})`, width: 'max-content', minHeight: '100%' }">
                       
                       <!-- Left Wing (Converging L -> R) -->
                       <div class="flex flex-row gap-12">
                          <div v-for="(col, colIdx) in splitBracketMatches.left" :key="'left-'+col.round" 
                               class="bracket-round flex flex-column min-w-[300px] w-[320px]" style="min-height: 100%">
                             <div class="round-title mb-8 px-4 text-right">
                                <h4 class="m-0 font-black uppercase italic tracking-widest text-[11px] text-secondary">{{ translateStage(col.stageName) }}</h4>
                                <div class="text-[9px] font-bold text-slate-600 uppercase tracking-widest mt-1">{{ col.matches.length }} {{ t('common.matches') }}</div>
                             </div>
                             <div class="flex-grow-1 flex flex-column justify-content-around relative pb-4" style="min-height: 500px">
                                <div v-for="(m, i) in col.matches" :key="m.id" class="bracket-match-container relative flex flex-row">
                                   <div v-if="m.mesa" class="w-[24px] flex-shrink-0 bg-slate-900 border-y border-l border-white/10 rounded-l-xl flex align-items-center justify-center relative">
                                      <span class="text-[15px] font-black text-secondary uppercase tracking-tighter whitespace-nowrap absolute" style="width: 120px; text-align: center; left: 50%; top: 50%; transform: translate(-50%, -50%) rotate(-90deg);">
                                         {{ formatMesa(m.mesa) }}
                                      </span>
                                   </div>
                                    <div class="bracket-match-card flex-grow-1 mus-glass border-white/5 overflow-hidden">
                                       <div class="team-row-bracket flex justify-content-between align-items-center p-3 border-b border-white/5">
                                          <span class="text-xs font-black text-main uppercase italic truncate pr-2">{{ m.teamA?.name || m.teamA || t('tournament_admin.matches.tbd') }}</span>
                                          <span class="font-black italic text-lg ml-4">{{ m.scoreA }}</span>
                                       </div>
                                       <div class="team-row-bracket flex justify-content-between align-items-center p-3">
                                          <span class="text-xs font-black text-main uppercase italic truncate pr-2" :class="{ 'opacity-40': !m.teamB && m.status === 'finished' }">{{ m.teamB?.name || m.teamB || (m.teamA && m.status === 'finished' ? 'BYE' : t('tournament_admin.matches.tbd')) }}</span>
                                          <span class="font-black italic text-lg ml-4">{{ m.scoreB }}</span>
                                       </div>
                                    </div>
                                   <div v-if="colIdx < splitBracketMatches.left.length - 1" class="bracket-connector"></div>
                                </div>
                             </div>
                          </div>
                       </div>

                       <!-- Central Column: Final & 3rd Place -->
                       <div class="px-20 relative" style="min-width: 600px; display: grid; grid-template-rows: 1fr auto 1fr; min-height: 100%;">
                          
                          <!-- Top Spacer to ensure perfect centering of row 2 -->
                          <div style="grid-row: 1;"></div>

                          <!-- Row 2: GRAND FINAL (Centered) -->
                          <div style="grid-row: 2;" class="flex flex-column align-items-center gap-6 py-10">
                             <div v-for="m in splitBracketMatches.final.filter(f => f.bracketPosition === 0)" :key="'final-'+m.id" 
                                  class="flex flex-column align-items-center gap-6">
                                <div class="text-center">
                                   <div class="w-32 h-px bg-gradient-to-r from-transparent via-secondary to-transparent mx-auto mb-4"></div>
                                   <Tag :value="$t('tournament_view.grand_final')" severity="warning" class="font-black italic uppercase tracking-[0.4em] text-xl px-6 py-3" />
                                   <div class="w-32 h-px bg-gradient-to-r from-transparent via-secondary to-transparent mx-auto mt-4"></div>
                                </div>
                                <div class="bracket-match-card mus-glass border-secondary/50 w-[480px] shadow-2xl scale-110 bg-secondary/5">
                                   <div class="p-5 border-b border-white/10 flex justify-content-between align-items-center" :class="{ 'bg-secondary/10': m.scoreA > m.scoreB }">
                                      <span class="text-2xl font-black text-main italic uppercase truncate pr-4">{{ m.teamA?.name || m.teamA || t('tournament_view.bracket.finalist_a') }}</span>
                                      <span class="text-5xl font-black text-secondary">{{ m.scoreA }}</span>
                                   </div>
                                   <div class="p-5 flex justify-content-between align-items-center" :class="{ 'bg-secondary/10': m.scoreB > m.scoreA }">
                                      <span class="text-2xl font-black text-main italic uppercase truncate pr-4">{{ m.teamB?.name || m.teamB || t('tournament_view.bracket.finalist_b') }}</span>
                                      <span class="text-5xl font-black text-secondary">{{ m.scoreB }}</span>
                                   </div>
                                </div>
                             </div>
                          </div>

                          <!-- Row 3: 3rd PLACE (Directly below) -->
                          <div style="grid-row: 3;" class="flex flex-column align-items-center pt-12">
                             <div v-for="m in splitBracketMatches.final.filter(f => f.bracketPosition === 1)" :key="'third-'+m.id" 
                                  class="flex flex-column align-items-center gap-6">
                                <div class="text-center">
                                   <div class="w-24 h-px bg-gradient-to-r from-transparent via-white/20 to-transparent mx-auto mb-3"></div>
                                   <Tag :value="t('tournament_view.knockout.third_place')" severity="secondary" 
                                        class="font-black italic uppercase tracking-[0.3em] text-sm px-4 py-2" />
                                   <div class="w-24 h-px bg-gradient-to-r from-transparent via-white/20 to-transparent mx-auto mt-3"></div>
                                </div>
                                <div class="bracket-match-card mus-glass border-white/20 w-[480px] shadow-2xl scale-100">
                                   <div class="p-4 border-b border-white/10 flex justify-content-between align-items-center" :class="{ 'bg-white/5': m.scoreA > m.scoreB }">
                                      <span class="text-xl font-black text-main italic uppercase truncate pr-4">{{ m.teamA?.name || m.teamA || t('tournament_view.bracket.semifinalist_a') }}</span>
                                      <span class="text-4xl font-black text-slate-400">{{ m.scoreA }}</span>
                                   </div>
                                   <div class="p-4 flex justify-content-between align-items-center" :class="{ 'bg-white/5': m.scoreB > m.scoreA }">
                                      <span class="text-xl font-black text-main italic uppercase truncate pr-4">{{ m.teamB?.name || m.teamB || t('tournament_view.bracket.semifinalist_b') }}</span>
                                      <span class="text-4xl font-black text-slate-400">{{ m.scoreB }}</span>
                                   </div>
                                </div>
                             </div>
                          </div>
                       </div>

                       <!-- Right Wing (Converging L -> R) -->
                       <div class="flex flex-row gap-12">
                          <div v-for="(col, colIdx) in splitBracketMatches.right" :key="'right-'+col.round" 
                               class="bracket-round flex flex-column min-w-[300px] w-[320px]" style="min-height: 100%">
                             <div class="round-title mb-8 px-4">
                                <h4 class="m-0 font-black uppercase italic tracking-widest text-[11px] text-secondary">{{ translateStage(col.stageName) }}</h4>
                                <div class="text-[9px] font-bold text-slate-600 uppercase tracking-widest mt-1">{{ col.matches.length }} {{ t('common.matches') }}</div>
                             </div>
                             <div class="flex-grow-1 flex flex-column justify-content-around relative pb-4" style="min-height: 500px">
                                <div v-for="(m, i) in col.matches" :key="m.id" class="bracket-match-container relative right-wing flex flex-row">
                                   <div v-if="m.mesa" class="w-[24px] flex-shrink-0 bg-slate-900 border-y border-l border-white/10 rounded-l-xl flex align-items-center justify-center relative">
                                      <span class="text-[15px] font-black text-secondary uppercase tracking-tighter whitespace-nowrap absolute" style="width: 120px; text-align: center; left: 50%; top: 50%; transform: translate(-50%, -50%) rotate(-90deg);">
                                         {{ formatMesa(m.mesa) }}
                                      </span>
                                   </div>
                                   <div class="bracket-match-card flex-grow-1 mus-glass border-white/5 overflow-hidden">
                                      <div class="team-row-bracket flex justify-content-between align-items-center p-3 border-b border-white/5">
                                         <span class="text-xs font-black text-main uppercase italic truncate pr-2">{{ m.teamA?.name || m.teamA || t('tournament_admin.matches.tbd') }}</span>
                                         <span class="font-black italic text-lg ml-4">{{ m.scoreA }}</span>
                                      </div>
                                      <div class="team-row-bracket flex justify-content-between align-items-center p-3">
                                         <span class="text-xs font-black text-main uppercase italic truncate pr-2" :class="{ 'opacity-40': !m.teamB && m.status === 'finished' }">{{ m.teamB?.name || m.teamB || (m.teamA && m.status === 'finished' ? 'BYE' : t('tournament_admin.matches.tbd')) }}</span>
                                         <span class="font-black italic text-lg ml-4">{{ m.scoreB }}</span>
                                      </div>
                                   </div>
                                   <div v-if="colIdx > 0" class="bracket-connector right-connector"></div>
                                </div>
                             </div>
                          </div>
                       </div>
                    </div>
                  </div>
               </div>
            </div>

            <div v-else-if="activeTab === 'teams'">
               <div v-if="tournament.teams?.length" class="mus-table-wrapper rounded-2xl overflow-hidden border border-white/5 bg-white/5">
                  <table class="w-full text-left border-collapse">
                     <tbody>
                        <tr v-for="tt in tournament.teams" :key="tt.id" class="border-b border-white/10 last:border-0 hover:bg-white/5 transition-colors group">
                           <td class="p-4 w-16 text-center border-r border-white/5">
                              <div style="width: 40px; height: 40px; border-radius: 50%;" class="mx-auto bg-[#e9c349]/10 border border-[#e9c349]/20 flex align-items-center justify-center">
                                 <i class="pi pi-users text-[#e9c349]"></i>
                              </div>
                           </td>
                           <td class="p-4 font-bold text-main text-lg uppercase italic">
                              {{ tt.name }}
                           </td>
                           <td class="p-4 text-right w-1/3 border-l border-white/5">
                              <span class="text-slate-400 text-xs font-black uppercase tracking-widest">{{ tt.mesa ? getStageLabel(tt.mesa) : '-' }}</span>
                           </td>
                        </tr>
                     </tbody>
                  </table>
               </div>
               
               <div v-if="!tournament.teams?.length" class="text-center py-10 opacity-30">
                  <i class="pi pi-users text-4xl mb-4"></i>
                  <p class="font-bold">{{ t('tournament_view.teams.empty') }}</p>
               </div>
            </div>

         </div>
      </div>

      <!-- Sidebar -->
      <div v-if="showActivity" class="col-12 lg:col-4">
        <div class="card p-6 mus-glass border-white/5 h-full">
           <h3 class="text-xl font-black text-main italic uppercase mb-6 leading-none">{{ t('tournament_view.activity.title') }}</h3>
           <Timeline :value="[
             { icon: 'pi pi-check', color: '#e9c349', message: t('tournament_view.activity.match_closed', { table: 4, team: 'Alpha', score: '40-12' }), time: t('tournament_view.activity.time_5m') },
             { icon: 'pi pi-play', color: '#f4d125', message: t('tournament_view.activity.match_started', { table: 1, team1: 'Kings', team2: 'Jokers' }), time: t('tournament_view.activity.time_12m') },
             { icon: 'pi pi-user', color: '#3b82f6', message: t('tournament_view.activity.player_registered', { name: 'Blind Betty' }), time: t('tournament_view.activity.time_20m') }
           ]">
              <template #marker="slotProps">
                 <span class="flex align-items-center justify-center border-circle w-8 h-8 z-1" :style="{ backgroundColor: slotProps.item.color + '20', color: slotProps.item.color, border: '1px solid ' + slotProps.item.color + '40' }">
                    <i :class="slotProps.item.icon" class="text-xs"></i>
                 </span>
              </template>
              <template #content="slotProps">
                 <div class="mb-6 ml-4">
                    <p class="text-main text-xs font-black italic m-0 leading-tight">{{ slotProps.item.message }}</p>
                    <small class="text-slate-600 font-bold uppercase tracking-widest text-[8px]">{{ slotProps.item.time }}</small>
                 </div>
              </template>
           </Timeline>
        </div>
      </div>
    </template>

    <!-- Dialogs -->
    <Dialog v-model:visible="editingMatch" modal :header="t('tournament_view.match_edit.title')" :style="{ width: '460px' }" class="mus-dialog">
      <div v-if="editingMatch" class="p-6">
        <div class="flex flex-column gap-6">
          <!-- Team A scorecard -->
          <div class="p-6 bg-white/[0.02] rounded-3xl border border-white/5 flex flex-column align-items-center gap-5 relative overflow-hidden group">
            <div class="absolute top-0 right-0 w-32 h-32 bg-[#e9c349]/5 blur-3xl rounded-full -mr-16 -mt-16 pointer-events-none"></div>
            
            <div class="flex flex-column align-items-center relative z-10 text-center">
              <span class="text-[10px] font-black uppercase tracking-[0.3em] text-[#e9c349] mb-2 opacity-60">{{ t('tournament_view.match_edit.local') }}</span>
              <span class="font-black text-main italic text-2xl truncate max-w-[340px] leading-tight uppercase">{{ editingMatch.teamA }}</span>
            </div>

            <div class="flex align-items-center justify-content-center gap-5 relative z-10">
              <button @click="adjustScore(1, -1)" class="stepper-btn-v3" :disabled="editScore1 <= 0">
                <i class="pi pi-minus"></i>
              </button>
              <div class="score-display-v2">
                <input v-model.number="editScore1" type="number" readonly class="score-input-v2" :max="tournament.ruleGames">
              </div>
              <button @click="adjustScore(1, 1)" class="stepper-btn-v3" :disabled="editScore1 >= (tournament.ruleGames || 40)">
                <i class="pi pi-plus"></i>
              </button>
            </div>
          </div>

          <!-- Battle Divider -->
          <div class="flex align-items-center justify-content-center py-1">
            <div class="h-px bg-gradient-to-r from-transparent via-white/10 to-transparent flex-1"></div>
            <div class="mx-8 px-5 py-2 rounded-full border border-white/10 bg-[#0c0d0c] shadow-2xl z-10">
               <span class="text-[11px] font-black uppercase tracking-[0.5em] text-slate-600">VS</span>
            </div>
            <div class="h-px bg-gradient-to-r from-transparent via-white/10 to-transparent flex-1"></div>
          </div>

          <!-- Team B scorecard -->
          <div class="p-6 bg-white/[0.02] rounded-3xl border border-white/5 flex flex-column align-items-center gap-5 relative overflow-hidden group">
            <div class="absolute top-0 right-0 w-32 h-32 bg-[#e9c349]/5 blur-3xl rounded-full -mr-16 -mt-16 pointer-events-none"></div>
            
            <div class="flex flex-column align-items-center relative z-10 text-center">
              <span class="text-[10px] font-black uppercase tracking-[0.3em] text-[#e9c349] mb-2 opacity-60">{{ t('tournament_view.match_edit.visitor') }}</span>
              <span class="font-black text-main italic text-2xl truncate max-w-[340px] leading-tight uppercase">{{ editingMatch.teamB }}</span>
            </div>

            <div class="flex align-items-center justify-content-center gap-5 relative z-10">
              <button @click="adjustScore(2, -1)" class="stepper-btn-v3" :disabled="editScore2 <= 0">
                <i class="pi pi-minus"></i>
              </button>
              <div class="score-display-v2">
                <input v-model.number="editScore2" type="number" readonly class="score-input-v2" :max="tournament.ruleGames">
              </div>
              <button @click="adjustScore(2, 1)" class="stepper-btn-v3" :disabled="editScore2 >= (tournament.ruleGames || 40)">
                <i class="pi pi-plus"></i>
              </button>
            </div>
          </div>
        </div>

        <!-- Rule Summary Footer -->
        <div class="mt-8 p-4 rounded-2xl bg-[#e9c349]/5 border border-[#e9c349]/10 flex align-items-center gap-3">
            <i class="pi pi-info-circle text-[#e9c349]"></i>
            <span class="text-[10px] font-bold uppercase tracking-widest text-slate-400">
                {{ t('tournament_view.match_edit.rule_summary', { games: tournament.ruleGames || '...', points: tournament.rulePoints || '...' }) }}
            </span>
        </div>

        <div class="mt-10 flex gap-4">
          <button @click="editingMatch = null" class="mus-btn-secondary flex-1">
            {{ t('tournament_form.actions.cancel') }}
          </button>
          <button @click="saveMatchResult" :disabled="isSavingResult" class="mus-btn-primary flex-1">
            <i v-if="isSavingResult" class="pi pi-spin pi-spinner mr-2"></i>
            {{ t('tournament_view.match_edit.save') }}
          </button>
        </div>
      </div>
    </Dialog>

    <!-- Tournament Poster Dialog -->
    <Dialog v-model:visible="showPosterDialog" modal :header="tournament?.name" :style="{ width: '90vw', maxWidth: '800px' }" class="mus-dialog">
      <div class="flex justify-center bg-black/40 p-4 rounded-xl">
         <img :src="tournament?.posterPath || '/vertical.png'" class="max-w-full h-auto rounded-lg shadow-2xl" alt="Tournament Poster" />
      </div>
    </Dialog>
  </div>
</template>

<style scoped>
:deep(.p-timeline-event-opposite) {
  display: none;
}
:deep(.p-timeline-event-content) {
  padding-bottom: 2rem;
}

.mus-tab-btn {
  background: none; border: none; padding: 10px 20px;
  font-size: 11px; font-weight: 900; text-transform: uppercase;
  letter-spacing: 0.15em; color: #475569; cursor: pointer;
  transition: all 0.3s; position: relative;
}
.mus-tab-btn:hover { color: #94a3b8; }
.mus-tab-btn.active { color: var(--text-main); }
.mus-tab-btn.active::after {
  content: ''; position: absolute; bottom: -4px; left: 20px; right: 20px;
  height: 2px; background: #e9c349; border-radius: 2px;
  box-shadow: 0 0 10px #e9c349;
}

.mus-table-wrapper { 
  background: rgba(0,0,0,0.2); 
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
}
th { border-bottom: 1px solid var(--border); }

.rule-icon-item {
  display: flex;
  align-items: center;
  gap: 4px;
  cursor: help;
  transition: opacity 0.2s;
}
.rule-icon-item:hover {
  opacity: 0.8;
}

/* Premium Scorecard V3 Styles */
.stepper-btn-v3 {
  width: 58px;
  height: 52px;
  background: linear-gradient(180deg, rgba(15, 179, 97, 0.15) 0%, rgba(15, 179, 97, 0.05) 100%);
  border: 1px solid rgba(15, 179, 97, 0.3);
  border-bottom: 3px solid rgba(15, 179, 97, 0.5); /* 3D effect */
  border-radius: 16px;
  color: #ffffff;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s cubic-bezier(0.34, 1.56, 0.64, 1);
  font-size: 18px;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
}

.stepper-btn-v3:hover:not(:disabled) {
  background: var(--primary);
  color: #050505;
  border-color: #ffffff;
  transform: translateY(-2px);
  box-shadow: 0 10px 20px var(--primary-glow);
}

.stepper-btn-v3:active:not(:disabled) {
  transform: translateY(1px);
  border-bottom-width: 1px;
}

.stepper-btn-v3:disabled {
  opacity: 0.05;
  cursor: not-allowed;
  filter: grayscale(1);
  border-bottom-width: 1px;
}

.score-display-v2 {
  background: #000000;
  border: 1px solid var(--border);
  border-radius: 20px;
  width: 110px;
  height: 85px;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: inset 0 6px 20px rgba(0, 0, 0, 0.9);
}

.score-input-v2 {
  background: transparent;
  border: none;
  color: #ffffff;
  font-size: 48px;
  font-weight: 950;
  text-align: center;
  width: 100%;
  outline: none;
  font-family: 'Inter', sans-serif;
  font-style: italic;
  letter-spacing: -2px;
  text-shadow: 0 0 20px rgba(15, 179, 97, 0.3);
}

/* Remove Arrows from Number Input */
input[type=number]::-webkit-inner-spin-button, 
input[type=number]::-webkit-outer-spin-button { 
  -webkit-appearance: none; 
  margin: 0; 
}
input[type=number] { -moz-appearance: textfield; }

.mus-btn-gold {
  background: linear-gradient(135deg, #bf953f 0%, #fcf6ba 50%, #aa771c 100%) !important;
  color: #050505 !important;
  border: none !important;
  font-weight: 900 !important;
  text-transform: uppercase;
  letter-spacing: 0.15em;
  border-radius: 99px !important;
  transition: all 0.3s;
}

.mus-btn-gold:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 20px rgba(191, 149, 63, 0.4);
}

/* Bracket Specific Styles */
.bracket-viewport {
  width: 100%;
  position: relative;
}

.bracket-wrapper {
  mask-image: linear-gradient(to right, transparent, black 5%, black 95%, transparent);
  -webkit-mask-image: linear-gradient(to right, transparent, black 5%, black 95%, transparent);
}

.bracket-match-card {
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.02);
  backdrop-filter: blur(10px);
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.4);
  width: 100%;
  min-height: 90px;
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.bracket-connector {
  position: absolute;
  right: -48px;
  top: 50%;
  width: 48px; /* Matches the gap-12 (3rem = 48px) */
  height: 2px;
  background: rgba(233, 195, 73, 0.2);
}

/* Vertical Sibling Connectors */
.bracket-match-container:nth-child(odd):not(:last-child) .bracket-connector::after {
  content: '';
  position: absolute;
  right: 24px; /* Centered in the 48px gap */
  top: 0;
  width: 2px;
  height: calc(50% + 65px); /* Slightly increased to ensure overlap */
  background: rgba(233, 195, 73, 0.2);
}

.bracket-match-container:nth-child(even) .bracket-connector::after {
  content: '';
  position: absolute;
  right: 24px; /* Centered in the 48px gap */
  bottom: 0;
  width: 2px;
  height: calc(50% + 65px);
  background: rgba(233, 195, 73, 0.2);
}

/* Right-connector (Mirrored) Vertical Lines */
.right-connector::after {
  right: auto !important;
  left: 24px !important;
}

/* Vertical mode connectors */
.bracket-round.is-vertical .bracket-connector {
  right: auto;
  bottom: -48px;
  left: 50%;
  width: 2px;
  height: 80px; /* Increased to match gap-20 (5rem = 80px) */
  top: auto;
}

/* Vertical lines for meetups - creating a proper bracket tree look */
.bracket-match-container.has-connectors::after {
  content: '';
  position: absolute;
  right: -48px;
  width: 2px;
  background: rgba(233, 195, 73, 0.3);
  z-index: 1;
}

/* Vertical mode meetups */
.bracket-round.is-vertical .bracket-match-container.has-connectors::after {
  right: auto;
  bottom: -80px; /* Match the 80px connector */
  left: 0;
  width: 100%;
  height: 2px;
  top: auto;
}

/* The vertical line spans half the distance to the sibling match */
.bracket-round:not(:last-child) .bracket-match-container:nth-child(odd)::after {
  height: 50%;
  top: 50%;
}
.bracket-round:not(:last-child) .bracket-match-container:nth-child(even)::after {
  height: 50%;
  bottom: 50%;
}

/* Vertical mode logic for sibling connectors */
.bracket-round.is-vertical .bracket-match-container:nth-child(odd)::after {
  width: 50%;
  left: 50%;
  height: 2px;
  bottom: -80px;
  top: auto;
}
.bracket-round.is-vertical .bracket-match-container:nth-child(even)::after {
  width: 50%;
  right: 50%;
  left: auto;
  height: 2px;
  bottom: -80px;
  top: auto;
}

.round-title {
  position: sticky;
  top: 0;
  z-index: 20;
  background: rgba(5, 5, 5, 0.9);
  backdrop-filter: blur(10px);
  padding: 15px 15px 15px 15px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  height: 80px; /* Fixed height for alignment */
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.bracket-viewport.is-fullscreen .round-title {
  height: 100px;
  background: #050505;
}

.custom-scrollbar::-webkit-scrollbar {
  height: 6px;
  width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: rgba(255, 255, 255, 0.02);
  border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: rgba(233, 195, 73, 0.2);
  border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: rgba(233, 195, 73, 0.4);
}

/* TV / Fullscreen Mode Styles */
.bracket-viewport.is-fullscreen {
  background: #050505;
  padding: 40px;
  width: 100vw;
  height: 100vh;
  display: flex;
  flex-direction: column;
  overflow-y: auto;
}

.bracket-viewport.is-fullscreen .bracket-wrapper {
  flex: 1;
  mask-image: none;
  -webkit-mask-image: none;
  padding: 0;
  overflow: auto !important;
  background: #050505;
}

.bracket-viewport.is-fullscreen .bracket-round {
  min-width: 350px;
  width: 400px;
  height: auto;
  min-height: 100%;
  display: flex;
  flex-direction: column;
}

.bracket-viewport.is-fullscreen .bracket-round {
  min-width: 350px;
  width: 380px;
}

.bracket-viewport.is-fullscreen .bracket-match-card {
  min-height: 120px;
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.1);
}

.bracket-viewport.is-fullscreen .team-row-bracket {
  padding: 1.2rem;
}

.bracket-viewport.is-fullscreen .team-row-bracket span.text-xs {
  font-size: 1.1rem !important;
}

.bracket-viewport.is-fullscreen .team-row-bracket span.text-lg {
  font-size: 2rem !important;
}

.bracket-viewport.is-fullscreen .round-title h4 {
  font-size: 1.2rem;
}

.fs-controls {
  z-index: 100;
}

/* Right wing mirrored connectors */
.right-wing .bracket-connector.right-connector {
  right: auto;
  left: -48px;
}

.right-wing.has-connectors::after {
  right: auto;
  left: -48px;
}
</style>
