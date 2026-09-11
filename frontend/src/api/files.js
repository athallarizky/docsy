import client, { getToken } from './client'

export const filesApi = {
  async list({ folderId, departmentId, search, page = 1, perPage = 20 } = {}) {
    const { data, meta } = await client.get('/files', {
      params: {
        ...(folderId ? { folder_id: folderId } : {}),
        ...(departmentId ? { department_id: departmentId } : {}),
        ...(search ? { search } : {}),
        page,
        per_page: perPage,
      },
    })
    return { files: data, meta }
  },
  async detail(fileId) {
    const { data } = await client.get(`/files/${fileId}`)
    return data
  },
  async update(fileId, payload) {
    const { data } = await client.put(`/files/${fileId}`, payload)
    return data
  },
  async remove(fileId) {
    return client.delete(`/files/${fileId}`)
  },
  /**
   * Upload with progress. Axios handles multipart automatically when a
   * FormData body is passed — do NOT set Content-Type manually.
   */
  upload({ title, folderId, departmentId, file, onProgress }) {
    const form = new FormData()
    form.append('title', title)
    // never stringify null — omit instead (literal "null" fails the backend integer rule)
    if (folderId) form.append('folder_id', folderId)
    form.append('department_id', departmentId)
    form.append('file', file)

    return client.post('/files', form, {
      onUploadProgress: (event) => {
        if (event.total) onProgress?.(Math.round((event.loaded / event.total) * 100))
      },
    })
  },
  /**
   * Private files need the Authorization header — <img src> can't send one.
   * Fetch as blob with the token, then hand back an object URL.
   */
  async objectUrl(fileId, mode = 'preview') {
    const response = await client.get(`/files/${fileId}/${mode}`, {
      responseType: 'blob',
    })
    return URL.createObjectURL(response) // axios returns the blob directly (envelope unwrap is a no-op for blobs)
  },
  async download(fileId) {
    const blob = await client.get(`/files/${fileId}/download`, { responseType: 'blob' })
    return blob
  },
}

export { getToken }
