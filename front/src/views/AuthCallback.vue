<script setup>
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { setAccount } from '../state/account'

const backendUrl = import.meta.env.VITE_BACKEND_URL || 'http://localhost'
const router = useRouter()
const error = ref('')

onMounted(async () => {
  try {
    const response = await fetch(`${backendUrl}/api/me`, {
      credentials: 'include',
    })

    if (!response.ok) {
      throw new Error('Auth failed')
    }

    const data = await response.json()
    setAccount(data)
    router.replace({ path: '/' })
  } catch (err) {
    error.value = 'Connexion échouée. Réessaye.'
  }
})
</script>

<template>
  <main class="page">
    <section class="card">
      <h1>Connexion en cours...</h1>
      <p class="subtitle" v-if="!error">On termine la configuration, tu vas être redirigé.</p>
      <p class="subtitle" v-else>{{ error }}</p>
    </section>
  </main>
</template>
