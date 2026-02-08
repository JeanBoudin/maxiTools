<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { useRoute } from 'vue-router'

const route = useRoute()
const backendUrl = import.meta.env.VITE_BACKEND_URL || 'http://localhost'

const overlayKey = computed(() => (typeof route.query.key === 'string' ? route.query.key : ''))
const speed = computed(() => Math.max(0.2, Number(route.query.speed || 1)))
const intensity = computed(() => Math.max(0.2, Number(route.query.intensity || 1)))

const seedString = ref('StreamDNA')

function hashString(input) {
  let h = 2166136261
  for (let i = 0; i < input.length; i += 1) {
    h ^= input.charCodeAt(i)
    h += (h << 1) + (h << 4) + (h << 7) + (h << 8) + (h << 24)
  }
  return h >>> 0
}

function mulberry32(seed) {
  return () => {
    let t = (seed += 0x6d2b79f5)
    t = Math.imul(t ^ (t >>> 15), t | 1)
    t ^= t + Math.imul(t ^ (t >>> 7), t | 61)
    return ((t ^ (t >>> 14)) >>> 0) / 4294967296
  }
}

function buildPath(seed, radius, amplitude, points = 140, phase = 0) {
  const rand = mulberry32(seed)
  const step = (Math.PI * 2) / points
  const coords = []

  for (let i = 0; i <= points; i += 1) {
    const angle = i * step + phase
    const spike = rand() > 0.82 ? 2.2 : 1
    const noise = (rand() - 0.5) * amplitude * spike
    const r = radius + noise + Math.sin(angle * 2) * amplitude * 0.35
    const x = 250 + Math.cos(angle) * r
    const y = 250 + Math.sin(angle) * r
    coords.push([x, y])
  }

  let d = `M ${coords[0][0].toFixed(2)} ${coords[0][1].toFixed(2)}`
  for (let i = 1; i < coords.length; i += 1) {
    d += ` L ${coords[i][0].toFixed(2)} ${coords[i][1].toFixed(2)}`
  }
  return d
}

const pathA = computed(() => {
  const seed = hashString(seedString.value)
  return buildPath(seed, 150, 60 * intensity.value, 96, 0)
})

const pathB = computed(() => {
  const seed = hashString(seedString.value + 'b')
  return buildPath(seed, 120, 55 * intensity.value, 96, Math.PI / 6)
})

const accent = computed(() => {
  return '#8b0000'
})

const accentAlt = computed(() => {
  return '#9a9a9a'
})

async function loadSeed() {
  if (!overlayKey.value) return
  try {
    const response = await fetch(
      `${backendUrl}/api/overlay/stats?key=${encodeURIComponent(overlayKey.value)}`
    )
    if (!response.ok) return
    const data = await response.json()
    seedString.value = data.display_name || data.login || seedString.value
  } catch (err) {
    // ignore
  }
}

onMounted(() => {
  document.documentElement.classList.add('overlay-transparent')
  document.body.classList.add('overlay-transparent')
  document.getElementById('app')?.classList.add('overlay-transparent')
  loadSeed()
})

onUnmounted(() => {
  document.documentElement.classList.remove('overlay-transparent')
  document.body.classList.remove('overlay-transparent')
  document.getElementById('app')?.classList.remove('overlay-transparent')
})
</script>

<template>
  <main class="dna-overlay">
    <svg viewBox="0 0 500 500" class="dna-svg" aria-label="Stream DNA">
      <defs>
        <filter id="dnaRough">
          <feTurbulence type="fractalNoise" baseFrequency="0.9" numOctaves="2" seed="8" />
          <feDisplacementMap in="SourceGraphic" scale="6" />
        </filter>
        <filter id="dnaGlow">
          <feGaussianBlur stdDeviation="3" result="blur" />
          <feMerge>
            <feMergeNode in="blur" />
            <feMergeNode in="SourceGraphic" />
          </feMerge>
        </filter>
        <linearGradient id="dnaMetal" x1="0%" y1="0%" x2="100%" y2="100%">
          <stop offset="0%" stop-color="#1f1f1f" />
          <stop offset="30%" stop-color="#bfbfbf" />
          <stop offset="60%" stop-color="#6a6a6a" />
          <stop offset="100%" stop-color="#2a2a2a" />
        </linearGradient>
      </defs>
      <g :style="{ animationDuration: `${34 / speed}s` }" class="dna-rotate" filter="url(#dnaRough)">
        <path :d="pathA" class="dna-path dna-brutal" :style="{ stroke: accent }" />
        <path :d="pathB" class="dna-path dna-brutal dna-secondary" stroke="url(#dnaMetal)" />
      </g>
    </svg>
  </main>
</template>
