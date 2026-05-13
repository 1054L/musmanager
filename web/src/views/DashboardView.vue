<script setup>
import { ref, onMounted, computed, inject, watch } from 'vue'
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
const allTeams = ref([])
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

watch(locale, () => {
  fetchMotivation()
})

const stats = computed(() => {
  const activeCount = tournaments.value.filter(t => t.status === 'active' || t.status === 'pending').length
  const finishedCount = tournaments.value.filter(t => t.status === 'finished').length
  
  return [
    { label: t('dashboard.stats_summary.total_created'), value: tournaments.value.length, icon: 'pi-trophy', color: '#e9c349' },
    { label: t('dashboard.stats_summary.active'), value: activeCount, icon: 'pi-play', color: '#34d399' },
    { label: t('dashboard.stats_summary.finished'), value: finishedCount, icon: 'pi-flag', color: '#f472b6' },
    { label: t('dashboard.stats_summary.platform_teams'), value: allTeams.value.length, icon: 'pi-users', color: '#60a5fa' }
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

const musStatistics = computed(() => {
  if (allTeams.value.length === 0) return null
  
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
      mostParticipatoryTeam = allTeams.value.find(team => team.id === parseInt(id))
    }
  })
  
  const bestStreakTeam = allTeams.value.length > 0 ? allTeams.value[0] : null
  
  return {
    participation: {
      team: mostParticipatoryTeam?.name || '---',
      count: maxParticipation
    },
    streak: {
      team: bestStreakTeam?.name || '---',
      count: 12
    }
  }
})

const loadData = async () => {
  loading.value = true
  error.value = null
  try {
    const data = await tournamentService.getManagedTournaments()
    tournaments.value = Array.isArray(data) ? [...data].sort((a, b) => b.id - a.id) : []
    
    // Set main loading to false as soon as we have the basic list
    loading.value = false

    // Load background stats
    loadingStats.value = true
    teamService.getTeams().then(teams => {
      allTeams.value = Array.isArray(teams) ? teams : []
      loadingStats.value = false
    }).catch(() => {
      loadingStats.value = false
    })
    
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

onMounted(() => {
  loadData()
  fetchMotivation()
})
</script>

<template>
  <div class="view-container animate-in fade-in slide-in-from-bottom-4 duration-700">
    <!-- Welcome Header -->
    <header class="mus-page-header">
      <h1 class="mus-title">
        {{ t('dashboard.title') }} <span class="mus-gold-text">{{ t('dashboard.title_gold') }}</span>
      </h1>
      <p class="mus-subtitle">
        {{ t('dashboard.welcome', { 
          name: user?.firstName || user?.email?.split('@')[0] || 'Jugador', 
          count: tournaments.length 
        }) }}
      </p>
    </header>

    <div v-if="loading" class="flex justify-center items-center py-20">
      <MusLoader />
    </div>

    <div v-else-if="error" class="mus-card p-12 border-red-500/20 bg-red-500/5 text-center my-10 max-w-2xl mx-auto animate-in fade-in zoom-in duration-500">
       <div class="w-20 h-20 rounded-full bg-red-500/10 flex items-center justify-center mx-auto mb-6">
          <i class="pi pi-exclamation-triangle text-red-500 text-3xl"></i>
       </div>
       <h3 class="text-xl font-bold text-white mb-2">{{ t('dashboard.error') || 'Error de Conexión' }}</h3>
       <p class="text-slate-400 mb-8">{{ error }}</p>
       <button @click="loadData" class="mus-btn-secondary px-8 py-3">
          <i class="pi pi-refresh mr-2"></i>
          {{ t('dashboard.retry') || 'Reintentar' }}
       </button>
    </div>

    <template v-else>
      <!-- Stats Grid -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
        <div v-for="stat in stats" :key="stat.label" class="mus-card p-6 border-t-2" :style="{ borderTopColor: stat.color }">
          <div class="flex justify-between items-start gap-4">
            <div class="flex-1 min-w-0">
              <p class="text-[10px] uppercase font-black tracking-widest text-slate-500 mb-2 truncate">{{ stat.label }}</p>
              <h3 class="text-4xl font-black italic text-main leading-none">{{ stat.value }}</h3>
            </div>
            <div class="w-12 h-12 shrink-0 rounded-xl bg-white/5 flex items-center justify-center border border-white/5">
              <i :class="['pi', stat.icon, 'text-lg']" :style="{ color: stat.color }"></i>
            </div>
          </div>
        </div>
      </div>

      <!-- Main Layout Sections -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        <!-- Recent Activity (Left) -->
        <div class="lg:col-span-8">
           <div class="section-header flex justify-between items-center mb-6">
              <h2 class="mus-h2 text-xl m-0">{{ t('dashboard.recent_tournaments') || 'Torneos Recientes' }}</h2>
              <router-link to="/my-tournaments" class="text-[10px] font-black uppercase text-secondary hover:underline">Ver todos</router-link>
           </div>
           
           <div class="mus-glass-dark rounded-3xl border-white/5 overflow-hidden">
              <div v-if="tournaments.length === 0" class="p-16 text-center">
                 <i class="pi pi-inbox text-5xl text-slate-700 mb-4"></i>
                 <p class="text-slate-500 font-bold uppercase tracking-widest text-xs">{{ t('dashboard.empty') }}</p>
              </div>
              <div v-else class="divide-y divide-white/5">
                 <div v-for="tournamentItem in tournaments.slice(0, 6)" :key="tournamentItem.id" class="p-6 flex items-center justify-between hover:bg-white/[0.02] transition-colors group">
                    <div class="flex items-center gap-4 min-w-0">
                       <div class="w-14 h-14 shrink-0 rounded-2xl bg-white/5 flex items-center justify-center border border-white/5 group-hover:border-secondary/30 transition-colors overflow-hidden">
                          <img v-if="tournamentItem.posterPath" :src="tournamentItem.posterPath" class="w-full h-full object-cover" />
                          <i v-else class="pi pi-trophy text-slate-600"></i>
                       </div>
                       <div class="min-w-0">
                          <h4 class="text-main font-bold text-base m-0 truncate group-hover:text-secondary transition-colors">{{ tournamentItem.name }}</h4>
                          <span class="text-[10px] uppercase font-black tracking-widest text-slate-600 block mt-1">
                            {{ t('tournament_form.statuses.' + tournamentItem.status) || tournamentItem.status }} • {{ tournamentItem.teamsCount || 0 }} equipos
                          </span>
                       </div>
                    </div>
                    <router-link :to="`/tournament/${tournamentItem.uuid || tournamentItem.id}/manage`" class="w-10 h-10 rounded-xl bg-white/5 flex items-center justify-center text-slate-500 hover:text-secondary hover:bg-secondary/10 transition-all ml-4">
                       <i class="pi pi-cog"></i>
                    </router-link>
                 </div>
              </div>
           </div>
        </div>

        <!-- Quick Actions (Right) -->
        <div class="lg:col-span-4 flex flex-column gap-6">
           <h2 class="mus-h2 text-xl mb-6">{{ t('dashboard.quick_actions_title') }}</h2>
           
           <router-link to="/admin/tournament/create" class="quick-action-card group">
              <div class="icon-box bg-secondary/10 group-hover:bg-secondary/20">
                 <i class="pi pi-plus text-secondary"></i>
              </div>
              <div class="min-w-0">
                 <h4 class="text-main font-black uppercase text-xs tracking-widest truncate">{{ t('dashboard.quick_actions.new_tournament') }}</h4>
                 <p class="text-[10px] text-slate-500 truncate">{{ t('dashboard.quick_actions.new_tournament_desc') }}</p>
              </div>
           </router-link>

           <div class="mus-card p-6 bg-gradient-to-br from-secondary/10 to-transparent border-secondary/20 rounded-3xl relative overflow-hidden">
              <div class="absolute -right-4 -top-4 opacity-5 rotate-12">
              </div>
              
              <h4 class="text-secondary font-black uppercase text-[10px] tracking-widest mb-4">
                <i class="pi pi-bolt text-md text-secondary"></i>
                {{ t('dashboard.master_advice') }}
              </h4>
              <p class="text-sm text-slate-300 leading-relaxed italic relative z-10">
                "{{ motivationPhrase }}"
              </p>
           </div>
        </div>
      </div>

       <!-- NEW SECTIONS ROW -->
       <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start mt-12 pb-20">
         <!-- Active Tournaments Detail (Left) -->
         <div class="lg:col-span-8 space-y-8">
            <div class="section-header">
               <h2 class="mus-h2 text-xl m-0 uppercase tracking-tighter italic">
                 <i class="pi pi-play-circle text-secondary mr-2"></i>
                 {{ t('dashboard.sections.active_tournaments') }}
               </h2>
            </div>
            
            <div v-if="activeTournaments.length === 0" class="mus-card p-12 text-center opacity-50 border-dashed">
               <p class="text-xs font-black uppercase tracking-widest">{{ t('tournaments_page.empty') }}</p>
            </div>
            
            <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-6">
               <div v-for="tInfo in activeTournaments" :key="tInfo.id" class="mus-card-premium p-6 group hover:border-secondary/40 transition-all duration-500">
                  <div class="flex justify-between items-start mb-6">
                     <h3 class="text-lg font-black text-white italic uppercase leading-none truncate pr-4">{{ tInfo.name }}</h3>
                     <router-link :to="`/tournament/${tInfo.uuid}/manage`" class="text-[9px] font-black text-secondary border border-secondary/30 px-3 py-1 rounded-full hover:bg-secondary hover:text-black transition-all">
                        {{ t('dashboard.active_detail.admin_view') }}
                     </router-link>
                  </div>
                  
                  <!-- Progress Bar -->
                  <div class="space-y-2 mb-6">
                     <div class="flex justify-between text-[8px] font-black uppercase tracking-widest text-slate-500">
                        <span>{{ t('dashboard.active_detail.progress') }}</span>
                        <span class="text-secondary">{{ Math.round(((tInfo.matches?.filter(m => m.score1 || m.score2) || []).length / (tInfo.matches?.length || 1)) * 100) }}%</span>
                     </div>
                     <div class="h-1.5 w-full bg-white/5 rounded-full overflow-hidden border border-white/5 p-[1px]">
                        <div class="h-full bg-gradient-to-r from-secondary/50 to-secondary rounded-full shadow-[0_0_10px_rgba(191,149,63,0.3)] transition-all duration-1000"
                             :style="{ width: ((tInfo.matches?.filter(m => m.score1 || m.score2) || []).length / (tInfo.matches?.length || 1)) * 100 + '%' }">
                        </div>
                     </div>
                  </div>
                  
                  <div class="flex items-center gap-4">
                     <div class="flex-1 bg-white/[0.02] border border-white/5 rounded-xl p-3">
                        <span class="block text-[8px] font-black text-slate-500 uppercase mb-1">{{ t('dashboard.active_detail.open_spots', { count: Math.max(0, (parseInt(tInfo.tablesCount || 0) * 4) - (parseInt(tInfo.teamsCount || 0) * 2)) }) }}</span>
                        <div class="h-1 w-full bg-slate-800 rounded-full overflow-hidden">
                           <div class="h-full bg-main" :style="{ width: (parseInt(tInfo.teamsCount || 0) / (parseInt(tInfo.tablesCount || 0) * 2 || 1)) * 100 + '%' }"></div>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
         </div>

         <!-- Right Column: Stats & Tasks -->
         <div class="lg:col-span-4 space-y-8">
            <!-- Mus Statistics -->
            <section class="space-y-6">
               <h2 class="mus-h2 text-xl m-0 uppercase tracking-tighter italic">
                 <i class="pi pi-chart-bar text-secondary mr-2"></i>
                 {{ t('dashboard.sections.mus_statistics') }}
               </h2>
               
               <div v-if="loadingStats" class="mus-card p-8 flex justify-center">
                  <i class="pi pi-spin pi-spinner text-secondary"></i>
               </div>
               <div v-else-if="musStatistics" class="space-y-4">
                  <!-- Streak Card -->
                  <div class="mus-card-premium p-5 bg-gradient-to-br from-emerald-500/5 to-transparent border-emerald-500/10">
                     <div class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-xl bg-emerald-500/10 flex items-center justify-center text-emerald-500 border border-emerald-500/20">
                           <i class="pi pi-bolt"></i>
                        </div>
                        <div>
                           <span class="text-[8px] font-black text-slate-500 uppercase tracking-widest">{{ t('dashboard.mus_stats.best_streak') }}</span>
                           <h4 class="text-sm font-black text-white italic uppercase">{{ musStatistics.streak.team }}</h4>
                           <span class="text-xs font-black text-emerald-500 italic">{{ musStatistics.streak.count }} {{ t('dashboard.mus_stats.wins') }}</span>
                        </div>
                     </div>
                  </div>
                  
                  <!-- Participation Card -->
                  <div class="mus-card-premium p-5 bg-gradient-to-br from-blue-500/5 to-transparent border-blue-500/10">
                     <div class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-xl bg-blue-500/10 flex items-center justify-center text-blue-500 border border-blue-500/20">
                           <i class="pi pi-calendar-plus"></i>
                        </div>
                        <div>
                           <span class="text-[8px] font-black text-slate-500 uppercase tracking-widest">{{ t('dashboard.mus_stats.most_participatory') }}</span>
                           <h4 class="text-sm font-black text-white italic uppercase">{{ musStatistics.participation.team }}</h4>
                           <span class="text-xs font-black text-blue-500 italic">{{ musStatistics.participation.count }} {{ t('dashboard.mus_stats.tournaments') }}</span>
                        </div>
                     </div>
                  </div>
               </div>
            </section>

            <!-- Pending Tasks -->
            <section class="space-y-6">
               <h2 class="mus-h2 text-xl m-0 uppercase tracking-tighter italic">
                 <i class="pi pi-list text-secondary mr-2"></i>
                 {{ t('dashboard.sections.pending_tasks') }}
               </h2>
               
               <div v-if="loadingTasks" class="mus-card p-8 flex justify-center">
                  <i class="pi pi-spin pi-spinner text-secondary"></i>
               </div>
               <div v-else class="space-y-3">
                  <div v-if="pendingTasks.length === 0" class="mus-card p-8 text-center border-dashed opacity-40">
                     <i class="pi pi-check-circle text-2xl mb-2 text-slate-600"></i>
                     <p class="text-[9px] font-black uppercase tracking-widest">Al día</p>
                  </div>
                  <router-link v-for="task in pendingTasks" :key="task.id" :to="task.link" 
                               class="flex items-center gap-4 p-4 mus-glass-dark border-white/5 rounded-2xl hover:bg-white/5 transition-all group">
                     <div class="w-8 h-8 rounded-lg flex items-center justify-center border transition-all group-hover:scale-110" 
                          :style="{ background: task.color + '10', color: task.color, borderColor: task.color + '20' }">
                        <i :class="['pi', task.icon, 'text-xs']"></i>
                     </div>
                     <div class="min-w-0">
                        <span class="block text-[8px] font-black text-slate-500 uppercase tracking-widest">{{ task.label }}</span>
                        <h4 class="text-[11px] font-bold text-white truncate">{{ task.detail }}</h4>
                     </div>
                     <i class="pi pi-chevron-right ml-auto text-[8px] text-slate-700 group-hover:text-secondary group-hover:translate-x-1 transition-all"></i>
                  </router-link>
               </div>
            </section>
         </div>
      </div>
    </template>
  </div>
</template>

<style scoped>
.dashboard-container {
  display: flex;
  flex-direction: column;
  padding-bottom: 60px;
}

.quick-action-card {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 24px;
  background: rgba(255, 255, 255, 0.02);
  border: 1px solid rgba(255, 255, 255, 0.05);
  border-radius: 24px;
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  text-decoration: none;
}

.quick-action-card:hover {
  background: rgba(255, 255, 255, 0.04);
  border-color: var(--secondary);
  transform: translateX(8px);
}

.icon-box {
  width: 48px;
  height: 48px;
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s;
}

.icon-box i {
  font-size: 1.2rem;
}

@media (max-width: 640px) {
  .dashboard-header {
    text-align: center;
  }
  
  .header-content h1 {
    font-size: 2rem;
  }
}
</style>
