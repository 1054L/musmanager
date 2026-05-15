<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { tournamentService, teamService } from '../services/api'
import { useI18n } from 'vue-i18n'
import Card from 'primevue/card'
import Tag from 'primevue/tag'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import Checkbox from 'primevue/checkbox'
import MusLoader from '../components/MusLoader.vue'
import TournamentEnrollmentForm from '../components/TournamentEnrollmentForm.vue'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import { useMercure } from '../composables/useMercure'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const { subscribe } = useMercure()
const toast = useToast()
const confirm = useConfirm()
const uuid = route.params.uuid

const tournament = ref(null)
const loading = ref(true)
const error = ref(null)
const matches = ref([])
const enrolledTeams = ref([])
const availableTeams = ref([])
const enrollmentTeamId = ref(null)
const groupsCount = ref(2)
const processing = ref(false)
const showEnrollmentDialog = ref(false)
const enrollmentForm = ref(null)
const matchesOrder = ref('asc')
const collapsedStages = ref({})

const onEnrollmentDialogShow = () => {
  setTimeout(() => {
    if (enrollmentForm.value) {
      enrollmentForm.value.focusInput()
    }
  }, 300)
}

// Match editing state
const editingMatch = ref(null)
const editScore1 = ref(0)
const editScore2 = ref(0)
const isSavingResult = ref(false)

const fetchTournamentData = async (silent = false) => {
  if (!silent) loading.value = true
  error.value = null
  try {
    const data = await tournamentService.getTournament(uuid)
    tournament.value = data
    enrolledTeams.value = data.tournamentTeams || []
    
    if (data.matches) {
      matches.value = data.matches.map(m => ({
        id: m.id,
        status: m.score1 > 0 || m.score2 > 0 ? 'finished' : 'pending',
        teamA: m.team1,
        scoreA: m.score1,
        teamB: m.team2,
        scoreB: m.score2,
        stage: m.stage,
        bracketRound: m.bracketRound,
        bracketPosition: m.bracketPosition,
        games: m.games || []
      }))
    }

    availableTeams.value = await teamService.getTeams()
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  await fetchTournamentData()
  
  // Subscribe to Mercure updates to keep all admins in sync
  subscribe(`tournament/${uuid}`, (data) => {
    if (data.matchId) {
      const matchId = Number(data.matchId);
      const match = matches.value.find(m => Number(m.id) === matchId);
      if (match) {
        match.scoreA = data.score1;
        match.scoreB = data.score2;
        
        // Also update team names if provided (for advancements)
        if (data.team1) match.teamA = data.team1;
        if (data.team2) match.teamB = data.team2;
      } else {
        // If match not found in current list, re-fetch
        fetchTournamentData(true);
      }
    }
  });
})

const handleStatusChange = async (newStatus) => {
  if (tournament.value.status === newStatus) return
  processing.value = true
  try {
    await tournamentService.updateTournament(uuid, { status: newStatus })
    toast.add({ severity: 'success', summary: t('common.success'), detail: t('tournament_admin.status.update_success'), life: 3000 })
    await fetchTournamentData()
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Error', detail: e.message, life: 5000 })
  } finally {
    processing.value = false
  }
}

const onEnrollmentSuccess = (newTeam) => {
  showEnrollmentDialog.value = false
  if (newTeam) {
    enrolledTeams.value.push(newTeam)
    if (tournament.value) tournament.value.teamsCount++
  }
  toast.add({ severity: 'success', summary: t('common.success'), detail: t('tournament_admin.enrollment.add_success'), life: 3000 })
}

const handleToggleConfirm = async (registrationId) => {
  try {
    const response = await tournamentService.toggleConfirmTeam(registrationId)
    const team = enrolledTeams.value.find(tt => tt.id === registrationId)
    if (team) {
      team.isConfirmed = response.isConfirmed
    }
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Error', detail: e.message, life: 5000 })
  }
}

const handleRemoveTeam = async (event, registrationId) => {
  confirm.require({
    target: event.currentTarget,
    message: t('tournament_admin.enrollment.delete_confirm_msg'),
    header: t('tournament_admin.enrollment.delete_confirm_title'),
    icon: 'pi pi-exclamation-triangle',
    rejectProps: {
        label: t('tournament_admin.match_edit.cancel_btn'),
        severity: 'secondary',
        outlined: true
    },
    acceptProps: {
        label: t('tournament_admin.enrollment.delete_btn') || 'Eliminar',
        severity: 'danger'
    },
    accept: async () => {
      try {
        await tournamentService.unenrollTeam(registrationId)
        enrolledTeams.value = enrolledTeams.value.filter(tt => tt.id !== registrationId)
        if (tournament.value) tournament.value.teamsCount--
        toast.add({ severity: 'success', summary: t('common.success'), detail: t('tournament_admin.enrollment.delete_success'), life: 3000 })
      } catch (e) {
        toast.add({ severity: 'error', summary: 'Error', detail: e.message, life: 5000 })
      }
    }
  })
}

const handleGenerateGroups = async () => {
  processing.value = true
  try {
    await tournamentService.generateGroups(uuid, groupsCount.value)
    toast.add({ severity: 'success', summary: t('common.success'), detail: t('tournament_mgmt.groups_success'), life: 3000 })
    await fetchTournamentData(true)
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Error', detail: e.message, life: 5000 })
  } finally {
    processing.value = false
  }
}

const handleGenerateMatches = async (event) => {
  confirm.require({
    target: event.currentTarget,
    message: t('tournament_admin.preparation.draw_confirm_msg'),
    header: t('tournament_admin.preparation.draw_confirm_title'),
    icon: 'pi pi-exclamation-triangle',
    rejectProps: {
        label: t('tournament_admin.match_edit.cancel_btn'),
        severity: 'secondary',
        outlined: true
    },
    acceptProps: {
        label: t('tournament_admin.preparation.draw_confirm_btn'),
        severity: 'primary'
    },
    accept: async () => {
      processing.value = true
      try {
        await tournamentService.generateMatches(uuid)
        // Automatically switch to active phase
        await tournamentService.updateTournament(uuid, { status: 'active' })
        const msg = tournament.value?.type === 'eliminatory' ? t('tournament_admin.preparation.draw_success') : t('dashboard.matches_success')
        toast.add({ severity: 'success', summary: t('common.success'), detail: msg, life: 3000 })
        await fetchTournamentData(true)
      } catch (e) {
        toast.add({ severity: 'error', summary: 'Error', detail: e.message, life: 5000 })
      } finally {
        processing.value = false
      }
    }
  })
}

const editGames = ref([])

const calculatedScore1 = computed(() => {
  return editGames.value.filter(g => g.points1 >= (tournament.value?.rulePoints || 40)).length
})

const calculatedScore2 = computed(() => {
  return editGames.value.filter(g => g.points2 >= (tournament.value?.rulePoints || 40)).length
})

const openEditModal = (match) => {
  editingMatch.value = match
  
  const toWin = tournament.value?.ruleGames || 3
  const maxPossibleGames = (toWin * 2) - 1
  const currentGames = match.games || []
  
  editGames.value = []
  for (let i = 0; i < maxPossibleGames; i++) {
    editGames.value.push({
      points1: currentGames[i]?.points1 || 0,
      points2: currentGames[i]?.points2 || 0
    })
  }
}

const setWinnerPoints = (game, team) => {
  const max = tournament.value?.rulePoints || 40
  if (team === 1) {
    game.points1 = max
    // If the other team has points, we might want to keep them or clear them. 
    // Usually in Mus, the loser keeps their points.
  } else {
    game.points2 = max
  }
}

const enforceMaxPoints = (game, team) => {
  const max = tournament.value?.rulePoints || 40
  if (team === 1) {
    if (game.points1 > max) game.points1 = max
  } else {
    if (game.points2 > max) game.points2 = max
  }
}

const adjustScore = (team, amount) => {
  const max = tournament.value?.ruleGames || 40
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
    await tournamentService.updateMatchScore(editingMatch.value.id, {
      games: editGames.value
    })
    
    // Update local match state reactively
    const match = matches.value.find(m => m.id === editingMatch.value.id)
    if (match) {
      match.scoreA = calculatedScore1.value
      match.scoreB = calculatedScore2.value
      match.status = (calculatedScore1.value > 0 || calculatedScore2.value > 0) ? 'finished' : 'pending'
      match.games = [...editGames.value]
    }

    editingMatch.value = null
    toast.add({ severity: 'success', summary: t('common.success'), detail: t('tournament_view.match_edit.save_success'), life: 3000 })
    
    // Refresh all tournament data to show updated next round matches reactively
    await fetchTournamentData(true)

    // Silent check for tournament finish
    const allFinished = matches.value.every(m => m.status === 'finished')
    if (allFinished && tournament.value.status === 'active') {
       tournament.value.status = 'finished'
    }
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Error', detail: e.message, life: 5000 })
  } finally {
    isSavingResult.value = false
  }
}

const stats = computed(() => {
  if (!tournament.value) return []
  const confirmedCount = enrolledTeams.value.filter(tt => tt.isConfirmed).length
  return [
    { label: t('tournament_view.tabs.teams'), value: tournament.value?.teamsCount || 0, icon: 'pi-users', color: '#e9c349' },
    { label: t('tournament_admin.stats.confirmed'), value: confirmedCount, icon: 'pi-verified', color: '#10b981' },
    { label: t('tournament_view.tabs.matches'), value: matches.value.length, icon: 'pi-calendar', color: '#f4d125' },
    { label: t('tournament_admin.stats.finished'), value: matches.value.filter(m => m.status === 'finished').length, icon: 'pi-check-circle', color: '#3b82f6' }
  ]
})

const groupedMatches = computed(() => {
  const groups = {}
  matches.value.forEach(match => {
    let stage = normalizeStageKey(match.stage)
    // If it's 3rd place, group it with final to show them together
    if (stage === 'third_place') stage = 'final'
    
    if (!groups[stage]) groups[stage] = []
    groups[stage].push(match)
  })
  
  let entries = Object.entries(groups)
  if (matchesOrder.value === 'desc') {
    entries.reverse()
  }
  return entries
})

const toggleStage = (stage) => {
  collapsedStages.value[stage] = !collapsedStages.value[stage]
}

const toggleMatchesOrder = () => {
  matchesOrder.value = matchesOrder.value === 'asc' ? 'desc' : 'asc'
}

const statusOptions = [
  { value: 'draft', label: t('tournament_form.statuses.draft'), icon: 'pi-pencil', color: '#94a3b8' },
  { value: 'pending', label: t('tournament_form.statuses.pending'), icon: 'pi-user-plus', color: '#3b82f6' },
  { value: 'active', label: t('tournament_form.statuses.active'), icon: 'pi-play', color: '#10b981' },
  { value: 'finished', label: t('tournament_form.statuses.finished'), icon: 'pi-flag-fill', color: '#e9c349' }
]

const goToPublicView = () => {
  window.open(`/tournament/${uuid}`, '_blank');
}

const normalizeStageKey = (stage) => {
  if (!stage) return 'general'
  const s = stage.toLowerCase().trim()
  
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
</script>

<template>
  <div class="admin-page">
    <div v-if="loading" class="flex items-center justify-center min-h-[600px]">
      <MusLoader />
    </div>

    <div v-else-if="error" class="max-w-2xl mx-auto mt-20">
      <div class="mus-card-premium text-center p-12 relative overflow-hidden">
        <div class="absolute top-0 left-0 w-full h-1 bg-rose-500"></div>
        <h2 class="text-3xl font-black text-main italic uppercase tracking-tight mb-4">{{ t('dashboard.error') }}</h2>
        <p class="text-slate-500 font-medium mb-8">{{ error }}</p>
        <button @click="router.push('/dashboard')" class="mus-btn-secondary px-8 py-4">
          <i class="pi pi-arrow-left mr-2"></i> {{ t('nav.dashboard') }}
        </button>
      </div>
    </div>

    <template v-else-if="tournament">
      <!-- Header Style Replicated from Dashboard -->
      <header class="mb-12 px-2">
        <div class="flex flex-column lg:flex-row justify-content-between align-items-center gap-8">
          <div class="flex align-items-center gap-6">
            <button @click="router.push('/my-tournaments')" class="back-btn" v-tooltip.right="t('tournament_admin.back_tooltip')">
              <i class="pi pi-chevron-left"></i>
            </button>
            <div>
              <div class="flex align-items-center gap-2 mb-1">
                <span class="text-slate-500 text-[9px] font-black uppercase tracking-[0.4em]">{{ t('tournament_admin.panel_title') }}</span>
                <span class="h-1 w-1 rounded-full bg-slate-700"></span>
                <span class="text-secondary text-[9px] font-black uppercase tracking-[0.2em] italic">{{ t('tournament_form.types.' + tournament.type) }}</span>
              </div>
              <h1 class="text-3xl md:text-5xl font-black text-white italic uppercase tracking-tighter m-0 leading-none">
                {{ tournament.name.split(' ').slice(0, -1).join(' ') }} <span class="text-secondary">{{ tournament.name.split(' ').slice(-1)[0] }}</span>
              </h1>
              <div class="flex align-items-center gap-6 text-slate-500 text-[10px] font-bold uppercase tracking-[0.2em] mt-3">
                <span class="flex align-items-center gap-2"><i class="pi pi-map-marker text-secondary/50"></i>{{ tournament.location || '---' }}</span>
                <div class="w-1 h-1 rounded-full bg-slate-800"></div>
                <span class="flex align-items-center gap-2"><i class="pi pi-calendar text-secondary/50"></i>{{ tournament.startDate ? new Date(tournament.startDate).toLocaleDateString() : '...' }}</span>
                <div class="w-1 h-1 rounded-full bg-slate-800"></div>
                <span class="text-secondary">{{ t('tournament_form.statuses.' + tournament.status) }}</span>
              </div>
            </div>
          </div>
          
          <div class="flex flex-column gap-2">
            <button @click="goToPublicView" class="eye-btn" v-tooltip.left="t('tournament_view.public_view') || 'Vista Pública'">
              <i class="pi pi-eye"></i>
            </button>
            <button @click="router.push(`/admin/tournament/${uuid}/edit`)" class="eye-btn" v-tooltip.left="t('common.edit') || 'Editar'">
              <i class="pi pi-pencil"></i>
            </button>
          </div>
        </div>
      </header>

      <!-- Stats Ribbon -->
      <div class="grid mt-4">
        <div v-for="stat in stats" :key="stat.label" class="col-12 md:col-3">
          <div class="stat-card-premium">
            <div class="stat-icon" :style="{ background: stat.color + '10', color: stat.color, borderColor: stat.color + '20' }">
              <i :class="['pi', stat.icon]"></i>
            </div>
            <div class="stat-content">
              <span class="stat-label">{{ stat.label }}</span>
              <span class="stat-value">{{ stat.value }}</span>
            </div>
            <div class="stat-glow" :style="{ background: stat.color }"></div>
          </div>
        </div>
      </div>

      <div class="grid mt-4">
        <!-- LEFT COLUMN: Enrollment & Teams (Only in Preparation) -->
        <div v-if="['draft', 'pending'].includes(tournament.status)" class="col-12 lg:col-5 space-y-8">
          <!-- Enrollment Panel -->
          <section v-if="['draft', 'pending'].includes(tournament.status)" class="side-panel-premium">
            <h3 class="panel-title"><i class="pi pi-user-plus"></i> {{ t('tournament_admin.enrollment.title') }}</h3>
            <div class="p-6">
              <button @click="showEnrollmentDialog = true" class="mus-btn-primary-large w-full">
                <i class="pi pi-plus"></i>
                <span>{{ t('tournament_admin.enrollment.add_btn') }}</span>
              </button>
            </div>
          </section>

          <!-- Enrolled Teams List -->
          <section v-if="['draft', 'pending'].includes(tournament.status)" class="side-panel-premium">
            <div class="panel-title flex align-items-center justify-content-between">
              <span><i class="pi pi-users"></i> {{ t('tournament_admin.enrollment.list_title') }}</span>
              <span class="text-xs opacity-40 font-black">{{ enrolledTeams.length }}</span>
            </div>
            <div class="p-4">
              <div v-if="enrolledTeams.length === 0" class="text-center py-10 opacity-30">
                <i class="pi pi-info-circle text-2xl mb-2"></i>
                <p class="text-[9px] font-black uppercase tracking-widest">{{ t('tournament_admin.enrollment.empty') }}</p>
              </div>
              <div v-else class="teams-list-scroll custom-scrollbar">
                <div v-for="tt in enrolledTeams" :key="tt.id" class="team-list-item flex align-items-center justify-content-between">
                  <div class="flex align-items-center gap-3">
                    <Checkbox :modelValue="tt.isConfirmed" :binary="true" @change="handleToggleConfirm(tt.id)" v-tooltip.top="t('tournament_admin.enrollment.confirmed_tooltip')" />
                    <div class="team-info">
                      <span class="name" :class="{ 'opacity-50': !tt.isConfirmed }">{{ tt.team?.name }}</span>
                      <span class="group-tag" v-if="tt.mesa">{{ tt.mesa }}</span>
                    </div>
                  </div>
                  <button v-if="['draft', 'pending'].includes(tournament.status)" @click="handleRemoveTeam($event, tt.id)" class="remove-btn" v-tooltip.left="t('tournament_admin.enrollment.delete_btn') || 'Eliminar'">
                    <i class="pi pi-times"></i>
                  </button>
                </div>
              </div>
            </div>
          </section>

          <!-- Logistics Panel -->
          <section v-if="tournament.type === 'groups' && ['draft', 'pending'].includes(tournament.status)" class="side-panel-premium">
            <h3 class="panel-title"><i class="pi pi-sitemap"></i> {{ t('tournament_admin.groups.config_title') }}</h3>
            <div class="p-6">
              <div class="flex flex-column gap-6">
                <div class="flex align-items-center justify-content-between">
                  <label class="text-[10px] font-black uppercase text-slate-500">{{ t('tournament_admin.groups.count_label') }}</label>
                  <div class="stepper-control">
                    <button @click="groupsCount = Math.max(2, groupsCount - 1)" class="stepper-action"><i class="pi pi-minus"></i></button>
                    <span class="stepper-value">{{ groupsCount }}</span>
                    <button @click="groupsCount++" class="stepper-action"><i class="pi pi-plus"></i></button>
                  </div>
                </div>
                <button @click="handleGenerateGroups" :disabled="processing || enrolledTeams.length < 2" class="mus-btn-secondary-large w-full">
                  <i class="pi pi-sync mr-2"></i> {{ t('tournament_admin.groups.generate_btn') }}
                </button>
              </div>
            </div>
          </section>
        </div>

        <!-- MAIN COLUMN: Status & Control -->
        <div :class="['draft', 'pending'].includes(tournament.status) ? 'col-12 lg:col-7' : 'col-12'" class="space-y-8">
          <!-- Phase / Status Control (Only in Preparation) -->
          <section v-if="['draft', 'pending'].includes(tournament.status)" class="side-panel-premium">
            <h3 class="panel-title"><i class="pi pi-step-forward"></i> {{ t('tournament_admin.status.title') }}</h3>
            <div class="p-6">
              <div class="grid">
                <div v-for="option in statusOptions" :key="option.value" class="col-3">
                  <button @click="handleStatusChange(option.value)"
                        :disabled="processing"
                        class="status-toggle-btn"
                        :class="{ active: tournament.status === option.value }">
                  <i :class="['pi', option.icon, 'mb-2']"></i>
                  <span>{{ option.label }}</span>
                  <div class="active-indicator" :style="{ background: option.color }"></div>
                </button>
              </div>
            </div>
          </div>
        </section>

          <!-- Preparation/Active View -->
          <div class="w-full">
            <!-- PREPARATION MODE (Draft/Pending) -->
            <section v-if="['draft', 'pending'].includes(tournament.status)" class="preparation-mode">
              <div class="mus-card-premium p-10 text-center border-dashed border-white/10">
                <div class="section-icon mx-auto mb-6 w-20 h-20 bg-secondary/10 flex items-center justify-center rounded-full border border-secondary/20">
                  <i class="pi pi-cog text-3xl text-secondary animate-spin-slow"></i>
                </div>
                <h2 class="text-3xl font-black text-main italic uppercase tracking-tighter mb-4">{{ t('tournament_admin.preparation.title') }}</h2>
                <p class="text-slate-500 font-bold text-xs uppercase tracking-widest max-w-md mx-auto mb-10 leading-relaxed">
                  {{ enrolledTeams.length < 2 ? t('tournament_admin.preparation.not_ready_desc') : t('tournament_admin.preparation.ready_desc') }}
                </p>
                
                <div class="flex flex-column items-center gap-6">
                  <div class="flex align-items-center gap-10 bg-black/40 p-6 rounded-2xl border border-white/5">
                    <div class="text-center">
                      <span class="block text-[9px] font-black text-slate-600 uppercase mb-1">{{ t('tournament_admin.preparation.teams_label') }}</span>
                      <span class="text-2xl font-black italic text-white">{{ enrolledTeams.length }}</span>
                    </div>
                    <div class="h-8 w-px bg-white/10"></div>
                    <div class="text-center">
                      <span class="block text-[9px] font-black text-slate-600 uppercase mb-1">{{ t('tournament_admin.preparation.system_label') }}</span>
                      <span class="text-2xl font-black italic text-secondary">{{ t('tournament_form.types.' + tournament.type) }}</span>
                    </div>
                  </div>

                  <button @click="handleGenerateMatches($event)" 
                          :disabled="processing || enrolledTeams.length < 2" 
                          class="mus-btn-primary px-6 py-3 text-sm">
                    <i class="pi pi-bolt mr-2 text-xs"></i>
                    {{ tournament.type === 'eliminatory' ? t('tournament_admin.preparation.initial_draw') : t('tournament_mgmt.generate_matches') }}
                  </button>
                </div>
              </div>
            </section>

            <!-- ACTIVE MODE (Active/Finished) -->
            <section v-else class="active-tournament-view">
               <div class="flex align-items-center justify-content-between mb-8 px-2">
                  <h3 class="m-0 text-main font-black uppercase italic tracking-tighter text-2xl">
                     <i class="pi pi-calendar mr-3 text-secondary"></i>
                     {{ t('tournament_view.tabs.matches') }}
                  </h3>
                  <button @click="toggleMatchesOrder" class="mus-btn-secondary px-4 py-2 text-[9px] gap-2">
                     <i :class="matchesOrder === 'asc' ? 'pi pi-sort-amount-up' : 'pi pi-sort-amount-down'"></i>
                     {{ matchesOrder === 'asc' ? t('tournament_admin.matches.order_asc') : t('tournament_admin.matches.order_desc') }}
                  </button>
               </div>

               <div v-if="matches.length === 0" class="text-center py-20 opacity-30">
                 <div class="w-20 h-20 bg-white/5 rounded-full flex items-center justify-center mx-auto mb-6 border border-white/10">
                   <i class="pi pi-calendar-times text-4xl text-slate-700"></i>
                 </div>
                 <h3 class="text-main font-black uppercase italic mb-2">{{ t('tournament_admin.matches.no_matchups') }}</h3>
                 <p class="text-slate-500 font-bold text-xs uppercase tracking-widest mb-8">{{ t('tournament_admin.matches.error_desc') }}</p>
               </div>

               <div v-else class="space-y-12">
                <div v-for="([stage, stageMatches]) in groupedMatches" :key="stage" class="stage-group">
                  <div class="stage-header flex align-items-center gap-4 mb-6 cursor-pointer hover:opacity-80 transition-all" @click="toggleStage(stage)">
                    <span class="stage-dot" :class="{ 'is-collapsed': collapsedStages[stage] }"></span>
                    <h4 class="m-0 text-secondary font-black italic uppercase tracking-[0.25em] text-sm">
                      {{ stage === 'final' && stageMatches.some(m => normalizeStageKey(m.stage) === 'third_place') ? t('tournament_view.knockout.final') + ' & ' + t('tournament_view.knockout.third_place') : (t('tournament_view.knockout.' + stage) || t('tournament_view.rounds.' + stage) || stage) }}
                    </h4>
                    <div class="h-px flex-1 bg-gradient-to-r from-secondary/20 to-transparent"></div>
                    <i class="pi text-xs text-slate-500" :class="collapsedStages[stage] ? 'pi-chevron-down' : 'pi-chevron-up'"></i>
                  </div>
                  
                    <div v-show="!collapsedStages[stage]" class="grid">
                      <div v-for="match in stageMatches" :key="match.id" 
                           @click="openEditModal(match)"
                           :class="['final', 'third_place'].includes(normalizeStageKey(match.stage)) ? 'col-12 md:col-6 mb-4' : 'col-12 md:col-6 lg:col-3'">
                      <div class="match-card-premium group">
                      <div class="match-card-bg"></div>
                      <div class="relative z-10">
                        <div class="flex justify-content-between align-items-center mb-4">
                          <div class="flex flex-column gap-1">
                            <span class="text-[8px] font-black text-secondary uppercase tracking-[0.2em]">
                               {{ t('tournament_view.knockout.' + normalizeStageKey(match.stage)) || t('tournament_view.rounds.' + normalizeStageKey(match.stage)) || match.stage }}
                             </span>
                             <span class="match-status-tag" :class="{ 'is-finished': match.status === 'finished' }">
                               {{ t('tournament_view.match_status.' + match.status) }}
                             </span>
                          </div>
                          <div class="match-edit-icon"><i class="pi pi-pencil"></i></div>
                        </div>
                        <div class="team-row">
                          <span class="team-name" :class="{ 'is-winner': match.scoreA > match.scoreB }">{{ match.teamA || t('tournament_admin.matches.tbd') }}</span>
                          <span class="team-score" :class="{ 'is-winner': match.scoreA > match.scoreB }">{{ match.scoreA }}</span>
                        </div>
                        <div class="team-row mt-2">
                          <span class="team-name" :class="{ 'is-winner': match.scoreB > match.scoreA }">{{ match.teamB || t('tournament_admin.matches.tbd') }}</span>
                          <span class="text-slate-700 font-black italic text-xl" v-if="match.scoreA === 0 && match.scoreB === 0 && !match.teamA && !match.teamB">VS</span>
                          <span v-else class="team-score" :class="{ 'is-winner': match.scoreB > match.scoreA }">{{ match.scoreB }}</span>
                        </div>
                      </div>
                    </div>
                </div>
              </div>
            </div>
          </div>
        </section>
      </div>
    </div>
  </div>
</template>

    <!-- Score Edit Dialog (Redesigned for Games - Side by Side - Ultra Wide - Fixed) -->
    <Dialog v-model:visible="editingMatch" modal :header="t('tournament_admin.match_edit.title')" :style="{ width: '1000px' }" :dismissableMask="true" class="mus-dialog-premium no-scroll">
      <div v-if="editingMatch" class="p-0 overflow-hidden">
        <div class="grid grid-nogutter">
          <!-- LEFT COLUMN: INFO & SUMMARY (Horizontal alignment - Optimized) -->
          <div class="col-12 md:col-6 p-5 bg-white/2 border-right border-white/5">
            <div class="flex flex-column h-full">
              <div class="flex align-items-center gap-3 mb-6">
                <div class="h-1 w-6 bg-secondary rounded-full"></div>
                <span class="text-[9px] font-black text-secondary uppercase tracking-[0.4em]">{{ t('tournament_admin.match_edit.panel_title') }}</span>
              </div>

              <!-- Teams Scoreboard Style -->
              <div class="scoreboard-premium">
                  <div class="flex justify-content-between align-items-center gap-4 mb-6">
                    <div class="flex-1 min-w-0">
                      <span class="text-[9px] font-black text-secondary/60 uppercase tracking-[0.3em] block mb-1">{{ t('tournament_view.match_edit.local') }}</span>
                      <div class="text-xl font-black text-white italic truncate" v-tooltip.bottom="editingMatch.teamA">{{ editingMatch.teamA }}</div>
                    </div>
                    <div class="flex-shrink-0 px-2">
                      <span class="text-xl font-black italic text-white/10">VS</span>
                    </div>
                    <div class="flex-1 min-w-0 text-right">
                      <span class="text-[9px] font-black text-secondary/60 uppercase tracking-[0.3em] block mb-1">{{ t('tournament_view.match_edit.visitor') }}</span>
                      <div class="text-xl font-black text-white italic truncate" v-tooltip.bottom="editingMatch.teamB">{{ editingMatch.teamB }}</div>
                    </div>
                 </div>
                 <div class="match-summary-panel p-6 bg-secondary/10 border border-secondary/20 rounded-[32px] text-center shadow-2xl relative overflow-hidden">
                    <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-transparent via-secondary to-transparent opacity-20"></div>
                    <span class="text-[9px] font-black text-secondary uppercase tracking-[0.5em] block mb-4">{{ t('tournament_admin.match_edit.score_games') }}</span>
                    <div class="flex align-items-center justify-content-center gap-10">
                      <div class="flex flex-column align-items-center">
                        <span class="text-7xl font-black italic text-white leading-none tracking-tighter">{{ calculatedScore1 }}</span>
                      </div>
                      <div class="h-16 w-px bg-white/10 mx-4"></div>
                      <div class="flex flex-column align-items-center">
                        <span class="text-7xl font-black italic text-white leading-none tracking-tighter">{{ calculatedScore2 }}</span>
                      </div>
                    </div>
                 </div>
              </div>

              <div class="rules-hint mt-6 p-4 bg-emerald-500/10 border border-emerald-500/20 rounded-2xl flex align-items-center gap-4">
                <i class="pi pi-info-circle text-emerald-500 text-lg"></i>
                <div class="flex flex-column">
                  <span class="text-[9px] font-black text-white uppercase tracking-widest">{{ t('tournament_admin.match_edit.regulation') }}</span>
                  <span v-if="tournament" class="text-[9px] font-bold text-emerald-500/80 uppercase tracking-widest">
                    {{ t('tournament_admin.match_edit.best_of', { 
                      total: (tournament?.ruleGames || 3) * 2 - 1, 
                      needed: tournament?.ruleGames || 3, 
                      points: tournament?.rulePoints || 40 
                    }) }}
                  </span>
                </div>
              </div>

              <!-- MOVED ACTIONS: NOW ON THE LEFT -->
              <div class="dialog-actions mt-8 grid grid-cols-2 gap-4">
                <button @click="editingMatch = null" class="mus-btn-secondary px-4 py-3 text-[10px]">{{ t('tournament_admin.match_edit.cancel_btn') }}</button>
                <button @click="saveMatchResult" :disabled="isSavingResult" class="mus-btn-primary px-4 py-3 text-[10px]">
                  <i v-if="isSavingResult" class="pi pi-spin pi-spinner mr-2"></i>
                  {{ isSavingResult ? t('tournament_admin.match_edit.saving_btn') : t('tournament_admin.match_edit.save_btn') }}
                </button>
              </div>
            </div>
          </div>

          <!-- RIGHT COLUMN: GAMES EDITOR -->
          <div class="col-12 md:col-6 p-5">
            <div class="flex align-items-center justify-content-between mb-6">
               <h4 class="m-0 text-white font-black italic uppercase tracking-widest">{{ t('tournament_admin.match_edit.games_breakdown') }}</h4>
               <span v-if="tournament" class="text-[10px] font-bold text-slate-500">{{ t('tournament_admin.match_edit.points_per_game', { points: tournament?.rulePoints || 40 }) }}</span>
            </div>

            <div class="games-editor-container">
               <div class="grid mb-4 px-4 opacity-40">
                 <div class="col-2 text-[9px] font-black uppercase tracking-[0.2em]">{{ t('tournament_admin.match_edit.game_label') }}</div>
                 <div class="col-5 text-[9px] font-black uppercase tracking-[0.2em] text-center">{{ t('tournament_view.match_edit.local') }}</div>
                 <div class="col-5 text-[9px] font-black uppercase tracking-[0.2em] text-center">{{ t('tournament_view.match_edit.visitor') }}</div>
               </div>

               <div class="games-list custom-scrollbar max-h-[500px] overflow-y-auto pr-2">
                 <div v-for="(game, index) in editGames" :key="index" 
                      class="game-row flex align-items-center mb-2 p-2 bg-white/5 rounded-2xl border border-white/5 hover:border-secondary/20 transition-all">
                    <div class="col-2 flex align-items-center justify-content-center">
                      <span class="text-[10px] font-black uppercase text-slate-500 tracking-widest">{{ index + 1 }}º</span>
                    </div>
                    <div class="col-5 px-4">
                      <div class="score-input-wrapper relative">
                        <input type="number" 
                               v-model.number="game.points1" 
                               :min="0" 
                               :max="tournament?.rulePoints || 40"
                               @input="enforceMaxPoints(game, 1)"
                               class="game-score-field text-sm"
                               :class="{ 'is-winner': game.points1 >= (tournament?.rulePoints || 40) }">
                        <button @click="setWinnerPoints(game, 1)" class="quick-win-btn" v-tooltip.top="t('tournament_admin.match_edit.win_tooltip')">
                          <i class="pi pi-check"></i>
                        </button>
                      </div>
                    </div>
                    <div class="col-5 px-4">
                      <div class="score-input-wrapper relative">
                        <input type="number" 
                               v-model.number="game.points2" 
                               :min="0" 
                               :max="tournament?.rulePoints || 40"
                               @input="enforceMaxPoints(game, 2)"
                               class="game-score-field text-sm"
                               :class="{ 'is-winner': game.points2 >= (tournament?.rulePoints || 40) }">
                        <button @click="setWinnerPoints(game, 2)" class="quick-win-btn" v-tooltip.top="t('tournament_admin.match_edit.win_tooltip')">
                          <i class="pi pi-check"></i>
                        </button>
                      </div>
                    </div>
                 </div>
               </div>
            </div>
          </div>
        </div>
      </div>
    </Dialog>

    <!-- Enrollment Dialog -->
    <Dialog v-model:visible="showEnrollmentDialog" modal :header="t('tournament_admin.enrollment.dialog_title')" :style="{ width: '450px' }" class="mus-dialog-premium" @show="onEnrollmentDialogShow">
      <div class="p-4">
        <TournamentEnrollmentForm ref="enrollmentForm" :tournamentUuid="uuid" @success="onEnrollmentSuccess" @cancel="showEnrollmentDialog = false" />
      </div>
    </Dialog>
  </div>
</template>

<style scoped>
.admin-page { 
  max-width: 1500px; 
  margin: 0 auto; 
  padding: 40px 60px 100px 60px; 
  opacity: 1 !important; 
  visibility: visible !important; 
  position: relative;
  z-index: 1;
}

/* Header & Back Button */
.back-btn { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); width: 40px; height: 40px; border-radius: 12px; color: #64748b; cursor: pointer; transition: 0.3s; display: flex; align-items: center; justify-content: center; }
.back-btn:hover { background: rgba(255,255,255,0.08); color: white; border-color: rgba(255,255,255,0.2); transform: translateX(-3px); }
.eye-btn { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); width: 40px; height: 40px; border-radius: 12px; color: #64748b; cursor: pointer; transition: 0.3s; display: flex; align-items: center; justify-content: center; }
.eye-btn:hover { background: rgba(255,255,255,0.08); color: var(--secondary); border-color: var(--secondary); transform: translateX(3px); }

/* Buttons */
.mus-btn-primary { 
  background: linear-gradient(135deg, #bf953f 0%, #fcf6ba 50%, #aa771c 100%); 
  color: #050505; 
  border: none; 
  font-weight: 950; 
  text-transform: uppercase; 
  letter-spacing: 0.1em; 
  border-radius: 18px; 
  cursor: pointer; 
  transition: all 0.3s; 
  padding: 12px 24px;
  display: flex;
  align-items: center;
  justify-content: center;
}
.mus-btn-primary:hover:not(:disabled) { 
  transform: translateY(-3px); 
  box-shadow: 0 15px 35px -10px rgba(191, 149, 63, 0.5); 
}
.mus-btn-primary:disabled { opacity: 0.5; cursor: not-allowed; filter: grayscale(1); }

.mus-btn-secondary { 
  background: rgba(255, 255, 255, 0.05); 
  border: 1px solid rgba(255, 255, 255, 0.1); 
  color: white; 
  font-weight: 950; 
  text-transform: uppercase; 
  letter-spacing: 0.1em; 
  border-radius: 18px; 
  cursor: pointer; 
  transition: all 0.3s; 
  display: flex;
  align-items: center;
  justify-content: center;
}
.mus-btn-secondary:hover { 
  background: rgba(255, 255, 255, 0.1); 
  border-color: rgba(255, 255, 255, 0.2); 
}

.mus-btn-primary-large { 
  background: linear-gradient(135deg, #bf953f 0%, #fcf6ba 50%, #aa771c 100%); 
  color: #050505; 
  border: none; 
  font-weight: 950; 
  text-transform: uppercase; 
  letter-spacing: 0.1em; 
  border-radius: 18px; 
  padding: 18px;
  cursor: pointer; 
  transition: all 0.3s; 
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
}
.mus-btn-primary-large:hover:not(:disabled) { 
  transform: translateY(-3px); 
  box-shadow: 0 15px 35px -10px rgba(191, 149, 63, 0.5); 
}
.mus-btn-primary-large:disabled { opacity: 0.5; cursor: not-allowed; filter: grayscale(1); }

.mus-btn-secondary-large { 
  background: rgba(255, 255, 255, 0.05); 
  border: 1px solid rgba(255, 255, 255, 0.1); 
  color: white; 
  font-weight: 950; 
  text-transform: uppercase; 
  letter-spacing: 0.1em; 
  border-radius: 18px; 
  padding: 18px;
  cursor: pointer; 
  transition: all 0.3s; 
  display: flex;
  align-items: center;
  justify-content: center;
}
.mus-btn-secondary-large:hover { 
  background: rgba(255, 255, 255, 0.1); 
  border-color: rgba(255, 255, 255, 0.2); 
}

/* Side Panels */
.side-panel-premium { 
  background: rgba(255, 255, 255, 0.02); 
  border: 1px solid rgba(255, 255, 255, 0.05); 
  border-radius: 32px; 
  overflow: hidden;
  backdrop-filter: blur(20px);
}
.panel-title { 
  background: rgba(255, 255, 255, 0.03); 
  padding: 18px 24px; 
  margin: 0; 
  font-size: 11px; 
  font-weight: 950; 
  text-transform: uppercase; 
  letter-spacing: 0.2em; 
  color: #64748b; 
  display: flex; 
  align-items: center; 
  gap: 12px; 
  border-bottom: 1px solid rgba(255, 255, 255, 0.05); 
}
.panel-title i { color: var(--secondary); font-size: 14px; }

/* Status Toggles */
.status-toggle-btn { 
  width: 100%; 
  aspect-ratio: 1/1; 
  background: rgba(255, 255, 255, 0.02); 
  border: 1px solid rgba(255, 255, 255, 0.05); 
  border-radius: 24px; 
  color: #64748b; 
  display: flex; 
  flex-direction: column; 
  align-items: center; 
  justify-content: center; 
  gap: 8px; 
  cursor: pointer; 
  transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); 
  position: relative; 
  overflow: hidden;
}
.status-toggle-btn i { font-size: 20px; }
.status-toggle-btn span { font-size: 9px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.1em; }
.status-toggle-btn:hover:not(:disabled) { background: rgba(255, 255, 255, 0.08); color: white; transform: scale(1.05); }
.status-toggle-btn.active { background: rgba(255, 255, 255, 0.1); color: white; border-color: rgba(255,255,255,0.2); }
.active-indicator { position: absolute; bottom: 0; left: 0; width: 100%; height: 4px; opacity: 0; transition: 0.3s; }
.status-toggle-btn.active .active-indicator { opacity: 1; }

/* Team List */
.teams-list-scroll { max-height: 400px; overflow-y: auto; padding-right: 8px; }
.team-list-item { 
  padding: 14px 18px; 
  background: rgba(255,255,255,0.02); 
  border: 1px solid rgba(255,255,255,0.05); 
  border-radius: 20px; 
  margin-bottom: 8px; 
  transition: 0.3s; 
}
.team-list-item:hover { background: rgba(255,255,255,0.05); transform: translateX(5px); }
.team-info { display: flex; flex-direction: column; gap: 2px; }
.team-info .name { font-weight: 900; font-style: italic; color: white; font-size: 13px; text-transform: uppercase; }
.team-info .group-tag { font-size: 8px; font-weight: 900; color: var(--secondary); background: rgba(191, 149, 63, 0.1); padding: 2px 8px; border-radius: 99px; width: fit-content; }
.remove-btn { background: none; border: none; color: #f43f5e; cursor: pointer; padding: 8px; border-radius: 10px; transition: 0.2s; opacity: 0.3; }
.team-list-item:hover .remove-btn { opacity: 1; }
.remove-btn:hover { background: rgba(244, 63, 94, 0.1); transform: scale(1.2); }

/* Stats */
.stat-card-premium { 
  background: rgba(255, 255, 255, 0.02); 
  border: 1px solid rgba(255, 255, 255, 0.05); 
  padding: 24px; 
  border-radius: 32px; 
  display: flex; 
  align-items: center; 
  gap: 20px; 
  position: relative; 
  overflow: hidden;
  backdrop-filter: blur(10px);
}
.stat-icon { width: 56px; height: 56px; border-radius: 20px; display: flex; align-items: center; justify-content: center; font-size: 22px; border: 1px solid; }
.stat-content { display: flex; flex-direction: column; }
.stat-label { font-size: 9px; font-weight: 950; text-transform: uppercase; letter-spacing: 0.2em; color: #64748b; margin-bottom: 4px; }
.stat-value { font-size: 28px; font-weight: 950; color: white; line-height: 1; font-style: italic; }
.stat-glow { position: absolute; top: 0; right: 0; width: 60px; height: 60px; filter: blur(40px); opacity: 0.1; }

/* Stepper */
.stepper-control { display: flex; align-items: center; gap: 15px; background: rgba(0,0,0,0.2); padding: 8px; border-radius: 20px; border: 1px solid rgba(255,255,255,0.05); }
.stepper-action { background: rgba(255,255,255,0.05); border: none; width: 32px; height: 32px; border-radius: 14px; color: white; cursor: pointer; transition: 0.2s; }
.stepper-action:hover { background: var(--secondary); color: black; }
.stepper-value { font-size: 18px; font-weight: 950; width: 30px; text-align: center; color: var(--secondary); font-style: italic; }

/* Match Cards */
.match-card-premium { 
  background: rgba(255, 255, 255, 0.03); 
  border: 1px solid rgba(255, 255, 255, 0.05); 
  border-radius: 28px; 
  padding: 24px; 
  position: relative; 
  overflow: hidden; 
  cursor: pointer; 
  transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); 
  height: 100%;
}
.match-card-premium:hover { transform: translateY(-8px) scale(1.02); border-color: var(--secondary); box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5); }
.match-card-bg { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: linear-gradient(135deg, rgba(191, 149, 63, 0.05) 0%, transparent 100%); opacity: 0; transition: 0.4s; }
.match-card-premium:hover .match-card-bg { opacity: 1; }
.match-status-tag { font-size: 8px; font-weight: 950; text-transform: uppercase; padding: 4px 10px; border-radius: 8px; background: rgba(255,255,255,0.05); color: #64748b; letter-spacing: 0.1em; }
.match-status-tag.is-finished { background: rgba(16, 185, 129, 0.1); color: #10b981; }
.match-edit-icon { width: 32px; height: 32px; background: rgba(255,255,255,0.05); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: var(--secondary); opacity: 0; transition: 0.3s; transform: scale(0.5); }
.match-card-premium:hover .match-edit-icon { opacity: 1; transform: scale(1); }
.team-row { display: flex; justify-content: space-between; align-items: center; }
.team-name { font-size: 15px; font-weight: 900; color: #94a3b8; text-transform: uppercase; font-style: italic; transition: 0.3s; flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; padding-right: 15px; }
.team-name.is-winner { color: white; }
.team-score { font-size: 24px; font-weight: 950; color: #334155; font-style: italic; transition: 0.3s; }
.team-score.is-winner { color: var(--secondary); }

.stage-dot { width: 12px; height: 12px; border-radius: 4px; background: var(--secondary); box-shadow: 0 0 15px var(--secondary); transition: 0.3s; }
.stage-dot.is-collapsed { background: #475569; box-shadow: none; }

/* Scoreboard Premium Styles */
.scoreboard-premium { 
  padding: 10px;
}

.match-summary-panel { 
  background: rgba(191, 149, 63, 0.08) !important;
}

.game-score-field { 
  width: 100%;
  background: rgba(0, 0, 0, 0.3);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 12px;
  color: white;
  padding: 12px 15px;
  text-align: center;
  font-weight: 900;
  font-family: 'Inter', sans-serif;
  transition: all 0.3s;
  outline: none;
}
.game-score-field:focus { 
  border-color: var(--secondary);
  background: rgba(0,0,0,0.5);
}
.game-score-field.is-winner { 
  color: var(--secondary);
  border-color: rgba(191, 149, 63, 0.4);
  background: rgba(191, 149, 63, 0.1);
}

.quick-win-btn { 
  position: absolute;
  right: -5px;
  top: 50%;
  transform: translateY(-50%);
  background: var(--secondary);
  color: black;
  border: none;
  width: 24px;
  height: 24px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 10px;
  cursor: pointer;
  transition: 0.2s;
  opacity: 0;
  pointer-events: none;
}
.score-input-wrapper:hover .quick-win-btn { 
  opacity: 1;
  pointer-events: auto;
  right: 8px;
}
.quick-win-btn:hover { 
  transform: translateY(-50%) scale(1.1);
}

/* Animations */
@keyframes spin-slow {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}
.animate-spin-slow {
  animation: spin-slow 8s linear infinite;
}

/* Scrollbar */
.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.2); }

/* Dialog overrides */
:deep(.no-scroll .p-dialog-content) {
  overflow: hidden !important;
}
</style>
