import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/utils/api'
import { getApiErrorMessage } from '@/utils/formatters'

export const useUsersStore = defineStore('users', () => {
  const usersList = ref([])
  const userDetails = ref(null)
  const chatTitle = ref('')
  const totalUsers = ref(0)
  const hasMore = ref(false)
  const loading = ref(false)
  const error = ref('')

  const fetchUsers = async (chatId, { limit = 50, offset = 0 } = {}) => {
    loading.value = true
    error.value = ''
    try {
      const response = await api.get(`/api/admin/chat/${chatId}/users`, {
        params: { limit, offset },
      })
      usersList.value = response.data.users || []
      totalUsers.value = response.data.total
      hasMore.value = response.data.hasMore
      chatTitle.value = response.data.chatTitle || ''
    } catch (err) {
      error.value = 'Failed to load users: ' + getApiErrorMessage(err)
      throw err
    } finally {
      loading.value = false
    }
  }

  const fetchUserDetails = async (chatId, userId) => {
    loading.value = true
    error.value = ''
    try {
      const response = await api.get(`/api/admin/chat/${chatId}/users/${userId}`)
      userDetails.value = response.data
    } catch (err) {
      error.value = 'Failed to load user details: ' + getApiErrorMessage(err)
      throw err
    } finally {
      loading.value = false
    }
  }

  const unbanUser = async (chatId, userId) => {
    error.value = ''
    try {
      await api.post(`/api/admin/chat/${chatId}/users/${userId}/unban`)
    } catch (err) {
      error.value = 'Failed to unban user: ' + getApiErrorMessage(err)
      throw err
    }
  }

  const clearError = () => {
    error.value = ''
  }

  return {
    usersList,
    userDetails,
    chatTitle,
    totalUsers,
    hasMore,
    loading,
    error,
    fetchUsers,
    fetchUserDetails,
    unbanUser,
    clearError,
  }
})
