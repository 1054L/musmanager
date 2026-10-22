<script setup>
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import GoogleAd from '../components/GoogleAd.vue'

const { t } = useI18n()

// Active section tab: 'rules', 'strategy', 'org'
const activeTab = ref('rules')

const changeTab = (tab) => {
  activeTab.value = tab
}
</script>

<template>
  <div class="reglas-view mus-container py-20">
    <!-- Header -->
    <section class="text-center mb-12 animate-fade-in">
      <h1 class="mus-h1 mb-6">{{ t('reglas_page.title') }}</h1>
      <p class="mus-p-gold text-2xl font-bold italic tracking-wider">{{ t('reglas_page.subtitle') }}</p>
      <div class="mus-divider mx-auto mt-8"></div>
    </section>

    <!-- Top Ad Block -->
    <div class="col-12 px-4 mb-10">
      <GoogleAd :key="'top-rules'" />
    </div>

    <!-- Tabs Navigation -->
    <div class="flex justify-center gap-4 mb-16 animate-fade-in">
      <button 
        @click="changeTab('rules')" 
        class="tab-btn px-6 py-3 rounded-full font-bold text-sm tracking-wider uppercase transition-all duration-300"
        :class="activeTab === 'rules' ? 'tab-btn-active' : 'tab-btn-inactive'"
      >
        {{ t('reglas_page.nav_rules') }}
      </button>
      <button 
        @click="changeTab('strategy')" 
        class="tab-btn px-6 py-3 rounded-full font-bold text-sm tracking-wider uppercase transition-all duration-300"
        :class="activeTab === 'strategy' ? 'tab-btn-active' : 'tab-btn-inactive'"
      >
        {{ t('reglas_page.nav_strategy') }}
      </button>
      <button 
        @click="changeTab('org')" 
        class="tab-btn px-6 py-3 rounded-full font-bold text-sm tracking-wider uppercase transition-all duration-300"
        :class="activeTab === 'org' ? 'tab-btn-active' : 'tab-btn-inactive'"
      >
        {{ t('reglas_page.nav_org') }}
      </button>
    </div>

    <!-- Content Sections -->
    <div class="content-container">
      <transition name="fade" mode="out-in">
        <!-- 1. Rules Tab -->
        <div v-if="activeTab === 'rules'" key="rules" class="grid gap-10 animate-fade-in">
          <!-- Intro Card -->
          <div class="mus-glass p-8 md:p-12 rounded-3xl border-emerald-500/10 bg-emerald-500/5">
            <h2 class="mus-h2 mb-6 text-xl tracking-wider text-left border-b border-emerald-500/20 pb-4 text-[#e9c349]">
              {{ t('reglas_page.rules_title') }}
            </h2>
            <p class="mus-p text-lg leading-relaxed mb-6">
              {{ t('reglas_page.rules_intro') }}
            </p>
          </div>

          <!-- Deck & Values -->
          <div class="grid lg:grid-cols-12 gap-8 items-stretch">
            <div class="lg:col-span-8 mus-glass p-8 md:p-10 rounded-3xl border-emerald-500/10">
              <h3 class="mus-h3 mb-4 text-[#a1d2ab] tracking-wider">{{ t('reglas_page.rules_deck_title') }}</h3>
              <p class="mus-p text-sm leading-relaxed text-[#ceead1]">
                {{ t('reglas_page.rules_deck_text') }}
              </p>
            </div>
            <!-- Info Box -->
            <div class="lg:col-span-4 mus-glass p-8 rounded-3xl border-[#e9c349]/20 bg-[#e9c349]/5 flex flex-col justify-center">
              <h4 class="text-xs font-bold text-[#e9c349] uppercase tracking-widest mb-2">{{ t('reglas_page.rules_variant_title') }}</h4>
              <p class="text-xs leading-relaxed text-[#ceead1]">
                {{ t('reglas_page.rules_variant_text') }}
              </p>
            </div>
          </div>

          <!-- The Four Lances -->
          <div class="mus-glass p-8 md:p-10 rounded-3xl border-emerald-500/10">
            <h3 class="mus-h3 mb-6 text-[#a1d2ab] tracking-wider">{{ t('reglas_page.rules_rounds_title') }}</h3>
            <p class="mus-p text-sm leading-relaxed text-[#ceead1] mb-8">
              {{ t('reglas_page.rules_rounds_intro') }}
            </p>

            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
              <!-- Lance 1 -->
              <div class="lance-card p-6 rounded-2xl border border-emerald-500/10 bg-emerald-500/5 flex flex-col gap-3">
                <span class="text-emerald-400 font-bold text-xs uppercase tracking-widest">{{ t('reglas_page.rules_lance_label', { num: 1 }) }}</span>
                <h4 class="font-bold text-base text-[#e9c349]">{{ t('reglas_page.rules_round1_title') }}</h4>
                <p class="text-xs leading-relaxed text-[#8b938a]">{{ t('reglas_page.rules_round1_desc') }}</p>
              </div>

              <!-- Lance 2 -->
              <div class="lance-card p-6 rounded-2xl border border-emerald-500/10 bg-emerald-500/5 flex flex-col gap-3">
                <span class="text-emerald-400 font-bold text-xs uppercase tracking-widest">{{ t('reglas_page.rules_lance_label', { num: 2 }) }}</span>
                <h4 class="font-bold text-base text-[#e9c349]">{{ t('reglas_page.rules_round2_title') }}</h4>
                <p class="text-xs leading-relaxed text-[#8b938a]">{{ t('reglas_page.rules_round2_desc') }}</p>
              </div>

              <!-- Lance 3 -->
              <div class="lance-card p-6 rounded-2xl border border-emerald-500/10 bg-emerald-500/5 flex flex-col gap-3">
                <span class="text-emerald-400 font-bold text-xs uppercase tracking-widest">{{ t('reglas_page.rules_lance_label', { num: 3 }) }}</span>
                <h4 class="font-bold text-base text-[#e9c349]">{{ t('reglas_page.rules_round3_title') }}</h4>
                <p class="text-xs leading-relaxed text-[#8b938a]">{{ t('reglas_page.rules_round3_desc') }}</p>
              </div>

              <!-- Lance 4 -->
              <div class="lance-card p-6 rounded-2xl border border-emerald-500/10 bg-emerald-500/5 flex flex-col gap-3">
                <span class="text-emerald-400 font-bold text-xs uppercase tracking-widest">{{ t('reglas_page.rules_lance_label', { num: 4 }) }}</span>
                <h4 class="font-bold text-base text-[#e9c349]">{{ t('reglas_page.rules_round4_title') }}</h4>
                <p class="text-xs leading-relaxed text-[#8b938a]">{{ t('reglas_page.rules_round4_desc') }}</p>
              </div>
            </div>
          </div>

          <!-- Betting Rules -->
          <div class="mus-glass p-8 md:p-10 rounded-3xl border-emerald-500/10">
            <h3 class="mus-h3 mb-4 text-[#a1d2ab] tracking-wider">{{ t('reglas_page.rules_betting_title') }}</h3>
            <p class="mus-p text-sm leading-relaxed text-[#ceead1]">
              {{ t('reglas_page.rules_betting_text') }}
            </p>
          </div>
        </div>

        <!-- 2. Strategy Tab -->
        <div v-else-if="activeTab === 'strategy'" key="strategy" class="grid gap-10 animate-fade-in">
          <!-- Intro Card -->
          <div class="mus-glass p-8 md:p-12 rounded-3xl border-emerald-500/10 bg-emerald-500/5">
            <h2 class="mus-h2 mb-6 text-xl tracking-wider text-left border-b border-emerald-500/20 pb-4 text-[#e9c349]">
              {{ t('reglas_page.strategy_title') }}
            </h2>
            <p class="mus-p text-lg leading-relaxed">
              {{ t('reglas_page.strategy_intro') }}
            </p>
          </div>

          <!-- Three Strategy Cards -->
          <div class="grid md:grid-cols-3 gap-8">
            <!-- Strategic Card 1 -->
            <div class="mus-card p-8 hover:bg-emerald-500/5 border-emerald-500/10 transition-all flex flex-col gap-4">
              <h3 class="mus-h3 text-[#0fb361]">{{ t('reglas_page.strategy_card1_title') }}</h3>
              <p class="text-xs leading-relaxed text-[#8b938a]">{{ t('reglas_page.strategy_card1_desc') }}</p>
            </div>

            <!-- Strategic Card 2 -->
            <div class="mus-card p-8 hover:bg-emerald-500/5 border-emerald-500/10 transition-all flex flex-col gap-4">
              <h3 class="mus-h3 text-[#0fb361]">{{ t('reglas_page.strategy_card2_title') }}</h3>
              <p class="text-xs leading-relaxed text-[#8b938a]">{{ t('reglas_page.strategy_card2_desc') }}</p>
            </div>

            <!-- Strategic Card 3 -->
            <div class="mus-card p-8 hover:bg-emerald-500/5 border-emerald-500/10 transition-all flex flex-col gap-4">
              <h3 class="mus-h3 text-[#0fb361]">{{ t('reglas_page.strategy_card3_title') }}</h3>
              <p class="text-xs leading-relaxed text-[#8b938a]">{{ t('reglas_page.strategy_card3_desc') }}</p>
            </div>
          </div>
        </div>

        <!-- 3. Tournament Organization Tab -->
        <div v-else-if="activeTab === 'org'" key="org" class="grid gap-10 animate-fade-in">
          <!-- Intro Card -->
          <div class="mus-glass p-8 md:p-12 rounded-3xl border-emerald-500/10 bg-emerald-500/5">
            <h2 class="mus-h2 mb-6 text-xl tracking-wider text-left border-b border-emerald-500/20 pb-4 text-[#e9c349]">
              {{ t('reglas_page.org_title') }}
            </h2>
            <p class="mus-p text-lg leading-relaxed">
              {{ t('reglas_page.org_intro') }}
            </p>
          </div>

          <!-- Three Org Cards -->
          <div class="grid md:grid-cols-3 gap-8">
            <!-- Strategic Card 1 -->
            <div class="mus-card p-8 hover:bg-emerald-500/5 border-emerald-500/10 transition-all flex flex-col gap-4">
              <h3 class="mus-h3 text-[#0fb361]">{{ t('reglas_page.org_card1_title') }}</h3>
              <p class="text-xs leading-relaxed text-[#8b938a]">{{ t('reglas_page.org_card1_desc') }}</p>
            </div>

            <!-- Strategic Card 2 -->
            <div class="mus-card p-8 hover:bg-emerald-500/5 border-emerald-500/10 transition-all flex flex-col gap-4">
              <h3 class="mus-h3 text-[#0fb361]">{{ t('reglas_page.org_card2_title') }}</h3>
              <p class="text-xs leading-relaxed text-[#8b938a]">{{ t('reglas_page.org_card2_desc') }}</p>
            </div>

            <!-- Strategic Card 3 -->
            <div class="mus-card p-8 hover:bg-emerald-500/5 border-emerald-500/10 transition-all flex flex-col gap-4">
              <h3 class="mus-h3 text-[#0fb361]">{{ t('reglas_page.org_card3_title') }}</h3>
              <p class="text-xs leading-relaxed text-[#8b938a]">{{ t('reglas_page.org_card3_desc') }}</p>
            </div>
          </div>
        </div>
      </transition>
    </div>

    <!-- Bottom Ad Block -->
    <div class="col-12 px-4 mt-12">
      <GoogleAd :key="'bottom-rules'" />
    </div>
  </div>
</template>

<style scoped>
.tab-btn {
  background: rgba(13, 36, 21, 0.4);
  border: 1px solid rgba(233, 195, 73, 0.15);
  color: #a1d2ab;
  cursor: pointer;
}

.tab-btn:hover {
  background: rgba(233, 195, 73, 0.05);
  border-color: rgba(233, 195, 73, 0.4);
  color: #e9c349;
}

.tab-btn-active {
  background: linear-gradient(135deg, #bf953f 0%, #aa771c 100%) !important;
  border-color: #e9c349 !important;
  color: #0d2415 !important;
  box-shadow: 0 0 15px rgba(233, 195, 73, 0.2);
}

.tab-btn-inactive {
  opacity: 0.8;
}

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

.lance-card {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.lance-card:hover {
  transform: translateY(-5px);
  border-color: rgba(233, 195, 73, 0.3);
  background: rgba(233, 195, 73, 0.02);
}

.light-mode .tab-btn {
  background: #f1f5f9;
  border-color: #cbd5e1;
  color: #475569;
}

.light-mode .tab-btn:hover {
  background: #e2e8f0;
  border-color: #bf953f;
  color: #bf953f;
}

.light-mode .tab-btn-active {
  background: linear-gradient(135deg, #bf953f 0%, #aa771c 100%) !important;
  border-color: #bf953f !important;
  color: #ffffff !important;
}

.light-mode .lance-card {
  background: #ffffff;
  border-color: #e2e8f0;
}

.light-mode .lance-card:hover {
  border-color: #bf953f;
}

/* Animations */
.animate-fade-in {
  animation: fadeIn 0.8s ease-out;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(15px); }
  to { opacity: 1; transform: translateY(0); }
}

/* Vue Transitions */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
