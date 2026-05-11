<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { tournamentService, teamService } from '../services/api'
import { useI18n } from 'vue-i18n'
import Dropdown from 'primevue/dropdown'
import { useLocationStore } from '../stores/locationStore'
import MusLoader from '../components/MusLoader.vue'

const locationStore = useLocationStore()
const { t } = useI18n()
const router = useRouter()
const route = useRoute()
const uuid = route.params.uuid

const form = ref({
  name: '',
  type: 'eliminatory',
  status: 'draft',
  statusDescription: '',
  startDate: '',
  endDate: '',
  ruleKings: 8,
  rulePoints: 40,
  ruleGames: 3,
  tablesCount: null,
  location: '',
  provinceId: null,
  townId: null,
  poster: null,
  rulesFile: null,
  private: false
})
const existingPosterPath = ref(null)
const existingRulesPath = ref(null)
const posterPreview = ref(null)
const loading = ref(true)
const saving = ref(false)
const error = ref(null)
const success = ref(false)
const enrolledTeams = ref([])
const availableTeams = ref([])
const enrollmentTeamId = ref(null)
const groupsCount = ref(2)
const generating = ref(false)

const isOtherPoints = ref(false)
const isOtherGames = ref(false)

const setPoints = (val) => {
  if (val === 'other') {
    isOtherPoints.value = true
  } else {
    isOtherPoints.value = false
    form.value.rulePoints = val
  }
}

const setGames = (val) => {
  if (val === 'other') {
    isOtherGames.value = true
  } else {
    isOtherGames.value = false
    form.value.ruleGames = val
  }
}

const filteredTowns = ref([])

const onProvinceChange = (isManual = true) => {
  if (form.value.provinceId) {
    filteredTowns.value = locationStore.getTownsByProvince(form.value.provinceId)
  } else {
    filteredTowns.value = []
  }
  if (isManual) {
    form.value.townId = null
  }
}

watch(() => form.value.provinceId, () => onProvinceChange(true))

const types = computed(() => [
  { value: 'eliminatory', label: t('tournament_form.types.eliminatory') },
  { value: 'league', label: t('tournament_form.types.league') },
  { value: 'groups', label: t('tournament_form.types.groups') }
])

const statuses = computed(() => [
  { value: 'draft', label: t('tournament_form.statuses.draft') },
  { value: 'pending', label: t('tournament_form.statuses.pending') },
  { value: 'active', label: t('tournament_form.statuses.active') },
  { value: 'finished', label: t('tournament_form.statuses.finished') }
])

onMounted(async () => {
  try {
    const data = await tournamentService.getTournament(uuid)
    form.value = {
      name: data.name || '',
      type: data.type || 'eliminatory',
      status: data.status || 'draft',
      statusDescription: data.statusDescription || '',
      startDate: data.startDate ? data.startDate.substring(0, 16) : '',
      endDate: data.endDate ? data.endDate.substring(0, 16) : '',
      ruleKings: data.ruleKings || 8,
      rulePoints: data.rulePoints || 40,
      ruleGames: data.ruleGames || 3,
      tablesCount: data.tablesCount,
      location: data.location || '',
      provinceId: data.provinceId || null,
      townId: data.townId || null,
      poster: null,
      rulesFile: null,
      private: data.private || false
    }
    existingPosterPath.value = data.posterPath
    existingRulesPath.value = data.rulesPath
    
    // Initialize towns list based on loaded province
    onProvinceChange(false)
    
    if (![20, 30, 40].includes(form.value.rulePoints)) isOtherPoints.value = true
    if (![3, 4, 5].includes(form.value.ruleGames)) isOtherGames.value = true

    await loadEnrolledTeams()
    await loadAvailableTeams()
  } catch (e) {
    error.value = t('tournament_form.messages.load_error', { uuid: uuid, error: e.message })
  } finally {
    loading.value = false
  }
})

const loadEnrolledTeams = async () => {
  try {
    const data = await tournamentService.getTournament(uuid)
    enrolledTeams.value = data.tournamentTeams || []
  } catch (e) { console.error(e) }
}

const loadAvailableTeams = async () => {
  try {
    availableTeams.value = await teamService.getTeams()
  } catch (e) { console.error(e) }
}

const handleEnrollTeam = async () => {
  if (!enrollmentTeamId.value) return
  try {
    await tournamentService.enrollTeam(uuid, enrollmentTeamId.value)
    await loadEnrolledTeams()
    enrollmentTeamId.value = null
  } catch (e) { error.value = e.message }
}

const handleGenerateGroups = async () => {
  generating.value = true
  try {
    await tournamentService.generateGroups(uuid, groupsCount.value)
    await loadEnrolledTeams()
  } catch (e) { error.value = e.message }
  finally { generating.value = false }
}

const handleGenerateMatches = async () => {
  generating.value = true
  try {
    await tournamentService.generateMatches(uuid)
    success.value = true
  } catch (e) { error.value = e.message }
  finally { generating.value = false }
}

const onFileChange = (e) => {
  const file = e.target.files[0]
  if (file) {
    form.value.poster = file
    if (file.type.startsWith('image/')) {
      posterPreview.value = URL.createObjectURL(file)
    } else {
      posterPreview.value = null
    }
  }
}

const onRulesFileChange = (e) => {
  const file = e.target.files[0]
  if (file) {
    form.value.rulesFile = file
  }
}

const handleSave = async () => {
  saving.value = true
  error.value = null
  success.value = false
  try {
    const formData = new FormData()
    formData.append('name', form.value.name)
    formData.append('type', form.value.type)
    formData.append('status', form.value.status)
    if (form.value.statusDescription) formData.append('statusDescription', form.value.statusDescription)
    if (form.value.startDate) formData.append('startDate', form.value.startDate)
    if (form.value.endDate) formData.append('endDate', form.value.endDate)
    formData.append('ruleKings', form.value.ruleKings)
    formData.append('rulePoints', form.value.rulePoints)
    formData.append('ruleGames', form.value.ruleGames)
    if (form.value.tablesCount !== null) formData.append('tablesCount', form.value.tablesCount)
    
    // Geographic data
    if (form.value.location) formData.append('location', form.value.location)
    if (form.value.provinceId) formData.append('provinceId', form.value.provinceId)
    if (form.value.townId) formData.append('townId', form.value.townId)
    
    // Files
    if (form.value.poster) formData.append('poster', form.value.poster)
    if (form.value.rulesFile) formData.append('rulesFile', form.value.rulesFile)
    
    formData.append('private', form.value.private)

    await tournamentService.updateTournament(uuid, formData)
    success.value = true
    setTimeout(() => router.push('/dashboard'), 1200)
  } catch (e) {
    error.value = e.message
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <div class="form-page">
    <header class="form-header mb-8">
      <h1 class="mus-h1 italic text-2xl mb-1">
        EDITAR <span class="mus-gold-text">TORNEO</span>
      </h1>
      <p class="uuid-badge opacity-40">{{ $t('tournament_view.uuid') }}: {{ uuid }}</p>
    </header>

    <div v-if="loading" class="form-card mus-glass flex items-center justify-center" style="min-height:600px;">
      <MusLoader />
    </div>

    <div v-else class="form-card mus-glass relative overflow-hidden">
      <div v-if="saving" class="absolute inset-0 z-50 flex flex-column items-center justify-center bg-black/60 backdrop-blur-sm rounded-3xl">
        <MusLoader />
        <p class="mt-4 text-secondary font-black italic uppercase tracking-widest text-xs animate-pulse">
          {{ t('tournament_form.actions.saving') }}
        </p>
      </div>

      <form @submit.prevent="handleSave" class="mus-form">
        <div v-if="error" class="error-msg mb-8">{{ error }}</div>
        <div v-if="success" class="success-msg mb-8">
          <i class="pi pi-check-circle mr-2"></i> {{ $t('tournament_form.messages.save_success') }}
        </div>

        <div class="dual-column-grid">
          <!-- COLUMN 1: BASIS & LOGISTICS -->
          <div class="column">
            <section class="form-section">
              <h3 class="section-title"><i class="pi pi-info-circle mr-2"></i> {{ t('tournament_form.sections.identity') }}</h3>
              <div class="form-group">
                <label class="mus-label">{{ $t('tournament_form.labels.name') }} *</label>
                <input v-model="form.name" type="text" required class="mus-input">
              </div>
            </section>

            <section class="form-section">
              <h3 class="section-title"><i class="pi pi-map-marker mr-2"></i> {{ t('tournament_form.sections.location') }}</h3>
              <div class="grid grid-cols-2 gap-4 mb-4">
                <div class="form-group">
                  <label class="mus-label">{{ t('tournament_form.labels.province') }}</label>
                  <Dropdown 
                    v-model="form.provinceId" 
                    :options="locationStore.provinces" 
                    optionLabel="name" 
                    optionValue="id"
                    filter 
                    :placeholder="t('tournament_form.placeholders.province')"
                    class="mus-dropdown"
                  />
                </div>
                <div class="form-group">
                  <label class="mus-label">{{ t('tournament_form.labels.town') }}</label>
                  <Dropdown 
                    v-model="form.townId" 
                    :options="filteredTowns" 
                    optionLabel="name" 
                    optionValue="id"
                    filter 
                    :disabled="!form.provinceId"
                    :placeholder="t('tournament_form.placeholders.town')"
                    class="mus-dropdown"
                  />
                </div>
              </div>
              <div class="form-group">
                <label class="mus-label">{{ t('tournament_form.labels.location') }}</label>
                <input v-model="form.location" type="text" :placeholder="t('tournament_form.placeholders.location')" class="mus-input">
              </div>
            </section>

            <section class="form-section">
              <h3 class="section-title"><i class="pi pi-calendar mr-2"></i> {{ t('tournament_form.sections.schedule') }}</h3>
              <div class="date-grid">
                <div class="form-group">
                  <label class="mus-label">{{ t('tournament_form.labels.startDate') }}</label>
                  <input v-model="form.startDate" type="datetime-local" class="mus-input date-input">
                </div>
                <div class="form-group">
                  <label class="mus-label">{{ t('tournament_form.labels.endDate') }}</label>
                  <input v-model="form.endDate" type="datetime-local" class="mus-input date-input">
                </div>
              </div>
            </section>

            <section class="form-section">
              <h3 class="section-title"><i class="pi pi-file-pdf mr-2"></i> {{ t('tournament_form.sections.docs') }}</h3>
              <div class="grid grid-cols-1 gap-6">
                <!-- Poster -->
                <div class="file-box">
                  <label class="mus-label">{{ t('tournament_form.labels.poster') }}</label>
                  <div class="file-upload-wrapper-compact" :class="{ 'has-file': form.poster || existingPosterPath }">
                    <input type="file" @change="onFileChange" accept="image/*" id="poster-upload" class="hidden-input">
                    <label for="poster-upload" class="file-upload-label-compact">
                      <i class="pi" :class="form.poster ? 'pi-check-circle' : 'pi-image'"></i>
                      <span>{{ form.poster ? form.poster.name : (existingPosterPath ? $t('tournament_form.labels.posterChange') : $t('tournament_form.labels.posterSelect')) }}</span>
                    </label>
                  </div>
                </div>

                <!-- Rules PDF -->
                <div class="file-box">
                  <label class="mus-label">{{ t('tournament_form.labels.rulesFile') }}</label>
                  <div class="file-upload-wrapper-compact rules-pdf" :class="{ 'has-file': form.rulesFile || existingRulesPath }">
                    <input type="file" @change="onRulesFileChange" accept="application/pdf" id="rules-upload" class="hidden-input">
                    <label for="rules-upload" class="file-upload-label-compact">
                      <i class="pi" :class="form.rulesFile ? 'pi-file-pdf' : 'pi-file'"></i>
                      <span>{{ form.rulesFile ? form.rulesFile.name : (existingRulesPath ? $t('tournament_form.labels.rulesFileChange') : $t('tournament_form.labels.rulesFileSelect')) }}</span>
                    </label>
                  </div>
                </div>
              </div>
              <div v-if="posterPreview || existingPosterPath" class="poster-preview mt-4">
                <img :src="posterPreview || existingPosterPath" alt="Cartel">
              </div>
            </section>
          </div>

          <!-- COLUMN 2: TEAMS & RULES -->
          <div class="column">
            <section class="form-section">
              <h3 class="section-title"><i class="pi pi-users mr-2"></i> {{ t('tournament_mgmt.team_mgmt') }}</h3>
              <div class="management-box p-6 mus-glass-dark rounded-3xl border-white/5">
                <div class="flex gap-2 mb-6">
                  <select v-model="enrollmentTeamId" class="mus-input flex-1 text-xs">
                    <option :value="null" disabled>{{ t('tournament_mgmt.select_team') }}</option>
                    <option v-for="team in availableTeams" :key="team.id" :value="team.id">{{ team.name }}</option>
                  </select>
                  <button type="button" @click="handleEnrollTeam" class="mus-btn-gold-small" :disabled="!enrollmentTeamId">
                    <i class="pi pi-plus"></i>
                  </button>
                </div>

                <div v-if="enrolledTeams.length > 0" class="max-h-[180px] overflow-y-auto mb-6 pr-2 custom-scrollbar">
                   <div v-for="tt in enrolledTeams" :key="tt.id" class="flex justify-between items-center p-3 border-b border-white/5 last:border-0 text-xs">
                      <span class="font-bold text-main">{{ tt.team.name }}</span>
                      <span class="opacity-40 uppercase font-black text-[9px]">{{ tt.groupName || t('tournament_mgmt.no_group') }}</span>
                   </div>
                </div>

                <div class="flex flex-col gap-3">
                  <button type="button" @click="handleGenerateGroups" class="mus-btn-secondary" :disabled="generating || enrolledTeams.length < 2">
                    <i class="pi pi-sitemap mr-2"></i> {{ t('tournament_mgmt.draw_groups') }}
                  </button>
                  <button type="button" @click="handleGenerateMatches" class="mus-btn-secondary" :disabled="generating || enrolledTeams.length < 2">
                    <i class="pi pi-calendar mr-2"></i> {{ t('tournament_mgmt.generate_matches') }}
                  </button>
                </div>
              </div>
            </section>

            <section class="form-section">
               <h3 class="section-title"><i class="pi pi-cog mr-2"></i> Reglas & Sistema</h3>
               <div class="grid grid-cols-2 gap-3 mb-4">
                  <div v-for="opt in types" :key="opt.value" @click="form.type = opt.value" class="option-card" :class="{ active: form.type === opt.value }">
                    <span class="option-label text-[10px]">{{ opt.label }}</span>
                  </div>
               </div>
               <div class="rules-box p-6 mus-glass-dark rounded-3xl border-white/5 flex flex-col gap-6">
                  <div class="form-group">
                    <label class="mus-label">Puntos por juego</label>
                    <div class="flex gap-2">
                       <button type="button" v-for="val in [30, 40]" :key="val" @click="setPoints(val)" class="compact-btn flex-1" :class="{ active: !isOtherPoints && form.rulePoints === val }">{{ val }}</button>
                    </div>
                  </div>
                  <div class="form-group">
                    <label class="mus-label">Juegos por chico</label>
                    <div class="flex gap-2">
                       <button type="button" v-for="val in [3, 4]" :key="val" @click="setGames(val)" class="compact-btn flex-1" :class="{ active: !isOtherGames && form.ruleGames === val }">{{ val }}</button>
                    </div>
                  </div>
               </div>
            </section>

            <section class="form-section">
               <h3 class="section-title"><i class="pi pi-eye mr-2"></i> Estado & Privacidad</h3>
               <div class="flex flex-wrap gap-2 mb-4">
                  <button type="button" v-for="opt in statuses" :key="opt.value" @click="form.status = opt.value" class="status-pill" :class="{ active: form.status === opt.value }">{{ opt.label }}</button>
               </div>
               <div @click="form.private = !form.private" class="option-card" :class="{ active: form.private }">
                  <span class="option-label text-[10px]">{{ t('tournament_form.labels.private_desc') }}</span>
               </div>
            </section>
          </div>
        </div>

        <div class="form-actions mt-12 pt-10 border-t border-white/5 flex gap-4">
          <button type="button" @click="router.push('/dashboard')" class="cancel-btn">Cancelar</button>
          <button type="submit" :disabled="saving || success" class="mus-btn-gold-large flex-1">
            <i class="pi" :class="saving ? 'pi-spin pi-spinner' : 'pi-save'"></i>
            <span>{{ saving ? t('tournament_form.actions.saving') : t('tournament_form.actions.save') }}</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<style scoped>
.form-page { max-width: 1400px; margin: 0 auto; padding: 0 40px 80px 40px; }
.form-card { padding: 80px; border-radius: 40px; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(20px); }
.dual-column-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 80px; }
.column { display: flex; flex-direction: column; gap: 48px; }
.form-section { display: flex; flex-direction: column; gap: 20px; }
.section-title { font-size: 10px; font-weight: 950; text-transform: uppercase; letter-spacing: 0.3em; color: var(--secondary); opacity: 0.8; margin: 0; display: flex; align-items: center; }
.mus-label { font-size: 9px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.15em; color: #64748b; margin-bottom: 8px; display: block; }
.mus-input { background: var(--surface-hover); border: 1px solid var(--border); border-radius: 16px; padding: 18px 24px; color: var(--text-main); font-size: 14px; width: 100%; box-sizing: border-box; outline: none; transition: 0.3s; }
.mus-input:focus { border-color: var(--secondary); background: rgba(255, 255, 255, 0.05); }
.date-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }

.file-box { display: flex; flex-direction: column; }
.file-upload-wrapper-compact { border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 16px; padding: 14px 20px; transition: 0.3s; cursor: pointer; background: rgba(255,255,255,0.02); }
.file-upload-wrapper-compact:hover { border-color: var(--secondary); background: rgba(255,255,255,0.04); }
.file-upload-wrapper-compact.has-file { border-color: #10b981; background: rgba(16, 185, 129, 0.05); }
.file-upload-wrapper-compact.rules-pdf.has-file { border-color: var(--secondary); background: rgba(233, 195, 73, 0.05); }
.hidden-input { position: absolute; visibility: hidden; width: 0; height: 0; }
.file-upload-label-compact { display: flex; align-items: center; gap: 12px; cursor: pointer; }
.file-upload-label-compact i { font-size: 16px; color: #475569; }
.has-file .file-upload-label-compact i { color: #10b981; }
.rules-pdf.has-file .file-upload-label-compact i { color: var(--secondary); }
.file-upload-label-compact span { font-size: 12px; font-weight: 600; color: #94a3b8; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

.option-card { background: rgba(255, 255, 255, 0.02); border: 1px solid rgba(255, 255, 255, 0.06); padding: 16px; border-radius: 16px; text-align: center; cursor: pointer; }
.option-card.active { border-color: var(--secondary); background: rgba(233, 195, 73, 0.1); }
.option-label { font-size: 11px; font-weight: 900; text-transform: uppercase; color: #94a3b8; }
.option-card.active .option-label { color: var(--text-main); }
.compact-btn { background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.06); color: #64748b; font-size: 10px; font-weight: 900; border-radius: 12px; padding: 12px; cursor: pointer; }
.compact-btn.active { background: var(--secondary); color: black; border-color: var(--secondary); }
.status-pill { background: rgba(255, 255, 255, 0.02); border: 1px solid rgba(255, 255, 255, 0.06); padding: 10px 20px; border-radius: 99px; font-size: 10px; font-weight: 900; text-transform: uppercase; color: #64748b; cursor: pointer; }
.status-pill.active { background: var(--secondary); color: black; }
.mus-btn-gold-large { background: var(--secondary); border: none; border-radius: 24px; padding: 24px; color: black; font-size: 16px; font-weight: 950; text-transform: uppercase; cursor: pointer; display: flex; align-items: center; justify-content: center; }
.mus-btn-gold-small { background: var(--secondary); border: none; border-radius: 12px; padding: 12px 20px; color: black; cursor: pointer; }
.mus-btn-secondary { background: var(--surface-hover); border: 1px solid var(--border); border-radius: 14px; padding: 14px; color: var(--text-main); font-size: 11px; font-weight: 900; text-transform: uppercase; cursor: pointer; }
.cancel-btn { background: transparent; border: 1px solid rgba(255,255,255,0.1); color: #64748b; border-radius: 16px; padding: 18px 32px; font-size: 12px; font-weight: 900; cursor: pointer; }
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 10px; }
.poster-preview img { width: 100%; height: 250px; object-fit: cover; border-radius: 20px; }
@media (max-width: 1024px) { .dual-column-grid { grid-template-columns: 1fr; gap: 40px; } .form-card { padding: 40px; } }
</style>
