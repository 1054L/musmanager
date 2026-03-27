<script setup>
import { ref, onMounted } from 'vue'
import { tournamentService, authService } from '../services/api'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import Popover from 'primevue/popover'
import MusLoader from '../components/MusLoader.vue'
import { useConfirm } from 'primevue/useconfirm'
import { useToast } from 'primevue/usetoast'
import ConfirmDialog from 'primevue/confirmdialog'

const { t } = useI18n()
const router = useRouter()
const confirm = useConfirm()
const toast = useToast()
const user = authService.getUser()
const tournaments = ref([])
const loading = ref(true)
const error = ref(null)
const copiedUuid = ref(null)
const posterOp = ref(null)
const hoveredPoster = ref(null)

const onPosterHover = (event, posterPath) => {
  hoveredPoster.value = posterPath
  posterOp.value.toggle(event)
}

const onPosterLeave = () => {
  posterOp.value.hide()
}

const confirmDelete = (event, tournament) => {
  confirm.require({
    target: event.currentTarget,
    message: t('dashboard.delete_confirm_msg'),
    header: t('dashboard.delete_confirm_title'),
    icon: 'pi pi-exclamation-triangle',
    rejectProps: {
        label: t('tournament_form.actions.cancel'),
        severity: 'secondary',
        outlined: true
    },
    acceptProps: {
        label: t('common.loading') === 'Kargatzen...' ? 'Ezabatu' : (t('common.loading') === 'Loading...' ? 'Delete' : 'Eliminar'),
        severity: 'danger'
    },
    accept: async () => {
      loading.value = true
      try {
        await tournamentService.deleteTournament(tournament.uuid)
        toast.add({ severity: 'success', summary: t('dashboard.delete_success'), life: 3000 })
        await fetchTournaments()
      } catch (e) {
        toast.add({ severity: 'error', summary: 'Error', detail: e.message, life: 5000 })
        loading.value = false
      }
    }
  })
}

const copyToClipboard = async (uuid) => {
  const url = `${window.location.origin}/tournament/${uuid}`
  try {
    await navigator.clipboard.writeText(url)
    copiedUuid.value = uuid
    setTimeout(() => {
      if (copiedUuid.value === uuid) copiedUuid.value = null
    }, 2000)
  } catch (err) {
    console.error('Failed to copy: ', err)
  }
}

const fetchTournaments = async () => {
  loading.value = true
  error.value = null
  try {
    // Artificial delay to ensure MusLoader is visible and premium feel is maintained
    await new Promise(resolve => setTimeout(resolve, 500))
    tournaments.value = await tournamentService.getManagedTournaments()
    
    // Debug info for dev console
    console.log('User ID from Auth:', user?.id)
    console.log('Tournaments loaded:', tournaments.value.length)
  } catch (e) {
    console.error('Error loading tournaments:', e)
    // Show detailed error if possible
    error.value = `${t('dashboard.error')}: ${e.message}`
  } finally {
    loading.value = false
  }
}

// Hidden debug toggle (triple click on subtitle)
const debugInfo = ref(null)
const showDebug = ref(false)
const toggleDebug = () => {
  showDebug.value = !showDebug.value
  if (showDebug.value) {
    debugInfo.value = {
      user: { ...user, password: '***' },
      apiUrl: '/api',
      localStorage: { ...localStorage },
      userAgent: navigator.userAgent
    }
  }
}

const getStatusSeverity = (status) => {
  switch (status) {
    case 'active': return 'success';
    case 'finished': return 'danger';
    case 'pending': return 'info';
    case 'draft': return 'secondary';
    default: return 'secondary';
  }
}

const quickActions = [
  { 
    title: t('dashboard.quick_actions.new_tournament'), 
    icon: 'pi pi-trophy', 
    route: '/admin/tournament/create',
    desc: t('dashboard.quick_actions.new_tournament_desc')
  },
  { 
    title: t('dashboard.quick_actions.new_team'), 
    icon: 'pi pi-users', 
    route: '/admin/team/create',
    desc: t('dashboard.quick_actions.new_team_desc')
  }
]

onMounted(fetchTournaments)
</script>

<template>
  <div class="dashboard-container">
    
    <!-- Welcome Header -->
    <header class="dashboard-header">
      <div class="header-content">
        <h1 class="mus-h1 italic">
          {{ t('dashboard.title') }} <span class="text-[#0fb361]">{{ t('dashboard.subtitle') }}</span>
        </h1>
        <p class="mus-p opacity-60 mt-4 cursor-pointer select-none" @click="toggleDebug">
          {{ t('dashboard.welcome', { name: user?.email.split('@')[0], count: tournaments.length }) }}
        </p>
        
        <!-- Debug Info Panel -->
        <div v-if="showDebug" class="debug-panel mt-4 p-4 mus-glass text-xs font-mono text-slate-400 overflow-auto max-h-[200px]">
          <pre>{{ JSON.stringify(debugInfo, null, 2) }}</pre>
        </div>
      </div>
    </header>

    <!-- Quick Actions Menu -->
    <section class="quick-actions">
      <h2 class="section-title">{{ t('dashboard.quick_actions_title') }}</h2>
      <div class="actions-grid">
        <div v-for="action in quickActions" :key="action.title" 
             @click="router.push(action.route)" 
             class="action-card mus-glass">
          <div class="action-icon">
            <i :class="action.icon"></i>
          </div>
          <div class="action-info">
            <span class="action-name">{{ action.title }}</span>
            <span class="action-desc">{{ action.desc }}</span>
          </div>
          <i class="pi pi-chevron-right action-arrow"></i>
        </div>
      </div>
    </section>

    <!-- Main Table Section -->
    <section class="tournaments-section mus-glass p-0 overflow-hidden">
      <div class="section-header p-8 border-b border-white/5 flex items-center justify-between">
        <h2 class="section-title m-0">{{ t('dashboard.my_tournaments') }}</h2>
        <div class="flex items-center gap-4">
           <button @click="fetchTournaments" class="refresh-btn" v-tooltip.top="t('dashboard.retry')">
             <i class="pi pi-refresh" :class="{ 'animate-spin': loading }"></i>
           </button>
        </div>
      </div>

      <div v-if="loading" class="p-8">
        <MusLoader />
      </div>

      <div v-else-if="error" class="p-8 text-center">
        <i class="pi pi-exclamation-triangle text-rose-500 text-3xl mb-4"></i>
        <p class="text-slate-400 font-bold uppercase tracking-widest text-xs mb-6">{{ error }}</p>
        <button @click="fetchTournaments" class="mus-button-primary scale-90">{{ t('dashboard.retry') }}</button>
      </div>

      <div v-else-if="tournaments.length === 0" class="empty-state-container p-12 text-center">
        <div class="empty-icon-wrapper mb-6">
          <i class="pi pi-trophy text-5xl opacity-10"></i>
        </div>
        <h3 class="text-xl font-black text-white italic uppercase mb-2">{{ t('dashboard.empty_title') }}</h3>
        <p class="text-slate-500 text-xs font-bold uppercase tracking-widest mb-8 max-w-sm mx-auto opacity-60">{{ t('dashboard.empty_subtitle') }}</p>
        <Button :label="t('dashboard.empty_cta')" icon="pi pi-plus" class="mus-btn-primary scale-110" 
                @click="router.push('/admin/tournament/create')" />
      </div>

      <DataTable v-else :value="tournaments" responsiveLayout="scroll" :paginator="true" :rows="8" 
        class="custom-table">
        
        <Column field="name" :header="t('dashboard.tournament')" sortable>
          <template #body="slotProps">
            <span class="tournament-name" @click="router.push(`/tournament/${slotProps.data.uuid}`)">
              {{ slotProps.data.name }}
            </span>
          </template>
        </Column>
        
        <Column field="type" :header="t('dashboard.category')" sortable>
          <template #body="slotProps">
             <div class="type-tag">
               {{ t('tournament_form.types.' + slotProps.data.type) }}
             </div>
          </template>
        </Column>
 
        <Column field="status" :header="t('dashboard.status')" sortable>
          <template #body="slotProps">
            <span class="status-indicator" :class="slotProps.data.status">
              {{ t('tournament_form.statuses.' + slotProps.data.status) }}
            </span>
          </template>
        </Column>
 
        <Column class="text-right pr-8">
          <template #body="slotProps">
             <div class="flex justify-end gap-2">
               <!-- Preview Poster -->
               <router-link v-if="slotProps.data.posterPath" 
                       :to="`/tournament/${slotProps.data.uuid}`"
                       class="row-action-btn"
                       @mouseenter="(e) => onPosterHover(e, slotProps.data.posterPath)"
                       @mouseleave="onPosterLeave"
                       v-tooltip.top="t('dashboard.view_poster')">
                 <i class="pi pi-eye"></i>
               </router-link>

               <!-- Copy Link -->
               <button class="row-action-btn" 
                       @click="copyToClipboard(slotProps.data.uuid)" 
                       v-tooltip.top="t('dashboard.copy_link_help')">
                 <i :class="copiedUuid === slotProps.data.uuid ? 'pi pi-check text-[#0fb361]' : 'pi pi-copy'"></i>
               </button>

               <!-- Edit -->
               <router-link :to="`/admin/tournament/${slotProps.data.uuid}/edit`"
                       class="row-action-btn"
                       v-tooltip.top="t('dashboard.manage')">
                 <i class="pi pi-pencil"></i>
               </router-link>

               <!-- Delete -->
               <button class="row-action-btn hover:!bg-rose-500/10 hover:!text-rose-500 hover:!border-rose-500/20"
                       @click="confirmDelete($event, slotProps.data)"
                       v-tooltip.top="t('common.loading') === 'Kargatzen...' ? 'Ezabatu' : (t('common.loading') === 'Loading...' ? 'Delete' : 'Eliminar')">
                 <i class="pi pi-trash"></i>
               </button>
             </div>
          </template>
        </Column>
      </DataTable>

      <!-- Poster Preview Popover -->
      <Popover ref="posterOp" class="poster-preview-popover">
        <div class="poster-preview-content">
          <img v-if="hoveredPoster" 
               :src="hoveredPoster" 
               alt="Poster">
        </div>
      </Popover>
    </section>

  </div>
</template>

<style scoped>
.dashboard-container {
  display: flex;
  flex-direction: column;
  gap: 60px;
}

.section-title {
  font-size: 10px;
  font-weight: 950;
  text-transform: uppercase;
  letter-spacing: 0.3em;
  color: #475569;
  margin-bottom: 24px;
}

/* Poster Preview Popover */
:deep(.poster-preview-popover) {
  background: rgba(10, 10, 10, 0.98);
  backdrop-filter: blur(20px);
  border: 1px solid rgba(255, 255, 255, 0.1);
  padding: 0;
  box-shadow: 0 30px 60px -12px rgba(0, 0, 0, 0.8);
  overflow: hidden;
  border-radius: 12px;
}

:deep(.poster-preview-popover::before),
:deep(.poster-preview-popover::after) {
  display: none !important; /* Remove the arrow for a cleaner "hover" look */
}

.poster-preview-content {
  width: 140px;
  height: 200px;
  overflow: hidden;
}

.poster-preview-content img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

/* Quick Actions Grid */
.actions-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 24px;
}

.action-card {
  padding: 24px;
  border-radius: 24px;
  display: flex;
  align-items: center;
  gap: 20px;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.165, 0.84, 0.44, 1);
  position: relative;
  overflow: hidden;
}

.action-card:hover {
  transform: translateY(-4px);
  border-color: rgba(15, 179, 97, 0.3);
  background: rgba(15, 179, 97, 0.03);
}

.action-icon {
  width: 48px;
  height: 48px;
  background: rgba(15, 179, 97, 0.1);
  border: 1px solid rgba(15, 179, 97, 0.2);
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #0fb361;
  font-size: 18px;
}

.action-info {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.action-name {
  color: white;
  font-weight: 900;
  text-transform: uppercase;
  font-style: italic;
  font-size: 14px;
  letter-spacing: -0.02em;
}

.action-desc {
  color: #64748b;
  font-size: 10px;
  font-weight: 600;
}

.action-arrow {
  margin-left: auto;
  color: #1e293b;
  font-size: 12px;
  transition: transform 0.3s;
}

.action-card:hover .action-arrow {
  color: #0fb361;
  transform: translateX(4px);
}

/* Table Customization */
.custom-table :deep(.p-datatable-wrapper) {
  background: transparent;
}

.custom-table :deep(.p-datatable-thead > tr > th) {
  background: rgba(255, 255, 255, 0.01);
  color: #475569;
  font-size: 9px;
  font-weight: 900;
  text-transform: uppercase;
  letter-spacing: 0.15em;
  padding: 20px 32px;
  border-color: rgba(255, 255, 255, 0.03);
}

.custom-table :deep(.p-datatable-tbody > tr) {
  background: transparent;
  color: #94a3b8;
  border-bottom: 1px solid rgba(255, 255, 255, 0.02);
  cursor: pointer;
  transition: all 0.2s;
}

.custom-table :deep(.p-datatable-tbody > tr:hover) {
  background: rgba(255, 255, 255, 0.02);
}

.custom-table :deep(.p-datatable-tbody > tr > td) {
  padding: 18px 32px;
  border: none;
}

.tournament-name {
  color: white;
  font-weight: 800;
  font-size: 13px;
  font-style: italic;
}

.type-tag {
  font-size: 8px;
  font-weight: 900;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  color: #64748b;
  background: rgba(255, 255, 255, 0.02);
  border: 1px solid rgba(255, 255, 255, 0.05);
  padding: 4px 10px;
  border-radius: 4px;
  width: fit-content;
}

.status-indicator {
  font-size: 8px;
  font-weight: 900;
  text-transform: uppercase;
  display: flex;
  align-items: center;
  gap: 8px;
}

.status-indicator::before {
  content: '';
  width: 6px;
  height: 6px;
  border-radius: 50%;
}

.status-indicator.active { color: #0fb361; }
.status-indicator.active::before { background: #0fb361; box-shadow: 0 0 10px #0fb361; }

.status-indicator.pending { color: #f4d125; }
.status-indicator.pending::before { background: #f4d125; }

.status-indicator.draft { color: #64748b; }
.status-indicator.draft::before { background: #64748b; }

.refresh-btn {
  background: transparent;
  border: none;
  color: #475569;
  cursor: pointer;
  font-size: 14px;
  transition: color 0.3s;
}

.refresh-btn:hover {
  color: #0fb361;
}

.row-action-btn {
  background: rgba(255, 255, 255, 0.02);
  border: 1px solid rgba(255, 255, 255, 0.05);
  width: 32px;
  height: 32px;
  border-radius: 8px;
  color: #64748b !important;
  cursor: pointer;
  transition: all 0.3s;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  text-decoration: none !important;
  padding: 0;
  line-height: 1;
}

.row-action-btn:hover {
  background: #0fb361;
  color: #050505;
  border-color: #0fb361;
}

.empty-state-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 400px;
}

.empty-icon-wrapper {
  width: 100px;
  height: 100px;
  background: rgba(255, 255, 255, 0.01);
  border: 1px solid rgba(255, 255, 255, 0.03);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 32px;
}

@keyframes rotate {
  to { transform: rotate(360deg); }
}
</style>
