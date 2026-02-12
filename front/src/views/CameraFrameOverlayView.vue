<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { useRoute } from 'vue-router'

const route = useRoute()
const backendUrl = import.meta.env.VITE_BACKEND_URL || 'http://localhost'
const overlayKey = computed(() => (typeof route.query.key === 'string' ? route.query.key : ''))

const goalEnabled = ref(false)
const goalTitle = ref('Objectif d’abonnement')
const goalCurrent = ref(0)
const goalTarget = ref(10)
const goalSubtitle = ref('Nouveaux abonnements')
const rotationEnabled = ref(false)
const rotationIntervalSeconds = ref(12)
const rotationItems = ref(['sub_goal', 'now_playing'])
const nowPlayingTitle = ref('Musique en cours')
const nowPlayingEmpty = ref('Rien en lecture')
const spotifyConnected = ref(false)
const nowPlayingTrack = ref('')
const nowPlayingArtist = ref('')
const activeSlot = ref('sub_goal')

let rotationTimer
let nowPlayingTimer

const progressPercent = computed(() => {
  if (!goalTarget.value) return 0
  return Math.min(100, Math.round((goalCurrent.value / goalTarget.value) * 100))
})

async function loadSettings() {
  if (!overlayKey.value) return
  try {
    const response = await fetch(
      `${backendUrl}/api/overlay/camera?key=${encodeURIComponent(overlayKey.value)}`
    )
    if (!response.ok) return
    const data = await response.json()
    goalEnabled.value = Boolean(data.show_sub_goal)
    goalTitle.value = data.sub_goal_title || goalTitle.value
    goalCurrent.value = Number(data.sub_goal_current ?? goalCurrent.value)
    goalTarget.value = Number(data.sub_goal_target ?? goalTarget.value)
    goalSubtitle.value = data.sub_goal_subtitle || goalSubtitle.value
    rotationEnabled.value = Boolean(data.rotation_enabled)
    rotationIntervalSeconds.value = Number(data.rotation_interval_seconds ?? rotationIntervalSeconds.value)
    rotationItems.value = Array.isArray(data.rotation_items) ? data.rotation_items : rotationItems.value
    nowPlayingTitle.value = data.now_playing_title || nowPlayingTitle.value
    nowPlayingEmpty.value = data.now_playing_empty || nowPlayingEmpty.value
    startRotation()
  } catch (err) {
    // ignore
  }
}

function availableSlots() {
  const slots = []
  if (rotationItems.value.includes('sub_goal') && goalEnabled.value) {
    slots.push('sub_goal')
  }
  if (rotationItems.value.includes('now_playing')) {
    slots.push('now_playing')
  }
  return slots
}

function startRotation() {
  clearInterval(rotationTimer)
  const slots = availableSlots()
  if (slots.length === 0) {
    activeSlot.value = ''
    return
  }
  if (!rotationEnabled.value || slots.length <= 1) {
    activeSlot.value = slots[0]
    return
  }
  activeSlot.value = slots[0]
  rotationTimer = setInterval(() => {
    const index = slots.indexOf(activeSlot.value)
    activeSlot.value = slots[(index + 1) % slots.length]
  }, rotationIntervalSeconds.value * 1000)
}

async function loadNowPlaying() {
  if (!overlayKey.value) return
  try {
    const response = await fetch(
      `${backendUrl}/api/overlay/now-playing?key=${encodeURIComponent(overlayKey.value)}`
    )
    if (!response.ok) return
    const data = await response.json()
    spotifyConnected.value = Boolean(data.connected)
    nowPlayingTrack.value = data.track || ''
    nowPlayingArtist.value = data.artist || ''
  } catch (err) {
    // ignore
  }
}

const nowPlayingLine = computed(() => {
  if (nowPlayingTrack.value) {
    return nowPlayingArtist.value
      ? `${nowPlayingTrack.value} · ${nowPlayingArtist.value}`
      : nowPlayingTrack.value
  }
  return nowPlayingEmpty.value
})

onMounted(() => {
  document.documentElement.classList.add('overlay-transparent')
  document.body.classList.add('overlay-transparent')
  document.getElementById('app')?.classList.add('overlay-transparent')
  loadSettings()
  loadNowPlaying()
  nowPlayingTimer = setInterval(loadNowPlaying, 10000)
})

onUnmounted(() => {
  document.documentElement.classList.remove('overlay-transparent')
  document.body.classList.remove('overlay-transparent')
  document.getElementById('app')?.classList.remove('overlay-transparent')
  clearInterval(rotationTimer)
  clearInterval(nowPlayingTimer)
})
</script>

<template>
  <main class="camera-frame-overlay">
    <div class="camera-frame-box">
      <div class="camera-frame-box-inner"></div>
      <div v-if="activeSlot" class="camera-goal">
        <template v-if="activeSlot === 'sub_goal'">
          <div class="camera-goal-row">
            <span class="camera-goal-title">{{ goalTitle }}</span>
            <span>{{ goalCurrent }}/{{ goalTarget }}</span>
          </div>
          <div class="camera-goal-progress">
            <span :style="{ width: `${progressPercent}%` }"></span>
          </div>
          <div class="camera-goal-row">
            <span>{{ goalSubtitle }}</span>
            <span>{{ progressPercent }}%</span>
          </div>
        </template>
        <template v-else-if="activeSlot === 'now_playing'">
          <div class="camera-goal-row">
            <span class="camera-goal-title">{{ nowPlayingTitle }}</span>
            <span class="camera-goal-pill">{{ spotifyConnected ? 'Live' : 'Hors ligne' }}</span>
          </div>
          <div class="camera-goal-row camera-goal-now">
            <span>{{ nowPlayingLine }}</span>
          </div>
        </template>
      </div>
    </div>
  </main>
</template>
