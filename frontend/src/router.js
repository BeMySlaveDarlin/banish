import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import logger from '@/utils/logger'

const routes = [
  {
    path: '/',
    redirect: '/chats',
  },
  {
    path: '/auth/:token',
    name: 'AdminLogin',
    component: () => import('./pages/AdminLogin.vue'),
  },
  {
    path: '/',
    component: () => import('./pages/AdminLayout.vue'),
    meta: { requiresAuth: true },
    children: [
      {
        path: 'chats',
        name: 'ChatsList',
        component: () => import('./pages/ChatsList.vue'),
      },
      {
        path: 'chat/:chatId',
        name: 'ChatDetails',
        component: () => import('./pages/ChatDetails.vue'),
      },
      {
        path: 'chat/:chatId/users',
        name: 'UsersList',
        component: () => import('./pages/UsersList.vue'),
      },
      {
        path: 'chat/:chatId/users/:userId',
        name: 'UserDetails',
        component: () => import('./pages/UserDetails.vue'),
      },
      {
        path: 'chat/:chatId/config',
        name: 'ChatConfig',
        component: () => import('./pages/ChatConfig.vue'),
      },
      {
        path: 'chat/:chatId/logs',
        name: 'AuditLogs',
        component: () => import('./pages/AuditLogs.vue'),
      },
    ],
  },
]

const router = createRouter({
  history: createWebHistory('/admin/'),
  routes,
})

router.beforeEach(async (to, from, next) => {
  logger.log('Router navigation:', from.path, '->', to.path)

  const authStore = useAuthStore()
  const requiresAuth = to.matched.some((record) => record.meta.requiresAuth)

  if (requiresAuth && !authStore.sessionChecked) {
    await authStore.validateSession()
  }

  if (requiresAuth && !authStore.isAuthenticated) {
    next('/auth/expired')
  } else {
    next()
  }
})

export default router
