<script setup>
import { computed, onMounted, ref } from 'vue'
import DashboardLayout from '../layouts/DashboardLayout.vue'
import { useAccount } from '../state/account'

const backendUrl = import.meta.env.VITE_BACKEND_URL || 'http://localhost'
const account = useAccount()

const host = ref('host.docker.internal')
const port = ref(4455)
const useTls = ref(false)
const password = ref('')
const hasPassword = ref(false)

const loading = ref(false)
const saved = ref(false)
const error = ref('')

const scenes = ref([])
const scenesLoading = ref(false)
const scenesError = ref('')
const switchMessage = ref('')
const activeScene = ref('')

const publicBase = computed(() => {
  if (!account.overlayKey) return ''
  return `${backendUrl}/api/obs/switch?key=${encodeURIComponent(account.overlayKey)}`
})

function sceneUrl(sceneName) {
  if (!publicBase.value) return ''
  return `${publicBase.value}&scene=${encodeURIComponent(sceneName)}`
}

async function fetchSettings() {
  loading.value = true
  error.value = ''
  try {
    const response = await fetch(`${backendUrl}/api/obs/settings`, {
      credentials: 'include',
    })
    if (!response.ok) throw new Error('Erreur API')
    const data = await response.json()
    host.value = data.host || 'host.docker.internal'
    port.value = data.port || 4455
    useTls.value = Boolean(data.use_tls)
    hasPassword.value = Boolean(data.has_password)
  } catch (err) {
    error.value = 'Impossible de charger la config OBS.'
  } finally {
    loading.value = false
  }
}

async function saveSettings() {
  loading.value = true
  error.value = ''
  try {
    const response = await fetch(`${backendUrl}/api/obs/settings`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      credentials: 'include',
      body: JSON.stringify({
        host: host.value,
        port: Number(port.value),
        use_tls: useTls.value,
        password: password.value || undefined,
      }),
    })
    if (!response.ok) throw new Error('Erreur API')
    const data = await response.json()
    hasPassword.value = Boolean(data.has_password)
    password.value = ''
    saved.value = true
    setTimeout(() => (saved.value = false), 2000)
  } catch (err) {
    error.value = 'Impossible de sauvegarder la config OBS.'
  } finally {
    loading.value = false
  }
}

async function fetchScenes() {
  scenesLoading.value = true
  scenesError.value = ''
  try {
    const response = await fetch(`${backendUrl}/api/obs/scenes`, {
      method: 'POST',
      credentials: 'include',
    })
    if (!response.ok) {
      const payload = await response.json().catch(() => ({}))
      throw new Error(payload.message || 'Erreur API')
    }
    const data = await response.json()
    scenes.value = Array.isArray(data.scenes) ? data.scenes : []
    activeScene.value = data.current_scene || ''
  } catch (err) {
    scenesError.value = err.message || 'Impossible de charger les scènes.'
  } finally {
    scenesLoading.value = false
  }
}

async function switchScene(sceneName) {
  switchMessage.value = ''
  try {
    const response = await fetch(`${backendUrl}/api/obs/switch`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      credentials: 'include',
      body: JSON.stringify({ scene: sceneName }),
    })
    if (!response.ok) {
      const payload = await response.json().catch(() => ({}))
      throw new Error(payload.message || 'Erreur API')
    }
    activeScene.value = sceneName
    switchMessage.value = `Scène changée → ${sceneName}`
    setTimeout(() => (switchMessage.value = ''), 2500)
  } catch (err) {
    switchMessage.value = err.message || 'Impossible de changer la scène.'
  }
}

async function copySceneUrl(sceneName) {
  const url = sceneUrl(sceneName)
  if (!url) return
  try {
    await navigator.clipboard.writeText(url)
    switchMessage.value = 'URL copiée.'
    setTimeout(() => (switchMessage.value = ''), 2000)
  } catch (err) {
    switchMessage.value = 'Copie impossible.'
  }
}

onMounted(() => {
  fetchSettings()
  fetchScenes()
})
</script>

<template>
  <DashboardLayout>
    <template #header>
      <p class="eyebrow">OBS Scenes</p>
      <h1>Change tes scènes via une simple URL.</h1>
      <p class="subtitle">
        Configure OBS WebSocket, récupère tes scènes, puis utilise les liens pour switcher.
      </p>
    </template>

    <div class="bento">
      <article class="bento-card bento-hero">
        <div>
          <p class="eyebrow">Connexion OBS</p>
          <h2>Configure l’accès WebSocket</h2>
          <p class="subtitle">
            Si OBS tourne sur ta machine, utilise <span class="mono">host.docker.internal</span> quand
            le backend est en Docker. Port par défaut: <span class="mono">4455</span>.
          </p>
        </div>
        <div class="bento-actions">
          <button class="primary" type="button" @click="saveSettings" :disabled="loading">
            {{ saved ? 'Sauvegardé' : loading ? 'Sauvegarde…' : 'Sauvegarder' }}
          </button>
          <button class="ghost" type="button" @click="fetchScenes" :disabled="scenesLoading">
            {{ scenesLoading ? 'Connexion…' : 'Lister les scènes' }}
          </button>
        </div>
      </article>

      <article class="bento-card">
        <div class="form-grid">
          <div class="form-field">
            <span class="label">Host OBS</span>
            <input v-model="host" class="input" type="text" placeholder="host.docker.internal" />
          </div>
          <div class="form-field">
            <span class="label">Port</span>
            <input v-model.number="port" class="input" type="number" min="1" max="65535" />
          </div>
          <div class="form-field">
            <span class="label">Mot de passe (OBS WebSocket)</span>
            <input
              v-model="password"
              class="input"
              type="password"
              placeholder="Laisser vide pour ne pas changer"
            />
            <p v-if="hasPassword" class="muted">Un mot de passe est déjà enregistré.</p>
          </div>
          <label class="toggle">
            <input v-model="useTls" type="checkbox" />
            <span>Utiliser TLS (wss)</span>
          </label>
        </div>
        <p v-if="error" class="form-error">{{ error }}</p>
      </article>

      <article class="bento-card obs-scenes-card">
        <div class="bento-head">
          <div>
            <p class="eyebrow">Scènes</p>
            <h3>Vos scènes OBS</h3>
            <p class="subtitle">
              Copie l’URL d’une scène pour créer un bouton dans StreamDeck, un raccourci, etc.
            </p>
          </div>
          <p v-if="switchMessage" class="status-text">{{ switchMessage }}</p>
        </div>

        <div v-if="scenes.length === 0" class="empty-state">
          <p class="subtitle">Aucune scène chargée.</p>
          <p class="muted">Clique sur “Lister les scènes” après avoir configuré OBS.</p>
        </div>

        <div v-else class="obs-pad-grid">
          <button
            v-for="scene in scenes"
            :key="scene"
            class="obs-pad"
            :class="{ active: activeScene === scene }"
            type="button"
            @click="switchScene(scene)"
          >
            <span class="obs-pad-title">{{ scene }}</span>
            <span class="obs-pad-actions">
              <span class="obs-pad-action" @click.stop="copySceneUrl(scene)">Copier URL</span>
              <span class="obs-pad-action">Tester</span>
            </span>
          </button>
        </div>

        <p v-if="scenesError" class="form-error">{{ scenesError }}</p>
      </article>
    </div>
  </DashboardLayout>
</template>
