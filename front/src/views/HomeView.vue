<script setup>
import { computed, ref } from 'vue'
import DashboardLayout from '../layouts/DashboardLayout.vue'
import { useAccount } from '../state/account'

const account = useAccount()
const copied = ref(false)

const overlayUrl = computed(() => {
  if (!account.overlayKey) return ''
  return `${window.location.origin}/overlay?key=${encodeURIComponent(account.overlayKey)}`
})

const isConnected = computed(() => Boolean(account.overlayKey))

async function copyOverlayUrl() {
  if (!overlayUrl.value) return
  await navigator.clipboard.writeText(overlayUrl.value)
  copied.value = true
  setTimeout(() => (copied.value = false), 2000)
}
</script>

<template>
  <DashboardLayout>
    <template #header>
      <p class="eyebrow">Tableau de bord</p>
      <h1>Organise ton stream comme un cockpit.</h1>
      <p class="subtitle">
        Active tes outils visuels, copie les liens OBS et prépare les prochains modules.
      </p>
    </template>

    <template #actions>
      <button class="ghost" type="button">Ajouter un outil</button>
    </template>

    <div class="bento">
      <article class="bento-card bento-hero">
        <div>
          <p class="eyebrow">Connexion</p>
          <h2>Ton hub Twitch</h2>
          <p class="subtitle">
            La connexion sécurise tes tokens et génère une clé unique pour les overlays OBS.
          </p>
        </div>
        <div class="bento-actions">
          <p class="micro">Aucun token n’est exposé côté OBS.</p>
        </div>
      </article>

      <article class="bento-card">
        <div class="bento-head">
          <h3>Stats circulaires</h3>
          <span class="tag tag-live">Disponible</span>
        </div>
        <p class="subtitle">
          Vue stylée pour viewers, followers, subs et heure. Parfait pour un overlay discret.
        </p>
        <div class="bento-body">
          <p class="label">Lien OBS</p>
          <p class="mono">{{ overlayUrl || 'Connecte Twitch pour générer le lien.' }}</p>
        </div>
        <div class="bento-actions">
          <button class="secondary" type="button" :disabled="!isConnected" @click="copyOverlayUrl">
            {{ copied ? 'Copié' : 'Copier le lien' }}
          </button>
          <a v-if="isConnected" class="ghost" :href="overlayUrl" target="_blank" rel="noreferrer">
            Ouvrir l’overlay
          </a>
        </div>
      </article>

      <article class="bento-card">
        <div class="bento-head">
          <h3>Promo réseaux sociaux</h3>
          <span class="tag">Configurable</span>
        </div>
        <p class="subtitle">Rotation automatique toutes les 10 minutes avec tes réseaux.</p>
        <div class="bento-body">
          <p class="label">Statut</p>
          <p class="mono">Prêt à configurer</p>
        </div>
        <div class="bento-actions">
          <router-link class="ghost" to="/promos">Configurer l’outil</router-link>
        </div>
      </article>

      <article class="bento-card muted">
        <div class="bento-head">
          <h3>Screen break</h3>
          <span class="tag">Bientôt</span>
        </div>
        <p class="subtitle">Écran de pause animé avec musique et timer.</p>
        <div class="bento-body">
          <p class="label">Statut</p>
          <p class="mono">À venir</p>
        </div>
      </article>
    </div>
  </DashboardLayout>
</template>
