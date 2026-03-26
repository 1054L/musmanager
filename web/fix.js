import fs from 'fs';
const file = 'c:\\code\\iosu\\mus\\web\\src\\views\\TournamentView.vue';
let content = fs.readFileSync(file, 'utf8');

content = content.replace(
  '<div class="flex align-items-center gap-4">\n                                         <div class="w-8 h-8 rounded-lg bg-slate-800 border border-white/10 flex align-items-center justify-center text-[10px] font-black">A</div>\n                                         <span class="text-white font-black italic text-lg">{{ match.teamA }}</span>\n                                      </div>',
  '<div class="flex align-items-center gap-4 flex-1 min-w-0 pr-4">\n                                         <div class="w-8 h-8 min-w-[32px] rounded-lg bg-slate-800 border border-white/10 flex align-items-center justify-center text-[10px] font-black">A</div>\n                                         <span class="text-white font-black italic text-lg truncate w-full" :title="match.teamA">{{ match.teamA }}</span>\n                                      </div>'
);

content = content.replace(
  '<div class="flex align-items-center gap-4">\n                                         <div class="w-8 h-8 rounded-lg bg-slate-800 border border-white/10 flex align-items-center justify-center text-[10px] font-black">B</div>\n                                         <span class="text-white font-black italic text-lg">{{ match.teamB }}</span>\n                                      </div>',
  '<div class="flex align-items-center gap-4 flex-1 min-w-0 mt-4 pr-4">\n                                         <div class="w-8 h-8 min-w-[32px] rounded-lg bg-slate-800 border border-white/10 flex align-items-center justify-center text-[10px] font-black">B</div>\n                                         <span class="text-white font-black italic text-lg truncate w-full" :title="match.teamB">{{ match.teamB }}</span>\n                                      </div>'
);

content = content.replace(
  '<span class="text-2xl font-black italic" :class="match.scoreA > match.scoreB ? \'text-[#0fb361]\' : \'text-slate-600\'">{{ match.scoreA }}</span>',
  '<span class="text-2xl font-black italic flex-shrink-0 text-right w-12" :class="match.scoreA > match.scoreB ? \'text-[#0fb361]\' : \'text-slate-600\'">{{ match.scoreA }}</span>'
);

content = content.replace(
  '<span class="text-2xl font-black italic" :class="match.scoreB > match.scoreA ? \'text-[#0fb361]\' : \'text-slate-600\'">{{ match.scoreB }}</span>',
  '<span class="text-2xl font-black italic flex-shrink-0 text-right w-12" :class="match.scoreB > match.scoreA ? \'text-[#0fb361]\' : \'text-slate-600\'">{{ match.scoreB }}</span>'
);

content = content.replace(
  '<span class="text-[10px] font-black text-slate-700 tracking-widest">{{ match.time }}</span>',
  ''
);

fs.writeFileSync(file, content);
console.log('Fixed');
