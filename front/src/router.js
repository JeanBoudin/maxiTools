import { createRouter, createWebHistory } from 'vue-router'
import HomeView from './views/HomeView.vue'
import AuthCallback from './views/AuthCallback.vue'
import OverlayView from './views/OverlayView.vue'
import OverlaysView from './views/OverlaysView.vue'
import PromosView from './views/PromosView.vue'
import SettingsView from './views/SettingsView.vue'
import PromoOverlayView from './views/PromoOverlayView.vue'
import LoginView from './views/LoginView.vue'
import CameraFrameOverlayView from './views/CameraFrameOverlayView.vue'
import OverlaysCameraView from './views/OverlaysCameraView.vue'
import CameraCircleOverlayView from './views/CameraCircleOverlayView.vue'
import OverlaysCameraShapeView from './views/OverlaysCameraShapeView.vue'
import { clearAccount, setAccount, useAccount } from './state/account'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/login', name: 'login', component: LoginView },
    { path: '/', name: 'home', component: HomeView, meta: { requiresAuth: true } },
    { path: '/overlays', name: 'overlays', component: OverlaysView, meta: { requiresAuth: true } },
    { path: '/overlays/camera', name: 'overlays-camera', component: OverlaysCameraView, meta: { requiresAuth: true } },
    {
      path: '/overlays/camera/shapes',
      name: 'overlays-camera-shapes',
      component: OverlaysCameraShapeView,
      meta: { requiresAuth: true },
    },
    { path: '/promos', name: 'promos', component: PromosView, meta: { requiresAuth: true } },
    { path: '/settings', name: 'settings', component: SettingsView, meta: { requiresAuth: true } },
    { path: '/auth/callback', name: 'auth-callback', component: AuthCallback },
    { path: '/overlay', name: 'overlay', component: OverlayView },
    { path: '/overlay/promo', name: 'overlay-promo', component: PromoOverlayView },
    { path: '/overlay/camera', name: 'overlay-camera', component: CameraFrameOverlayView },
    { path: '/overlay/camera/circle', name: 'overlay-camera-circle', component: CameraCircleOverlayView },
  ],
})

const backendUrl = import.meta.env.VITE_BACKEND_URL || 'http://localhost'

router.beforeEach(async (to) => {
  if (to.path === '/login') {
    const account = useAccount()

    if (account.overlayKey) {
      return { path: '/' }
    }

    try {
      const response = await fetch(`${backendUrl}/api/me`, {
        credentials: 'include',
      })

      if (response.ok) {
        const data = await response.json()
        setAccount(data)
        return { path: '/' }
      }
    } catch (err) {
      // ignore
    }
  }

  if (!to.meta.requiresAuth) {
    return true
  }

  const account = useAccount()

  if (account.overlayKey) {
    return true
  }

  try {
    const response = await fetch(`${backendUrl}/api/me`, {
      credentials: 'include',
    })

    if (!response.ok) {
      clearAccount()
      return { path: '/login' }
    }

    const data = await response.json()
    setAccount(data)
    return true
  } catch (err) {
    clearAccount()
    return { path: '/login' }
  }
})

export default router
