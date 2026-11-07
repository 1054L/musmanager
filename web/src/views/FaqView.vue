<script setup>
import { ref, computed, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import GoogleAd from '../components/GoogleAd.vue'

const { t } = useI18n()

const categories = ['mus', 'mgmt', 'account', 'ads']
const selectedCategory = ref('all')
const searchQuery = ref('')
const activeFaqId = ref(null)

const toggleFaq = (id) => {
  if (activeFaqId.value === id) {
    activeFaqId.value = null
  } else {
    activeFaqId.value = id
  }
}

// Watch category or search query to automatically collapse open accordions
watch([selectedCategory, searchQuery], () => {
  activeFaqId.value = null
})

// Build the questions computed property based on structured indices defined in translations
const allQuestions = computed(() => {
  const list = []
  
  // mus category has 6 questions
  for (let i = 0; i < 6; i++) {
    list.push({
      category: 'mus',
      id: `mus-${i}`,
      q: t(`faq.questions.mus[${i}].q`),
      a: t(`faq.questions.mus[${i}].a`)
    })
  }
  
  // mgmt category has 5 questions
  for (let i = 0; i < 5; i++) {
    list.push({
      category: 'mgmt',
      id: `mgmt-${i}`,
      q: t(`faq.questions.mgmt[${i}].q`),
      a: t(`faq.questions.mgmt[${i}].a`)
    })
  }
  
  // account category has 3 questions
  for (let i = 0; i < 3; i++) {
    list.push({
      category: 'account',
      id: `account-${i}`,
      q: t(`faq.questions.account[${i}].q`),
      a: t(`faq.questions.account[${i}].a`)
    })
  }
  
  // ads category has 3 questions
  for (let i = 0; i < 3; i++) {
    list.push({
      category: 'ads',
      id: `ads-${i}`,
      q: t(`faq.questions.ads[${i}].q`),
      a: t(`faq.questions.ads[${i}].a`)
    })
  }
  
  return list
})

// Computed questions filtered by search text and selected category
const filteredQuestions = computed(() => {
  let list = allQuestions.value
  
  if (selectedCategory.value !== 'all') {
    list = list.filter(q => q.category === selectedCategory.value)
  }
  
  if (searchQuery.value.trim()) {
    const query = searchQuery.value.toLowerCase()
    list = list.filter(q => q.q.toLowerCase().includes(query) || q.a.toLowerCase().includes(query))
  }
  
  return list
})
</script>

<template>
  <div class="faq-view mus-container py-20">
    <!-- Header -->
    <section class="text-center mb-16 animate-fade-in">
      <h1 class="mus-h1 mb-6">{{ t('faq.title') }}</h1>
      <p class="mus-p-gold text-2xl font-bold italic tracking-wider">{{ t('faq.subtitle') }}</p>
      <div class="mus-divider mx-auto mt-8"></div>
    </section>

    <!-- Top Ad Block -->
    <div class="w-full px-4 mb-12">
      <GoogleAd :key="'top-faq'" />
    </div>

    <!-- Search and Filters Section -->
    <div class="w-full flex flex-col gap-8 mb-12 animate-fade-in">
      <!-- Search Input -->
      <div class="w-full relative">
        <input 
          type="text" 
          v-model="searchQuery" 
          class="mus-input-field pl-12 pr-12" 
          :placeholder="t('faq.search_placeholder')" 
        />
        <i class="pi pi-search absolute left-4 top-1/2 -translate-y-1/2 text-[#a1d2ab] text-lg"></i>
        <button 
          v-if="searchQuery" 
          @click="searchQuery = ''" 
          class="absolute right-4 top-1/2 -translate-y-1/2 text-[#8b938a] hover:text-[#e9c349] transition-colors"
        >
          <i class="pi pi-times"></i>
        </button>
      </div>

      <!-- Category Filter Tabs -->
      <div class="flex flex-wrap justify-center gap-4">
        <button 
          @click="selectedCategory = 'all'" 
          class="category-btn"
          :class="{ active: selectedCategory === 'all' }"
        >
          {{ t('tournaments_page.filters.all') || 'Todos' }}
        </button>
        <button 
          v-for="cat in categories" 
          :key="cat"
          @click="selectedCategory = cat" 
          class="category-btn"
          :class="{ active: selectedCategory === cat }"
        >
          {{ t(`faq.categories.${cat}`) }}
        </button>
      </div>
    </div>

    <!-- Questions Accordion Section -->
    <div class="w-full mb-16 animate-fade-in">
      <div class="mus-glass p-6 md:p-10 rounded-3xl border-emerald-500/10">
        <!-- If results exist -->
        <div v-if="filteredQuestions.length > 0" class="flex flex-col gap-6">
          <div 
            v-for="faq in filteredQuestions" 
            :key="faq.id"
            class="faq-item border-b border-emerald-500/10 pb-6 last:border-b-0 last:pb-0"
          >
            <button 
              @click="toggleFaq(faq.id)" 
              class="flex justify-between items-center w-full text-left font-bold text-sm md:text-base text-[#ceead1] hover:text-[#e9c349] transition-all py-2"
            >
              <span class="flex items-center gap-3">
                <span class="text-[#e9c349] font-mono text-xs md:text-sm">?</span>
                {{ faq.q }}
              </span>
              <span class="text-xs transition-transform duration-300 ml-4 flex-shrink-0" :class="{ 'rotate-180': activeFaqId === faq.id }">
                ▼
              </span>
            </button>
            <transition name="slide-fade">
              <div v-show="activeFaqId === faq.id" class="mt-4 pl-6 text-xs md:text-sm leading-relaxed text-[#8b938a] border-l-2 border-[#e9c349]/40">
                {{ faq.a }}
              </div>
            </transition>
          </div>
        </div>

        <!-- No results state -->
        <div v-else class="text-center py-12">
          <i class="pi pi-filter-slash text-4xl text-[#8b938a] mb-4"></i>
          <p class="text-[#ceead1] text-lg font-bold mb-2">
            {{ t('faq.no_results') }}
          </p>
        </div>
      </div>
    </div>

    <!-- CTA Section -->
    <div class="w-full mb-20 animate-slide-up">
      <div class="mus-glass p-8 md:p-12 rounded-3xl border-emerald-500/20 bg-emerald-500/5 relative overflow-hidden group text-center flex flex-col items-center gap-6">
        <div class="absolute -left-16 -bottom-16 w-32 h-32 bg-emerald-500/10 rounded-full blur-3xl group-hover:scale-150 transition-all duration-700"></div>
        <div class="absolute -right-16 -top-16 w-32 h-32 bg-emerald-500/10 rounded-full blur-3xl group-hover:scale-150 transition-all duration-700"></div>
        
        <div>
          <h2 class="mus-h2 mb-3 text-[#e9c349] tracking-wider text-2xl font-bold">
            {{ t('faq.cta_title') }}
          </h2>
          <p class="mus-p max-w-xl text-sm leading-relaxed text-[#ceead1]">
            {{ t('faq.cta_desc') }}
          </p>
        </div>
        
        <router-link to="/contacto" class="mus-btn-primary py-3 px-8 text-sm font-bold tracking-wider uppercase">
          {{ t('faq.cta_button') }}
        </router-link>
      </div>
    </div>

    <!-- Bottom Ad Block -->
    <div class="w-full px-4">
      <GoogleAd :key="'bottom-faq'" />
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

.category-btn {
  background: rgba(13, 36, 21, 0.4);
  border: 1px solid rgba(233, 195, 73, 0.15);
  color: #a1d2ab;
  padding: 10px 20px;
  border-radius: 99px;
  font-size: 12px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.category-btn:hover {
  border-color: #e9c349;
  color: #e9c349;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(233, 195, 73, 0.1);
}

.category-btn.active {
  background: linear-gradient(135deg, #bf953f 0%, #fcf6ba 50%, #aa771c 100%);
  color: #0f2415;
  border-color: transparent;
  font-weight: 800;
  box-shadow: 0 4px 20px rgba(233, 195, 73, 0.3);
}

.light-mode .category-btn {
  background: #ffffff;
  border-color: #cbd5e1;
  color: #475569;
}

.light-mode .category-btn:hover {
  border-color: #bf953f;
  color: #bf953f;
}

.light-mode .category-btn.active {
  background: linear-gradient(135deg, #bf953f 0%, #fcf6ba 50%, #aa771c 100%);
  color: #0f2415;
}

/* Animations */
.animate-fade-in {
  animation: fadeIn 1s ease-out;
}

.animate-slide-up {
  animation: slideUp 0.8s ease-out;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}

@keyframes slideUp {
  from { opacity: 0; transform: translateY(40px); }
  to { opacity: 1; transform: translateY(0); }
}

/* Vue transitions */
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
