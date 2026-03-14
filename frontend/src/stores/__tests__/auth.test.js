import { describe, it, expect, vi, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useAuthStore } from '@/stores/auth'

vi.mock('@/utils/api', () => ({
  default: {
    post: vi.fn(),
    get: vi.fn(),
    interceptors: {
      request: { use: vi.fn() },
      response: { use: vi.fn() },
    },
  },
}))

vi.mock('@/utils/logger', () => ({
  default: {
    log: vi.fn(),
    warn: vi.fn(),
    error: vi.fn(),
    info: vi.fn(),
  },
}))

import api from '@/utils/api'

describe('auth store', () => {
  let store

  beforeEach(() => {
    setActivePinia(createPinia())
    store = useAuthStore()
    vi.clearAllMocks()
  })

  describe('initial state', () => {
    it('has null user and isAuthenticated false', () => {
      expect(store.user).toBeNull()
      expect(store.isAuthenticated).toBe(false)
      expect(store.sessionChecked).toBe(false)
    })
  })

  describe('login', () => {
    it('sets user on successful login and returns true', async () => {
      const userData = { id: 1, name: 'Admin' }
      api.post.mockResolvedValueOnce({ data: userData })

      const result = await store.login('test-token')

      expect(result).toBe(true)
      expect(store.user).toEqual(userData)
      expect(store.isAuthenticated).toBe(true)
      expect(api.post).toHaveBeenCalledWith('/api/admin/auth/login/', { token: 'test-token' })
    })

    it('returns false on login failure and keeps user null', async () => {
      api.post.mockRejectedValueOnce(new Error('Invalid token'))

      const result = await store.login('bad-token')

      expect(result).toBe(false)
      expect(store.user).toBeNull()
      expect(store.isAuthenticated).toBe(false)
    })
  })

  describe('logout', () => {
    it('clears user after successful logout', async () => {
      store.user = { id: 1, name: 'Admin' }
      api.post.mockResolvedValueOnce({})

      await store.logout()

      expect(store.user).toBeNull()
      expect(store.isAuthenticated).toBe(false)
      expect(api.post).toHaveBeenCalledWith('/api/admin/auth/logout/')
    })

    it('clears user even when logout API fails', async () => {
      store.user = { id: 1, name: 'Admin' }
      api.post.mockRejectedValueOnce(new Error('Network error'))

      await store.logout()

      expect(store.user).toBeNull()
      expect(store.isAuthenticated).toBe(false)
    })
  })

  describe('validateSession', () => {
    it('sets user on successful validation and returns true', async () => {
      const userData = { id: 1, name: 'Admin' }
      api.post.mockResolvedValueOnce({ data: userData })

      const result = await store.validateSession()

      expect(result).toBe(true)
      expect(store.user).toEqual(userData)
      expect(store.sessionChecked).toBe(true)
      expect(api.post).toHaveBeenCalledWith('/api/admin/auth/validate/')
    })

    it('clears user on failed validation and returns false', async () => {
      api.post.mockRejectedValueOnce(new Error('Unauthorized'))

      const result = await store.validateSession()

      expect(result).toBe(false)
      expect(store.user).toBeNull()
      expect(store.sessionChecked).toBe(true)
    })

    it('skips API call if session already checked and returns cached result', async () => {
      store.sessionChecked = true
      store.user = { id: 1, name: 'Admin' }

      const result = await store.validateSession()

      expect(result).toBe(true)
      expect(api.post).not.toHaveBeenCalled()
    })

    it('returns false when session already checked and no user', async () => {
      store.sessionChecked = true
      store.user = null

      const result = await store.validateSession()

      expect(result).toBe(false)
      expect(api.post).not.toHaveBeenCalled()
    })
  })

  describe('isAuthenticated computed', () => {
    it('returns true when user is set', () => {
      store.user = { id: 1 }
      expect(store.isAuthenticated).toBe(true)
    })

    it('returns false when user is null', () => {
      store.user = null
      expect(store.isAuthenticated).toBe(false)
    })
  })
})
