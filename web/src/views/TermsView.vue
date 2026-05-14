<script setup>
import { useI18n } from 'vue-i18n'

const { t, tm } = useI18n()
</script>

<template>
  <div class="legal-view mus-container py-20 max-w-4xl mx-auto">
    <nav class="flex flex-wrap justify-center gap-3 md:gap-4 mb-16 animate-fade-in">
      <router-link to="/terminos" class="legal-nav-link" active-class="active">{{ t('footer.terms') }}</router-link>
      <router-link to="/privacidad" class="legal-nav-link" active-class="active">{{ t('footer.privacy') }}</router-link>
      <router-link to="/cookies" class="legal-nav-link" active-class="active">{{ t('footer.cookies') }}</router-link>
    </nav>

    <header class="text-center mb-12 animate-fade-in">
      <h1 class="mus-h1 mb-4">{{ t('legal.terms_title') }}</h1>
      <p class="text-slate-500 font-bold uppercase tracking-widest text-xs">{{ t('legal.terms_last_updated') }}</p>
      <div class="mus-divider mx-auto mt-8"></div>
    </header>

    <div class="mb-12 bg-[var(--secondary)]/5 border border-[var(--secondary)]/20 rounded-[32px] p-8 md:p-10 flex flex-col md:flex-row items-center md:items-start gap-8 animate-fade-in shadow-[0_0_50px_rgba(233,195,73,0.05)]">
      <div class="w-16 h-16 rounded-2xl bg-[var(--secondary)]/10 border border-[var(--secondary)]/20 flex items-center justify-center text-[var(--secondary)] flex-shrink-0 shadow-[0_0_20px_rgba(233,195,73,0.1)]">
        <i class="pi pi-exclamation-triangle text-3xl"></i>
      </div>
      <div class="flex-1 text-center md:text-left">
        <p class="text-[11px] font-black uppercase tracking-wider text-[var(--secondary)] leading-loose opacity-90">
          {{ t('legal.age_notice') }}
        </p>
      </div>
    </div>
    
    <div class="mus-glass p-8 md:p-12 rounded-[40px] mb-12 border-[var(--secondary)]/10">
      <p class="mus-p mb-12 text-lg leading-relaxed">
        {{ t('legal.terms_p1') }}
      </p>

      <div v-for="(section, i) in tm('legal.terms_sections')" :key="i" class="mb-12 last:mb-0 group">
        <div class="flex items-start gap-8">
          <div class="w-14 h-14 rounded-2xl bg-[var(--secondary)]/10 border border-[var(--secondary)]/20 flex items-center justify-center text-[var(--secondary)] font-black italic group-hover:bg-[var(--secondary)] group-hover:text-black transition-all flex-shrink-0 shadow-[0_0_20px_rgba(233,195,73,0.05)] group-hover:shadow-[0_0_30px_rgba(233,195,73,0.2)]">
            {{ i + 1 }}
          </div>
          <div class="flex-1">
            <h2 class="mus-h3 mb-4 text-white group-hover:text-[var(--secondary)] transition-colors">
              {{ t(`legal.terms_sections[${i}].title`).replace(/^\d+\.\s*/, '') }}
            </h2>
            <p class="mus-p text-slate-400 text-base leading-relaxed">
              {{ t(`legal.terms_sections[${i}].text`) }}
            </p>
          </div>
        </div>
      </div>

      <div class="mt-12 pt-8 border-t border-white/5 flex items-start gap-4">
        <div class="w-2 h-2 bg-[var(--secondary)] rounded-full mt-1 flex-shrink-0"></div>
        <p class="text-xs text-slate-500 leading-relaxed max-w-lg">
          {{ t('legal.contact_footer') }}
        </p>
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
@reference "../style.css";

.legal-view {
  animation: fadeIn 0.8s ease-out;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}

.legal-nav-link {
  @apply px-6 py-2.5 rounded-full border border-white/5 text-[10px] font-black uppercase tracking-widest text-slate-500 transition-all hover:border-[var(--secondary)] hover:text-[var(--secondary)] bg-white/[0.02];
}

.legal-nav-link.active {
  @apply border-[var(--secondary)] bg-[var(--secondary)]/10 text-[var(--secondary)];
}
</style>
