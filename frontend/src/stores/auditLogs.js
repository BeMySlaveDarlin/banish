import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/utils/api'
import { getApiErrorMessage } from '@/utils/formatters'

export const useAuditLogsStore = defineStore('auditLogs', () => {
  const logs = ref([])
  const chatTitle = ref('')
  const totalLogs = ref(0)
  const hasMore = ref(false)
  const loading = ref(false)
  const error = ref('')

  const PAGE_SIZE = 20

  const fetchLogs = async (chatId, { limit = PAGE_SIZE, offset = 0 } = {}) => {
    loading.value = true
    error.value = ''
    try {
      const response = await api.get(`/api/admin/chat/${chatId}/audit-logs`, {
        params: { limit, offset },
      })
      logs.value = response.data.logs || []
      chatTitle.value = response.data.chatTitle || ''
      totalLogs.value = response.data.total || response.data.logs?.length || 0
      hasMore.value = response.data.hasMore || false
    } catch (err) {
      error.value = 'Failed to load audit logs: ' + getApiErrorMessage(err)
      throw err
    } finally {
      loading.value = false
    }
  }

  const clearError = () => {
    error.value = ''
  }

  return {
    logs,
    chatTitle,
    totalLogs,
    hasMore,
    loading,
    error,
    PAGE_SIZE,
    fetchLogs,
    clearError,
  }
})
