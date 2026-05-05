<script setup>
import { useI18n } from 'vue-i18n'
import GoogleAd from '../components/GoogleAd.vue';

const { t } = useI18n()

const steps = [
  { key: 'step1', icon: 'pi-cog' },
  { key: 'step2', icon: 'pi-plus-circle' },
  { key: 'step3', icon: 'pi-users' },
  { key: 'step4', icon: 'pi-bolt' },
  { key: 'step5', icon: 'pi-chart-bar' }
]
</script>

<template>
  <div class="how-it-works-view mus-container py-20">
    <!-- Header Section -->
    <header class="text-center mb-24 animate-in fade-in slide-in-from-bottom-10 duration-1000">
      <h1 class="mus-h1 mb-6 italic">{{ t('how_it_works_page.title') }}</h1>
      <p class="mus-p-gold text-2xl font-black uppercase tracking-tighter mb-8">
        {{ t('how_it_works_page.subtitle') }}
      </p>
      <div class="mus-divider mx-auto mb-10"></div>
      <p class="mus-p max-w-3xl mx-auto opacity-70 leading-relaxed text-lg">
        {{ t('how_it_works_page.intro') }}
      </p>
    </header>

    <div class="col-12 px-4 mb-20">
      <GoogleAd :key="'top-how'" />
    </div>

    <!-- Timeline Steps -->
    <div class="timeline relative max-w-4xl mx-auto mb-32">
      <!-- Vertical Line -->
      <div class="absolute left-[20px] md:left-1/2 top-0 bottom-0 w-px bg-gradient-to-b from-emerald-500/0 via-emerald-500/30 to-emerald-500/0"></div>

      <div v-for="(step, i) in steps" :key="i" 
           class="timeline-item mb-20 relative flex flex-col md:flex-row items-start md:items-center"
           :class="i % 2 === 0 ? 'md:flex-row' : 'md:flex-row-reverse'">
        
        <!-- Icon Node -->
        <div class="absolute left-0 md:left-1/2 md:-translate-x-1/2 w-10 h-10 rounded-full bg-[#0fb361] flex items-center justify-center z-10 shadow-[0_0_20px_rgba(15,179,97,0.4)]">
          <i :class="'pi ' + step.icon" class="text-black font-bold"></i>
        </div>

        <!-- Content Card -->
        <div class="w-full md:w-[45%] pl-14 md:pl-0">
          <div class="mus-glass p-8 rounded-3xl border-white/5 hover:border-emerald-500/20 transition-all group">
            <span class="text-[10px] font-black text-emerald-500/50 uppercase tracking-widest block mb-2">Paso {{ i + 1 }}</span>
            <h3 class="mus-h3 mb-4 group-hover:text-white transition-colors">{{ t(`how_it_works_page.${step.key}.title`) }}</h3>
            <p class="mus-p-small opacity-60 leading-relaxed">{{ t(`how_it_works_page.${step.key}.desc`) }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Final CTA -->
    <section class="text-center py-20 animate-in fade-in zoom-in duration-1000">
      <div class="mus-glass p-16 rounded-[4rem] border-emerald-500/10 bg-emerald-500/5 relative overflow-hidden">
        <div class="absolute top-0 right-0 p-8 opacity-10">
          <img src="/logo.png" class="w-40" />
        </div>
        <h2 class="mus-h2 mb-8 italic">{{ t('cta.title') }}</h2>
        <p class="mus-p mb-12 max-w-2xl mx-auto opacity-70">
          {{ t('cta.desc') }}
        </p>
        <router-link to="/register" class="mus-btn-primary scale-110">
          {{ t('hero.cta') }}
        </router-link>
      </div>
    </section>

    <div class="col-12 px-4 mt-20">
      <GoogleAd :key="'bottom-how'" />
    </div>
  </div>
</template>

<style scoped>
.mus-p-gold {
  background: linear-gradient(135deg, #0fb361 0%, #34d399 50%, #059669 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.timeline-item {
  animation: slideIn 0.8s cubic-bezier(0.4, 0, 0.2, 1) both;
}

@keyframes slideIn {
  from { opacity: 0; transform: translateY(30px); }
  to { opacity: 1; transform: translateY(0); }
}

/* Stagger animation for timeline items */
.timeline-item:nth-child(1) { animation-delay: 0.1s; }
.timeline-item:nth-child(2) { animation-delay: 0.2s; }
.timeline-item:nth-child(3) { animation-delay: 0.3s; }
.timeline-item:nth-child(4) { animation-delay: 0.4s; }
.timeline-item:nth-child(5) { animation-delay: 0.5s; }
</style>
