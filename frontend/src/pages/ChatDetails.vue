<template>
  <div class="chat-details-container">
    <PageHeader
      :title="chatTitle"
      :subtitle="`#${chatId}`"
      show-back
      back-title="Go back to chats list"
      @back="goBack"
    />

    <ErrorAlert :message="chatsStore.error" @dismiss="chatsStore.clearError()" />

    <LoadingSpinner v-if="chatsStore.loading" />

    <div v-else-if="chatsStore.chatInfo" class="info-grid">
      <div class="info-card">
        <h3>Statistics</h3>
        <div class="stat-row">
          <span>Members:</span>
          <span>{{ chatsStore.chatInfo.membersCount }}</span>
        </div>
        <div class="stat-row">
          <span>Total Bans:</span>
          <span>{{ chatsStore.chatInfo.stats.totalBans }}</span>
        </div>
        <div class="stat-row">
          <span>Active Bans:</span>
          <span :style="{ color: chatsStore.chatInfo.stats.activeBans > 0 ? '#dc3545' : '#28a745' }">
            {{ chatsStore.chatInfo.stats.activeBans }}
          </span>
        </div>
        <div class="stat-row">
          <span>Total Votes:</span>
          <span>{{ chatsStore.chatInfo.stats.totalVotes }}</span>
        </div>
      </div>

      <div class="info-card">
        <h3>Recent Bans</h3>
        <div v-if="chatsStore.totalBans > 0">
          <table class="table">
            <thead>
              <tr>
                <th>Spammer</th>
                <th>Status</th>
                <th>Date</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="ban in chatsStore.recentBans" :key="ban.id">
                <td>#{{ ban.spammerId }}</td>
                <td><span class="badge" :class="'badge-' + getBanStatusClass(ban.status)">{{ ban.status }}</span></td>
                <td>{{ formatDate(ban.createdAt) }}</td>
              </tr>
            </tbody>
          </table>
          <Pagination
            :current-page="bansPage"
            :total-pages="bansTotalPages"
            :total-items="chatsStore.totalBans"
            :has-more="chatsStore.bansHasMore"
            @prev="prevBansPage"
            @next="nextBansPage"
          />
        </div>
        <p v-else class="empty-text">No bans yet</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useChatsStore } from '@/stores/chats'
import { formatDate, getBanStatusClass } from '@/utils/formatters'
import PageHeader from '@/components/PageHeader.vue'
import LoadingSpinner from '@/components/LoadingSpinner.vue'
import ErrorAlert from '@/components/ErrorAlert.vue'
import Pagination from '@/components/Pagination.vue'

const route = useRoute()
const router = useRouter()
const chatsStore = useChatsStore()

const bansPage = ref(0)
const bansPageSize = 5

const chatId = computed(() => route.params.chatId)
const chatTitle = computed(() => chatsStore.chatInfo?.title || 'Chat')
const bansTotalPages = computed(() => Math.ceil(chatsStore.totalBans / bansPageSize))

const loadInfo = async () => {
  const offset = bansPage.value * bansPageSize
  try {
    await chatsStore.fetchChatInfo(chatId.value, { limit: bansPageSize, offset })
  } catch {
    // error handled in store
  }
}

const nextBansPage = async () => {
  if (chatsStore.bansHasMore) {
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

watch(() => route.params.chatId, () => {
  bansPage.value = 0
  loadInfo()
}, { immediate: true })
</script>

<style scoped>
.chat-details-container {
  max-width: 1200px;
  margin: 0 auto;
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
</style>
