import client from './client'

export const authApi = {
  async login(email, password) {
    const envelope = await client.post('/auth/login', { email, password })
    return envelope.data // { token, user }
  },
  async me() {
    const envelope = await client.get('/auth/me')
    return envelope.data // user
  },
  async logout() {
    return client.post('/auth/logout')
  },
}
