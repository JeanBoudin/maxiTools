<script setup>
import DashboardLayout from '../layouts/DashboardLayout.vue'
</script>

<template>
  <DashboardLayout>
    <template #header>
      <p class="eyebrow">Réglages</p>
      <h1>Paramètres généraux</h1>
      <p class="subtitle">Centralise les options de ton studio.</p>
    </template>

    <div class="bento">
      <article class="bento-card muted">
        <div class="bento-head">
          <h3>À venir</h3>
          <span class="tag">Bientôt</span>
        </div>
        <p class="subtitle">Ici tu pourras gérer les thèmes, scènes et accès.</p>
      </article>
    </div>
  </DashboardLayout>
</template>
