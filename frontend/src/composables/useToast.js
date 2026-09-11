import { ref } from 'vue'

const toasts = ref([])
let uid = 0

/** Minimal toast queue — success/error/neutral, auto-dismiss. */
export function useToast() {
  function push(message, type = 'success') {
    const id = ++uid
    toasts.value.push({ id, message, type })
    setTimeout(() => {
      toasts.value = toasts.value.filter((t) => t.id !== id)
    }, 3500)
  }

  return {
    toasts,
    success: (m) => push(m, 'success'),
    error: (m) => push(m, 'error'),
  }
}
