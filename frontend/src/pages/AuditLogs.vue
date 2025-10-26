<template>
  <div class="logs-wrapper">
    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
    </div>

    <div v-else-if="logs.length === 0" class="empty-state">
      <p>No logs found</p>
    </div>

    <div v-else class="logs-content">
      <div class="logs-header">
        <button class="btn-back" @click="goBack" title="Go back to chat details">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M19 12H5M12 19l-7-7 7-7"/>
          </svg>
        </button>
        <div class="header-left">
          <h1>Audit Logs</h1>
          <div class="chat-context">
            <span class="chat-id">Chat #{{ chatId }}</span>
            <span v-if="chatTitle" class="chat-title">— {{ chatTitle }}</span>
          </div>
        </div>
      </div>

      <div class="table-responsive">
        <table class="table table-striped">
          <thead>
            <tr>
              <th>Time</th>
              <th>User</th>
              <th>Action</th>
              <th>Description</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="log in paginatedLogs" :key="log.id">
              <td><small>{{ formatDate(log.createdAt) }}</small></td>
              <td><small>#{{ log.userId }}</small></td>
              <td>
                <span class="badge" :class="'badge-' + getActionColor(log.actionType)">
                  {{ formatAction(log.actionType) }}
                </span>
              </td>
              <td><small>{{ log.description || '-' }}</small></td>
            </tr>
          </tbody>
        </table>

        <div v-if="totalLogs > pageSize" class="pagination">
          <button class="btn btn-secondary" @click="prevPage" :disabled="page === 0">← Previous</button>
          <span class="pagination-info">Page {{ page + 1 }} of {{ totalPages }} ({{ paginatedLogs.length }} of {{ totalLogs }})</span>
          <button class="btn btn-secondary" @click="nextPage" :disabled="page >= totalPages - 1">Next →</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/utils/api'

const route = useRoute()
const router = useRouter()
const chatId = route.params.chatId
const logs = ref([])
const chatTitle = ref('')
const loading = ref(false)

const page = ref(0)
const pageSize = ref(20)

const totalLogs = computed(() => logs.value.length)

const totalPages = computed(() => Math.ceil(totalLogs.value / pageSize.value))

const paginatedLogs = computed(() => {
  const start = page.value * pageSize.value
  const end = start + pageSize.value
  return logs.value.slice(start, end)
})

const loadLogs = async () => {
  loading.value = true
  try {
    const response = await api.get(`/api/admin/chat/${chatId}/audit-logs?limit=500`)
    logs.value = response.data.logs || []
    chatTitle.value = response.data.chatTitle || ''
    page.value = 0
  } catch (error) {
    console.error('Failed to load logs:', error)
  } finally {
    loading.value = false
  }
}

const nextPage = () => {
  if (page.value < totalPages.value - 1) {
    page.value++
  }
}

const prevPage = () => {
  if (page.value > 0) {
    page.value--
  }
}

const goBack = () => {
  router.push(`/chat/${chatId}`)
}

const formatDate = (dateString) => {
  const date = new Date(dateString)
  return date.toLocaleDateString() + ' ' + date.toLocaleTimeString()
}

const formatAction = (action) => {
  const actions = {
    'auth_login': 'Login',
    'auth_logout': 'Logout',
    'config_update': 'Config Update',
    'user_list_view': 'View Users',
    'user_details_view': 'View User',
    'unban_user': 'Unban'
  }
  return actions[action] || action
}

const getActionColor = (action) => {
  const colors = {
    'auth_login': 'success',
    'auth_logout': 'secondary',
    'config_update': 'info',
    'unban_user': 'success',
    'user_list_view': 'primary',
    'user_details_view': 'primary'
  }
  return colors[action] || 'secondary'
}

onMounted(() => loadLogs())
</script>

<style scoped>
.logs-wrapper {
  padding: 30px;
}

.logs-header {
  display: flex;
  align-items: flex-start;
  gap: 15px;
  margin-bottom: 30px;
  padding-bottom: 20px;
  border-bottom: 2px solid #e9ecef;
}

.btn-back {
  background: none;
  border: none;
  cursor: pointer;
  color: #666;
  padding: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 6px;
  transition: all 0.3s ease;
  flex-shrink: 0;
  margin-top: 4px;
}

.btn-back:hover {
  background-color: #f0f0f0;
  color: #333;
}

.btn-back svg {
  width: 20px;
  height: 20px;
}

.header-left {
  flex: 1;
}

.header-left h1 {
  margin: 0 0 8px 0;
  font-size: 28px;
  color: #333;
}

.chat-context {
  font-size: 14px;
  color: #666;
}

.chat-id {
  font-weight: 600;
}

.chat-title {
  margin-left: 5px;
  color: #999;
}

.table-responsive {
  overflow-x: auto;
}

.empty-state,
.loading-state {
  text-align: center;
  padding: 40px 20px;
}

.table small {
  color: #666;
}

.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 20px;
  margin-top: 30px;
  padding: 20px;
  border-top: 1px solid #e9ecef;
}

.pagination-info {
  color: #666;
  font-size: 14px;
  min-width: 180px;
  text-align: center;
}

@media (max-width: 768px) {
  .logs-wrapper {
    padding: 15px;
  }

  .logs-header {
    padding-bottom: 15px;
    margin-bottom: 20px;
  }

  .header-left h1 {
    font-size: 22px;
  }

  .chat-context {
    font-size: 12px;
  }

  .table {
    font-size: 12px;
  }

  .table th,
  .table td {
    padding: 8px 6px;
  }
}
</style>
