import fs from 'fs';

const file = 'c:\\code\\iosu\\mus\\web\\src\\views\\TournamentView.vue';
let content = fs.readFileSync(file, 'utf8');

// 1. Script changes: replace expandedStages with activeStageTab and Watcher
const oldScriptTarget = `const expandedStages = ref({})
const toggleStage = (stage) => { expandedStages.value[stage] = !expandedStages.value[stage] }

const knockoutStages = ['Cuartos de Final', 'Semifinales', 'Final', '3er y 4º Puesto'];

const groupedMatches = computed(() => {
  if (!matches.value.length) return []
  const acc = {}
  matches.value.forEach(match => {
    const stage = match.stage || 'General'
    if (knockoutStages.includes(stage)) return; // Exclude from normal list
    if (!acc[stage]) acc[stage] = []
    acc[stage].push(match)
  })
  
  // Set all to expanded by default
  const entries = Object.entries(acc).reverse();
  entries.forEach(([s]) => {
    if (expandedStages.value[s] === undefined) expandedStages.value[s] = true;
  });
  
  return entries;
})`;

const newScriptTarget = `const activeStageTab = ref(null);

const knockoutStages = ['Cuartos de Final', 'Semifinales', 'Final', '3er y 4º Puesto'];

const groupedMatches = computed(() => {
  if (!matches.value.length) return []
  const acc = {}
  matches.value.forEach(match => {
    const stage = match.stage || 'General'
    if (knockoutStages.includes(stage)) return; // Exclude from normal list
    if (!acc[stage]) acc[stage] = []
    acc[stage].push(match)
  })
  
  const entries = Object.entries(acc).reverse();
  return entries;
});

// Auto-select first stage tab when matches load
watch(groupedMatches, (newGroups) => {
  if (newGroups.length > 0 && !activeStageTab.value) {
    activeStageTab.value = newGroups[0][0];
  }
}, { immediate: true });`;

content = content.replace(oldScriptTarget, newScriptTarget);

// Add 'watch' to imports if not there
if (!content.includes('watch,')) {
    content = content.replace('ref, computed, onMounted', 'ref, computed, onMounted, watch');
}

// 2. HTML changes: replace Accordions with Sub-Tabs
const oldHtmlTarget = `<div v-if="activeTab === 'matches'">
              <div class="grid">
                 <div v-for="([stage, stageMatches]) in groupedMatches" :key="stage" class="col-12 mb-4">
                   <button @click="toggleStage(stage)" class="w-full flex align-items-center justify-content-between p-4 bg-white/5 hover:bg-white/10 border border-white/10 rounded-xl transition-all cursor-pointer text-left mb-2 group">
                     <span class="text-[#0fb361] font-black italic uppercase tracking-[0.2em] text-sm flex align-items-center gap-3">
                       <i class="pi pi-calendar text-lg group-hover:scale-110 transition-transform"></i> {{ stage }}
                     </span>
                     <i class="pi text-slate-400" :class="expandedStages[stage] ? 'pi-chevron-up' : 'pi-chevron-down'"></i>
                   </button>
                   <div v-if="expandedStages[stage]" class="mus-table-wrapper rounded-xl overflow-hidden border border-white/5 bg-white/5 animation-fade-in mt-3">`;

const newHtmlTarget = `<div v-if="activeTab === 'matches'">
              <!-- Sub-tabs for Matchdays -->
              <div class="flex overflow-x-auto gap-3 mb-6 pb-2" style="scrollbar-width: none;">
                 <button v-for="([stage, _]) in groupedMatches" :key="'tab-'+stage"
                         @click="activeStageTab = stage"
                         class="px-5 py-2.5 rounded-full border transition-all whitespace-nowrap text-[11px] font-black italic uppercase tracking-widest cursor-pointer"
                         :class="activeStageTab === stage ? 'bg-[#0fb361]/20 text-[#0fb361] border-[#0fb361]/50 shadow-[0_0_15px_rgba(15,179,97,0.2)]' : 'bg-white/5 text-slate-400 border-white/10 hover:bg-white/10 hover:text-white'">
                    <i class="pi pi-calendar mr-2"></i>{{ stage }}
                 </button>
              </div>

              <div class="grid">
                 <div v-for="([stage, stageMatches]) in groupedMatches" :key="stage" class="col-12">
                   <div v-show="activeStageTab === stage" class="mus-table-wrapper rounded-2xl overflow-hidden border border-white/5 bg-white/5 animation-fade-in">`;

content = content.replace(oldHtmlTarget, newHtmlTarget);

fs.writeFileSync(file, content);
console.log('Tabs logic injected');
