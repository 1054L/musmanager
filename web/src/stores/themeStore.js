import { defineStore } from 'pinia'

export const useThemeStore = defineStore('theme', {
  state: () => ({
    isDark: localStorage.getItem('mus_theme') !== 'light'
  }),
  actions: {
    toggleTheme() {
      this.isDark = !this.isDark
      const theme = this.isDark ? 'dark' : 'light'
      localStorage.setItem('mus_theme', theme)
      this.applyTheme()
    },
    applyTheme() {
      const theme = this.isDark ? 'dark' : 'light'
      document.documentElement.setAttribute('data-theme', theme)
      // Also apply a class to body for global targeting
      if (this.isDark) {
        document.body.classList.remove('light-mode')
        document.body.classList.add('dark-mode')
      } else {
        document.body.classList.remove('dark-mode')
        document.body.classList.add('light-mode')
      }
    },
    initTheme() {
      this.applyTheme()
    }
  }
})
