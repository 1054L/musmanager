<script setup>
import { authService } from '../services/api'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { ref, inject } from 'vue'
const { t, locale } = useI18n()
const router = useRouter()
const user = inject('user')
const handleLogout = inject('logout')
const showLangMenu = ref(false)
const isMobileMenuOpen = ref(false)

// Inject Global Auth Modal Trigger
const openAuthModal = inject('openAuthModal')
const isSidebarOpen = inject('isSidebarOpen', ref(true))

const toggleSidebar = () => {
  isSidebarOpen.value = !isSidebarOpen.value
}

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

const toggleMobileMenu = () => {
  isMobileMenuOpen.value = !isMobileMenuOpen.value
}

const closeMobileMenu = () => {
  isMobileMenuOpen.value = false
}

// Remove local handleLogout as it's injected
</script>

<template>
  <header class="navbar-wrapper" :class="{ 'with-sidebar': user, 'sidebar-collapsed': !isSidebarOpen }">
    <div class="navbar-container" :class="{ 'menu-open': isMobileMenuOpen }">
      <!-- Sidebar Toggle (Desktop - Only Logged In) -->
      <button v-if="user" @click="toggleSidebar" class="sidebar-toggle-desktop hide-mobile">
        <i class="pi" :class="isSidebarOpen ? 'pi-align-left' : 'pi-align-justify'"></i>
      </button>

      <!-- Logo -->
      <router-link :to="user ? '/dashboard' : '/'" class="logo-area" @click="closeMobileMenu">
        <img src="/logo.png" class="logo-image" alt="Mus Manager Logo" />
        <div class="logo-text">
          <span class="logo-title mus-gold-text">Mus Manager</span>
        </div>
      </router-link>

      <!-- Nav Links (Desktop - Only Guest) -->
      <nav v-if="!user" class="nav-links">
        <router-link :to="{ path: '/tournaments', query: { status: 'active' } }" class="nav-item">{{ t('nav.tournaments') }}</router-link>
        <router-link to="/como-funciona" class="nav-item">{{ t('nav.howItWorks') }}</router-link>
        <router-link to="/caracteristicas" class="nav-item">{{ t('nav.features') }}</router-link>
      </nav>

      <!-- Right Side -->
      <div class="nav-actions">
        <!-- Language (Desktop) -->
        <div class="lang-selector desktop-only">
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
        <div class="auth-actions">
          <template v-if="!user">
            <button @click="openAuthModal('register')" class="mus-button-primary scale-90 btn-compact">
              {{ t('nav.getStarted') }}
            </button>
            <button @click="openAuthModal('login')" class="login-icon-btn hide-mobile" v-tooltip.bottom="t('nav.login')">
              <i class="pi pi-user"></i>
            </button>
          </template>
          <template v-else>
            <button @click="handleLogout" class="logout-btn hide-mobile" v-tooltip.bottom="t('nav.logout')">
              <i class="pi pi-power-off"></i>
            </button>
          </template>
        </div>

        <!-- Mobile Menu Toggle -->
        <button class="mobile-menu-toggle" @click="toggleMobileMenu">
          <i class="pi" :class="isMobileMenuOpen ? 'pi-times' : 'pi-bars'"></i>
        </button>
      </div>

      <!-- Mobile Menu Overlay -->
      <Transition name="mobile-menu">
        <div v-if="isMobileMenuOpen" class="mobile-menu-overlay">
          <nav class="mobile-nav-links">
            <router-link :to="{ path: '/tournaments', query: { status: 'active' } }" class="mobile-nav-item" @click="closeMobileMenu">
              <i class="pi pi-trophy"></i>
              {{ t('nav.tournaments') }}
            </router-link>
            <router-link to="/como-funciona" class="mobile-nav-item" @click="closeMobileMenu">
              <i class="pi pi-info-circle"></i>
              {{ t('nav.howItWorks') }}
            </router-link>
            <router-link to="/caracteristicas" class="mobile-nav-item" @click="closeMobileMenu">
              <i class="pi pi-star"></i>
              {{ t('nav.features') }}
            </router-link>
            <div class="mobile-divider"></div>
            
            <!-- Language (Mobile) -->
            <div class="mobile-lang-grid">
              <button v-for="lang in languages" :key="lang.code" 
                      @click="changeLanguage(lang.code)" 
                      class="mobile-lang-item" :class="{ active: locale === lang.code }">
                {{ lang.name }}
              </button>
            </div>


            <div class="mobile-divider"></div>
            
            <!-- Extra Auth (Mobile) -->
            <div class="mobile-auth-footer" v-if="user">
              <router-link to="/profile" class="mobile-nav-item" @click="closeMobileMenu">
                <i class="pi pi-user"></i>
                {{ t('profile.title') || 'Mi Perfil' }}
              </router-link>
              <button @click="handleLogout" class="mobile-nav-item logout">
                <i class="pi pi-power-off"></i>
                {{ t('nav.logout') || 'Cerrar Sesión' }}
              </button>
            </div>
            <div class="mobile-auth-footer" v-else>
               <button @click="openAuthModal('login'); closeMobileMenu()" class="mobile-nav-item">
                <i class="pi pi-sign-in"></i>
                {{ t('nav.login') }}
              </button>
            </div>
          </nav>
        </div>
      </Transition>

    </div>
  </header>
</template>

<style scoped>
.navbar-wrapper {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  z-index: 1000;
  display: flex;
  justify-content: center;
}

@media (min-width: 768px) {
  .navbar-wrapper {
    padding: 24px;
  }
}

.navbar-wrapper.with-sidebar {
  left: 280px;
  width: calc(100% - 280px);
  justify-content: center;
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  padding: 24px 0 0 0; /* Align top but keep sides flush with content area */
}

.navbar-wrapper.with-sidebar.sidebar-collapsed {
  left: 80px;
  width: calc(100% - 80px);
}

@media (max-width: 767px) {
  .navbar-wrapper.with-sidebar {
    left: 0;
    width: 100%;
  }
}

.navbar-container {
  width: 100%;
  max-width: 1440px;
  background: var(--surface);
  padding: 12px 24px;
  border-bottom: 2px solid var(--secondary);
  display: flex;
  align-items: center;
  justify-content: space-between;
  box-shadow: var(--shadow-md);
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
}

.navbar-container.menu-open {
  background: var(--surface);
}

@media (min-width: 768px) {
  .navbar-container {
    padding: 12px 32px;
  }
}

.logo-area {
  display: flex;
  align-items: center;
  gap: 8px;
  text-decoration: none;
  z-index: 1001;
}

@media (min-width: 768px) {
  .logo-area {
    gap: 12px;
  }
}

.logo-image {
  width: 24px;
  height: 24px;
  object-fit: contain;
  filter: drop-shadow(0 0 10px rgba(15, 179, 97, 0.2));
}

@media (min-width: 768px) {
  .logo-image {
    width: 32px;
    height: 32px;
  }
}

.logo-text {
  display: flex;
  flex-direction: column;
}

.logo-title {
  color: var(--text-main);
  font-weight: 900;
  font-size: 14px;
  text-transform: uppercase;
  font-style: italic;
  letter-spacing: -0.05em;
  line-height: 1;
  padding: 3px;
}

@media (min-width: 768px) {
  .logo-title {
    font-size: 18px;
  }
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
  color: var(--text-muted);
  font-size: 9px;
  font-weight: 900;
  text-transform: uppercase;
  letter-spacing: 0.3em;
  text-decoration: none;
  transition: color 0.3s;
}

.nav-item:hover {
  color: var(--secondary);
}

.nav-item.router-link-active {
  color: var(--secondary);
  position: relative;
}

.nav-item.router-link-active::after {
  content: '';
  position: absolute;
  bottom: -8px;
  left: 10%;
  width: 80%;
  height: 2px;
  background: var(--secondary);
  border-radius: 99px;
  box-shadow: 0 0 10px rgba(233, 195, 73, 0.5);
}

.nav-actions {
  display: flex;
  align-items: center;
  gap: 12px;
  z-index: 1001;
}

@media (min-width: 768px) {
  .nav-actions {
    gap: 24px;
  }
}

.auth-actions {
  display: flex;
  align-items: center;
  gap: 8px;
}

.lang-selector.desktop-only {
  display: none;
}

@media (min-width: 1024px) {
  .lang-selector.desktop-only {
    display: block;
    position: relative;
  }
}

.lang-btn {
  background: var(--surface-hover);
  border: 1px solid var(--border);
  color: var(--text-muted);
  padding: 6px 16px;
  border-radius: 99px;
  font-size: 8px;
  font-weight: 900;
  cursor: pointer;
  transition: all 0.3s;
}

.theme-toggle-btn {
  background: var(--surface-hover);
  border: 1px solid var(--border);
  color: var(--text-main);
  width: 36px;
  height: 36px;
  border-radius: 99px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.3s;
  box-shadow: var(--shadow-sm);
}

.theme-toggle-btn:hover {
  background: var(--primary-glow);
  color: var(--primary);
  transform: rotate(12deg) scale(1.1);
}

.lang-btn:hover {
  color: var(--primary);
  background: var(--surface-hover);
}

.lang-dropdown {
  position: absolute;
  top: 40px;
  right: 0;
  background: var(--surface);
  backdrop-filter: blur(20px);
  border: 1px solid var(--border);
  border-radius: 12px;
  padding: 8px;
  min-width: 120px;
}

.dropdown-item {
  width: 100%;
  padding: 8px 16px;
  background: transparent;
  border: none;
  color: var(--text-muted);
  text-align: left;
  font-size: 9px;
  font-weight: 900;
  text-transform: uppercase;
  border-radius: 8px;
  cursor: pointer;
}

.dropdown-item:hover {
  background: var(--primary-glow);
  color: var(--primary);
}

.login-icon-btn {
  background: var(--surface-hover);
  border: 1px solid var(--border);
  color: var(--text-muted);
  width: 32px;
  height: 32px;
  border-radius: 99px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.3s;
  margin-left: 12px;
}

.login-icon-btn:hover {
  color: var(--secondary);
  border-color: var(--secondary);
  transform: translateY(-2px);
  box-shadow: 0 0 15px rgba(233, 195, 73, 0.2);
}

.hide-mobile {
  display: none;
}

@media (min-width: 768px) {
  .hide-mobile {
    display: inline-block;
  }
}

.btn-compact {
  padding: 8px 16px !important;
  font-size: 10px !important;
}

.logout-btn {
  width: 32px;
  height: 32px;
  background: var(--surface-hover);
  border: 1px solid var(--border);
  border-radius: 99px;
  color: var(--text-muted);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.3s;
}

.logout-btn:hover {
  color: #fb7185;
  background: rgba(251, 113, 133, 0.1);
  border-color: #fb7185;
}

.sidebar-toggle-desktop {
  background: transparent;
  border: 1px solid var(--border);
  color: var(--secondary);
  width: 40px;
  height: 40px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.3s;
}

.sidebar-toggle-desktop:hover {
  background: var(--surface);
  border-color: var(--secondary);
  box-shadow: 0 0 15px rgba(233, 195, 73, 0.2);
}

.mobile-menu-toggle {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  background: var(--surface-hover);
  border: 1px solid var(--border);
  border-radius: 12px;
  color: var(--text-main);
  cursor: pointer;
  transition: all 0.3s;
}

@media (min-width: 1024px) {
  .mobile-menu-toggle {
    display: none;
  }
}

.mobile-menu-toggle:hover {
  background: var(--primary-glow);
  border-color: var(--primary);
}

/* Mobile Menu Overlay */
.mobile-menu-overlay {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  margin-top: 12px;
  background: var(--surface);
  backdrop-filter: blur(40px);
  border: 1px solid var(--border);
  border-radius: 24px;
  padding: 24px;
  box-shadow: var(--shadow-md);
  overflow: hidden;
}

.mobile-nav-links {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.mobile-nav-item {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 16px;
  color: var(--text-muted);
  text-decoration: none;
  font-size: 11px;
  font-weight: 900;
  text-transform: uppercase;
  letter-spacing: 0.2em;
  border-radius: 16px;
  transition: all 0.3s;
  background: transparent;
  border: none;
  width: 100%;
  text-align: left;
  cursor: pointer;
}

.mobile-nav-item i {
  color: var(--primary);
  font-size: 14px;
}

.mobile-nav-item:hover, .mobile-nav-item.router-link-active {
  background: var(--surface-hover);
  color: var(--secondary);
  border-left: 4px solid var(--secondary);
}

.mobile-nav-item.logout {
  color: #fb7185;
}

.mobile-nav-item.logout i {
  color: #fb7185;
}

.mobile-divider {
  height: 1px;
  background: var(--border);
  margin: 16px 0;
}

.mobile-lang-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 8px;
}

.mobile-lang-item {
  padding: 12px;
  background: var(--surface-hover);
  border: 1px solid var(--border);
  border-radius: 12px;
  color: var(--text-muted);
  font-size: 9px;
  font-weight: 900;
  text-transform: uppercase;
  cursor: pointer;
  transition: all 0.3s;
}

.mobile-lang-item.active {
  background: var(--primary);
  color: white;
  border-color: var(--primary);
}

/* Transitions */
.mobile-menu-enter-active, .mobile-menu-leave-active {
  transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}

.mobile-menu-enter-from, .mobile-menu-leave-to {
  opacity: 0;
  transform: translateY(-20px) scale(0.95);
}

.scale-90 {
  transform: scale(0.9);
}
</style>
