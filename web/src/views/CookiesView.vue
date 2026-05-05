<script setup>
import { useI18n } from 'vue-i18n'

const { t, tm } = useI18n()
</script>

<template>
  <div class="legal-view mus-container py-20 max-w-4xl mx-auto">
    <header class="text-center mb-16 animate-fade-in">
      <h1 class="mus-h1 mb-4">{{ t('legal.cookies_title') }}</h1>
      <div class="mus-divider mx-auto mt-8"></div>
    </header>
    
    <div class="mus-glass p-8 md:p-12 rounded-[40px] mb-12 border-emerald-500/10">
      <p class="mus-p mb-12 text-lg leading-relaxed">
        {{ t('legal.cookies_p1') }}
      </p>

      <div v-for="(section, i) in tm('legal.cookies_sections')" :key="i" class="mb-12">
        <h2 class="mus-h3 mb-4 text-[#0fb361]">{{ section.title }}</h2>
        <p class="mus-p text-slate-400 leading-relaxed">{{ section.text }}</p>
      </div>

      <div class="overflow-x-auto mt-12 bg-black/20 rounded-3xl p-1 border border-white/5">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="text-emerald-500 text-[10px] font-black uppercase tracking-widest border-b border-white/5">
              <th class="p-6">{{ t('legal.cookies_table_headers.type') }}</th>
              <th class="p-6">{{ t('legal.cookies_table_headers.purpose') }}</th>
              <th class="p-6">{{ t('legal.cookies_table_headers.expiry') }}</th>
            </tr>
          </thead>
          <tbody class="text-sm">
            <tr v-for="(cookie, i) in tm('legal.cookies_list')" :key="i" class="border-b border-white/5 last:border-0 hover:bg-white/[0.02] transition-colors">
              <td class="p-6 font-bold text-white">{{ cookie.name }}</td>
              <td class="p-6 text-slate-400">{{ cookie.purpose }}</td>
              <td class="p-6 text-emerald-500/60 font-mono text-xs">{{ cookie.expiry }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <p class="mus-p mt-12 text-sm italic text-slate-500 leading-relaxed border-t border-white/5 pt-8">
        {{ t('legal.cookies_footer') }}
      </p>
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

th {
  background: rgba(15, 179, 97, 0.02);
}
</style>
