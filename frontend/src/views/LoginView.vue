<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const router = useRouter()
const auth = useAuthStore()

const email = ref('')
const password = ref('')
const loading = ref(false)
const error = ref(null)

async function submit() {
  loading.value = true
  error.value = null
  try {
    const user = await auth.login(email.value, password.value)
    // role-aware landing (ux-flow §2.1)
    router.push(user.role === 'administrator' ? { name: 'dashboard' } : { name: 'explorer' })
  } catch (e) {
    error.value = e.message || 'Login failed'
  } finally {
    loading.value = false
  }
}

/** demo quick-fill helpers (ux-flow §2.1) */
function fillAs(role) {
  email.value = role === 'admin' ? 'admin@example.com' : 'viewer@example.com'
  password.value = 'password'
}
</script>

<template>
  <div class="grid min-h-screen place-items-center bg-slate-50 dark:bg-slate-950">
    <div class="w-full max-w-sm rounded-2xl border border-slate-200 bg-white p-8 shadow-sm dark:border-slate-800 dark:bg-slate-900">
      <div class="mb-6 flex items-center gap-3">
        <span class="grid h-10 w-10 place-items-center rounded-xl bg-brand-600 font-bold text-white">D</span>
        <div>
          <h1 class="text-lg font-semibold text-slate-900 dark:text-slate-50">Docsy</h1>
          <p class="text-sm text-slate-500 dark:text-slate-400">File Management System</p>
        </div>
      </div>

      <form class="space-y-4" @submit.prevent="submit">
        <div>
          <label class="mb-1 block text-sm font-medium" for="email">Email</label>
          <input
            id="email"
            v-model="email"
            type="email"
            required
            autocomplete="email"
            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100 dark:border-slate-700 dark:bg-slate-800"
            placeholder="you@company.com"
          />
        </div>

        <div>
          <label class="mb-1 block text-sm font-medium" for="password">Password</label>
          <input
            id="password"
            v-model="password"
            type="password"
            required
            autocomplete="current-password"
            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100 dark:border-slate-700 dark:bg-slate-800"
            placeholder="••••••••"
          />
        </div>

        <p v-if="error" class="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700 dark:bg-red-950/50 dark:text-red-300">
          {{ error }}
        </p>

        <button
          type="submit"
          :disabled="loading"
          class="w-full rounded-lg bg-brand-600 px-3 py-2 text-sm font-semibold text-white transition hover:bg-brand-700 disabled:opacity-60"
        >
          {{ loading ? 'Signing in…' : 'Sign in' }}
        </button>
      </form>

      <div class="mt-6 border-t border-slate-200 pt-4 dark:border-slate-800">
        <p class="mb-2 text-xs uppercase tracking-wide text-slate-400">Demo accounts</p>
        <div class="flex gap-2">
          <button
            type="button"
            class="flex-1 rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-medium hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800"
            @click="fillAs('admin')"
          >
            Fill as Admin
          </button>
          <button
            type="button"
            class="flex-1 rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-medium hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800"
            @click="fillAs('viewer')"
          >
            Fill as Viewer
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
