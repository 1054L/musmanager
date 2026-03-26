import fs from 'fs';

const file = 'c:\\code\\iosu\\mus\\web\\src\\views\\TournamentView.vue';
let content = fs.readFileSync(file, 'utf8');

// 1. Add new refs and computeds
const scriptInsert = `
const expandedStages = ref({})
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
})

const bracketMatches = computed(() => {
  const cols = [];
  ['Cuartos de Final', 'Semifinales', 'Final'].forEach(col => {
    const m = matches.value.filter(x => x.stage === col);
    if (m.length) cols.push({ stage: col, matches: m });
  });
  return cols;
})

const thirdPlaceMatch = computed(() => matches.value.find(x => x.stage === '3er y 4º Puesto'))
`;

content = content.replace(
  'const groupedMatches = computed(() => {\n  if (!matches.value.length) return []\n  const acc = {}\n  matches.value.forEach(match => {\n    const stage = match.stage || \'General\'\n    if (!acc[stage]) acc[stage] = []\n    acc[stage].push(match)\n  })\n  return Object.entries(acc).reverse()\n})',
  scriptInsert
);


// 2. Change matches loop to Accordion style
const matchDisplayTarget = `<div class="grid">
                 <div v-for="([stage, stageMatches]) in groupedMatches" :key="stage" class="col-12 mb-8">
                   <h4 class="text-[#0fb361] font-black italic uppercase tracking-[0.2em] mb-4 flex align-items-center gap-2 text-xs">
                      <i class="pi pi-calendar"></i> {{ stage }}
                   </h4>
                   <div class="mus-table-wrapper rounded-2xl overflow-hidden border border-white/5 bg-white/5">`;

const matchDisplayReplace = `<div class="grid">
                 <div v-for="([stage, stageMatches]) in groupedMatches" :key="stage" class="col-12 mb-4">
                   <button @click="toggleStage(stage)" class="w-full flex align-items-center justify-content-between p-4 bg-white/5 hover:bg-white/10 border border-white/10 rounded-xl transition-all cursor-pointer text-left mb-2 group">
                     <span class="text-[#0fb361] font-black italic uppercase tracking-[0.2em] text-sm flex align-items-center gap-3">
                       <i class="pi pi-calendar text-lg group-hover:scale-110 transition-transform"></i> {{ stage }}
                     </span>
                     <i class="pi text-slate-400" :class="expandedStages[stage] ? 'pi-chevron-up' : 'pi-chevron-down'"></i>
                   </button>
                   <div v-if="expandedStages[stage]" class="mus-table-wrapper rounded-xl overflow-hidden border border-white/5 bg-white/5 animation-fade-in mt-3">`;

content = content.replace(matchDisplayTarget, matchDisplayReplace);


// 3. Replace the Bracket tab content
const bracketTarget = `<div v-else-if="activeTab === 'bracket'" class="text-center py-20 opacity-30">
              <i class="pi pi-sitemap text-6xl mb-4"></i>
              <h3 class="font-black italic uppercase">{{ t('tournament_view.bracket.title') }}</h3>
              <p>{{ t('tournament_view.bracket.empty_desc') }}</p>
           </div>`;

const bracketReplace = `<div v-else-if="activeTab === 'bracket'" class="py-6">
              <div v-if="bracketMatches.length === 0" class="text-center py-20 opacity-30">
                <i class="pi pi-sitemap text-6xl mb-4"></i>
                <h3 class="font-black italic uppercase">{{ t('tournament_view.bracket.title') }}</h3>
                <p>{{ t('tournament_view.bracket.empty_desc') }}</p>
              </div>
              <div v-else>
                <div class="flex overflow-x-auto py-8 px-2 gap-8 bracket-container justify-content-center">
                  <div v-for="col in bracketMatches" :key="col.stage" class="flex flex-column justify-content-around gap-6 min-w-[280px] w-[320px] relative">
                     <h4 class="text-center font-black uppercase tracking-widest text-[#0fb361] text-xs mb-2 bg-[#0fb361]/10 py-2 rounded">{{ col.stage }}</h4>
                     <div class="flex-1 flex flex-column justify-content-around gap-6">
                        <div v-for="(m, i) in col.matches" :key="i" class="card bg-[#0a0a0a] border border-white/10 p-4 rounded-xl relative hover:border-[#0fb361]/50 cursor-pointer transition-colors" @click="tournament.isManager ? openEditModal(m) : null">
                          <div class="flex align-items-center justify-content-between mb-3">
                            <span class="text-white font-bold text-sm truncate pr-2 w-10/12" :title="m.teamA">{{ m.teamA || 'Por decidir' }}</span>
                            <span class="font-black text-lg" :class="m.scoreA > m.scoreB ? 'text-[#0fb361]' : 'text-slate-500'">{{ m.scoreA }}</span>
                          </div>
                          <div class="h-px bg-white/10 w-full my-3"></div>
                          <div class="flex align-items-center justify-content-between">
                            <span class="text-white font-bold text-sm truncate pr-2 w-10/12" :title="m.teamB">{{ m.teamB || 'Por decidir' }}</span>
                            <span class="font-black text-lg" :class="m.scoreB > m.scoreA ? 'text-[#0fb361]' : 'text-slate-500'">{{ m.scoreB }}</span>
                          </div>
                          
                          <!-- Connector lines -->
                          <div v-if="col.stage !== 'Final'" class="absolute -right-4 top-1/2 w-4 h-px bg-[#0fb361]/30"></div>
                        </div>
                     </div>
                  </div>
                </div>

                <!-- 3er y 4o Puesto -->
                <div v-if="thirdPlaceMatch" class="mt-8 border-t border-white/10 pt-8 max-w-md mx-auto">
                  <h4 class="text-center font-black uppercase tracking-widest text-slate-500 text-xs mb-4">3er y 4º Puesto</h4>
                  <div class="card bg-[#0a0a0a] border border-[#f4d125]/30 p-4 rounded-xl cursor-pointer hover:border-[#f4d125]" @click="tournament.isManager ? openEditModal(thirdPlaceMatch) : null">
                     <div class="flex align-items-center justify-content-between mb-3">
                       <span class="text-white font-bold text-sm truncate pr-2" :title="thirdPlaceMatch.teamA">{{ thirdPlaceMatch.teamA || 'Por decidir' }}</span>
                       <span class="font-black text-lg" :class="thirdPlaceMatch.scoreA > thirdPlaceMatch.scoreB ? 'text-[#f4d125]' : 'text-slate-500'">{{ thirdPlaceMatch.scoreA }}</span>
                     </div>
                     <div class="h-px bg-white/10 w-full my-3"></div>
                     <div class="flex align-items-center justify-content-between">
                       <span class="text-white font-bold text-sm truncate pr-2" :title="thirdPlaceMatch.teamB">{{ thirdPlaceMatch.teamB || 'Por decidir' }}</span>
                       <span class="font-black text-lg" :class="thirdPlaceMatch.scoreB > thirdPlaceMatch.scoreA ? 'text-[#f4d125]' : 'text-slate-500'">{{ thirdPlaceMatch.scoreB }}</span>
                     </div>
                  </div>
                </div>
              </div>
           </div>`;

content = content.replace(bracketTarget, bracketReplace);

fs.writeFileSync(file, content);
console.log('Fixed layout');
