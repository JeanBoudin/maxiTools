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
  } catch (err) {
    // ignore
  }
}

onMounted(() => {
  document.documentElement.classList.add('overlay-transparent')
  document.body.classList.add('overlay-transparent')
  document.getElementById('app')?.classList.add('overlay-transparent')
  loadSettings()
})

onUnmounted(() => {
  document.documentElement.classList.remove('overlay-transparent')
  document.body.classList.remove('overlay-transparent')
  document.getElementById('app')?.classList.remove('overlay-transparent')
})
</script>

<template>
  <main class="camera-frame-overlay">
    <div class="camera-frame-box">
      <div class="camera-frame-box-inner"></div>
      <div v-if="goalEnabled" class="camera-goal">
        <div class="camera-goal-row">
          <span class="camera-goal-title">⭐ {{ goalTitle }}</span>
          <span>{{ goalCurrent }}/{{ goalTarget }}</span>
        </div>
        <div class="camera-goal-progress">
          <span :style="{ width: `${progressPercent}%` }"></span>
        </div>
        <div class="camera-goal-row">
          <span>{{ goalSubtitle }}</span>
          <span>{{ progressPercent }}%</span>
        </div>
      </div>
    </div>
  </main>
</template>
