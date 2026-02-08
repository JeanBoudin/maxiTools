<script setup>
import { onMounted, onUnmounted } from 'vue'


onMounted(() => {
  document.documentElement.classList.add('overlay-transparent')
  document.body.classList.add('overlay-transparent')
  document.getElementById('app')?.classList.add('overlay-transparent')
})

onUnmounted(() => {
  document.documentElement.classList.remove('overlay-transparent')
  document.body.classList.remove('overlay-transparent')
  document.getElementById('app')?.classList.remove('overlay-transparent')
})
</script>

<template>
  <main class="camera-circle-overlay">
    <div class="camera-hole"></div>
  </main>
</template>
