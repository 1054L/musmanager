<script setup>
import { ref, onMounted } from 'vue'
import { teamService, tournamentService } from '../services/api'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
const router = useRouter()

const form = ref({
  name: '',
  tournamentId: null
})

const tournaments = ref([])
const loading = ref(false)
const saving = ref(false)
const error = ref(null)
const success = ref(false)

onMounted(async () => {
  loading.value = true
  try {
    tournaments.value = await tournamentService.getManagedTournaments()
    // By default select the first one if available
    if (tournaments.value.length > 0) {
      form.value.tournamentId = tournaments.value[0].id
    }
  } catch (e) {
    error.value = t('team_form.error_loading_tournaments')
  } finally {
    loading.value = false
  }
})

const handleCreate = async () => {
  saving.value = true
  error.value = null
  success.value = false
  try {
    await teamService.createTeam({
      name: form.value.name,
      tournamentId: form.value.tournamentId
    })
    
    // Clear name but KEEP tournamentId for "sticky" behavior
    form.value.name = ''
    success.value = true
    
    // Optional: Hide success message after 3 seconds
    setTimeout(() => { success.value = false }, 3000)
    
  } catch (e) {
    error.value = e.message
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <div class="form-page">
    <header class="form-header">
      <button @click="router.push('/dashboard')" class="back-link">
        <i class="pi pi-arrow-left"></i>
        {{ $t('tournament_form.back_dashboard') }}
      </button>
      <h1 class="mus-h1 italic mt-8">{{ $t('team_form.title') }}</h1>
    </header>

    <div class="form-card mus-glass">
      <div v-if="loading" class="loading-state">
        <span class="spinner"></span>
        <p>{{ $t('common.loading') }}</p>
      </div>

      <form v-else @submit.prevent="handleCreate" class="mus-form">
        <div v-if="error" class="error-msg">{{ error }}</div>
        <div v-if="success" class="success-msg">{{ $t('team_form.success_msg') }}</div>

        <!-- Seleccionar Torneo -->
        <div class="form-group">
          <label class="mus-label">{{ $t('team_form.tournament') }} <span class="required">*</span></label>
          <select v-model="form.tournamentId" required class="mus-input">
            <option v-for="t in tournaments" :key="t.id" :value="t.id">
              {{ t.name }} ({{ $t('tournament_card.status.' + t.status) }})
            </option>
          </select>
        </div>

        <!-- Nombre del Equipo -->
        <div class="form-group">
          <label class="mus-label">{{ $t('team_form.name') }} <span class="required">*</span></label>
          <input v-model="form.name" type="text" required :placeholder="$t('team_form.name_placeholder')" class="mus-input">
        </div>

        <button type="submit" :disabled="saving" class="mus-btn-primary w-full mt-4">
          <span v-if="saving" class="spinner mr-2"></span>
          {{ saving ? $t('tournament_form.actions.saving') : '👥 ' + $t('team_form.submit') }}
        </button>
      </form>
    </div>
  </div>
</template>

<style scoped>
.form-page { max-width: 500px; margin: 0 auto; padding: 40px 20px; }

.back-link {
  background: none; border: none; color: #64748b;
  font-size: 10px; font-weight: 800; text-transform: uppercase;
  letter-spacing: 0.1em; display: flex; align-items: center; gap: 10px;
  cursor: pointer; transition: color 0.3s;
}
.back-link:hover { color: white; }

.form-card { padding: 40px; border-radius: 24px; }
.mus-form { display: flex; flex-direction: column; gap: 24px; }

.form-group { display: flex; flex-direction: column; gap: 8px; }

.mus-label {
  font-size: 10px; font-weight: 900; text-transform: uppercase;
  letter-spacing: 0.2em; color: #475569; margin-left: 4px;
}
.required { color: #0fb361; }

.mus-input {
  background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08);
  border-radius: 12px; padding: 14px 20px; color: white;
  font-size: 14px; font-weight: 500; outline: none; transition: all 0.3s;
  width: 100%; box-sizing: border-box;
}
.mus-input:focus { border-color: rgba(15,179,97,0.5); background: rgba(255,255,255,0.05); }

.error-msg {
  background: rgba(244,63,94,0.1); border: 1px solid rgba(244,63,94,0.2);
  color: #fb7185; padding: 14px; border-radius: 12px;
  font-size: 12px; font-weight: 600; text-align: center;
}

.success-msg {
  background: rgba(15,179,97,0.1); border: 1px solid rgba(15,179,97,0.2);
  color: #0fb361; padding: 14px; border-radius: 12px;
  font-size: 12px; font-weight: 600; text-align: center;
}

.loading-state {
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  padding: 40px; gap: 16px; color: #64748b; font-size: 12px; font-weight: 800;
  text-transform: uppercase; letter-spacing: 0.1em;
}

.mus-btn-primary {
  background: #0fb361; border: none; border-radius: 16px;
  padding: 16px; color: black; font-size: 14px; font-weight: 900;
  text-transform: uppercase; letter-spacing: 0.1em; cursor: pointer;
  display: flex; align-items: center; justify-content: center; gap: 10px;
  transition: all 0.3s;
}
.mus-btn-primary:hover:not(:disabled) { background: #0ca358; transform: translateY(-2px); box-shadow: 0 10px 20px -5px rgba(15,179,97,0.4); }
.mus-btn-primary:disabled { opacity: 0.5; cursor: not-allowed; }

.spinner {
  width: 16px; height: 16px; border: 2px solid rgba(0,0,0,0.1);
  border-top-color: black; border-radius: 50%;
  animation: rotate 0.6s linear infinite;
}

@keyframes rotate { to { transform: rotate(360deg); } }
</style>
