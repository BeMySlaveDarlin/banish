<template>
  <div class="chats-container">
    <div class="page-header">
      <h1>My Chats</h1>
      <button class="btn btn-primary" @click="refreshChats">Refresh</button>
    </div>

    <div v-if="message" :class="['alert', 'alert-' + messageType]">
      {{ message }}
    </div>

    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <p>Loading chats...</p>
    </div>

    <div v-else-if="chats.length === 0" class="empty-state">
      <p>You don't have access to any chats yet.</p>
    </div>

    <div v-else class="chats-grid">
      <div v-for="chat in chats" :key="chat.id" class="chat-card">
        <div class="chat-header">
          <h2>{{ chat.title }}</h2>
          <span v-if="!chat.isEnabled" class="badge badge-warning">Disabled</span>
        </div>

        <div class="chat-stats">
          <div class="stat">
            <span class="stat-label">Members:</span>
            <span class="stat-value">{{ chat.membersCount }}</span>
          </div>
          <div class="stat">
            <span class="stat-label">Total Bans:</span>
            <span class="stat-value">{{ chat.stats.totalBans }}</span>
          </div>
          <div class="stat">
            <span class="stat-label">Active Bans:</span>
            <span class="stat-value" :style="{ color: chat.stats.activeBans > 0 ? '#dc3545' : '#28a745' }">
              {{ chat.stats.activeBans }}
            </span>
          </div>
        </div>

        <div class="chat-actions">
          <router-link :to="`/chat/${chat.id}`" class="btn btn-primary btn-small">
            View Details
          </router-link>
          <router-link :to="`/chat/${chat.id}/users`" class="btn btn-secondary btn-small">
            Manage Users
          </router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import {onMounted, ref} from 'vue'
import api from '@/utils/api'

console.log('📋 ChatsList.vue script setup executed')

const chats = ref([])
const loading = ref(false)
const message = ref('')
const messageType = ref('info')

const loadChats = async () => {
  console.log('📥 Loading chats...')
  loading.value = true
  message.value = ''

  try {
    console.log('🌐 Sending request to /api/admin/chats')
    const response = await api.get('/api/admin/chats')
    console.log('✅ Chats loaded:', response.data)
    chats.value = response.data.chats || []
    console.log('📊 Chats count:', chats.value.length)
  } catch (error) {
    console.error('❌ Failed to load chats:', error)
    console.error('Response data:', error.response?.data)
    message.value = 'Failed to load chats: ' + (error.response?.data?.error || error.message)
    messageType.value = 'danger'
  } finally {
    loading.value = false
  }
}

const refreshChats = async () => {
  console.log('🔄 Refreshing chats...')
  await loadChats()
  message.value = 'Chats refreshed!'
  messageType.value = 'success'
  setTimeout(() => {
    message.value = ''
  }, 3000)
}

onMounted(() => {
  console.log('📌 ChatsList.vue mounted')
  loadChats()
})
</script>

<style scoped>
.chats-container {
  max-width: 1200px;
  margin: 0 auto;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 30px;
  gap: 15px;
  flex-wrap: wrap;
}

.page-header h1 {
  font-size: 28px;
  margin: 0;
}

.chats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 20px;
}

.chat-card {
  background: white;
  border-radius: 8px;
  padding: 20px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  transition: all 0.3s ease;
  display: flex;
  flex-direction: column;
}

.chat-card:hover {
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
  transform: translateY(-2px);
}

.chat-header {
  display: flex;
  justify-content: space-between;
  align-items: start;
  margin-bottom: 15px;
  gap: 10px;
}

.chat-header h2 {
  font-size: 18px;
  margin: 0;
  flex: 1;
}

.chat-stats {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 10px;
  margin-bottom: 15px;
  padding: 15px 0;
  border-top: 1px solid #e9ecef;
  border-bottom: 1px solid #e9ecef;
}

.stat {
  text-align: center;
}

.stat-label {
  display: block;
  font-size: 12px;
  color: #888;
  margin-bottom: 5px;
}

.stat-value {
  display: block;
  font-size: 20px;
  font-weight: 700;
  color: #333;
}

.chat-actions {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
  margin-top: auto;
}

.chat-actions .btn {
  flex: 1;
  min-width: 120px;
}

.loading-state,
.empty-state {
  text-align: center;
  padding: 60px 20px;
}

.loading-state p,
.empty-state p {
  color: #777;
  font-size: 16px;
  margin-top: 15px;
}

@media (max-width: 768px) {
  .page-header {
    flex-direction: column;
    align-items: stretch;
  }

  .page-header h1 {
    margin-bottom: 10px;
  }

  .page-header button {
    width: 100%;
  }

  .chats-grid {
    grid-template-columns: 1fr;
  }
}
</style>
