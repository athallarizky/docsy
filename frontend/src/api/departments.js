import client from './client'

export const departmentsApi = {
  async list() {
    const { data } = await client.get('/departments')
    return data
  },
  async create(payload) {
    const { data } = await client.post('/departments', payload)
    return data
  },
}
