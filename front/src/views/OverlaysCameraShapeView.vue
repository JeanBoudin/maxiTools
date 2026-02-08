<script setup>
import { computed, ref } from 'vue'
import DashboardLayout from '../layouts/DashboardLayout.vue'

const copied = ref(false)
const circleUrl = computed(() => `${window.location.origin}/overlay/camera/circle`)

async function copyUrl() {
  await navigator.clipboard.writeText(circleUrl.value)
  copied.value = true
  setTimeout(() => (copied.value = false), 2000)
}
</script>

<template>
  <DashboardLayout>
    <template #header>
      <p class="eyebrow">Overlays</p>
      <h1>Cadres caméra · Formes</h1>
      <p class="subtitle">Choisis une forme simple pour ton overlay caméra.</p>
    </template>

    <div class="bento">
      <article class="bento-card bento-hero">
        <div>
          <p class="eyebrow">Forme ronde</p>
          <h2>Overlay circulaire</h2>
          <p class="subtitle">Un cercle net pour cadrer ta caméra.</p>
        </div>
        <div class="bento-actions">
          <button class="secondary" type="button" @click="copyUrl">
            {{ copied ? 'Copié' : 'Copier le lien' }}
          </button>
          <a class="ghost" :href="circleUrl" target="_blank" rel="noreferrer">Ouvrir l’overlay</a>
        </div>
      </article>

      <article class="bento-card">
        <div class="bento-head">
          <h3>Lien OBS</h3>
          <span class="tag">Actif</span>
        </div>
        <p class="subtitle">Utilise ce lien pour le cadre circulaire.</p>
        <div class="bento-body">
          <p class="label">Lien OBS</p>
          <p class="mono">{{ circleUrl }}</p>
        </div>
      </article>
    </div>
  </DashboardLayout>
</template>
