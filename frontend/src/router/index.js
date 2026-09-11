import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { registerUnauthorizedHandler } from '../api/client'

const routes = [
  {
    path: '/login',
    name: 'login',
    component: () => import('../views/LoginView.vue'),
    meta: { guestOnly: true },
  },
  {
    path: '/',
    name: 'explorer',
    component: () => import('../views/ExplorerView.vue'),
  },
  {
    path: '/folders/:id?',
    name: 'folder',
    component: () => import('../views/ExplorerView.vue'),
  },
  {
    path: '/dashboard',
    name: 'dashboard',
    component: () => import('../views/DashboardView.vue'),
    meta: { adminOnly: true },
  },
  {
    path: '/:pathMatch(.*)*',
    redirect: '/',
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach(async (to) => {
  const auth = useAuthStore()

  if (!auth.isAuthenticated) {
    return to.meta.guestOnly ? true : { name: 'login' }
  }

  // hydrate user profile once per session (needed for role checks)
  if (!auth.user) {
    await auth.fetchMe()
    if (!auth.isAuthenticated) return { name: 'login' }
  }

  if (to.meta.guestOnly) return { name: 'explorer' }
  if (to.meta.adminOnly && !auth.isAdmin) return { name: 'explorer' }
  return true
})

// token died mid-session -> back to login (registered to avoid circular import)
registerUnauthorizedHandler(() => {
  const auth = useAuthStore()
  auth.user = null
  router.push({ name: 'login' })
})

export default router
