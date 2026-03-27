import { createApp } from 'vue'
import './style.css'
import 'primeicons/primeicons.css'
import 'primeflex/primeflex.css'
import './layout/styles/layout.scss'
import App from './App.vue'
import router from './router'
import i18n from './i18n'
import PrimeVue from 'primevue/config'
import Aura from '@primevue/themes/aura'
import { createPinia } from 'pinia'

import Tooltip from 'primevue/tooltip'
import ConfirmationService from 'primevue/confirmationservice'
import ToastService from 'primevue/toastservice'

const app = createApp(App)
const pinia = createPinia()

app.use(pinia)
app.use(router)
app.use(i18n)
app.use(ConfirmationService)
app.use(ToastService)
app.use(PrimeVue, {
    theme: {
        preset: Aura,
        options: {
            darkModeSelector: '.layout-theme-dark'
        }
    }
})
app.directive('tooltip', Tooltip)

app.mount('#app')
