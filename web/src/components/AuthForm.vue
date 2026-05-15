<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { authService } from '../services/api'
import { useI18n } from 'vue-i18n'
import MusLoader from './MusLoader.vue'

const props = defineProps({
  initialMode: {
    type: String,
    default: 'login' // 'login' or 'register'
  },
  redirect: {
    type: String,
    default: '/dashboard'
  }
})

const emit = defineEmits(['success', 'mode-change'])

const { t } = useI18n()
const router = useRouter()
const mode = ref(props.initialMode)
const email = ref('')
const password = ref('')
const confirmPassword = ref('')
const role = ref('user')
const ageVerified = ref(false)
const loading = ref(false)
const error = ref(null)

const isLogin = computed(() => mode.value === 'login')

const formattedSubtitle = computed(() => {
  const text = isLogin.value ? t('auth.loginDesc') : t('auth.registerDesc')
  return text.replace('Mus Manager', '<span class="mus-gold-text">Mus Manager</span>')
})

const switchMode = (newMode) => {
  mode.value = newMode
  error.value = null
  emit('mode-change', newMode)
}

const handleSubmit = async () => {
  loading.value = true
  error.value = null
  
  try {
    if (mode.value === 'register') {
      if (password.value !== confirmPassword.value) {
        throw new Error(t('auth.passwordMismatch'))
      }
      if (!ageVerified.value) {
        throw new Error(t('auth.age_verification'))
      }
      await authService.register(email.value, password.value, role.value, ageVerified.value)
    }
    
    await authService.login(email.value, password.value)
    emit('success')
    
    // Use location.href to ensure a clean state and full reload on the dashboard
    if (props.redirect) {
      window.location.href = props.redirect
    } else {
      window.location.reload()
    }
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="auth-form-container relative z-10">
    <MusLoader v-if="loading" overlay />
    <header class="text-center mb-10">
      <img src="/logo.png" class="logo-image-form" alt="Mus Manager Logo" />
      <h2 class="auth-title">
        {{ isLogin ? t('auth.welcome') : t('auth.join') }}
      </h2>
      <p class="auth-subtitle" v-html="formattedSubtitle"></p>
    </header>

    <form @submit.prevent="handleSubmit" class="space-y-6 text-left">
      <Transition name="slide-fade">
        <div v-if="error" class="error-banner">
          <i class="pi pi-exclamation-circle mr-2"></i>
          {{ error }}
        </div>
      </Transition>

      <div class="input-group">
        <label class="input-label">{{ t('auth.email') }}</label>
        <div class="input-wrapper">
          <i class="pi pi-envelope input-icon"></i>
          <input v-model="email" type="email" required 
                 class="mus-input-field" 
                  :placeholder="t('auth.emailPlaceholder')">
        </div>
      </div>

      <div class="input-group">
        <label class="input-label">{{ t('auth.password') }}</label>
        <div class="input-wrapper">
          <i class="pi pi-lock input-icon"></i>
          <input v-model="password" type="password" required 
                 class="mus-input-field" 
                 :placeholder="t('auth.passwordPlaceholder')">
        </div>
      </div>

      <div v-if="isLogin" class="flex justify-end -mt-4">
        <router-link to="/forgot-password" class="text-[10px] font-bold uppercase tracking-widest text-[#0fb361] hover:text-[#f4d125] transition-colors border-b border-[#0fb361]/20">
          {{ t('auth.forgot_password_link') }}
        </router-link>
      </div>

      <Transition name="expand">
        <div v-if="!isLogin" class="input-group">
          <label class="input-label">{{ t('auth.confirm') }}</label>
          <div class="input-wrapper">
            <i class="pi pi-shield input-icon"></i>
            <input v-model="confirmPassword" type="password" required 
                   class="mus-input-field" 
                   placeholder="••••••••">
          </div>
        </div>
      </Transition>

      <Transition name="expand">
        <div v-if="!isLogin">
          <div class="input-group">
            <label class="input-label">{{ t('auth.role') }}</label>
            <div class="flex gap-4">
              <label class="flex-1 cursor-pointer">
                <input type="radio" v-model="role" value="user" class="hidden peer">
                <div class="mus-glass p-3 rounded-lg border border-[var(--border)] text-center transition-all peer-checked:border-[var(--secondary)] peer-checked:bg-[var(--surface-hover)]">
                  <i class="pi pi-user text-xs mb-1 block text-[var(--secondary)]"></i>
                  <span class="text-[10px] font-bold uppercase tracking-wider text-[var(--text-main)]">{{ t('auth.roleUser') }}</span>
                </div>
              </label>
              <label class="flex-1 cursor-pointer">
                <input type="radio" v-model="role" value="admin" class="hidden peer">
                <div class="mus-glass p-3 rounded-lg border border-[var(--border)] text-center transition-all peer-checked:border-[var(--secondary)] peer-checked:bg-[var(--surface-hover)]">
                  <i class="pi pi-star text-xs mb-1 block text-[var(--secondary)]"></i>
                  <span class="text-[10px] font-bold uppercase tracking-wider text-[var(--text-main)]">{{ t('auth.roleAdmin') }}</span>
                </div>
              </label>
            </div>
          </div>
          <div class="input-group">
            <label class="flex items-center gap-3 cursor-pointer select-none group/check">
              <div class="relative flex items-center justify-center">
                <input type="checkbox" v-model="ageVerified" required class="hidden peer">
                <div class="w-6 h-6 border-2 border-[var(--border)] rounded-lg transition-all peer-checked:bg-[var(--secondary)] peer-checked:border-[var(--secondary)] flex items-center justify-center group-hover/check:border-[var(--secondary)]">
                  <i class="pi pi-check text-[10px] text-[var(--surface)] opacity-0 peer-checked:opacity-100 transition-opacity"></i>
                </div>
              </div>
              <span class="text-[11px] font-bold text-[var(--text-main)] opacity-70 group-hover/check:opacity-100 transition-opacity uppercase tracking-wider">
                {{ t('auth.age_verification') }}
              </span>
            </label>
          </div>
        </div>
      </Transition>

      <button type="submit" :disabled="loading || (!isLogin && !ageVerified)" class="mus-button-primary w-full py-3 mt-2 group">
        <span class="font-black uppercase tracking-widest text-sm">
          {{ isLogin ? t('auth.submitLogin') : t('auth.submitRegister') }}
        </span>
        <i v-if="!loading" class="pi pi-arrow-right ml-2 group-hover:translate-x-1 transition-transform"></i>
      </button>
    </form>
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
.auth-title { font-size: 32px; color: var(--secondary); }
.auth-subtitle { font-family: var(--font-main); color: var(--text-main); margin-top: 12px; font-weight: 400; font-size: 14px; text-transform: uppercase; letter-spacing: 0.05em; opacity: 0.8; }

.error-banner {
  background: rgba(244, 63, 94, 0.1); border: 1px solid rgba(244, 63, 94, 0.2);
  padding: 16px; border-radius: 16px; font-size: 12px; color: #fb7185;
  font-weight: 600; text-align: center; display: flex; align-items: center; justify-content: center;
}

.input-group { display: flex; flex-direction: column; gap: 10px; }
.input-label { font-size: 10px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.25em; color: var(--text-muted); margin-left: 8px; }

.input-wrapper { position: relative; }
.input-icon { position: absolute; left: 24px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 14px; transition: color 0.3s; }
.mus-input-field:focus + .input-icon { color: var(--primary); }

.mus-input-field {
  width: 100%; background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 20px; padding: 18px 24px 18px 56px; color: var(--text-main); font-size: 14px; font-weight: 600;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.mus-input-field:focus { outline: none; border-color: var(--secondary); background: rgba(233, 195, 73, 0.05); box-shadow: 0 0 10px rgba(233, 195, 73, 0.2); }



.footer-switch { font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.1em; color: var(--text-muted); }
.switch-btn {
  background: none; border: none; color: var(--primary); font-weight: 950; text-transform: uppercase;
  border-bottom: 2px solid var(--primary-glow); padding: 0 2px 2px; margin-left: 4px;
  cursor: pointer; transition: all 0.3s;
}
.switch-btn:hover { color: var(--text-main); border-color: var(--text-main); }

/* Transitions */
.slide-fade-enter-active, .slide-fade-leave-active { transition: all 0.3s ease-out; }
.slide-fade-enter-from, .slide-fade-leave-to { transform: translateY(-10px); opacity: 0; }

.expand-enter-active, .expand-leave-active { transition: all 0.3s ease; max-height: 100px; opacity: 1; }
.expand-enter-from, .expand-leave-to { max-height: 0; opacity: 0; overflow: hidden; transform: scaleY(0); }

@keyframes rotate { to { transform: rotate(360deg); } }
</style>
