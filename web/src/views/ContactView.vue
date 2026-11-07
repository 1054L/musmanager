<script setup>
import { ref, reactive } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const form = reactive({
  name: '',
  email: '',
  subject: '',
  message: ''
})

const isSending = ref(false)
const submitStatus = ref(null) // 'success' or 'error'



const submitForm = () => {
  if (!form.name || !form.email || !form.subject || !form.message) {
    submitStatus.value = 'error'
    return
  }

  isSending.value = true
  submitStatus.value = null

  // Simulate contact form submission
  setTimeout(() => {
    isSending.value = false
    submitStatus.value = 'success'
    // Clear form
    form.name = ''
    form.email = ''
    form.subject = ''
    form.message = ''
  }, 1500)
}
</script>

<template>
  <div class="contact-view mus-container py-20">
    <!-- Header -->
    <section class="text-center mb-16 animate-fade-in">
      <h1 class="mus-h1 mb-6">{{ t('contacto.title') }}</h1>
      <p class="mus-p-gold text-2xl font-bold italic tracking-wider">{{ t('contacto.subtitle') }}</p>
      <div class="mus-divider mx-auto mt-8"></div>
    </section>

    <!-- FAQ Redirect Banner (Full width at the top of content) -->
    <div class="w-full mb-12 animate-fade-in">
      <div class="mus-glass p-6 md:p-8 rounded-3xl border-emerald-500/10 bg-emerald-500/5 relative overflow-hidden group flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
        <div class="absolute -right-16 -top-16 w-32 h-32 bg-emerald-500/10 rounded-full blur-3xl group-hover:scale-150 transition-all duration-700"></div>
        <div>
          <h3 class="mus-h3 mb-2 text-[#e9c349] tracking-wider text-xl">
            {{ t('faq.title') }}
          </h3>
          <p class="mus-p text-sm leading-relaxed text-[#ceead1] max-w-3xl">
            {{ t('contacto.has_questions') }}
          </p>
        </div>
        <router-link to="/faq" class="mus-btn-primary inline-block text-center text-xs py-3 px-6 whitespace-nowrap">
          {{ t('contacto.view_faq') }}
        </router-link>
      </div>
    </div>

    <!-- Main Layout: Form and Direct Contact Card stacked, each occupying full width -->
    <div class="flex flex-col gap-12 mb-20 w-full">
      <!-- Contact Form (Full width) -->
      <div class="w-full animate-slide-left">
        <div class="mus-glass p-8 md:p-10 rounded-3xl border-emerald-500/10 w-full">
          <h2 class="mus-h2 mb-6 text-xl tracking-wider text-left border-b border-emerald-500/20 pb-4">
            {{ t('contacto.form_title') }}
          </h2>

          <form @submit.prevent="submitForm" class="flex flex-col gap-6">
            <!-- Name Input -->
            <div class="flex flex-col gap-2">
              <label for="name" class="text-xs font-bold text-[#a1d2ab] uppercase tracking-widest">
                {{ t('contacto.name') }} <span class="text-red-500">*</span>
              </label>
              <input 
                type="text" 
                id="name" 
                v-model="form.name" 
                required 
                class="mus-input-field" 
                :placeholder="t('contacto.name_placeholder')" 
              />
            </div>

            <!-- Email Input -->
            <div class="flex flex-col gap-2">
              <label for="email" class="text-xs font-bold text-[#a1d2ab] uppercase tracking-widest">
                {{ t('contacto.email') }} <span class="text-red-500">*</span>
              </label>
              <input 
                type="email" 
                id="email" 
                v-model="form.email" 
                required 
                class="mus-input-field" 
                :placeholder="t('contacto.email_placeholder')" 
              />
            </div>

            <!-- Subject Input -->
            <div class="flex flex-col gap-2">
              <label for="subject" class="text-xs font-bold text-[#a1d2ab] uppercase tracking-widest">
                {{ t('contacto.subject') }} <span class="text-red-500">*</span>
              </label>
              <input 
                type="text" 
                id="subject" 
                v-model="form.subject" 
                required 
                class="mus-input-field" 
                :placeholder="t('contacto.subject_placeholder')" 
              />
            </div>

            <!-- Message Input -->
            <div class="flex flex-col gap-2">
              <label for="message" class="text-xs font-bold text-[#a1d2ab] uppercase tracking-widest">
                {{ t('contacto.message') }} <span class="text-red-500">*</span>
              </label>
              <textarea 
                id="message" 
                v-model="form.message" 
                required 
                rows="6" 
                class="mus-input-field resize-none" 
                :placeholder="t('contacto.message_placeholder')"
              ></textarea>
            </div>

            <!-- Notification Messages -->
            <transition name="fade">
              <div v-if="submitStatus === 'success'" class="alert-success p-4 rounded-xl text-sm font-bold border border-emerald-500/30 bg-emerald-500/10 text-emerald-400">
                {{ t('contacto.success') }}
              </div>
              <div v-else-if="submitStatus === 'error'" class="alert-error p-4 rounded-xl text-sm font-bold border border-red-500/30 bg-red-500/10 text-red-400">
                {{ t('contacto.error') }}
              </div>
            </transition>

            <!-- Submit Button -->
            <button 
              type="submit" 
              class="mus-btn-primary mt-4 self-start"
              :disabled="isSending"
            >
              <span v-if="isSending">{{ t('contacto.sending') }}</span>
              <span v-else>{{ t('contacto.submit') }}</span>
            </button>
          </form>
        </div>
      </div>

      <!-- Direct Contact Card (Full width) -->
      <div class="w-full animate-slide-right">
        <div class="mus-glass p-8 md:p-10 rounded-3xl border-emerald-500/20 bg-emerald-500/5 relative overflow-hidden group w-full flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
          <div class="absolute -right-16 -top-16 w-32 h-32 bg-emerald-500/10 rounded-full blur-3xl group-hover:scale-150 transition-all duration-700"></div>
          <div>
            <h3 class="mus-h3 mb-2 text-[#e9c349] tracking-wider text-xl">
              info@musmanager.com
            </h3>
            <p class="mus-p text-sm leading-relaxed text-[#ceead1]">
              {{ t('contacto.email_us') }}
            </p>
          </div>
          <a href="mailto:info@musmanager.com" class="mus-btn-primary inline-block text-center text-xs py-3 px-6 whitespace-nowrap">
            {{ t('footer.contact') }}
          </a>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.mus-p-gold {
  background: linear-gradient(135deg, #bf953f 0%, #fcf6ba 50%, #aa771c 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.mus-divider {
  width: 80px;
  height: 4px;
  background: linear-gradient(90deg, transparent, #e9c349, transparent);
  border-radius: 99px;
}

.mus-input-field {
  width: 100%;
  padding: 14px 18px;
  background: rgba(13, 36, 21, 0.4);
  border: 1px solid rgba(233, 195, 73, 0.15);
  border-radius: 8px;
  color: #ceead1;
  font-size: 14px;
  font-family: inherit;
  transition: all 0.3s ease;
}

.mus-input-field:focus {
  outline: none;
  background: rgba(13, 36, 21, 0.6);
  border-color: #e9c349;
  box-shadow: 0 0 10px rgba(233, 195, 73, 0.1);
}

.light-mode .mus-input-field {
  background: #ffffff !important;
  border-color: #cbd5e1 !important;
  color: #0f172a !important;
}

.light-mode .mus-input-field:focus {
  border-color: #bf953f !important;
  box-shadow: 0 0 10px rgba(191, 149, 63, 0.1) !important;
}

/* Animations */
.animate-fade-in {
  animation: fadeIn 1s ease-out;
}

.animate-slide-left {
  animation: slideLeft 0.8s ease-out;
}

.animate-slide-right {
  animation: slideRight 0.8s ease-out;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}

@keyframes slideLeft {
  from { opacity: 0; transform: translateX(-40px); }
  to { opacity: 1; transform: translateX(0); }
}

@keyframes slideRight {
  from { opacity: 0; transform: translateX(40px); }
  to { opacity: 1; transform: translateX(0); }
}

/* Vue transitions */
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.3s;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}

.slide-fade-enter-active {
  transition: all 0.3s ease-out;
}
.slide-fade-leave-active {
  transition: all 0.2s cubic-bezier(1, 0.5, 0.8, 1);
}
.slide-fade-enter-from, .slide-fade-leave-to {
  transform: translateY(-10px);
  opacity: 0;
}
</style>
