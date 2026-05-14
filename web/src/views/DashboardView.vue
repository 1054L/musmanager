<script setup>
import { ref, onMounted, computed, inject, watch, reactive } from 'vue'
import { useI18n } from 'vue-i18n'
import { tournamentService, teamService } from '../services/api'
import MusLoader from '../components/MusLoader.vue'

const { t, locale } = useI18n()
const user = inject('user')
const loading = ref(true)
const loadingStats = ref(false)
const loadingTasks = ref(false)
const error = ref(null)
const tournaments = ref([])
const detailedTournaments = ref([])
const motivationPhrase = ref('')

const fetchMotivation = async () => {
  try {
    const currentLocale = locale.value || 'es'
    const response = await fetch(`/motivation/${currentLocale}.json`)
    if (!response.ok) throw new Error('Motivation file not found')
    const phrases = await response.json()
    if (Array.isArray(phrases) && phrases.length > 0) {
      const randomIndex = Math.floor(Math.random() * phrases.length)
      motivationPhrase.value = phrases[randomIndex].text
    }
  } catch (err) {
    console.error('Error fetching motivation:', err)
    motivationPhrase.value = locale.value === 'es' 
      ? "En el Mus, la seña es ley, pero la gestión debe ser ciencia." 
      : (locale.value === 'eu' ? "Musean, keinua legea da, baina kudeaketak zientzia izan behar du." : "In Mus, the sign is law, but management must be science.")
  }
}

const loadData = async () => {
  loading.value = true
  error.value = null
  try {
    const data = await tournamentService.getManagedTournaments()
    let tournamentList = []
    if (Array.isArray(data)) {
      tournamentList = data
    } else if (data && Array.isArray(data.tournaments)) {
      tournamentList = data.tournaments
    } else if (data && Array.isArray(data.data)) {
      tournamentList = data.data
    }
    
    tournaments.value = [...tournamentList].sort((a, b) => {
      const idA = a.id || a.uuid || ''
      const idB = b.id || b.uuid || ''
      return idB > idA ? 1 : -1
    })
    
    // Set main loading to false as soon as we have the basic list
    loading.value = false

    // Load background stats
    loadingStats.value = false // We calculate them from tournaments.value now
    
    // Load background tasks
    loadingTasks.value = true
    const activeOrDraft = tournaments.value
      .filter(t => ['active', 'draft', 'pending'].includes(t.status))
      .slice(0, 8)
    
    if (activeOrDraft.length > 0) {
      const detailPromises = activeOrDraft.map(t => 
        tournamentService.getTournament(t.uuid).catch(() => null)
      )
      
      const results = await Promise.all(detailPromises)
      detailedTournaments.value = results.filter(r => r !== null)
    }
    loadingTasks.value = false
    
  } catch (err) {
    console.error('Error loading dashboard data:', err)
    error.value = err.message || t('dashboard.error')
    loading.value = false
  }
}

watch(() => user?.value, (newUser) => {
  if (newUser) loadData()
}, { immediate: true })

watch(locale, () => {
  fetchMotivation()
})

const stats = computed(() => {
  const totalCount = tournaments.value.length
  const activeCount = tournaments.value.filter(t => t.status === 'active' || t.status === 'pending').length
  const finishedCount = tournaments.value.filter(t => t.status === 'finished').length
  
  // Calculate total teams across all user's tournaments
  const totalTeams = tournaments.value.reduce((sum, t) => sum + (t.teamsCount || 0), 0)
  
  return [
    { id: 'total', label: t('dashboard.stats_summary.total_created'), value: totalCount, icon: 'pi-trophy', color: '#e9c349' },
    { id: 'active', label: t('dashboard.stats_summary.active'), value: activeCount, icon: 'pi-play', color: '#34d399' },
    { id: 'finished', label: t('dashboard.stats_summary.finished'), value: finishedCount, icon: 'pi-flag', color: '#f472b6' },
    { id: 'teams', label: t('dashboard.stats_summary.platform_teams'), value: totalTeams, icon: 'pi-users', color: '#60a5fa' }
  ]
})

const activeTournaments = computed(() => {
  return detailedTournaments.value.filter(t => t.status === 'active')
})

const pendingTasks = computed(() => {
  const tasks = []
  
  // Draft tournaments
  tournaments.value.filter(t => t.status === 'draft').forEach(t => {
    tasks.push({
      id: `draft-${t.id}`,
      label: t('dashboard.tasks.draft'),
      detail: t.name,
      link: `/tournament/${t.uuid}/manage`,
      icon: 'pi-pencil',
      color: '#94a3b8'
    })
  })
  
  // Unconfirmed teams
  detailedTournaments.value.forEach(t => {
    const unconfirmed = (t.tournamentTeams || []).filter(tt => !tt.isConfirmed)
    if (unconfirmed.length > 0) {
      tasks.push({
        id: `unconfirmed-${t.id}`,
        label: t('dashboard.tasks.unconfirmed'),
        detail: `${t.name}: ${unconfirmed.length} parejas`,
        link: `/tournament/${t.uuid}/manage`,
        icon: 'pi-user-plus',
        color: '#60a5fa'
      })
    }
  })
  
  // Late matches (> 3 days)
  const threeDaysAgo = new Date()
  threeDaysAgo.setDate(threeDaysAgo.getDate() - 3)
  
  detailedTournaments.value.forEach(t => {
    if (t.status === 'active' && t.matches) {
      const lateMatches = t.matches.filter(m => {
        const isPending = !m.score1 && !m.score2
        const matchDate = m.createdAt ? new Date(m.createdAt) : new Date(t.startDate)
        return isPending && matchDate < threeDaysAgo
      })
      
      if (lateMatches.length > 0) {
        tasks.push({
          id: `late-${t.id}`,
          label: t('dashboard.tasks.late_matches'),
          detail: `${t.name}: ${lateMatches.length} partidas`,
          link: `/tournament/${t.uuid}/manage`,
          icon: 'pi-clock',
          color: '#f43f5e'
        })
      }
    }
  })
  
  return tasks
})

const recentTeams = computed(() => {
  const allTeams = []
  detailedTournaments.value.forEach(t => {
    (t.tournamentTeams || []).forEach(tt => {
      if (tt.team) {
        allTeams.push({
          ...tt,
          tournamentName: t.name,
          tournamentUuid: t.uuid
        })
      }
    })
  })
  
  // Sort by registration date descending (createdAt)
  return allTeams.sort((a, b) => {
    const dateA = a.createdAt ? new Date(a.createdAt) : new Date(0)
    const dateB = b.createdAt ? new Date(b.createdAt) : new Date(0)
    return dateB - dateA
  }).slice(0, 5)
})

const musStatistics = computed(() => {
  const teamsInUserTournaments = []
  detailedTournaments.value.forEach(t => {
    (t.tournamentTeams || []).forEach(tt => {
      if (tt.team && !teamsInUserTournaments.some(existing => existing.id === tt.team.id)) {
        teamsInUserTournaments.push(tt.team)
      }
    })
  })

  if (teamsInUserTournaments.length === 0) return null
  
  const participationMap = {}
  detailedTournaments.value.forEach(t => {
    (t.tournamentTeams || []).forEach(tt => {
      const teamId = tt.teamId || tt.team?.id
      if (teamId) {
        participationMap[teamId] = (participationMap[teamId] || 0) + 1
      }
    })
  })
  
  let mostParticipatoryTeam = null
  let maxParticipation = 0
  
  Object.entries(participationMap).forEach(([id, count]) => {
    if (count > maxParticipation) {
      maxParticipation = count
      mostParticipatoryTeam = teamsInUserTournaments.find(team => team.id === parseInt(id))
    }
  })
  
  // For streak, we would ideally fetch from a dedicated endpoint, 
  // but for now let's pick the first team or a placeholder if no data
  const bestStreakTeam = teamsInUserTournaments[0] || { name: '---' }
  
  return {
    participation: {
      team: mostParticipatoryTeam?.name || '---',
      count: maxParticipation
    },
    streak: {
      team: bestStreakTeam?.name || '---',
      count: 12 // This is still a placeholder value as per the original logic
    }
  }
})



const getStatusColor = (status) => {
  switch (status) {
    case 'draft': return '#94a3b8' // Grey
    case 'pending': return '#e9c349' // Gold
    case 'active': return '#22c55e' // Green
    case 'finished': return '#3b82f6' // Blue
    default: return '#94a3b8'
  }
}

onMounted(() => {
  // If user is already there, load data. Otherwise the watcher will handle it.
  if (user?.value) loadData()
  fetchMotivation()
})
</script>

<template>
  <div class="view-container animate-in fade-in slide-in-from-bottom-4 duration-1000">
    <!-- Header: KONTROL PANELA -->
    <header class="flex justify-between items-center mb-12 px-2">
      <div>
        <h1 class="text-3xl font-black uppercase tracking-tighter text-white m-0 italic">
          {{ t('dashboard.title') }} <span class="text-secondary">{{ t('dashboard.title_gold') }}</span>
        </h1>
        <p class="text-[10px] uppercase font-bold tracking-[0.3em] text-slate-500 mt-1">
          {{ t('dashboard.welcome', { 
            name: (user?.value?.firstName || user?.firstName) || (user?.value?.email?.split('@')?.[0] || user?.email?.split('@')?.[0]) || 'Jugador', 
            count: tournaments.length 
          }) }}
        </p>
      </div>
    </header>

    <div v-if="loading" class="flex justify-center items-center py-20">
      <MusLoader />
    </div>

    <div v-else-if="error" class="mus-card p-12 border-red-500/20 bg-red-500/5 text-center my-10 max-w-2xl mx-auto">
       <div class="w-20 h-20 rounded-full bg-red-500/10 flex items-center justify-center mx-auto mb-6">
          <i class="pi pi-exclamation-triangle text-red-500 text-3xl"></i>
       </div>
       <h3 class="text-xl font-bold text-white mb-2">{{ t('dashboard.error') }}</h3>
       <p class="text-slate-400 mb-8">{{ error }}</p>
       <button @click="loadData" class="mus-btn-secondary px-8 py-3">
          <i class="pi pi-refresh mr-2"></i>
          {{ t('dashboard.retry') }}
       </button>
    </div>

    <template v-else>
      <!-- Horizontal Stats Grid (4 cards) - Full Width -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-12 w-full">
        <div v-for="stat in stats" :key="stat.id" 
             class="mus-glass p-6 rounded-2xl flex flex-col justify-between min-h-[130px] relative group hover:scale-[1.02] transition-all duration-500"
             :style="{ borderLeftColor: stat.color, borderLeftWidth: '7px', borderLeftStyle: 'solid' }">
          
          <div class="flex justify-between items-start">
            <span class="text-[9px] font-black uppercase tracking-[0.2em] text-slate-500">{{ stat.label }}</span>
            <div class="w-8 h-8 rounded-full flex items-center justify-center border border-white/10 transition-all"
                 :style="{ color: stat.color, borderColor: stat.color + '20' }">
              <i :class="['pi', stat.icon, 'text-[10px]']"></i>
            </div>
          </div>
          
          <div class="mt-4">
            <span class="text-4xl lg:text-5xl font-black italic text-white tracking-tighter">
              {{ stat.value ?? 0 }}
            </span>
          </div>
        </div>
      </div>

      <!-- Master Advice Section (Large Card) -->
      <div class="mb-12">
        <div class="mus-glass p-12 rounded-[32px] border-white/5 relative overflow-hidden group">
          <div class="relative z-10 flex flex-col md:flex-row md:items-center gap-10">
            <!-- New Shield Position -->
            <div class="flex items-center justify-center opacity-20 group-hover:opacity-40 transition-opacity duration-700">
              <i class="pi pi-shield text-5xl md:text-6xl text-secondary"></i>
            </div>
            
            <div class="w-1.5 h-24 bg-secondary rounded-full shadow-[0_0_20px_rgba(233,195,73,0.4)]"></div>
            
            <div class="flex-1">
              <span class="text-[9px] font-black uppercase tracking-[0.5em] text-secondary mb-4 block">{{ t('dashboard.master_advice') }}</span>
              <h2 class="text-2xl md:text-3xl font-black italic text-white/90 leading-tight tracking-tight max-w-3xl">
                "{{ motivationPhrase }}"
              </h2>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent Content Grid (Tournaments & Teams) -->
      <div class="flex flex-col lg:flex-row gap-8 mb-12">
        <!-- Recent Tournaments Section -->
        <div class="lg:w-1/2">
          <div class="flex items-center justify-between mb-8 px-2">
            <div class="flex items-center gap-3">
              <div class="w-1 h-6 bg-secondary rounded-full"></div>
              <h2 class="text-2xl font-black italic uppercase tracking-tighter text-white m-0 whitespace-nowrap">
                {{ t('dashboard.recent_tournaments') }}
              </h2>
            </div>
            <router-link to="/my-tournaments" class="text-[10px] font-black uppercase tracking-widest text-secondary hover:underline flex items-center gap-2">
              {{ t('dashboard.view_all') }}
              <i class="pi pi-chevron-right text-[8px]"></i>
            </router-link>
          </div>

          <div class="mus-glass rounded-[32px] border-white/5 overflow-hidden">
            <div v-if="tournaments.length === 0" class="p-20 text-center">
               <i class="pi pi-inbox text-4xl text-slate-700 mb-4"></i>
               <p class="text-slate-500 font-black uppercase tracking-widest text-[10px]">{{ t('dashboard.empty') }}</p>
            </div>
            <div v-else class="divide-y divide-white/5">
              <router-link v-for="tournamentItem in tournaments.slice(0, 5)" :key="tournamentItem.id" 
                           :to="`/tournament/${tournamentItem.uuid || tournamentItem.id}/manage`"
                           class="p-6 flex items-center justify-between hover:bg-white/[0.03] transition-all group">
                <div class="flex items-center gap-6 overflow-hidden">
                  <div class="rounded-full flex-none flex items-center justify-center border transition-all"
                       :style="{ 
                         width: '48px', height: '48px', minWidth: '48px', minHeight: '48px',
                         borderColor: getStatusColor(tournamentItem.status) + '30',
                         backgroundColor: getStatusColor(tournamentItem.status) + '10'
                       }">
                    <i class="pi pi-trophy" :style="{ color: getStatusColor(tournamentItem.status) }"></i>
                  </div>
                  <div>
                    <h4 class="text-lg font-black text-white italic uppercase tracking-tight group-hover:text-secondary transition-colors">{{ tournamentItem.name }}</h4>
                    <div class="flex items-center gap-4 mt-1">
                      <span class="text-[9px] uppercase font-bold tracking-widest text-slate-500">
                        {{ t('tournament_form.statuses.' + tournamentItem.status) || tournamentItem.status }}
                      </span>
                      <div class="w-1 h-1 rounded-full bg-white/10"></div>
                      <span class="text-[9px] uppercase font-bold tracking-widest text-slate-500">
                        {{ t('tournament_view.pairs_count', { count: tournamentItem.teamsCount || 0 }) }}
                      </span>
                    </div>
                  </div>
                </div>
                <div class="hidden sm:flex flex-col gap-1 py-1">
                  <div class="flex items-center gap-2">
                    <i class="pi pi-crown text-secondary text-[10px]"></i>
                    <span class="text-[10px] font-black text-white/90 leading-none">{{ tournamentItem.ruleKings || 4 }}</span>
                  </div>
                  <div class="flex items-center gap-2">
                    <i class="pi pi-hashtag text-secondary text-[10px]"></i>
                    <span class="text-[10px] font-black text-white/90 leading-none">{{ tournamentItem.rulePoints || 40 }}</span>
                  </div>
                  <div class="flex items-center gap-2">
                    <i class="pi pi-bolt text-secondary text-[10px]"></i>
                    <span class="text-[10px] font-black text-white/90 leading-none">{{ tournamentItem.ruleGames || 3 }}</span>
                  </div>
                </div>
                <div class="flex sm:hidden">
                   <i class="pi pi-chevron-right text-slate-700"></i>
                </div>
              </router-link>
            </div>
          </div>
        </div>

        <!-- Recent Teams Section -->
        <div class="lg:w-1/2">
          <div class="flex items-center justify-between mb-8 px-2">
            <div class="flex items-center gap-3">
              <div class="w-1 h-6 bg-blue-500 rounded-full"></div>
              <h2 class="text-2xl font-black italic uppercase tracking-tighter text-white m-0 whitespace-nowrap">
                {{ t('dashboard.recent_teams') }}
              </h2>
            </div>
          </div>

          <div class="mus-glass rounded-[32px] border-white/5 overflow-hidden">
            <div v-if="recentTeams.length === 0" class="p-20 text-center">
               <i class="pi pi-users text-4xl text-slate-700 mb-4"></i>
               <p class="text-slate-500 font-black uppercase tracking-widest text-[10px]">Sin inscripciones recientes</p>
            </div>
            <div v-else class="divide-y divide-white/5">
              <div v-for="teamItem in recentTeams" :key="teamItem.id" 
                   class="p-6 flex items-center justify-between hover:bg-white/[0.03] transition-all group">
                <div class="flex items-center gap-6">
                  <div class="rounded-full flex-none flex items-center justify-center border border-blue-500/30 bg-blue-500/10"
                       style="width: 48px; height: 48px; min-width: 48px; min-height: 48px;">
                    <i class="pi pi-users text-blue-500"></i>
                  </div>
                  <div>
                    <h4 class="text-lg font-black text-white italic uppercase tracking-tight group-hover:text-blue-400 transition-colors">
                      {{ teamItem.team?.name || 'Pareja sin nombre' }}
                    </h4>
                    <div class="flex items-center gap-4 mt-1">
                      <span class="text-[9px] uppercase font-bold tracking-widest text-slate-500">
                        {{ teamItem.tournamentName }}
                      </span>
                      <div class="w-1 h-1 rounded-full bg-white/10"></div>
                      <span class="text-[9px] uppercase font-bold tracking-widest text-slate-500">
                        {{ teamItem.createdAt ? new Date(teamItem.createdAt).toLocaleDateString() : 'Reciente' }}
                      </span>
                    </div>
                  </div>
                </div>
                <div class="flex flex-col items-end gap-1">
                   <span class="text-[9px] font-black text-blue-500 uppercase tracking-widest">REGISTRADO</span>
                   <span class="text-[10px] font-bold text-slate-400 uppercase tracking-tighter">{{ teamItem.team?.players?.length || 0 }} JUGADORES</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Bottom Status Grid - Unified Full Width - Fixed Row -->
      <div class="flex flex-row items-stretch gap-6 mb-20 w-full">
        <div class="mus-glass p-6 rounded-2xl grid grid-cols-[48px_1fr] items-center gap-4 hover:bg-blue-500/5 transition-all group flex-1 min-w-0 h-full"
             style="border-left: 7px solid #3b82f6;">
          <div class="w-12 h-12 rounded-full bg-blue-500/10 flex items-center justify-center text-blue-500">
            <i class="pi pi-check-circle text-lg"></i>
          </div>
          <div class="min-w-0">
            <h5 class="text-[10px] font-black text-white uppercase tracking-widest leading-tight">{{ t('dashboard.sections.operational_card') }}</h5>
            <p class="text-[9px] font-bold text-slate-500 uppercase tracking-tighter mt-1 leading-relaxed">{{ t('dashboard.sections.operational_status_desc') }}</p>
          </div>
        </div>
        <div class="mus-glass p-6 rounded-2xl grid grid-cols-[48px_1fr] items-center gap-4 hover:bg-purple-500/5 transition-all group flex-1 min-w-0 h-full"
             style="border-left: 7px solid #a855f7;">
          <div class="w-12 h-12 rounded-full bg-purple-500/10 flex items-center justify-center text-purple-500">
            <i class="pi pi-chart-line text-lg"></i>
          </div>
          <div class="min-w-0">
            <h5 class="text-[10px] font-black text-white uppercase tracking-widest leading-tight">{{ t('dashboard.sections.general_card') }}</h5>
            <p class="text-[9px] font-bold text-slate-500 uppercase tracking-tighter mt-1 leading-relaxed">{{ t('dashboard.sections.general_status_desc') }}</p>
          </div>
        </div>
        <div class="mus-glass p-6 rounded-2xl grid grid-cols-[48px_1fr] items-center gap-4 hover:bg-orange-500/5 transition-all group flex-1 min-w-0 h-full"
             style="border-left: 7px solid #f97316;">
          <div class="w-12 h-12 rounded-full bg-orange-500/10 flex items-center justify-center text-orange-500">
            <i class="pi pi-bell text-lg"></i>
          </div>
          <div class="min-w-0">
            <h5 class="text-[10px] font-black text-white uppercase tracking-widest leading-tight">{{ t('dashboard.sections.center_card') }}</h5>
            <p class="text-[9px] font-bold text-slate-500 uppercase tracking-tighter mt-1 leading-relaxed">{{ t('dashboard.sections.center_status_desc') }}</p>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<style scoped>
.view-container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 2rem;
}

.mus-glass {
  backdrop-filter: blur(40px);
  -webkit-backdrop-filter: blur(40px);
}

.text-secondary {
  color: #e9c349; /* Gold accent */
}

.bg-secondary {
  background-color: #e9c349;
}

.border-secondary {
  border-color: #e9c349;
}

/* Typography Overrides for high fidelity */
h1, h2, h3, h4, h5 {
  font-family: 'Montserrat', sans-serif;
}

.font-mono {
  font-family: 'JetBrains Mono', monospace;
}

@keyframes scan {
  0% { transform: translateY(-100%); opacity: 0; }
  50% { opacity: 1; }
  100% { transform: translateY(400%); opacity: 0; }
}

.animate-scan {
  animation: scan 4s linear infinite;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .view-container {
    padding: 1rem;
  }
  
  .text-4xl {
    font-size: 2.5rem;
  }
}
</style>
