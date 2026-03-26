<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { tournamentService, teamService } from '../services/api'
import { useI18n } from 'vue-i18n'

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
  poster: null // Holds the NEW file to upload
})
const existingPosterPath = ref(null) // Holds the CURRENT path from API
const posterPreview = ref(null) // Holds the local preview of the NEW file
const loading = ref(true)
const saving = ref(false)
const error = ref(null)
const success = ref(false)
const enrolledTeams = ref([])
const availableTeams = ref([])
const enrollmentTeamId = ref(null)
const groupsCount = ref(2)
const generating = ref(false)

const API_HOST = 'http://localhost:8002'

// Rules state for "Otro"
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

const types = [
  { value: 'eliminatory', label: t('tournament_form.types.eliminatory') },
  { value: 'league', label: t('tournament_form.types.league') },
  { value: 'groups', label: t('tournament_form.types.groups') }
]

const statuses = [
  { value: 'draft', label: t('tournament_form.statuses.draft') },
  { value: 'pending', label: t('tournament_form.statuses.pending') },
  { value: 'active', label: t('tournament_form.statuses.active') },
  { value: 'finished', label: t('tournament_form.statuses.finished') }
]

// Load existing tournament data
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
      poster: null
    }
    existingPosterPath.value = data.posterPath
    
    // Check if points/games are "Other"
    if (![20, 30, 40].includes(form.value.rulePoints)) {
      isOtherPoints.value = true
    }
    if (![3, 4, 5].includes(form.value.ruleGames)) {
      isOtherGames.value = true
    }

    await loadEnrolledTeams()
    await loadAvailableTeams()
  } catch (e) {
    error.value = t('tournament_form.messages.load_error', { uuid: uuid, error: e.message })
    console.error('Error loading tournament:', e)
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
    if (form.value.location) formData.append('location', form.value.location)
    if (form.value.poster) formData.append('poster', form.value.poster)

    await tournamentService.updateTournament(uuid, formData)
    success.value = true
    setTimeout(() => router.push('/dashboard'), 1200)
  } catch (e) {
    error.value = e.message
  } finally {
    saving.value = false
  }
}

const handlePublish = async () => {
  form.value.status = 'pending'
  await handleSave()
}
</script>

<template>
  <div class="form-page">
    <header class="form-header">
      <button @click="router.push('/dashboard')" class="back-link">
        <i class="pi pi-arrow-left"></i>
        {{ $t('tournament_form.back_dashboard') }}
      </button>
      <h1 class="mus-h1 italic mt-8">{{ $t('tournament_form.edit_title') }}</h1>
      <p class="uuid-badge">{{ $t('tournament_view.uuid') }}: {{ uuid }}</p>
    </header>

    <!-- Loading skeleton -->
    <div v-if="loading" class="form-card mus-glass flex items-center justify-center" style="min-height:300px;">
      <div class="spinner-lg"></div>
    </div>

    <div v-else class="form-card mus-glass">
      <form @submit.prevent="handleSave" class="mus-form">
        <div v-if="error" class="error-msg">{{ error }}</div>
        <div v-if="success" class="success-msg">
          <i class="pi pi-check-circle mr-2"></i> {{ $t('tournament_form.messages.save_success') }}
        </div>

        <!-- Nombre -->
        <div class="form-group" v-if="!error">
          <label class="mus-label">{{ $t('tournament_form.labels.name') }} <span class="required">*</span></label>
          <input v-model="form.name" type="text" required class="mus-input">
        </div>

        <!-- Ubicación -->
        <div class="form-group" v-if="!error">
          <label class="mus-label">{{ $t('tournament_form.labels.location') }}</label>
          <div class="relative">
            <i class="pi pi-map-marker absolute left-4 top-1/2 -translate-y-1/2 text-slate-500"></i>
            <input v-model="form.location" type="text" :placeholder="t('tournament_form.placeholders.location')" class="mus-input pl-12">
          </div>
        </div>

        <!-- Fechas -->
        <div class="grid" v-if="!error">
          <div class="col-12 md:col-6">
            <div class="form-group">
              <label class="mus-label">{{ $t('tournament_form.labels.startDate') }}</label>
              <input v-model="form.startDate" type="datetime-local" class="mus-input date-input">
            </div>
          </div>
          <div class="col-12 md:col-6">
            <div class="form-group">
              <label class="mus-label">{{ $t('tournament_form.labels.endDate') }}</label>
              <input v-model="form.endDate" type="datetime-local" class="mus-input date-input">
            </div>
          </div>
        </div>
        

        <!-- Subida de Cartel -->
        <div class="form-group" v-if="!error">
          <label class="mus-label">{{ $t('tournament_form.labels.poster') }}</label>
          <div class="file-upload-wrapper" :class="{ 'has-file': form.poster || existingPosterPath }">
            <input type="file" @change="onFileChange" accept="image/*,application/pdf" id="poster-upload" class="hidden-input">
            <label for="poster-upload" class="file-upload-label">
              <i class="pi" :class="form.poster ? 'pi-file-pdf' : 'pi-cloud-upload'"></i>
              <span>{{ form.poster ? form.poster.name : (existingPosterPath ? $t('tournament_form.labels.posterChange') : $t('tournament_form.labels.posterSelect')) }}</span>
            </label>
          </div>
          
          <!-- Preview -->
          <div v-if="posterPreview" class="poster-preview">
            <p class="preview-tag">{{ $t('tournament_form.labels.newPoster') }}</p>
            <img :src="posterPreview" alt="Preview nuevo cartel">
          </div>
          <div v-else-if="existingPosterPath && !form.poster" class="poster-preview">
            <p class="preview-tag">{{ $t('tournament_form.labels.currentPoster') }}</p>
            <img :src="existingPosterPath.startsWith('http') ? existingPosterPath : API_HOST + existingPosterPath" alt="Cartel actual">
          </div>
        </div>

        <!-- Sistema de Juego -->
        <div class="form-group">
          <label class="mus-label">{{ $t('tournament_form.labels.gameSystem') }} <span class="required">*</span></label>
          <div class="radio-group">
            <div v-for="opt in types" :key="opt.value"
                 @click="form.type = opt.value"
                 class="radio-item" :class="{ active: form.type === opt.value }">
              <div class="radio-check"></div>
              <span class="radio-label">{{ opt.label }}</span>
            </div>
          </div>
        </div>

        <!-- Gestión de Equipos e Inscripciones -->
        <div class="management-section p-6 mus-glass-dark rounded-3xl border-white/5 mb-8" v-if="!error">
          <h3 class="text-lg font-black text-white italic uppercase tracking-tight mb-6 flex align-items-center justify-content-between">
            <span><i class="pi pi-users mr-2 text-[#0fb361]"></i> {{ t('tournament_mgmt.team_mgmt') }}</span>
            <span class="text-xs font-bold text-slate-500 tracking-widest">{{ t('tournament_mgmt.enrolled_count', { count: enrolledTeams.length }) }}</span>
          </h3>

          <!-- Inscribir Equipo -->
          <div class="flex gap-4 mb-8">
            <div class="flex-1">
              <select v-model="enrollmentTeamId" class="mus-input">
                <option :value="null" disabled>{{ t('tournament_mgmt.select_team') }}</option>
                <option v-for="team in availableTeams" :key="team.id" :value="team.id">
                  {{ team.name }}
                </option>
              </select>
            </div>
            <button type="button" @click="handleEnrollTeam" class="mus-btn-primary px-8" :disabled="!enrollmentTeamId">
              {{ t('tournament_mgmt.enroll') }}
            </button>
          </div>

          <!-- Tabla de Inscritos -->
          <div v-if="enrolledTeams.length > 0" class="enrolled-list mb-8">
            <div v-for="tt in enrolledTeams" :key="tt.id" 
                 class="flex align-items-center justify-content-between p-3 border-b border-white/5 last:border-0">
              <span class="font-bold text-white">{{ tt.team.name }}</span>
              <span class="text-[10px] font-black uppercase tracking-widest px-3 py-1 rounded-full bg-slate-800 text-slate-400">
                {{ tt.groupName || t('tournament_mgmt.no_group') }}
              </span>
            </div>
          </div>

          <!-- Acciones de Generación -->
          <div class="flex flex-column gap-4 border-t border-white/5 pt-6">
            <div class="flex align-items-center gap-4">
              <div class="flex-1">
                <label class="mus-label mb-2 block">{{ t('tournament_mgmt.groups_count_label') }}</label>
                <div class="flex gap-2">
                  <button type="button" v-for="n in [2, 4, 8]" :key="n"
                          @click="groupsCount = n"
                          class="mus-btn-small" :class="{ active: groupsCount === n }">
                    {{ n }}
                  </button>
                </div>
              </div>
              <button type="button" @click="handleGenerateGroups" class="mus-btn-secondary" :disabled="generating || enrolledTeams.length < 2">
                <i class="pi" :class="generating ? 'pi-spin pi-spinner' : 'pi-sitemap'"></i>
                {{ t('tournament_mgmt.draw_groups') }}
              </button>
            </div>
            <button type="button" @click="handleGenerateMatches" class="mus-btn-secondary w-full" :disabled="generating || enrolledTeams.length < 2">
                <i class="pi" :class="generating ? 'pi-spin pi-spinner' : 'pi-calendar'"></i>
                {{ t('tournament_mgmt.generate_matches') }}
            </button>
          </div>
        </div>

        <!-- Reglas y Gestión -->
        <div class="rules-section p-4 mus-glass-dark rounded-xl border-white/5 mb-8" v-if="!error">
          <h3 class="text-sm font-black text-[#0fb361] uppercase tracking-widest mb-4 flex align-items-center">
            <i class="pi pi-cog mr-2"></i> {{ $t('tournament_form.labels.rules_section') }}
          </h3>
          
          <div class="grid">
            <!-- Reyes -->
            <div class="col-12 md:col-4">
              <div class="form-group">
                <label class="mus-label">{{ $t('tournament_form.labels.ruleKings') }}</label>
                <div class="flex gap-2">
                  <button type="button" v-for="val in [4, 8]" :key="val"
                          @click="form.ruleKings = val"
                          class="mus-btn-small" :class="{ active: form.ruleKings === val }">
                    {{ val }}
                  </button>
                </div>
              </div>
            </div>
            
            <!-- Tantos (Puntos) -->
            <div class="col-12 md:col-6">
              <div class="form-group">
                <label class="mus-label">{{ $t('tournament_form.labels.rulePoints') }}</label>
                <div class="flex flex-wrap gap-2 mb-3">
                  <button type="button" v-for="val in [20, 30, 40]" :key="val"
                          @click="setPoints(val)"
                          class="mus-btn-small" :class="{ active: !isOtherPoints && form.rulePoints === val }">
                    {{ val }}
                  </button>
                  <button type="button" @click="setPoints('other')" 
                          class="mus-btn-small" :class="{ active: isOtherPoints }">
                    {{ t('tournament_form.labels.other') }}
                  </button>
                </div>
                <input v-if="isOtherPoints" v-model.number="form.rulePoints" type="number" 
                       class="mus-input py-3 text-sm" :placeholder="t('tournament_form.placeholders.customPoints')">
              </div>
            </div>

            <!-- Chicos (Juegos) -->
            <div class="col-12 md:col-6">
              <div class="form-group">
                <label class="mus-label">{{ $t('tournament_form.labels.ruleGames') }}</label>
                <div class="flex flex-wrap gap-2 mb-3">
                  <button type="button" v-for="val in [3, 4, 5]" :key="val"
                          @click="setGames(val)"
                          class="mus-btn-small" :class="{ active: !isOtherGames && form.ruleGames === val }">
                    {{ val }}
                  </button>
                  <button type="button" @click="setGames('other')" 
                          class="mus-btn-small" :class="{ active: isOtherGames }">
                    {{ t('tournament_form.labels.other') }}
                  </button>
                </div>
                <input v-if="isOtherGames" v-model.number="form.ruleGames" type="number" 
                       class="mus-input py-3 text-sm" :placeholder="t('tournament_form.placeholders.customGames')">
              </div>
            </div>
          </div>
        </div>

        <!-- Estado -->
        <div class="form-group">
          <label class="mus-label">{{ $t('tournament_form.labels.status') }}</label>
          <div class="radio-group">
            <div v-for="opt in statuses" :key="opt.value"
                 @click="form.status = opt.value"
                 class="radio-item" :class="{ active: form.status === opt.value }">
              <div class="radio-check"></div>
              <span class="radio-label">{{ opt.label }}</span>
            </div>
          </div>
        </div>

        <!-- Información de Estado -->
        <div class="form-group" v-if="!error">
          <label class="mus-label">{{ $t('tournament_form.labels.statusDescription') }}</label>
          <textarea v-model="form.statusDescription" 
                    :placeholder="$t('tournament_form.labels.statusDescriptionPlaceholderEdit')" 
                    class="mus-input min-h-[100px] py-4"></textarea>
        </div>

        <div class="form-actions mt-8 flex flex-column md:flex-row gap-4">
          <button type="button" @click="router.push('/dashboard')" class="cancel-btn flex-1 md:flex-initial">
            {{ $t('tournament_form.actions.cancel') }}
          </button>
          
          <div class="flex-1 flex gap-4">
            <button type="submit" :disabled="saving || success" class="mus-btn-primary flex-1">
              <i class="pi" :class="saving ? 'pi-spin pi-spinner' : 'pi-save'"></i>
              <span>{{ saving ? $t('tournament_form.actions.saving') : $t('tournament_form.actions.save') }}</span>
            </button>
            
            <button v-if="form.status === 'draft'" type="button" @click="handlePublish" 
                    class="mus-btn-secondary flex-1" :disabled="saving || success">
              <i class="pi pi-send"></i>
              <span>{{ $t('tournament_form.statuses.publish') }}</span>
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
</template>

<style scoped>
.form-page { max-width: 640px; margin: 0 auto; }

.back-link {
  background: none; border: none; color: #64748b;
  font-size: 10px; font-weight: 800; text-transform: uppercase;
  letter-spacing: 0.1em; display: flex; align-items: center; gap: 10px;
  cursor: pointer; transition: color 0.3s;
}
.back-link:hover { color: white; }

.uuid-badge {
  font-size: 9px; font-weight: 700; color: #1e293b;
  letter-spacing: 0.1em; margin-top: 8px; font-family: monospace;
}

.form-card { padding: 48px; border-radius: 32px; }
.mus-form { display: flex; flex-direction: column; gap: 32px; }

.form-group { display: flex; flex-direction: column; gap: 12px; }

.mus-label {
  font-size: 10px; font-weight: 900; text-transform: uppercase;
  letter-spacing: 0.2em; color: #475569; margin-left: 4px;
}
.required { color: #0fb361; }

.mus-input {
  background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08);
  border-radius: 16px; padding: 16px 24px; color: white;
  font-size: 14px; font-weight: 500; outline: none; transition: all 0.3s;
  width: 100%; box-sizing: border-box;
}
.mus-input:focus { border-color: rgba(15,179,97,0.5); background: rgba(255,255,255,0.05); }

.file-upload-wrapper {
  position: relative;
  border: 2px dashed rgba(255, 255, 255, 0.08);
  border-radius: 16px;
  padding: 24px;
  text-align: center;
  transition: all 0.3s;
  cursor: pointer;
}
.file-upload-wrapper:hover {
  border-color: rgba(15, 179, 97, 0.5);
  background: rgba(255, 255, 255, 0.02);
}
.file-upload-wrapper.has-file {
  border-style: solid;
  border-color: #0fb361;
  background: rgba(15, 179, 97, 0.05);
}

.hidden-input {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  border: 0;
}

.file-upload-label {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
  cursor: pointer;
}
.file-upload-label i {
  font-size: 24px;
  color: #475569;
}
.file-upload-wrapper.has-file i {
  color: #0fb361;
}
.file-upload-label span {
  font-size: 13px;
  font-weight: 600;
  color: #94a3b8;
}
.file-upload-wrapper.has-file span {
  color: white;
}

.preview-tag {
  font-size: 9px;
  font-weight: 800;
  text-transform: uppercase;
  color: #475569;
  letter-spacing: 0.1em;
  margin-bottom: 8px;
  margin-left: 4px;
}

.date-input { color-scheme: dark; }

.poster-preview {
  margin-top: 8px; border-radius: 12px; overflow: hidden;
  border: 1px solid rgba(255,255,255,0.08); max-height: 180px;
}
.poster-preview img { width: 100%; height: 180px; object-fit: cover; display: block; }

.radio-group { display: flex; flex-direction: column; gap: 10px; }
.radio-item {
  background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05);
  padding: 14px 20px; border-radius: 14px; display: flex; align-items: center;
  gap: 16px; cursor: pointer; transition: all 0.3s;
}
.radio-item:hover { background: rgba(255,255,255,0.04); }
.radio-item.active { border-color: rgba(15,179,97,0.4); background: rgba(15,179,97,0.05); }

.radio-check {
  width: 16px; height: 16px; border: 2px solid #1e293b;
  border-radius: 50%; position: relative; flex-shrink: 0;
}
.radio-item.active .radio-check { border-color: #0fb361; }
.radio-item.active .radio-check::after {
  content: ''; position: absolute; inset: 3px;
  background: #0fb361; border-radius: 50%;
}
.radio-label { font-size: 12px; font-weight: 700; color: #94a3b8; }
.radio-item.active .radio-label { color: white; }

.form-actions {
  display: flex; gap: 16px; justify-content: flex-end; margin-top: 8px;
}
.cancel-btn {
  background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08);
  color: #64748b; font-size: 11px; font-weight: 800; text-transform: uppercase;
  letter-spacing: 0.1em; border-radius: 16px; padding: 14px 28px; cursor: pointer;
  transition: all 0.3s;
}
.cancel-btn:hover { background: rgba(255,255,255,0.06); color: white; }

.error-msg {
  background: rgba(244,63,94,0.1); border: 1px solid rgba(244,63,94,0.2);
  color: #fb7185; padding: 16px; border-radius: 16px;
  font-size: 12px; font-weight: 600; text-align: center;
}
.success-msg {
  background: rgba(15,179,97,0.1); border: 1px solid rgba(15,179,97,0.2);
  color: #0fb361; padding: 16px; border-radius: 16px;
  font-size: 12px; font-weight: 700; text-align: center;
  display: flex; align-items: center; justify-content: center;
}

.mus-btn-small {
  background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08);
  color: #64748b; font-size: 11px; font-weight: 800; border-radius: 8px;
  padding: 8px 16px; cursor: pointer; transition: all 0.3s;
}
.mus-btn-small:hover { background: rgba(255,255,255,0.06); color: white; }
.mus-btn-small.active { background: #0fb361; color: black; border-color: #0fb361; }

.mus-btn-primary {
  background: #0fb361; border: none; border-radius: 16px;
  padding: 16px 24px; color: black; font-size: 14px; font-weight: 900;
  text-transform: uppercase; letter-spacing: 0.1em; cursor: pointer;
  display: flex; align-items: center; justify-content: center; gap: 10px;
  transition: all 0.3s;
}
.mus-btn-primary:hover { background: #0ca358; transform: translateY(-2px); box-shadow: 0 10px 20px -5px rgba(15,179,97,0.4); }
.mus-btn-primary:disabled { opacity: 0.5; cursor: not-allowed; transform: none; box-shadow: none; }

.mus-btn-secondary {
  background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1);
  border-radius: 16px; padding: 16px 24px; color: white; font-size: 14px;
  font-weight: 900; text-transform: uppercase; letter-spacing: 0.1em;
  cursor: pointer; display: flex; align-items: center; justify-content: center;
  gap: 10px; transition: all 0.3s;
}
.mus-btn-secondary:hover { background: rgba(255,255,255,0.1); border-color: white; }

@keyframes rotate { to { transform: rotate(360deg); } }
</style>
