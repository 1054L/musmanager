<script setup>
import { ref, onMounted, watch } from 'vue'
import { teamService, tournamentService } from '../services/api'
import { useI18n } from 'vue-i18n'
import MusLoader from './MusLoader.vue'

const props = defineProps({
  tournamentId: {
    type: Number,
    default: null
  },
  standalone: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['success', 'cancel'])

const { t } = useI18n()

const form = ref({
  name: '',
  tournamentId: props.tournamentId
})

const tournaments = ref([])
const loading = ref(false)
const saving = ref(false)
const error = ref(null)
const success = ref(false)

// Keep form in sync with prop if it changes
watch(() => props.tournamentId, (newVal) => {
  form.value.tournamentId = newVal
})

onMounted(async () => {
  if (props.standalone || !props.tournamentId) {
    loading.value = true
    try {
      tournaments.value = await tournamentService.getManagedTournaments()
      if (tournaments.value.length > 0 && !form.value.tournamentId) {
        form.value.tournamentId = tournaments.value[0].id
      }
    } catch (e) {
      error.value = t('team_form.error_loading_tournaments')
    } finally {
      loading.value = false
    }
  }
})

const handleCreate = async () => {
  if (!form.value.tournamentId) {
    error.value = t('team_form.error_no_tournament')
    return
  }

  saving.value = true
  error.value = null
  success.value = false
  try {
    await teamService.createTeam({
      name: form.value.name,
      tournamentId: form.value.tournamentId
    })
    
    // Clear name but KEEP tournamentId for "sticky" behavior
    const lastTnyId = form.value.tournamentId
    form.value.name = ''
    success.value = true
    emit('success', lastTnyId)
    
    setTimeout(() => { success.value = false }, 3000)
    
  } catch (e) {
    error.value = e.message
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <div class="team-enroll-form">
    <MusLoader v-if="loading" />
    <MusLoader v-if="saving" overlay />
    
    <form v-if="!loading" @submit.prevent="handleCreate" class="mus-form">
      <div v-if="error" class="error-msg">{{ error }}</div>
      <div v-if="success" class="success-msg">{{ t('team_form.success_msg') }}</div>

      <!-- Select Tournament (only if standalone) -->
      <div class="form-group" v-if="standalone">
        <label class="mus-label">{{ t('team_form.tournament') }} <span class="required">*</span></label>
        <select v-model="form.tournamentId" required class="mus-input">
          <option v-for="tny in tournaments" :key="tny.id" :value="tny.id">
            {{ tny.name }}
          </option>
        </select>
      </div>

      <!-- Team Name -->
      <div class="form-group">
        <label class="mus-label">{{ t('team_form.name') }} <span class="required">*</span></label>
        <input v-model="form.name" 
               type="text" 
               required 
               ref="nameInput"
               :placeholder="t('team_form.name_placeholder')" 
               class="mus-input">
      </div>

      <div class="flex gap-4 mt-6">
        <button v-if="!standalone" type="button" @click="emit('cancel')" class="mus-btn-secondary flex-1">
          {{ t('tournament_form.actions.cancel') }}
        </button>
        <button type="submit" :disabled="saving" class="mus-btn-primary flex-1">
          <i v-if="!saving" class="pi pi-users mr-2"></i>
          {{ saving ? t('tournament_form.actions.saving') : t('team_form.submit') }}
        </button>
      </div>
    </form>
  </div>
</template>

<style scoped>
.team-enroll-form {
  position: relative;
  z-index: 1;
}

.mus-form { 
  display: flex; 
  flex-direction: column; 
  gap: 28px; 
}

.form-group { 
  display: flex; 
  flex-direction: column; 
  gap: 12px; 
}

.mus-label {
  font-size: 9px; 
  font-weight: 950; 
  text-transform: uppercase;
  letter-spacing: 0.3em; 
  color: #334155; 
  margin-left: 4px;
}

.required { 
  color: var(--primary); 
  opacity: 0.6;
}

.mus-input {
  background: #000000; 
  border: 1px solid rgba(255, 255, 255, 0.04);
  border-radius: 14px; 
  padding: 16px 20px; 
  color: white;
  font-size: 14px; 
  font-weight: 500; 
  outline: none; 
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  width: 100%; 
  box-sizing: border-box;
}

.mus-input:focus { 
  border-color: var(--primary); 
  background: rgba(15, 179, 97, 0.02);
  box-shadow: 0 0 25px var(--primary-glow);
}

.error-msg {
  background: rgba(244, 63, 94, 0.05); 
  border: 1px solid rgba(244, 63, 94, 0.1);
  color: #fb7185; 
  padding: 16px; 
  border-radius: 14px;
  font-size: 11px; 
  font-weight: 700; 
  text-align: center;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  opacity: 0.9;
}

.success-msg {
  background: rgba(15, 179, 97, 0.05); 
  border: 1px solid rgba(15, 179, 97, 0.1);
  color: var(--primary); 
  padding: 16px; 
  border-radius: 14px;
  font-size: 11px; 
  font-weight: 700; 
  text-align: center;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  opacity: 0.9;
}

.mus-btn-primary {
  background: var(--primary); 
  border: none; 
  border-radius: 9999px;
  padding: 18px; 
  color: #050505 !important; 
  font-size: 11px; 
  font-weight: 950;
  text-transform: uppercase; 
  letter-spacing: 0.2em; 
  cursor: pointer;
  display: flex; 
  align-items: center; 
  justify-content: center; 
  gap: 12px;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 10px 30px rgba(15, 179, 97, 0.2);
}

.mus-btn-primary:hover:not(:disabled) { 
  background: #12d674; 
  transform: translateY(-2px) scale(1.02); 
  box-shadow: 0 15px 40px rgba(15, 179, 97, 0.3); 
}

.mus-btn-primary:active:not(:disabled) {
  transform: translateY(0) scale(0.98);
}

.mus-btn-primary:disabled { 
  opacity: 0.3; 
  cursor: not-allowed;
  filter: grayscale(1);
}

.mus-btn-secondary {
  background: rgba(255, 255, 255, 0.02); 
  border: 1px solid rgba(255, 255, 255, 0.05);
  color: #64748b; 
  border-radius: 9999px; 
  padding: 18px;
  font-size: 10px; 
  font-weight: 900; 
  text-transform: uppercase;
  letter-spacing: 0.2em; 
  cursor: pointer; 
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.mus-btn-secondary:hover { 
  background: rgba(255, 255, 255, 0.05); 
  color: white;
  border-color: rgba(255, 255, 255, 0.1);
}
</style>
