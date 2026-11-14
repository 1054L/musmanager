<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { tournamentService, teamService } from '../services/api'
import { useI18n } from 'vue-i18n'
import Card from 'primevue/card'
import Tag from 'primevue/tag'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import Calendar from 'primevue/calendar'
import Checkbox from 'primevue/checkbox'
import MusLoader from '../components/MusLoader.vue'
import TournamentEnrollmentForm from '../components/TournamentEnrollmentForm.vue'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import { useMercure } from '../composables/useMercure'

const { t, locale } = useI18n()
const route = useRoute()
const router = useRouter()
const { subscribe } = useMercure()
const toast = useToast()
const confirm = useConfirm()
const uuid = route.params.uuid

const getStageLabel = (stage) => {
  if (!stage) return t('tournament_view.rounds.general')
  if (stage.startsWith('matchDay_')) {
    const day = stage.split('_')[1]
    const matchDayLabel = t('tournament_view.rounds.matchDay')
    if (!day) return matchDayLabel
    return locale.value === 'eu'
      ? `${day}. ${matchDayLabel}`
      : `${matchDayLabel} ${day}`
  }

  const groupMatch = stage.trim().match(/^(?:grupo|group|talde)\s+([a-z0-9]+)$/i)
  if (groupMatch) {
    return t('common.group_name', { name: groupMatch[1].toUpperCase() })
  }

  const normalizedStage = normalizeStageKey(stage)
  const knockoutKey = `tournament_view.knockout.${normalizedStage}`
  const knockoutLabel = t(knockoutKey)
  if (knockoutLabel !== knockoutKey) return knockoutLabel

  const roundKey = `tournament_view.rounds.${normalizedStage}`
  const roundLabel = t(roundKey)
  if (roundLabel !== roundKey) return roundLabel

  return stage
}


const tournament = ref(null)
const loading = ref(true)
const error = ref(null)
const matches = ref([])
const enrolledTeams = ref([])
const availableTeams = ref([])
const enrollmentTeamId = ref(null)
const groupsCount = ref(4)
const teamsPerGroup = ref(2)
const pairingMode = ref('seeded')
const showGroupsConfigModal = ref(false)
const classification = ref({})
const showStandingsInAdmin = ref(true)
const processing = ref(false)
const showEnrollmentDialog = ref(false)
const enrollmentForm = ref(null)
const matchesOrder = ref('asc')
const collapsedStages = ref({})
const leagueMatchDays = ref([])
const showManagersModal = ref(false)
const isSendingInvites = ref(false)
const inviteRows = ref([{ email: '', locale: locale.value || 'es' }])

const totalCoAdminsCount = computed(() => {
  return (tournament.value?.managers?.length || 0) + (tournament.value?.pendingManagers?.length || 0)
})

const addInviteRow = () => {
  const lastLocale = inviteRows.value[inviteRows.value.length - 1]?.locale || locale.value || 'es'
  inviteRows.value.push({ email: '', locale: lastLocale })
}

const removeInviteRow = (idx) => {
  if (inviteRows.value.length > 1) {
    inviteRows.value.splice(idx, 1)
  } else {
    inviteRows.value[0].email = ''
  }
}

const handleEmailSplit = (idx) => {
  const row = inviteRows.value[idx]
  if (!row || !row.email) return
  const parts = row.email.split(/[\s,;]+/).map(s => s.trim()).filter(Boolean)
  if (parts.length > 1) {
    const rowLocale = row.locale || locale.value || 'es'
    row.email = parts[0]
    const extraRows = parts.slice(1).map(email => ({ email, locale: rowLocale }))
    inviteRows.value.splice(idx + 1, 0, ...extraRows)
  }
}

const handleInviteManagers = async () => {
  // Expand any row that might contain comma/space-separated emails
  const expanded = []
  for (const row of inviteRows.value) {
    const parts = (row.email || '').split(/[\s,;]+/).map(s => s.trim()).filter(Boolean)
    for (const email of parts) {
      expanded.push({ email, locale: row.locale || 'es' })
    }
  }

  if (expanded.length === 0) return

  isSendingInvites.value = true
  try {
    const res = await tournamentService.inviteManagers(uuid, expanded)
    if (tournament.value) {
      tournament.value.managers = res.managers || []
      tournament.value.pendingManagers = res.pendingManagers || []
    }
    const results = res.results || []
    const existingCount = results.filter(r => r.status === 'added_existing_user').length
    const invitedCount = results.filter(r => r.status === 'invited_new_user').length
    toast.add({
      severity: 'success',
      summary: t('common.success'),
      detail: t('tournament_admin.managers.invite_success_summary', {
        total: existingCount + invitedCount,
        existing: existingCount,
        invited: invitedCount
      }),
      life: 5000
    })
    inviteRows.value = [{ email: '', locale: locale.value || 'es' }]
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Error', detail: e.message, life: 5000 })
  } finally {
    isSendingInvites.value = false
  }
}

const handleResendManagerInvite = async (pendingItem) => {
  isSendingInvites.value = true
  try {
    const res = await tournamentService.inviteManagers(uuid, [
      { email: pendingItem.email, locale: pendingItem.locale || 'es' }
    ])
    if (tournament.value) {
      tournament.value.managers = res.managers || []
      tournament.value.pendingManagers = res.pendingManagers || []
    }
    toast.add({
      severity: 'success',
      summary: t('common.success'),
      detail: t('tournament_admin.managers.resend_success', { email: pendingItem.email }),
      life: 3500
    })
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Error', detail: e.message, life: 5000 })
  } finally {
    isSendingInvites.value = false
  }
}

const handleRemoveManager = (event, payload) => {
  confirm.require({
    target: event.currentTarget,
    message: t('tournament_admin.managers.remove_confirm_msg', { email: payload.email }),
    header: t('tournament_admin.managers.remove_confirm_title'),
    icon: 'pi pi-exclamation-triangle',
    rejectProps: {
      label: t('tournament_form.actions.cancel'),
      severity: 'secondary',
      outlined: true
    },
    acceptProps: {
      label: t('common.delete'),
      severity: 'danger'
    },
    accept: async () => {
      processing.value = true
      try {
        const res = await tournamentService.removeManager(uuid, payload)
        if (tournament.value) {
          tournament.value.managers = res.managers || []
          tournament.value.pendingManagers = res.pendingManagers || []
        }
        toast.add({
          severity: 'success',
          summary: t('common.success'),
          detail: t('tournament_admin.managers.remove_success'),
          life: 3000
        })
      } catch (e) {
        toast.add({ severity: 'error', summary: 'Error', detail: e.message, life: 5000 })
      } finally {
        processing.value = false
      }
    }
  })
}

const toggleMatchDay = (day) => {
  const index = leagueMatchDays.value.indexOf(day);
  if (index === -1) {
    leagueMatchDays.value.push(day);
  } else {
    leagueMatchDays.value.splice(index, 1);
  }
  leagueMatchDays.value.sort();
};

const onEnrollmentDialogShow = () => {
  setTimeout(() => {
    if (enrollmentForm.value) {
      enrollmentForm.value.focusInput()
    }
  }, 300)
}

// Match editing state
const editingMatch = ref(null)
const editScore1 = ref(0)
const editScore2 = ref(0)
const isSavingResult = ref(false)

const fetchTournamentData = async (silent = false) => {
  if (!silent) loading.value = true
  error.value = null
  try {
    const data = await tournamentService.getTournament(uuid)
    tournament.value = data
    enrolledTeams.value = data.teams || []
    if (Array.isArray(data.leagueMatchDays)) {
      leagueMatchDays.value = data.leagueMatchDays
    } else if (data.leagueMatchDays && typeof data.leagueMatchDays === 'object' && Array.isArray(data.leagueMatchDays.matchDays)) {
      leagueMatchDays.value = data.leagueMatchDays.matchDays
    } else {
      leagueMatchDays.value = []
    }

    if (data.type === 'groups' && data.leagueMatchDays && typeof data.leagueMatchDays === 'object' && !Array.isArray(data.leagueMatchDays)) {
      if (data.leagueMatchDays.groupsCount) groupsCount.value = Number(data.leagueMatchDays.groupsCount)
      if (data.leagueMatchDays.teamsPerGroup) teamsPerGroup.value = Number(data.leagueMatchDays.teamsPerGroup)
      if (data.leagueMatchDays.pairingMode) pairingMode.value = data.leagueMatchDays.pairingMode
    } else {
      const assignedGroups = new Set((data.teams || []).map(tt => tt.mesa).filter(Boolean))
      if (assignedGroups.size >= 2) {
        groupsCount.value = assignedGroups.size
      } else if (!silent) {
        groupsCount.value = (data.teams?.length || 0) >= 12 ? 4 : 2
      }
    }
    
    if (data.matches) {
      matches.value = data.matches.map(m => ({
        id: m.id,
        status: m.status || (m.scoreA > 0 || m.scoreB > 0 ? 'finished' : 'pending'),
        teamA: m.teamA,
        scoreA: m.scoreA,
        teamB: m.teamB,
        scoreB: m.scoreB,
        stage: m.stage,
        bracketRound: m.bracketRound,
        bracketPosition: m.bracketPosition,
        matchDay: m.matchDay,
        deadlineDate: m.deadlineDate,
        games: m.games || []
      }))
    }

    if (data.type === 'groups' || data.type === 'league') {
      classification.value = await tournamentService.getClassification(uuid).catch(() => ({}))
    }

    availableTeams.value = await teamService.getTeams()
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  await fetchTournamentData()
  
  // Subscribe to Mercure updates to keep all admins in sync
  subscribe(`tournament/${uuid}`, (data) => {
    if (data.matchId) {
      const matchId = Number(data.matchId);
      const match = matches.value.find(m => Number(m.id) === matchId);
      if (match) {
        match.scoreA = data.score1;
        match.scoreB = data.score2;
        
        // Also update team names if provided (for advancements)
        if (data.team1) match.teamA = data.team1;
        if (data.team2) match.teamB = data.team2;
      } else {
        // If match not found in current list, re-fetch
        fetchTournamentData(true);
      }
    }
  });
})

const handleStatusChange = async (newStatus) => {
  if (tournament.value.status === newStatus) return
  processing.value = true
  try {
    await tournamentService.updateTournament(uuid, { status: newStatus })
    toast.add({ severity: 'success', summary: t('common.success'), detail: t('tournament_admin.status.update_success'), life: 3000 })
    await fetchTournamentData()
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Error', detail: e.message, life: 5000 })
  } finally {
    processing.value = false
  }
}

const onEnrollmentSuccess = (newTeam) => {
  showEnrollmentDialog.value = false
  if (newTeam) {
    enrolledTeams.value.push(newTeam)
    if (tournament.value) tournament.value.teamsCount++
    const hasAssignedGroups = enrolledTeams.value.some(tt => tt.mesa)
    if (tournament.value?.type === 'groups' && !hasAssignedGroups && enrolledTeams.value.length >= 12 && groupsCount.value === 2) {
      groupsCount.value = 4
    }
  }
  toast.add({ severity: 'success', summary: t('common.success'), detail: t('tournament_admin.enrollment.add_success'), life: 3000 })
}

const handleToggleConfirm = async (registrationId) => {
  try {
    const response = await tournamentService.toggleConfirmTeam(registrationId)
    const team = enrolledTeams.value.find(tt => tt.id === registrationId)
    if (team) {
      team.isConfirmed = response.isConfirmed
      const detailKey = response.isConfirmed ? 'tournament_admin.enrollment.confirmed_success' : 'tournament_admin.enrollment.unconfirmed_success'
      toast.add({ 
        severity: 'success', 
        summary: t('common.success'), 
        detail: t(detailKey) || (response.isConfirmed ? 'Pareja confirmada' : 'Confirmación anulada'), 
        life: 3000 
      })
    }
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Error', detail: e.message, life: 5000 })
  }
}

const handleRemoveTeam = async (event, registrationId) => {
  confirm.require({
    target: event.currentTarget,
    message: t('tournament_admin.enrollment.delete_confirm_msg'),
    header: t('tournament_admin.enrollment.delete_confirm_title'),
    icon: 'pi pi-exclamation-triangle',
    rejectProps: {
        label: t('tournament_admin.match_edit.cancel_btn'),
        severity: 'secondary',
        outlined: true
    },
    acceptProps: {
        label: t('tournament_admin.enrollment.delete_btn') || 'Eliminar',
        severity: 'danger'
    },
    accept: async () => {
      try {
        await tournamentService.unenrollTeam(registrationId)
        enrolledTeams.value = enrolledTeams.value.filter(tt => tt.id !== registrationId)
        if (tournament.value) tournament.value.teamsCount--
        toast.add({ severity: 'success', summary: t('common.success'), detail: t('tournament_admin.enrollment.delete_success'), life: 3000 })
      } catch (e) {
        toast.add({ severity: 'error', summary: 'Error', detail: e.message, life: 5000 })
      }
    }
  })
}

const handleGenerateGroups = async () => {
  processing.value = true
  try {
    await tournamentService.generateGroups(uuid, groupsCount.value)
    toast.add({ severity: 'success', summary: t('common.success'), detail: t('tournament_mgmt.groups_success'), life: 3000 })
    await fetchTournamentData(true)
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Error', detail: e.message, life: 5000 })
  } finally {
    processing.value = false
  }
}

const groupsDistributionSummary = computed(() => {
  const totalTeams = enrolledTeams.value.length
  const gCount = Math.max(2, groupsCount.value || 2)
  if (totalTeams === 0) return { text: t('tournament_admin.groups.enrolled_pairs_subtitle', { count: 0 }), totalMatches: 0, minSize: 0 }

  const baseSize = Math.floor(totalTeams / gCount)
  const remainder = totalTeams % gCount

  let totalMatches = 0
  for (let i = 0; i < gCount; i++) {
    const size = baseSize + (i < remainder ? 1 : 0)
    totalMatches += (size * (size - 1)) / 2
  }

  let text = ''
  if (remainder === 0) {
    text = t('tournament_admin.groups.dist_even', { groups: gCount, size: baseSize })
  } else {
    text = t('tournament_admin.groups.dist_uneven', { rem: remainder, sizePlus: baseSize + 1, rest: gCount - remainder, size: baseSize })
  }

  return { text, totalMatches, minSize: baseSize }
})

const knockoutSummary = computed(() => {
  const gCount = Math.max(2, groupsCount.value || 2)
  const qualified = gCount * (teamsPerGroup.value || 2)
  let roundLabel = t('tournament_view.bracket.title')
  if (qualified === 2) roundLabel = t('tournament_view.knockout.final')
  else if (qualified <= 4) roundLabel = t('tournament_view.knockout.t4')
  else if (qualified <= 8) roundLabel = t('tournament_view.knockout.t8')
  else if (qualified <= 16) roundLabel = t('tournament_view.knockout.t16')
  else roundLabel = t('tournament_view.knockout.t32')

  return { qualified, roundLabel }
})

const confirmGenerateGroupsMatches = async () => {
  processing.value = true
  try {
    await tournamentService.generateMatches(uuid, {
      groupsCount: groupsCount.value,
      teamsPerGroup: teamsPerGroup.value,
      pairingMode: pairingMode.value,
      redrawGroups: true
    })
    await tournamentService.updateTournament(uuid, { status: 'active' })
    showGroupsConfigModal.value = false
    toast.add({ severity: 'success', summary: t('common.success'), detail: t('dashboard.matches_success'), life: 3000 })
    await fetchTournamentData(true)
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Error', detail: e.message, life: 5000 })
  } finally {
    processing.value = false
  }
}

const handleResetMatches = (event) => {
  confirm.require({
    target: event.currentTarget,
    message: t('tournament_admin.matches.reset_confirm_msg'),
    header: t('tournament_admin.matches.reset_confirm_title'),
    icon: 'pi pi-refresh',
    rejectProps: {
      label: t('tournament_admin.match_edit.cancel_btn'),
      severity: 'secondary',
      outlined: true
    },
    acceptProps: {
      label: t('tournament_admin.matches.reset_confirm_accept'),
      severity: 'danger'
    },
    accept: async () => {
      processing.value = true
      try {
        await tournamentService.resetMatches(uuid)
        toast.add({
          severity: 'success',
          summary: t('common.success'),
          detail: t('tournament_admin.matches.reset_success'),
          life: 4000
        })
        await fetchTournamentData(true)
      } catch (e) {
        toast.add({ severity: 'error', summary: 'Error', detail: e.message, life: 5000 })
      } finally {
        processing.value = false
      }
    }
  })
}

const handleGenerateMatches = async (event) => {
  if (tournament.value?.type === 'groups') {
    showGroupsConfigModal.value = true
    return
  }

  confirm.require({
    target: event.currentTarget,
    message: t('tournament_admin.preparation.draw_confirm_msg'),
    header: t('tournament_admin.preparation.draw_confirm_title'),
    icon: 'pi pi-exclamation-triangle',
    rejectProps: {
        label: t('tournament_admin.match_edit.cancel_btn'),
        severity: 'secondary',
        outlined: true
    },
    acceptProps: {
        label: t('tournament_admin.preparation.draw_confirm_btn'),
        severity: 'primary'
    },
    accept: async () => {
      if (tournament.value.type === 'league' && (!leagueMatchDays.value || leagueMatchDays.value.length === 0)) {
        toast.add({ severity: 'error', summary: 'Error', detail: t('tournament_admin.teams.league.match_days_required'), life: 5000 })
        return
      }
      processing.value = true
      try {
        if (tournament.value.type === 'league') {
          await tournamentService.updateTournament(uuid, { leagueMatchDays: leagueMatchDays.value })
        } else if (tournament.value.type === 'groups') {
          await tournamentService.updateTournament(uuid, {
            leagueMatchDays: {
              groupsCount: groupsCount.value,
              teamsPerGroup: teamsPerGroup.value,
              pairingMode: pairingMode.value,
              matchDays: leagueMatchDays.value
            }
          })
        }
        await tournamentService.generateMatches(uuid, {
          groupsCount: groupsCount.value,
          teamsPerGroup: teamsPerGroup.value,
          pairingMode: pairingMode.value,
          matchDays: leagueMatchDays.value
        })
        // Automatically switch to active phase
        await tournamentService.updateTournament(uuid, { status: 'active' })
        const msg = tournament.value?.type === 'eliminatory' ? t('tournament_admin.preparation.draw_success') : t('dashboard.matches_success')
        toast.add({ severity: 'success', summary: t('common.success'), detail: msg, life: 3000 })
        await fetchTournamentData(true)
      } catch (e) {
        toast.add({ severity: 'error', summary: 'Error', detail: e.message, life: 5000 })
      } finally {
        processing.value = false
      }
    }
  })
}

const hasKnockoutMatches = computed(() => {
  return matches.value.some(m => m.bracketRound !== null && m.bracketRound !== undefined)
})

const groupStageProgress = computed(() => {
  const groupMatches = matches.value.filter(m => !m.bracketRound)
  const finished = groupMatches.filter(m => m.status === 'finished').length
  return {
    total: groupMatches.length,
    finished,
    allDone: groupMatches.length > 0 && finished === groupMatches.length
  }
})

const sortedEnrolledTeams = computed(() => {
  return [...enrolledTeams.value].sort((a, b) => {
    if (a.mesa && b.mesa && a.mesa !== b.mesa) {
      return a.mesa.localeCompare(b.mesa, undefined, { numeric: true })
    }
    if (a.mesa && !b.mesa) return -1
    if (!a.mesa && b.mesa) return 1
    return (a.name || '').localeCompare(b.name || '')
  })
})

const saveGroupsConfig = async (newTeamsPerGroup, newPairingMode) => {
  if (newTeamsPerGroup !== undefined) teamsPerGroup.value = newTeamsPerGroup
  if (newPairingMode !== undefined) pairingMode.value = newPairingMode
  try {
    await tournamentService.updateTournament(uuid, {
      leagueMatchDays: {
        groupsCount: groupsCount.value,
        teamsPerGroup: teamsPerGroup.value,
        pairingMode: pairingMode.value,
        matchDays: leagueMatchDays.value
      }
    })
  } catch (e) {
    console.error('Error saving groups config:', e)
  }
}

const handleGenerateKnockout = (event) => {
  confirm.require({
    target: event.currentTarget,
    message: t('tournament_admin.groups.generate_bracket_confirm_msg', { count: teamsPerGroup.value }),
    header: t('tournament_admin.groups.generate_bracket_confirm_title'),
    icon: 'pi pi-sitemap',
    rejectProps: {
      label: t('tournament_admin.match_edit.cancel_btn'),
      severity: 'secondary',
      outlined: true
    },
    acceptProps: {
      label: t('tournament_admin.groups.generate_bracket_confirm_accept'),
      severity: 'primary'
    },
    accept: async () => {
      processing.value = true
      try {
        await tournamentService.generateKnockout(uuid, {
          teamsPerGroup: teamsPerGroup.value,
          pairingMode: pairingMode.value
        })
        toast.add({
          severity: 'success',
          summary: t('common.success'),
          detail: t('tournament_admin.groups.generate_bracket_success'),
          life: 3000
        })
        await fetchTournamentData(true)
      } catch (e) {
        toast.add({ severity: 'error', summary: 'Error', detail: e.message, life: 5000 })
      } finally {
        processing.value = false
      }
    }
  })
}

const editGames = ref([])
const editDeadlineDate = ref(null)

const editingDateStageMatches = ref([])
const editingDateStageName = ref('')
const editMatchDateValue = ref(null)
const isSavingDate = ref(false)

const openStageDateModal = (stageName, stageMatches) => {
  editingDateStageName.value = stageName
  editingDateStageMatches.value = stageMatches
  editMatchDateValue.value = stageMatches[0]?.deadlineDate ? new Date(stageMatches[0].deadlineDate) : null
}

const saveStageDate = async () => {
  isSavingDate.value = true
  try {
    let dateIso = null
    if (editMatchDateValue.value) {
      // Adjust for local timezone offset to avoid the date going backwards by 1 day when converting to UTC
      const d = editMatchDateValue.value
      dateIso = new Date(d.getTime() - (d.getTimezoneOffset() * 60000)).toISOString()
    }
    
    const matchIds = editingDateStageMatches.value.map(m => m.id)
    
    await tournamentService.updateStageDate(matchIds, dateIso)
    
    // Update local state reactively
    matches.value.forEach((m, idx) => {
      if (matchIds.includes(m.id)) {
        matches.value[idx] = { ...m, deadlineDate: dateIso }
      }
    })

    toast.add({ severity: 'success', summary: t('common.success'), detail: t('tournament_admin.match_edit.date_save_success'), life: 3000 })
    editingDateStageMatches.value = []
    await fetchTournamentData(true)
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Error', detail: e.message, life: 5000 })
  } finally {
    isSavingDate.value = false
  }
}

const calculatedScore1 = computed(() => {
  return editGames.value.filter(g => g.points1 >= (tournament.value?.rulePoints || 40)).length
})

const calculatedScore2 = computed(() => {
  return editGames.value.filter(g => g.points2 >= (tournament.value?.rulePoints || 40)).length
})

const openEditModal = (match) => {
  if (!match.teamA || !match.teamB) {
    return
  }
  editingMatch.value = match
  editDeadlineDate.value = match.deadlineDate ? new Date(match.deadlineDate) : null
  
  const toWin = tournament.value?.ruleGames || 3
  const maxPossibleGames = (toWin * 2) - 1
  const currentGames = match.games || []
  
  editGames.value = []
  for (let i = 0; i < maxPossibleGames; i++) {
    editGames.value.push({
      points1: currentGames[i]?.points1 || 0,
      points2: currentGames[i]?.points2 || 0
    })
  }
}

const setWinnerPoints = (game, team) => {
  const max = tournament.value?.rulePoints || 40
  if (team === 1) {
    game.points1 = max
    // If the other team has points, we might want to keep them or clear them. 
    // Usually in Mus, the loser keeps their points.
  } else {
    game.points2 = max
  }
}

const enforceMaxPoints = (game, team) => {
  const max = tournament.value?.rulePoints || 40
  if (team === 1) {
    if (game.points1 > max) game.points1 = max
  } else {
    if (game.points2 > max) game.points2 = max
  }
}

const adjustScore = (team, amount) => {
  const max = tournament.value?.ruleGames || 40
  if (team === 1) {
    const newVal = editScore1.value + amount
    if (newVal >= 0 && newVal <= max) editScore1.value = newVal
  } else {
    const newVal = editScore2.value + amount
    if (newVal >= 0 && newVal <= max) editScore2.value = newVal
  }
}

const saveMatchResult = async () => {
  isSavingResult.value = true
  try {
    await tournamentService.updateMatchScore(editingMatch.value.id, {
      games: editGames.value,
      deadlineDate: editDeadlineDate.value ? editDeadlineDate.value.toISOString() : null
    })
    
    // Update local match state reactively
    const matchIdx = matches.value.findIndex(m => m.id === editingMatch.value.id)
    if (matchIdx !== -1) {
      // Re-create the match object to trigger Vue reactivity properly
      matches.value[matchIdx] = {
        ...matches.value[matchIdx],
        scoreA: calculatedScore1.value,
        scoreB: calculatedScore2.value,
        status: (calculatedScore1.value > 0 || calculatedScore2.value > 0) ? 'finished' : 'pending',
        games: JSON.parse(JSON.stringify(editGames.value)),
        deadlineDate: editDeadlineDate.value ? editDeadlineDate.value.toISOString() : null
      }
    }

    editingMatch.value = null
    toast.add({ severity: 'success', summary: t('common.success'), detail: t('tournament_view.match_edit.save_success'), life: 3000 })
    
    // Refresh all tournament data to show updated next round matches reactively
    await fetchTournamentData(true)

    // Silent check for tournament finish
    const allFinished = matches.value.every(m => m.status === 'finished')
    if (allFinished && tournament.value.status === 'active') {
       tournament.value.status = 'finished'
    }
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Error', detail: e.message, life: 5000 })
  } finally {
    isSavingResult.value = false
  }
}

const stats = computed(() => {
  if (!tournament.value) return []
  const confirmedCount = enrolledTeams.value.filter(tt => tt.isConfirmed).length
  return [
    { label: t('tournament_view.tabs.teams'), value: enrolledTeams.value.length, icon: 'pi-users', color: '#e9c349' },
    { label: t('tournament_admin.stats.confirmed'), value: confirmedCount, icon: 'pi-verified', color: '#10b981' },
    { label: t('tournament_view.tabs.matches'), value: matches.value.length, icon: 'pi-calendar', color: '#f4d125' },
    { label: t('tournament_admin.stats.finished'), value: matches.value.filter(m => m.status === 'finished').length, icon: 'pi-check-circle', color: '#3b82f6' }
  ]
})

const groupedMatches = computed(() => {
  const groups = {}
  
  matches.value.forEach(match => {
    let groupKey = ''
    if (tournament.value?.type === 'league') {
      const day = match.matchDay || (match.stage?.toLowerCase().includes('jornada') || match.stage?.toLowerCase().includes('jardunaldia') ? match.stage.match(/\d+/)?.[0] : null)
      groupKey = day ? `matchDay_${day}` : 'general'
    } else {
      groupKey = normalizeStageKey(match.stage)
      if (groupKey === 'third_place') groupKey = 'final'
    }

    if (!groups[groupKey]) groups[groupKey] = []
    groups[groupKey].push(match)
  })
  
  let entries = Object.entries(groups)
  
  // Sort entries
  const stageOrder = ['t64', 't32', 't16', 't8', 't4', 'final', 'third_place']
  entries.sort((a, b) => {
    if (tournament.value?.type === 'league') {
      const dayA = parseInt(a[0].split('_')[1] || 0)
      const dayB = parseInt(b[0].split('_')[1] || 0)
      return dayA - dayB
    }
    const idxA = stageOrder.indexOf(a[0])
    const idxB = stageOrder.indexOf(b[0])
    if (idxA !== -1 && idxB !== -1) return idxA - idxB
    if (idxA === -1 && idxB !== -1) return -1
    if (idxA !== -1 && idxB === -1) return 1
    return a[0].localeCompare(b[0], undefined, { numeric: true })
  })

  if (matchesOrder.value === 'desc') {
    entries.reverse()
  }
  return entries
})

const restingTeamsByDay = computed(() => {
  if (!tournament.value || tournament.value.type !== 'league' || !enrolledTeams.value.length) return {}
  if (enrolledTeams.value.length % 2 === 0) return {}

  const resting = {}
  const allTeams = enrolledTeams.value

  groupedMatches.value.forEach(([stage, stageMatches]) => {
    if (!stage.startsWith('matchDay_')) return

    const playingTeamIds = new Set()
    stageMatches.forEach(m => {
      if (m.teamA?.id) playingTeamIds.add(m.teamA.id)
      if (m.teamB?.id) playingTeamIds.add(m.teamB.id)
    })

    const restingOnDay = allTeams.filter(team => !playingTeamIds.has(team.id))
    if (restingOnDay.length > 0) {
      resting[stage] = restingOnDay
    }
  })

  return resting
})

const toggleStage = (stage) => {
  collapsedStages.value[stage] = !collapsedStages.value[stage]
}

const isGroupStage = (stage, stageMatches) => {
  if (tournament.value?.type !== 'groups') return false
  if (!stageMatches || stageMatches.length === 0) return false
  return !stageMatches.some(m => m.bracketRound !== null && m.bracketRound !== undefined)
}

const getGroupRounds = (stage, stageMatches) => {
  if (!stageMatches || stageMatches.length === 0) return []

  // Collect all teams in this group
  const groupTeamsMap = new Map()
  enrolledTeams.value.forEach(tt => {
    if (tt.mesa && tt.mesa.toLowerCase().trim() === stage.toLowerCase().trim()) {
      groupTeamsMap.set(tt.id, { id: tt.id, name: tt.name })
    }
  })
  stageMatches.forEach(m => {
    if (m.teamA?.id && !groupTeamsMap.has(m.teamA.id)) {
      groupTeamsMap.set(m.teamA.id, { id: m.teamA.id, name: m.teamA.name })
    }
    if (m.teamB?.id && !groupTeamsMap.has(m.teamB.id)) {
      groupTeamsMap.set(m.teamB.id, { id: m.teamB.id, name: m.teamB.name })
    }
  })
  const groupTeams = Array.from(groupTeamsMap.values())

  // Bucket matches by matchDay (inferring sequentially if matchDay is missing)
  const sorted = [...stageMatches].sort((a, b) => (a.id || 0) - (b.id || 0))
  const buckets = new Map()
  let inferredRound = 1
  let usedTeamIds = new Set()

  sorted.forEach(m => {
    let day = m.matchDay ? Number(m.matchDay) : null
    if (!day) {
      const t1 = m.teamA?.id
      const t2 = m.teamB?.id
      if ((t1 && usedTeamIds.has(t1)) || (t2 && usedTeamIds.has(t2))) {
        inferredRound++
        usedTeamIds = new Set()
      }
      if (t1) usedTeamIds.add(t1)
      if (t2) usedTeamIds.add(t2)
      day = inferredRound
    }
    if (!buckets.has(day)) buckets.set(day, [])
    buckets.get(day).push(m)
  })

  const rounds = Array.from(buckets.entries())
    .sort((a, b) => a[0] - b[0])
    .map(([day, roundMatches]) => {
      const playingIds = new Set()
      roundMatches.forEach(m => {
        if (m.teamA?.id) playingIds.add(m.teamA.id)
        if (m.teamB?.id) playingIds.add(m.teamB.id)
      })
      const restingTeams = groupTeams.length % 2 !== 0
        ? groupTeams.filter(t => !playingIds.has(t.id))
        : []
      return {
        day,
        stageKey: `matchDay_${day}`,
        label: getStageLabel(`matchDay_${day}`),
        deadlineDate: roundMatches[0]?.deadlineDate || null,
        matches: roundMatches,
        restingTeams
      }
    })

  if (matchesOrder.value === 'desc') {
    rounds.reverse()
  }
  return rounds
}

const toggleMatchesOrder = () => {
  matchesOrder.value = matchesOrder.value === 'asc' ? 'desc' : 'asc'
}

const statusOptions = [
  { value: 'draft', label: t('tournament_form.statuses.draft'), icon: 'pi-pencil', color: '#94a3b8' },
  { value: 'pending', label: t('tournament_form.statuses.pending'), icon: 'pi-user-plus', color: '#3b82f6' },
  { value: 'active', label: t('tournament_form.statuses.active'), icon: 'pi-play', color: '#10b981' },
  { value: 'finished', label: t('tournament_form.statuses.finished'), icon: 'pi-flag-fill', color: '#e9c349' }
]

const goToPublicView = () => {
  window.open(`/tournament/${uuid}`, '_blank');
}

const normalizeStageKey = (stage) => {
  if (!stage) return 'general'
  const s = stage.toLowerCase().trim()

  if (s.startsWith('grupo') || s.startsWith('mesa') || s.startsWith('group') || s.startsWith('talde') || s.startsWith('mahaia')) {
    return stage.trim()
  }
  
  const rondaMatch = s.match(/(?:ronda|round)\s*(\d+)/i)
  if (rondaMatch) {
    const roundNumber = Number(rondaMatch[1])
    const roundMap = ['final', 't4', 't8', 't16', 't32', 't64']
    return roundMap[roundNumber - 1] || stage.toLowerCase().replace(/\s+/g, '_')
  }
  
  // Check for 64 (Round of 64 / Treintadosavos)
  if (s.includes('jornada') || s.includes('jardunaldia')) return 'matchDay'
  if (s.includes('treintadosavos') || s.includes('treintaidosavos') || s.includes('trintadosavos') || s.includes('64') || s.includes('hirurogei')) return 't64'
  
  // Check for 32 (Round of 32 / Dieciseisavos)
  if (s.includes('dieciseisavos') || s.includes('32') || s.includes('hamasei')) return 't32'
  
  // Check for 16 (Round of 16 / Octavos)
  if (s.includes('octavos') || s.includes('16') || s.includes('zortzi')) return 't16'
  
  // Check for 8 (Quarter-finals / Cuartos)
  if (s.includes('cuartos') || s.includes('quarter') || s.includes('8') || s.includes('laurden')) return 't8'
  
  // Check for 3rd place (Check this before t4/semi because it might contain '4')
  if (s.includes('3º') || s.includes('3er') || s.includes('tercer') || s.includes('3/') || s.includes('hirugarren')) return 'third_place'
  
  // Check for 4 (Semi-finals)
  if (s.includes('semi') || s.includes('4') || s.includes('erdi')) return 't4'
  
  // Final only if it's exactly "final" or common standalone terms
  if (s === 'final' || s === 'finala' || s === 'la final' || s === 'gran final') return 'final'
  
  // Fallback for slugs or unknown stages
  return stage.toLowerCase().replace(/\s+/g, '_')
}
</script>

<template>
  <div class="admin-page">
    <div v-if="loading" class="flex items-center justify-center min-h-[600px]">
      <MusLoader />
    </div>

    <div v-else-if="error" class="max-w-2xl mx-auto mt-20">
      <div class="mus-card-premium text-center p-12 relative overflow-hidden">
        <div class="absolute top-0 left-0 w-full h-1 bg-rose-500"></div>
        <h2 class="text-3xl font-black text-main italic uppercase tracking-tight mb-4">{{ t('dashboard.error') }}</h2>
        <p class="text-slate-500 font-medium mb-8">{{ error }}</p>
        <button @click="router.push('/dashboard')" class="mus-btn-secondary px-8 py-4">
          <i class="pi pi-arrow-left mr-2"></i> {{ t('nav.dashboard') }}
        </button>
      </div>
    </div>

    <template v-else-if="tournament">
      <!-- Header Style Replicated from Dashboard -->
      <header class="mb-12 px-2">
        <div class="flex flex-column lg:flex-row justify-content-between align-items-center gap-8">
          <div class="flex align-items-center gap-6">
            <button @click="router.push('/my-tournaments')" class="back-btn" v-tooltip.right="t('tournament_admin.back_tooltip')">
              <i class="pi pi-chevron-left"></i>
            </button>
            <div>
              <div class="flex align-items-center gap-2 mb-1">
                <span class="text-slate-500 text-[9px] font-black uppercase tracking-[0.4em]">{{ t('tournament_admin.panel_title') }}</span>
                <span class="h-1 w-1 rounded-full bg-slate-700"> </span>
                <span class="text-secondary text-[9px] font-black uppercase tracking-[0.2em] italic">{{ t('tournament_form.types.' + tournament.type) }}</span>
              </div>
              <h1 class="text-3xl md:text-5xl font-black text-white italic uppercase tracking-tighter m-0 leading-none">
                {{ tournament.name.split(' ').slice(0, -1).join(' ') }} <span class="text-secondary">{{ tournament.name.split(' ').slice(-1)[0] }}</span>
              </h1>
              <div class="flex align-items-center gap-6 text-slate-500 text-[10px] font-bold uppercase tracking-[0.2em] mt-3">
                <span class="flex align-items-center gap-2"><i class="pi pi-map-marker text-secondary/50"></i>{{ tournament.location || '---' }}</span>
                <div class="w-1 h-1 rounded-full bg-slate-800"></div>
                <span class="flex align-items-center gap-2"><i class="pi pi-calendar text-secondary/50"></i>{{ tournament.startDate ? new Date(tournament.startDate).toLocaleDateString() : '...' }}</span>
                <div class="w-1 h-1 rounded-full bg-slate-800"></div>
                <span class="text-secondary">{{ t('tournament_form.statuses.' + tournament.status) }}</span>
              </div>
            </div>
          </div>
          
          <div class="flex flex-column gap-2">
            <button @click="goToPublicView" class="eye-btn" v-tooltip.left="t('tournament_view.public_view') || 'Vista Pública'">
              <i class="pi pi-eye"></i>
            </button>
            <button @click="showManagersModal = true" class="eye-btn relative" v-tooltip.left="t('tournament_admin.managers.btn_tooltip')">
              <i class="pi pi-shield"></i>
              <span v-if="totalCoAdminsCount > 0"
                    style="width: 18px; height: 18px; min-width: 18px; border-radius: 50%;"
                    class="absolute -top-1 -right-1 bg-[#e9c349] text-slate-900 text-[9px] font-black flex align-items-center justify-content-center">
                {{ totalCoAdminsCount }}
              </span>
            </button>
            <button @click="router.push(`/admin/tournament/${uuid}/edit`)" class="eye-btn" v-tooltip.left="t('common.edit') || 'Editar'">
              <i class="pi pi-pencil"></i>
            </button>
          </div>
        </div>
      </header>

      <!-- Stats Ribbon -->
      <div class="grid mt-4">
        <div v-for="stat in stats" :key="stat.label" class="col-12 md:col-3">
          <div class="stat-card-premium">
            <div class="stat-icon" :style="{ background: stat.color + '10', color: stat.color, borderColor: stat.color + '20' }">
              <i :class="['pi', stat.icon]"></i>
            </div>
            <div class="stat-content">
              <span class="stat-label">{{ stat.label }}</span>
              <span class="stat-value">{{ stat.value }}</span>
            </div>
            <div class="stat-glow" :style="{ background: stat.color }"></div>
          </div>
        </div>
      </div>

      <div class="grid mt-4">
        <!-- LEFT COLUMN: Enrollment & Teams (Only in Preparation) -->
        <div v-if="['draft', 'pending'].includes(tournament.status)" class="col-12 lg:col-5 space-y-8">
          <!-- Enrollment Panel -->
          <section v-if="['draft', 'pending'].includes(tournament.status)" class="side-panel-premium">
            <h3 class="panel-title"><i class="pi pi-user-plus"></i> {{ t('tournament_admin.enrollment.title') }}</h3>
            <div class="p-6">
              <button @click="showEnrollmentDialog = true" class="mus-btn-primary-large w-full">
                <i class="pi pi-plus"></i>
                <span>{{ t('tournament_admin.enrollment.add_btn') }}</span>
              </button>
            </div>
          </section>

          <!-- Enrolled Teams List -->
          <section v-if="['draft', 'pending'].includes(tournament.status)" class="side-panel-premium">
            <div class="panel-title flex align-items-center justify-content-between">
              <span><i class="pi pi-users"></i> {{ t('tournament_admin.enrollment.list_title') }}</span>
              <span class="text-xs opacity-40 font-black">{{ enrolledTeams.length }}</span>
            </div>
            <div class="p-4">
              <div v-if="enrolledTeams.length === 0" class="text-center py-10 opacity-30">
                <i class="pi pi-info-circle text-2xl mb-2"></i>
                <p class="text-[9px] font-black uppercase tracking-widest">{{ t('tournament_admin.enrollment.empty') }}</p>
              </div>
              <div v-else class="teams-list-scroll custom-scrollbar">
                <div v-for="tt in sortedEnrolledTeams" :key="tt.id" class="team-list-item flex align-items-center justify-content-between">
                  <div class="flex align-items-center gap-3">
                    <Checkbox :modelValue="tt.isConfirmed" :binary="true" @change="handleToggleConfirm(tt.id)" v-tooltip.top="t('tournament_admin.enrollment.confirmed_tooltip')" />
                    <div class="team-info">
                      <span class="name" :class="{ 'opacity-50': !tt.isConfirmed }">{{ tt.name }}</span>
                      <span class="group-tag" v-if="tt.mesa">{{ getStageLabel(tt.mesa) }}</span>
                    </div>
                  </div>
                  <button v-if="['draft', 'pending'].includes(tournament.status)" @click="handleRemoveTeam($event, tt.id)" class="remove-btn" v-tooltip.left="t('tournament_admin.enrollment.delete_btn') || 'Eliminar'">
                    <i class="pi pi-times"></i>
                  </button>
                </div>
              </div>
            </div>
          </section>

          <!-- Logistics Panel -->
          <section v-if="tournament.type === 'groups' && ['draft', 'pending'].includes(tournament.status)" class="side-panel-premium">
            <h3 class="panel-title"><i class="pi pi-sitemap"></i> {{ t('tournament_admin.groups.config_title') }}</h3>
            <div class="p-6">
              <div class="flex flex-column gap-6">
                <div class="flex align-items-center justify-content-between">
                  <label class="text-[10px] font-black uppercase text-slate-500">{{ t('tournament_admin.groups.count_label') }}</label>
                  <div class="stepper-control">
                    <button @click="groupsCount = Math.max(2, groupsCount - 1)" class="stepper-action"><i class="pi pi-minus"></i></button>
                    <span class="stepper-value">{{ groupsCount }}</span>
                    <button @click="groupsCount++" class="stepper-action"><i class="pi pi-plus"></i></button>
                  </div>
                </div>
                <button @click="handleGenerateGroups" :disabled="processing || enrolledTeams.length < 2" class="mus-btn-secondary-large w-full">
                  <i class="pi pi-sync mr-2"></i> {{ t('tournament_admin.groups.generate_btn') }}
                </button>
              </div>
            </div>
          </section>
        </div>

        <!-- MAIN COLUMN: Status & Control -->
        <div :class="['draft', 'pending'].includes(tournament.status) ? 'col-12 lg:col-7' : 'col-12'" class="space-y-8">
          <!-- Phase / Status Control (Only in Preparation) -->
          <section v-if="['draft', 'pending'].includes(tournament.status)" class="side-panel-premium">
            <h3 class="panel-title"><i class="pi pi-step-forward"></i> {{ t('tournament_admin.status.title') }}</h3>
            <div class="p-6">
              <!-- League / Groups Match Days Selector -->
              <div v-if="(tournament.type === 'league' || tournament.type === 'groups') && ['draft', 'pending'].includes(tournament.status)" class="w-full mb-10 pb-10 border-b border-white/5">
                <div class="flex align-items-center justify-center gap-2 mb-6">
                  <span class="block text-[10px] font-black text-slate-500 uppercase tracking-widest">
                    {{ t('tournament_admin.teams.league.match_days_label') }}
                  </span>
                  <i class="pi pi-info-circle text-slate-600 text-xs cursor-help" v-tooltip.top="t('tournament_admin.teams.league.match_days_tooltip')"></i>
                </div>
                <div class="flex justify-center gap-3">
                  <button v-for="day in [1,2,3,4,5,6,7]" :key="day"
                          @click="toggleMatchDay(day)"
                          type="button"
                          class="w-12 h-12 rounded-xl font-black text-sm transition-all flex items-center justify-center border"
                          :class="leagueMatchDays.includes(day) 
                            ? 'bg-[#e9c349] text-slate-900 border-[#e9c349] shadow-lg shadow-[#e9c349]/20' 
                            : 'bg-white/5 text-slate-500 border-white/5 hover:bg-white/10'">
                    {{ ['L', 'M', 'X', 'J', 'V', 'S', 'D'][day-1] }}
                  </button>
                </div>
                <p class="text-[10px] text-slate-600 font-bold uppercase mt-6 opacity-60 italic text-center">
                  {{ t('tournament_admin.teams.league.match_days_hint') }}
                </p>
              </div>

              <div class="grid">
                <div v-for="option in statusOptions" :key="option.value" class="col-3">
                  <button @click="handleStatusChange(option.value)"
                        :disabled="processing"
                        class="status-toggle-btn"
                        :class="{ active: tournament.status === option.value }">
                  <i :class="['pi', option.icon, 'mb-2']"></i>
                  <span>{{ option.label }}</span>
                  <div class="active-indicator" :style="{ background: option.color }"></div>
                </button>
              </div>
            </div>
          </div>
        </section>

          <!-- Preparation/Active View -->
          <div class="w-full">
            <!-- PREPARATION MODE (Draft/Pending) -->
            <section v-if="['draft', 'pending'].includes(tournament.status)" class="preparation-mode">
              <div class="mus-card-premium p-10 text-center border-dashed border-white/10">
                <div class="section-icon mx-auto mb-6 w-20 h-20 bg-secondary/10 flex items-center justify-center rounded-full border border-secondary/20">
                  <i class="pi pi-cog text-3xl text-secondary animate-spin-slow"></i>
                </div>
                <h2 class="text-3xl font-black text-main italic uppercase tracking-tighter mb-4">{{ t('tournament_admin.preparation.title') }}</h2>
                <p class="text-slate-500 font-bold text-xs uppercase tracking-widest max-w-md mx-auto mb-10 leading-relaxed">
                  {{ enrolledTeams.length < 2 ? t('tournament_admin.preparation.not_ready_desc') : t('tournament_admin.preparation.ready_desc') }}
                </p>
                <div class="flex flex-column items-center gap-6">
                  <div class="flex align-items-center gap-10 bg-black/40 p-6 rounded-2xl border border-white/5">
                    <div class="text-center">
                      <span class="block text-[9px] font-black text-slate-600 uppercase mb-1">{{ t('tournament_admin.preparation.teams_label') }}</span>
                      <span class="text-2xl font-black italic text-white">{{ enrolledTeams.length }}</span>
                    </div>
                    <div class="h-8 w-px bg-white/10"></div>
                    <div class="text-center">
                      <span class="block text-[9px] font-black text-slate-600 uppercase mb-1">{{ t('tournament_admin.preparation.system_label') }}</span>
                      <span class="text-2xl font-black italic text-secondary">{{ t('tournament_form.types.' + tournament.type) }}</span>
                    </div>
                    <template v-if="tournament.type === 'groups'">
                      <div class="h-8 w-px bg-white/10"></div>
                      <div class="text-center">
                        <span class="block text-[9px] font-black text-slate-600 uppercase mb-1">{{ t('tournament_admin.groups.count_label') }}</span>
                        <span class="text-2xl font-black italic text-emerald-400">{{ groupsCount }}</span>
                      </div>
                    </template>
                  </div>
                  

                  <button @click="handleGenerateMatches($event)" 
                          :disabled="processing || enrolledTeams.length < 2" 
                          class="mus-btn-primary px-6 py-3 text-sm">
                    <i class="pi pi-bolt mr-2 text-xs"></i>
                    {{ tournament.type === 'eliminatory' ? t('tournament_admin.preparation.initial_draw') : t('tournament_mgmt.generate_matches') }}
                  </button>
                </div>
              </div>
            </section>

            <!-- ACTIVE MODE (Active/Finished) -->
            <section v-else class="active-tournament-view">
               <!-- GROUPS STANDINGS & KNOCKOUT GENERATOR (Only for 'groups' tournaments) -->
               <div v-if="tournament.type === 'groups'" class="mb-12 space-y-8">
                  <!-- Knockout Phase Generator Card -->
                  <div v-if="!hasKnockoutMatches" class="mus-card-premium p-6 border border-[#e9c349]/30 bg-[#e9c349]/[0.03] rounded-3xl">
                    <div class="flex flex-column lg:flex-row align-items-center justify-content-between gap-6">
                      <div>
                        <div class="flex align-items-center gap-3 mb-2">
                          <i class="pi pi-sitemap text-secondary text-xl"></i>
                          <h4 class="m-0 text-white font-black italic uppercase tracking-wider text-lg">
                            {{ t('tournament_admin.groups.knockout_card_title') }}
                          </h4>
                          <span class="px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest"
                                :class="groupStageProgress.allDone ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-white/5 text-slate-400 border border-white/10'">
                            {{ t('tournament_admin.groups.group_matches_progress', { finished: groupStageProgress.finished, total: groupStageProgress.total }) }}
                          </span>
                        </div>
                        <p class="m-0 text-xs text-slate-400 font-bold">
                          {{ t('tournament_admin.groups.knockout_card_desc') }}
                        </p>
                      </div>

                      <div class="flex flex-wrap align-items-center gap-4">
                        <div class="flex align-items-center gap-2 bg-black/40 px-4 py-2 rounded-2xl border border-white/10">
                          <span class="text-[9px] font-black uppercase tracking-wider text-slate-400">{{ t('tournament_admin.groups.qualify_per_group_label') }}</span>
                          <div class="flex gap-1">
                            <button v-for="n in [1, 2, 3, 4]" :key="n"
                                    type="button"
                                    @click="saveGroupsConfig(n, undefined)"
                                    style="width: 28px; height: 28px; min-width: 28px;"
                                    class="rounded-lg font-black text-xs border transition-all flex align-items-center justify-content-center"
                                    :class="teamsPerGroup === n ? 'bg-[#e9c349] text-slate-900 border-[#e9c349]' : 'bg-white/5 text-slate-400 border-white/10'">
                              {{ n }}
                            </button>
                          </div>
                        </div>

                        <div class="flex align-items-center gap-2 bg-black/40 px-4 py-2 rounded-2xl border border-white/10">
                          <span class="text-[9px] font-black uppercase tracking-wider text-slate-400">{{ t('tournament_admin.groups.pairings_label') }}</span>
                          <button type="button"
                                  @click="saveGroupsConfig(undefined, pairingMode === 'seeded' ? 'random' : 'seeded')"
                                  class="px-3 py-1 rounded-lg font-black text-[10px] uppercase tracking-wider bg-white/5 text-[#e9c349] border border-[#e9c349]/30">
                            {{ pairingMode === 'seeded' ? t('tournament_admin.groups.pairing_seeded_title') : t('tournament_admin.groups.pairing_random_title') }}
                          </button>
                        </div>

                        <button @click="handleGenerateKnockout($event)"
                                :disabled="processing"
                                class="mus-btn-primary px-5 py-3 text-xs">
                          <i class="pi pi-bolt mr-2"></i>
                          {{ t('tournament_admin.groups.generate_bracket_btn', { count: Object.keys(classification || {}).length * teamsPerGroup }) }}
                        </button>
                      </div>
                    </div>
                  </div>

                  <!-- Compact Group Standings Preview for Admin -->
                  <div v-if="Object.keys(classification || {}).length > 0" class="side-panel-premium">
                    <div class="panel-title flex align-items-center justify-content-between cursor-pointer" @click="showStandingsInAdmin = !showStandingsInAdmin">
                      <span><i class="pi pi-table mr-2"></i> {{ t('tournament_admin.groups.standings_panel_title') }}</span>
                      <i class="pi text-xs text-slate-500" :class="showStandingsInAdmin ? 'pi-chevron-up' : 'pi-chevron-down'"></i>
                    </div>
                    <div v-show="showStandingsInAdmin" class="p-4 grid">
                      <div v-for="(teams, groupName) in classification" :key="groupName" class="col-12 lg:col-6 p-2">
                        <div class="bg-black/30 border border-white/5 rounded-2xl overflow-hidden">
                          <div class="px-4 py-2 bg-white/5 border-b border-white/5 flex justify-content-between align-items-center">
                            <span class="text-xs font-black italic uppercase text-[#e9c349] tracking-widest">{{ getStageLabel(groupName) }}</span>
                            <span class="text-[9px] font-bold uppercase text-slate-500">{{ t('tournament_admin.groups.top_qualify_badge', { count: teamsPerGroup }) }}</span>
                          </div>
                          <table class="w-full text-left border-collapse text-xs">
                            <thead>
                              <tr class="text-[9px] font-black uppercase text-slate-500 border-b border-white/5">
                                <th class="p-2 text-center w-8">#</th>
                                <th class="p-2">{{ t('tournament_view.classification.team') }}</th>
                                <th class="p-2 text-center">{{ t('tournament_view.classification.played') }}</th>
                                <th class="p-2 text-center text-emerald-400">{{ t('tournament_view.classification.mw') }}</th>
                                <th class="p-2 text-center text-rose-400">{{ t('tournament_view.classification.ml') }}</th>
                                <th class="p-2 text-center">{{ t('tournament_view.classification.vdiff') }}</th>
                                <th class="p-2 text-center">{{ t('tournament_view.classification.tdiff') }}</th>
                              </tr>
                            </thead>
                            <tbody>
                              <tr v-for="(tRow, idx) in teams" :key="tRow.id || tRow.teamName"
                                  class="border-b border-white/5 last:border-0"
                                  :class="idx < teamsPerGroup ? 'bg-emerald-500/[0.06]' : ''">
                                <td class="p-2 text-center font-black" :class="idx < teamsPerGroup ? 'text-[#e9c349]' : 'text-slate-600'">{{ idx + 1 }}</td>
                                <td class="p-2 font-black uppercase italic text-white truncate max-w-[160px]">{{ tRow.teamName }}</td>
                                <td class="p-2 text-center text-slate-400 font-bold">{{ tRow.matchesPlayed }}</td>
                                <td class="p-2 text-center text-emerald-400 font-black">{{ tRow.matchesWon }}</td>
                                <td class="p-2 text-center text-rose-400/60 font-bold">{{ tRow.matchesLost }}</td>
                                <td class="p-2 text-center font-bold" :class="tRow.vacasDiff >= 0 ? 'text-emerald-400' : 'text-rose-400'">{{ tRow.vacasDiff > 0 ? '+' : '' }}{{ tRow.vacasDiff }}</td>
                                <td class="p-2 text-center font-bold" :class="tRow.tantosDiff >= 0 ? 'text-slate-300' : 'text-rose-400/70'">{{ tRow.tantosDiff > 0 ? '+' : '' }}{{ tRow.tantosDiff }}</td>
                              </tr>
                            </tbody>
                          </table>
                        </div>
                      </div>
                    </div>
                  </div>
               </div>

               <div class="flex align-items-center justify-content-between mb-8 px-2">
                  <h3 class="m-0 text-main font-black uppercase italic tracking-tighter text-2xl">
                     <i class="pi pi-calendar mr-3 text-secondary"></i>
                     {{ t('tournament_view.tabs.matches') }}
                  </h3>
                  <div class="flex align-items-center gap-3">
                    <button @click="handleResetMatches($event)"
                            :disabled="processing"
                            class="mus-btn-secondary px-4 py-2 text-[9px] gap-2 border-rose-500/30 text-rose-400 hover:bg-rose-500/10">
                      <i class="pi pi-refresh"></i>
                      {{ t('tournament_admin.matches.reset_btn') }}
                    </button>
                    <button v-if="matches.length > 0" @click="toggleMatchesOrder" class="mus-btn-secondary px-4 py-2 text-[9px] gap-2">
                       <i :class="matchesOrder === 'asc' ? 'pi pi-sort-amount-up' : 'pi pi-sort-amount-down'"></i>
                       {{ matchesOrder === 'asc' ? t('tournament_admin.matches.order_asc') : t('tournament_admin.matches.order_desc') }}
                    </button>
                  </div>
               </div>

               <div v-if="matches.length === 0" class="text-center py-20">
                 <div class="w-20 h-20 bg-white/5 rounded-full flex items-center justify-center mx-auto mb-6 border border-white/10 opacity-30">
                   <i class="pi pi-calendar-times text-4xl text-slate-700"></i>
                 </div>
                 <h3 class="text-main font-black uppercase italic mb-2 opacity-40">{{ t('tournament_admin.matches.no_matchups') }}</h3>
                 <p class="text-slate-500 font-bold text-xs uppercase tracking-widest mb-8 opacity-40">{{ t('tournament_admin.matches.error_desc') }}</p>
                 <div class="flex justify-center gap-4">
                   <button @click="handleGenerateMatches($event)"
                           :disabled="processing || enrolledTeams.length < 2"
                           class="mus-btn-primary px-6 py-3 text-xs">
                     <i class="pi pi-bolt mr-2"></i>
                     {{ tournament.type === 'eliminatory' ? t('tournament_admin.preparation.initial_draw') : t('tournament_mgmt.generate_matches') }}
                   </button>
                 </div>
               </div>

               <div v-else class="space-y-12">
                <div v-for="([stage, stageMatches]) in groupedMatches" :key="stage" class="stage-group">
                  <div class="stage-header flex align-items-center gap-4 mb-6 cursor-pointer hover:opacity-80 transition-all" @click="toggleStage(stage)">
                    <span class="stage-dot" :class="{ 'is-collapsed': collapsedStages[stage] }"></span>
                    <h4 class="m-0 text-secondary font-black italic uppercase tracking-[0.25em] text-sm">
                      <template v-if="stage.startsWith('matchDay_')">
                        {{ getStageLabel(stage) }}
                        <span v-if="stageMatches[0]?.deadlineDate" class="ml-2 text-slate-500 font-bold tracking-normal lowercase opacity-60">
                          - [{{ new Date(stageMatches[0].deadlineDate).toLocaleDateString() }}]
                        </span>
                      </template>
                      <template v-else-if="stage === 'general'">
                        {{ t('tournament_view.rounds.general') }}
                      </template>
                      <template v-else>
                        {{ stage === 'final' && stageMatches.some(m => normalizeStageKey(m.stage) === 'third_place') ? t('tournament_view.knockout.final') + ' & ' + t('tournament_view.knockout.third_place') : getStageLabel(stage) }}
                      </template>
                    </h4>
                    <button v-if="tournament?.type === 'league' || tournament?.type === 'groups'" @click.stop="openStageDateModal(stage, stageMatches)" class="mus-btn-secondary px-2 py-1 ml-auto text-[10px]" v-tooltip.top="t('tournament_admin.match_edit.date_tooltip')"><i class="pi pi-calendar"></i></button>
                    <div class="h-px flex-1 bg-gradient-to-r from-secondary/20 to-transparent ml-4"></div>
                    <i class="pi text-xs text-slate-500" :class="collapsedStages[stage] ? 'pi-chevron-down' : 'pi-chevron-up'"></i>
                  </div>
                  
                  <!-- Resting Teams Info (for League) -->
                  <div v-if="!collapsedStages[stage] && restingTeamsByDay[stage]" class="flex flex-wrap gap-2 mb-4">
                    <div v-for="team in restingTeamsByDay[stage]" :key="'rest-'+team.id" 
                         class="bg-[#e9c349]/10 border border-[#e9c349]/20 rounded-xl px-3 py-2 flex align-items-center gap-2">
                      <i class="pi pi-info-circle text-[#e9c349] text-xs"></i>
                      <span class="text-[9px] font-black text-slate-400 uppercase tracking-wider">{{ t('tournament_view.match_status.resting') || 'Descansa' }}:</span>
                      <span class="text-[10px] font-black text-white uppercase italic">{{ team.name }}</span>
                    </div>
                  </div>

                  <!-- Group Stage Separated by Jornadas/Rounds (for 'groups' tournaments) -->
                  <div v-if="isGroupStage(stage, stageMatches)" v-show="!collapsedStages[stage]" class="space-y-6 pl-2 md:pl-4 border-l-2 border-white/5">
                    <div v-for="round in getGroupRounds(stage, stageMatches)" :key="stage + '-' + round.stageKey" class="mb-6">
                      <div class="flex align-items-center justify-content-between mb-3">
                        <div class="flex align-items-center gap-2">
                          <i class="pi pi-calendar text-secondary text-xs"></i>
                          <span class="text-xs font-black italic uppercase tracking-[0.2em] text-white">
                            {{ round.label }}
                          </span>
                          <span v-if="round.deadlineDate" class="ml-1 text-slate-400 font-bold text-xs lowercase opacity-70">
                            - [{{ new Date(round.deadlineDate).toLocaleDateString() }}]
                          </span>
                        </div>
                        <button @click.stop="openStageDateModal(`${getStageLabel(stage)} · ${round.label}`, round.matches)"
                                class="mus-btn-secondary px-2 py-1 text-[10px]"
                                v-tooltip.top="t('tournament_admin.match_edit.date_tooltip')">
                          <i class="pi pi-calendar"></i>
                        </button>
                      </div>

                      <!-- Resting Teams in this Group & Jornada -->
                      <div v-if="round.restingTeams.length > 0" class="flex flex-wrap gap-2 mb-3">
                        <div v-for="team in round.restingTeams" :key="'rest-' + stage + '-' + round.day + '-' + team.id"
                             class="bg-[#e9c349]/10 border border-[#e9c349]/20 rounded-xl px-3 py-2 flex align-items-center gap-2">
                          <i class="pi pi-info-circle text-[#e9c349] text-xs"></i>
                          <span class="text-[9px] font-black text-slate-400 uppercase tracking-wider">{{ t('tournament_view.match_status.resting') || 'Descansa' }}:</span>
                          <span class="text-[10px] font-black text-white uppercase italic">{{ team.name }}</span>
                        </div>
                      </div>

                      <div class="grid">
                        <div v-for="match in round.matches" :key="match.id"
                             @click="openEditModal(match)"
                             class="col-12 md:col-6 lg:col-3">
                          <div class="match-card-premium group" :class="{ 'cursor-default opacity-80': !match.teamA || !match.teamB }">
                            <div class="match-card-bg"></div>
                            <div class="relative z-10">
                              <div class="flex justify-content-between align-items-center mb-4">
                                <div class="flex flex-column gap-1">
                                  <span class="text-[8px] font-black text-secondary uppercase tracking-[0.2em]">
                                    {{ getStageLabel(normalizeStageKey(match.stage)) }} · {{ round.label }}
                                  </span>
                                  <span class="match-status-tag" :class="{ 'is-finished': match.status === 'finished' }">
                                    {{ t('tournament_view.match_status.' + match.status) }}
                                  </span>
                                </div>
                                <div v-if="match.teamA && match.teamB" class="flex gap-2 relative z-20">
                                  <div class="match-edit-icon"><i class="pi pi-pencil"></i></div>
                                </div>
                              </div>
                              <div class="team-row">
                                <span class="team-name" :class="{ 'is-winner': match.scoreA > match.scoreB || (match.teamA && !match.teamB && match.status === 'finished') }">{{ match.teamA?.name || match.teamA || t('tournament_admin.matches.tbd') }}</span>
                                <span class="team-score" :class="{ 'is-winner': match.scoreA > match.scoreB }">{{ match.scoreA }}</span>
                              </div>
                              <div class="team-row mt-2">
                                <span class="team-name" :class="{ 'is-winner': match.scoreB > match.scoreA, 'opacity-40': !match.teamB && match.status === 'finished' }">{{ match.teamB?.name || match.teamB || (match.teamA && match.status === 'finished' ? 'BYE' : t('tournament_admin.matches.tbd')) }}</span>
                                <span class="text-slate-700 font-black italic text-xl" v-if="match.scoreA === 0 && match.scoreB === 0 && !match.teamA && !match.teamB">VS</span>
                                <span v-else class="team-score" :class="{ 'is-winner': match.scoreB > match.scoreA }">{{ match.scoreB }}</span>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Standard Matches Grid (for League & Eliminatory/Knockout Stages) -->
                  <div v-else v-show="!collapsedStages[stage]" class="grid">
                      <div v-for="match in stageMatches" :key="match.id" 
                           @click="openEditModal(match)"
                           :class="['final', 'third_place'].includes(normalizeStageKey(match.stage)) ? 'col-12 md:col-6 mb-4' : 'col-12 md:col-6 lg:col-3'">
                      <div class="match-card-premium group" :class="{ 'cursor-default opacity-80': !match.teamA || !match.teamB }">
                      <div class="match-card-bg"></div>
                      <div class="relative z-10">
                        <div class="flex justify-content-between align-items-center mb-4">
                          <div class="flex flex-column gap-1">
                             <span class="text-[8px] font-black text-secondary uppercase tracking-[0.2em]">
                                {{ match.matchDay ? getStageLabel(`matchDay_${match.matchDay}`) : getStageLabel(normalizeStageKey(match.stage)) }}
                              </span>
                             <span class="match-status-tag" :class="{ 'is-finished': match.status === 'finished' }">
                               {{ t('tournament_view.match_status.' + match.status) }}
                             </span>
                          </div>
                          <div v-if="match.teamA && match.teamB" class="flex gap-2 relative z-20">
                             <div class="match-edit-icon"><i class="pi pi-pencil"></i></div>
                          </div>
                        </div>
                        <div class="team-row">
                          <span class="team-name" :class="{ 'is-winner': match.scoreA > match.scoreB || (match.teamA && !match.teamB && match.status === 'finished') }">{{ match.teamA?.name || match.teamA || t('tournament_admin.matches.tbd') }}</span>
                          <span class="team-score" :class="{ 'is-winner': match.scoreA > match.scoreB }">{{ match.scoreA }}</span>
                        </div>
                        <div class="team-row mt-2">
                          <span class="team-name" :class="{ 'is-winner': match.scoreB > match.scoreA, 'opacity-40': !match.teamB && match.status === 'finished' }">{{ match.teamB?.name || match.teamB || (match.teamA && match.status === 'finished' ? 'BYE' : t('tournament_admin.matches.tbd')) }}</span>
                          <span class="text-slate-700 font-black italic text-xl" v-if="match.scoreA === 0 && match.scoreB === 0 && !match.teamA && !match.teamB">VS</span>
                          <span v-else class="team-score" :class="{ 'is-winner': match.scoreB > match.scoreA }">{{ match.scoreB }}</span>
                        </div>
                      </div>
                    </div>
                </div>
              </div>
            </div>
          </div>
        </section>
      </div>
    </div>
  </div>
</template>

    <!-- Score Edit Dialog (Redesigned for Games - Side by Side - Ultra Wide - Fixed) -->
    <Dialog :visible="!!editingMatch" @update:visible="(val) => !val ? editingMatch = null : null" modal :closable="false" :style="{ width: '1000px' }" :dismissableMask="true" class="mus-dialog-premium no-scroll">
      <div v-if="editingMatch" class="p-0 overflow-hidden">
        <!-- Header visual premium -->
        <div class="form-header-premium pt-2 px-5 pb-0">
          <div class="flex align-items-center justify-content-between">
            <div class="flex align-items-center gap-4">
              <div class="header-icon scale-90">
                <i class="pi pi-pencil text-secondary text-lg"></i>
              </div>
              <div>
                <h2 class="text-xl font-black text-white italic uppercase tracking-tighter m-0 leading-none">
                  {{ t('tournament_admin.match_edit.title').split(' ').slice(0, -1).join(' ') }} 
                  <span class="text-secondary">{{ t('tournament_admin.match_edit.title').split(' ').slice(-1)[0] }}</span>
                </h2>
                <p class="text-[8px] font-black text-slate-500 uppercase tracking-[0.3em] mt-1">{{ t('tournament_admin.match_edit.subtitle') }}</p>
              </div>
            </div>
            
            <!-- Botón de cierre X -->
            <button @click="editingMatch = null" class="close-modal-btn" type="button">
              <i class="pi pi-times"></i>
            </button>
          </div>
          <div class="header-line mt-3"></div>
        </div>

        <div class="grid grid-nogutter">
          <!-- LEFT COLUMN: INFO & SUMMARY (Horizontal alignment - Optimized) -->
          <div class="col-12 md:col-6 p-5 bg-white/2 border-right border-white/5">
            <div class="flex flex-column h-full">
              <div class="flex align-items-center gap-3 mb-6">
                <div class="h-1 w-6 bg-secondary rounded-full"></div>
                <span class="text-[9px] font-black text-secondary uppercase tracking-[0.4em]">{{ t('tournament_admin.match_edit.panel_title') }}</span>
              </div>

              <!-- Teams Scoreboard Style -->
              <div class="scoreboard-premium">
                  <div class="flex justify-content-between align-items-center gap-4 mb-6">
                    <div class="flex-1 min-w-0">
                      <span class="text-[9px] font-black text-secondary/60 uppercase tracking-[0.3em] block mb-1">{{ t('tournament_view.match_edit.local') }}</span>
                      <div class="text-xl font-black text-white italic truncate" v-tooltip.bottom="editingMatch.teamA?.name || editingMatch.teamA">{{ editingMatch.teamA?.name || editingMatch.teamA }}</div>
                    </div>
                    <div class="flex-shrink-0 px-2">
                      <span class="text-xl font-black italic text-white/10">VS</span>
                    </div>
                    <div class="flex-1 min-w-0 text-right">
                      <span class="text-[9px] font-black text-secondary/60 uppercase tracking-[0.3em] block mb-1">{{ t('tournament_view.match_edit.visitor') }}</span>
                      <div class="text-xl font-black text-white italic truncate" v-tooltip.bottom="editingMatch.teamB?.name || editingMatch.teamB">{{ editingMatch.teamB?.name || editingMatch.teamB }}</div>
                    </div>
                 </div>
                 <div class="match-summary-panel p-6 bg-secondary/10 border border-secondary/20 rounded-[32px] text-center shadow-2xl relative overflow-hidden">
                    <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-transparent via-secondary to-transparent opacity-20"></div>
                    <span class="text-[9px] font-black text-secondary uppercase tracking-[0.5em] block mb-4">{{ t('tournament_admin.match_edit.score_games') }}</span>
                    <div class="flex align-items-center justify-content-center gap-10">
                      <div class="flex flex-column align-items-center">
                        <span class="text-7xl font-black italic text-white leading-none tracking-tighter">{{ calculatedScore1 }}</span>
                      </div>
                      <div class="h-16 w-px bg-white/10 mx-4"></div>
                      <div class="flex flex-column align-items-center">
                        <span class="text-7xl font-black italic text-white leading-none tracking-tighter">{{ calculatedScore2 }}</span>
                      </div>
                    </div>
                 </div>
              </div>



              <div class="rules-hint mt-6 p-4 bg-emerald-500/10 border border-emerald-500/20 rounded-2xl flex align-items-center gap-4">
                <i class="pi pi-info-circle text-emerald-500 text-lg"></i>
                <div class="flex flex-column">
                  <span class="text-[9px] font-black text-white uppercase tracking-widest">{{ t('tournament_admin.match_edit.regulation') }}</span>
                  <span v-if="tournament" class="text-[9px] font-bold text-emerald-500/80 uppercase tracking-widest">
                    {{ t('tournament_admin.match_edit.best_of', { 
                      total: (tournament?.ruleGames || 3) * 2 - 1, 
                      needed: tournament?.ruleGames || 3, 
                      points: tournament?.rulePoints || 40 
                    }) }}
                  </span>
                </div>
              </div>

              <!-- MOVED ACTIONS: NOW ON THE LEFT -->
              <div class="dialog-actions mt-8 grid grid-cols-2 gap-4">
                <button @click="editingMatch = null" class="mus-btn-secondary px-4 py-3 text-[10px]">{{ t('tournament_admin.match_edit.cancel_btn') }}</button>
                <button @click="saveMatchResult" :disabled="isSavingResult" class="mus-btn-primary px-4 py-3 text-[10px]">
                  <i v-if="isSavingResult" class="pi pi-spin pi-spinner mr-2"></i>
                  {{ isSavingResult ? t('tournament_admin.match_edit.saving_btn') : t('tournament_admin.match_edit.save_btn') }}
                </button>
              </div>
            </div>
          </div>

          <!-- RIGHT COLUMN: GAMES EDITOR -->
          <div class="col-12 md:col-6 p-5">
            <div class="flex align-items-center justify-content-between mb-6">
               <h4 class="m-0 text-white font-black italic uppercase tracking-widest">{{ t('tournament_admin.match_edit.games_breakdown') }}</h4>
               <span v-if="tournament" class="text-[10px] font-bold text-slate-500">{{ t('tournament_admin.match_edit.points_per_game', { points: tournament?.rulePoints || 40 }) }}</span>
            </div>

            <div class="games-editor-container">
               <div class="grid mb-4 px-4 opacity-40">
                 <div class="col-2 text-[9px] font-black uppercase tracking-[0.2em]">{{ t('tournament_admin.match_edit.game_label') }}</div>
                 <div class="col-5 text-[9px] font-black uppercase tracking-[0.2em] text-center">{{ t('tournament_view.match_edit.local') }}</div>
                 <div class="col-5 text-[9px] font-black uppercase tracking-[0.2em] text-center">{{ t('tournament_view.match_edit.visitor') }}</div>
               </div>

               <div class="games-list custom-scrollbar max-h-[500px] overflow-y-auto pr-2">
                 <div v-for="(game, index) in editGames" :key="index" 
                      class="game-row flex align-items-center mb-2 p-2 bg-white/5 rounded-2xl border border-white/5 hover:border-secondary/20 transition-all">
                    <div class="col-2 flex align-items-center justify-content-center">
                      <span class="text-[10px] font-black uppercase text-slate-500 tracking-widest">{{ index + 1 }}º</span>
                    </div>
                    <div class="col-5 px-4">
                      <div class="score-input-wrapper relative">
                        <input type="number" 
                               v-model.number="game.points1" 
                               :min="0" 
                               :max="tournament?.rulePoints || 40"
                               @input="enforceMaxPoints(game, 1)"
                               class="game-score-field text-sm"
                               :class="{ 'is-winner': game.points1 >= (tournament?.rulePoints || 40) }">
                        <button @click="setWinnerPoints(game, 1)" class="quick-win-btn" v-tooltip.top="t('tournament_admin.match_edit.win_tooltip')">
                          <i class="pi pi-check"></i>
                        </button>
                      </div>
                    </div>
                    <div class="col-5 px-4">
                      <div class="score-input-wrapper relative">
                        <input type="number" 
                               v-model.number="game.points2" 
                               :min="0" 
                               :max="tournament?.rulePoints || 40"
                               @input="enforceMaxPoints(game, 2)"
                               class="game-score-field text-sm"
                               :class="{ 'is-winner': game.points2 >= (tournament?.rulePoints || 40) }">
                        <button @click="setWinnerPoints(game, 2)" class="quick-win-btn" v-tooltip.top="t('tournament_admin.match_edit.win_tooltip')">
                          <i class="pi pi-check"></i>
                        </button>
                      </div>
                    </div>
                 </div>
               </div>
            </div>
          </div>
        </div>
      </div>
    </Dialog>

    <!-- Date Edit Dialog -->
    <Dialog :visible="editingDateStageMatches.length > 0" @update:visible="(val) => !val ? editingDateStageMatches = [] : null" modal :closable="false" :style="{ width: '600px' }" :contentStyle="{ overflow: 'visible' }" class="mus-dialog-premium">
      <div v-if="editingDateStageMatches.length > 0" class="p-5">
        <div class="flex align-items-center justify-content-between mb-6">
          <h3 class="m-0 text-xl font-black text-white italic uppercase tracking-widest"><i class="pi pi-calendar text-secondary mr-2"></i> {{ t('tournament_admin.match_edit.date_title') }}</h3>
          <button @click="editingDateStageMatches = []" class="close-modal-btn" type="button"><i class="pi pi-times"></i></button>
        </div>
        
        <div class="mb-6">
          <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2">{{ t('common.deadline') || 'Fecha Límite' }}</label>
          <Calendar v-model="editMatchDateValue" 
                    dateFormat="dd/mm/yy" 
                    showIcon 
                    appendTo="body"
                    class="mus-calendar-premium w-full" 
                    :placeholder="t('tournament_admin.teams.league.match_days_label')" />
        </div>
        
        <div class="flex gap-3 justify-content-end">
          <button @click="editingDateStageMatches = []" class="mus-btn-secondary px-4 py-2 text-[10px]">{{ t('tournament_admin.match_edit.cancel_btn') }}</button>
          <button @click="saveStageDate" :disabled="isSavingDate" class="mus-btn-primary px-4 py-2 text-[10px]">
            <i v-if="isSavingDate" class="pi pi-spin pi-spinner mr-2"></i>
            {{ isSavingDate ? t('tournament_admin.match_edit.saving_btn') : t('tournament_admin.match_edit.save_btn') }}
          </button>
        </div>
      </div>
    </Dialog>

    <!-- Enrollment Dialog -->
    <Dialog v-model:visible="showEnrollmentDialog" modal :closable="false" :style="{ width: '450px' }" class="mus-dialog-premium" @show="onEnrollmentDialogShow">
      <div class="p-4">
        <TournamentEnrollmentForm ref="enrollmentForm" :tournamentUuid="uuid" @success="onEnrollmentSuccess" @cancel="showEnrollmentDialog = false" />
      </div>
    </Dialog>

    <!-- Groups & Knockout Configuration Modal -->
    <Dialog v-model:visible="showGroupsConfigModal" modal :closable="false" :style="{ width: '620px' }" :dismissableMask="true" class="mus-dialog-premium">
      <div class="p-5">
        <div class="flex align-items-center justify-content-between mb-4">
          <div class="flex align-items-center gap-3">
            <div class="header-icon scale-90">
              <i class="pi pi-sitemap text-secondary text-lg"></i>
            </div>
            <div>
              <h3 class="m-0 text-xl font-black text-white italic uppercase tracking-tighter">
                {{ t('tournament_admin.groups.modal_title_1') }} <span class="text-secondary">{{ t('tournament_admin.groups.modal_title_2') }}</span>
              </h3>
              <p class="m-0 text-[9px] font-black text-slate-500 uppercase tracking-[0.25em] mt-1">
                {{ t('tournament_admin.groups.enrolled_pairs_subtitle', { count: enrolledTeams.length }) }}
              </p>
            </div>
          </div>
          <button @click="showGroupsConfigModal = false" class="close-modal-btn" type="button">
            <i class="pi pi-times"></i>
          </button>
        </div>

        <div class="header-line mb-6"></div>

        <div class="space-y-6">
          <!-- Step 1: Number of Groups -->
          <div class="p-4 rounded-2xl bg-white/[0.03] border border-white/10">
            <div class="flex align-items-center justify-content-between mb-3">
              <div>
                <span class="block text-[10px] font-black uppercase tracking-widest text-[#e9c349]">{{ t('tournament_admin.groups.step1_title') }}</span>
                <span class="text-xs text-slate-400 font-bold">{{ t('tournament_admin.groups.step1_desc', { count: enrolledTeams.length }) }}</span>
              </div>
              <div class="stepper-control">
                <button type="button" @click="groupsCount = Math.max(2, groupsCount - 1)" class="stepper-action"><i class="pi pi-minus"></i></button>
                <span class="stepper-value">{{ groupsCount }}</span>
                <button type="button" @click="groupsCount = Math.min(Math.max(2, Math.floor(enrolledTeams.length / 2)), groupsCount + 1)" class="stepper-action"><i class="pi pi-plus"></i></button>
              </div>
            </div>

            <div class="flex flex-wrap gap-2 mb-3">
              <button v-for="g in [2, 3, 4, 5, 6, 8].filter(n => n * 2 <= enrolledTeams.length)" :key="'g-opt-'+g"
                      type="button"
                      @click="groupsCount = g"
                      class="px-3 py-1.5 rounded-xl font-black text-xs border transition-all"
                      :class="groupsCount === g ? 'bg-[#e9c349] text-slate-900 border-[#e9c349]' : 'bg-white/5 text-slate-400 border-white/10 hover:bg-white/10'">
                {{ t('tournament_admin.groups.n_groups_btn', { count: g }) }}
              </button>
            </div>

            <div class="p-3 rounded-xl bg-black/40 border border-white/5 flex align-items-center justify-content-between text-xs">
              <span class="font-bold text-slate-300"><i class="pi pi-users text-secondary mr-2"></i>{{ groupsDistributionSummary.text }}</span>
              <span class="font-black text-emerald-400 uppercase">{{ t('tournament_admin.groups.league_matches_count', { count: groupsDistributionSummary.totalMatches }) }}</span>
            </div>
          </div>

          <!-- Step 2: Qualifiers per group -->
          <div class="p-4 rounded-2xl bg-white/[0.03] border border-white/10">
            <div class="flex align-items-center justify-content-between mb-3">
              <div>
                <span class="block text-[10px] font-black uppercase tracking-widest text-[#e9c349]">{{ t('tournament_admin.groups.step2_title') }}</span>
                <span class="text-xs text-slate-400 font-bold">{{ t('tournament_admin.groups.step2_desc') }}</span>
              </div>
              <div class="flex gap-2">
                <button v-for="n in [1, 2, 3, 4]" :key="'qual-'+n"
                        type="button"
                        @click="teamsPerGroup = n"
                        style="width: 36px; height: 36px; min-width: 36px;"
                        class="rounded-xl font-black text-sm border transition-all flex align-items-center justify-content-center"
                        :class="teamsPerGroup === n ? 'bg-[#e9c349] text-slate-900 border-[#e9c349]' : 'bg-white/5 text-slate-400 border-white/10 hover:bg-white/10'">
                  {{ n }}
                </button>
              </div>
            </div>

            <div class="p-3 rounded-xl bg-black/40 border border-white/5 flex align-items-center justify-content-between text-xs">
              <span class="font-bold text-slate-300">
                <i class="pi pi-trophy text-secondary mr-2"></i>
                {{ t('tournament_admin.groups.step2_summary', { qual: teamsPerGroup, groups: groupsCount }) }}
              </span>
              <span class="font-black text-[#e9c349] uppercase">
                {{ t('tournament_admin.groups.qualified_to_round', { count: knockoutSummary.qualified, round: knockoutSummary.roundLabel }) }}
              </span>
            </div>
          </div>

          <!-- Step 3: Pairing Mode -->
          <div class="p-4 rounded-2xl bg-white/[0.03] border border-white/10">
            <span class="block text-[10px] font-black uppercase tracking-widest text-[#e9c349] mb-3">{{ t('tournament_admin.groups.step3_title') }}</span>
            <div class="grid grid-nogutter gap-3">
              <button type="button"
                      @click="pairingMode = 'seeded'"
                      class="col p-3 rounded-xl border text-left transition-all cursor-pointer"
                      :class="pairingMode === 'seeded' ? 'bg-[#e9c349]/15 border-[#e9c349] text-white' : 'bg-black/30 border-white/10 text-slate-400'">
                <div class="font-black text-xs uppercase mb-1 flex align-items-center justify-content-between">
                  <span>{{ t('tournament_admin.groups.pairing_seeded_title') }}</span>
                  <i v-if="pairingMode === 'seeded'" class="pi pi-check-circle text-[#e9c349]"></i>
                </div>
                <div class="text-[10px] opacity-80">{{ t('tournament_admin.groups.pairing_seeded_desc') }}</div>
              </button>

              <button type="button"
                      @click="pairingMode = 'random'"
                      class="col p-3 rounded-xl border text-left transition-all cursor-pointer"
                      :class="pairingMode === 'random' ? 'bg-[#e9c349]/15 border-[#e9c349] text-white' : 'bg-black/30 border-white/10 text-slate-400'">
                <div class="font-black text-xs uppercase mb-1 flex align-items-center justify-content-between">
                  <span>{{ t('tournament_admin.groups.pairing_random_title') }}</span>
                  <i v-if="pairingMode === 'random'" class="pi pi-check-circle text-[#e9c349]"></i>
                </div>
                <div class="text-[10px] opacity-80">{{ t('tournament_admin.groups.pairing_random_desc') }}</div>
              </button>
            </div>
          </div>
        </div>

        <div class="flex gap-3 justify-content-end mt-6">
          <button type="button" @click="showGroupsConfigModal = false" class="mus-btn-secondary px-5 py-3 text-xs">
            {{ t('tournament_admin.match_edit.cancel_btn') }}
          </button>
          <button type="button"
                  @click="confirmGenerateGroupsMatches"
                  :disabled="processing || enrolledTeams.length < 4"
                  class="mus-btn-primary px-6 py-3 text-xs">
            <i v-if="processing" class="pi pi-spin pi-spinner mr-2"></i>
            <i v-else class="pi pi-bolt mr-2"></i>
            {{ t('tournament_admin.groups.confirm_draw_btn') }}
          </button>
        </div>
      </div>
    </Dialog>

    <!-- TOURNAMENT CO-ADMINS / MANAGERS MODAL -->
    <Dialog v-model:visible="showManagersModal"
            modal
            :closable="false"
            :style="{ width: '720px', maxWidth: '95vw' }"
            :dismissableMask="true"
            class="mus-dialog-premium">
      <div class="p-6">
        <header class="flex justify-content-between align-items-center mb-6 border-b border-white/10 pb-4">
          <div class="flex align-items-center gap-3">
            <div style="width: 40px; height: 40px; min-width: 40px;" class="rounded-xl bg-secondary/10 flex align-items-center justify-content-center border border-secondary/20">
              <i class="pi pi-shield text-secondary text-lg"></i>
            </div>
            <div>
              <h3 class="m-0 text-white font-black italic uppercase tracking-tighter text-lg">
                {{ t('tournament_admin.managers.modal_title') }}
              </h3>
              <span class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">
                {{ t('tournament_admin.managers.modal_subtitle') }}
              </span>
            </div>
          </div>
          <button @click="showManagersModal = false" class="close-modal-btn small">
            <i class="pi pi-times text-xs"></i>
          </button>
        </header>

        <!-- Current Administrators & Pending Invitations -->
        <div class="mb-6">
          <span class="block text-[10px] font-black uppercase tracking-widest text-[#e9c349] mb-3">
            {{ t('tournament_admin.managers.current_section') }}
          </span>

          <div class="space-y-2 max-h-60 overflow-y-auto custom-scrollbar pr-1">
            <!-- Owner -->
            <div v-if="tournament?.owner" class="p-3 rounded-xl bg-white/[0.04] border border-white/10 flex align-items-center justify-content-between gap-3">
              <div class="flex align-items-center gap-3 min-w-0">
                <div style="width: 32px; height: 32px; min-width: 32px;" class="rounded-lg bg-[#e9c349]/15 border border-[#e9c349]/30 flex align-items-center justify-content-center text-[#e9c349]">
                  <i class="pi pi-crown text-xs"></i>
                </div>
                <div class="min-w-0">
                  <div class="text-xs font-black text-white truncate">{{ tournament.owner.email }}</div>
                  <div v-if="tournament.owner.firstName || tournament.owner.lastName" class="text-[10px] text-slate-400 font-bold">
                    {{ tournament.owner.firstName }} {{ tournament.owner.lastName }}
                  </div>
                </div>
              </div>
              <span class="px-2.5 py-1 rounded-full bg-[#e9c349]/20 border border-[#e9c349]/40 text-[#e9c349] text-[9px] font-black uppercase tracking-widest whitespace-nowrap">
                {{ t('tournament_admin.managers.owner_badge') }}
              </span>
            </div>

            <!-- Active Co-Admins -->
            <div v-for="mgr in (tournament?.managers || [])" :key="'mgr-' + mgr.id"
                 class="p-3 rounded-xl bg-emerald-500/[0.05] border border-emerald-500/20 flex align-items-center justify-content-between gap-3">
              <div class="flex align-items-center gap-3 min-w-0">
                <div style="width: 32px; height: 32px; min-width: 32px;" class="rounded-lg bg-emerald-500/15 border border-emerald-500/30 flex align-items-center justify-content-center text-emerald-400">
                  <i class="pi pi-user text-xs"></i>
                </div>
                <div class="min-w-0">
                  <div class="text-xs font-black text-white truncate">{{ mgr.email }}</div>
                  <div v-if="mgr.firstName || mgr.lastName" class="text-[10px] text-slate-400 font-bold">
                    {{ mgr.firstName }} {{ mgr.lastName }}
                  </div>
                </div>
              </div>
              <div class="flex align-items-center gap-2">
                <span class="px-2.5 py-1 rounded-full bg-emerald-500/20 border border-emerald-500/30 text-emerald-300 text-[9px] font-black uppercase tracking-widest whitespace-nowrap">
                  {{ t('tournament_admin.managers.active_badge') }}
                </span>
                <button v-if="tournament?.isOwner"
                        type="button"
                        @click="handleRemoveManager($event, { userId: mgr.id, email: mgr.email })"
                        style="width: 28px; height: 28px; min-width: 28px;"
                        class="rounded-lg bg-rose-500/10 border border-rose-500/20 text-rose-400 hover:bg-rose-500 hover:text-white transition-all flex align-items-center justify-content-center cursor-pointer"
                        v-tooltip.top="t('tournament_admin.managers.remove_tooltip')">
                  <i class="pi pi-times text-[10px]"></i>
                </button>
              </div>
            </div>

            <!-- Pending Registration Invitations -->
            <div v-for="pend in (tournament?.pendingManagers || [])" :key="'pend-' + pend.email"
                 class="p-3 rounded-xl bg-amber-500/[0.05] border border-amber-500/25 flex align-items-center justify-content-between gap-3">
              <div class="flex align-items-center gap-3 min-w-0">
                <div style="width: 32px; height: 32px; min-width: 32px;" class="rounded-lg bg-amber-500/15 border border-amber-500/30 flex align-items-center justify-content-center text-amber-400">
                  <i class="pi pi-envelope text-xs"></i>
                </div>
                <div class="min-w-0">
                  <div class="text-xs font-black text-white truncate">{{ pend.email }}</div>
                  <div class="text-[9px] text-slate-400 font-bold uppercase tracking-wider">
                    {{ t('tournament_admin.managers.lang_label') }}: {{ (pend.locale || 'es').toUpperCase() }}
                  </div>
                </div>
              </div>
              <div class="flex align-items-center gap-2">
                <span class="px-2.5 py-1 rounded-full bg-amber-500/20 border border-amber-500/30 text-amber-300 text-[9px] font-black uppercase tracking-widest whitespace-nowrap">
                  {{ t('tournament_admin.managers.pending_badge') }}
                </span>
                <button v-if="tournament?.isOwner"
                        type="button"
                        :disabled="isSendingInvites"
                        @click="handleResendManagerInvite(pend)"
                        style="width: 28px; height: 28px; min-width: 28px;"
                        class="rounded-lg bg-white/5 border border-white/10 text-slate-300 hover:border-[#e9c349] hover:text-[#e9c349] transition-all flex align-items-center justify-content-center cursor-pointer"
                        v-tooltip.top="t('tournament_admin.managers.resend_tooltip')">
                  <i class="pi pi-send text-[10px]"></i>
                </button>
                <button v-if="tournament?.isOwner"
                        type="button"
                        @click="handleRemoveManager($event, { email: pend.email })"
                        style="width: 28px; height: 28px; min-width: 28px;"
                        class="rounded-lg bg-rose-500/10 border border-rose-500/20 text-rose-400 hover:bg-rose-500 hover:text-white transition-all flex align-items-center justify-content-center cursor-pointer"
                        v-tooltip.top="t('tournament_admin.managers.remove_tooltip')">
                  <i class="pi pi-times text-[10px]"></i>
                </button>
              </div>
            </div>

            <div v-if="totalCoAdminsCount === 0" class="p-4 rounded-xl bg-black/30 border border-white/5 text-center text-xs text-slate-500 font-bold">
              {{ t('tournament_admin.managers.empty_co_admins') }}
            </div>
          </div>
        </div>

        <!-- Add / Invite New Co-Admins Form (Only for Tournament Owner) -->
        <div v-if="tournament?.isOwner" class="p-4 rounded-2xl bg-white/[0.03] border border-white/10">
          <div class="mb-3">
            <span class="block text-[10px] font-black uppercase tracking-widest text-[#e9c349] mb-1">
              {{ t('tournament_admin.managers.add_section_title') }}
            </span>
            <p class="m-0 text-xs text-slate-400 font-medium leading-relaxed">
              {{ t('tournament_admin.managers.add_section_desc') }}
            </p>
          </div>

          <div class="space-y-3 mt-4">
            <div v-for="(row, idx) in inviteRows" :key="'inv-row-' + idx"
                 class="flex flex-column sm:flex-row align-items-stretch sm:align-items-center gap-2">
              <div class="flex-1">
                <input v-model="row.email"
                       type="text"
                       @blur="handleEmailSplit(idx)"
                       :placeholder="t('tournament_admin.managers.email_placeholder')"
                       class="w-full px-4 py-2.5 rounded-xl bg-black/50 border border-white/10 text-white text-xs font-bold outline-none focus:border-[#e9c349]" />
              </div>
              <div class="flex align-items-center gap-2">
                <select v-model="row.locale"
                        class="px-3 py-2.5 rounded-xl bg-black/50 border border-white/10 text-[#e9c349] text-xs font-black uppercase outline-none cursor-pointer">
                  <option value="es" class="bg-slate-900 text-white">{{ t('tournament_admin.managers.lang_es') }}</option>
                  <option value="eu" class="bg-slate-900 text-white">{{ t('tournament_admin.managers.lang_eu') }}</option>
                  <option value="en" class="bg-slate-900 text-white">{{ t('tournament_admin.managers.lang_en') }}</option>
                </select>
                <button type="button"
                        @click="removeInviteRow(idx)"
                        style="width: 36px; height: 36px; min-width: 36px;"
                        class="rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-400 hover:bg-rose-500 hover:text-white transition-all flex align-items-center justify-content-center cursor-pointer">
                  <i class="pi pi-trash text-xs"></i>
                </button>
              </div>
            </div>
          </div>

          <div class="mt-3">
            <button type="button"
                    @click="addInviteRow"
                    class="px-3 py-1.5 rounded-xl bg-white/5 border border-white/10 text-slate-300 hover:text-white hover:bg-white/10 text-[10px] font-black uppercase tracking-wider cursor-pointer transition-all">
              <i class="pi pi-plus text-[9px] mr-1"></i>
              {{ t('tournament_admin.managers.add_row_btn') }}
            </button>
          </div>

          <div class="flex gap-3 justify-content-end mt-6 pt-4 border-t border-white/10">
            <button type="button" @click="showManagersModal = false" class="mus-btn-secondary px-5 py-3 text-xs">
              {{ t('tournament_admin.match_edit.cancel_btn') }}
            </button>
            <button type="button"
                    @click="handleInviteManagers"
                    :disabled="isSendingInvites || !inviteRows.some(r => r.email && r.email.trim())"
                    class="mus-btn-primary px-6 py-3 text-xs">
              <i v-if="isSendingInvites" class="pi pi-spin pi-spinner mr-2"></i>
              <i v-else class="pi pi-send mr-2"></i>
              {{ isSendingInvites ? t('tournament_admin.managers.sending_btn') : t('tournament_admin.managers.send_btn') }}
            </button>
          </div>
        </div>
      </div>
    </Dialog>
  </div>
</template>

<style scoped>
.admin-page { 
  max-width: 1500px; 
  margin: 0 auto; 
  padding: 40px 60px 100px 60px; 
  opacity: 1 !important; 
  visibility: visible !important; 
  position: relative;
  z-index: 1;
}

/* Header & Back Button */
.back-btn { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); width: 40px; height: 40px; border-radius: 12px; color: #64748b; cursor: pointer; transition: 0.3s; display: flex; align-items: center; justify-content: center; }
.back-btn:hover { background: rgba(255,255,255,0.08); color: white; border-color: rgba(255,255,255,0.2); transform: translateX(-3px); }
.eye-btn { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); width: 40px; height: 40px; border-radius: 12px; color: #64748b; cursor: pointer; transition: 0.3s; display: flex; align-items: center; justify-content: center; }
.eye-btn:hover { background: rgba(255,255,255,0.08); color: var(--secondary); border-color: var(--secondary); transform: translateX(3px); }

/* Buttons */
.mus-btn-primary { 
  background: linear-gradient(135deg, #bf953f 0%, #fcf6ba 50%, #aa771c 100%); 
  color: #050505; 
  border: none; 
  font-weight: 950; 
  text-transform: uppercase; 
  letter-spacing: 0.1em; 
  border-radius: 18px; 
  cursor: pointer; 
  transition: all 0.3s; 
  padding: 12px 24px;
  display: flex;
  align-items: center;
  justify-content: center;
}
.mus-btn-primary:hover:not(:disabled) { 
  transform: translateY(-3px); 
  box-shadow: 0 15px 35px -10px rgba(191, 149, 63, 0.5); 
}
.mus-btn-primary:disabled { opacity: 0.5; cursor: not-allowed; filter: grayscale(1); }

.mus-btn-secondary { 
  background: rgba(255, 255, 255, 0.05); 
  border: 1px solid rgba(255, 255, 255, 0.1); 
  color: white; 
  font-weight: 950; 
  text-transform: uppercase; 
  letter-spacing: 0.1em; 
  border-radius: 18px; 
  cursor: pointer; 
  transition: all 0.3s; 
  display: flex;
  align-items: center;
  justify-content: center;
}
.mus-btn-secondary:hover { 
  background: rgba(255, 255, 255, 0.1); 
  border-color: rgba(255, 255, 255, 0.2); 
}

.mus-btn-primary-large { 
  background: linear-gradient(135deg, #bf953f 0%, #fcf6ba 50%, #aa771c 100%); 
  color: #050505; 
  border: none; 
  font-weight: 950; 
  text-transform: uppercase; 
  letter-spacing: 0.1em; 
  border-radius: 18px; 
  padding: 18px;
  cursor: pointer; 
  transition: all 0.3s; 
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
}
.mus-btn-primary-large:hover:not(:disabled) { 
  transform: translateY(-3px); 
  box-shadow: 0 15px 35px -10px rgba(191, 149, 63, 0.5); 
}
.mus-btn-primary-large:disabled { opacity: 0.5; cursor: not-allowed; filter: grayscale(1); }

.mus-btn-secondary-large { 
  background: rgba(255, 255, 255, 0.05); 
  border: 1px solid rgba(255, 255, 255, 0.1); 
  color: white; 
  font-weight: 950; 
  text-transform: uppercase; 
  letter-spacing: 0.1em; 
  border-radius: 18px; 
  padding: 18px;
  cursor: pointer; 
  transition: all 0.3s; 
  display: flex;
  align-items: center;
  justify-content: center;
}
.mus-btn-secondary-large:hover { 
  background: rgba(255, 255, 255, 0.1); 
  border-color: rgba(255, 255, 255, 0.2); 
}

/* Side Panels */
.side-panel-premium { 
  background: rgba(255, 255, 255, 0.02); 
  border: 1px solid rgba(255, 255, 255, 0.05); 
  border-radius: 32px; 
  overflow: hidden;
  backdrop-filter: blur(20px);
}
.panel-title { 
  background: rgba(255, 255, 255, 0.03); 
  padding: 18px 24px; 
  margin: 0; 
  font-size: 11px; 
  font-weight: 950; 
  text-transform: uppercase; 
  letter-spacing: 0.2em; 
  color: #64748b; 
  display: flex; 
  align-items: center; 
  gap: 12px; 
  border-bottom: 1px solid rgba(255, 255, 255, 0.05); 
}
.panel-title i { color: var(--secondary); font-size: 14px; }

/* Status Toggles */
.status-toggle-btn { 
  width: 100%; 
  aspect-ratio: 1/1; 
  background: rgba(255, 255, 255, 0.02); 
  border: 1px solid rgba(255, 255, 255, 0.05); 
  border-radius: 24px; 
  color: #64748b; 
  display: flex; 
  flex-direction: column; 
  align-items: center; 
  justify-content: center; 
  gap: 8px; 
  cursor: pointer; 
  transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); 
  position: relative; 
  overflow: hidden;
}
.status-toggle-btn i { font-size: 20px; }
.status-toggle-btn span { font-size: 9px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.1em; }
.status-toggle-btn:hover:not(:disabled) { background: rgba(255, 255, 255, 0.08); color: white; transform: scale(1.05); }
.status-toggle-btn.active { background: rgba(255, 255, 255, 0.1); color: white; border-color: rgba(255,255,255,0.2); }
.active-indicator { position: absolute; bottom: 0; left: 0; width: 100%; height: 4px; opacity: 0; transition: 0.3s; }
.status-toggle-btn.active .active-indicator { opacity: 1; }

/* Team List */
.teams-list-scroll { max-height: 650px; overflow-y: auto; padding-right: 8px; }
.team-list-item { 
  padding: 14px 18px; 
  background: rgba(255,255,255,0.02); 
  border: 1px solid rgba(255,255,255,0.05); 
  border-radius: 20px; 
  margin-bottom: 8px; 
  transition: 0.3s; 
}
.team-list-item:hover { background: rgba(255,255,255,0.05); transform: translateX(5px); }
.team-info { display: flex; flex-direction: column; gap: 2px; }
.team-info .name { font-weight: 900; font-style: italic; color: white; font-size: 13px; text-transform: uppercase; }
.team-info .group-tag { font-size: 8px; font-weight: 900; color: var(--secondary); background: rgba(191, 149, 63, 0.1); padding: 2px 8px; border-radius: 99px; width: fit-content; }
.remove-btn { background: none; border: none; color: #f43f5e; cursor: pointer; padding: 8px; border-radius: 10px; transition: 0.2s; opacity: 0.3; }
.team-list-item:hover .remove-btn { opacity: 1; }
.remove-btn:hover { background: rgba(244, 63, 94, 0.1); transform: scale(1.2); }

/* Stats */
.stat-card-premium { 
  background: rgba(255, 255, 255, 0.02); 
  border: 1px solid rgba(255, 255, 255, 0.05); 
  padding: 24px; 
  border-radius: 32px; 
  display: flex; 
  align-items: center; 
  gap: 20px; 
  position: relative; 
  overflow: hidden;
  backdrop-filter: blur(10px);
}
.stat-icon { width: 56px; height: 56px; border-radius: 20px; display: flex; align-items: center; justify-content: center; font-size: 22px; border: 1px solid; }
.stat-content { display: flex; flex-direction: column; }
.stat-label { font-size: 9px; font-weight: 950; text-transform: uppercase; letter-spacing: 0.2em; color: #64748b; margin-bottom: 4px; }
.stat-value { font-size: 28px; font-weight: 950; color: white; line-height: 1; font-style: italic; }
.stat-glow { position: absolute; top: 0; right: 0; width: 60px; height: 60px; filter: blur(40px); opacity: 0.1; }

/* Stepper */
.stepper-control { display: flex; align-items: center; gap: 15px; background: rgba(0,0,0,0.2); padding: 8px; border-radius: 20px; border: 1px solid rgba(255,255,255,0.05); }
.stepper-action { background: rgba(255,255,255,0.05); border: none; width: 32px; height: 32px; border-radius: 14px; color: white; cursor: pointer; transition: 0.2s; }
.stepper-action:hover { background: var(--secondary); color: black; }
.stepper-value { font-size: 18px; font-weight: 950; width: 30px; text-align: center; color: var(--secondary); font-style: italic; }

/* Match Cards */
.match-card-premium { 
  background: rgba(255, 255, 255, 0.03); 
  border: 1px solid rgba(255, 255, 255, 0.05); 
  border-radius: 28px; 
  padding: 24px; 
  position: relative; 
  overflow: hidden; 
  cursor: pointer; 
  transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); 
  height: 100%;
}
.match-card-premium:hover { transform: translateY(-8px) scale(1.02); border-color: var(--secondary); box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5); }
.match-card-bg { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: linear-gradient(135deg, rgba(191, 149, 63, 0.05) 0%, transparent 100%); opacity: 0; transition: 0.4s; }
.match-card-premium:hover .match-card-bg { opacity: 1; }
.match-status-tag { font-size: 8px; font-weight: 950; text-transform: uppercase; padding: 4px 10px; border-radius: 8px; background: rgba(255,255,255,0.05); color: #64748b; letter-spacing: 0.1em; }
.match-status-tag.is-finished { background: rgba(16, 185, 129, 0.1); color: #10b981; }
.match-edit-icon { width: 32px; height: 32px; background: rgba(255,255,255,0.05); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: var(--secondary); opacity: 0; transition: 0.3s; transform: scale(0.5); }
.match-card-premium:hover .match-edit-icon { opacity: 1; transform: scale(1); }
.team-row { display: flex; justify-content: space-between; align-items: center; }
.team-name { font-size: 15px; font-weight: 900; color: #94a3b8; text-transform: uppercase; font-style: italic; transition: 0.3s; flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; padding-right: 15px; }
.team-name.is-winner { color: white; }
.team-score { font-size: 24px; font-weight: 950; color: #334155; font-style: italic; transition: 0.3s; }
.team-score.is-winner { color: var(--secondary); }

.stage-dot { width: 12px; height: 12px; border-radius: 4px; background: var(--secondary); box-shadow: 0 0 15px var(--secondary); transition: 0.3s; }
.stage-dot.is-collapsed { background: #475569; box-shadow: none; }

/* Scoreboard Premium Styles */
.scoreboard-premium { 
  padding: 10px;
}

.match-summary-panel { 
  background: rgba(191, 149, 63, 0.08) !important;
}

.game-score-field { 
  width: 100%;
  background: rgba(0, 0, 0, 0.3);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 12px;
  color: white;
  padding: 12px 15px;
  text-align: center;
  font-weight: 900;
  font-family: 'Inter', sans-serif;
  transition: all 0.3s;
  outline: none;
}
.game-score-field:focus { 
  border-color: var(--secondary);
  background: rgba(0,0,0,0.5);
}
.game-score-field.is-winner { 
  color: var(--secondary);
  border-color: rgba(191, 149, 63, 0.4);
  background: rgba(191, 149, 63, 0.1);
}

.quick-win-btn { 
  position: absolute;
  right: -5px;
  top: 50%;
  transform: translateY(-50%);
  background: var(--secondary);
  color: black;
  border: none;
  width: 24px;
  height: 24px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 10px;
  cursor: pointer;
  transition: 0.2s;
  opacity: 0;
  pointer-events: none;
}
.score-input-wrapper:hover .quick-win-btn { 
  opacity: 1;
  pointer-events: auto;
  right: 8px;
}
.quick-win-btn:hover { 
  transform: translateY(-50%) scale(1.1);
}

/* Animations */
@keyframes spin-slow {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}
.animate-spin-slow {
  animation: spin-slow 8s linear infinite;
}

/* Scrollbar */
.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.2); }

/* Dialog overrides */
:deep(.no-scroll .p-dialog-content) {
  overflow: hidden !important;
}
/* Premium Modal Headers */
.form-header-premium {
  position: relative;
}

.header-icon {
  width: 48px;
  height: 48px;
  background: rgba(191, 149, 63, 0.1);
  border: 1px solid rgba(191, 149, 63, 0.2);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  aspect-ratio: 1/1;
}

.header-line {
  height: 1px;
  width: 100%;
  background: linear-gradient(90deg, var(--secondary) 0%, rgba(191, 149, 63, 0.2) 50%, transparent 100%);
  opacity: 0.5;
}

.close-modal-btn {
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.05);
  color: #64748b;
  width: 32px;
  height: 32px;
  flex-shrink: 0;
  aspect-ratio: 1 / 1;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s ease;
}

.close-modal-btn:hover {
  background: rgba(239, 68, 68, 0.1);
  border-color: rgba(239, 68, 68, 0.2);
  color: #f43f5e;
  transform: rotate(90deg);
}
</style>
