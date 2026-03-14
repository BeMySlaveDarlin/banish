import { describe, it, expect, vi, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useChatsStore } from '@/stores/chats'

vi.mock('@/utils/api', () => ({
  default: {
    get: vi.fn(),
    post: vi.fn(),
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

describe('chats store', () => {
  let store

  beforeEach(() => {
    setActivePinia(createPinia())
    store = useChatsStore()
    vi.clearAllMocks()
  })

  describe('initial state', () => {
    it('has empty defaults', () => {
      expect(store.chatsList).toEqual([])
      expect(store.chatInfo).toBeNull()
      expect(store.chatConfig).toBeNull()
      expect(store.loading).toBe(false)
      expect(store.error).toBe('')
    })
  })

  describe('fetchChats', () => {
    it('loads chats on success', async () => {
      const chats = [{ id: 1, title: 'Test Chat' }, { id: 2, title: 'Chat 2' }]
      api.get.mockResolvedValueOnce({ data: { chats } })

      await store.fetchChats()

      expect(store.chatsList).toEqual(chats)
      expect(store.loading).toBe(false)
      expect(store.error).toBe('')
      expect(api.get).toHaveBeenCalledWith('/api/admin/chats')
    })

    it('defaults to empty array when response has no chats key', async () => {
      api.get.mockResolvedValueOnce({ data: {} })

      await store.fetchChats()

      expect(store.chatsList).toEqual([])
    })

    it('sets error and re-throws on failure', async () => {
      const err = new Error('Network Error')
      err.response = undefined
      api.get.mockRejectedValueOnce(err)

      await expect(store.fetchChats()).rejects.toThrow('Network Error')

      expect(store.error).toContain('Failed to load chats')
      expect(store.loading).toBe(false)
    })

    it('sets loading to true during fetch', async () => {
      let resolvePromise
      api.get.mockReturnValueOnce(new Promise((resolve) => {
        resolvePromise = resolve
      }))

      const fetchPromise = store.fetchChats()
      expect(store.loading).toBe(true)

      resolvePromise({ data: { chats: [] } })
      await fetchPromise

      expect(store.loading).toBe(false)
    })
  })

  describe('fetchChatInfo', () => {
    it('loads chat info with default params', async () => {
      const chatData = {
        id: 1,
        title: 'Test',
        recentBans: [{ id: 10 }],
        totalRecentBans: 5,
        hasMore: true,
      }
      api.get.mockResolvedValueOnce({ data: chatData })

      await store.fetchChatInfo(123)

      expect(store.chatInfo).toEqual(chatData)
      expect(store.recentBans).toEqual([{ id: 10 }])
      expect(store.totalBans).toBe(5)
      expect(store.bansHasMore).toBe(true)
      expect(api.get).toHaveBeenCalledWith('/api/admin/chat/123/info', {
        params: { limit: 5, offset: 0 },
      })
    })

    it('passes custom limit and offset', async () => {
      api.get.mockResolvedValueOnce({ data: {} })

      await store.fetchChatInfo(1, { limit: 10, offset: 5 })

      expect(api.get).toHaveBeenCalledWith('/api/admin/chat/1/info', {
        params: { limit: 10, offset: 5 },
      })
    })
  })

  describe('clearError', () => {
    it('resets error to empty string', () => {
      store.error = 'Some error'
      store.clearError()
      expect(store.error).toBe('')
    })
  })
})
