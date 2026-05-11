<script setup>
import { ref, onMounted, computed } from 'vue'
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

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
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
        status: m.score1 > 0 || m.score2 > 0 ? t('tournament_view.match_status.finished') : t('tournament_view.match_status.pending'),
        teamA: m.team1,
        scoreA: m.score1,
        teamB: m.team2,
        scoreB: m.score2,
        stage: m.stage,
        bracketRound: m.bracketRound,
        bracketPosition: m.bracketPosition
      }))
    }

    availableTeams.value = await teamService.getTeams()
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}

onMounted(fetchTournamentData)

const handleStatusChange = async (newStatus) => {
  if (tournament.value.status === newStatus) return
  processing.value = true
  try {
    await tournamentService.updateTournament(uuid, { status: newStatus })
    toast.add({ severity: 'success', summary: t('common.success'), detail: 'Fase actualizada', life: 3000 })
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
  toast.add({ severity: 'success', summary: t('common.success'), detail: 'Pareja inscrita correctamente', life: 3000 })
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
    message: '¿Estás seguro de que quieres desapuntar a esta pareja? Esta acción no se puede deshacer.',
    header: 'Confirmar Eliminación',
    icon: 'pi pi-exclamation-triangle',
    rejectProps: {
        label: 'Cancelar',
        severity: 'secondary',
        outlined: true
    },
    acceptProps: {
        label: 'Eliminar',
        severity: 'danger'
    },
    accept: async () => {
      try {
        await tournamentService.unenrollTeam(registrationId)
        enrolledTeams.value = enrolledTeams.value.filter(tt => tt.id !== registrationId)
        if (tournament.value) tournament.value.teamsCount--
        toast.add({ severity: 'success', summary: t('common.success'), detail: 'Pareja eliminada correctamente', life: 3000 })
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

const handleGenerateMatches = async () => {
  processing.value = true
  try {
    await tournamentService.generateMatches(uuid)
    // Automatically switch to active phase
    await tournamentService.updateTournament(uuid, { status: 'active' })
    const msg = tournament.value.type === 'eliminatory' ? 'Sorteo realizado con éxito. ¡Torneo en marcha!' : t('dashboard.matches_success')
    toast.add({ severity: 'success', summary: t('common.success'), detail: msg, life: 3000 })
    await fetchTournamentData(true)
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Error', detail: e.message, life: 5000 })
  } finally {
    processing.value = false
  }
}

const openEditModal = (match) => {
  editingMatch.value = match
  editScore1.value = match.scoreA
  editScore2.value = match.scoreB
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
    await tournamentService.updateMatchScore(editingMatch.value.id, editScore1.value, editScore2.value)
    
    // Update local match state reactively
    const match = matches.value.find(m => m.id === editingMatch.value.id)
    if (match) {
      match.scoreA = editScore1.value
      match.scoreB = editScore2.value
      match.status = (editScore1.value > 0 || editScore2.value > 0) ? t('tournament_view.match_status.finished') : t('tournament_view.match_status.pending')
    }

    editingMatch.value = null
    toast.add({ severity: 'success', summary: t('common.success'), detail: t('tournament_view.match_edit.save_success'), life: 3000 })
    
    // Silent check for tournament finish
    const allFinished = matches.value.every(m => m.status === t('tournament_view.match_status.finished'))
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
    { label: t('tournament_view.tabs.teams'), value: tournament.value.teamsCount || 0, icon: 'pi-users', color: '#e9c349' },
    { label: 'Confirmadas', value: confirmedCount, icon: 'pi-verified', color: '#10b981' },
    { label: t('tournament_view.tabs.matches'), value: matches.value.length, icon: 'pi-calendar', color: '#f4d125' },
    { label: 'Finalizados', value: matches.value.filter(m => m.status === t('tournament_view.match_status.finished')).length, icon: 'pi-check-circle', color: '#3b82f6' }
  ]
})

const groupedMatches = computed(() => {
  const acc = {}
  
  // Sort matches by bracketRound descending and bracketPosition ascending
  const sortedMatches = [...matches.value].sort((a, b) => {
    if (a.bracketRound !== b.bracketRound) return b.bracketRound - a.bracketRound;
    return a.bracketPosition - b.bracketPosition;
  });

  sortedMatches.forEach(match => {
    const stage = match.stage || t('tournament_view.rounds.general')
    if (!acc[stage]) acc[stage] = []
    acc[stage].push(match)
  })
  
  return Object.entries(acc)
})

const statusOptions = [
  { value: 'draft', label: t('tournament_form.statuses.draft'), icon: 'pi-pencil', color: '#94a3b8' },
  { value: 'pending', label: t('tournament_form.statuses.pending'), icon: 'pi-user-plus', color: '#3b82f6' },
  { value: 'active', label: t('tournament_form.statuses.active'), icon: 'pi-play', color: '#10b981' },
  { value: 'finished', label: t('tournament_form.statuses.finished'), icon: 'pi-flag-fill', color: '#e9c349' }
]
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
      <!-- Header Section -->
      <header class="admin-header mb-12">
        <div class="flex flex-column lg:flex-row justify-content-between align-items-center gap-12">
          <div class="flex align-items-center gap-10 mr-4">
            <button @click="router.push('/my-tournaments')" class="back-btn" v-tooltip.right="'Volver a Mis Torneos'">
              <i class="pi pi-chevron-left"></i>
            </button>
            <div class="flex flex-column gap-2">
              <div class="flex align-items-center gap-2">
                <span class="text-slate-500 text-[9px] font-black uppercase tracking-[0.4em]">Panel de Gestión</span>
                <span class="h-1 w-1 rounded-full bg-slate-700"></span>
                <span class="text-secondary text-[9px] font-black uppercase tracking-[0.2em] italic">{{ t('tournament_form.types.' + tournament.type) }}</span>
              </div>
              <div class="flex align-items-center gap-6">
                <h1 class="text-3xl md:text-5xl font-black text-main italic uppercase tracking-tighter m-0 leading-none">{{ tournament.name }}</h1>
                <div class="h-10 w-px bg-white/10 hidden md:block"></div>
                <div class="flex align-items-center gap-6 text-slate-500 text-xs font-bold uppercase tracking-widest">
                  <span class="flex align-items-center gap-2"><i class="pi pi-map-marker text-secondary"></i>{{ tournament.location || '---' }}</span>
                  <span class="flex align-items-center gap-2"><i class="pi pi-calendar text-secondary"></i>{{ tournament.startDate ? new Date(tournament.startDate).toLocaleDateString() : '...' }}</span>
                </div>
                <Tag :value="t('tournament_form.statuses.' + tournament.status)" severity="success" class="mus-tag-gold" />
              </div>
            </div>
          </div>
          
          <div class="flex gap-4">
            <button @click="router.push(`/tournament/${uuid}`)" class="mus-btn-secondary px-8 py-4">
              <i class="pi pi-eye mr-2"></i> {{ t('tournament_view.public_view') || 'Vista Pública' }}
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
            <h3 class="panel-title"><i class="pi pi-user-plus"></i> Inscripción de Parejas</h3>
            <div class="p-6">
              <button @click="showEnrollmentDialog = true" class="mus-btn-primary-large w-full">
                <i class="pi pi-plus"></i>
                <span>INSCRIBIR NUEVA PAREJA</span>
              </button>
            </div>
          </section>

          <!-- Enrolled Teams List -->
          <section v-if="['draft', 'pending'].includes(tournament.status)" class="side-panel-premium">
            <div class="panel-title flex align-items-center justify-content-between">
              <span><i class="pi pi-users"></i> Parejas Inscritas</span>
              <span class="text-xs opacity-40 font-black">{{ enrolledTeams.length }}</span>
            </div>
            <div class="p-4">
              <div v-if="enrolledTeams.length === 0" class="text-center py-10 opacity-30">
                <i class="pi pi-info-circle text-2xl mb-2"></i>
                <p class="text-[9px] font-black uppercase tracking-widest">No hay parejas todavía</p>
              </div>
              <div v-else class="teams-list-scroll custom-scrollbar">
                <div v-for="tt in enrolledTeams" :key="tt.id" class="team-list-item flex align-items-center justify-content-between">
                  <div class="flex align-items-center gap-3">
                    <Checkbox :modelValue="tt.isConfirmed" :binary="true" @change="handleToggleConfirm(tt.id)" v-tooltip.top="'Confirmado / Pagado'" />
                    <div class="team-info">
                      <span class="name" :class="{ 'opacity-50': !tt.isConfirmed }">{{ tt.team?.name }}</span>
                      <span class="group-tag" v-if="tt.groupName">{{ tt.groupName }}</span>
                    </div>
                  </div>
                  <button v-if="['draft', 'pending'].includes(tournament.status)" @click="handleRemoveTeam($event, tt.id)" class="remove-btn" v-tooltip.left="'Eliminar'">
                    <i class="pi pi-times"></i>
                  </button>
                </div>
              </div>
            </div>
          </section>

          <!-- Logistics Panel -->
          <section v-if="tournament.type === 'groups' && ['draft', 'pending'].includes(tournament.status)" class="side-panel-premium">
            <h3 class="panel-title"><i class="pi pi-sitemap"></i> Configuración de Grupos</h3>
            <div class="p-6">
              <div class="flex flex-column gap-6">
                <div class="flex align-items-center justify-content-between">
                  <label class="text-[10px] font-black uppercase text-slate-500">Número de Grupos</label>
                  <div class="stepper-control">
                    <button @click="groupsCount = Math.max(2, groupsCount - 1)" class="stepper-action"><i class="pi pi-minus"></i></button>
                    <span class="stepper-value">{{ groupsCount }}</span>
                    <button @click="groupsCount++" class="stepper-action"><i class="pi pi-plus"></i></button>
                  </div>
                </div>
                <button @click="handleGenerateGroups" :disabled="processing || enrolledTeams.length < 2" class="mus-btn-secondary-large w-full">
                  <i class="pi pi-sync mr-2"></i> REPARTIR EN GRUPOS
                </button>
              </div>
            </div>
          </section>
        </div>

        <!-- MAIN COLUMN: Status & Control -->
        <div :class="['draft', 'pending'].includes(tournament.status) ? 'col-12 lg:col-7' : 'col-12'" class="space-y-8">
          <!-- Phase / Status Control (Only in Preparation) -->
          <section v-if="['draft', 'pending'].includes(tournament.status)" class="side-panel-premium">
            <h3 class="panel-title"><i class="pi pi-step-forward"></i> Fase del Torneo</h3>
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
                <h2 class="text-3xl font-black text-main italic uppercase tracking-tighter mb-4">Modo Preparación</h2>
                <p class="text-slate-500 font-bold text-xs uppercase tracking-widest max-w-md mx-auto mb-10 leading-relaxed">
                  {{ enrolledTeams.length < 2 ? 'Inscribe al menos a 2 parejas para poder realizar el sorteo.' : 'Todo listo. Pulsa el botón inferior para realizar el sorteo y comenzar el torneo oficialmente.' }}
                </p>
                
                <div class="flex flex-column items-center gap-6">
                  <div class="flex align-items-center gap-10 bg-black/40 p-6 rounded-2xl border border-white/5">
                    <div class="text-center">
                      <span class="block text-[9px] font-black text-slate-600 uppercase mb-1">Parejas</span>
                      <span class="text-2xl font-black italic text-white">{{ enrolledTeams.length }}</span>
                    </div>
                    <div class="h-8 w-px bg-white/10"></div>
                    <div class="text-center">
                      <span class="block text-[9px] font-black text-slate-600 uppercase mb-1">Sistema</span>
                      <span class="text-2xl font-black italic text-secondary">{{ t('tournament_form.types.' + tournament.type) }}</span>
                    </div>
                  </div>

                  <button @click="handleGenerateMatches" 
                          :disabled="processing || enrolledTeams.length < 2" 
                          class="mus-btn-primary px-12 py-5 text-lg">
                    <i class="pi pi-bolt mr-3"></i>
                    {{ tournament.type === 'eliminatory' ? 'SORTEO INICIAL' : t('tournament_mgmt.generate_matches') }}
                  </button>
                </div>
              </div>
            </section>

            <!-- ACTIVE MODE (Active/Finished) -->
            <section v-else class="admin-section">
              <div class="section-header-premium mb-8">
                <div class="flex align-items-center gap-4">
                  <div class="section-icon"><i class="pi pi-bolt"></i></div>
                  <h2 class="m-0 uppercase italic font-black text-2xl tracking-tight text-main">Control de Resultados</h2>
                </div>
              </div>

              <div v-if="matches.length === 0" class="empty-state-card p-12 text-center rounded-3xl border-dashed border-white/10 mb-10">
                <div class="w-20 h-20 bg-white/5 rounded-full flex items-center justify-center mx-auto mb-6 border border-white/10">
                  <i class="pi pi-calendar-times text-4xl text-slate-700"></i>
                </div>
                <h3 class="text-main font-black uppercase italic mb-2">No hay enfrentamientos</h3>
                <p class="text-slate-500 font-bold text-xs uppercase tracking-widest mb-8">Algo ha ido mal. Intenta generar el calendario de nuevo.</p>
              </div>

              <div v-else class="space-y-12">
                <div v-for="([stage, stageMatches]) in groupedMatches" :key="stage" class="stage-group">
                  <div class="stage-header flex align-items-center gap-4 mb-6">
                    <span class="stage-dot"></span>
                    <h4 class="m-0 text-secondary font-black italic uppercase tracking-[0.25em] text-sm">{{ stage }}</h4>
                    <div class="h-px flex-1 bg-gradient-to-r from-secondary/20 to-transparent"></div>
                  </div>
                  
                    <div class="grid">
                      <div v-for="match in stageMatches" :key="match.id" 
                           @click="openEditModal(match)"
                           class="col-12 md:col-6 lg:col-3">
                      <div class="match-card-premium group">
                      <div class="match-card-bg"></div>
                      <div class="relative z-10">
                        <div class="flex justify-content-between align-items-center mb-4">
                          <span class="match-status-tag" :class="{ 'is-finished': match.status === t('tournament_view.match_status.finished') }">
                            {{ match.status }}
                          </span>
                          <div class="match-edit-icon"><i class="pi pi-pencil"></i></div>
                        </div>
                        <div class="team-row">
                          <span class="team-name" :class="{ 'is-winner': match.scoreA > match.scoreB }">{{ match.teamA || 'POR DECIDIR' }}</span>
                          <span class="team-score" :class="{ 'is-winner': match.scoreA > match.scoreB }">{{ match.scoreA }}</span>
                        </div>
                        <div class="team-row mt-2">
                          <span class="team-name" :class="{ 'is-winner': match.scoreB > match.scoreA }">{{ match.teamB || 'POR DECIDIR' }}</span>
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

    <!-- Score Edit Dialog (Redesigned) -->
    <Dialog v-model:visible="editingMatch" modal :header="'Introducir Resultado'" :style="{ width: '500px' }" class="mus-dialog-premium">
      <div v-if="editingMatch" class="p-6">
        <div class="flex flex-column gap-8">
          <!-- Team A Scorecard -->
          <div class="scorecard-premium">
            <div class="scorecard-header">
              <span class="role-label">LOCAL</span>
              <h3 class="team-title">{{ editingMatch.teamA }}</h3>
            </div>
            <div class="score-controls">
              <button @click="adjustScore(1, -1)" class="score-step-btn" :disabled="editScore1 <= 0">
                <i class="pi pi-minus"></i>
              </button>
              <div class="score-input-container">
                <input v-model.number="editScore1" type="number" readonly class="score-input-field">
              </div>
              <button @click="adjustScore(1, 1)" class="score-step-btn" :disabled="editScore1 >= (tournament.ruleGames || 40)">
                <i class="pi pi-plus"></i>
              </button>
            </div>
          </div>

          <div class="vs-divider">
            <div class="line"></div>
            <div class="vs-badge">VS</div>
            <div class="line"></div>
          </div>

          <!-- Team B Scorecard -->
          <div class="scorecard-premium">
            <div class="scorecard-header">
              <span class="role-label">VISITANTE</span>
              <h3 class="team-title">{{ editingMatch.teamB }}</h3>
            </div>
            <div class="score-controls">
              <button @click="adjustScore(2, -1)" class="score-step-btn" :disabled="editScore2 <= 0">
                <i class="pi pi-minus"></i>
              </button>
              <div class="score-input-container">
                <input v-model.number="editScore2" type="number" readonly class="score-input-field">
              </div>
              <button @click="adjustScore(2, 1)" class="score-step-btn" :disabled="editScore2 >= (tournament.ruleGames || 40)">
                <i class="pi pi-plus"></i>
              </button>
            </div>
          </div>
        </div>

        <div class="rules-hint mt-8">
          <i class="pi pi-info-circle"></i>
          <span>REGLAMENTO: {{ tournament.ruleGames }} juegos a {{ tournament.rulePoints }} tantos</span>
        </div>

        <div class="dialog-actions mt-10 grid grid-cols-2 gap-4">
          <button @click="editingMatch = null" class="mus-btn-secondary px-6 py-4">CANCELAR</button>
          <button @click="saveMatchResult" :disabled="isSavingResult" class="mus-btn-primary px-6 py-4">
            <i v-if="isSavingResult" class="pi pi-spin pi-spinner mr-2"></i>
            {{ isSavingResult ? 'GUARDANDO...' : 'GUARDAR RESULTADO' }}
          </button>
        </div>
      </div>
    </Dialog>

    <!-- Enrollment Dialog -->
    <Dialog v-model:visible="showEnrollmentDialog" modal :header="'Inscribir Pareja'" :style="{ width: '450px' }" class="mus-dialog-premium">
      <div class="p-4">
        <TournamentEnrollmentForm :tournamentUuid="uuid" @success="onEnrollmentSuccess" @cancel="showEnrollmentDialog = false" />
      </div>
    </Dialog>
  </div>
</template>

<style scoped>
.admin-page { max-width: 1500px; margin: 0 auto; padding: 40px 60px 100px 60px; }

/* Header & Back Button */
.back-btn { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); width: 40px; height: 40px; border-radius: 12px; color: #64748b; cursor: pointer; transition: 0.3s; display: flex; align-items: center; justify-content: center; }
.back-btn:hover { background: rgba(255,255,255,0.08); color: white; border-color: rgba(255,255,255,0.2); transform: translateX(-3px); }

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
  padding: 18px;
  font-weight: 950; 
  text-transform: uppercase; 
  letter-spacing: 0.1em; 
  border-radius: 18px; 
  cursor: pointer; 
  transition: all 0.3s; 
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
}
.mus-btn-secondary-large:hover:not(:disabled) { 
  background: rgba(255, 255, 255, 0.1); 
  border-color: rgba(255, 255, 255, 0.2); 
}

/* Premium Cards */
.mus-card-premium { background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 32px; }

/* Stat Cards */
.stat-card-premium { background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05); border-radius: 24px; padding: 24px; display: flex; align-items: center; gap: 20px; position: relative; overflow: hidden; }
.stat-icon { width: 56px; height: 56px; border-radius: 16px; border: 1px solid transparent; display: flex; align-items: center; justify-content: center; font-size: 20px; }
.stat-label { font-size: 9px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.2em; color: #64748b; display: block; margin-bottom: 4px; }
.stat-value { font-size: 32px; font-weight: 950; color: white; font-style: italic; letter-spacing: -1px; }
.stat-glow { position: absolute; bottom: -20px; right: -20px; width: 60px; height: 60px; filter: blur(40px); opacity: 0.1; }

/* Match Cards */
.match-card-premium { background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05); border-radius: 20px; padding: 24px; cursor: pointer; position: relative; transition: 0.4s cubic-bezier(0.4, 0, 0.2, 1); overflow: hidden; }
.match-card-premium:hover { transform: translateY(-5px) scale(1.02); border-color: rgba(233, 195, 73, 0.3); background: rgba(255,255,255,0.04); }
.match-card-bg { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: linear-gradient(135deg, rgba(233, 195, 73, 0.05) 0%, transparent 100%); opacity: 0; transition: 0.4s; }
.match-card-premium:hover .match-card-bg { opacity: 1; }

.match-status-tag { font-size: 8px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.15em; color: #64748b; background: rgba(255,255,255,0.05); padding: 4px 10px; border-radius: 6px; }
.match-status-tag.is-finished { color: #10b981; background: rgba(16, 185, 129, 0.1); }
.match-edit-icon { color: var(--secondary); font-size: 12px; opacity: 0.3; transition: 0.3s; }
.match-card-premium:hover .match-edit-icon { opacity: 1; transform: scale(1.2); }

.team-row { display: flex; justify-content: space-between; align-items: center; }
.team-name { color: #94a3b8; font-weight: 700; font-size: 14px; max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.team-name.is-winner { color: white; font-weight: 950; font-style: italic; }
.team-score { font-size: 20px; font-weight: 950; color: #334155; font-style: italic; }
.team-score.is-winner { color: var(--secondary); text-shadow: 0 0 15px rgba(233, 195, 73, 0.3); }

/* Side Panels */
.side-panel-premium { background: rgba(15, 23, 42, 0.4); border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 24px; overflow: hidden; }
.panel-title { background: rgba(255,255,255,0.02); padding: 18px 24px; font-size: 11px; font-weight: 950; text-transform: uppercase; letter-spacing: 0.2em; color: var(--secondary); border-bottom: 1px solid rgba(255,255,255,0.05); margin: 0; display: flex; align-items: center; gap: 12px; }

/* Custom Select */
.custom-select-wrapper { position: relative; width: 100%; }
.mus-select { width: 100%; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 14px; padding: 14px 20px; color: white; font-size: 12px; font-weight: 600; outline: none; appearance: none; transition: 0.3s; cursor: pointer; }
.mus-select:focus { border-color: var(--secondary); background: rgba(255,255,255,0.05); }
.select-arrow { position: absolute; right: 18px; top: 50%; transform: translateY(-50%); color: #64748b; font-size: 10px; pointer-events: none; }

/* Stepper Control */
.stepper-control { background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 14px; display: flex; align-items: center; gap: 20px; padding: 4px; }
.stepper-action { background: transparent; border: none; width: 32px; height: 32px; border-radius: 10px; color: var(--secondary); cursor: pointer; transition: 0.2s; }
.stepper-action:hover { background: rgba(233, 195, 73, 0.15); }
.stepper-value { font-weight: 950; font-size: 14px; color: white; min-width: 20px; text-align: center; }

/* Team List */
.teams-list-scroll { max-height: 380px; overflow-y: auto; padding-right: 8px; }
.team-list-item { display: flex; justify-content: space-between; align-items: center; padding: 12px 16px; background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05); border-radius: 14px; margin-bottom: 8px; transition: 0.3s; }
.team-list-item:hover { background: rgba(255,255,255,0.04); border-color: rgba(255,255,255,0.1); }
.team-info { display: flex; flex-direction: column; }
.team-info .name { font-size: 12px; font-weight: 700; color: white; }
.team-info .group-tag { font-size: 8px; font-weight: 900; text-transform: uppercase; color: #475569; margin-top: 2px; }
.remove-btn { background: transparent; border: none; color: #334155; width: 28px; height: 28px; border-radius: 8px; cursor: pointer; transition: 0.3s; }
.remove-btn:hover { background: rgba(244, 63, 94, 0.1); color: #f43f5e; }

/* Scorecards */
.scorecard-premium { background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.05); border-radius: 28px; padding: 32px; text-align: center; }
.role-label { font-size: 9px; font-weight: 950; text-transform: uppercase; letter-spacing: 0.3em; color: var(--secondary); opacity: 0.5; margin-bottom: 12px; display: block; }
.team-title { font-size: 24px; font-weight: 950; font-style: italic; color: white; margin: 0 0 28px 0; letter-spacing: -0.5px; }
.score-controls { display: flex; align-items: center; justify-content: center; gap: 24px; }
.score-step-btn { width: 56px; height: 56px; border-radius: 18px; background: rgba(233, 195, 73, 0.1); border: 1px solid rgba(233, 195, 73, 0.2); color: var(--secondary); cursor: pointer; transition: 0.3s; }
.score-step-btn:hover:not(:disabled) { background: var(--secondary); color: black; transform: translateY(-2px); }
.score-input-container { background: black; border: 1px solid rgba(255,255,255,0.1); border-radius: 20px; width: 100px; height: 80px; display: flex; align-items: center; justify-content: center; box-shadow: inset 0 10px 30px rgba(0,0,0,0.8); }
.score-input-field { background: transparent; border: none; color: white; font-size: 42px; font-weight: 950; font-style: italic; text-align: center; width: 100%; outline: none; }

.vs-divider { display: flex; align-items: center; gap: 15px; }
.vs-divider .line { flex: 1; height: 1px; background: rgba(255,255,255,0.05); }
.vs-badge { font-size: 10px; font-weight: 950; color: #334155; letter-spacing: 0.4em; }

.rules-hint { display: flex; align-items: center; justify-content: center; gap: 10px; color: #475569; font-size: 10px; font-weight: 900; text-transform: uppercase; background: rgba(0,0,0,0.2); padding: 12px; border-radius: 12px; }

/* Sticky sidebar */
.sticky-sidebar { position: sticky; top: 40px; }

.generate-matches-btn { background: rgba(233, 195, 73, 0.1); border: 1px solid rgba(233, 195, 73, 0.3); color: var(--secondary); padding: 8px 16px; border-radius: 12px; font-size: 9px; font-weight: 950; cursor: pointer; transition: 0.3s; }
.generate-matches-btn:hover:not(:disabled) { background: var(--secondary); color: black; }

/* Status Toggle Buttons */
.status-toggle-btn { background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05); border-radius: 16px; padding: 16px; color: #64748b; cursor: pointer; transition: 0.3s; display: flex; flex-direction: column; align-items: center; justify-content: center; position: relative; overflow: hidden; }
.status-toggle-btn i { font-size: 18px; }
.status-toggle-btn span { font-size: 9px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.1em; }
.status-toggle-btn:hover:not(:disabled) { background: rgba(255,255,255,0.05); color: white; border-color: rgba(255,255,255,0.1); }
.status-toggle-btn.active { background: rgba(233, 195, 73, 0.05); border-color: rgba(233, 195, 73, 0.2); color: white; }
.status-toggle-btn.active .active-indicator { position: absolute; bottom: 0; left: 0; width: 100%; height: 3px; opacity: 1; }
.active-indicator { position: absolute; bottom: 0; left: 0; width: 100%; height: 3px; opacity: 0; transition: 0.3s; }

/* Transitions */
.animate-spin { animation: spin 1s linear infinite; }
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }

/* Scrollbar */
.custom-scrollbar::-webkit-scrollbar { width: 5px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.2); }

@media (max-width: 1024px) {
  .admin-page { padding: 30px 20px; }
  .sticky-sidebar { position: relative; top: 0; }
}
</style>
