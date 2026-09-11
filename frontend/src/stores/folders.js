import { defineStore } from 'pinia'
import { foldersApi } from '../api/folders'

export const useFoldersStore = defineStore('folders', {
  state: () => ({
    currentFolderId: null,
    breadcrumbs: [],
    childFolders: [],
    isLoading: false,
  }),

  getters: {
    currentFolder: (state) =>
      state.breadcrumbs.length ? state.breadcrumbs[state.breadcrumbs.length - 1] : null,
  },

  actions: {
    async openFolder(folderId = null) {
      this.currentFolderId = folderId
      this.isLoading = true
      try {
        const [children, crumbs] = await Promise.all([
          foldersApi.list(folderId),
          folderId ? foldersApi.breadcrumbs(folderId) : Promise.resolve([]),
        ])
        this.childFolders = children
        this.breadcrumbs = crumbs
      } finally {
        this.isLoading = false
      }
    },

    async createFolder(name, parentId = null) {
      await foldersApi.create({ name, parent_id: parentId })
      await this.openFolder(this.currentFolderId)
    },

    async renameFolder(folderId, name) {
      await foldersApi.update(folderId, { name, parent_id: this.breadcrumbs.at(-1)?.id ?? null })
      await this.openFolder(this.currentFolderId)
    },

    async deleteFolder(folderId) {
      await foldersApi.remove(folderId)
      await this.openFolder(this.currentFolderId)
    },
  },
})
