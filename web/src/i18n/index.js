import { createI18n } from 'vue-i18n'
import es from './locales/es.json'
import en from './locales/en.json'
import eu from './locales/eu.json'

const messages = {
  es,
  en,
  eu
}

// Get locale from localStorage or browser settings
const getLocale = () => {
  const saved = localStorage.getItem('locale')
  if (saved) return saved
  
  const browserLang = navigator.language.split('-')[0]
  return messages[browserLang] ? browserLang : 'es'
}

const i18n = createI18n({
  legacy: false, // Use Composition API
  locale: getLocale(),
  fallbackLocale: 'en',
  messages,
})

export default i18n
