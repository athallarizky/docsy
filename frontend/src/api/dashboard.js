import client from './client'

export const dashboardApi = {
  async stats() {
    const { data } = await client.get('/dashboard/stats')
    return data
  },
}
