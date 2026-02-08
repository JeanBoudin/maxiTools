<script setup>
const backendUrl = import.meta.env.VITE_BACKEND_URL || 'http://localhost'

function connectTwitch() {
  const returnTo = encodeURIComponent(window.location.origin + '/')
  window.location.href = `${backendUrl}/auth/twitch/redirect?return_to=${returnTo}`
}
</script>

<template>
  <main class="login">
    <section class="login-card">
      <div class="login-brand">
        <span class="brand-mark">RS</span>
        <div>
          <p class="brand-title">RoueStream</p>
          <p class="brand-subtitle">Studio overlays</p>
        </div>
      </div>

      <div class="login-body">
        <p class="eyebrow">Connexion</p>
        <h1>Connecte ton Twitch pour accéder au dashboard.</h1>
        <p class="subtitle">
          Tu contrôles les overlays et les promos depuis un espace privé. Les liens OBS restent publics.
        </p>
      </div>

      <div class="login-actions">
        <button class="primary" type="button" @click="connectTwitch">Se connecter avec Twitch</button>
      </div>
    </section>
  </main>
</template>
