<script setup>
import { useI18n } from 'vue-i18n'

const { t, tm } = useI18n()
</script>

<template>
  <div class="legal-view mus-container py-20 max-w-4xl mx-auto">
    <h1 class="mus-h1 mb-4">{{ t('legal.privacy_title') }}</h1>
    <p class="text-slate-500 font-bold mb-12 uppercase tracking-widest text-xs">{{ t('legal.privacy_last_updated') }}</p>
    
    <div class="mus-glass p-8 md:p-12 rounded-3xl mb-12">
      <p class="mus-p mb-10 text-lg">
        {{ t('legal.privacy_p1') }}
      </p>

      <div v-for="(section, i) in tm('legal.privacy_sections')" :key="i" class="mb-10">
        <h2 class="mus-h3 mb-4 text-[#0fb361]">{{ section.title }}</h2>
        <p class="mus-p text-slate-400">{{ section.text }}</p>
      </div>

      <div class="mb-10">
        <h2 class="mus-h3 mb-4 text-[#0fb361]">Google AdSense</h2>
        <p class="mus-p text-slate-400">
           Esta aplicación utiliza Google AdSense para mostrar anuncios. Google utiliza cookies para servir anuncios basados en las visitas previas del usuario a este u otros sitios web. Los usuarios pueden inhabilitar la publicidad personalizada visitando la configuración de anuncios de Google.
        </p>
      </div>
    </div>

    <div class="text-center">
      <router-link to="/" class="mus-btn-secondary">
        <i class="pi pi-arrow-left mr-2"></i> {{ t('tournament_form.back_dashboard') }}
      </router-link>
    </div>
  </div>
</template>

<style scoped>
.legal-view {
  line-height: 1.8;
}
</style>
