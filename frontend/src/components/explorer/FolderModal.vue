<script setup>
import { ref, watch } from 'vue'
import AppModal from '../common/AppModal.vue'

const props = defineProps({
  open: Boolean,
  mode: { type: String, default: 'create' }, // create | rename
  folder: { type: Object, default: null },
})
const emit = defineEmits(['close', 'submit'])

const name = ref('')
const error = ref(null)

watch(
  () => props.open,
  (open) => {
    if (open) {
      name.value = props.mode === 'rename' ? props.folder?.name : ''
      error.value = null
    }
  },
)

function submit() {
  if (!name.value.trim()) {
    error.value = 'Folder name is required'
    return
  }
  emit('submit', name.value.trim())
}
</script>

<template>
  <AppModal :open="open" :title="mode === 'rename' ? 'Rename folder' : 'New folder'" @close="emit('close')">
    <form class="space-y-4" @submit.prevent="submit">
      <div>
        <label class="mb-1 block text-sm font-medium" for="folder-name">Name</label>
        <input
          id="folder-name"
          v-model="name"
          type="text"
          autofocus
          class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm outline-none focus:border-brand-500 dark:border-slate-700 dark:bg-slate-800"
          placeholder="e.g. Q3 Reports"
        />
        <p v-if="error" class="mt-1 text-xs text-red-600">{{ error }}</p>
      </div>
      <div class="flex justify-end gap-2">
        <button type="button" class="rounded-lg px-4 py-2 text-sm hover:bg-slate-100 dark:hover:bg-slate-800" @click="emit('close')">
          Cancel
        </button>
        <button type="submit" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">
          {{ mode === 'rename' ? 'Rename' : 'Create' }}
        </button>
      </div>
    </form>
  </AppModal>
</template>
