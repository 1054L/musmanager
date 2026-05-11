<script setup>
import { ref, onMounted, computed, inject } from 'vue'
import { useI18n } from 'vue-i18n'
import { tournamentService } from '../services/api'
import MusLoader from '../components/MusLoader.vue'

const { t } = useI18n()
const user = inject('user')
const loading = ref(true)
const error = ref(null)
const tournaments = ref([])

const stats = computed(() => {
  const active = tournaments.value.filter(t => t.status === 'active' || t.status === 'pending').length
  const totalTeams = tournaments.value.reduce((acc, curr) => acc + (curr.teamsCount || 0), 0)
  const totalMatches = tournaments.value.reduce((acc, curr) => acc + (curr.matchesCount || 0), 0)
  
  return [
    { label: t('dashboard.stats.total_tournaments') || 'Torneos Totales', value: tournaments.value.length, icon: 'pi-trophy', color: '#e9c349' },
    { label: t('dashboard.stats.active_tournaments') || 'Torneos Activos', value: active, icon: 'pi-play', color: '#34d399' },
    { label: t('dashboard.stats.total_teams') || 'Parejas Totales', value: totalTeams, icon: 'pi-users', color: '#60a5fa' },
    { label: t('dashboard.stats.total_matches') || 'Partidas Jugadas', value: totalMatches, icon: 'pi-check-circle', color: '#f472b6' }
  ]
})

const loadData = async () => {
  loading.value = true
  error.value = null
  try {
    const data = await tournamentService.getManagedTournaments()
    // Sort by ID descending to show newest first
    tournaments.value = Array.isArray(data) ? [...data].sort((a, b) => b.id - a.id) : []
  } catch (err) {
    console.error('Error loading dashboard data:', err)
    error.value = err.message || t('dashboard.error')
  } finally {
    loading.value = false
  }
}

onMounted(loadData)
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
              <i :class="['pi', stat.icon, 'text-xl']" :style="{ color: stat.color }"></i>
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
                 <div v-for="t in tournaments.slice(0, 6)" :key="t.id" class="p-6 flex items-center justify-between hover:bg-white/[0.02] transition-colors group">
                    <div class="flex items-center gap-4 min-w-0">
                       <div class="w-14 h-14 shrink-0 rounded-2xl bg-white/5 flex items-center justify-center border border-white/5 group-hover:border-secondary/30 transition-colors overflow-hidden">
                          <img v-if="t.posterPath" :src="t.posterPath" class="w-full h-full object-cover" />
                          <i v-else class="pi pi-trophy text-slate-600"></i>
                       </div>
                       <div class="min-w-0">
                          <h4 class="text-main font-bold text-base m-0 truncate group-hover:text-secondary transition-colors">{{ t.name }}</h4>
                          <span class="text-[10px] uppercase font-black tracking-widest text-slate-600 block mt-1">
                            {{ t('tournament_form.statuses.' + t.status) || t.status }} • {{ t.teamsCount || 0 }} equipos
                          </span>
                       </div>
                    </div>
                    <router-link :to="`/admin/tournament/${t.id}/edit`" class="w-10 h-10 rounded-xl bg-white/5 flex items-center justify-center text-slate-500 hover:text-secondary hover:bg-secondary/10 transition-all ml-4">
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
                <i class="pi pi-bolt text-7xl text-secondary"></i>
              </div>
              <h4 class="text-secondary font-black uppercase text-[10px] tracking-widest mb-4">CONSEJO MAESTRO</h4>
              <p class="text-sm text-slate-300 leading-relaxed italic relative z-10">
                "En el Mus, la seña es ley, pero la gestión debe ser ciencia. Mantén tus cuadros actualizados en vivo para una experiencia épica."
              </p>
           </div>
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
