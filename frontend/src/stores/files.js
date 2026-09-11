import { defineStore } from 'pinia'
import { filesApi } from '../api/files'

export const useFilesStore = defineStore('files', {
  state: () => ({
    files: [],
    meta: null,
    searchQuery: '',
    departmentFilter: null,
    isLoading: false,
  }),

  actions: {
    async fetchFiles({ folderId } = {}) {
      this.isLoading = true
      try {
        const { files, meta } = await filesApi.list({
          folderId: this.searchQuery ? null : folderId,
          departmentId: this.departmentFilter,
          search: this.searchQuery || undefined,
        })
        this.files = files
        this.meta = meta
      } finally {
        this.isLoading = false
      }
    },

    async removeFile(fileId) {
      await filesApi.remove(fileId)
      await this.fetchFiles()
    },
  },
})
