<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import DashboardLayout from '../layouts/DashboardLayout.vue'
import { useAccount } from '../state/account'

const backendUrl = import.meta.env.VITE_BACKEND_URL || 'http://localhost'
const account = useAccount()
const isConnected = computed(() => Boolean(account.overlayKey))

const promoLines = ref([])
const intervalMinutes = ref(10)
const displayDurationSeconds = ref(8)
const saved = ref(false)
const loading = ref(false)
const error = ref('')

const previewUrl = computed(() => `${window.location.origin}/overlay/promo?preview=1`)
const obsUrl = computed(() => {
  if (!account.overlayKey) return ''
  return `${window.location.origin}/overlay/promo?key=${encodeURIComponent(account.overlayKey)}`
})

const presetTemplates = [
  'YouTube: youtube.com/tonpseudo',
  'TikTok: @tonpseudo',
  'X (Twitter): @tonpseudo',
  'Twitch: twitch.tv/tonpseudo',
  'Discord: discord.gg/tonserveur',
  'Instagram: @tonpseudo',
]

function defaultLines() {
  return ['Suivez-moi sur Instagram @tonpseudo', 'Retrouvez-moi sur YouTube /tonpseudo']
}

function normalizeLines(lines) {
  const cleaned = lines
    .map((line) => String(line || '').trim())
    .filter(Boolean)

  return cleaned.length > 0 ? cleaned : defaultLines()
}

function loadLocalLines() {
  const raw = localStorage.getItem('promo_lines')
  if (!raw) return defaultLines()

  try {
    const parsed = JSON.parse(raw)
    if (Array.isArray(parsed)) {
      return normalizeLines(parsed)
    }
  } catch (err) {
    // ignore
  }

  return normalizeLines(raw.split('\n'))
}

function hydrateLocal(linesValue, intervalValue, durationValue) {
  localStorage.setItem('promo_lines', JSON.stringify(linesValue))
  localStorage.setItem('promo_interval', String(intervalValue || 10))
  localStorage.setItem('promo_display_duration', String(durationValue || 8))
}

function addLine(value = '') {
  promoLines.value.push(value)
}

function removeLine(index) {
  promoLines.value.splice(index, 1)
}

function addPreset(value) {
  addLine(value)
}

async function fetchSettings() {
  if (!account.overlayKey) {
    promoLines.value = loadLocalLines()
    intervalMinutes.value = Number(localStorage.getItem('promo_interval') || 10)
    displayDurationSeconds.value = Number(localStorage.getItem('promo_display_duration') || 8)
    return
  }

  loading.value = true
  error.value = ''
  try {
    const response = await fetch(`${backendUrl}/api/promo/settings`, {
      credentials: 'include',
    })

    if (!response.ok) {
      throw new Error('Erreur API')
    }

    const data = await response.json()
    const apiLines = Array.isArray(data.lines) ? data.lines : []
    promoLines.value = normalizeLines(apiLines)
    intervalMinutes.value = data.interval_minutes ?? 10
    displayDurationSeconds.value = data.display_duration_seconds ?? 8
    hydrateLocal(promoLines.value, intervalMinutes.value, displayDurationSeconds.value)
  } catch (err) {
    error.value = 'Impossible de charger les promos.'
  } finally {
    loading.value = false
  }
}

async function saveSettings() {
  const normalizedLines = normalizeLines(promoLines.value)
  promoLines.value = normalizedLines

  hydrateLocal(normalizedLines, intervalMinutes.value, displayDurationSeconds.value)

  if (!account.overlayKey) {
    saved.value = true
    setTimeout(() => (saved.value = false), 2000)
    return
  }

  loading.value = true
  error.value = ''

  try {
    const response = await fetch(`${backendUrl}/api/promo/settings`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      credentials: 'include',
      body: JSON.stringify({
        lines: normalizedLines,
        interval_minutes: intervalMinutes.value || 10,
        display_duration_seconds: displayDurationSeconds.value || 8,
      }),
    })

    if (!response.ok) {
      throw new Error('Erreur API')
    }

    saved.value = true
    setTimeout(() => (saved.value = false), 2000)
  } catch (err) {
    error.value = 'Impossible de sauvegarder les promos.'
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchSettings()
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
      <p class="eyebrow">Promos</p>
      <h1>Fais tourner tes réseaux sociaux.</h1>
      <p class="subtitle">Un overlay qui affiche tes liens toutes les X minutes.</p>
    </template>

    <div class="bento">
      <article class="bento-card bento-hero">
        <div>
          <p class="eyebrow">Configuration</p>
          <h2>Définis tes messages promo</h2>
          <p class="subtitle">
            Chaque entrée devient un message qui s’affiche à tour de rôle.
          </p>
        </div>
        <div class="bento-actions">
          <button class="primary" type="button" @click="saveSettings" :disabled="loading">
            {{ saved ? 'Sauvegardé' : loading ? 'Sauvegarde…' : 'Sauvegarder' }}
          </button>
          <a class="ghost" :href="previewUrl" target="_blank" rel="noreferrer">Prévisualiser</a>
        </div>
      </article>

      <article class="bento-card">
        <div class="form-grid">
          <div class="form-field">
            <span class="label">Messages promo</span>
            <div class="promo-list">
              <div v-for="(line, index) in promoLines" :key="index" class="promo-row">
                <input
                  v-model="promoLines[index]"
                  class="input"
                  type="text"
                  :disabled="loading"
                  placeholder="Ex: YouTube: youtube.com/tonpseudo"
                />
                <button
                  class="icon-button"
                  type="button"
                  :disabled="loading"
                  @click="removeLine(index)"
                >
                  Supprimer
                </button>
              </div>
            </div>
            <div class="promo-actions">
              <button class="ghost" type="button" @click="addLine()" :disabled="loading">
                Ajouter un message
              </button>
            </div>
          </div>

          <div class="form-field">
            <span class="label">Raccourcis</span>
            <div class="chip-row">
              <button
                v-for="preset in presetTemplates"
                :key="preset"
                class="chip"
                type="button"
                :disabled="loading"
                @click="addPreset(preset)"
              >
                {{ preset.split(':')[0] }}
              </button>
            </div>
          </div>

          <label class="form-field">
            <span class="label">Intervalle (minutes)</span>
            <input
              v-model.number="intervalMinutes"
              type="number"
              min="1"
              max="120"
              class="input"
              :disabled="loading"
            />
          </label>

          <label class="form-field">
            <span class="label">Durée d’affichage (secondes)</span>
            <input
              v-model.number="displayDurationSeconds"
              type="number"
              min="2"
              max="60"
              class="input"
              :disabled="loading"
            />
          </label>
        </div>
        <p v-if="error" class="form-error">{{ error }}</p>
        <p v-if="!isConnected" class="micro">Connecte Twitch pour sauvegarder côté serveur.</p>
      </article>

      <article class="bento-card">
        <div class="bento-head">
          <h3>Lien OBS</h3>
          <span class="tag">Actif</span>
        </div>
        <p class="subtitle">Ce lien utilisera la configuration sauvegardée côté serveur.</p>
        <div class="bento-body">
          <p class="label">Lien OBS</p>
          <p class="mono">{{ obsUrl || 'Connecte Twitch pour générer la clé.' }}</p>
        </div>
      </article>
    </div>
  </DashboardLayout>
</template>
