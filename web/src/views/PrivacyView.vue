<script setup>
import { useI18n } from 'vue-i18n'

const { t, tm } = useI18n()
</script>

<template>
  <div class="legal-view mus-container py-20 max-w-4xl mx-auto">
    <header class="text-center mb-16 animate-fade-in">
      <h1 class="mus-h1 mb-4">{{ t('legal.privacy_title') }}</h1>
      <p class="text-slate-500 font-bold uppercase tracking-widest text-xs">{{ t('legal.privacy_last_updated') }}</p>
      <div class="mus-divider mx-auto mt-8"></div>
    </header>
    
    <div class="mus-glass p-8 md:p-12 rounded-[40px] mb-12 border-emerald-500/10">
      <p class="mus-p mb-12 text-lg leading-relaxed">
        {{ t('legal.privacy_p1') }}
      </p>

      <div v-for="(section, i) in tm('legal.privacy_sections')" :key="i" class="mb-12 last:mb-0 group">
        <div class="flex items-start gap-6">
          <div class="w-1 h-8 bg-emerald-500/20 group-hover:bg-emerald-500 transition-colors rounded-full mt-1"></div>
          <div>
            <h2 class="mus-h3 mb-4 text-white group-hover:text-emerald-400 transition-colors">{{ section.title }}</h2>
            <p class="mus-p text-slate-400 text-base leading-relaxed">{{ section.text }}</p>
          </div>
        </div>
      </div>
    </div>

    <div class="text-center">
      <router-link to="/" class="mus-btn-primary">
        <i class="pi pi-arrow-left mr-2"></i> {{ t('nav.home') }}
      </router-link>
    </div>
  </div>
</template>

<style scoped>
.legal-view {
  animation: fadeIn 0.8s ease-out;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>
