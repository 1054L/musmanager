import fs from 'fs';

const file = 'c:\\code\\iosu\\mus\\web\\src\\views\\TournamentView.vue';
let content = fs.readFileSync(file, 'utf8');

const targetHtml = `<div v-if="activeTab === 'matches'">
              <div class="grid">
                 <div v-for="([stage, stageMatches]) in groupedMatches" :key="stage" class="col-12 mb-8">
                    <h4 class="text-[#0fb361] font-black italic uppercase tracking-[0.2em] mb-4 flex align-items-center gap-2 text-xs">
                       <i class="pi pi-calendar"></i> {{ stage }}
                    </h4>
                    <div class="mus-table-wrapper rounded-2xl overflow-hidden border border-white/5 bg-white/5">`;

const replaceHtml = `<div v-if="activeTab === 'matches'">
              <!-- Sub-tabs for Matchdays -->
              <div class="mus-tabs mb-6 overflow-x-auto hide-scrollbar">
                 <button v-for="([stage, _]) in groupedMatches" :key="'tab-'+stage"
                         @click="activeStageTab = stage"
                         class="mus-tab-btn" :class="{ active: activeStageTab === stage }">
                    {{ stage }}
                 </button>
              </div>

              <!-- Match Tables -->
              <div class="grid">
                 <div v-for="([stage, stageMatches]) in groupedMatches" :key="stage" class="col-12">
                   <div v-show="activeStageTab === stage" class="mus-table-wrapper rounded-2xl overflow-hidden border border-white/5 bg-white/5 animation-fade-in">`;

if (content.indexOf(targetHtml) !== -1) {
    content = content.replace(targetHtml, replaceHtml);
    fs.writeFileSync(file, content);
    console.log('Tabs correctly injected');
} else {
    console.log('Target not found! Current file mismatch. Here is the activeTab matches div:');
    const start = content.indexOf('<div v-if="activeTab === \'matches\'">');
    console.log(content.substring(start, start + 300));
}
