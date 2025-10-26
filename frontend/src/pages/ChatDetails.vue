<template>
  <div class="chat-details-container">
    <div class="chat-header">
      <button class="btn-back" @click="goBack" title="Go back to chats list">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M19 12H5M12 19l-7-7 7-7"/>
        </svg>
      </button>
      <div class="chat-title-section">
        <h1>{{ chatTitle }}</h1>
        <div class="chat-id">#{{ chatId }}</div>
      </div>
    </div>

    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
    </div>

    <div v-else-if="info" class="info-grid">
      <div class="info-card">
        <h3>Statistics</h3>
        <div class="stat-row">
          <span>Members:</span>
          <span>{{ info.membersCount }}</span>
        </div>
        <div class="stat-row">
          <span>Total Bans:</span>
          <span>{{ info.stats.totalBans }}</span>
        </div>
        <div class="stat-row">
          <span>Active Bans:</span>
          <span :style="{ color: info.stats.activeBans > 0 ? '#dc3545' : '#28a745' }">
            {{ info.stats.activeBans }}
          </span>
        </div>
        <div class="stat-row">
          <span>Total Votes:</span>
          <span>{{ info.stats.totalVotes }}</span>
        </div>
      </div>

      <div class="info-card">
        <h3>Recent Bans</h3>
        <div v-if="totalBans > 0">
          <table class="table">
            <thead>
              <tr>
                <th>Spammer</th>
                <th>Status</th>
                <th>Date</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="ban in recentBans" :key="ban.id">
                <td>#{{ ban.spammerId }}</td>
                <td><span class="badge" :class="'badge-' + getBanStatus(ban.status)">{{ ban.status }}</span></td>
                <td>{{ formatDate(ban.createdAt) }}</td>
              </tr>
            </tbody>
          </table>
          <div v-if="totalBans > bansPageSize" class="pagination">
            <button class="btn btn-secondary" @click="prevBansPage" :disabled="bansPage === 0">← Previous</button>
            <span class="pagination-info">Page {{ bansPage + 1 }} ({{ recentBans.length }} of {{ totalBans }})</span>
            <button class="btn btn-secondary" @click="nextBansPage" :disabled="!bansHasMore">Next →</button>
          </div>
        </div>
        <p v-else class="empty-text">No bans yet</p>
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
const info = ref(null)
const recentBans = ref([])
const loading = ref(false)

const bansPage = ref(0)
const bansPageSize = ref(5)
const totalBans = ref(0)
const bansHasMore = ref(false)

const chatTitle = computed(() => info.value?.title || 'Chat')

const loadInfo = async () => {
  loading.value = true
  try {
    const bansOffset = bansPage.value * bansPageSize.value
    const response = await api.get(`/api/admin/chat/${chatId}/info`, {
      params: {
        limit: bansPageSize.value,
        offset: bansOffset
      }
    })
    info.value = response.data
    recentBans.value = response.data.recentBans || []
    totalBans.value = response.data.totalRecentBans || 0
    bansHasMore.value = response.data.hasMore || false
  } catch (error) {
    console.error('Failed to load chat info:', error)
  } finally {
    loading.value = false
  }
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString()
}

const getBanStatus = (status) => {
  return status === 'active' ? 'danger' : 'secondary'
}

const nextBansPage = async () => {
  if (bansHasMore.value) {
    bansPage.value++
    await loadInfo()
  }
}

const prevBansPage = async () => {
  if (bansPage.value > 0) {
    bansPage.value--
    await loadInfo()
  }
}

const goBack = () => {
  router.push('/chats')
}

onMounted(() => loadInfo())
</script>

<style scoped>
.chat-header {
  display: flex;
  align-items: center;
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
}

.btn-back:hover {
  background-color: #f0f0f0;
  color: #333;
}

.btn-back svg {
  width: 20px;
  height: 20px;
}

.chat-title-section {
  display: flex;
  align-items: baseline;
  gap: 15px;
}

.chat-details-container h1 {
  margin: 0;
  font-size: 28px;
}

.chat-id {
  font-size: 16px;
  color: #666;
  font-weight: 500;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
  gap: 20px;
}

.info-card {
  background: white;
  border-radius: 8px;
  padding: 20px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.info-card h3 {
  margin-top: 0;
  margin-bottom: 15px;
  font-size: 18px;
}

.stat-row {
  display: flex;
  justify-content: space-between;
  padding: 10px 0;
  border-bottom: 1px solid #e9ecef;
  font-size: 14px;
}

.stat-row:last-child {
  border-bottom: none;
}

.empty-text {
  color: #999;
  text-align: center;
  padding: 20px 0;
}

.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 15px;
  margin-top: 15px;
  padding-top: 15px;
  border-top: 1px solid #e9ecef;
}

.pagination-info {
  color: #666;
  font-size: 14px;
  min-width: 100px;
  text-align: center;
}

.loading-state {
  display: flex;
  justify-content: center;
  padding: 40px 0;
}
</style>
