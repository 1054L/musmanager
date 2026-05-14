<script setup>
import { ref, provide } from 'vue'
import MusNavbar from './MusNavbar.vue'
import MusSidebar from './MusSidebar.vue'
import MusFooter from '../components/MusFooter.vue'
import AuthModal from '../components/AuthModal.vue'
import { authService } from '../services/api'
import CookieBanner from '../components/CookieBanner.vue'
import TermsModal from '../components/TermsModal.vue'
import ConfirmDialog from 'primevue/confirmdialog'
import Toast from 'primevue/toast'

import { useThemeStore } from '../stores/themeStore'

// Global Auth Modal State
const showAuthModal = ref(false)
const authModalMode = ref('login')

const themeStore = useThemeStore()
const user = ref(authService.getUser())
const isSidebarOpen = ref(true)

// Terms Modal Logic
const showTermsModal = ref(user.value && !user.value.termsAccepted)

const onTermsAccepted = () => {
  showTermsModal.value = false
  // Update local user reference
  user.value = authService.getUser()
}

const openAuthModal = (mode = 'login') => {
  authModalMode.value = mode
  showAuthModal.value = true
}

// Provide to all children (Navbar, HomeView, etc)
provide('openAuthModal', openAuthModal)
provide('isSidebarOpen', isSidebarOpen)
provide('user', user)

const handleLogout = () => {
  authService.logout()
  user.value = null
  window.location.href = '/' // Force reload to clear all states
}
provide('logout', handleLogout)
</script>

<template>
  <div class="mus-layout">
    <Toast />
    <ConfirmDialog />
    <CookieBanner />
    
    <!-- Fondo y Efectos Atmosféricos (Solo en Modo Oscuro) -->
    <div v-if="themeStore.isDark" class="bg-elements">
      <div class="bg-gradient"></div>
      <div class="bg-noise"></div>
      <div class="bg-vignette"></div>
      <div class="bg-grid"></div>
      <div class="bg-glow"></div>
      <div class="bg-glow-2"></div>
    </div>

    <!-- Header / Navbar -->
    <MusNavbar />

    <!-- Sidebar (Logged In Only) -->
    <MusSidebar v-if="user" :isOpen="isSidebarOpen" />

    <!-- Main Content -->
    <main class="mus-main" :class="{ 'with-sidebar': user, 'sidebar-collapsed': !isSidebarOpen }">
      <div class="mus-content-wrapper">
        <slot />
      </div>
    </main>

    <!-- Footer -->
    <MusFooter />

    <!-- Global Auth Modal -->
    <AuthModal 
      :isOpen="showAuthModal" 
      :initialMode="authModalMode" 
      redirect="/dashboard"
      @close="showAuthModal = false"
    />

    <!-- Global Terms Modal -->
    <TermsModal 
      :isOpen="showTermsModal"
      @accepted="onTermsAccepted"
    />
  </div>
</template>

<style scoped>
.mus-layout {
  min-height: 100vh;
  background: var(--bg-app);
  color: var(--text-main);
  font-family: var(--font-main);
  display: flex;
  flex-direction: column;
  transition: background-color 0.3s, color 0.3s;
}

.bg-elements {
  position: fixed;
  inset: 0;
  z-index: 0;
  pointer-events: none;
}

.bg-gradient {
  position: absolute;
  inset: 0;
  background: radial-gradient(circle at 50% 50%, var(--bg-app) 0%, var(--bg-gradient-end) 100%);
  opacity: var(--bg-elements-opacity, 1);
}

.bg-noise {
  position: absolute;
  inset: 0;
  background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noiseFilter'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.65' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noiseFilter)'/%3E%3C/svg%3E");
  opacity: calc(var(--bg-elements-opacity, 1) * 0.5);
  mix-blend-mode: overlay;
}

.bg-vignette {
  position: absolute;
  inset: 0;
  background: radial-gradient(circle at 50% 50%, transparent 20%, var(--bg-app) 100%);
  opacity: calc(var(--bg-elements-opacity, 1) * 0.4);
}

.bg-grid {
  position: absolute;
  inset: 0;
  background-image: url('/grid.svg');
  background-size: 60px 60px;
  opacity: calc(var(--bg-elements-opacity, 1) * 0.02);
}

.bg-glow {
  position: absolute;
  top: -10%;
  right: -10%;
  width: 50%;
  height: 50%;
  background: radial-gradient(circle, var(--primary) 0%, transparent 70%);
  filter: blur(140px);
  opacity: calc(var(--bg-elements-opacity, 1) * 0.05);
}

.bg-glow-2 {
  position: absolute;
  bottom: -15%;
  left: -10%;
  width: 60%;
  height: 60%;
  background: radial-gradient(circle, var(--primary) 0%, transparent 70%);
  filter: blur(160px);
  opacity: calc(var(--bg-elements-opacity, 1) * 0.03);
}

.mus-main {
  position: relative;
  z-index: 10;
  flex: 1 0 auto;
  padding-top: 140px;
  padding-bottom: 80px;
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.mus-main.with-sidebar {
  padding-left: 280px;
}

.mus-main.with-sidebar.sidebar-collapsed {
  padding-left: 80px;
}

@media (max-width: 767px) {
  .mus-main.with-sidebar {
    padding-left: 0;
  }
}

.mus-content-wrapper {
  max-width: 1440px;
  margin: 0 auto;
  padding: 0 12px;
}

@media (min-width: 768px) {
  .mus-content-wrapper {
    padding: 0 32px;
  }
}

</style>
