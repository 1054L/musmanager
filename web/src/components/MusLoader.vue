<script setup>
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

defineProps({
  fullScreen: {
    type: Boolean,
    default: false
  },
  text: {
    type: String,
    default: null
  },
  overlay: {
    type: Boolean,
    default: false
  }
})
</script>

<template>
  <div :class="[
    'mus-loader-container',
    { 'full-screen': fullScreen },
    { 'overlay': overlay }
  ]">
    <div class="loader-content">
      <div class="logo-animation-wrapper">
        <div class="logo-box-animated">
          <span class="logo-letter">M</span>
        </div>
        <div class="pulse-ring"></div>
        <div class="pulse-ring-outer"></div>
      </div>
      <p v-if="text !== ''" class="loading-text">
        {{ text || t('common.loading') }}
      </p>
    </div>
  </div>
</template>

<style scoped>
.mus-loader-container {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem;
  width: 100%;
}

.mus-loader-container.full-screen {
  position: fixed;
  inset: 0;
  z-index: 9999;
  background: rgba(5, 5, 5, 0.9);
  backdrop-filter: blur(10px);
}

.mus-loader-container.overlay {
  position: absolute;
  inset: 0;
  z-index: 100;
  background: rgba(5, 5, 5, 0.7);
  backdrop-filter: blur(4px);
  border-radius: inherit;
}

.loader-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1.5rem;
}

.logo-animation-wrapper {
  position: relative;
  width: 60px;
  height: 60px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.logo-box-animated {
  width: 44px;
  height: 44px;
  background: #0fb361;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #050505;
  box-shadow: 0 0 30px rgba(15, 179, 97, 0.4);
  z-index: 10;
  animation: logo-float 2s ease-in-out infinite;
}

.logo-letter {
  font-size: 26px;
  font-weight: 950;
  font-style: italic;
  line-height: 1;
  transform: translateY(-1px);
}

.pulse-ring, .pulse-ring-outer {
  position: absolute;
  border: 2px solid #0fb361;
  border-radius: 16px;
  opacity: 0;
  z-index: 5;
}

.pulse-ring {
  inset: 4px;
  animation: pulse 2s cubic-bezier(0.24, 0, 0.38, 1) infinite;
}

.pulse-ring-outer {
  inset: 0;
  animation: pulse 2s cubic-bezier(0.24, 0, 0.38, 1) 0.5s infinite;
}

.loading-text {
  font-size: 10px;
  font-weight: 900;
  text-transform: uppercase;
  letter-spacing: 0.4em;
  color: #94a3b8;
  margin: 0;
  opacity: 0.8;
  animation: text-fade 1.5s ease-in-out infinite alternate;
}

@keyframes logo-float {
  0%, 100% { transform: translateY(0) scale(1); }
  50% { transform: translateY(-5px) scale(1.05); }
}

@keyframes pulse {
  0% { transform: scale(0.8); opacity: 0.5; }
  100% { transform: scale(1.8); opacity: 0; }
}

@keyframes text-fade {
  from { opacity: 0.4; transform: translateY(0); }
  to { opacity: 0.9; transform: translateY(2px); }
}
</style>
