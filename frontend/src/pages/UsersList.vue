<template>
  <div class="users-wrapper">
    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
    </div>

    <div v-else-if="users.length === 0" class="empty-state">
      <p>No users found</p>
    </div>

    <div v-else class="users-content">
      <div class="users-header">
        <button class="btn-back" @click="goBack" title="Go back to chat details">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M19 12H5M12 19l-7-7 7-7"/>
          </svg>
        </button>
        <div class="header-left">
          <h1>Users in Chat</h1>
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
              <th>Username</th>
              <th>Messages</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="user in users" :key="user.id">
              <td>
                <strong>{{ user.username || user.name }}</strong>
                <br>
                <small>#{{ user.id }}</small>
              </td>
              <td>{{ user.messagesCount }}</td>
              <td>
                <span v-if="user.isBanned" class="badge badge-danger">Banned ({{ user.bansCount }})</span>
                <span v-else-if="user.isAdmin" class="badge badge-primary">Admin</span>
                <span v-else-if="user.isBot" class="badge badge-secondary">Bot</span>
                <span v-else class="badge badge-success">Active</span>
              </td>
              <td>
                <div class="action-buttons">
                  <router-link :to="`/chat/${chatId}/users/${user.id}`" class="btn btn-primary btn-small">
                    Details
                  </router-link>
                  <button v-if="user.isBanned" class="btn btn-success btn-small" @click="unbanUser(user.id)">
                    Unban
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>

        <div class="pagination">
          <button class="btn btn-secondary" @click="prevPage" :disabled="page === 0">← Previous</button>
          <span class="pagination-info">Page {{ page + 1 }} ({{ users.length }} of {{ totalUsers }})</span>
          <button class="btn btn-secondary" @click="nextPage" :disabled="!hasMore">Next →</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/utils/api'

const route = useRoute()
const router = useRouter()
const chatId = route.params.chatId
const users = ref([])
const chatTitle = ref('')
const loading = ref(false)

const page = ref(0)
const pageSize = ref(50)
const totalUsers = ref(0)
const hasMore = ref(false)

const loadUsers = async () => {
  loading.value = true
  try {
    const offset = page.value * pageSize.value
    const response = await api.get(`/api/admin/chat/${chatId}/users`, {
      params: {
        limit: pageSize.value,
        offset: offset
      }
    })
    users.value = response.data.users || []
    totalUsers.value = response.data.total
    hasMore.value = response.data.hasMore
    chatTitle.value = response.data.chatTitle || ''
  } catch (error) {
    console.error('Failed to load users:', error)
  } finally {
    loading.value = false
  }
}

const nextPage = async () => {
  if (hasMore.value) {
    page.value++
    await loadUsers()
  }
}

const prevPage = async () => {
  if (page.value > 0) {
    page.value--
    await loadUsers()
  }
}

const unbanUser = async (userId) => {
  if (!confirm('Are you sure you want to unban this user?')) return

  try {
    await api.post(`/api/admin/chat/${chatId}/users/${userId}/unban`)
    await loadUsers()
  } catch (error) {
    alert('Failed to unban user: ' + error.message)
  }
}

const goBack = () => {
  router.push(`/chat/${chatId}`)
}

onMounted(() => loadUsers())
</script>

<style scoped>
.users-wrapper {
  padding: 30px;
}

.users-header {
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

.action-buttons {
  display: flex;
  gap: 5px;
  flex-wrap: wrap;
}

.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 20px;
  margin-top: 30px;
  padding: 20px;
}

.pagination-info {
  color: #666;
  font-size: 14px;
  min-width: 150px;
  text-align: center;
}

.empty-state,
.loading-state {
  text-align: center;
  padding: 40px 20px;
}

@media (max-width: 768px) {
  .users-wrapper {
    padding: 15px;
  }

  .users-header {
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
    font-size: 13px;
  }

  .action-buttons {
    flex-direction: column;
  }

  .btn-small {
    padding: 4px 8px;
    font-size: 11px;
  }
}
</style>
