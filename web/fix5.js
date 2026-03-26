import fs from 'fs';

const file = 'c:\\code\\iosu\\mus\\web\\src\\views\\TournamentView.vue';
let content = fs.readFileSync(file, 'utf8');

// 1. Update bracketMatches computed property to group by pairs
const oldComputed = `const bracketMatches = computed(() => {
  const cols = [];
  ['Cuartos de Final', 'Semifinales', 'Final'].forEach(col => {
    const m = matches.value.filter(x => x.stage === col);
    if (m.length) cols.push({ stage: col, matches: m });
  });
  return cols;
})`;

const newComputed = `const bracketMatches = computed(() => {
  const cols = [];
  ['Cuartos de Final', 'Semifinales', 'Final'].forEach((colStage, colIndex) => {
    const m = matches.value.filter(x => x.stage === colStage);
    if (m.length) {
      const pairs = [];
      for (let i = 0; i < m.length; i += 2) {
        pairs.push(m.slice(i, i + 2));
      }
      cols.push({ stage: colStage, index: colIndex, pairs });
    }
  });
  return cols;
})`;

content = content.replace(oldComputed, newComputed);

// 2. Update the Bracket HTML template
const oldHtml = `<div class="flex overflow-x-auto py-8 px-2 gap-8 bracket-container justify-content-center">
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
                </div>`;

const newHtml = `<div class="flex overflow-x-auto py-8 px-2 gap-8 bracket-container justify-content-center">
                  <div v-for="col in bracketMatches" :key="col.stage" class="flex flex-column justify-content-around gap-0 min-w-[280px] w-[320px] relative">
                     <h4 class="text-center font-black uppercase tracking-widest text-[#0fb361] text-xs mb-4 bg-[#0fb361]/10 py-2 rounded absolute -top-12 w-full">{{ col.stage }}</h4>
                     <div class="flex-1 flex flex-column justify-content-center h-full">
                        <div v-for="(pair, pIndex) in col.pairs" :key="pIndex" class="flex flex-column justify-content-around relative flex-1 py-2">
                           
                           <div v-for="(m, mIndex) in pair" :key="mIndex" class="card bg-[#0a0a0a] border border-white/10 p-4 rounded-xl relative hover:border-[#0fb361]/50 cursor-pointer transition-colors z-10 m-2" @click="tournament.isManager ? openEditModal(m) : null">
                             <div class="flex align-items-center justify-content-between mb-3">
                               <span class="text-white font-bold text-sm truncate pr-2 w-10/12" :title="m.teamA">{{ m.teamA || 'Por decidir' }}</span>
                               <span class="font-black text-lg" :class="m.scoreA > m.scoreB ? 'text-[#0fb361]' : 'text-slate-500'">{{ m.scoreA }}</span>
                             </div>
                             <div class="h-px bg-white/10 w-full my-3"></div>
                             <div class="flex align-items-center justify-content-between">
                               <span class="text-white font-bold text-sm truncate pr-2 w-10/12" :title="m.teamB">{{ m.teamB || 'Por decidir' }}</span>
                               <span class="font-black text-lg" :class="m.scoreB > m.scoreA ? 'text-[#0fb361]' : 'text-slate-500'">{{ m.scoreB }}</span>
                             </div>
                             
                             <!-- Horizontal line out to the right (if not final) -->
                             <div v-if="col.stage !== 'Final'" class="absolute -right-4 top-1/2 w-4 h-px bg-[#0fb361]/40"></div>
                             <!-- Horizontal line in from the left (if not first column) -->
                             <div v-if="col.index !== 0" class="absolute -left-4 top-1/2 w-4 h-px bg-[#0fb361]/40"></div>
                           </div>

                           <!-- Vertical connecting line between pairs -->
                           <div v-if="pair.length === 2 && col.stage !== 'Final'" class="absolute -right-4 top-[25%] bottom-[25%] w-px bg-[#0fb361]/40"></div>
                           
                           <!-- Horizontal line connecting the vertical bar to the next match, extending right -->
                           <div v-if="pair.length === 2 && col.stage !== 'Final'" class="absolute -right-8 top-1/2 w-4 h-px bg-[#0fb361]/40"></div>
                        </div>
                     </div>
                  </div>
                </div>`;

content = content.replace(oldHtml, newHtml);

fs.writeFileSync(file, content);
console.log('Bracket lines fixed');
