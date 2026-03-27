<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { tournamentService } from '../services/api'
import { useI18n } from 'vue-i18n'
import Card from 'primevue/card'
import Tag from 'primevue/tag'
import ProgressBar from 'primevue/progressbar'
import Button from 'primevue/button'
import Timeline from 'primevue/timeline'
import Dialog from 'primevue/dialog'
import GoogleAd from '../components/GoogleAd.vue'
import MusLoader from '../components/MusLoader.vue'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const activeTab = ref('matches')
const classification = ref({})
const tournament = ref(null)
const loading = ref(true)
const error = ref(null)
const matches = ref([])
const showActivity = ref(false)
const editingMatch = ref(null)
const editScore1 = ref(0)
const editScore2 = ref(0)
const isSavingResult = ref(false)


const activeStageTab = ref(null);

const knockoutStages = [
  t('tournament_view.knockout.t32'),
  t('tournament_view.knockout.t16'),
  t('tournament_view.knockout.t8'),
  t('tournament_view.knockout.t4'), 
  t('tournament_view.knockout.final'), 
  t('tournament_view.knockout.third_place')
];
const groupedMatches = computed(() => {
  if (!matches.value.length) return []
  const acc = {}
  matches.value.forEach(match => {
    const stage = match.stage || t('tournament_view.rounds.general')
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
}, { immediate: true });

const bracketMatches = computed(() => {
  const cols = [];
  const orderedTreeStages = [
    t('tournament_view.knockout.t32'),
    t('tournament_view.knockout.t16'),
    t('tournament_view.knockout.t8'),
    t('tournament_view.knockout.t4'), 
    t('tournament_view.knockout.final')
  ];
  
  orderedTreeStages.forEach(col => {
    const m = matches.value.filter(x => x.stage === col);
    if (m.length) cols.push({ stage: col, matches: m });
  });
  return cols;
})

const thirdPlaceMatch = computed(() => matches.value.find(x => x.stage === t('tournament_view.knockout.third_place')))


const fetchTournament = async () => {
  loading.value = true
  error.value = null
  try {
    const data = await tournamentService.getTournament(route.params.uuid)
    tournament.value = data
    if (data.matches) {
      matches.value = data.matches.map(m => ({
        id: m.id,
        status: m.score1 > 0 || m.score2 > 0 ? t('tournament_view.match_status.finished') : t('tournament_view.match_status.pending'),
        teamA: m.team1,
        scoreA: m.score1,
        teamB: m.team2,
        scoreB: m.score2,
        stage: m.stage
      }))
    }
    
    if (data.type === 'groups' || data.type === 'league') {
       classification.value = await tournamentService.getClassification(route.params.uuid)
    }

    // Set default tab for different tournament types
    if (data.type === 'eliminatory' && activeTab.value === 'matches') {
       activeTab.value = 'bracket'
    } else if (data.type === 'league' && activeTab.value === 'matches') {
       activeTab.value = 'standings'
    }
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}

onMounted(fetchTournament)

const openEditModal = (match) => {
  editingMatch.value = match
  editScore1.value = match.scoreA
  editScore2.value = match.scoreB
}

const saveMatchResult = async () => {
  isSavingResult.value = true
  try {
    await tournamentService.updateMatchScore(editingMatch.value.id, editScore1.value, editScore2.value)
    editingMatch.value = null
    await fetchTournament()
  } catch (e) {
    alert(e.message)
  } finally {
    isSavingResult.value = false
  }
}
</script>

<template>
  <div class="grid">
    <!-- Loading State -->
    <div v-if="loading" class="col-12 py-8">
       <MusLoader />
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="col-12 max-w-2xl mx-auto mt-8">
       <Card class="mus-glass border-rose-500/10 text-center p-8">
          <template #title>
             <h2 class="text-3xl font-black text-white italic uppercase tracking-tight">{{ t('tournament_view.restricted_access') }}</h2>
          </template>
          <template #content>
             <p class="text-slate-500 font-medium mb-6">{{ error }}</p>
             <Button :label="t('nav.dashboard')" icon="pi pi-arrow-left" @click="router.push('/dashboard')" class="p-button-secondary" />
          </template>
       </Card>
    </div>

    <!-- Main Tournament View -->
    <template v-else-if="tournament">
      <!-- Tournament Header Card -->
      <div class="col-12 mb-6">
        <div class="card p-4 md:p-5 mus-glass border-white/5 relative overflow-hidden">
          <!-- Draft Banner -->
          <div v-if="tournament.status === 'draft'" class="absolute inset-0 z-0 flex items-center justify-center pointer-events-none opacity-5 overflow-hidden">
             <div class="rotate-[-15deg] text-[150px] font-black whitespace-nowrap uppercase tracking-[0.2em]">
                {{ t('tournament_view.draft_mode') }}
             </div>
          </div>
          <div class="absolute top-0 right-0 w-1/2 h-full bg-gradient-to-l from-[#0fb361]/5 to-transparent"></div>
          
          <div class="relative z-10 flex flex-column md:flex-row justify-content-between align-items-center gap-6">
            <div class="flex-1">
              <div class="flex align-items-center gap-3 mb-2">
                <Tag :value="t('tournament_form.statuses.' + tournament.status)" severity="success" class="italic font-black uppercase text-[8px] tracking-widest" />
              </div>
              <h1 class="text-3xl md:text-4xl font-black text-white tracking-tight italic uppercase leading-tight m-0">{{ tournament.name }}</h1>
              <p class="text-slate-500 text-xs font-bold uppercase tracking-widest m-0 flex align-items-center gap-4">
                <span><i class="pi pi-user mr-2 text-[#0fb361]"></i>{{ t('tournament_view.pairs_count', { count: tournament.teamsCount || 0 }) }}</span>
                <span><i class="pi pi-calendar mr-2 text-[#0fb361]"></i>{{ tournament.startDate ? new Date(tournament.startDate).toLocaleDateString() : '...' }} - {{ tournament.endDate ? new Date(tournament.endDate).toLocaleDateString() : '...' }}</span>
                <span v-if="tournament.location"><i class="pi pi-map-marker mr-2 text-[#0fb361]"></i>{{ tournament.location }}</span>
              </p>
            </div>

            <div class="flex gap-4 align-items-center bg-white/5 p-3 rounded-2xl border border-white/5">
                <div class="rule-icon-item" v-tooltip.top="t('tournament_form.labels.ruleKings') + ': ' + tournament.ruleKings">
                  <i class="pi pi-crown text-xl text-[#0fb361]"></i>
                  <span class="text-xs font-black ml-2">{{ tournament.ruleKings }}</span>
                </div>
                <div class="rule-icon-item border-l border-white/10 pl-4" v-tooltip.top="t('tournament_form.labels.rulePoints') + ': ' + tournament.rulePoints">
                  <i class="pi pi-hashtag text-xl text-[#0fb361]"></i>
                  <span class="text-xs font-black ml-2">{{ tournament.rulePoints }}</span>
                </div>
                <div class="rule-icon-item border-l border-white/10 pl-4" v-tooltip.top="t('tournament_form.labels.ruleGames') + ': ' + tournament.ruleGames">
                  <i class="pi pi-bolt text-xl text-[#0fb361]"></i>
                  <span class="text-xs font-black ml-2">{{ tournament.ruleGames }}</span>
                </div>
                <div v-if="tournament.tablesCount" class="rule-icon-item border-l border-white/10 pl-4" v-tooltip.top="t('tournament_form.labels.tablesCount') + ': ' + tournament.tablesCount">
                  <i class="pi pi-table text-xl text-[#f4d125]"></i>
                  <span class="text-xs font-black ml-2 text-[#f4d125]">{{ tournament.tablesCount }}</span>
                </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Google Ad Slot (Top) -->
      <div class="col-12 px-4">
        <GoogleAd :key="'top-' + activeTab" />
      </div>

      <!-- Main Content Container -->
      <div :class="showActivity ? 'col-12 lg:col-8' : 'col-12'">
        <div class="card p-6 mus-glass border-white/5">
           <div class="flex gap-4 mb-2 border-b border-white/5 pb-4">
              <button v-if="tournament.type !== 'eliminatory'" @click="activeTab = 'matches'" 
                      class="mus-tab-btn" :class="{ active: activeTab === 'matches' }">
                {{ t('tournament_view.tabs.matches') }}
              </button>
              <button v-if="tournament.type === 'groups' || tournament.type === 'league'" @click="activeTab = 'standings'" 
                      class="mus-tab-btn" :class="{ active: activeTab === 'standings' }">
                {{ t('tournament_view.tabs.standings') }}
              </button>
              <button v-if="tournament.type !== 'league'" @click="activeTab = 'bracket'" 
                      class="mus-tab-btn" :class="{ active: activeTab === 'bracket' }">
                {{ t('tournament_view.tabs.bracket') }}
              </button>
              <button @click="activeTab = 'teams'" 
                      class="mus-tab-btn" :class="{ active: activeTab === 'teams' }">
                {{ t('tournament_view.tabs.teams') }}
              </button>
           </div>

           <!-- Content according to active tab -->
           <div v-if="activeTab === 'matches'">
              <div class="grid">
                <div v-for="([stage, stageMatches]) in groupedMatches" :key="stage" class="col-12 mb-8">
                   <h4 class="text-[#0fb361] font-black italic uppercase tracking-[0.2em] mb-4 flex align-items-center gap-2 text-xs">
                      <i class="pi pi-calendar"></i> {{ stage }}
                   </h4>
                   <div class="mus-table-wrapper rounded-2xl overflow-hidden border border-white/5 bg-white/5">
                      <table class="w-full text-left border-collapse">
                         <tbody>
                            <tr v-for="(match, i) in stageMatches" :key="i" class="border-b border-white/10 last:border-0 hover:bg-white/5 transition-colors group">
                               <td class="p-4 w-7/12">
                                  <div class="flex flex-column gap-3">
                                     <div class="flex align-items-center gap-3">
                                        <div class="w-6 h-6 flex-shrink-0 rounded bg-slate-800 border border-white/10 flex align-items-center justify-center text-[9px] font-black">A</div>
                                        <span class="text-white font-black italic text-base truncate" :title="match.teamA">{{ match.teamA }}</span>
                                     </div>
                                     <div class="flex align-items-center gap-3">
                                        <div class="w-6 h-6 flex-shrink-0 rounded bg-slate-800 border border-white/10 flex align-items-center justify-center text-[9px] font-black">B</div>
                                        <span class="text-white font-black italic text-base truncate" :title="match.teamB">{{ match.teamB }}</span>
                                     </div>
                                  </div>
                               </td>
                               <td class="p-4 w-2/12 border-l border-white/5 align-middle">
                                  <div class="flex flex-column gap-3 text-center">
                                     <span class="text-xl font-black italic" :class="match.scoreA > match.scoreB ? 'text-[#0fb361]' : 'text-slate-600'">{{ match.scoreA }}</span>
                                     <span class="text-xl font-black italic" :class="match.scoreB > match.scoreA ? 'text-[#0fb361]' : 'text-slate-600'">{{ match.scoreB }}</span>
                                  </div>
                               </td>
                               <td class="p-4 w-3/12 border-l border-white/5 align-middle text-center">
                                  <div class="flex flex-column align-items-center justify-center gap-2">
                                     <Tag :value="match.status" :severity="match.status === t('tournament_view.match_status.finished') ? 'success' : 'secondary'" class="text-[8px] whitespace-nowrap" />
                                     <button v-if="tournament.isManager" 
                                             @click="openEditModal(match)"
                                             class="mt-2 text-[9px] font-black text-[#0fb361] uppercase border border-[#0fb361]/30 px-2 py-1 rounded bg-[#0fb361]/5 hover:bg-[#0fb361]/20 transition-all whitespace-nowrap">
                                       {{ t('dashboard.manage') }}
                                     </button>
                                  </div>
                               </td>
                            </tr>
                         </tbody>
                      </table>
                   </div>
                </div>
              </div>
              <!-- Inner Ad -->
              <GoogleAd :key="'inner-' + activeTab" />
           </div>

           <div v-else-if="activeTab === 'standings'" class="classification-container">
             <div v-for="(teams, groupName) in classification" :key="groupName" class="mb-10">
               <h4 class="text-[#0fb361] font-black italic uppercase tracking-widest mb-4 flex items-center gap-2">
                 <i class="pi pi-table"></i> {{ groupName }}
               </h4>
               <div class="mus-table-wrapper rounded-2xl overflow-hidden border border-white/5">
                 <table class="w-full text-left border-collapse">
                   <thead>
                     <tr class="bg-white/5 text-[10px] uppercase font-black tracking-widest text-slate-500">
                       <th class="p-4 w-5/12 min-w-[200px]">{{ t('tournament_view.classification.team') }}</th>
                       <th class="p-4 text-center">{{ t('tournament_view.classification.played') }}</th>
                       <th class="p-4 text-center">{{ t('tournament_view.classification.won') }}</th>
                       <th class="p-4 text-center">{{ t('tournament_view.classification.lost') }}</th>
                       <th class="p-4 text-center text-white">{{ t('tournament_view.classification.points') }}</th>
                     </tr>
                   </thead>
                   <tbody>
                     <tr v-for="team in teams" :key="team.teamName" class="border-t border-white/5 hover:bg-white/5 transition-colors">
                       <td class="p-4 font-bold text-white">{{ team.teamName }}</td>
                       <td class="p-4 text-center text-slate-400">{{ team.played }}</td>
                       <td class="p-4 text-center text-emerald-500">{{ team.won }}</td>
                       <td class="p-4 text-center text-rose-500">{{ team.lost }}</td>
                       <td class="p-4 text-center font-black text-[#0fb361]">{{ team.points }}</td>
                     </tr>
                   </tbody>
                 </table>
               </div>
             </div>
             <div v-if="Object.keys(classification).length === 0" class="text-center py-10 opacity-30">
               <i class="pi pi-info-circle text-4xl mb-4"></i>
               <p class="font-bold">{{ t('tournament_view.classification.empty') }}</p>
             </div>
           </div>

           <div v-else-if="activeTab === 'bracket'" class="py-6">
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
           </div>

           <div v-else-if="activeTab === 'teams'">
              <div v-if="tournament.tournamentTeams?.length" class="mus-table-wrapper rounded-2xl overflow-hidden border border-white/5 bg-white/5">
                 <table class="w-full text-left border-collapse">
                    <tbody>
                       <tr v-for="tt in tournament.tournamentTeams" :key="tt.id" class="border-b border-white/10 last:border-0 hover:bg-white/5 transition-colors group">
                          <td class="p-4 w-16 text-center border-r border-white/5">
                             <div class="w-10 h-10 mx-auto rounded-full bg-[#0fb361]/10 border border-[#0fb361]/20 flex align-items-center justify-center">
                                <i class="pi pi-users text-[#0fb361]"></i>
                             </div>
                          </td>
                          <td class="p-4 font-bold text-white text-lg">
                             {{ tt.team?.name }}
                          </td>
                          <td class="p-4 text-right w-1/3 border-l border-white/5">
                             <span class="text-slate-400 text-xs font-black uppercase tracking-widest">{{ tt.groupName || '-' }}</span>
                          </td>
                       </tr>
                    </tbody>
                 </table>
              </div>
              
              <div v-if="!tournament.tournamentTeams?.length" class="text-center py-10 opacity-30">
                 <i class="pi pi-users text-4xl mb-4"></i>
                 <p class="font-bold">{{ t('tournament_view.teams.empty') }}</p>
              </div>
           </div>
        </div>
      </div>

      <!-- Sidebar -->
      <div v-if="showActivity" class="col-12 lg:col-4">
        <div class="card p-6 mus-glass border-white/5 h-full">
           <h3 class="text-xl font-black text-white italic uppercase mb-6 leading-none">{{ t('tournament_view.activity.title') }}</h3>
           <Timeline :value="[
             { icon: 'pi pi-check', color: '#0fb361', message: t('tournament_view.activity.match_closed', { table: 4, team: 'Alpha', score: '40-12' }), time: t('tournament_view.activity.time_5m') },
             { icon: 'pi pi-play', color: '#f4d125', message: t('tournament_view.activity.match_started', { table: 1, team1: 'Kings', team2: 'Jokers' }), time: t('tournament_view.activity.time_12m') },
             { icon: 'pi pi-user', color: '#3b82f6', message: t('tournament_view.activity.player_registered', { name: 'Blind Betty' }), time: t('tournament_view.activity.time_20m') }
           ]">
              <template #marker="slotProps">
                 <span class="flex align-items-center justify-center border-circle w-8 h-8 z-1" :style="{ backgroundColor: slotProps.item.color + '20', color: slotProps.item.color, border: '1px solid ' + slotProps.item.color + '40' }">
                    <i :class="slotProps.item.icon" class="text-xs"></i>
                 </span>
              </template>
              <template #content="slotProps">
                 <div class="mb-6 ml-4">
                    <p class="text-white text-xs font-black italic m-0 leading-tight">{{ slotProps.item.message }}</p>
                    <small class="text-slate-600 font-bold uppercase tracking-widest text-[8px]">{{ slotProps.item.time }}</small>
                 </div>
              </template>
           </Timeline>
        </div>
      </div>
    </template>

    <!-- Dialogs -->
    <Dialog v-model:visible="editingMatch" modal :header="t('tournament_view.match_edit.title')" :style="{ width: '400px' }" class="mus-dialog">
      <div v-if="editingMatch" class="p-4">
        <div class="flex flex-column gap-6">
          <div class="flex align-items-center justify-between p-4 bg-white/5 rounded-2xl border border-white/5">
            <span class="font-black text-white italic">{{ editingMatch.teamA }}</span>
            <input v-model.number="editScore1" type="number" class="mus-input w-20 text-center text-xl font-black" min="0" max="40">
          </div>
          <div class="flex align-items-center justify-content-center opacity-20">
            <div class="h-px bg-white flex-1"></div>
            <span class="mx-4 text-xs font-black uppercase tracking-widest">VS</span>
            <div class="h-px bg-white flex-1"></div>
          </div>
          <div class="flex align-items-center justify-content-between p-4 bg-white/5 rounded-2xl border border-white/5">
            <span class="font-black text-white italic">{{ editingMatch.teamB }}</span>
            <input v-model.number="editScore2" type="number" class="mus-input w-20 text-center text-xl font-black" min="0" max="40">
          </div>
        </div>
        <div class="mt-8 flex gap-4">
          <Button :label="t('tournament_form.actions.cancel')" text @click="editingMatch = null" class="flex-1 p-button-secondary font-black uppercase text-xs" />
          <Button :label="t('tournament_view.match_edit.save')" @click="saveMatchResult" :loading="isSavingResult" class="flex-1 mus-btn-primary" />
        </div>
      </div>
    </Dialog>
  </div>
</template>

<style scoped>
:deep(.p-timeline-event-opposite) {
  display: none;
}
:deep(.p-timeline-event-content) {
  padding-bottom: 2rem;
}

.mus-tab-btn {
  background: none; border: none; padding: 10px 20px;
  font-size: 11px; font-weight: 900; text-transform: uppercase;
  letter-spacing: 0.15em; color: #475569; cursor: pointer;
  transition: all 0.3s; position: relative;
}
.mus-tab-btn:hover { color: #94a3b8; }
.mus-tab-btn.active { color: white; }
.mus-tab-btn.active::after {
  content: ''; position: absolute; bottom: -4px; left: 20px; right: 20px;
  height: 2px; background: #0fb361; border-radius: 2px;
  box-shadow: 0 0 10px #0fb361;
}

.mus-table-wrapper { background: rgba(0,0,0,0.2); }
th { border-bottom: 1px solid rgba(255,255,255,0.05); }

.rule-icon-item {
  display: flex;
  align-items: center;
  gap: 4px;
  cursor: help;
  transition: opacity 0.2s;
}
.rule-icon-item:hover {
  opacity: 0.8;
}
</style>
