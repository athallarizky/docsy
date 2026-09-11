<script setup>
import { ref, computed, watch } from 'vue'
import AppModal from '../common/AppModal.vue'
import { filesApi } from '../../api/files'
import { departmentsApi } from '../../api/departments'
import { useToast } from '../../composables/useToast'

const props = defineProps({
  open: Boolean,
  folderId: { type: Number, default: null },
  folderName: { type: String, default: '' },
})
const emit = defineEmits(['close', 'uploaded'])

const { success, error: toastError } = useToast()

const departments = ref([])
const title = ref('')
const departmentId = ref(null)
const file = ref(null)
const isDragging = ref(false)
const progress = ref(0)
const uploading = ref(false)
const error = ref(null)

const canSubmit = computed(() => title.value.trim() && departmentId.value && file.value && props.folderId)

async function loadDepartments() {
  departments.value = await departmentsApi.list()
}

// refresh on each open — a department may have just been created elsewhere
watch(
  () => props.open,
  (open) => open && loadDepartments(),
)

function onFileChosen(f) {
  file.value = f
  if (!title.value) title.value = f.name.replace(/\.[^.]+$/, '')
}

function onDrop(e) {
  isDragging.value = false
  const dropped = e.dataTransfer?.files?.[0]
  if (dropped) onFileChosen(dropped)
}

async function submit() {
  uploading.value = true
  error.value = null
  progress.value = 0
  try {
    await filesApi.upload({
      title: title.value.trim(),
      folderId: props.folderId,
      departmentId: departmentId.value,
      file: file.value,
      onProgress: (p) => (progress.value = p),
    })
    success(`Uploaded "${file.value.name}"`)
    emit('uploaded')
    emit('close')
  } catch (e) {
    error.value = e.errors ? Object.values(e.errors).flat().join(' ') : e.message
    toastError('Upload failed')
  } finally {
    uploading.value = false
  }
}
</script>

<template>
  <AppModal :open="open" title="Upload file" @close="emit('close')">
    <form class="space-y-4" @submit.prevent="submit">
      <p class="rounded-lg bg-slate-50 px-3 py-2 text-xs text-slate-500 dark:bg-slate-800 dark:text-slate-400">
        Uploading into 📁 {{ folderName || 'current folder' }}
      </p>
      <!-- dropzone -->
      <label
        class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed p-8 text-center transition"
        :class="
          isDragging
            ? 'border-brand-500 bg-brand-50 dark:bg-brand-700/20'
            : 'border-slate-300 hover:border-brand-400 dark:border-slate-600'
        "
        @dragover.prevent="isDragging = true"
        @dragleave="isDragging = false"
        @drop.prevent="onDrop"
      >
        <span class="text-3xl">📤</span>
        <span class="text-sm font-medium">{{ file ? file.name : 'Drag & drop or click to choose' }}</span>
        <span class="text-xs text-slate-400">PDF, image, office, zip — max 25MB</span>
        <input type="file" class="hidden" @change="onFileChosen($event.target.files[0])" />
      </label>

      <div>
        <label class="mb-1 block text-sm font-medium" for="upload-title">Title</label>
        <input
          id="upload-title"
          v-model="title"
          type="text"
          class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800"
        />
      </div>

      <div>
        <label class="mb-1 block text-sm font-medium" for="upload-dept">Department</label>
        <select
          id="upload-dept"
          v-model.number="departmentId"
          class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800"
        >
          <option :value="null" disabled>Choose department…</option>
          <option v-for="dept in departments" :key="dept.id" :value="dept.id">{{ dept.name }}</option>
        </select>
      </div>

      <div v-if="uploading" class="h-2 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
        <div class="h-full rounded-full bg-brand-600 transition-all" :style="{ width: progress + '%' }" />
      </div>

      <p v-if="error" class="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700 dark:bg-red-950/50 dark:text-red-300">
        {{ error }}
      </p>

      <div class="flex justify-end gap-2">
        <button type="button" class="rounded-lg px-4 py-2 text-sm hover:bg-slate-100 dark:hover:bg-slate-800" @click="emit('close')">
          Cancel
        </button>
        <button
          type="submit"
          :disabled="!canSubmit || uploading"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700 disabled:opacity-50"
        >
          {{ uploading ? `Uploading ${progress}%` : 'Upload' }}
        </button>
      </div>
    </form>
  </AppModal>
</template>
