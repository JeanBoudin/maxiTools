<script setup>
import { computed, ref } from 'vue'
import DashboardLayout from '../layouts/DashboardLayout.vue'

const copied = ref(false)
const waitingUrl = computed(() => `${window.location.origin}/overlay/waiting`)

async function copyUrl() {
  await navigator.clipboard.writeText(waitingUrl.value)
  copied.value = true
  setTimeout(() => (copied.value = false), 2000)
}
</script>

<template>
  <DashboardLayout>
    <template #header>
      <p class="eyebrow">Overlays</p>
      <h1>Écran d’attente</h1>
      <p class="subtitle">Un écran de transition sombre et élégant avant le live.</p>
    </template>

    <div class="bento">
      <article class="bento-card bento-hero">
        <div>
          <p class="eyebrow">Waiting Screen</p>
          <h2>Ambiance gothique & minimaliste</h2>
          <p class="subtitle">À afficher quand tu démarres ton stream.</p>
        </div>
        <div class="bento-actions">
          <button class="secondary" type="button" @click="copyUrl">
            {{ copied ? 'Copié' : 'Copier le lien' }}
          </button>
          <a class="ghost" :href="waitingUrl" target="_blank" rel="noreferrer">Ouvrir l’overlay</a>
        </div>
      </article>

      <article class="bento-card">
        <div class="bento-head">
          <h3>Prévisualisation</h3>
          <span class="tag">Live</span>
        </div>
        <div class="preview-frame">
          <iframe class="preview-iframe" :src="waitingUrl" title="Preview Waiting"></iframe>
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
          <p class="mono">{{ waitingUrl }}</p>
        </div>
      </article>
    </div>
  </DashboardLayout>
</template>
