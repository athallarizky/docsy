import { defineStore } from 'pinia'
import { authApi } from '../api/auth'
import { getToken, setToken, clearToken } from '../api/client'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    token: getToken(),
    user: null,
  }),

  getters: {
    isAuthenticated: (state) => Boolean(state.token),
    isAdmin: (state) => state.user?.role === 'administrator',
  },

  actions: {
    async login(email, password) {
      const { token, user } = await authApi.login(email, password)
      setToken(token)
      this.token = token
      this.user = user
      return user
    },

    async fetchMe() {
      if (!this.token) return null
      try {
        this.user = await authApi.me()
      } catch {
        // 401 already cleared the token via the interceptor
        this.token = null
      }
      return this.user
    },

    async logout() {
      try {
        await authApi.logout()
      } catch {
        // revocation best-effort: local state clears regardless
      }
      clearToken()
      this.token = null
      this.user = null
    },
  },
})
