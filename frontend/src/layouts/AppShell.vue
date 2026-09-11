<script setup>
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { useDarkMode } from '../composables/useDarkMode'

const router = useRouter()
const auth = useAuthStore()
const { isDark, toggle } = useDarkMode()

async function handleLogout() {
  await auth.logout()
  router.push({ name: 'login' })
}
</script>

<template>
  <div class="min-h-screen bg-slate-50 text-slate-800 dark:bg-slate-950 dark:text-slate-100">
    <!-- Top bar -->
    <header
      class="sticky top-0 z-20 flex h-14 items-center gap-4 border-b border-slate-200 bg-white px-4 dark:border-slate-800 dark:bg-slate-900"
    >
      <RouterLink :to="{ name: 'explorer' }" class="flex items-center gap-2 font-semibold">
        <span class="grid h-7 w-7 place-items-center rounded-lg bg-brand-600 text-sm text-white">D</span>
        <span class="hidden sm:inline">Docsy</span>
      </RouterLink>

      <div class="ml-auto flex items-center gap-3">
        <!-- role badge -->
        <span
          class="rounded-full px-2 py-0.5 text-xs font-medium"
          :class="
            auth.isAdmin
              ? 'bg-brand-50 text-brand-700 dark:bg-brand-700/30 dark:text-brand-100'
              : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300'
          "
        >
          {{ auth.isAdmin ? 'Administrator' : 'Viewer' }}
        </span>

        <button
          type="button"
          class="rounded-lg p-2 hover:bg-slate-100 dark:hover:bg-slate-800"
          :title="isDark ? 'Switch to light' : 'Switch to dark'"
          @click="toggle"
        >
          {{ isDark ? '☀️' : '🌙' }}
        </button>

        <div class="hidden text-sm sm:block">{{ auth.user?.name }}</div>

        <button
          type="button"
          class="rounded-lg px-3 py-1.5 text-sm font-medium text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800"
          @click="handleLogout"
        >
          Logout
        </button>
      </div>
    </header>

    <div class="flex">
      <!-- Sidebar -->
      <aside
        class="hidden w-56 shrink-0 border-r border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900 md:block"
      >
        <nav class="space-y-1">
          <RouterLink
            :to="{ name: 'explorer' }"
            class="block rounded-lg px-3 py-2 text-sm font-medium hover:bg-slate-100 dark:hover:bg-slate-800"
            active-class="bg-brand-50 text-brand-700 dark:bg-brand-700/30 dark:text-brand-100"
          >
            📁 Files
          </RouterLink>
          <RouterLink
            v-if="auth.isAdmin"
            :to="{ name: 'dashboard' }"
            class="block rounded-lg px-3 py-2 text-sm font-medium hover:bg-slate-100 dark:hover:bg-slate-800"
            active-class="bg-brand-50 text-brand-700 dark:bg-brand-700/30 dark:text-brand-100"
          >
            📊 Dashboard
          </RouterLink>
        </nav>
      </aside>

      <!-- Page content -->
      <main class="min-w-0 flex-1 p-4 md:p-6">
        <RouterView />
      </main>
    </div>
  </div>
</template>
