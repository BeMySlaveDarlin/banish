import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/utils/api'
import logger from '@/utils/logger'

export const useAuthStore = defineStore('auth', () => {
  const user = ref(null)
  const sessionChecked = ref(false)

  const isAuthenticated = computed(() => !!user.value)

  const login = async (loginToken) => {
    try {
      const response = await api.post('/api/admin/auth/login/', { token: loginToken })
      user.value = response.data
      logger.log('User logged in, cookie set by server')
      return true
    } catch (error) {
      logger.error('Login failed:', error.message)
      return false
    }
  }

  const logout = async () => {
    try {
      await api.post('/api/admin/auth/logout/')
    } catch (error) {
      logger.error('Logout API error:', error.message)
    } finally {
      user.value = null
    }
  }

  const validateSession = async () => {
    if (sessionChecked.value) {
      return !!user.value
    }

    try {
      const response = await api.post('/api/admin/auth/validate/')
      user.value = response.data
      sessionChecked.value = true
      return true
    } catch (error) {
      logger.error('Session validation failed:', error.message)
      user.value = null
      sessionChecked.value = true
      return false
    }
  }

  return {
    user,
    isAuthenticated,
    login,
    logout,
    validateSession,
    sessionChecked,
  }
})
