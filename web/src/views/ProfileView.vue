<script setup>
import { ref, onMounted } from 'vue'
import { authService } from '../services/api'
import { useI18n } from 'vue-i18n'
import { useToast } from 'primevue/usetoast'
import MusLoader from '../components/MusLoader.vue'

const { t } = useI18n()
const toast = useToast()

const loading = ref(true)
const saving = ref(false)
const userProfile = ref({
  firstName: '',
  lastName: '',
  nickname: '',
  phone: '',
  email: ''
})

const passwords = ref({
  newPassword: '',
  confirmPassword: ''
})

const loadProfile = async () => {
  try {
    const user = authService.getUser()
    // Fetch full profile from API to get the latest firstName/lastName
    const response = await fetch('/api/me', {
      headers: authService.getAuthHeader()
    })
    
    if (response.ok) {
      const data = await response.json()
      userProfile.value = {
        firstName: data.firstName || '',
        lastName: data.lastName || '',
        nickname: data.nickname || '',
        phone: data.phone || '',
        email: data.email || ''
      }
    }
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: t('common.error'), life: 3000 })
  } finally {
    loading.value = false
  }
}

const updateProfile = async () => {
  if (passwords.value.newPassword && passwords.value.newPassword !== passwords.value.confirmPassword) {
    toast.add({ severity: 'error', summary: 'Error', detail: t('auth.passwordMismatch') || 'Las contraseñas no coinciden', life: 3000 })
    return
  }

  saving.value = true
  try {
    const data = {
      firstName: userProfile.value.firstName,
      lastName: userProfile.value.lastName,
      nickname: userProfile.value.nickname,
      phone: userProfile.value.phone
    }
    
    if (passwords.value.newPassword) {
      data.password = passwords.value.newPassword
    }

    await authService.updateProfile(data)
    
    // Update local storage if password changed
    if (data.password) {
      const currentLocalUser = authService.getUser();
      currentLocalUser.password = data.password;
      localStorage.setItem('user', JSON.stringify(currentLocalUser));
    }
    
    toast.add({ severity: 'success', summary: t('common.success'), detail: t('profile.updated') || 'Perfil actualizado', life: 3000 })
    passwords.value.newPassword = ''
    passwords.value.confirmPassword = ''
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: error.message, life: 3000 })
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  loadProfile()
})
</script>

<template>
  <div class="view-container animate-in fade-in duration-500">
    <div class="mus-page-header">
      <h1 class="mus-title">{{ t('profile.title') || 'Mi Perfil' }}</h1>
      <p class="mus-subtitle">{{ t('profile.desc') || 'Gestiona tu información personal y credenciales' }}</p>
    </div>

    <MusLoader v-if="loading" />

    <div v-else class="max-w-5xl mx-auto mus-glass rounded-[3rem] p-8 md:p-12 shadow-2xl relative overflow-hidden border border-[var(--border)]">
      <!-- Background Glow Effects -->
      <div class="absolute -top-24 -right-24 w-64 h-64 bg-[var(--primary)] opacity-10 blur-[80px] rounded-full pointer-events-none"></div>
      
      <form @submit.prevent="updateProfile" class="space-y-8 relative z-10">
        
        <div class="section-block">
          <h2 class="section-title"><i class="pi pi-user mr-2"></i>{{ t('profile.personalInfo') || 'Información Personal' }}</h2>
          
          <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
            <div class="input-group">
              <label class="input-label">{{ t('profile.firstName') || 'Nombre' }}</label>
              <div class="input-wrapper">
                <i class="pi pi-id-card input-icon"></i>
                <input v-model="userProfile.firstName" type="text" class="mus-input-field" :placeholder="t('profile.firstNamePlaceholder') || 'Nombre'">
              </div>
            </div>
            
            <div class="input-group">
              <label class="input-label">{{ t('profile.lastName') || 'Apellidos' }}</label>
              <div class="input-wrapper">
                <i class="pi pi-id-card input-icon"></i>
                <input v-model="userProfile.lastName" type="text" class="mus-input-field" :placeholder="t('profile.lastNamePlaceholder') || 'Apellidos'">
              </div>
            </div>

            <div class="input-group">
              <label class="input-label">{{ t('profile.nickname') || 'Mote' }}</label>
              <div class="input-wrapper">
                <i class="pi pi-user-edit input-icon"></i>
                <input v-model="userProfile.nickname" type="text" class="mus-input-field" :placeholder="t('profile.nicknamePlaceholder') || 'Mote'">
              </div>
            </div>
            
            <div class="input-group">
              <label class="input-label">{{ t('profile.phone') || 'Teléfono' }}</label>
              <div class="input-wrapper">
                <i class="pi pi-phone input-icon"></i>
                <input v-model="userProfile.phone" type="tel" class="mus-input-field" :placeholder="t('profile.phonePlaceholder') || 'Móvil'">
              </div>
            </div>
            <div class="input-group">
              <label class="input-label">{{ t('auth.email') }}</label>
              <div class="input-wrapper opacity-75 cursor-not-allowed">
                <i class="pi pi-envelope input-icon"></i>
                <input v-model="userProfile.email" type="email" disabled class="mus-input-field" title="El correo electrónico no se puede cambiar">
              </div>
            </div>
            <div class="md:col-span-2 pt-6">
              <div class="bg-[var(--bg-app)]/50 p-4 rounded-2xl border border-[var(--border)] flex items-center gap-4">
                <i class="pi pi-info-circle text-[var(--secondary)] text-lg"></i>
                <span class="text-[11px] text-[var(--text-muted)] font-medium leading-relaxed uppercase tracking-wider">{{ t('profile.emailHelp') || 'El correo electrónico se usa para iniciar sesión y no se puede modificar.' }}</span>
              </div>
            </div>
          </div>
        </div>

        <div class="divider"></div>

        <div class="section-block">
          <h2 class="section-title"><i class="pi pi-lock mr-2"></i>{{ t('profile.security') || 'Seguridad' }}</h2>
          <p class="text-xs text-[var(--text-muted)] mb-6">{{ t('profile.passwordHelp') || 'Deja los campos en blanco si no deseas cambiar la contraseña.' }}</p>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="input-group">
              <label class="input-label">{{ t('profile.newPassword') || 'Nueva Contraseña' }}</label>
              <div class="input-wrapper">
                <i class="pi pi-key input-icon"></i>
                <input v-model="passwords.newPassword" type="password" class="mus-input-field" placeholder="••••••••">
              </div>
            </div>
            
            <div class="input-group">
              <label class="input-label">{{ t('auth.confirm') }}</label>
              <div class="input-wrapper">
                <i class="pi pi-shield input-icon"></i>
                <input v-model="passwords.confirmPassword" type="password" class="mus-input-field" placeholder="••••••••">
              </div>
            </div>
          </div>
        </div>

        <div class="pt-6 flex justify-end">
          <button type="submit" :disabled="saving" class="mus-button-primary group">
            <span class="font-black uppercase tracking-widest text-sm">
              <i v-if="saving" class="pi pi-spin pi-spinner mr-2"></i>
              {{ t('common.save') }}
            </span>
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<style scoped>
.view-container {
  /* Inherits from style.css */
}

.section-block {
  display: flex;
  flex-direction: column;
}

.section-title {
  font-size: 14px;
  font-weight: 900;
  color: var(--secondary);
  text-transform: uppercase;
  letter-spacing: 0.15em;
  margin-bottom: 24px;
  display: flex;
  align-items: center;
}

.divider {
  height: 1px;
  background: var(--border);
  margin: 32px 0;
}

.input-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.input-label {
  font-size: 10px;
  font-weight: 900;
  text-transform: uppercase;
  letter-spacing: 0.25em;
  color: var(--text-muted);
  margin-left: 12px;
}

.input-wrapper {
  position: relative;
}

.input-icon {
  position: absolute;
  left: 20px;
  top: 50%;
  transform: translateY(-50%);
  color: var(--text-muted);
  font-size: 14px;
  transition: color 0.3s;
}

.mus-input-field:focus + .input-icon {
  color: var(--secondary);
}

.mus-input-field {
  width: 100%;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 20px;
  padding: 14px 16px 14px 48px;
  color: var(--text-main);
  font-size: 13px;
  font-weight: 600;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.mus-input-field:focus {
  outline: none;
  border-color: var(--secondary);
  background: var(--surface-hover);
  box-shadow: 0 0 0 4px rgba(233, 195, 73, 0.1);
}

.mus-input-field:disabled {
  background: var(--bg-app);
  color: var(--text-muted);
  border-color: var(--border);
  opacity: 0.6;
}
</style>
