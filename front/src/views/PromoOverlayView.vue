<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { useRoute } from 'vue-router'

const route = useRoute()
const backendUrl = import.meta.env.VITE_BACKEND_URL || 'http://localhost'
const preview = computed(() => route.query.preview === '1')
const overlayKey = computed(() => (typeof route.query.key === 'string' ? route.query.key : ''))

const lines = ref([])
const currentIndex = ref(0)
const intervalMinutes = ref(10)
const displayDurationSeconds = ref(8)
const error = ref('')
const isVisible = ref(false)

let rotationTimer
let hideTimer

function loadFromLocalStorage() {
  const rawLines = localStorage.getItem('promo_lines') || ''
  const interval = Number(localStorage.getItem('promo_interval') || 10)
  const duration = Number(localStorage.getItem('promo_display_duration') || 8)
  intervalMinutes.value = interval > 0 ? interval : 10
  displayDurationSeconds.value = duration > 0 ? duration : 8

  let list = []
  if (rawLines) {
    try {
      const parsed = JSON.parse(rawLines)
      if (Array.isArray(parsed)) {
        list = parsed
      }
    } catch (err) {
      list = rawLines.split('\n')
    }
  }

  list = list
    .map((line) => String(line || '').trim())
    .filter(Boolean)

  lines.value = list.length > 0 ? list : ['Ajoute tes promos dans le dashboard']
}

async function loadFromApi() {
  if (!overlayKey.value) {
    lines.value = ['Clé overlay manquante']
    return
  }

  try {
    const response = await fetch(
      `${backendUrl}/api/overlay/promo?key=${encodeURIComponent(overlayKey.value)}`
    )

    if (!response.ok) {
      throw new Error('Erreur API')
    }

    const data = await response.json()
    lines.value = Array.isArray(data.lines) && data.lines.length > 0 ? data.lines : ['Promo vide']
    intervalMinutes.value = data.interval_minutes ?? 10
    displayDurationSeconds.value = data.display_duration_seconds ?? 8
  } catch (err) {
    error.value = 'Impossible de charger les promos.'
    lines.value = ['Erreur de chargement']
  }
}

function startRotation() {
  clearInterval(rotationTimer)
  clearTimeout(hideTimer)
  showNow()
  rotationTimer = setInterval(() => {
    currentIndex.value = (currentIndex.value + 1) % lines.value.length
    showNow()
  }, intervalMinutes.value * 60 * 1000)
}

function showNow() {
  isVisible.value = true
  clearTimeout(hideTimer)
  hideTimer = setTimeout(() => {
    isVisible.value = false
  }, displayDurationSeconds.value * 1000)
}

onMounted(async () => {
  document.documentElement.classList.add('overlay-transparent')
  document.body.classList.add('overlay-transparent')
  document.getElementById('app')?.classList.add('overlay-transparent')

  if (preview.value) {
    loadFromLocalStorage()
  } else {
    await loadFromApi()
  }

  startRotation()
})

onUnmounted(() => {
  document.documentElement.classList.remove('overlay-transparent')
  document.body.classList.remove('overlay-transparent')
  document.getElementById('app')?.classList.remove('overlay-transparent')
  clearInterval(rotationTimer)
  clearTimeout(hideTimer)
})
</script>

<template>
  <main class="promo-overlay">
    <div class="promo-frame" :class="{ 'is-visible': isVisible }">
      <h1 class="promo-text">{{ lines[currentIndex] }}</h1>
      <p v-if="error" class="promo-hint">{{ error }}</p>
      <p v-else-if="preview" class="promo-hint">Prévisualisation locale.</p>
    </div>
  </main>
</template>
