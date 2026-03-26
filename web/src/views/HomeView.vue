<script setup>
import { useI18n } from 'vue-i18n'
import { inject } from 'vue'

const { t } = useI18n()
const openAuthModal = inject('openAuthModal')

const features = [
  { icon: '🏆', title: 'features.management.title', desc: 'features.management.desc' },
  { icon: '👥', title: 'features.registration.title', desc: 'features.registration.desc' },
  { icon: '📊', title: 'features.results.title', desc: 'features.results.desc' }
]

const stats = [
  { value: '500+', label: 'stats.tournaments' },
  { value: '12K+', label: 'stats.players' },
  { value: '4.9/5', label: 'stats.rating' },
  { value: '24/7', label: 'stats.support' }
]
</script>

<template>
  <div class="mus-container">
    
    <!-- Hero Section -->
    <section class="mus-hero">
      <div class="mus-hero-content">
        <div class="mus-badge">
          <span class="mus-dot"></span>
          <span class="mus-badge-text">{{ t('hero.partner') }}</span>
        </div>
        
        <h1 class="mus-h1">
          {{ t('hero.title') }} <br/>
          <span class="mus-gold-text">{{ t('hero.subtitle') }}</span>
        </h1>
        
        <p class="mus-p">
          {{ t('hero.desc') }}
        </p>
        
        <div class="mus-cta-group">
          <button @click="openAuthModal('register')" class="mus-btn-primary cursor-pointer border-none">
            {{ t('hero.cta') }}
          </button>
        </div>
      </div>

      <div class="mus-hero-visual">
        <div class="mus-visual-card">
          <img src="/deck_hero.png" class="mus-img" onerror="this.src='https://placehold.co/1200x800/050505/0fb361?text=Mus+Manager'"/>
        </div>
      </div>
    </section>

    <!-- Features Section -->
    <section class="mus-features">
      <div class="mus-section-header">
        <h2 class="mus-h2">{{ t('features.title') }}</h2>
        <p class="mus-p-muted">{{ t('features.desc') }}</p>
        <div class="mus-divider"></div>
      </div>

      <div class="mus-grid">
        <div v-for="(feat, i) in features" :key="i" class="mus-card">
          <div class="mus-icon">{{ feat.icon }}</div>
          <h3 class="mus-h3">{{ t(feat.title) }}</h3>
          <p class="mus-p-small">{{ t(feat.desc) }}</p>
        </div>
      </div>
    </section>

    <!-- Stats Section -->
    <section class="mus-stats">
       <div v-for="(stat, i) in stats" :key="i" class="mus-stat-item">
         <p class="mus-stat-value">{{ stat.value }}</p>
         <p class="mus-stat-label">{{ t(stat.label) }}</p>
       </div>
    </section>

    <!-- CTA Section -->
    <section class="mus-cta-banner">
      <h2 class="mus-h1 mus-dark">{{ t('cta.title') }}</h2>
      <p class="mus-p mus-dark opacity-60">{{ t('cta.desc') }}</p>
      <div class="mus-btn-center">
        <button @click="openAuthModal('register')" class="mus-btn-black cursor-pointer border-none">
          {{ t('cta.button') }}
        </button>
      </div>
    </section>

  </div>
</template>

<style scoped>
.mus-container {
  display: flex;
  flex-direction: column;
  gap: 160px;
  width: 100%;
}

.mus-hero {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 60px;
  padding: 40px 0;
}

@media (min-width: 1024px) {
  .mus-hero {
    flex-direction: row;
    text-align: left;
  }
}

.mus-hero-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 32px;
}

.mus-badge {
  display: inline-flex;
  align-items: center;
  gap: 12px;
  padding: 8px 16px;
  background: rgba(15, 179, 97, 0.1);
  border: 1px solid rgba(15, 179, 97, 0.2);
  border-radius: 99px;
  width: fit-content;
}

.mus-dot {
  width: 6px;
  height: 6px;
  background: #0fb361;
  border-radius: 99px;
  box-shadow: 0 0 10px #0fb361;
}

.mus-badge-text {
  font-size: 9px;
  font-weight: 900;
  color: #0fb361;
  text-transform: uppercase;
  letter-spacing: 0.3em;
}

.mus-h1 {
  font-size: clamp(2rem, 8vw, 4rem);
  font-weight: 900;
  line-height: 0.95;
  text-transform: uppercase;
  font-style: italic;
  letter-spacing: -0.05em;
}

.mus-h2 {
  font-size: clamp(1.5rem, 6vw, 3rem);
  font-weight: 900;
  text-transform: uppercase;
  font-style: italic;
}

.mus-p {
  font-size: 1.125rem;
  color: #94a3b8;
  line-height: 1.6;
}

.mus-cta-group {
  display: flex;
  flex-wrap: wrap;
  gap: 32px;
  padding-top: 24px;
}

.mus-btn-primary {
  padding: 20px 48px;
  background: #0fb361;
  color: #050505;
  font-weight: 900;
  text-transform: uppercase;
  border-radius: 99px;
  text-decoration: none;
  font-size: 11px;
  letter-spacing: 0.2em;
  transition: all 0.3s;
  box-shadow: 0 15px 40px rgba(15, 179, 97, 0.3);
}

.mus-btn-primary:hover {
  transform: translateY(-2px);
  background: #12d674;
}

.mus-btn-secondary {
  display: flex;
  align-items: center;
  gap: 12px;
  color: #64748b;
  font-weight: 900;
  text-transform: uppercase;
  font-size: 9px;
  letter-spacing: 0.2em;
  text-decoration: none;
  transition: color 0.3s;
}

.mus-btn-secondary:hover {
  color: white;
}

.mus-hero-visual {
  flex: 1;
  position: relative;
  width: 100%;
}

.mus-visual-card {
  background: rgba(15, 179, 97, 0.03);
  backdrop-filter: blur(20px);
  padding: 12px;
  border-radius: 3rem;
  border: 1px solid rgba(255, 255, 255, 0.05);
  transform: rotate(1deg);
  transition: transform 0.7s;
}

.mus-visual-card:hover {
  transform: rotate(0);
}

.mus-img {
  width: 100%;
  height: auto;
  border-radius: 2.5rem;
}

.mus-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 40px;
}

.mus-card {
  background: rgba(255, 255, 255, 0.02);
  padding: 40px;
  border-radius: 3.5rem;
  border: 1px solid rgba(255, 255, 255, 0.05);
  transition: transform 0.3s;
}

.mus-card:hover {
  transform: translateY(-10px);
  background: rgba(15, 179, 97, 0.05);
}

.mus-icon {
  font-size: 3rem;
  margin-bottom: 32px;
}

.mus-h3 {
  font-size: 1.25rem;
  font-weight: 900;
  text-transform: uppercase;
  margin-bottom: 16px;
  font-style: italic;
}

.mus-p-small {
  font-size: 0.875rem;
  color: #64748b;
  line-height: 1.6;
}

.mus-stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 40px;
  background: rgba(15, 179, 97, 0.05);
  padding: 80px 48px;
  border-radius: 4rem;
  text-align: center;
}

.mus-stat-value {
  font-size: clamp(2rem, 5vw, 4rem);
  font-weight: 900;
  font-style: italic;
  line-height: 1;
  margin-bottom: 12px;
}

.mus-stat-label {
  font-size: 10px;
  font-weight: 900;
  text-transform: uppercase;
  letter-spacing: 0.3em;
  color: #0fb361;
}

.mus-cta-banner {
  background: #0fb361;
  padding: 80px 40px;
  border-radius: 5rem;
  text-align: center;
  display: flex;
  flex-direction: column;
  gap: 40px;
  position: relative;
  overflow: hidden;
}

.mus-dark {
  color: #050505 !important;
}

.mus-btn-black {
  display: inline-block;
  background: #050505;
  color: #0fb361;
  padding: 24px 64px;
  border-radius: 99px;
  font-weight: 900;
  text-transform: uppercase;
  text-decoration: none;
  font-size: 12px;
  letter-spacing: 0.15em;
  transition: transform 0.3s;
}

.mus-btn-black:hover {
  transform: scale(1.1);
}

.mus-section-header {
  text-align: center;
  margin-bottom: 80px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 24px;
}

.mus-divider {
  width: 64px;
  height: 4px;
  background: #0fb361;
  border-radius: 99px;
}
</style>
