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
      <h1 class="mus-h1 mb-4">{{ t('legal.cookies_title') }}</h1>
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
        {{ t('legal.cookies_p1') }}
      </p>

      <div v-for="(section, i) in tm('legal.cookies_sections')" :key="i" class="mb-12">
        <h2 class="mus-h3 mb-4 text-[var(--secondary)]">{{ t(`legal.cookies_sections[${i}].title`) }}</h2>
        <p class="mus-p text-slate-400 leading-relaxed">{{ t(`legal.cookies_sections[${i}].text`) }}</p>
      </div>

      <div class="overflow-x-auto mt-12 bg-black/20 rounded-3xl p-1 border border-white/5">
        <table class="w-full text-left border-collapse min-w-[600px]">
          <thead>
            <tr class="text-[var(--secondary)] text-[10px] font-black uppercase tracking-widest border-b border-white/5">
              <th class="p-6">{{ t('legal.cookies_table_headers.type') }}</th>
              <th class="p-6">{{ t('legal.cookies_table_headers.purpose') }}</th>
              <th class="p-6">{{ t('legal.cookies_table_headers.expiry') }}</th>
            </tr>
          </thead>
          <tbody class="text-sm">
            <tr v-for="(cookie, i) in tm('legal.cookies_list')" :key="i" class="border-b border-white/5 last:border-0 hover:bg-white/[0.02] transition-colors">
              <td class="p-6 font-bold text-white">{{ cookie.name }}</td>
              <td class="p-6 text-slate-400">{{ cookie.purpose }}</td>
              <td class="p-6 text-[var(--secondary)]/60 font-mono text-xs">{{ cookie.expiry }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <p class="mus-p mt-12 text-sm italic text-slate-500 leading-relaxed border-t border-white/5 pt-8 mb-12">
        {{ t('legal.cookies_footer') }}
      </p>

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

th {
  background: rgba(233, 195, 73, 0.02);
}

.legal-nav-link {
  @apply px-6 py-2.5 rounded-full border border-white/5 text-[10px] font-black uppercase tracking-widest text-slate-500 transition-all hover:border-[var(--secondary)] hover:text-[var(--secondary)] bg-white/[0.02];
}

.legal-nav-link.active {
  @apply border-[var(--secondary)] bg-[var(--secondary)]/10 text-[var(--secondary)];
}
</style>
