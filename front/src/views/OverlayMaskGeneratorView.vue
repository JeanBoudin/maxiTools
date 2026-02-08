<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import DashboardLayout from '../layouts/DashboardLayout.vue'

const shape = ref('rect')
const width = ref(1280)
const height = ref(720)
const diameter = ref(800)
const radius = ref(32)
const inverted = ref(false)
const padding = ref(24)

const canvasRef = ref(null)

const canvasWidth = computed(() => (shape.value === 'circle' ? diameter.value : width.value))
const canvasHeight = computed(() => (shape.value === 'circle' ? diameter.value : height.value))

function draw() {
  const canvas = canvasRef.value
  if (!canvas) return

  const w = Math.max(1, Number(canvasWidth.value || 1))
  const h = Math.max(1, Number(canvasHeight.value || 1))
  const pad = Math.max(0, Number(padding.value || 0))

  canvas.width = w
  canvas.height = h

  const ctx = canvas.getContext('2d')
  if (!ctx) return

  ctx.clearRect(0, 0, w, h)

  const bg = inverted.value ? '#ffffff' : '#000000'
  const fg = inverted.value ? '#000000' : '#ffffff'

  ctx.fillStyle = bg
  ctx.fillRect(0, 0, w, h)

  ctx.fillStyle = fg
  if (shape.value === 'circle') {
    const r = Math.max(0, Math.min(w, h) / 2 - pad)
    ctx.beginPath()
    ctx.arc(w / 2, h / 2, r, 0, Math.PI * 2)
    ctx.fill()
  } else if (shape.value === 'round') {
    const r = Math.max(0, Math.min(Number(radius.value || 0), Math.min(w, h) / 2))
    const left = pad
    const top = pad
    const right = w - pad
    const bottom = h - pad
    ctx.beginPath()
    ctx.moveTo(left + r, top)
    ctx.lineTo(right - r, top)
    ctx.quadraticCurveTo(right, top, right, top + r)
    ctx.lineTo(right, bottom - r)
    ctx.quadraticCurveTo(right, bottom, right - r, bottom)
    ctx.lineTo(left + r, bottom)
    ctx.quadraticCurveTo(left, bottom, left, bottom - r)
    ctx.lineTo(left, top + r)
    ctx.quadraticCurveTo(left, top, left + r, top)
    ctx.closePath()
    ctx.fill()
  } else {
    ctx.fillRect(pad, pad, Math.max(0, w - pad * 2), Math.max(0, h - pad * 2))
  }
}

function download() {
  const canvas = canvasRef.value
  if (!canvas) return
  const link = document.createElement('a')
  link.download = `mask-${shape.value}-${canvas.width}x${canvas.height}.png`
  link.href = canvas.toDataURL('image/png')
  link.click()
}

watch([shape, width, height, diameter, radius, padding, inverted], draw)

onMounted(() => {
  draw()
})
</script>

<template>
  <DashboardLayout>
    <template #header>
      <p class="eyebrow">Overlays</p>
      <h1>Générateur de masque PNG</h1>
      <p class="subtitle">Crée un masque OBS (blanc = visible, noir = transparent).</p>
    </template>

    <div class="bento">
      <article class="bento-card">
        <div class="form-grid">
          <label class="form-field">
            <span class="label">Forme</span>
            <select v-model="shape" class="input">
              <option value="rect">Rectangle</option>
              <option value="round">Rectangle arrondi</option>
              <option value="circle">Cercle</option>
            </select>
          </label>

          <label v-if="shape !== 'circle'" class="form-field">
            <span class="label">Largeur (px)</span>
            <input v-model.number="width" class="input" type="number" min="1" />
          </label>

          <label v-if="shape !== 'circle'" class="form-field">
            <span class="label">Hauteur (px)</span>
            <input v-model.number="height" class="input" type="number" min="1" />
          </label>

          <label v-if="shape === 'circle'" class="form-field">
            <span class="label">Diamètre (px)</span>
            <input v-model.number="diameter" class="input" type="number" min="1" />
          </label>

          <label v-if="shape === 'round'" class="form-field">
            <span class="label">Rayon (px)</span>
            <input v-model.number="radius" class="input" type="number" min="0" />
          </label>

          <label class="form-field">
            <span class="label">Marge (px)</span>
            <input v-model.number="padding" class="input" type="number" min="0" />
          </label>

          <label class="form-field">
            <span class="label">Inverser (noir/white)</span>
            <select v-model="inverted" class="input">
              <option :value="false">Blanc visible</option>
              <option :value="true">Noir visible</option>
            </select>
          </label>
        </div>

        <div class="bento-actions">
          <button class="primary" type="button" @click="download">Générer PNG</button>
        </div>
      </article>

      <article class="bento-card">
        <div class="bento-head">
          <h3>Prévisualisation</h3>
          <span class="tag">PNG</span>
        </div>
        <div class="preview-frame">
          <canvas ref="canvasRef" class="mask-canvas"></canvas>
        </div>
      </article>
    </div>
  </DashboardLayout>
</template>
