<script setup>
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
</script>

<template>
  <div class="legal-view mus-container py-20 max-w-4xl mx-auto">
    <h1 class="mus-h1 mb-12">{{ t('legal.terms_title') }}</h1>
    
    <div class="mus-glass p-8 md:p-12 rounded-3xl mb-12">
      <p class="mus-p mb-10 text-lg">
        {{ t('legal.terms_p1') }}
      </p>

      <div class="mb-10">
        <h2 class="mus-h3 mb-4 text-[#0fb361]">1. Uso de la Plataforma</h2>
        <p class="mus-p text-slate-400">
          Mus Manager se proporciona como una herramienta para la organización de torneos de mus. Queda prohibido el uso de la plataforma para apuestas ilegales o cualquier actividad que vulnere la legislación vigente.
        </p>
      </div>

      <div class="mb-10">
        <h2 class="mus-h3 mb-4 text-[#0fb361]">2. Responsabilidad</h2>
        <p class="mus-p text-slate-400">
          Los organizadores de torneos son responsables de la veracidad de los datos introducidos y de la gestión física de los eventos. Mus Manager no se hace responsable de las disputas que puedan surgir entre jugadores u organizadores.
        </p>
      </div>

      <div class="mb-10">
        <h2 class="mus-h3 mb-4 text-[#0fb361]">3. Propiedad Intelectual</h2>
        <p class="mus-p text-slate-400">
          Todo el software, diseño y contenido (excepto el aportado por los usuarios) es propiedad de Mus Manager.
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
