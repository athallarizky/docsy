import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import { useDarkMode } from './composables/useDarkMode'
import './assets/main.css'

// apply the persisted theme before mount (no flash of wrong theme)
const { init } = useDarkMode()
init()

const app = createApp(App)
app.use(createPinia())
app.use(router)
app.mount('#app')
