<script setup>
import { computed, ref } from 'vue'
import DashboardLayout from '../layouts/DashboardLayout.vue'
import { useAccount } from '../state/account'

const account = useAccount()
const copied = ref(false)
const copiedPromo = ref(false)
const copiedCamera = ref(false)

const statsUrl = computed(() => {
  if (!account.overlayKey) return ''
  return `${window.location.origin}/overlay?key=${encodeURIComponent(account.overlayKey)}`
})

const promoUrl = computed(() => {
  if (!account.overlayKey) return ''
  return `${window.location.origin}/overlay/promo?key=${encodeURIComponent(account.overlayKey)}`
})

const cameraUrl = computed(() => {
  if (!account.overlayKey) return `${window.location.origin}/overlay/camera`
  return `${window.location.origin}/overlay/camera?key=${encodeURIComponent(account.overlayKey)}`
})

const isConnected = computed(() => Boolean(account.overlayKey))

async function copyUrl(value, flag) {
  if (!value) return
  await navigator.clipboard.writeText(value)
  flag.value = true
  setTimeout(() => (flag.value = false), 2000)
}
</script>

<template>
  <DashboardLayout>
    <template #header>
      <p class="eyebrow">Overlays</p>
      <h1>Prépare tes scènes OBS.</h1>
      <p class="subtitle">Chaque overlay a un lien unique. Copie-le dans OBS.</p>
    </template>

    <div class="bento">
      <article class="bento-card">
        <div class="bento-head">
          <h3>Stats circulaires</h3>
          <span class="tag tag-live">Actif</span>
        </div>
        <p class="subtitle">Viewer count, followers, subs et heure en rotation.</p>
        <div class="bento-body">
          <p class="label">Lien OBS</p>
          <p class="mono">{{ statsUrl || 'Connecte Twitch pour générer le lien.' }}</p>
        </div>
        <div class="bento-actions">
          <button class="secondary" type="button" :disabled="!isConnected" @click="copyUrl(statsUrl, copied)">
            {{ copied ? 'Copié' : 'Copier le lien' }}
          </button>
          <a
            v-if="isConnected"
            class="ghost"
            :href="statsUrl"
            target="_blank"
            rel="noreferrer"
          >
            Ouvrir l’overlay
          </a>
        </div>
      </article>

      <article class="bento-card">
        <div class="bento-head">
          <h3>Cadre caméra</h3>
          <span class="tag">Actif</span>
        </div>
        <p class="subtitle">Overlay transparent avec un cadre centré pour la caméra.</p>
        <div class="bento-body">
          <p class="label">Lien OBS</p>
          <p class="mono">{{ cameraUrl }}</p>
        </div>
        <div class="bento-actions">
          <button class="secondary" type="button" @click="copyUrl(cameraUrl, copiedCamera)">
            {{ copiedCamera ? 'Copié' : 'Copier le lien' }}
          </button>
          <a class="ghost" :href="cameraUrl" target="_blank" rel="noreferrer">Ouvrir l’overlay</a>
          <router-link class="ghost" to="/overlays/camera">Configurer</router-link>
          <router-link class="ghost" to="/overlays/camera/shapes">Formes</router-link>
        </div>
      </article>

      <article class="bento-card">
        <div class="bento-head">
          <h3>Promo réseaux sociaux</h3>
          <span class="tag">Actif</span>
        </div>
        <p class="subtitle">Rotation automatique des messages promo.</p>
        <div class="bento-body">
          <p class="label">Lien OBS</p>
          <p class="mono">{{ promoUrl || 'Connecte Twitch pour générer le lien.' }}</p>
        </div>
        <div class="bento-actions">
          <button
            class="secondary"
            type="button"
            :disabled="!isConnected"
            @click="copyUrl(promoUrl, copiedPromo)"
          >
            {{ copiedPromo ? 'Copié' : 'Copier le lien' }}
          </button>
          <a v-if="isConnected" class="ghost" :href="promoUrl" target="_blank" rel="noreferrer">
            Ouvrir l’overlay
          </a>
        </div>
      </article>

      <article class="bento-card muted">
        <div class="bento-head">
          <h3>Screen break</h3>
          <span class="tag">Bientôt</span>
        </div>
        <p class="subtitle">Pause animée avec timer, musique et message.</p>
      </article>
    </div>
  </DashboardLayout>
</template>
