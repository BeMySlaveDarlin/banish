<template>
  <div class="chats-container">
    <PageHeader title="My Chats">
      <template #actions>
        <button class="btn btn-primary" @click="refreshChats">Refresh</button>
      </template>
    </PageHeader>

    <ErrorAlert :message="chatsStore.error" @dismiss="chatsStore.clearError()" />

    <div v-if="successMessage" class="alert alert-success">
      {{ successMessage }}
    </div>

    <LoadingSpinner v-if="chatsStore.loading" text="Loading chats..." />

    <div v-else-if="chatsStore.chatsList.length === 0" class="empty-state">
      <p>You don't have access to any chats yet.</p>
    </div>

    <div v-else class="chats-grid">
      <div v-for="chat in chatsStore.chatsList" :key="chat.id" class="chat-card">
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
import { ref, onMounted } from 'vue'
import { useChatsStore } from '@/stores/chats'
import PageHeader from '@/components/PageHeader.vue'
import LoadingSpinner from '@/components/LoadingSpinner.vue'
import ErrorAlert from '@/components/ErrorAlert.vue'

const chatsStore = useChatsStore()
const successMessage = ref('')

const loadChats = async () => {
  try {
    await chatsStore.fetchChats()
  } catch {
    // error is handled in store
  }
}

const refreshChats = async () => {
  await loadChats()
  if (!chatsStore.error) {
    successMessage.value = 'Chats refreshed!'
    setTimeout(() => {
      successMessage.value = ''
    }, 3000)
  }
}

onMounted(() => loadChats())
</script>

<style scoped>
.chats-container {
  max-width: 1200px;
  margin: 0 auto;
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

.empty-state {
  text-align: center;
  padding: 60px 20px;
}

.empty-state p {
  color: #777;
  font-size: 16px;
  margin-top: 15px;
}

@media (max-width: 768px) {
  .chats-grid {
    grid-template-columns: 1fr;
  }
}
</style>
