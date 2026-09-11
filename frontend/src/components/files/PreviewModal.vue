<script setup>
import { ref, watch, onUnmounted } from 'vue'
import AppModal from '../common/AppModal.vue'
import { filesApi } from '../../api/files'
import { useAuthStore } from '../../stores/auth'

const props = defineProps({
  open: Boolean,
  file: { type: Object, default: null },
})
const emit = defineEmits(['close', 'edit'])

const auth = useAuthStore()
const objectUrl = ref(null)
const loading = ref(true)
const failed = ref(false)

watch(
  () => [props.open, props.file?.id],
  async ([open]) => {
    if (objectUrl.value) URL.revokeObjectURL(objectUrl.value)
    objectUrl.value = null
    if (!open || !props.file) return

    loading.value = true
    failed.value = false
    try {
      // private files need the Authorization header -> fetch as blob
      objectUrl.value = await filesApi.objectUrl(props.file.id, 'preview')
    } catch {
      failed.value = true
    } finally {
      loading.value = false
    }
  },
  { immediate: true },
)

onUnmounted(() => objectUrl.value && URL.revokeObjectURL(objectUrl.value))

async function download() {
  const blob = await filesApi.download(props.file.id)
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = props.file.original_name
  a.click()
  URL.revokeObjectURL(url)
}

function fileIcon(mime) {
  if (mime?.startsWith('image/')) return '🖼️'
  if (mime === 'application/pdf') return '📕'
  return '📄'
}
</script>

<template>
  <AppModal :open="open" :title="file?.title ?? 'Preview'" wide @close="emit('close')">
    <div class="grid gap-5 md:grid-cols-[1fr_240px]">
      <!-- viewer pane -->
      <div class="grid min-h-[320px] place-items-center overflow-hidden rounded-xl bg-slate-100 dark:bg-slate-800">
        <span v-if="loading" class="animate-pulse text-sm text-slate-400">Loading preview…</span>
        <span v-else-if="failed" class="text-sm text-red-500">Preview failed to load</span>
        <img v-else-if="file.mime_type?.startsWith('image/')" :src="objectUrl" :alt="file.title" class="max-h-[60vh] object-contain" />
        <iframe v-else-if="file.mime_type === 'application/pdf'" :src="objectUrl" class="h-[60vh] w-full" />
        <div v-else class="p-8 text-center">
          <span class="text-5xl">{{ fileIcon(file.mime_type) }}</span>
          <p class="mt-2 text-sm text-slate-500">No inline preview — download to open</p>
        </div>
      </div>

      <!-- metadata pane -->
      <aside v-if="file" class="space-y-3 text-sm">
        <div>
          <p class="text-xs uppercase tracking-wide text-slate-400">Title</p>
          <p class="font-medium">{{ file.title }}</p>
        </div>
        <div>
          <p class="text-xs uppercase tracking-wide text-slate-400">File name</p>
          <p>{{ file.original_name }}</p>
        </div>
        <div>
          <p class="text-xs uppercase tracking-wide text-slate-400">Folder</p>
          <p>{{ file.folder?.name ?? '—' }}</p>
        </div>
        <div>
          <p class="text-xs uppercase tracking-wide text-slate-400">Department</p>
          <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs dark:bg-slate-800">{{ file.department?.name ?? '—' }}</span>
        </div>
        <div>
          <p class="text-xs uppercase tracking-wide text-slate-400">Uploaded by</p>
          <p>{{ file.uploaded_by?.name ?? '—' }}</p>
        </div>
        <div>
          <p class="text-xs uppercase tracking-wide text-slate-400">Size</p>
          <p>{{ file.file_size_formatted }}</p>
        </div>
        <div>
          <p class="text-xs uppercase tracking-wide text-slate-400">Date</p>
          <p>{{ new Date(file.created_at).toLocaleString() }}</p>
        </div>

        <div class="space-y-2 pt-2">
          <button
            type="button"
            class="w-full rounded-lg bg-brand-600 px-3 py-2 text-sm font-semibold text-white hover:bg-brand-700"
            @click="download"
          >
            ⬇ Download
          </button>
          <button
            v-if="auth.isAdmin"
            type="button"
            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm hover:bg-slate-50 dark:border-slate-600 dark:hover:bg-slate-800"
            @click="emit('edit', file)"
          >
            ✏️ Edit metadata
          </button>
        </div>
      </aside>
    </div>
  </AppModal>
</template>
