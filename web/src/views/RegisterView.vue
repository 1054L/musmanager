<script setup>
import AuthForm from '../components/AuthForm.vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
</script>

<template>
  <div class="flex flex-column align-items-center justify-content-start min-h-screen pt-2 pb-6 px-6 relative overflow-hidden">
    <!-- Extra Background Interest -->
    <div class="absolute top-0 left-0 w-full h-full bg-[url('/grid.svg')] opacity-5 pointer-events-none"></div>

    <div class="max-w-md w-full rounded-[3rem] p-10 md:p-16 shadow-2xl relative overflow-hidden border border-white/10 animate-in fade-in zoom-in duration-500">
      <!-- Background Glow Effects -->
      <div class="absolute -top-24 -left-24 w-64 h-64 bg-[#f4d125]/10 blur-[80px] rounded-full"></div>
      <div class="absolute -bottom-24 -right-24 w-64 h-64 bg-[#0fb361]/05 blur-[80px] rounded-full"></div>
      
      <!-- Reusable Auth Form -->
      <AuthForm initialMode="register" redirect="/dashboard" />

      <!-- Footer -->
      <footer class="mt-4 text-center pt-4 border-t border-white/5 relative z-10">
        <p class="text-slate-500 text-[10px] font-bold uppercase tracking-widest leading-loose">
          {{ t('auth.haveAccount') }} <br/>
          <router-link to="/login" class="text-[#0fb361] hover:text-[#f4d125] transition-colors font-black border-b border-[#0fb361]/30">
            {{ t('auth.loginHere') }}
          </router-link>
        </p>
      </footer>
    </div>
  </div>
</template>
