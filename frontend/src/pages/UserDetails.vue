<template>
  <div class="user-details-container">
    <h1>User Details</h1>

    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
    </div>

    <div v-else-if="user" class="details-grid">
      <div class="card">
        <div class="card-header">User Info</div>
        <div class="card-body">
          <p><strong>ID:</strong> {{ user.id }}</p>
          <p><strong>Username:</strong> {{ user.username || 'N/A' }}</p>
          <p><strong>Name:</strong> {{ user.name || 'N/A' }}</p>
          <p><strong>Messages:</strong> {{ user.messagesCount }}</p>
          <p><strong>Trusted Count:</strong> {{ user.trustedCount }}</p>
          <p v-if="user.isAdmin" class="badge badge-primary">Admin</p>
          <p v-if="user.isBot" class="badge badge-secondary">Bot</p>
        </div>
      </div>

      <div class="card">
        <div class="card-header">Ban History</div>
        <div class="card-body">
          <p v-if="user.bans.length === 0">No bans</p>
          <div v-else class="bans-list">
            <div v-for="ban in user.bans" :key="ban.id" class="ban-item">
              <span class="badge" :class="'badge-' + getBanStatus(ban.status)">{{ ban.status }}</span>
              <span class="text-muted">{{ formatDate(ban.createdAt) }}</span>
              <span>Votes: {{ ban.votesFor }}/{{ ban.votesAgainst }}</span>
            </div>
          </div>
        </div>
      </div>

      <div v-if="user.isBanned" class="card full-width">
        <button class="btn btn-success" @click="unban">Unban User</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import {onMounted, ref} from 'vue'
import {useRoute} from 'vue-router'
import api from '@/utils/api'

const route = useRoute()
const chatId = route.params.chatId
const userId = route.params.userId
const user = ref(null)
const loading = ref(false)

const loadUser = async () => {
  loading.value = true
  try {
    const response = await api.get(`/api/admin/chat/${chatId}/users/${userId}`)
    user.value = response.data
  } catch (error) {
    console.error('Failed to load user:', error)
  } finally {
    loading.value = false
  }
}

const unban = async () => {
  if (!confirm('Unban this user?')) return
  try {
    await api.post(`/api/admin/chat/${chatId}/users/${userId}/unban`)
    await loadUser()
  } catch (error) {
    alert('Failed to unban: ' + error.message)
  }
}

const formatDate = (dateString) => new Date(dateString).toLocaleDateString()
const getBanStatus = (status) => status === 'active' ? 'danger' : 'secondary'

onMounted(() => loadUser())
</script>

<style scoped>
.user-details-container h1 {
  margin-bottom: 20px;
}

.details-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 20px;
}

.card {
  background: white;
  border-radius: 8px;
  padding: 20px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.card.full-width {
  grid-column: 1 / -1;
}

.card-header {
  font-weight: 700;
  margin-bottom: 15px;
  padding-bottom: 10px;
  border-bottom: 1px solid #e9ecef;
}

.card-body p {
  margin: 10px 0;
  font-size: 14px;
}

.bans-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.ban-item {
  display: flex;
  gap: 10px;
  padding: 10px;
  background: #f8f9fa;
  border-radius: 4px;
  font-size: 13px;
}

.text-muted {
  color: #999;
}
</style>
