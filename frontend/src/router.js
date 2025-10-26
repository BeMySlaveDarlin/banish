import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const routes = [
  {
    path: '/',
    redirect: '/chats'
  },
  {
    path: '/auth/:token',
    name: 'AdminLogin',
    component: () => import('./pages/AdminLogin.vue')
  },
  {
    path: '/',
    component: () => import('./pages/AdminLayout.vue'),
    meta: { requiresAuth: true },
    children: [
      {
        path: 'chats',
        name: 'ChatsList',
        component: () => import('./pages/ChatsList.vue')
      },
      {
        path: 'chat/:chatId',
        name: 'ChatDetails',
        component: () => import('./pages/ChatDetails.vue')
      },
      {
        path: 'chat/:chatId/users',
        name: 'UsersList',
        component: () => import('./pages/UsersList.vue')
      },
      {
        path: 'chat/:chatId/users/:userId',
        name: 'UserDetails',
        component: () => import('./pages/UserDetails.vue')
      },
      {
        path: 'chat/:chatId/config',
        name: 'ChatConfig',
        component: () => import('./pages/ChatConfig.vue')
      },
      {
        path: 'chat/:chatId/logs',
        name: 'AuditLogs',
        component: () => import('./pages/AuditLogs.vue')
      },
    ]
  }
]

const router = createRouter({
  history: createWebHistory('/admin/'),
  routes
})

router.beforeEach(async (to, from, next) => {
  console.log('🔀 Router navigation:', {
    from: from.path,
    to: to.path,
    name: to.name
  })

  const authStore = useAuthStore()
  const requiresAuth = to.matched.some(record => record.meta.requiresAuth)

  // Check session on first protected route access
  if (requiresAuth && !authStore.sessionChecked) {
    console.log('🔐 First protected route access, validating session...')
    await authStore.validateSession()
  }

  console.log('🔐 Auth check:', {
    requiresAuth,
    isAuthenticated: authStore.isAuthenticated,
    sessionChecked: authStore.sessionChecked
  })

  if (requiresAuth && !authStore.isAuthenticated) {
    console.log('⛔ Access denied, redirecting to auth')
    next('/auth/invalid')
  } else {
    console.log('✅ Navigation allowed')
    next()
  }
})

router.afterEach((to, from) => {
  console.log('✅ Navigation completed to:', to.path)
})

export default router
