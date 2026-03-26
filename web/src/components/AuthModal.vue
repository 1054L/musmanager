<script setup>
import AuthForm from './AuthForm.vue'

const props = defineProps({
  isOpen: Boolean,
  initialMode: {
    type: String,
    default: 'login' // 'login' or 'register'
  }
})

const emit = defineEmits(['close', 'success'])

const handleSuccess = () => {
  emit('success')
  emit('close')
}

// Handle ESC key
const handleKeydown = (e) => {
  if (e.key === 'Escape' && props.isOpen) {
    emit('close')
  }
}

import { onMounted, onUnmounted } from 'vue'
onMounted(() => window.addEventListener('keydown', handleKeydown))
onUnmounted(() => window.removeEventListener('keydown', handleKeydown))
</script>

<template>
  <Transition name="modal-fade">
    <div v-if="isOpen" class="modal-overlay" @click.self="emit('close')">
      
      <div class="modal-content mus-glass animate-in fade-in zoom-in duration-300">
        <!-- Close Button -->
        <button class="close-btn" @click="emit('close')" aria-label="Close">
          <i class="pi pi-times"></i>
        </button>

        <!-- Decorative Glow -->
        <div class="glow-element"></div>

        <div class="modal-inner">
          <AuthForm 
            :initialMode="initialMode" 
            :redirect="null" 
            @success="handleSuccess" 
          />
        </div>
      </div>
    </div>
  </Transition>
</template>

<style scoped>
.modal-overlay {
  position: fixed;
  inset: 0;
  z-index: 10000;
  background: rgba(0, 0, 0, 0.85);
  backdrop-filter: blur(8px);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px;
}

.modal-content {
  position: relative;
  width: 100%;
  max-width: 480px;
  background: rgba(15, 179, 97, 0.03);
  border-radius: 40px;
  overflow: hidden;
  box-shadow: 0 50px 100px rgba(0, 0, 0, 0.5);
}

.close-btn {
  position: absolute;
  top: 24px;
  right: 24px;
  width: 40px;
  height: 40px;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 50%;
  color: #64748b;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  z-index: 20;
  transition: all 0.3s;
}

.close-btn:hover {
  background: rgba(255, 255, 255, 0.1);
  color: white;
  transform: rotate(90deg);
}

.glow-element {
  position: absolute;
  top: -100px;
  right: -100px;
  width: 250px;
  height: 250px;
  background: #0fb361;
  opacity: 0.1;
  filter: blur(80px);
  border-radius: 50%;
  pointer-events: none;
}

.modal-inner {
  padding: 60px 48px;
  position: relative;
  z-index: 10;
}

.modal-header {
  text-align: center;
  margin-bottom: 48px;
}

.logo-circle {
  width: 64px;
  height: 64px;
  background: rgba(15, 179, 97, 0.1);
  border: 1px solid rgba(15, 179, 97, 0.2);
  border-radius: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 24px;
  color: #0fb361;
  font-weight: 900;
  font-size: 24px;
}

.modal-title {
  font-size: 32px;
  font-weight: 950;
  text-transform: uppercase;
  font-style: italic;
  letter-spacing: -0.05em;
  margin-bottom: 8px;
}

.modal-subtitle {
  color: #64748b;
  font-size: 14px;
  font-weight: 500;
}

.auth-form {
  display: flex;
  flex-direction: column;
  gap: 32px;
}

.error-box {
  background: rgba(244, 63, 94, 0.1);
  border: 1px solid rgba(244, 63, 94, 0.2);
  color: #fb7185;
  padding: 16px;
  border-radius: 16px;
  font-size: 12px;
  font-weight: 600;
  text-align: center;
}

.input-group {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.input-label {
  font-size: 10px;
  font-weight: 900;
  text-transform: uppercase;
  letter-spacing: 0.2em;
  color: #475569;
  margin-left: 4px;
}

.mus-input {
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 20px;
  padding: 16px 24px;
  color: white;
  font-size: 14px;
  font-weight: 500;
  outline: none;
  transition: all 0.3s;
}

.mus-input:focus {
  border-color: rgba(15, 179, 97, 0.5);
  background: rgba(255, 255, 255, 0.05);
}

.modal-footer {
  margin-top: 40px;
  padding-top: 40px;
  border-top: 1px solid rgba(255, 255, 255, 0.05);
  text-align: center;
}

.footer-text {
  font-size: 10px;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  color: #64748b;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.text-link {
  background: none;
  border: none;
  color: #0fb361;
  font-weight: 900;
  text-transform: uppercase;
  border-bottom: 2px solid rgba(15, 179, 97, 0.3);
  padding: 0;
  cursor: pointer;
  width: fit-content;
  align-self: center;
  transition: all 0.3s;
}

.text-link:hover {
  color: white;
  border-color: white;
}

.spinner {
  width: 14px;
  height: 14px;
  border: 2px solid rgba(0, 0, 0, 0.2);
  border-top-color: #000;
  border-radius: 50%;
  animation: rotate 0.8s linear infinite;
  display: inline-block;
  margin-right: 12px;
}

@keyframes rotate {
  to { transform: rotate(360deg); }
}

/* Transitions */
.modal-fade-enter-active,
.modal-fade-leave-active {
  transition: opacity 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.modal-fade-enter-from,
.modal-fade-leave-to {
  opacity: 0;
}

.modal-fade-enter-active .modal-content {
  animation: modal-in 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
}

@keyframes modal-in {
  from { transform: scale(0.9) translateY(20px); opacity: 0; }
  to { transform: scale(1) translateY(0); opacity: 1; }
}
</style>
