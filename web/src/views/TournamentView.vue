<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { tournamentService } from '../services/api'
import { useI18n } from 'vue-i18n'
import Card from 'primevue/card'
import Tag from 'primevue/tag'
import ProgressBar from 'primevue/progressbar'
import Button from 'primevue/button'
import Timeline from 'primevue/timeline'
import Dialog from 'primevue/dialog'
import GoogleAd from '../components/GoogleAd.vue'
import MusLoader from '../components/MusLoader.vue'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const activeTab = ref('matches')
const classification = ref({})
const tournament = ref(null)
const loading = ref(true)
const error = ref(null)
const matches = ref([])
const showActivity = ref(false)
const editingMatch = ref(null)
const editScore1 = ref(0)
const editScore2 = ref(0)
const isSavingResult = ref(false)
const showPosterDialog = ref(false)


const activeStageTab = ref(null);

const knockoutStages = [
  t('tournament_view.knockout.t32'),
  t('tournament_view.knockout.t16'),
  t('tournament_view.knockout.t8'),
  t('tournament_view.knockout.t4'), 
  t('tournament_view.knockout.final'), 
  t('tournament_view.knockout.third_place')
];
const groupedMatches = computed(() => {
  if (!matches.value.length) return []
  const acc = {}
  matches.value.forEach(match => {
    const stage = match.stage || t('tournament_view.rounds.general')
    if (knockoutStages.includes(stage)) return; // Exclude from normal list
    if (!acc[stage]) acc[stage] = []
    acc[stage].push(match)
  })
  
  const entries = Object.entries(acc).reverse();
  return entries;
});

// Auto-select first stage tab when matches load
watch(groupedMatches, (newGroups) => {
  if (newGroups.length > 0 && !activeStageTab.value) {
    activeStageTab.value = newGroups[0][0];
  }
}, { immediate: true });

const bracketMatches = computed(() => {
  const cols = [];
  const orderedTreeStages = [
    t('tournament_view.knockout.t32'),
    t('tournament_view.knockout.t16'),
    t('tournament_view.knockout.t8'),
    t('tournament_view.knockout.t4'), 
    t('tournament_view.knockout.final')
  ];
  
  orderedTreeStages.forEach(col => {
    const m = matches.value.filter(x => x.stage === col);
    if (m.length) cols.push({ stage: col, matches: m });
  });
  return cols;
})

const thirdPlaceMatch = computed(() => matches.value.find(x => x.stage === t('tournament_view.knockout.third_place')))


const fetchTournament = async () => {
  loading.value = true
  error.value = null
  try {
    const data = await tournamentService.getTournament(route.params.uuid)
    tournament.value = data
    if (data.matches) {
      matches.value = data.matches.map(m => ({
        id: m.id,
        status: m.score1 > 0 || m.score2 > 0 ? t('tournament_view.match_status.finished') : t('tournament_view.match_status.pending'),
        teamA: m.team1,
        scoreA: m.score1,
        teamB: m.team2,
        scoreB: m.score2,
        stage: m.stage
      }))
    }
    
    if (data.type === 'groups' || data.type === 'league') {
       classification.value = await tournamentService.getClassification(route.params.uuid)
    }

    // Set default tab for different tournament types
    if (data.type === 'eliminatory' && activeTab.value === 'matches') {
       activeTab.value = 'bracket'
    } else if (data.type === 'league' && activeTab.value === 'matches') {
       activeTab.value = 'standings'
    }
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}

onMounted(fetchTournament)

const openEditModal = (match) => {
  editingMatch.value = match
  editScore1.value = match.scoreA
  editScore2.value = match.scoreB
}

const adjustScore = (team, amount) => {
  const max = tournament.value?.ruleGames || 40 // Default to 40 if not set, though usually it's 2, 3 or 4
  if (team === 1) {
    const newVal = editScore1.value + amount
    if (newVal >= 0 && newVal <= max) editScore1.value = newVal
  } else {
    const newVal = editScore2.value + amount
    if (newVal >= 0 && newVal <= max) editScore2.value = newVal
  }
}

const saveMatchResult = async () => {
  isSavingResult.value = true
  try {
    await tournamentService.updateMatchScore(editingMatch.value.id, editScore1.value, editScore2.value)
    editingMatch.value = null
    await fetchTournament()
  } catch (e) {
    alert(e.message)
  } finally {
    isSavingResult.value = false
  }
}

const openPoster = () => {
  const path = tournament.value?.posterPath || '/vertical.png';
  if (path.toLowerCase().endsWith('.pdf')) {
    window.open(path, '_blank');
  } else {
    showPosterDialog.value = true;
  }
}
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
             <h2 class="text-3xl font-black text-white italic uppercase tracking-tight">{{ t('tournament_view.restricted_access') }}</h2>
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
          <div class="absolute top-0 right-0 w-1/2 h-full bg-gradient-to-l from-[#0fb361]/5 to-transparent"></div>
          
          <div class="relative z-10 flex flex-column md:flex-row justify-content-between align-items-center gap-6">
            <div class="flex-1">
              <div class="flex align-items-center gap-3 mb-2">
                <Tag :value="t('tournament_form.statuses.' + tournament.status)" severity="success" class="italic font-black uppercase text-[8px] tracking-widest" />
              </div>
              <h1 class="text-3xl md:text-4xl font-black text-white tracking-tight italic uppercase leading-tight m-0">{{ tournament.name }}</h1>
              <p class="text-slate-500 text-xs font-bold uppercase tracking-widest m-0 flex flex-wrap align-items-center gap-4">
                <span @click="openPoster" class="cursor-pointer hover:text-white transition-colors flex align-items-center gap-2">
                  <i class="pi pi-eye text-[#0fb361]"></i>
                  {{ t('dashboard.poster') }}
                </span>
                <span><i class="pi pi-user mr-2 text-[#0fb361]"></i>{{ t('tournament_view.pairs_count', { count: tournament.teamsCount || 0 }) }}</span>
                <span><i class="pi pi-calendar mr-2 text-[#0fb361]"></i>{{ tournament.startDate ? new Date(tournament.startDate).toLocaleDateString() : '...' }} - {{ tournament.endDate ? new Date(tournament.endDate).toLocaleDateString() : '...' }}</span>
                <span v-if="tournament.location"><i class="pi pi-map-marker mr-2 text-[#0fb361]"></i>{{ tournament.location }}</span>
              </p>
            </div>

            <div class="flex gap-4 align-items-center bg-white/5 p-3 rounded-2xl border border-white/5">
                <div class="rule-icon-item" v-tooltip.top="t('tournament_form.labels.ruleKings') + ': ' + tournament.ruleKings">
                  <i class="pi pi-crown text-xl text-[#0fb361]"></i>
                  <span class="text-xs font-black ml-2">{{ tournament.ruleKings }}</span>
                </div>
                <div class="rule-icon-item border-l border-white/10 pl-4" v-tooltip.top="t('tournament_form.labels.rulePoints') + ': ' + tournament.rulePoints">
                  <i class="pi pi-hashtag text-xl text-[#0fb361]"></i>
                  <span class="text-xs font-black ml-2">{{ tournament.rulePoints }}</span>
                </div>
                <div class="rule-icon-item border-l border-white/10 pl-4" v-tooltip.top="t('tournament_form.labels.ruleGames') + ': ' + tournament.ruleGames">
                  <i class="pi pi-bolt text-xl text-[#0fb361]"></i>
                  <span class="text-xs font-black ml-2">{{ tournament.ruleGames }}</span>
                </div>
                <div v-if="tournament.tablesCount" class="rule-icon-item border-l border-white/10 pl-4" v-tooltip.top="t('tournament_form.labels.tablesCount') + ': ' + tournament.tablesCount">
                  <i class="pi pi-table text-xl text-[#f4d125]"></i>
                  <span class="text-xs font-black ml-2 text-[#f4d125]">{{ tournament.tablesCount }}</span>
                </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Google Ad Slot (Top) -->
      <div class="col-12 px-4">
        <GoogleAd :key="'top-' + activeTab" />
      </div>

      <!-- Main Content Container -->
      <div :class="showActivity ? 'col-12 lg:col-8' : 'col-12'">
        <div class="card p-3 md:p-6 mus-glass border-white/5">
           <div class="flex gap-4 mb-2 border-b border-white/5 pb-4">
              <button v-if="tournament.type !== 'eliminatory'" @click="activeTab = 'matches'" 
                      class="mus-tab-btn" :class="{ active: activeTab === 'matches' }">
                {{ t('tournament_view.tabs.matches') }}
              </button>
              <button v-if="tournament.type === 'groups' || tournament.type === 'league'" @click="activeTab = 'standings'" 
                      class="mus-tab-btn" :class="{ active: activeTab === 'standings' }">
                {{ t('tournament_view.tabs.standings') }}
              </button>
              <button v-if="tournament.type !== 'league'" @click="activeTab = 'bracket'" 
                      class="mus-tab-btn" :class="{ active: activeTab === 'bracket' }">
                {{ t('tournament_view.tabs.bracket') }}
              </button>
              <button @click="activeTab = 'teams'" 
                      class="mus-tab-btn" :class="{ active: activeTab === 'teams' }">
                {{ t('tournament_view.tabs.teams') }}
              </button>
              <button v-if="tournament.isManager" @click="activeTab = 'admin'" 
                      class="mus-tab-btn border-l border-white/5 pl-4 ml-auto" :class="{ active: activeTab === 'admin' }">
                <i class="pi pi-cog mr-2"></i> {{ t('tournament_view.tabs.admin') || 'Admin' }}
              </button>
           </div>

           <!-- Content according to active tab -->
           <div v-if="activeTab === 'matches'">
              <div class="grid">
                <!-- Highlighted Matches (Final & 3rd Place) -->
                <div v-if="matches.find(m => m.stage === t('tournament_view.knockout.final')) || matches.find(m => m.stage === t('tournament_view.knockout.third_place'))" class="col-12 mb-8">
                   <h4 class="text-[#f4d125] font-black italic uppercase tracking-[0.2em] mb-4 flex align-items-center gap-2 text-xs">
                      <i class="pi pi-trophy"></i> {{ t('tournament_view.tabs.bracket') }}
                   </h4>
                   <div class="grid">
                      <!-- Final -->
                      <div v-for="match in matches.filter(m => m.stage === t('tournament_view.knockout.final'))" :key="'final-'+match.id" class="col-12 mb-4">
                         <div class="mus-table-wrapper rounded-2xl overflow-hidden border border-[#f4d125]/30 bg-[#f4d125]/5 p-1">
                            <table class="w-full text-left border-collapse bg-slate-900/50 rounded-xl">
                               <tbody>
                                  <tr class="group">
                                     <td class="p-4 w-7/12">
                                        <div class="flex flex-column gap-3">
                                           <div class="flex align-items-center gap-3">
                                              <div class="w-6 h-6 flex-shrink-0 rounded bg-slate-800 border border-white/10 flex align-items-center justify-center text-[9px] font-black">A</div>
                                              <span class="text-white font-black italic text-lg truncate">{{ match.teamA }}</span>
                                           </div>
                                           <div class="flex align-items-center gap-3">
                                              <div class="w-6 h-6 flex-shrink-0 rounded bg-slate-800 border border-white/10 flex align-items-center justify-center text-[9px] font-black">B</div>
                                              <span class="text-white font-black italic text-lg truncate">{{ match.teamB }}</span>
                                           </div>
                                        </div>
                                     </td>
                                     <td class="p-4 w-2/12 border-l border-white/5 align-middle text-center">
                                        <div class="flex flex-column gap-3">
                                           <span class="text-2xl font-black italic" :class="match.scoreA > match.scoreB ? 'text-[#f4d125]' : 'text-slate-600'">{{ match.scoreA }}</span>
                                           <span class="text-2xl font-black italic" :class="match.scoreB > match.scoreA ? 'text-[#f4d125]' : 'text-slate-600'">{{ match.scoreB }}</span>
                                        </div>
                                     </td>
                                     <td class="p-4 w-3/12 border-l border-white/5 align-middle text-center">
                                        <div class="flex flex-column align-items-center justify-center gap-3">
                                           <Tag :value="t('tournament_view.knockout.final')" severity="warning" class="text-[9px] font-black uppercase" />
                                           <button v-if="tournament.isManager" @click="openEditModal(match)" class="text-[9px] font-black text-[#f4d125] uppercase border border-[#f4d125]/30 px-3 py-1 rounded bg-[#f4d125]/5 hover:bg-[#f4d125]/20 transition-all">
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
                      <div v-for="match in matches.filter(m => m.stage === t('tournament_view.knockout.third_place'))" :key="'third-'+match.id" class="col-12 mb-4">
                         <div class="mus-table-wrapper rounded-2xl overflow-hidden border border-white/10 bg-white/5 p-1">
                            <table class="w-full text-left border-collapse bg-slate-900/50 rounded-xl">
                               <tbody>
                                  <tr class="group">
                                     <td class="p-4 w-7/12">
                                        <div class="flex flex-column gap-3">
                                           <div class="flex align-items-center gap-3">
                                              <div class="w-6 h-6 flex-shrink-0 rounded bg-slate-800 border border-white/10 flex align-items-center justify-center text-[9px] font-black">A</div>
                                              <span class="text-white font-black italic text-lg truncate">{{ match.teamA }}</span>
                                           </div>
                                           <div class="flex align-items-center gap-3">
                                              <div class="w-6 h-6 flex-shrink-0 rounded bg-slate-800 border border-white/10 flex align-items-center justify-center text-[9px] font-black">B</div>
                                              <span class="text-white font-black italic text-lg truncate">{{ match.teamB }}</span>
                                           </div>
                                        </div>
                                     </td>
                                     <td class="p-4 w-2/12 border-l border-white/5 align-middle text-center">
                                        <div class="flex flex-column gap-3">
                                           <span class="text-2xl font-black italic" :class="match.scoreA > match.scoreB ? 'text-white' : 'text-slate-600'">{{ match.scoreA }}</span>
                                           <span class="text-2xl font-black italic" :class="match.scoreB > match.scoreA ? 'text-white' : 'text-slate-600'">{{ match.scoreB }}</span>
                                        </div>
                                     </td>
                                     <td class="p-4 w-3/12 border-l border-white/5 align-middle text-center">
                                        <div class="flex flex-column align-items-center justify-center gap-3">
                                           <Tag :value="t('tournament_view.knockout.third_place')" severity="secondary" class="text-[9px] font-black uppercase" />
                                           <button v-if="tournament.isManager" @click="openEditModal(match)" class="text-[9px] font-black text-slate-400 uppercase border border-white/10 px-3 py-1 rounded bg-white/5 hover:bg-white/10 transition-all">
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
                   <h4 class="text-[#0fb361] font-black italic uppercase tracking-[0.2em] mb-4 flex align-items-center gap-2 text-xs">
                      <i class="pi pi-calendar"></i> {{ stage }}
                   </h4>
                   <div class="mus-table-wrapper rounded-2xl overflow-hidden border border-white/5 bg-white/5">
                      <table class="w-full text-left border-collapse">
                         <tbody>
                            <tr v-for="(match, i) in stageMatches" :key="i" class="border-b border-white/10 last:border-0 hover:bg-white/5 transition-colors group">
                               <td class="p-4 w-7/12">
                                  <div class="flex flex-column gap-3">
                                     <div class="flex align-items-center gap-3">
                                        <div class="w-6 h-6 flex-shrink-0 rounded bg-slate-800 border border-white/10 flex align-items-center justify-center text-[9px] font-black">A</div>
                                        <span class="text-white font-black italic text-base truncate" :title="match.teamA">{{ match.teamA }}</span>
                                     </div>
                                     <div class="flex align-items-center gap-3">
                                        <div class="w-6 h-6 flex-shrink-0 rounded bg-slate-800 border border-white/10 flex align-items-center justify-center text-[9px] font-black">B</div>
                                        <span class="text-white font-black italic text-base truncate" :title="match.teamB">{{ match.teamB }}</span>
                                     </div>
                                  </div>
                               </td>
                               <td class="p-4 w-2/12 border-l border-white/5 align-middle">
                                  <div class="flex flex-column gap-3 text-center">
                                     <span class="text-xl font-black italic" :class="match.scoreA > match.scoreB ? 'text-[#0fb361]' : 'text-slate-600'">{{ match.scoreA }}</span>
                                     <span class="text-xl font-black italic" :class="match.scoreB > match.scoreA ? 'text-[#0fb361]' : 'text-slate-600'">{{ match.scoreB }}</span>
                                  </div>
                               </td>
                               <td class="p-4 w-3/12 border-l border-white/5 align-middle text-center">
                                  <div class="flex flex-column align-items-center justify-center gap-2">
                                     <Tag :value="match.status" :severity="match.status === t('tournament_view.match_status.finished') ? 'success' : 'secondary'" class="text-[8px] whitespace-nowrap" />
                                     <button v-if="tournament.isManager" 
                                             @click="openEditModal(match)"
                                             class="mt-2 text-[9px] font-black text-[#0fb361] uppercase border border-[#0fb361]/30 px-2 py-1 rounded bg-[#0fb361]/5 hover:bg-[#0fb361]/20 transition-all whitespace-nowrap">
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
              <!-- Inner Ad -->
              <GoogleAd :key="'inner-' + activeTab" />
           </div>

           <div v-else-if="activeTab === 'standings'" class="classification-container">
             <div v-for="(teams, groupName) in classification" :key="groupName" class="mb-10">
               <h4 class="text-[#0fb361] font-black italic uppercase tracking-widest mb-4 flex items-center gap-2">
                 <i class="pi pi-table"></i> {{ groupName }}
               </h4>
               <div class="mus-table-wrapper rounded-2xl overflow-hidden border border-white/5">
                 <table class="w-full text-left border-collapse">
                   <thead>
                     <tr class="bg-white/5 text-[10px] uppercase font-black tracking-widest text-slate-500">
                       <th class="p-4 w-5/12 min-w-[200px]">{{ t('tournament_view.classification.team') }}</th>
                       <th class="p-4 text-center">{{ t('tournament_view.classification.played') }}</th>
                       <th class="p-4 text-center">{{ t('tournament_view.classification.won') }}</th>
                       <th class="p-4 text-center">{{ t('tournament_view.classification.lost') }}</th>
                       <th class="p-4 text-center text-white">{{ t('tournament_view.classification.points') }}</th>
                     </tr>
                   </thead>
                   <tbody>
                     <tr v-for="team in teams" :key="team.teamName" class="border-t border-white/5 hover:bg-white/5 transition-colors">
                       <td class="p-4 font-bold text-white">{{ team.teamName }}</td>
                       <td class="p-4 text-center text-slate-400">{{ team.played }}</td>
                       <td class="p-4 text-center text-emerald-500">{{ team.won }}</td>
                       <td class="p-4 text-center text-rose-500">{{ team.lost }}</td>
                       <td class="p-4 text-center font-black text-[#0fb361]">{{ team.points }}</td>
                     </tr>
                   </tbody>
                 </table>
               </div>
             </div>
             <div v-if="Object.keys(classification).length === 0" class="text-center py-10 opacity-30">
               <i class="pi pi-info-circle text-4xl mb-4"></i>
               <p class="font-bold">{{ t('tournament_view.classification.empty') }}</p>
             </div>
           </div>

           <div v-else-if="activeTab === 'bracket'" class="py-6">
              <div v-if="bracketMatches.length === 0" class="text-center py-20 opacity-30">
                <i class="pi pi-sitemap text-6xl mb-4"></i>
                <h3 class="font-black italic uppercase">{{ t('tournament_view.bracket.title') }}</h3>
                <p>{{ t('tournament_view.bracket.empty_desc') }}</p>
              </div>
              <div v-else>
                <div class="flex overflow-x-auto py-8 px-2 gap-4 md:gap-8 bracket-container">
                   <div v-for="col in bracketMatches" :key="col.stage" class="flex flex-column justify-content-around gap-6 min-w-[280px] w-[320px] relative">
                     <h4 class="text-center font-black uppercase tracking-widest text-[#0fb361] text-xs mb-2 bg-[#0fb361]/10 py-2 rounded">{{ col.stage }}</h4>
                     <div class="flex-1 flex flex-column justify-content-around gap-6">
                        <div v-for="(m, i) in col.matches" :key="i" class="card bg-[#0a0a0a] border border-white/10 p-4 rounded-xl relative hover:border-[#0fb361]/50 cursor-pointer transition-colors" @click="tournament.isManager ? openEditModal(m) : null">
                          <div class="flex align-items-center justify-content-between mb-3">
                            <span class="text-white font-bold text-sm truncate pr-2 w-10/12" :title="m.teamA">{{ m.teamA || 'Por decidir' }}</span>
                            <span class="font-black text-lg" :class="m.scoreA > m.scoreB ? 'text-[#0fb361]' : 'text-slate-500'">{{ m.scoreA }}</span>
                          </div>
                          <div class="h-px bg-white/10 w-full my-3"></div>
                          <div class="flex align-items-center justify-content-between">
                            <span class="text-white font-bold text-sm truncate pr-2 w-10/12" :title="m.teamB">{{ m.teamB || 'Por decidir' }}</span>
                            <span class="font-black text-lg" :class="m.scoreB > m.scoreA ? 'text-[#0fb361]' : 'text-slate-500'">{{ m.scoreB }}</span>
                          </div>
                          
                          <!-- Connector lines -->
                          <div v-if="col.stage !== 'Final'" class="absolute -right-4 top-1/2 w-4 h-px bg-[#0fb361]/30"></div>
                        </div>
                     </div>
                  </div>
                </div>

                <!-- 3er y 4o Puesto -->
                <div v-if="thirdPlaceMatch" class="mt-8 border-t border-white/10 pt-8 max-w-md mx-auto">
                  <h4 class="text-center font-black uppercase tracking-widest text-slate-500 text-xs mb-4">3er y 4º Puesto</h4>
                  <div class="card bg-[#0a0a0a] border border-[#f4d125]/30 p-4 rounded-xl cursor-pointer hover:border-[#f4d125]" @click="tournament.isManager ? openEditModal(thirdPlaceMatch) : null">
                     <div class="flex align-items-center justify-content-between mb-3">
                       <span class="text-white font-bold text-sm truncate pr-2" :title="thirdPlaceMatch.teamA">{{ thirdPlaceMatch.teamA || 'Por decidir' }}</span>
                       <span class="font-black text-lg" :class="thirdPlaceMatch.scoreA > thirdPlaceMatch.scoreB ? 'text-[#f4d125]' : 'text-slate-500'">{{ thirdPlaceMatch.scoreA }}</span>
                     </div>
                     <div class="h-px bg-white/10 w-full my-3"></div>
                     <div class="flex align-items-center justify-content-between">
                       <span class="text-white font-bold text-sm truncate pr-2" :title="thirdPlaceMatch.teamB">{{ thirdPlaceMatch.teamB || 'Por decidir' }}</span>
                       <span class="font-black text-lg" :class="thirdPlaceMatch.scoreB > thirdPlaceMatch.scoreA ? 'text-[#f4d125]' : 'text-slate-500'">{{ thirdPlaceMatch.scoreB }}</span>
                     </div>
                  </div>
                </div>
              </div>
           </div>

           <div v-else-if="activeTab === 'teams'">
              <div v-if="tournament.tournamentTeams?.length" class="mus-table-wrapper rounded-2xl overflow-hidden border border-white/5 bg-white/5">
                 <table class="w-full text-left border-collapse">
                    <tbody>
                       <tr v-for="tt in tournament.tournamentTeams" :key="tt.id" class="border-b border-white/10 last:border-0 hover:bg-white/5 transition-colors group">
                          <td class="p-4 w-16 text-center border-r border-white/5">
                             <div class="w-10 h-10 mx-auto rounded-full bg-[#0fb361]/10 border border-[#0fb361]/20 flex align-items-center justify-center">
                                <i class="pi pi-users text-[#0fb361]"></i>
                             </div>
                          </td>
                          <td class="p-4 font-bold text-white text-lg">
                             {{ tt.team?.name }}
                          </td>
                          <td class="p-4 text-right w-1/3 border-l border-white/5">
                             <span class="text-slate-400 text-xs font-black uppercase tracking-widest">{{ tt.groupName || '-' }}</span>
                          </td>
                       </tr>
                    </tbody>
                 </table>
              </div>
              
              <div v-if="!tournament.tournamentTeams?.length" class="text-center py-10 opacity-30">
                 <i class="pi pi-users text-4xl mb-4"></i>
                 <p class="font-bold">{{ t('tournament_view.teams.empty') }}</p>
              </div>
           </div>

           <!-- Admin / Result Entry Section -->
           <div v-else-if="activeTab === 'admin' && tournament.isManager" class="admin-section">
              <div class="mb-6 flex align-items-center justify-between">
                <div>
                   <h3 class="text-white font-black uppercase italic tracking-tight m-0">{{ t('tournament_view.match_edit.title') }}</h3>
                   <p class="text-slate-500 text-xs font-bold uppercase tracking-widest">{{ t('tournament_view.active_matches_desc') || 'Registra los resultados de los enfrentamientos' }}</p>
                </div>
              </div>

              <div class="grid">
                <div v-if="matches.length === 0" class="col-12 text-center py-10 opacity-30 border-2 border-dashed border-white/10 rounded-2xl">
                   <p class="font-bold uppercase tracking-widest">{{ t('tournament_view.bracket.empty_desc') }}</p>
                </div>
                <div v-else v-for="match in matches" :key="match.id" class="col-12 md:col-6 lg:col-4 mb-4">
                   <div class="mus-glass p-4 rounded-2xl border border-white/5 hover:border-[#0fb361]/30 transition-all cursor-pointer group" @click="openEditModal(match)">
                      <div class="flex justify-between items-center mb-3">
                         <Tag :value="match.stage" severity="secondary" class="text-[8px] opacity-60" />
                         <span class="text-[8px] font-black text-[#0fb361]" v-if="match.status === t('tournament_view.match_status.finished')">
                            <i class="pi pi-check-circle mr-1"></i> {{ match.status }}
                         </span>
                      </div>
                      <div class="flex flex-column gap-2 mb-4">
                         <div class="flex justify-between items-center">
                            <span class="text-white font-bold text-sm truncate max-w-[150px]">{{ match.teamA }}</span>
                            <span class="font-black text-lg" :class="match.scoreA > match.scoreB ? 'text-[#0fb361]' : 'text-slate-600'">{{ match.scoreA }}</span>
                         </div>
                         <div class="flex justify-between items-center">
                            <span class="text-white font-bold text-sm truncate max-w-[150px]">{{ match.teamB }}</span>
                            <span class="font-black text-lg" :class="match.scoreB > match.scoreA ? 'text-[#0fb361]' : 'text-slate-600'">{{ match.scoreB }}</span>
                         </div>
                      </div>
                      <div class="pt-3 border-t border-white/5 text-center">
                         <span class="text-[9px] font-black uppercase text-[#0fb361] group-hover:bg-[#0fb361]/10 px-3 py-1 rounded-full transition-all">
                            {{ t('tournament_view.match_edit.save') }}
                         </span>
                      </div>
                   </div>
                </div>
              </div>
           </div>
        </div>
      </div>

      <!-- Sidebar -->
      <div v-if="showActivity" class="col-12 lg:col-4">
        <div class="card p-6 mus-glass border-white/5 h-full">
           <h3 class="text-xl font-black text-white italic uppercase mb-6 leading-none">{{ t('tournament_view.activity.title') }}</h3>
           <Timeline :value="[
             { icon: 'pi pi-check', color: '#0fb361', message: t('tournament_view.activity.match_closed', { table: 4, team: 'Alpha', score: '40-12' }), time: t('tournament_view.activity.time_5m') },
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
                    <p class="text-white text-xs font-black italic m-0 leading-tight">{{ slotProps.item.message }}</p>
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
            <div class="absolute top-0 right-0 w-32 h-32 bg-[#0fb361]/5 blur-3xl rounded-full -mr-16 -mt-16 pointer-events-none"></div>
            
            <div class="flex flex-column align-items-center relative z-10 text-center">
              <span class="text-[10px] font-black uppercase tracking-[0.3em] text-[#0fb361] mb-2 opacity-60">{{ t('tournament_view.match_edit.local') }}</span>
              <span class="font-black text-white italic text-2xl truncate max-w-[340px] leading-tight">{{ editingMatch.teamA }}</span>
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
            <div class="absolute top-0 right-0 w-32 h-32 bg-[#0fb361]/5 blur-3xl rounded-full -mr-16 -mt-16 pointer-events-none"></div>
            
            <div class="flex flex-column align-items-center relative z-10 text-center">
              <span class="text-[10px] font-black uppercase tracking-[0.3em] text-[#0fb361] mb-2 opacity-60">{{ t('tournament_view.match_edit.visitor') }}</span>
              <span class="font-black text-white italic text-2xl truncate max-w-[340px] leading-tight">{{ editingMatch.teamB }}</span>
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
        <div class="mt-8 p-4 rounded-2xl bg-[#0fb361]/5 border border-[#0fb361]/10 flex align-items-center gap-3">
            <i class="pi pi-info-circle text-[#0fb361]"></i>
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
.mus-tab-btn.active { color: white; }
.mus-tab-btn.active::after {
  content: ''; position: absolute; bottom: -4px; left: 20px; right: 20px;
  height: 2px; background: #0fb361; border-radius: 2px;
  box-shadow: 0 0 10px #0fb361;
}

.mus-table-wrapper { background: rgba(0,0,0,0.2); }
th { border-bottom: 1px solid rgba(255,255,255,0.05); }

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
  border: 1px solid rgba(255, 255, 255, 0.08);
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
</style>
