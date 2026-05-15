<script setup>
import { ref, onMounted } from 'vue'
import { tournamentService } from '../services/api'
import { useI18n } from 'vue-i18n'

const props = defineProps({
  tournamentUuid: {
    type: String,
    required: true
  }
})

const emit = defineEmits(['success', 'cancel'])
const { t } = useI18n()

const nameInput = ref(null)
const form = ref({
  name: '',
  isConfirmed: false
})

const saving = ref(false)
const error = ref(null)

const focusInput = () => {
  if (nameInput.value) {
    nameInput.value.focus()
  }
}

onMounted(() => {
  setTimeout(() => {
    focusInput()
  }, 400) // Un poco más de tiempo para que PrimeVue termine su lógica
})

defineExpose({ focusInput })

const handleSubmit = async () => {
  if (!form.value.name.trim()) {
    error.value = t('tournament_admin.enrollment.error_name_required')
    return
  }

  saving.value = true
  error.value = null
  try {
    const response = await tournamentService.enrollTeam(props.tournamentUuid, {
      name: form.value.name,
      isConfirmed: form.value.isConfirmed
    })
    emit('success', response.team)
    form.value.name = ''
    form.value.isConfirmed = false
  } catch (e) {
    error.value = e.message
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <div class="tournament-enroll-form">
    <form @submit.prevent="handleSubmit" class="mus-form">
      <div v-if="error" class="error-msg mb-4">{{ error }}</div>

      <div class="form-group">
        <label class="mus-label">{{ t('tournament_admin.enrollment.team_name_label') }} <span class="required">*</span></label>
        <input ref="nameInput"
               v-model="form.name" 
               type="text" 
               maxlength="255"
               required 
               autofocus
               :placeholder="t('tournament_admin.enrollment.team_name_placeholder')" 
               class="mus-input">
      </div>

      <div class="form-group mt-4">
        <div class="confirm-wrapper" 
             :class="{ 'is-confirmed': form.isConfirmed }"
             @click="form.isConfirmed = !form.isConfirmed">
          <Checkbox v-model="form.isConfirmed" :binary="true" />
          <div class="flex flex-column">
            <span class="text-xs font-black uppercase tracking-wider" :class="form.isConfirmed ? 'text-secondary' : 'text-white'">
              {{ t('tournament_admin.enrollment.is_confirmed_label') }}
            </span>
            <span class="text-[9px] font-bold uppercase mt-1" :class="form.isConfirmed ? 'text-secondary/70' : 'text-slate-500'">
              {{ form.isConfirmed ? t('tournament_admin.enrollment.is_confirmed_active') : t('tournament_admin.enrollment.is_confirmed_help') }}
            </span>
          </div>
          <i v-if="form.isConfirmed" class="pi pi-check-circle ml-auto text-secondary text-xl"></i>
        </div>
      </div>

      <div class="flex gap-4 mt-8">
        <button type="button" @click="emit('cancel')" class="mus-btn-secondary flex-1">
          {{ t('tournament_form.actions.cancel') }}
        </button>
        <button type="submit" :disabled="saving" class="mus-btn-primary flex-1">
          <i v-if="saving" class="pi pi-spin pi-spinner mr-2"></i>
          <i v-else class="pi pi-plus mr-2"></i>
          {{ saving ? t('tournament_admin.enrollment.submitting_btn') : t('tournament_admin.enrollment.submit_btn') }}
        </button>
      </div>
    </form>
  </div>
</template>

<style scoped>
.mus-form { display: flex; flex-direction: column; gap: 20px; }
.form-group { display: flex; flex-direction: column; gap: 10px; }
.mus-label { font-size: 10px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.2em; color: #64748b; margin-left: 4px; }
.required { color: var(--secondary); }
.mus-input { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 16px; padding: 18px 24px; color: white; font-size: 15px; font-weight: 600; outline: none; transition: all 0.3s ease; width: 100%; }
.mus-input:focus { border-color: var(--secondary); background: rgba(255,193,7,0.02); box-shadow: 0 0 20px rgba(255,193,7,0.1); }

.error-msg { background: rgba(244, 63, 94, 0.1); border: 1px solid rgba(244, 63, 94, 0.2); color: #fb7185; padding: 12px; border-radius: 12px; font-size: 11px; font-weight: 800; text-align: center; text-transform: uppercase; }

.mus-btn-primary { background: var(--secondary); border: none; border-radius: 9999px; padding: 18px; color: #000 !important; font-size: 11px; font-weight: 950; text-transform: uppercase; letter-spacing: 0.15em; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.3s ease; }
.mus-btn-primary:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(255,193,7,0.3); }
.mus-btn-primary:disabled { opacity: 0.5; cursor: not-allowed; }

.mus-btn-secondary { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: #94a3b8; border-radius: 9999px; padding: 18px; font-size: 11px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.15em; cursor: pointer; transition: all 0.3s ease; }
.mus-btn-secondary:hover { background: rgba(255,255,255,0.1); color: white; }

.confirm-wrapper {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 20px;
  background: rgba(255,255,255,0.03);
  border: 1px solid rgba(255,255,255,0.08);
  border-radius: 20px;
  cursor: pointer;
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.confirm-wrapper:hover {
  background: rgba(255,255,255,0.06);
  border-color: rgba(255,255,255,0.15);
}

.confirm-wrapper.is-confirmed {
  background: rgba(255, 193, 7, 0.08);
  border-color: rgba(255, 193, 7, 0.4);
  box-shadow: 0 10px 30px rgba(255, 193, 7, 0.1);
}
</style>
