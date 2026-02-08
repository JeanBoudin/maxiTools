<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import DashboardLayout from '../layouts/DashboardLayout.vue'
import { useAccount } from '../state/account'

const backendUrl = import.meta.env.VITE_BACKEND_URL || 'http://localhost'
const account = useAccount()

const copied = ref(false)
const loading = ref(false)
const saved = ref(false)
const error = ref('')

const showSubGoal = ref(false)
const subGoalTitle = ref('Objectif d’abonnement')
const subGoalCurrent = ref(0)
const subGoalTarget = ref(10)
const subGoalSubtitle = ref('Nouveaux abonnements')

const cameraUrl = computed(() => {
  if (!account.overlayKey) return `${window.location.origin}/overlay/camera`
  return `${window.location.origin}/overlay/camera?key=${encodeURIComponent(account.overlayKey)}`
})

const previewNonce = ref(0)
const previewUrl = computed(() => cameraUrl.value + `&preview=${previewNonce.value}`)

async function copyUrl() {
  await navigator.clipboard.writeText(cameraUrl.value)
  copied.value = true
  setTimeout(() => (copied.value = false), 2000)
}

async function fetchSettings() {
  if (!account.overlayKey) return
  loading.value = true
  error.value = ''
  try {
    const response = await fetch(`${backendUrl}/api/camera/settings`, {
      credentials: 'include',
    })
    if (!response.ok) throw new Error('Erreur API')
    const data = await response.json()
    showSubGoal.value = Boolean(data.show_sub_goal)
    subGoalTitle.value = data.sub_goal_title || subGoalTitle.value
    subGoalCurrent.value = Number(data.sub_goal_current ?? subGoalCurrent.value)
    subGoalTarget.value = Number(data.sub_goal_target ?? subGoalTarget.value)
    subGoalSubtitle.value = data.sub_goal_subtitle || subGoalSubtitle.value
  } catch (err) {
    error.value = 'Impossible de charger les réglages.'
  } finally {
    loading.value = false
  }
}

async function saveSettings() {
  if (!account.overlayKey) return
  loading.value = true
  error.value = ''

  try {
    const response = await fetch(`${backendUrl}/api/camera/settings`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      credentials: 'include',
      body: JSON.stringify({
        show_sub_goal: showSubGoal.value,
        sub_goal_title: subGoalTitle.value,
        sub_goal_current: subGoalCurrent.value,
        sub_goal_target: subGoalTarget.value,
        sub_goal_subtitle: subGoalSubtitle.value,
      }),
    })

    if (!response.ok) throw new Error('Erreur API')

    saved.value = true
    previewNonce.value += 1
    setTimeout(() => (saved.value = false), 2000)
  } catch (err) {
    error.value = 'Impossible de sauvegarder.'
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchSettings()
  previewNonce.value += 1
})

watch(
  () => account.overlayKey,
  (value, oldValue) => {
    if (value && value !== oldValue) {
      fetchSettings()
    }
  }
)
</script>

<template>
  <DashboardLayout>
    <template #header>
      <p class="eyebrow">Overlays</p>
      <h1>Cadres caméra</h1>
      <p class="subtitle">Un overlay transparent avec un cadre centré pour ta caméra.</p>
    </template>

    <div class="bento">
      <article class="bento-card bento-hero">
        <div>
          <p class="eyebrow">Cadre caméra</p>
          <h2>Overlay simple et propre</h2>
          <p class="subtitle">Colle ce lien dans OBS pour positionner ta caméra.</p>
        </div>
        <div class="bento-actions">
          <button class="secondary" type="button" @click="copyUrl">
            {{ copied ? 'Copié' : 'Copier le lien' }}
          </button>
          <a class="ghost" :href="cameraUrl" target="_blank" rel="noreferrer">Ouvrir l’overlay</a>
        </div>
      </article>

      <article class="bento-card">
        <div class="bento-head">
          <h3>Objectif d’abonnements</h3>
          <span class="tag">Optionnel</span>
        </div>
        <p class="subtitle">Affiche un bandeau de progression sous la caméra.</p>
        <div class="form-grid">
          <label class="form-field">
            <span class="label">Activer</span>
            <select v-model="showSubGoal" class="input" :disabled="loading">
              <option :value="true">Oui</option>
              <option :value="false">Non</option>
            </select>
          </label>
          <label class="form-field">
            <span class="label">Titre</span>
            <input v-model="subGoalTitle" class="input" type="text" :disabled="loading" />
          </label>
          <label class="form-field">
            <span class="label">Actuel</span>
            <input v-model.number="subGoalCurrent" class="input" type="number" min="0" :disabled="loading" />
          </label>
          <label class="form-field">
            <span class="label">Objectif</span>
            <input v-model.number="subGoalTarget" class="input" type="number" min="1" :disabled="loading" />
          </label>
          <label class="form-field">
            <span class="label">Sous-titre</span>
            <input v-model="subGoalSubtitle" class="input" type="text" :disabled="loading" />
          </label>
        </div>
        <div class="bento-actions">
          <button class="primary" type="button" @click="saveSettings" :disabled="loading">
            {{ saved ? 'Sauvegardé' : loading ? 'Sauvegarde…' : 'Sauvegarder' }}
          </button>
          <button class="ghost" type="button" :disabled="loading" @click="subGoalCurrent += 1; previewNonce += 1">
            +1 abonné (test)
          </button>
          <button class="ghost" type="button" :disabled="loading" @click="previewNonce += 1">
            Rafraîchir preview
          </button>
        </div>
        <p v-if="error" class="form-error">{{ error }}</p>
        <p v-if="!account.overlayKey" class="micro">Connecte Twitch pour sauvegarder côté serveur.</p>
      </article>

      <article class="bento-card">
        <div class="bento-head">
          <h3>Prévisualisation</h3>
          <span class="tag">Live</span>
        </div>
        <p class="subtitle">Aperçu direct du cadre avec l’objectif.</p>
        <div class="preview-frame">
          <iframe class="preview-iframe" :src="previewUrl" title="Prévisualisation cadre caméra"></iframe>
        </div>
      </article>

      <article class="bento-card">
        <div class="bento-head">
          <h3>Lien OBS</h3>
          <span class="tag">Actif</span>
        </div>
        <p class="subtitle">Utilise ce lien dans OBS (Browser Source).</p>
        <div class="bento-body">
          <p class="label">Lien OBS</p>
          <p class="mono">{{ cameraUrl }}</p>
        </div>
      </article>
    </div>
  </DashboardLayout>
</template>
