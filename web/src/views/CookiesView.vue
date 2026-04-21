<script setup>
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
</script>

<template>
  <div class="legal-view mus-container py-20 max-w-4xl mx-auto">
    <h1 class="mus-h1 mb-12">{{ t('legal.cookies_title') }}</h1>
    
    <div class="mus-glass p-8 md:p-12 rounded-3xl mb-12">
      <p class="mus-p mb-10 text-lg">
        {{ t('legal.cookies_p1') }}
      </p>

      <div class="mb-10">
        <h2 class="mus-h3 mb-4 text-[#0fb361]">¿Qué son las cookies?</h2>
        <p class="mus-p text-slate-400">
          Una cookie es un pequeño archivo que se almacena en tu ordenador para ayudar a recordar tus preferencias y monitorizar el rendimiento del sitio.
        </p>
      </div>

      <div class="mb-10">
        <h2 class="mus-h3 mb-4 text-[#0fb361]">Google AdSense y la Cookie DART</h2>
        <p class="mus-p text-slate-400">
          Google, como proveedor de terceros, utiliza cookies para servir anuncios en este sitio. El uso de la cookie DART permite a Google mostrar anuncios a los usuarios según su visita a este y otros sitios en Internet. Los usuarios pueden inhabilitar el uso de la cookie DART a través de la política de privacidad de la red de contenido y de anuncios de Google.
        </p>
      </div>

      <div class="mb-10">
        <h2 class="mus-h3 mb-4 text-[#0fb361]">Cookies de Terceros</h2>
        <p class="mus-p text-slate-400">
          Además de Google, otros proveedores o redes publicitarias podrían utilizar cookies para mostrar anuncios. Puedes consultar sus políticas de privacidad en sus respectivos sitios web.
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
