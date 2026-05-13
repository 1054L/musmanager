<script setup>
import { useI18n } from 'vue-i18n'
import { inject } from 'vue'

const { t } = useI18n()
const props = defineProps({
  isOpen: Boolean
})

const user = inject('user')
</script>

<template>
  <aside class="mus-sidebar" :class="{ 'collapsed': !isOpen }">
    <div class="sidebar-content">
      
      <!-- User Mini Profile -->
      <div class="sidebar-user-section" v-if="user">
        <div class="user-avatar-gold">
          <i class="pi pi-user"></i>
        </div>
        <div class="user-info" v-if="isOpen">
          <span class="user-name">{{ user.firstName || user.email.split('@')[0] }}</span>
          <span class="user-role">{{ t('nav.organizer') || 'Organizador' }}</span>
        </div>
      </div>

      <nav class="sidebar-nav">
        <router-link to="/dashboard" class="sidebar-nav-item">
          <i class="pi pi-th-large"></i>
          <span v-if="isOpen">{{ t('nav.dashboard') || 'Panel de Control' }}</span>
        </router-link>

        <router-link to="/my-tournaments" class="sidebar-nav-item">
          <i class="pi pi-trophy"></i>
          <span v-if="isOpen">{{ t('dashboard.my_tournaments') || 'Mis Torneos' }}</span>
        </router-link>
        
        <router-link to="/profile" class="sidebar-nav-item">
          <i class="pi pi-user-edit"></i>
          <span v-if="isOpen">{{ t('profile.title') || 'Mi Perfil' }}</span>
        </router-link>

        <div class="sidebar-divider"></div>

        <router-link to="/tournaments" class="sidebar-nav-item secondary">
          <i class="pi pi-search"></i>
          <span v-if="isOpen">{{ t('nav.tournaments') }}</span>
        </router-link>
      </nav>

      <!-- Sidebar Footer -->
      <div class="sidebar-footer" v-if="isOpen">
        <div class="gold-badge">
          <i class="pi pi-shield mr-2"></i>
          GOLD EDITION
        </div>
      </div>
    </div>
  </aside>
</template>

<style scoped>
.mus-sidebar {
  position: fixed;
  top: 0;
  left: 0;
  height: 100vh;
  width: 280px;
  background: var(--nav-surface);
  border-right: 1px solid var(--nav-border);
  z-index: 900; /* Below Navbar wrapper which is 1000 */
  padding-top: 140px; /* Space for fixed Navbar */
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  overflow-x: hidden;
}

.mus-sidebar.collapsed {
  width: 80px;
}

.sidebar-content {
  height: 100%;
  display: flex;
  flex-direction: column;
  padding: 24px;
}

.mus-sidebar.collapsed .sidebar-content {
  padding: 24px 12px;
  align-items: center;
}

.sidebar-user-section {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-bottom: 40px;
  padding: 12px;
  background: rgba(233, 195, 73, 0.05);
  border-radius: 20px;
  border: 1px solid rgba(233, 195, 73, 0.1);
}

.mus-sidebar.collapsed .sidebar-user-section {
  background: transparent;
  border: none;
  padding: 0;
}

.user-avatar-gold {
  width: 40px;
  height: 40px;
  background: linear-gradient(135deg, #af8d11 0%, #fcf6ba 50%, #af8d11 100%);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--bg-app);
  font-size: 1.2rem;
  box-shadow: 0 4px 15px rgba(233, 195, 73, 0.3);
  flex-shrink: 0;
}

.user-info {
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.user-name {
  color: var(--nav-text);
  font-weight: 800;
  font-size: 14px;
  white-space: nowrap;
  text-overflow: ellipsis;
  overflow: hidden;
}

.user-role {
  color: var(--secondary);
  font-size: 9px;
  font-weight: 900;
  text-transform: uppercase;
  letter-spacing: 0.1em;
}

.sidebar-nav {
  display: flex;
  flex-direction: column;
  gap: 8px;
  flex: 1;
}

.sidebar-nav-item {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 16px;
  color: var(--nav-muted);
  text-decoration: none;
  font-size: 11px;
  font-weight: 900;
  text-transform: uppercase;
  letter-spacing: 0.15em;
  border-radius: 16px;
  transition: all 0.3s;
  white-space: nowrap;
}

.mus-sidebar.collapsed .sidebar-nav-item {
  justify-content: center;
  padding: 16px 0;
  width: 48px;
}

.sidebar-nav-item i {
  font-size: 16px;
  transition: all 0.3s;
}

.sidebar-nav-item:hover {
  background: var(--nav-surface-hover);
  color: var(--primary);
}

.sidebar-nav-item.router-link-active {
  background: rgba(233, 195, 73, 0.08);
  color: var(--secondary);
  border-left: 3px solid var(--secondary);
}

.sidebar-nav-item.router-link-active i {
  transform: scale(1.1);
  filter: drop-shadow(0 0 8px rgba(233, 195, 73, 0.4));
}

.sidebar-divider {
  height: 1px;
  background: var(--nav-border);
  margin: 16px 0;
}

.gold-badge {
  background: linear-gradient(90deg, #af8d11, #fcf6ba, #af8d11);
  -webkit-background-clip: text;
  background-clip: text;
  -webkit-text-fill-color: transparent;
  font-weight: 950;
  font-size: 10px;
  letter-spacing: 0.2em;
  text-align: center;
  padding: 12px;
  border: 1px solid rgba(233, 195, 73, 0.2);
  border-radius: 12px;
}

@media (max-width: 767px) {
  .mus-sidebar {
    display: none; /* Hide desktop sidebar on mobile, use mobile menu */
  }
}
</style>
