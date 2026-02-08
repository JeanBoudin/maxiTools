<script setup>
import { computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { clearAccount, setAccount, useAccount } from '../state/account'

const route = useRoute()
const router = useRouter()
const backendUrl = import.meta.env.VITE_BACKEND_URL || 'http://localhost'

const account = useAccount()
const isConnected = computed(() => Boolean(account.overlayKey))

async function fetchAccount() {
  try {
    const response = await fetch(`${backendUrl}/api/me`, {
      credentials: 'include',
    })

    if (!response.ok) {
      return
    }

    const data = await response.json()
    setAccount(data)
  } catch (err) {
    // no-op
  }
}

function connectTwitch() {
  const returnTo = encodeURIComponent(window.location.origin + route.fullPath)
  window.location.href = `${backendUrl}/auth/twitch/redirect?return_to=${returnTo}`
}

async function logout() {
  try {
    await fetch(`${backendUrl}/api/logout`, {
      method: 'POST',
      credentials: 'include',
    })
  } catch (err) {
    // no-op
  } finally {
    clearAccount()
    router.push('/login')
  }
}

onMounted(() => {
  fetchAccount()
})
</script>

<template>
  <main class="dashboard">
    <aside class="sidebar">
      <div class="brand">
        <span class="brand-mark">RS</span>
        <div>
          <p class="brand-title">RoueStream</p>
          <p class="brand-subtitle">Studio overlays</p>
        </div>
      </div>

      <nav class="nav">
        <router-link class="nav-item" to="/" exact-active-class="is-active">Tableau de bord</router-link>
        <div class="nav-group">
          <router-link class="nav-item" to="/overlays" active-class="is-active">Overlays</router-link>
          <div class="nav-sub">
            <router-link class="nav-item nav-sub-item" to="/overlays/camera" active-class="is-active">
              Cadre caméra
            </router-link>
            <router-link
              class="nav-item nav-sub-item"
              to="/overlays/camera/shapes"
              active-class="is-active"
            >
              Formes caméra
            </router-link>
          </div>
        </div>
        <router-link class="nav-item" to="/promos" active-class="is-active">Promos</router-link>
        <router-link class="nav-item" to="/settings" active-class="is-active">Réglages</router-link>
      </nav>

      <div class="sidebar-footer">
        <p class="status">
          <span class="status-dot" :class="{ online: isConnected }"></span>
          {{ isConnected ? `Connecté · ${account.displayName || 'Twitch'}` : 'Non connecté' }}
        </p>
        <div class="sidebar-actions">
          <button class="primary" type="button" @click="connectTwitch">
            {{ isConnected ? 'Reconnecter Twitch' : 'Se connecter avec Twitch' }}
          </button>
          <button v-if="isConnected" class="text-button" type="button" @click="logout">
            Se déconnecter
          </button>
        </div>
      </div>
    </aside>

    <section class="content">
      <header class="content-header">
        <div>
          <slot name="header" />
        </div>
        <div class="header-actions">
          <slot name="actions" />
        </div>
      </header>

      <slot />
    </section>
  </main>
</template>
