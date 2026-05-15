<script setup>
import { ref } from 'vue'
import { authService } from '../services/api'
import { useI18n } from 'vue-i18n'
import { useToast } from 'primevue/usetoast'
import MusLoader from '../components/MusLoader.vue'

const { t, locale } = useI18n()
const toast = useToast()

const email = ref('')
const loading = ref(false)

const handleSubmit = async () => {
  loading.value = true
  try {
    const response = await authService.forgotPassword(email.value, locale.value)
    toast.add({
      severity: 'success',
      summary: t('common.success'),
      detail: response.message || t('auth.forgot_password.success'),
      life: 10000 // Increased life for dev messages
    })
    email.value = ''
  } catch (e) {
    toast.add({
      severity: 'error',
      summary: t('common.error'),
      detail: e.message || t('auth.forgot_password.error'),
      life: 5000
    })
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="flex flex-column align-items-center justify-content-start min-h-screen pt-2 pb-6 px-4 relative overflow-hidden">
    <div class="absolute top-0 left-0 w-full h-full bg-[url('/grid.svg')] opacity-5 pointer-events-none"></div>

    <div class="max-w-md w-full rounded-[2.5rem] p-8 md:p-12 mt-0 relative overflow-hidden border border-white/10 animate-in fade-in zoom-in duration-500">
      <MusLoader v-if="loading" overlay />
      
      <div class="absolute -top-24 -right-24 w-64 h-64 bg-[#0fb361]/10 blur-[80px] rounded-full"></div>
      
      <header class="text-center mb-6 relative z-10">
        <img src="/logo.png" class="logo-image-form" alt="Mus Manager Logo" />
        <h2 class="auth-title">
          {{ t('auth.forgot_password.title') }}
        </h2>
        <p class="auth-subtitle">
          {{ t('auth.forgot_password.subtitle') }}
        </p>
      </header>

      <form @submit.prevent="handleSubmit" class="space-y-4 text-left relative z-10">
        <div class="input-group">
          <label class="input-label">{{ t('auth.forgot_password.email_label') }}</label>
          <div class="input-wrapper">
            <i class="pi pi-envelope input-icon"></i>
            <input v-model="email" type="email" required 
                   class="mus-input-field" 
                   :placeholder="t('auth.emailPlaceholder')">
          </div>
        </div>

        <button type="submit" :disabled="loading" class="mus-button-primary w-full py-3 mt-2 group">
          <span class="font-black uppercase tracking-widest text-sm">
            {{ t('auth.forgot_password.submit') }}
          </span>
          <i v-if="!loading" class="pi pi-arrow-right ml-2 group-hover:translate-x-1 transition-transform"></i>
        </button>
      </form>

      <footer class="mt-4 text-center pt-4 border-t border-white/5 relative z-10">
        <p class="text-slate-500 text-[10px] font-bold uppercase tracking-widest leading-loose">
          <router-link to="/" class="text-[#0fb361] hover:text-[#f4d125] transition-colors font-black border-b border-[#0fb361]/30">
            {{ t('auth.forgot_password.back_to_home') || t('auth.forgot_password.back_to_login') }}
          </router-link>
        </p>
      </footer>
    </div>
  </div>
</template>

<style scoped>
.logo-image-form {
  width: 64px;
  height: 64px;
  object-fit: contain;
  margin: 0 auto 24px;
  filter: drop-shadow(0 10px 20px rgba(15, 179, 97, 0.2));
}
.auth-title { font-size: 24px; color: var(--secondary); font-weight: 900; text-transform: uppercase; letter-spacing: 1px; }
.auth-subtitle { font-family: var(--font-main); color: var(--text-main); margin-top: 12px; font-weight: 400; font-size: 13px; text-transform: uppercase; letter-spacing: 0.05em; opacity: 0.7; line-height: 1.5; }

.input-group { display: flex; flex-direction: column; gap: 10px; }
.input-label { font-size: 10px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.25em; color: var(--text-muted); margin-left: 8px; }

.input-wrapper { position: relative; }
.input-icon { position: absolute; left: 24px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 14px; }

.mus-input-field {
  width: 100%; background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 20px; padding: 18px 24px 18px 56px; color: var(--text-main); font-size: 14px; font-weight: 600;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.mus-input-field:focus { outline: none; border-color: var(--secondary); background: var(--surface-hover); }
</style>
