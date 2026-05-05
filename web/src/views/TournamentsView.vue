<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useTournamentStore } from '../stores/tournamentStore'
import { storeToRefs } from 'pinia'
import { useI18n } from 'vue-i18n'
import MusLoader from '../components/MusLoader.vue'
import GoogleAd from '../components/GoogleAd.vue'
import { authService } from '../services/api'

const { t, locale } = useI18n()
const route = useRoute()
const router = useRouter()
const tournamentStore = useTournamentStore()

const user = authService.getUser()
const isSuperAdmin = user?.roles?.includes('ROLE_SUPER_ADMIN')

// Filter State
const filterStatus = ref(route.query.status || 'all')

// Sync with route query
watch(() => route.query.status, (newStatus) => {
  filterStatus.value = newStatus || 'all'
})

// Use storeToRefs to maintain reactivity
const { tournaments, loading, error } = storeToRefs(tournamentStore)

const dynamicTitle = computed(() => {
  return t(`tournaments_page.title_${filterStatus.value}`)
})

const filteredTournaments = computed(() => {
  return tournaments.value.filter(tny => {
    // CRITICAL: Always exclude private or draft tournaments from public view
    if (tny.private || tny.status === 'draft') return false
    
    if (filterStatus.value === 'all') return true
    
    // Safety matching (case-insensitive and trimmed)
    const current = (tny.status || '').toLowerCase().trim()
    const target = filterStatus.value.toLowerCase().trim()
    return current === target
  })
})

const formatDate = (dateString) => {
  if (!dateString) return t('tournaments_page.coming_soon')
  const date = new Date(dateString)
  return new Intl.DateTimeFormat(locale.value === 'en' ? 'en-US' : (locale.value === 'eu' ? 'eu-ES' : 'es-ES'), {
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  }).format(date)
}

onMounted(() => {
  tournamentStore.fetchPublicTournaments(true)
})
</script>

<template>
  <div class="tournaments-view">
    <header class="page-header">
      <h1 class="mus-h1 italic">
        {{ dynamicTitle }}
      </h1>
      <p class="mus-p opacity-60 mt-4">{{ t('tournaments_page.subtitle') }}</p>
    </header>
    
    <!-- Sub-menu Filter Bar -->
    <nav class="filter-bar-container">
      <div class="filter-bar">
        <button v-for="status in ['all', 'active', 'pending', 'finished']" 
                :key="status"
                @click="filterStatus = status"
                class="filter-btn"
                :class="{ 'active': filterStatus === status }">
          <span class="filter-dot" v-if="filterStatus === status"></span>
          {{ t(`tournaments_page.filters.${status}`) }}
        </button>
      </div>
    </nav>
    
    <div v-if="loading" class="loading-state">
      <MusLoader />
    </div>

    <div v-else-if="error" class="error-state mus-glass">
      <p class="text-rose-400 font-bold mb-6">{{ error }}</p>
      <button @click="tournamentStore.fetchPublicTournaments()" class="mus-button-primary scale-90">{{ t('dashboard.retry') }}</button>
    </div>

    <div v-else-if="filteredTournaments.length === 0" class="empty-state">
      <div class="w-16 h-16 bg-slate-800 rounded-full flex items-center justify-center mx-auto mb-6 overflow-hidden border border-white/5">
        <img src="/vertical.png" class="w-full h-full object-cover opacity-20" />
      </div>
      <p class="text-slate-500 font-black uppercase tracking-[0.2em] text-[10px]">{{ t('tournaments_page.empty') }}</p>
    </div>

    <div v-else class="tournaments-grid">
      <div v-for="tny in filteredTournaments" :key="tny.id" 
           class="t-card mus-glass overflow-hidden cursor-pointer"
           @click="router.push(`/tournament/${tny.uuid}`)">
        <div class="t-poster-wrapper">
          <img v-if="tny.posterPath" :src="tny.posterPath" :alt="tny.name" class="t-poster" />
          <div v-else class="t-poster-placeholder">
             <img src="/vertical.png" class="t-poster opacity-20" />
          </div>
          <div class="t-poster-overlay"></div>
          <div class="t-badge" :class="tny.status">
            {{ tny.status === 'active' ? t('tournaments_page.active') : (tny.status === 'pending' ? t('tournaments_page.pending') : t('tournament_form.statuses.finished')) }}
          </div>
        </div>
        
        <div class="t-content">
          <h3 class="mus-h3 mb-4 line-clamp-1 italic">{{ tny.name }}</h3>
          
          <div class="t-meta">
            <div class="meta-item">
              <i class="pi pi-users"></i>
              <span>{{ t('tournaments_page.pairs_count', { count: tny.teamsCount || 0 }) }}</span>
            </div>
            <div class="meta-item">
              <i class="pi pi-calendar"></i>
              <span>{{ formatDate(tny.startDate) }}</span>
            </div>
            <!-- Admin Only: Owner Info -->
            <div v-if="isSuperAdmin && tny.owner" class="admin-meta mt-4 p-3 rounded-xl bg-primary/5 border border-primary/20">
              <div class="flex items-center gap-2 text-[10px] font-black text-primary">
                <i class="pi pi-shield text-[10px]"></i>
                <span class="uppercase tracking-widest">Admin Info</span>
              </div>
              <div class="mt-1 text-[11px] text-slate-300 font-bold">
                <span class="text-slate-500">Owner:</span> {{ tny.owner.name }} 
                <span class="text-slate-600 bg-black/40 px-2 py-0.5 rounded ml-2">ID: {{ tny.owner.id }}</span>
              </div>
            </div>
          </div>

          <div class="details-btn">
            {{ t('tournaments_page.view_details') }}
            <i class="pi pi-arrow-right"></i>
          </div>
        </div>
      </div>
    </div>
    <div class="col-12 px-4" v-if="filteredTournaments.length > 0">
      <GoogleAd :key="'bottom-tournaments'" />
    </div>
  </div>
</template>

<style scoped>
.tournaments-view {
  display: flex;
  flex-direction: column;
  gap: 40px;
  padding-bottom: 120px;
}

.page-header {
  text-align: center;
  margin-bottom: 20px;
}

.filter-bar-container {
  display: flex;
  justify-content: center;
  margin-bottom: 20px;
}

.filter-bar {
  display: flex;
  background: rgba(255, 255, 255, 0.03);
  backdrop-filter: blur(20px);
  padding: 6px;
  border-radius: 99px;
  border: 1px solid rgba(255, 255, 255, 0.05);
  gap: 4px;
  max-width: 100%;
  overflow-x: auto;
  scrollbar-width: none; /* Firefox */
}

.filter-bar::-webkit-scrollbar {
  display: none; /* Safari and Chrome */
}

.filter-btn {
  background: transparent;
  border: none;
  color: #64748b;
  padding: 10px 16px;
  border-radius: 99px;
  font-size: 9px;
  font-weight: 900;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  cursor: pointer;
  transition: all 0.3s;
  display: flex;
  align-items: center;
  gap: 6px;
  white-space: nowrap;
}

@media (min-width: 768px) {
  .filter-btn {
    padding: 10px 24px;
    font-size: 10px;
    letter-spacing: 0.15em;
    gap: 8px;
  }
}

.filter-btn:hover {
  color: white;
  background: rgba(255, 255, 255, 0.05);
}

.filter-btn.active {
  background: var(--primary);
  color: #050505;
  box-shadow: 0 10px 20px var(--primary-glow);
}

.filter-dot {
  width: 4px;
  height: 4px;
  background: #050505;
  border-radius: 50%;
}

.page-header {
  text-align: center;
}

.loading-state, .empty-state {
  text-align: center;
  padding: 100px 0;
}

.tournaments-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 24px;
}

@media (min-width: 768px) {
  .tournaments-grid {
    grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
    gap: 40px;
  }
}

.t-card {
  border-radius: 32px;
  position: relative;
  transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
  display: flex;
  flex-direction: column;
  border: 1px solid rgba(255, 255, 255, 0.05);
}

.t-card:hover {
  transform: translateY(-12px);
  border-color: rgba(15, 179, 97, 0.4);
  box-shadow: 0 40px 80px rgba(0,0,0,0.5);
}

.t-poster-wrapper {
  height: 200px;
  position: relative;
  overflow: hidden;
}

.t-poster {
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: top;
  transition: transform 0.6s ease;
}

.t-card:hover .t-poster {
  transform: scale(1.1);
}

.t-poster-placeholder {
  width: 100%;
  height: 100%;
  background: linear-gradient(135deg, #050505 0%, #1a1a1a 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 3rem;
}

.t-poster-overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(to bottom, transparent 0%, rgba(5, 5, 5, 0.8) 100%);
}

.t-badge {
  position: absolute;
  top: 20px;
  right: 20px;
  font-size: 9px;
  font-weight: 900;
  text-transform: uppercase;
  letter-spacing: 0.2em;
  padding: 8px 16px;
  border-radius: 99px;
  z-index: 10;
  backdrop-filter: blur(8px);
}

.t-badge.active {
  background: rgba(15, 179, 97, 0.2);
  color: #0fb361;
  border: 1px solid rgba(15, 179, 97, 0.3);
}

.t-badge.pending {
  background: rgba(244, 209, 37, 0.2);
  color: #f4d125;
  border: 1px solid rgba(244, 209, 37, 0.3);
}

.t-content {
  padding: 32px;
  flex: 1;
  display: flex;
  flex-direction: column;
}

.t-meta {
  display: flex;
  flex-direction: column;
  gap: 16px;
  margin-bottom: 32px;
}

.meta-item {
  display: flex;
  align-items: center;
  gap: 12px;
  font-size: 11px;
  font-weight: 700;
  color: rgba(255, 255, 255, 0.5);
  text-transform: uppercase;
  letter-spacing: 0.1em;
}

.meta-item i {
  color: #0fb361;
  font-size: 14px;
}

.details-btn {
  margin-top: auto;
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.08);
  color: white;
  padding: 18px;
  border-radius: 20px;
  font-size: 10px;
  font-weight: 900;
  text-transform: uppercase;
  letter-spacing: 0.2em;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  cursor: pointer;
  transition: all 0.3s;
}

.details-btn:hover {
  background: #0fb361;
  color: #050505;
  border-color: #0fb361;
  box-shadow: 0 10px 20px rgba(15, 179, 97, 0.3);
}



.line-clamp-1 {
  display: -webkit-box;
  -webkit-line-clamp: 1;
  line-clamp: 1;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
