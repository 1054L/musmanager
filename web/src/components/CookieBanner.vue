<script setup>
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
const isVisible = ref(false)

const acceptCookies = () => {
  localStorage.setItem('mus_cookies_accepted', 'true')
  isVisible.value = false
}

onMounted(() => {
  const accepted = localStorage.getItem('mus_cookies_accepted')
  if (!accepted) {
    // Show after a short delay for better UX
    setTimeout(() => {
      isVisible.value = true
    }, 1000)
  }
})
</script>

<template>
  <Transition name="slide-up">
    <div v-if="isVisible" class="cookie-banner-wrapper">
      <div class="cookie-banner mus-glass-dark">
        <div class="cookie-content">
          <div class="cookie-icon">
            <i class="pi pi-shield text-2xl text-[#0fb361]"></i>
          </div>
          <div class="cookie-text">
            <h4 class="text-white font-black uppercase text-[10px] tracking-widest mb-1">
              {{ t('cookie_banner.title') }}
            </h4>
            <p class="text-slate-400 text-xs leading-relaxed">
              {{ t('cookie_banner.desc') }}
              <router-link to="/cookies" class="text-[#0fb361] hover:underline ml-1">{{ t('footer.cookies') }}</router-link> y
              <router-link to="/terminos" class="text-[#0fb361] hover:underline ml-1">{{ t('footer.terms') }}</router-link>.
            </p>
          </div>
        </div>
        <div class="cookie-actions">
          <button @click="acceptCookies" class="mus-btn-accept">
            {{ t('cookie_banner.accept') }}
          </button>
          <router-link to="/cookies" class="mus-btn-settings" @click="isVisible = false">
            {{ t('cookie_banner.settings') }}
          </router-link>
        </div>
      </div>
    </div>
  </Transition>
</template>

<style scoped>
.cookie-banner-wrapper {
  position: fixed;
  bottom: 24px;
  left: 50%;
  transform: translateX(-50%);
  width: 95%;
  max-width: 600px;
  z-index: 9999;
}

.cookie-banner {
  padding: 24px;
  border-radius: 24px;
  display: flex;
  flex-direction: column;
  gap: 20px;
  border: 1px solid rgba(15, 179, 97, 0.2);
  box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5);
}

@media (min-width: 768px) {
  .cookie-banner {
    flex-direction: row;
    align-items: center;
    justify-content: space-between;
    padding: 24px 32px;
  }
}

.cookie-content {
  display: flex;
  gap: 20px;
  align-items: flex-start;
}

.cookie-text {
  flex: 1;
}

.cookie-actions {
  display: flex;
  gap: 12px;
  width: 100%;
}

@media (min-width: 768px) {
  .cookie-actions {
    width: auto;
  }
}

.mus-btn-accept {
  flex: 1;
  background: #0fb361;
  color: #050505;
  border: none;
  padding: 12px 24px;
  border-radius: 99px;
  font-size: 10px;
  font-weight: 900;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  cursor: pointer;
  transition: all 0.3s;
}

.mus-btn-accept:hover {
  background: #12d674;
  transform: translateY(-2px);
  box-shadow: 0 10px 20px rgba(15, 179, 97, 0.3);
}

.mus-btn-settings {
  flex: 1;
  background: rgba(255, 255, 255, 0.05);
  color: white;
  border: 1px solid rgba(255, 255, 255, 0.1);
  padding: 12px 24px;
  border-radius: 99px;
  font-size: 10px;
  font-weight: 900;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  text-decoration: none;
  text-align: center;
  transition: all 0.3s;
}

.mus-btn-settings:hover {
  background: rgba(255, 255, 255, 0.1);
  border-color: rgba(255, 255, 255, 0.2);
}

/* Transitions */
.slide-up-enter-active, .slide-up-leave-active {
  transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1);
}

.slide-up-enter-from, .slide-up-leave-to {
  opacity: 0;
  transform: translate(-50%, 40px);
}
</style>
