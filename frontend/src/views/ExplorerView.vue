<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { useFoldersStore } from '../stores/folders'
import { useFilesStore } from '../stores/files'
import { departmentsApi } from '../api/departments'
import { useToast } from '../composables/useToast'
import AppToasts from '../components/common/AppToasts.vue'
import BreadcrumbBar from '../components/explorer/BreadcrumbBar.vue'
import FileTable from '../components/explorer/FileTable.vue'
import FolderModal from '../components/explorer/FolderModal.vue'
import UploadModal from '../components/files/UploadModal.vue'
import PreviewModal from '../components/files/PreviewModal.vue'
import EditFileModal from '../components/files/EditFileModal.vue'
import DepartmentModal from '../components/explorer/DepartmentModal.vue'

const route = useRoute()
const auth = useAuthStore()
const folders = useFoldersStore()
const files = useFilesStore()
const { error: toastError, success: toastSuccess } = useToast()

const departments = ref([])
const searchInput = ref('')

const showFolderModal = ref(false)
const showDeptModal = ref(false)
const folderModalMode = ref('create')
const renamingFolder = ref(null)
const showUpload = ref(false)
const previewFile = ref(null)
const editingFile = ref(null)

const isSearch = computed(() => files.searchQuery.trim().length > 0)

async function loadDepartments() {
  if (!departments.value.length) departments.value = await departmentsApi.list()
}

async function openFolder(folderId) {
  await folders.openFolder(folderId)
  if (!isSearch.value) await files.fetchFiles({ folderId: folders.currentFolderId })
}

function navigate(folderId) {
  openFolder(folderId)
}

async function submitFolder(name) {
  try {
    if (folderModalMode.value === 'rename') {
      await folders.renameFolder(renamingFolder.value.id, name)
      toastSuccess('Folder renamed')
    } else {
      await folders.createFolder(name, folders.currentFolderId)
      toastSuccess(`Folder "${name}" created`)
    }
    showFolderModal.value = false
  } catch (e) {
    toastError(e.errors ? Object.values(e.errors).flat().join(' ') : e.message)
  }
}

async function submitDepartment(name) {
  try {
    await departmentsApi.create({ name })
    departments.value = await departmentsApi.list()
    showDeptModal.value = false
    toastSuccess(`Department "${name}" created`)
  } catch (e) {
    toastError(e.errors ? Object.values(e.errors).flat().join(' ') : e.message)
  }
}

async function deleteFolder(folder) {
  if (!confirm(`Delete "${folder.name}" and its entire subtree?`)) return
  try {
    await folders.deleteFolder(folder.id)
    toastSuccess(`Folder "${folder.name}" deleted`)
  } catch (e) {
    toastError(e.message)
  }
}

async function deleteFile(file) {
  if (!confirm(`Delete file "${file.title}"?`)) return
  try {
    await files.removeFile(file.id)
    toastSuccess(`File "${file.title}" deleted`)
  } catch (e) {
    toastError(e.message)
  }
}

function applySearch() {
  files.searchQuery = searchInput.value.trim()
  files.fetchFiles({ folderId: folders.currentFolderId })
}

function clearSearch() {
  searchInput.value = ''
  files.searchQuery = ''
  files.fetchFiles({ folderId: folders.currentFolderId })
}

watch(() => files.departmentFilter, () => files.fetchFiles({ folderId: folders.currentFolderId }))
watch(
  () => route.params.id,
  (id) => openFolder(id ? Number(id) : null),
)

onMounted(() => {
  openFolder(route.params.id ? Number(route.params.id) : null)
  loadDepartments()
})
</script>

<template>
  <div class="space-y-5">
    <AppToasts />

    <!-- toolbar -->
    <div class="flex flex-wrap items-center gap-2">
      <div class="flex flex-1 flex-col items-stretch gap-2 sm:flex-row sm:items-center">
        <input
          v-model="searchInput"
          type="search"
          placeholder="Search files (full-text)…"
          class="w-full max-w-sm rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm outline-none focus:border-brand-500 dark:border-slate-700 dark:bg-slate-800"
          @keyup.enter="applySearch"
        />
        <select
          v-model.number="files.departmentFilter"
          class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 sm:w-auto"
        >
          <option :value="null">All departments</option>
          <option v-for="dept in departments" :key="dept.id" :value="dept.id">{{ dept.name }}</option>
        </select>
      </div>

      <div v-if="auth.isAdmin" class="flex gap-2">
        <button
          type="button"
          class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium hover:bg-slate-50 dark:border-slate-600 dark:hover:bg-slate-800"
          @click="((folderModalMode = 'create'), (renamingFolder = null), (showFolderModal = true))"
        >
          + New Folder
        </button>
        <button
          type="button"
          class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium hover:bg-slate-50 dark:border-slate-600 dark:hover:bg-slate-800"
          @click="showDeptModal = true"
        >
          + Department
        </button>
        <button
          type="button"
          class="rounded-lg bg-brand-600 px-3 py-2 text-sm font-semibold text-white hover:bg-brand-700 disabled:cursor-not-allowed disabled:opacity-40"
          :disabled="!folders.currentFolderId"
          :title="folders.currentFolderId ? 'Upload into this folder' : 'Open a folder first — files live inside folders'"
          @click="showUpload = true"
        >
          ⬆ Upload File
        </button>
      </div>
    </div>

    <BreadcrumbBar :breadcrumbs="folders.breadcrumbs" @navigate="navigate" />

    <!-- search mode banner -->
    <div
      v-if="isSearch"
      class="flex items-center justify-between rounded-xl bg-brand-50 px-4 py-2.5 text-sm dark:bg-brand-700/20"
    >
      <span>
        Results for <strong>{{ files.searchQuery }}</strong>
        <span v-if="files.meta" class="text-slate-500"> — {{ files.meta.total }} file(s)</span>
      </span>
      <button type="button" class="font-medium text-brand-700 hover:underline dark:text-brand-100" @click="clearSearch">
        Clear search ✕
      </button>
    </div>

    <!-- folders -->
    <section v-if="!isSearch">
      <h2 class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">
        Folders ({{ folders.childFolders.length }})
      </h2>

      <div v-if="folders.isLoading" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <div v-for="i in 4" :key="i" class="h-20 animate-pulse rounded-xl bg-slate-100 dark:bg-slate-800" />
      </div>

      <div v-else-if="!folders.childFolders.length" class="rounded-xl border border-dashed border-slate-300 p-6 text-center text-sm text-slate-400 dark:border-slate-700">
        No folders here yet
      </div>

      <div v-else class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <div
          v-for="folder in folders.childFolders"
          :key="folder.id"
          class="group relative flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 bg-white p-4 transition hover:border-brand-400 hover:shadow-sm dark:border-slate-800 dark:bg-slate-900"
          @dblclick="navigate(folder.id)"
          @click="navigate(folder.id)"
        >
          <span class="text-2xl">📁</span>
          <div class="min-w-0">
            <p class="truncate font-medium">{{ folder.name }}</p>
          </div>
          <div
            v-if="auth.isAdmin"
            class="absolute right-2 top-2 hidden gap-1 group-hover:flex"
            @click.stop
          >
            <button
              type="button"
              class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-800"
              title="Rename"
              @click="((folderModalMode = 'rename'), (renamingFolder = folder), (showFolderModal = true))"
            >
              ✏️
            </button>
            <button
              type="button"
              class="rounded-lg p-1.5 text-red-400 hover:bg-red-50 dark:hover:bg-red-950/40"
              title="Delete subtree"
              @click="deleteFolder(folder)"
            >
              🗑
            </button>
          </div>
        </div>
      </div>
    </section>

    <!-- files -->
    <section>
      <h2 class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">
        Files ({{ files.files.length }}{{ files.meta ? ` of ${files.meta.total}` : '' }})
      </h2>

      <div v-if="files.isLoading" class="space-y-2">
        <div v-for="i in 3" :key="i" class="h-12 animate-pulse rounded-xl bg-slate-100 dark:bg-slate-800" />
      </div>

      <div v-else-if="!files.files.length" class="rounded-xl border border-dashed border-slate-300 p-8 text-center text-sm text-slate-400 dark:border-slate-700">
        {{ isSearch ? 'No files match your search' : folders.currentFolderId ? 'No files in this folder' : 'Open a folder to see & upload files' }}
      </div>

      <FileTable v-else :files="files.files" :is-admin="auth.isAdmin" @open="previewFile = $event" @delete="deleteFile" />

      <div v-if="files.meta && files.meta.last_page > 1" class="mt-3 flex items-center justify-center gap-2 text-sm">
        <button
          v-for="p in files.meta.last_page"
          :key="p"
          type="button"
          class="rounded-lg px-3 py-1.5"
          :class="p === files.meta.current_page ? 'bg-brand-600 text-white' : 'hover:bg-slate-100 dark:hover:bg-slate-800'"
          @click="files.meta && ((files.meta.current_page = p), files.fetchFiles({ folderId: folders.currentFolderId }))"
        >
          {{ p }}
        </button>
      </div>
    </section>

    <!-- modals -->
    <FolderModal
      :open="showFolderModal"
      :mode="folderModalMode"
      :folder="renamingFolder"
      @close="showFolderModal = false"
      @submit="submitFolder"
    />
    <UploadModal
      :open="showUpload"
      :folder-id="folders.currentFolderId"
      :folder-name="folders.breadcrumbs.at(-1)?.name"
      @close="showUpload = false"
      @uploaded="files.fetchFiles({ folderId: folders.currentFolderId })"
    />
    <DepartmentModal :open="showDeptModal" @close="showDeptModal = false" @submit="submitDepartment" />
    <PreviewModal :open="Boolean(previewFile)" :file="previewFile" @close="previewFile = null" @edit="((editingFile = $event), (previewFile = null))" />
    <EditFileModal
      :open="Boolean(editingFile)"
      :file="editingFile"
      @close="editingFile = null"
      @saved="files.fetchFiles({ folderId: folders.currentFolderId })"
    />
  </div>
</template>
