<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { useRoute } from 'vue-router'

const route = useRoute()
const backendUrl = import.meta.env.VITE_BACKEND_URL || 'http://localhost:8000'

const stats = ref({
  viewer_count: 0,
  follower_count: 0,
  subscriber_count: null,
  display_name: '',
})
const currentTime = ref('')
const error = ref('')

const overlayKey = computed(() => {
  const key = route.query.key
  return typeof key === 'string' ? key : ''
})

const statsText = computed(() => {
  if (!overlayKey.value) return 'MISSING OVERLAY KEY • '
  const subsText = stats.value.subscriber_count === null ? '--' : stats.value.subscriber_count
  return `VIEWERS: ${stats.value.viewer_count} — FOLLOWERS: ${stats.value.follower_count} — SUBS: ${subsText} — TIME: ${currentTime.value} • `
})

let statsInterval
let clockInterval

function updateTime() {
  currentTime.value = new Date().toLocaleTimeString([], {
    hour: '2-digit',
    minute: '2-digit',
  })
}

async function fetchStats() {
  if (!overlayKey.value) return
  error.value = ''

  try {
    const response = await fetch(
      `${backendUrl}/api/overlay/stats?key=${encodeURIComponent(overlayKey.value)}`
    )

    if (!response.ok) {
      throw new Error('Erreur API')
    }

    const data = await response.json()
    stats.value = {
      viewer_count: data.viewer_count ?? 0,
      follower_count: data.follower_count ?? 0,
      subscriber_count: data.subscriber_count ?? null,
      display_name: data.display_name ?? '',
    }
  } catch (err) {
    error.value = 'Erreur lors du chargement des stats.'
  }
}

onMounted(() => {
  document.documentElement.classList.add('overlay-transparent')
  document.body.classList.add('overlay-transparent')
  document.getElementById('app')?.classList.add('overlay-transparent')
  updateTime()
  fetchStats()
  statsInterval = setInterval(fetchStats, 30000)
  clockInterval = setInterval(updateTime, 10000)
})

onUnmounted(() => {
  document.documentElement.classList.remove('overlay-transparent')
  document.body.classList.remove('overlay-transparent')
  document.getElementById('app')?.classList.remove('overlay-transparent')
  clearInterval(statsInterval)
  clearInterval(clockInterval)
})
</script>

<template>
  <main class="overlay">
    <svg viewBox="0 0 500 500" aria-label="Twitch stats overlay">
      <defs>
        <path
          id="textcircle"
          d="M250,400 a150,150 0 0,1 0,-300 a150,150 0 0,1 0,300Z"
          transform="rotate(12,250,250)"
        />
      </defs>

      <g class="textcircle">
        <text textLength="940">
          <textPath xlink:href="#textcircle" textLength="940" id="statsText">
            {{ statsText }}
          </textPath>
        </text>
      </g>
    </svg>

    <div v-if="error" class="overlay-error">{{ error }}</div>
  </main>
</template>
