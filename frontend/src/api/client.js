import axios from 'axios'

const TOKEN_KEY = 'docsy.token'

export const getToken = () => localStorage.getItem(TOKEN_KEY)
export const setToken = (token) => localStorage.setItem(TOKEN_KEY, token)
export const clearToken = () => localStorage.removeItem(TOKEN_KEY)

/**
 * Single axios instance for the whole app.
 * - attaches the bearer token to every request
 * - unwraps the API envelope {success, message, data} -> caller gets `data`
 * - normalizes errors into { message, errors, status } and handles 401 globally
 */
const client = axios.create({
  baseURL: '/api/v1',
  headers: { Accept: 'application/json' },
})

client.interceptors.request.use((config) => {
  const token = getToken()
  if (token) config.headers.Authorization = `Bearer ${token}`
  return config
})

let onUnauthorized = () => {}
/** router registers itself here to avoid a circular import */
export function registerUnauthorizedHandler(fn) {
  onUnauthorized = fn
}

client.interceptors.response.use(
  (response) => response.data, // unwrap: callers receive the envelope body directly
  (error) => {
    const status = error.response?.status
    const body = error.response?.data

    const normalized = {
      status,
      success: body?.success ?? false,
      message:
        body?.message ??
        (status === undefined ? 'Network error — is the API running?' : error.message),
      errors: body?.errors ?? null,
    }

    // a dead/revoked token logs the user out — except on the login call itself
    const isLoginRequest = error.config?.url?.includes('/auth/login')
    if (status === 401 && !isLoginRequest) {
      clearToken()
      onUnauthorized()
    }

    return Promise.reject(normalized)
  },
)

export default client
