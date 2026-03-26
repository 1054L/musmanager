<script setup>
import { onMounted } from 'vue'
import { useTournamentStore } from '../stores/tournamentStore'
import { storeToRefs } from 'pinia'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'

const { t, locale } = useI18n()
const router = useRouter()
const tournamentStore = useTournamentStore()

// Use storeToRefs to maintain reactivity
const { tournaments, loading, error } = storeToRefs(tournamentStore)

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
  tournamentStore.fetchPublicTournaments()
})
</script>

<template>
  <div class="tournaments-view">
    <header class="page-header">
      <h1 class="mus-h1 italic">
        {{ t('tournaments_page.title') }}
      </h1>
      <p class="mus-p opacity-60 mt-4">{{ t('tournaments_page.subtitle') }}</p>
    </header>

    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <p class="text-xs font-black uppercase tracking-widest text-slate-500 mt-6">{{ t('dashboard.loading') }}</p>
    </div>

    <div v-else-if="error" class="error-state mus-glass">
      <p class="text-rose-400 font-bold mb-6">{{ error }}</p>
      <button @click="tournamentStore.fetchPublicTournaments()" class="mus-button-primary scale-90">{{ t('dashboard.retry') }}</button>
    </div>

    <div v-else-if="tournaments.length === 0" class="empty-state">
      <div class="w-16 h-16 bg-slate-800 rounded-full flex items-center justify-center mx-auto mb-6 text-2xl">🎴</div>
      <p class="text-slate-500 font-bold uppercase tracking-widest text-xs">{{ t('tournaments_page.empty') }}</p>
    </div>

    <div v-else class="tournaments-grid">
      <div v-for="tny in tournaments" :key="tny.id" class="t-card mus-glass overflow-hidden">
        <div class="t-poster-wrapper">
          <img v-if="tny.posterPath" :src="tny.posterPath" :alt="tny.name" class="t-poster" />
          <div v-else class="t-poster-placeholder">🎴</div>
          <div class="t-poster-overlay"></div>
          <div class="t-badge" :class="tny.status">
            {{ tny.status === 'active' ? t('tournaments_page.active') : t('tournaments_page.pending') }}
          </div>
        </div>
        
        <div class="t-content">
          <h3 class="mus-h3 mb-4 line-clamp-1">{{ tny.name }}</h3>
          
          <div class="t-meta">
            <div class="meta-item">
              <i class="pi pi-users"></i>
              <span>{{ t('tournaments_page.pairs_count', { count: tny.teamsCount || 0 }) }}</span>
            </div>
            <div class="meta-item">
              <i class="pi pi-calendar"></i>
              <span>{{ formatDate(tny.startDate) }}</span>
            </div>
          </div>

          <button @click="router.push(`/tournament/${tny.uuid}`)" class="details-btn">
            {{ t('tournaments_page.view_details') }}
            <i class="pi pi-arrow-right"></i>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.tournaments-view {
  display: flex;
  flex-direction: column;
  gap: 80px;
  padding-bottom: 120px;
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
  grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
  gap: 40px;
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

.spinner {
  width: 40px;
  height: 40px;
  border: 3px solid rgba(15, 179, 97, 0.1);
  border-top-color: #0fb361;
  border-radius: 50%;
  animation: rotate 1s linear infinite;
  margin: 0 auto;
}

@keyframes rotate {
  to { transform: rotate(360deg); }
}

.line-clamp-1 {
  display: -webkit-box;
  -webkit-line-clamp: 1;
  line-clamp: 1;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
