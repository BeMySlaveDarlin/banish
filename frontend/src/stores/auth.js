import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/utils/api'

console.log('🏪 Auth store loading...')

export const useAuthStore = defineStore('auth', () => {
  const user = ref(null)
  const token = ref(null)
  const sessionChecked = ref(false)

  console.log('🔓 Auth store initialized')

  const isAuthenticated = computed(() => !!user.value)

  const login = async (loginToken) => {
    console.log('🔐 Auth store: Starting login with token:', !!loginToken)
    try {
      const url = `/api/admin/auth/login/${loginToken}`
      console.log('📡 Auth store: Sending GET to', url)

      const response = await api.get(url)
      console.log('✅ Auth store: Login response received:', response.data)
      // Token is now in HTTP-only cookie, no need to store in localStorage
      user.value = response.data
      token.value = loginToken  // Store for reference only
      console.log('💾 Auth store: User logged in, cookie set by server')
      return true
    } catch (error) {
      console.error('❌ Auth store: Login failed:', {
        message: error.message,
        status: error.response?.status,
        data: error.response?.data
      })
      return false
    }
  }

  const logout = async () => {
    console.log('🚪 Auth store: Starting logout...')
    try {
      await api.get('/api/admin/auth/logout')
      console.log('✅ Auth store: Logout API call successful, cookie cleared')
    } catch (error) {
      console.error('⚠️ Auth store: Logout API error:', error.message)
    } finally {
      token.value = null
      user.value = null
      console.log('🗑️ Auth store: User session cleared')
    }
  }

  const validateSession = async () => {
    console.log('🔄 Auth store: Validating session...')
    if (sessionChecked.value) {
      console.log('🔄 Auth store: Session already checked in this session')
      return !!user.value
    }

    try {
      console.log('📡 Auth store: Sending validation request')
      const response = await api.post('/api/admin/auth/validate')
      console.log('✅ Auth store: Session validated:', response.data)
      user.value = response.data
      sessionChecked.value = true
      return true
    } catch (error) {
      console.error('❌ Auth store: Session validation failed:', {
        message: error.message,
        status: error.response?.status
      })
      token.value = null
      user.value = null
      sessionChecked.value = true
      console.log('🗑️ Auth store: Invalid session cleared')
      return false
    }
  }

  return {
    user,
    token,
    isAuthenticated,
    login,
    logout,
    validateSession,
    sessionChecked
  }
})
