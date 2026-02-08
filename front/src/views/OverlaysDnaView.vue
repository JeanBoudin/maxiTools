<script setup>
import { computed, ref } from 'vue'
import DashboardLayout from '../layouts/DashboardLayout.vue'
import { useAccount } from '../state/account'

const account = useAccount()
const speed = ref(1)
const intensity = ref(1)

const overlayUrl = computed(() => {
  if (!account.overlayKey) return `${window.location.origin}/overlay/dna`
  return `${window.location.origin}/overlay/dna?key=${encodeURIComponent(account.overlayKey)}`
})

const previewUrl = computed(() => {
  const base = overlayUrl.value
  const joiner = base.includes('?') ? '&' : '?'
  return `${base}${joiner}speed=${speed.value}&intensity=${intensity.value}`
})
</script>

<template>
  <DashboardLayout>
    <template #header>
      <p class="eyebrow">Overlays</p>
      <h1>Stream DNA</h1>
      <p class="subtitle">Signature visuelle générée à partir du pseudo Twitch.</p>
    </template>

    <div class="bento">
      <article class="bento-card bento-hero">
        <div>
          <p class="eyebrow">Signature</p>
          <h2>Motif unique pour ton stream</h2>
          <p class="subtitle">
            L’ADN visuel est dérivé de ton pseudo et évolue subtilement dans la scène.
          </p>
        </div>
        <div class="bento-actions">
          <a class="ghost" :href="previewUrl" target="_blank" rel="noreferrer">Ouvrir l’overlay</a>
        </div>
      </article>

      <article class="bento-card">
        <div class="bento-head">
          <h3>Réglages</h3>
          <span class="tag">Live</span>
        </div>
        <div class="form-grid">
          <label class="form-field">
            <span class="label">Vitesse</span>
            <input v-model.number="speed" class="input" type="range" min="0.4" max="2.5" step="0.1" />
          </label>
          <label class="form-field">
            <span class="label">Intensité</span>
            <input
              v-model.number="intensity"
              class="input"
              type="range"
              min="0.4"
              max="2.5"
              step="0.1"
            />
          </label>
        </div>
        <div class="bento-actions">
          <a class="ghost" :href="previewUrl" target="_blank" rel="noreferrer">Prévisualiser</a>
        </div>
      </article>

      <article class="bento-card">
        <div class="bento-head">
          <h3>Prévisualisation</h3>
          <span class="tag">Live</span>
        </div>
        <div class="preview-frame">
          <iframe class="preview-iframe" :src="previewUrl" title="Preview Stream DNA"></iframe>
        </div>
      </article>

      <article class="bento-card">
        <div class="bento-head">
          <h3>Lien OBS</h3>
          <span class="tag">Actif</span>
        </div>
        <p class="subtitle">Colle ce lien dans OBS (Browser Source).</p>
        <div class="bento-body">
          <p class="label">Lien OBS</p>
          <p class="mono">{{ previewUrl }}</p>
        </div>
      </article>
    </div>
  </DashboardLayout>
</template>
