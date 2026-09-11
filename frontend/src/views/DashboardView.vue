<script setup>
import { ref, onMounted } from 'vue'
import { dashboardApi } from '../api/dashboard'
import { useRouter } from 'vue-router'

const router = useRouter()
const stats = ref(null)
const isLoading = ref(true)

onMounted(async () => {
  try {
    stats.value = await dashboardApi.stats()
  } finally {
    isLoading.value = false
  }
})

const cards = [
  { key: 'total_folders', label: 'Folders', icon: '📁' },
  { key: 'total_files', label: 'Files', icon: '📄' },
  { key: 'total_departments', label: 'Departments', icon: '🏢' },
]
</script>

<template>
  <div class="space-y-6">
    <header>
      <h1 class="text-xl font-semibold">Dashboard</h1>
      <p class="text-sm text-slate-500 dark:text-slate-400">System overview — served from cache (TTL 10m)</p>
    </header>

    <!-- stat cards -->
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
      <template v-if="isLoading">
        <div v-for="i in 4" :key="i" class="h-24 animate-pulse rounded-2xl bg-slate-100 dark:bg-slate-800" />
      </template>
      <template v-else>
        <div
          v-for="card in cards"
          :key="card.key"
          class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900"
        >
          <div class="flex items-center justify-between">
            <p class="text-sm text-slate-500 dark:text-slate-400">{{ card.label }}</p>
            <span class="text-xl">{{ card.icon }}</span>
          </div>
          <p class="mt-2 text-3xl font-bold">{{ stats?.[card.key] ?? 0 }}</p>
        </div>
        <div class="rounded-2xl border border-brand-200 bg-brand-50 p-5 dark:border-brand-700/50 dark:bg-brand-700/20">
          <div class="flex items-center justify-between">
            <p class="text-sm text-brand-700 dark:text-brand-100">Storage</p>
            <span class="text-xl">💾</span>
          </div>
          <p class="mt-2 text-3xl font-bold text-brand-700 dark:text-brand-100">
            {{ stats?.total_files ?? 0 }}<span class="text-base font-medium"> files stored</span>
          </p>
        </div>
      </template>
    </div>

    <!-- recent files -->
    <section>
      <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-400">10 most recent files</h2>
      <div v-if="isLoading" class="space-y-2">
        <div v-for="i in 5" :key="i" class="h-12 animate-pulse rounded-xl bg-slate-100 dark:bg-slate-800" />
      </div>

      <div v-else-if="!stats?.recent_files?.length" class="rounded-xl border border-dashed border-slate-300 p-8 text-center text-sm text-slate-400 dark:border-slate-700">
        No files uploaded yet
      </div>

      <div v-else class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-800">
        <table class="w-full min-w-[640px] text-left text-sm">
          <thead class="border-b border-slate-200 bg-slate-50 text-xs uppercase tracking-wide text-slate-500 dark:border-slate-800 dark:bg-slate-900">
            <tr>
              <th class="px-4 py-2.5">Title</th>
              <th class="px-4 py-2.5">File name</th>
              <th class="px-4 py-2.5">Department</th>
              <th class="px-4 py-2.5">Uploaded by</th>
              <th class="px-4 py-2.5">Date</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
            <tr
              v-for="file in stats.recent_files"
              :key="file.id"
              class="cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800/50"
              @click="router.push({ name: 'explorer' })"
            >
              <td class="px-4 py-2.5 font-medium">{{ file.title }}</td>
              <td class="px-4 py-2.5 text-slate-500">{{ file.original_name }}</td>
              <td class="px-4 py-2.5">
                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs dark:bg-slate-800">{{ file.department_name ?? '—' }}</span>
              </td>
              <td class="px-4 py-2.5 text-slate-500">{{ file.uploaded_by_name ?? '—' }}</td>
              <td class="px-4 py-2.5 text-slate-500">{{ new Date(file.created_at).toLocaleString() }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>
  </div>
</template>
