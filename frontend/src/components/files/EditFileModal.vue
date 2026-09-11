<script setup>
import { ref, watch } from 'vue'
import AppModal from '../common/AppModal.vue'
import { filesApi } from '../../api/files'
import { departmentsApi } from '../../api/departments'
import { useToast } from '../../composables/useToast'

const props = defineProps({
  open: Boolean,
  file: { type: Object, default: null },
})
const emit = defineEmits(['close', 'saved'])

const { success } = useToast()
const departments = ref([])
const title = ref('')
const departmentId = ref(null)
const saving = ref(false)
const error = ref(null)

watch(
  () => props.open,
  async (open) => {
    if (!open) return
    title.value = props.file?.title ?? ''
    departmentId.value = props.file?.department?.id ?? null
    error.value = null
    if (!departments.value.length) departments.value = await departmentsApi.list()
  },
)

async function submit() {
  saving.value = true
  error.value = null
  try {
    await filesApi.update(props.file.id, {
      title: title.value.trim(),
      department_id: departmentId.value,
    })
    success('File metadata updated')
    emit('saved')
    emit('close')
  } catch (e) {
    error.value = e.errors ? Object.values(e.errors).flat().join(' ') : e.message
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <AppModal :open="open" title="Edit file metadata" @close="emit('close')">
    <form class="space-y-4" @submit.prevent="submit">
      <div>
        <label class="mb-1 block text-sm font-medium" for="edit-title">Title</label>
        <input
          id="edit-title"
          v-model="title"
          type="text"
          class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800"
        />
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium" for="edit-dept">Department</label>
        <select
          id="edit-dept"
          v-model.number="departmentId"
          class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800"
        >
          <option v-for="dept in departments" :key="dept.id" :value="dept.id">{{ dept.name }}</option>
        </select>
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
          :disabled="saving || !title.trim() || !departmentId"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700 disabled:opacity-50"
        >
          {{ saving ? 'Saving…' : 'Save' }}
        </button>
      </div>
    </form>
  </AppModal>
</template>
