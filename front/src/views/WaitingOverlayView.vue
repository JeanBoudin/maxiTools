<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue'

const timeLabel = ref('')
const totalSeconds = ref(0)
const remainingSeconds = ref(0)
const progress = computed(() => {
  if (!totalSeconds.value) return 0
  return Math.min(1, Math.max(0, 1 - remainingSeconds.value / totalSeconds.value))
})
const remainingLabel = computed(() => {
  const mins = Math.floor(remainingSeconds.value / 60)
  const secs = remainingSeconds.value % 60
  return `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`
})

const updateTime = () => {
  const now = new Date()
  timeLabel.value = now.toLocaleTimeString('fr-FR', {
    hour: '2-digit',
    minute: '2-digit',
  })
}

const readWaitingParams = () => {
  const params = new URLSearchParams(window.location.search)
  const minutesParam = Number(params.get('minutes') || params.get('m') || 0)
  const startParam = Number(params.get('start') || 0)
  if (!Number.isFinite(minutesParam) || minutesParam <= 0) {
    totalSeconds.value = 0
    remainingSeconds.value = 0
    return
  }
  totalSeconds.value = Math.floor(minutesParam * 60)
  const startMs = startParam ? startParam * 1000 : Date.now()
  const elapsed = Math.floor((Date.now() - startMs) / 1000)
  remainingSeconds.value = Math.max(0, totalSeconds.value - elapsed)
}

const tickCountdown = () => {
  if (!totalSeconds.value) return
  remainingSeconds.value = Math.max(0, remainingSeconds.value - 1)
}

onMounted(() => {
  document.documentElement.classList.remove('overlay-transparent')
  document.body.classList.remove('overlay-transparent')
  document.getElementById('app')?.classList.remove('overlay-transparent')
  updateTime()
  readWaitingParams()
  const interval = window.setInterval(updateTime, 1000)
  const countdown = window.setInterval(tickCountdown, 1000)
  document.body.dataset.waitingClock = String(interval)
  document.body.dataset.waitingCountdown = String(countdown)
})

onUnmounted(() => {
  const interval = Number(document.body.dataset.waitingClock || 0)
  if (interval) {
    window.clearInterval(interval)
    delete document.body.dataset.waitingClock
  }
  const countdown = Number(document.body.dataset.waitingCountdown || 0)
  if (countdown) {
    window.clearInterval(countdown)
    delete document.body.dataset.waitingCountdown
  }
})
</script>

<template>
  <main class="waiting-overlay">
    <div class="waiting-particles" aria-hidden="true">
      <span v-for="i in 42" :key="i" class="waiting-particle"></span>
    </div>
    <div class="waiting-veil"></div>
    <div class="waiting-frame">
      <h1 class="waiting-title">Le stream démarre</h1>
      <p class="waiting-subtitle">Installe-toi, on lance ça dans un instant.</p>
      <p class="waiting-clock">{{ timeLabel }}</p>
      <div v-if="totalSeconds" class="waiting-progress">
        <div class="waiting-progress-track">
          <div class="waiting-progress-fill" :style="{ width: `${progress * 100}%` }"></div>
        </div>
        <p class="waiting-progress-label">
          Arrive dans {{ remainingLabel }}
        </p>
      </div>

      <div class="waiting-rune">
        <span></span>
      </div>

      <p class="waiting-footer">Merci d’attendre</p>
    </div>
  </main>
</template>
