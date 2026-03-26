<script setup>
import { authService } from '../services/api'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { ref, inject } from 'vue'

const { t, locale } = useI18n()
const router = useRouter()
const user = ref(authService.getUser())
const showLangMenu = ref(false)

// Inject Global Auth Modal Trigger
const openAuthModal = inject('openAuthModal')

const languages = [
  { code: 'es', name: 'Español' },
  { code: 'en', name: 'English' },
  { code: 'eu', name: 'Euskara' }
]

const changeLanguage = (code) => {
  locale.value = code
  localStorage.setItem('locale', code)
  showLangMenu.value = false
}

const handleLogout = () => {
  authService.logout()
  user.value = null
  router.push('/')
}
</script>

<template>
  <header class="navbar-wrapper">
    <div class="navbar-container">
      
      <!-- Logo -->
      <router-link to="/" class="logo-area">
        <div class="logo-box">
          <span class="logo-letter mus-gold-text">M</span>
        </div>
        <div class="logo-text">
          <span class="logo-title mus-gold-text">Mus Manager</span>
          <!--<span class="logo-subtitle">Elite Suite</span>-->
        </div>
      </router-link>

      <!-- Nav Links -->
      <nav class="nav-links">
        <router-link to="/features" class="nav-item">{{ t('nav.features') }}</router-link>
        <router-link to="/tournaments" class="nav-item">{{ t('nav.tournaments') }}</router-link>
      </nav>

      <!-- Right Side -->
      <div class="nav-actions">
        <!-- Language -->
        <div class="lang-selector">
          <button @click="showLangMenu = !showLangMenu" class="lang-btn">
            {{ locale.toUpperCase() }}
          </button>
          <div v-if="showLangMenu" class="lang-dropdown">
            <button v-for="lang in languages" :key="lang.code" @click="changeLanguage(lang.code)" class="dropdown-item">
              {{ lang.name }}
            </button>
          </div>
        </div>

        <!-- Auth -->
        <template v-if="!user">
          <button @click="openAuthModal('login')" class="nav-link-subtle-btn">
            {{ t('nav.login') }}
          </button>
          <button @click="openAuthModal('register')" class="mus-button-primary scale-90">
            {{ t('nav.getStarted') }}
          </button>
        </template>
        <template v-else>
           <router-link to="/dashboard" class="mus-button-primary scale-90">
            {{ t('nav.dashboard') }}
          </router-link>
          <button @click="handleLogout" class="logout-btn">
            <i class="pi pi-power-off"></i>
          </button>
        </template>
      </div>

    </div>
  </header>
</template>

<style scoped>
.navbar-wrapper {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  padding: 24px;
  z-index: 1000;
  display: flex;
  justify-content: center;
}

.navbar-container {
  width: 100%;
  max-width: 1280px;
  background: rgba(15, 179, 97, 0.05);
  backdrop-filter: blur(30px);
  padding: 12px 32px;
  border-radius: 999px;
  border: 1px solid rgba(255, 255, 255, 0.05);
  display: flex;
  align-items: center;
  justify-content: space-between;
  box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
}

.logo-area {
  display: flex;
  align-items: center;
  gap: 12px;
  text-decoration: none;
}

.logo-box {
  width: 36px;
  height: 36px;
  background: #0fb361;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #050505;
  font-weight: 950;
  font-size: 18px;
  box-shadow: 0 0 15px rgba(15, 179, 97, 0.3);
  overflow: hidden;
}

.logo-letter {
  font-size: 22px;
  font-weight: 950;
  line-height: 1;
}

.logo-text {
  display: flex;
  flex-direction: column;
}

.logo-title {
  color: white;
  font-weight: 900;
  font-size: 18px;
  text-transform: uppercase;
  font-style: italic;
  letter-spacing: -0.05em;
  line-height: 1;
  padding: 3px;
}

.logo-subtitle {
  color: #0fb361;
  font-size: 7px;
  font-weight: 900;
  text-transform: uppercase;
  letter-spacing: 0.4em;
  opacity: 0.8;
  margin-top: 2px;
}

.nav-links {
  display: none;
  gap: 40px;
}

@media (min-width: 1024px) {
  .nav-links {
    display: flex;
  }
}

.nav-item {
  color: #94a3b8;
  font-size: 9px;
  font-weight: 900;
  text-transform: uppercase;
  letter-spacing: 0.3em;
  text-decoration: none;
  transition: color 0.3s;
}

.nav-item:hover {
  color: white;
}

.nav-actions {
  display: flex;
  align-items: center;
  gap: 24px;
}

.lang-selector {
  position: relative;
  display: none;
}

@media (min-width: 640px) {
  .lang-selector {
    display: block;
  }
}

.lang-btn {
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.05);
  color: #94a3b8;
  padding: 6px 16px;
  border-radius: 99px;
  font-size: 8px;
  font-weight: 900;
  cursor: pointer;
  transition: all 0.3s;
}

.lang-btn:hover {
  color: white;
  background: rgba(255, 255, 255, 0.08);
}

.lang-dropdown {
  position: absolute;
  top: 40px;
  right: 0;
  background: rgba(10, 10, 10, 0.95);
  backdrop-filter: blur(20px);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 12px;
  padding: 8px;
  min-width: 120px;
}

.dropdown-item {
  width: 100%;
  padding: 8px 16px;
  background: transparent;
  border: none;
  color: #94a3b8;
  text-align: left;
  font-size: 9px;
  font-weight: 900;
  text-transform: uppercase;
  border-radius: 8px;
  cursor: pointer;
}

.dropdown-item:hover {
  background: rgba(15, 179, 97, 0.1);
  color: #0fb361;
}

.nav-link-subtle-btn {
  background: none;
  border: none;
  color: #94a3b8;
  font-size: 9px;
  font-weight: 900;
  text-transform: uppercase;
  letter-spacing: 0.2em;
  cursor: pointer;
  transition: color 0.3s;
  padding: 0;
}

.nav-link-subtle-btn:hover {
  color: white;
}

.logout-btn {
  width: 32px;
  height: 32px;
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.05);
  border-radius: 99px;
  color: #94a3b8;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.3s;
}

.logout-btn:hover {
  color: #fb7185;
  background: rgba(251, 113, 133, 0.1);
}

.scale-90 {
  transform: scale(0.9);
}
</style>
