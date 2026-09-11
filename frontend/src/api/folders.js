import client from './client'

export const foldersApi = {
  async list(parentId = null) {
    const { data } = await client.get('/folders', {
      params: parentId ? { parent_id: parentId } : {},
    })
    return data
  },
  async tree() {
    const { data } = await client.get('/folders/tree')
    return data
  },
  async breadcrumbs(folderId) {
    const { data } = await client.get(`/folders/${folderId}/breadcrumbs`)
    return data
  },
  async create(payload) {
    const { data } = await client.post('/folders', payload)
    return data
  },
  async update(folderId, payload) {
    const { data } = await client.put(`/folders/${folderId}`, payload)
    return data
  },
  async remove(folderId) {
    return client.delete(`/folders/${folderId}`)
  },
}
