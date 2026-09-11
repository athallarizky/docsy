import { ref } from 'vue'

const THEME_KEY = 'docsy.theme'
const isDark = ref(false)

/** Class-strategy dark mode persisted in localStorage (ux-flow §4). */
export function useDarkMode() {
  const apply = () => document.documentElement.classList.toggle('dark', isDark.value)

  const init = () => {
    isDark.value =
      localStorage.getItem(THEME_KEY) === 'dark' ||
      (!localStorage.getItem(THEME_KEY) &&
        window.matchMedia('(prefers-color-scheme: dark)').matches)
    apply()
  }

  const toggle = () => {
    isDark.value = !isDark.value
    localStorage.setItem(THEME_KEY, isDark.value ? 'dark' : 'light')
    apply()
  }

  return { isDark, init, toggle }
}
