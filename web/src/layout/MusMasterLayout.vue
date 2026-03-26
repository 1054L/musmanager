<script setup>
import { ref, provide } from 'vue'
import MusNavbar from './MusNavbar.vue'
import AuthModal from '../components/AuthModal.vue'

// Global Auth Modal State
const showAuthModal = ref(false)
const authModalMode = ref('login')

const openAuthModal = (mode = 'login') => {
  authModalMode.value = mode
  showAuthModal.value = true
}

// Provide to all children (Navbar, HomeView, etc)
provide('openAuthModal', openAuthModal)
</script>

<template>
  <div class="mus-layout">
    
    <!-- Background Elements -->
    <div class="bg-elements">
       <div class="bg-gradient"></div>
       <div class="bg-noise"></div>
       <div class="bg-grid"></div>
       <div class="bg-glow"></div>
       <div class="bg-glow-2"></div>
       <div class="bg-vignette"></div>
    </div>

    <!-- Header / Navbar -->
    <MusNavbar />

    <!-- Main Content -->
    <main class="mus-main">
      <div class="mus-content-wrapper">
        <slot />
      </div>
    </main>

    <!-- Footer -->
    <footer class="mus-footer">
      <div class="mus-footer-grid">
        <div class="footer-brand">
           <div class="footer-logo">
              <div class="logo-box">
                <span class="logo-letter mus-gold-text">M</span>
              </div>
              <span class="logo-title mus-gold-text">Mus Manager</span>
           </div>
           <p class="footer-desc">
             {{ $t('footer.desc') }}
           </p>
        </div>
        
        <div class="footer-links-col">
           <h4 class="footer-h4">{{ $t('footer.product') }}</h4>
           <ul class="footer-ul">
             <li><router-link to="/features" class="footer-link">{{ $t('nav.features') }}</router-link></li>
             <li><router-link to="/tournaments" class="footer-link">{{ $t('nav.tournaments') }}</router-link></li>
           </ul>
        </div>

        <div class="footer-links-col">
           <h4 class="footer-h4">{{ $t('footer.company') }}</h4>
           <ul class="footer-ul">
             <li>{{ $t('footer.about') }}</li>
             <li>{{ $t('footer.contact') }}</li>
           </ul>
        </div>

        <div class="footer-links-col">
           <h4 class="footer-h4">{{ $t('footer.legal') }}</h4>
           <ul class="footer-ul">
             <li>{{ $t('footer.privacy') }}</li>
             <li>{{ $t('footer.terms') }}</li>
           </ul>
        </div>
      </div>

      <div class="footer-bottom">
         <p class="footer-copy" v-html="$t('footer.rights').replace('Mus Manager', '<span class=\'mus-gold-text\'>Mus Manager</span>')"></p>
         <div class="footer-status">
            <span class="status-dot"></span>
            <span class="status-text">System OK</span>
         </div>
      </div>
    </footer>

    <!-- Global Auth Modal -->
    <AuthModal 
      :isOpen="showAuthModal" 
      :initialMode="authModalMode" 
      @close="showAuthModal = false"
    />
  </div>
</template>

<style scoped>
.mus-layout {
  min-height: 100vh;
  background: #050505;
  color: white;
  font-family: 'Inter', sans-serif;
  display: flex;
  flex-direction: column;
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
  background: radial-gradient(circle at 50% 50%, #080808 0%, #020202 100%);
}

.bg-noise {
  position: absolute;
  inset: 0;
  background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noiseFilter'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.65' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noiseFilter)'/%3E%3C/svg%3E");
  opacity: 0.05;
  mix-blend-mode: overlay;
}

.bg-vignette {
  position: absolute;
  inset: 0;
  background: radial-gradient(circle at 50% 50%, transparent 20%, rgba(0, 0, 0, 0.5) 100%);
}

.bg-grid {
  position: absolute;
  inset: 0;
  background-image: url('/grid.svg');
  background-size: 60px 60px;
  opacity: 0.02;
}

.bg-glow {
  position: absolute;
  top: -10%;
  right: -10%;
  width: 50%;
  height: 50%;
  background: radial-gradient(circle, rgba(15, 179, 97, 0.05) 0%, transparent 70%);
  filter: blur(140px);
}

.bg-glow-2 {
  position: absolute;
  bottom: -15%;
  left: -10%;
  width: 60%;
  height: 60%;
  background: radial-gradient(circle, rgba(15, 179, 97, 0.02) 0%, transparent 70%);
  filter: blur(160px);
}

.mus-main {
  position: relative;
  z-index: 10;
  flex: 1 0 auto;
  padding-top: 140px;
  padding-bottom: 80px;
}

.mus-content-wrapper {
  max-width: 1280px;
  margin: 0 auto;
  padding: 0 24px;
}

.mus-footer {
  position: relative;
  z-index: 10;
  border-top: 1px solid rgba(255, 255, 255, 0.05);
  padding: 120px 24px 80px;
  max-width: 1280px;
  margin: 0 auto;
  width: 100%;
}

.mus-footer-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 80px;
}

@media (min-width: 1024px) {
  .mus-footer-grid {
    grid-template-columns: 2fr 1fr 1fr 1fr;
  }
}

.footer-brand {
  display: flex;
  flex-direction: column;
  gap: 32px;
}

.footer-logo {
  display: flex;
  align-items: center;
  gap: 12px;
}

.logo-box {
  width: 32px;
  height: 32px;
  background: #0fb361;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #050505;
  font-weight: 900;
  overflow: hidden;
}

.logo-letter {
  font-size: 18px;
  font-weight: 950;
  line-height: 1;
}

.logo-title {
  font-size: 20px;
  font-weight: 900;
  text-transform: uppercase;
  font-style: italic;
  letter-spacing: -0.05em;
  padding: 3px;
}

.footer-desc {
  color: #64748b;
  font-size: 16px;
  line-height: 1.6;
  max-width: 320px;
}

.footer-h4 {
  font-size: 10px;
  font-weight: 950;
  text-transform: uppercase;
  letter-spacing: 0.3em;
  margin-bottom: 32px;
}

.footer-ul {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.footer-ul li {
  font-size: 11px;
  font-weight: 700;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.1em;
}

.footer-link {
  color: inherit;
  text-decoration: none;
  transition: color 0.3s;
  cursor: pointer;
}

.footer-link:hover {
  color: white;
}

.footer-bottom {
  margin-top: 100px;
  padding-top: 40px;
  border-top: 1px solid rgba(255, 255, 255, 0.05);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: space-between;
  gap: 24px;
}

@media (min-width: 768px) {
  .footer-bottom {
    flex-direction: row;
  }
}

.footer-copy {
  font-size: 10px;
  font-weight: 900;
  text-transform: uppercase;
  letter-spacing: 0.2em;
  color: #475569;
}

.footer-status {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px 20px;
  background: rgba(255, 255, 255, 0.02);
  border: 1px solid rgba(255, 255, 255, 0.05);
  border-radius: 99px;
}

.status-dot {
  width: 6px;
  height: 6px;
  background: #0fb361;
  border-radius: 50%;
}

.status-text {
  font-size: 8px;
  font-weight: 900;
  color: #94a3b8;
  text-transform: uppercase;
  letter-spacing: 0.2em;
}
</style>
