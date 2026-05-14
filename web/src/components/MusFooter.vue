<script setup>
import { authService } from '../services/api'
</script>

<template>
  <footer class="mus-footer">
    <div class="mus-footer-grid">
      <div class="footer-brand">
        <div class="footer-logo">
          <img src="/logo.png" class="logo-image-footer" alt="Mus Manager Logo" />
          <span class="logo-title mus-gold-text">Mus Manager</span>
        </div>
        <p class="footer-desc">
          {{ $t('footer.desc') }}
        </p>
      </div>
      
      <div class="footer-links-col">
        <h4 class="footer-h4">{{ $t('footer.product') }}</h4>
        <ul class="footer-ul">
          <li><router-link to="/caracteristicas" class="footer-link">{{ $t('nav.features') }}</router-link></li>
          <li><router-link to="/como-funciona" class="footer-link">{{ $t('features.how_it_works.title') }}</router-link></li>
          <li><router-link :to="{ path: '/tournaments', query: { status: 'active' } }" class="footer-link">{{ $t('nav.tournaments') }}</router-link></li>
        </ul>
      </div>

      <div class="footer-links-col">
        <h4 class="footer-h4">{{ $t('footer.company') }}</h4>
        <ul class="footer-ul">
          <li><router-link to="/sobre-nosotros" class="footer-link">{{ $t('footer.about') }}</router-link></li>
          <li>{{ $t('footer.contact') }}</li>
        </ul>
      </div>

      <div class="footer-links-col">
        <h4 class="footer-h4">{{ $t('footer.legal') }}</h4>
        <ul class="footer-ul">
          <li><router-link to="/privacidad" class="footer-link">{{ $t('footer.privacy') }}</router-link></li>
          <li><router-link to="/terminos" class="footer-link">{{ $t('footer.terms') }}</router-link></li>
          <li><router-link to="/cookies" class="footer-link">{{ $t('footer.cookies') }}</router-link></li>
        </ul>
      </div>
    </div>

    <div class="footer-bottom">
      <p class="footer-copy" v-html="$t('footer.rights').replace('Mus Manager', '<span class=\'mus-gold-text\'>Mus Manager</span>')"></p>
      <div class="footer-status">
        <span class="status-dot"></span>
        <span class="status-text">{{ $t('footer.system_online') }} v1.0.1</span>
      </div>
    </div>
  </footer>
</template>

<style scoped>
.mus-footer {
  position: relative;
  z-index: 10;
  border-top: 1px solid rgba(255, 255, 255, 0.05);
  padding: 80px 16px 60px;
  max-width: 1280px;
  margin: 0 auto;
  width: 100%;
}

@media (min-width: 768px) {
  .mus-footer {
    padding: 120px 24px 80px;
  }
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

.logo-image-footer {
  width: 40px;
  height: 40px;
  object-fit: contain;
  filter: drop-shadow(0 0 10px rgba(15, 179, 97, 0.2));
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
  color: var(--text-muted);
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
  color: var(--text-muted);
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
  color: var(--primary);
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
  color: var(--text-muted);
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
  background: var(--primary);
  border-radius: 50%;
}

.status-text {
  font-size: 8px;
  font-weight: 900;
  color: var(--text-muted);
  text-transform: uppercase;
  letter-spacing: 0.2em;
}
</style>
