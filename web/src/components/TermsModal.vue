<script setup>
import { ref, computed } from 'vue'
import Dialog from 'primevue/dialog'
import Checkbox from 'primevue/checkbox'
import { authService } from '../services/api'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
const props = defineProps({
  isOpen: {
    type: Boolean,
    required: true
  }
})

const emit = defineEmits(['accepted'])

const accepted = ref(false)
const loading = ref(false)
const error = ref(null)

const handleAccept = async () => {
  if (!accepted.value) return
  
  loading.value = true
  error.value = null
  
  try {
    await authService.acceptTerms()
    emit('accepted')
  } catch (err) {
    console.error('Error accepting terms:', err)
    error.value = t('terms.error_accepting') || 'Error al aceptar los términos. Inténtalo de nuevo.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <Dialog 
    :visible="isOpen" 
    modal 
    :closable="false"
    :draggable="false"
    class="mus-terms-dialog max-w-2xl w-full"
    :pt="{
        root: { class: 'mus-glass border-secondary/30 overflow-hidden' },
        header: { class: 'hidden' },
        content: { class: 'p-0' }
    }"
  >
    <div class="modal-master-header bg-secondary p-6">
      <h2 class="text-black font-black italic uppercase tracking-tighter text-2xl leading-none">
        {{ t('terms.title') }}
      </h2>
    </div>

    <div class="terms-content p-8">
      <div class="terms-text-container mus-glass p-6 rounded-xl mb-6 max-h-[400px] overflow-y-auto custom-scrollbar">
        <h4 class="text-secondary font-black italic uppercase tracking-tighter mb-4">{{ t('terms.welcome') }}</h4>
        <p class="text-slate-300 text-sm leading-relaxed mb-4">
          {{ t('terms.intro') }}
        </p>
        
        <h5 class="text-white font-bold text-xs uppercase tracking-widest mb-2">{{ t('terms.section1_title') }}</h5>
        <p class="text-slate-400 text-xs leading-relaxed mb-4">
          {{ t('terms.section1_text') }}
        </p>

        <h5 class="text-white font-bold text-xs uppercase tracking-widest mb-2">{{ t('terms.section2_title') }}</h5>
        <p class="text-slate-400 text-xs leading-relaxed mb-4">
          {{ t('terms.section2_text') }}
        </p>

        
        <p class="text-slate-500 text-[10px] mt-8 italic border-t border-white/5 pt-4">
          {{ t('terms.last_updated') }}
        </p>
      </div>

      <div class="flex items-start gap-3 mb-4 px-2">
        <Checkbox v-model="accepted" :binary="true" inputId="terms-checkbox" class="mt-1" />
        <label for="terms-checkbox" class="text-sm text-slate-300 cursor-pointer select-none">
          {{ t('terms.checkbox_label') }}
        </label>
      </div>

      <div class="flex gap-4 px-2 mb-8 text-[10px] uppercase tracking-widest font-bold">
        <a href="/terminos" target="_blank" class="text-secondary hover:text-white transition-colors flex items-center gap-1">
          <i class="pi pi-external-link"></i> {{ t('legal.terms_title') }}
        </a>
        <a href="/privacidad" target="_blank" class="text-secondary hover:text-white transition-colors flex items-center gap-1">
          <i class="pi pi-external-link"></i> {{ t('legal.privacy_title') }}
        </a>
      </div>

      <div v-if="error" class="text-red-400 text-xs mb-4 px-2">
        <i class="pi pi-exclamation-circle mr-1"></i> {{ error }}
      </div>

      <div class="flex justify-end gap-4">
        <button 
          @click="handleAccept" 
          class="mus-btn-primary w-full py-4 relative group overflow-hidden"
          :disabled="!accepted || loading"
          :class="{ 'opacity-50 grayscale cursor-not-allowed': !accepted || loading }"
        >
          <span v-if="loading" class="flex items-center justify-center gap-2">
            <i class="pi pi-spin pi-spinner"></i> {{ t('common.loading').toUpperCase() }}
          </span>
          <span v-else>{{ t('terms.accept_btn') }}</span>
        </button>
      </div>
    </div>
  </Dialog>
</template>

<style scoped>
.terms-text-container {
  background: rgba(0, 0, 0, 0.3);
  border: 1px solid rgba(255, 255, 255, 0.05);
}

.custom-scrollbar::-webkit-scrollbar {
  width: 6px;
}

.custom-scrollbar::-webkit-scrollbar-track {
  background: rgba(255, 255, 255, 0.02);
  border-radius: 10px;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
  background: var(--secondary);
  border-radius: 10px;
  opacity: 0.5;
}

:deep(.mus-glass-content) {
  background: var(--surface);
  padding: 0;
}
</style>
