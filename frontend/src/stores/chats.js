import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/utils/api'
import { getApiErrorMessage } from '@/utils/formatters'

export const useChatsStore = defineStore('chats', () => {
  const chatsList = ref([])
  const chatInfo = ref(null)
  const chatConfig = ref(null)
  const loading = ref(false)
  const error = ref('')

  const recentBans = ref([])
  const totalBans = ref(0)
  const bansHasMore = ref(false)

  const fetchChats = async () => {
    loading.value = true
    error.value = ''
    try {
      const response = await api.get('/api/admin/chats')
      chatsList.value = response.data.chats || []
    } catch (err) {
      error.value = 'Failed to load chats: ' + getApiErrorMessage(err)
      throw err
    } finally {
      loading.value = false
    }
  }

  const fetchChatInfo = async (chatId, { limit = 5, offset = 0 } = {}) => {
    loading.value = true
    error.value = ''
    try {
      const response = await api.get(`/api/admin/chat/${chatId}/info`, {
        params: { limit, offset },
      })
      chatInfo.value = response.data
      recentBans.value = response.data.recentBans || []
      totalBans.value = response.data.totalRecentBans || 0
      bansHasMore.value = response.data.hasMore || false
    } catch (err) {
      error.value = 'Failed to load chat info: ' + getApiErrorMessage(err)
      throw err
    } finally {
      loading.value = false
    }
  }

  const fetchChatConfig = async (chatId) => {
    loading.value = true
    error.value = ''
    try {
      const response = await api.get(`/api/admin/chat/${chatId}/config`)
      chatConfig.value = response.data
    } catch (err) {
      error.value = 'Failed to load configuration: ' + getApiErrorMessage(err)
      throw err
    } finally {
      loading.value = false
    }
  }

  const saveChatConfig = async (chatId, data) => {
    try {
      await api.post(`/api/admin/chat/${chatId}/config`, data)
    } catch (err) {
      error.value = 'Failed to save config: ' + getApiErrorMessage(err)
      throw err
    }
  }

  const clearError = () => {
    error.value = ''
  }

  return {
    chatsList,
    chatInfo,
    chatConfig,
    loading,
    error,
    recentBans,
    totalBans,
    bansHasMore,
    fetchChats,
    fetchChatInfo,
    fetchChatConfig,
    saveChatConfig,
    clearError,
  }
})
