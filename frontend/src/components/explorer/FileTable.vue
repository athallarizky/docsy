<script setup>
defineProps({
  files: { type: Array, required: true },
  isAdmin: { type: Boolean, default: false },
})
const emit = defineEmits(['open', 'delete'])
</script>

<template>
  <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-800">
    <table class="w-full min-w-[640px] text-left text-sm">
      <thead class="border-b border-slate-200 bg-slate-50 text-xs uppercase tracking-wide text-slate-500 dark:border-slate-800 dark:bg-slate-900">
        <tr>
          <th class="px-4 py-2.5">Title</th>
          <th class="px-4 py-2.5">File name</th>
          <th class="px-4 py-2.5">Department</th>
          <th class="px-4 py-2.5">Size</th>
          <th class="px-4 py-2.5">Date</th>
          <th class="px-4 py-2.5 text-right">Actions</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
        <tr
          v-for="file in files"
          :key="file.id"
          class="cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800/50"
          @click="emit('open', file)"
        >
          <td class="px-4 py-2.5 font-medium">{{ file.title }}</td>
          <td class="px-4 py-2.5 text-slate-500">{{ file.original_name }}</td>
          <td class="px-4 py-2.5">
            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs dark:bg-slate-800">{{ file.department?.name ?? '—' }}</span>
          </td>
          <td class="px-4 py-2.5 text-slate-500">{{ file.file_size_formatted }}</td>
          <td class="px-4 py-2.5 text-slate-500">{{ new Date(file.created_at).toLocaleDateString() }}</td>
          <td class="px-4 py-2.5 text-right">
            <button
              v-if="isAdmin"
              type="button"
              class="rounded-lg p-1.5 text-red-500 hover:bg-red-50 dark:hover:bg-red-950/40"
              title="Delete file"
              @click.stop="emit('delete', file)"
            >
              🗑
            </button>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
