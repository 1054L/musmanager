<script setup>
import { useI18n } from 'vue-i18n'

const { t, tm } = useI18n()

const benefits = [
  {
    title: 'management.title',
    desc: 'management.desc',
    icon: '🏆',
    pointsKey: 'management.points'
  },
  {
    title: 'registration.title',
    desc: 'registration.desc',
    icon: '👥',
    pointsKey: 'registration.points'
  },
  {
    title: 'results.title',
    desc: 'results.desc',
    icon: '📊',
    pointsKey: 'results.points'
  }
]
</script>

<template>
  <div class="features-view">
    <section class="banner">
      <h1 class="mus-h1">
        {{ t('features.banner.title1') }} <br/>
        <span class="mus-gradient-text">{{ t('features.banner.title2') }}</span>
      </h1>
      <p class="mus-p max-w-2xl mx-auto mt-8">
        {{ t('features.banner.desc', { brand: 'Mus Manager' }) }}
      </p>
    </section>

    <section class="benefits-grid">
      <div v-for="(benefit, i) in benefits" :key="i" class="benefit-card mus-glass">
        <div class="benefit-header">
          <span class="benefit-icon">{{ benefit.icon }}</span>
          <h2 class="mus-h3">{{ t('features.' + benefit.title) }}</h2>
        </div>
        <p class="text-slate-500 font-medium mb-8 leading-relaxed">{{ t('features.' + benefit.desc) }}</p>
        
        <ul class="benefit-points">
          <li v-for="point in tm('features.' + benefit.pointsKey)" :key="point">
            <i class="pi pi-check-circle text-[#0fb361]"></i>
            <span>{{ point }}</span>
          </li>
        </ul>
      </div>
    </section>

    <!-- How it Works Section -->
    <section class="how-it-works">
      <h2 class="mus-h2 text-center italic mb-12">{{ t('features.how_it_works.title') }}</h2>
      <div class="steps-grid">
        <div class="step-card">
          <div class="step-num">01</div>
          <h3 class="mus-h3">{{ t('features.how_it_works.step1_title').split('. ')[1] }}</h3>
          <p class="mus-p-small">{{ t('features.how_it_works.step1_desc') }}</p>
        </div>
        <div class="step-card">
          <div class="step-num">02</div>
          <h3 class="mus-h3">{{ t('features.how_it_works.step2_title').split('. ')[1] }}</h3>
          <p class="mus-p-small">{{ t('features.how_it_works.step2_desc') }}</p>
        </div>
        <div class="step-card">
          <div class="step-num">03</div>
          <h3 class="mus-h3">{{ t('features.how_it_works.step3_title').split('. ')[1] }}</h3>
          <p class="mus-p-small">{{ t('features.how_it_works.step3_desc') }}</p>
        </div>
        <div class="step-card">
          <div class="step-num">04</div>
          <h3 class="mus-h3">{{ t('features.how_it_works.step4_title').split('. ')[1] }}</h3>
          <p class="mus-p-small">{{ t('features.how_it_works.step4_desc') }}</p>
        </div>
      </div>
    </section>

    <section class="deep-dive mus-glass">
      <div class="dive-content">
        <h2 class="mus-h2 italic">{{ t('hero.title') }}</h2>
        <p class="mus-p opacity-60">
          {{ t('hero.desc') }}
        </p>
        <div class="stats-row">
          <div class="stat">
            <span class="val">100%</span>
            <span class="lab">{{ t('features.stats_labels.digital') }}</span>
          </div>
          <div class="stat">
            <span class="val">0€</span>
            <span class="lab">{{ t('features.stats_labels.cost') }}</span>
          </div>
          <div class="stat">
            <span class="val">24/7</span>
            <span class="lab">{{ t('features.stats_labels.access') }}</span>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<style scoped>
.features-view {
  display: flex;
  flex-direction: column;
  gap: 120px;
  padding-bottom: 120px;
}

.banner {
  text-align: center;
}

.benefits-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
  gap: 40px;
}

.benefit-card {
  padding: 60px 48px;
  border-radius: 40px;
  display: flex;
  flex-direction: column;
}

.benefit-header {
  display: flex;
  align-items: center;
  gap: 20px;
  margin-bottom: 24px;
}

.benefit-icon {
  font-size: 2.5rem;
}

.benefit-points {
  list-style: none;
  padding: 0;
  margin: auto 0 0;
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.benefit-points li {
  display: flex;
  align-items: center;
  gap: 12px;
  font-size: 11px;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  color: #94a3b8;
}

.deep-dive {
  padding: 100px;
  border-radius: 60px;
  background: linear-gradient(135deg, rgba(15, 179, 97, 0.05) 0%, transparent 100%);
  text-align: center;
}

.dive-content {
  max-width: 800px;
  margin: 0 auto;
  display: flex;
  flex-direction: column;
  gap: 32px;
}

.stats-row {
  display: flex;
  justify-content: center;
  gap: 60px;
  margin-top: 40px;
}

.stat {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.how-it-works {
  display: flex;
  flex-direction: column;
  gap: 60px;
}

.steps-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 32px;
}

.step-card {
  background: rgba(255, 255, 255, 0.02);
  padding: 40px;
  border-radius: 32px;
  border: 1px solid rgba(255, 255, 255, 0.05);
  display: flex;
  flex-direction: column;
  gap: 20px;
  transition: all 0.3s;
}

.step-card:hover {
  background: rgba(15, 179, 97, 0.03);
  border-color: rgba(15, 179, 97, 0.2);
  transform: translateY(-5px);
}

.step-num {
  font-size: 0.75rem;
  font-weight: 950;
  color: #0fb361;
  letter-spacing: 0.2em;
  opacity: 0.5;
}

.val {
  font-size: 3rem;
  font-weight: 950;
  font-style: italic;
  color: #0fb361;
}

.lab {
  font-size: 10px;
  font-weight: 900;
  text-transform: uppercase;
  letter-spacing: 0.3em;
  color: #475569;
}

@media (max-width: 768px) {
  .deep-dive {
    padding: 60px 24px;
  }
  .stats-row {
    flex-direction: column;
    gap: 40px;
  }
}
</style>
