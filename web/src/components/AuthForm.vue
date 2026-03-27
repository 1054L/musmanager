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
        throw new Error(t('auth.passwordMismatch') || "Las contraseñas no coinciden")
      }
      await authService.register(email.value, password.value)
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
      <div class="logo-box">
        <span class="logo-letter mus-gold-text">M</span>
      </div>
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
                 placeholder="••••••••">
        </div>
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

      <button type="submit" :disabled="loading" class="mus-button-primary w-full py-5 mt-4 group">
        <span class="font-black uppercase tracking-widest text-sm">
          {{ isLogin ? t('auth.submitLogin') : t('auth.submitRegister') }}
        </span>
        <i v-if="!loading" class="pi pi-arrow-right ml-2 group-hover:translate-x-1 transition-transform"></i>
      </button>
    </form>

    <footer class="mt-8 text-center pt-8 border-t border-white/5">
      <p v-if="isLogin" class="footer-switch">
        {{ t('auth.noAccount') }} 
        <button @click="switchMode('register')" class="switch-btn">{{ t('auth.createAccount') }}</button>
      </p>
      <p v-else class="footer-switch">
        {{ t('auth.haveAccount') }} 
        <button @click="switchMode('login')" class="switch-btn">{{ t('auth.loginHere') }}</button>
      </p>
    </footer>
  </div>
</template>

<style scoped>
.logo-box {
  width: 64px; height: 64px; background: linear-gradient(135deg, rgba(15, 179, 97, 0.2), rgba(15, 179, 97, 0.05));
  border: 1px solid rgba(15, 179, 97, 0.2); border-radius: 20px;
  display: flex; align-items: center; justify-content: center;
  margin: 0 auto 24px; box-shadow: 0 10px 30px rgba(15, 179, 97, 0.1);
  overflow: hidden;
}
.logo-letter { font-size: 32px; font-weight: 950; line-height: 1; }
.auth-title { font-size: 32px; font-weight: 950; color: white; font-style: italic; text-transform: uppercase; letter-spacing: -0.025em; line-height: 1; }
.auth-subtitle { color: #475569; margin-top: 12px; font-weight: 600; font-size: 14px; text-transform: uppercase; letter-spacing: 0.05em; }

.error-banner {
  background: rgba(244, 63, 94, 0.1); border: 1px solid rgba(244, 63, 94, 0.2);
  padding: 16px; border-radius: 16px; font-size: 12px; color: #fb7185;
  font-weight: 600; text-align: center; display: flex; align-items: center; justify-content: center;
}

.input-group { display: flex; flex-direction: column; gap: 10px; }
.input-label { font-size: 10px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.25em; color: #475569; margin-left: 8px; }

.input-wrapper { position: relative; }
.input-icon { position: absolute; left: 24px; top: 50%; transform: translateY(-50%); color: #1e293b; font-size: 14px; transition: color 0.3s; }
.mus-input-field:focus + .input-icon { color: #0fb361; }

.mus-input-field {
  width: 100%; background: rgba(255, 255, 255, 0.02); border: 1px solid rgba(255, 255, 255, 0.06);
  border-radius: 20px; padding: 18px 24px 18px 56px; color: white; font-size: 14px; font-weight: 600;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.mus-input-field:focus { outline: none; border-color: #0fb361; background: rgba(15, 179, 97, 0.02); box-shadow: 0 0 0 4px rgba(15, 179, 97, 0.05); }



.footer-switch { font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.1em; color: #475569; }
.switch-btn {
  background: none; border: none; color: #0fb361; font-weight: 950; text-transform: uppercase;
  border-bottom: 2px solid rgba(15, 179, 97, 0.3); padding: 0 2px 2px; margin-left: 4px;
  cursor: pointer; transition: all 0.3s;
}
.switch-btn:hover { color: white; border-color: white; }

/* Transitions */
.slide-fade-enter-active, .slide-fade-leave-active { transition: all 0.3s ease-out; }
.slide-fade-enter-from, .slide-fade-leave-to { transform: translateY(-10px); opacity: 0; }

.expand-enter-active, .expand-leave-active { transition: all 0.3s ease; max-height: 100px; opacity: 1; }
.expand-enter-from, .expand-leave-to { max-height: 0; opacity: 0; overflow: hidden; transform: scaleY(0); }

@keyframes rotate { to { transform: rotate(360deg); } }
</style>
