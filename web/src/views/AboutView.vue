<script setup>
import { useI18n } from 'vue-i18n'
import GoogleAd from '../components/GoogleAd.vue';

const { t } = useI18n()
</script>

<template>
  <div class="about-view mus-container py-20">
    <section class="text-center mb-20 animate-fade-in">
      <h1 class="mus-h1 mb-6">{{ t('about.title') }}</h1>
      <p class="mus-p-gold text-2xl font-bold italic tracking-wider">{{ t('about.subtitle') }}</p>
      <div class="mus-divider mx-auto mt-8"></div>
    </section>

    <div class="col-12 px-4">
      <google-ad :key="'top-about'" />
    </div>
    <div class="grid lg:grid-cols-2 gap-16 items-center mb-32">
      <div class="animate-slide-left">
        <p class="mus-p text-lg leading-relaxed mb-8">
          {{ t('about.p1') }}
        </p>
        <h3 class="mus-h3 mb-6 text-[#0fb361] italic">{{ t('footer.history') }}</h3>
        <p class="mus-p text-lg leading-relaxed">
          {{ t('about.tradition_text') }}
        </p>
      </div>
      <div class="mus-glass p-8 rounded-3xl animate-slide-right flex items-center justify-center">
        <img src="/logo.png" alt="Mus Manager" class="w-full h-auto max-w-sm mx-auto filter drop-shadow-2xl" />
      </div>
    </div>

    <section class="grid md:grid-cols-3 gap-12 mb-32">
      <div class="mus-card p-10 hover:bg-emerald-500/5 border-emerald-500/10 transition-all cursor-default group">
         <h3 class="mus-h3 mb-4 text-[#0fb361] group-hover:translate-x-2 transition-transform">{{ t('about.mission_title') }}</h3>
         <p class="mus-p-small leading-relaxed opacity-80">{{ t('about.mission_text') }}</p>
      </div>
      <div class="mus-card p-10 hover:bg-emerald-500/5 border-emerald-500/10 transition-all cursor-default group">
         <h3 class="mus-h3 mb-4 text-[#0fb361] group-hover:translate-x-2 transition-transform">{{ t('about.why_title') }}</h3>
         <p class="mus-p-small leading-relaxed opacity-80">{{ t('about.why_text') }}</p>
      </div>
      <div class="mus-card p-10 hover:bg-emerald-500/5 border-emerald-500/10 transition-all cursor-default group">
         <h3 class="mus-h3 mb-4 text-[#0fb361] group-hover:translate-x-2 transition-transform">{{ t('about.vision_title') }}</h3>
         <p class="mus-p-small leading-relaxed opacity-80">{{ t('about.vision_text') }}</p>
      </div>
    </section>

    <section class="mus-glass p-16 rounded-[4rem] text-center border-emerald-500/20 bg-emerald-500/5 mb-32">
       <h2 class="mus-h2 mb-8 italic">{{ t('cta.title') }}</h2>
       <p class="mus-p mb-12 max-w-2xl mx-auto">{{ t('cta.desc') }}</p>
       <router-link to="/register" class="mus-btn-primary">
         {{ t('hero.cta') }}
       </router-link>
    </section>
    
    <div class="col-12 px-4">
      <GoogleAd :key="'bottom-about'" />
    </div>
  </div>
</template>

<style scoped>
.mus-p-gold {
  background: linear-gradient(135deg, #bf953f 0%, #fcf6ba 50%, #aa771c 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.animate-fade-in {
  animation: fadeIn 1s ease-out;
}

.animate-slide-left {
  animation: slideLeft 0.8s ease-out;
}

.animate-slide-right {
  animation: slideRight 0.8s ease-out;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}

@keyframes slideLeft {
  from { opacity: 0; transform: translateX(-50px); }
  to { opacity: 1; transform: translateX(0); }
}

@keyframes slideRight {
  from { opacity: 0; transform: translateX(50px); }
  to { opacity: 1; transform: translateX(0); }
}
</style>
