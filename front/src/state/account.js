import { reactive } from 'vue'

const state = reactive({
  overlayKey: localStorage.getItem('overlay_key') || '',
  displayName: localStorage.getItem('display_name') || '',
  profileImageUrl: localStorage.getItem('profile_image_url') || '',
})

export function useAccount() {
  return state
}

export function setAccount(data) {
  state.overlayKey = data.overlay_key || ''
  state.displayName = data.display_name || ''
  state.profileImageUrl = data.profile_image_url || ''

  localStorage.setItem('overlay_key', state.overlayKey)
  localStorage.setItem('display_name', state.displayName)
  localStorage.setItem('profile_image_url', state.profileImageUrl)
}

export function clearAccount() {
  state.overlayKey = ''
  state.displayName = ''
  state.profileImageUrl = ''

  localStorage.removeItem('overlay_key')
  localStorage.removeItem('display_name')
  localStorage.removeItem('profile_image_url')
}
