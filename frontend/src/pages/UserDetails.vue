<template>
  <div class="user-details-container">
    <PageHeader
      title="User Details"
      show-back
      back-title="Go back to users list"
      @back="goBack"
    />

    <ErrorAlert :message="usersStore.error" @dismiss="usersStore.clearError()" />

    <LoadingSpinner v-if="usersStore.loading" />

    <ConfirmDialog
      :visible="showConfirmUnban"
      title="Confirm Unban"
      message="Are you sure you want to unban this user?"
      confirm-text="Unban"
      confirm-variant="success"
      @confirm="performUnban"
      @cancel="showConfirmUnban = false"
    />

    <div v-if="usersStore.userDetails" class="details-grid">
      <div class="card">
        <div class="card-header">User Info</div>
        <div class="card-body">
          <p><strong>ID:</strong> {{ usersStore.userDetails.id }}</p>
          <p><strong>Username:</strong> {{ usersStore.userDetails.username || 'N/A' }}</p>
          <p><strong>Name:</strong> {{ usersStore.userDetails.name || 'N/A' }}</p>
          <p><strong>Messages:</strong> {{ usersStore.userDetails.messagesCount }}</p>
          <p><strong>Trusted Count:</strong> {{ usersStore.userDetails.trustedCount }}</p>
          <p v-if="usersStore.userDetails.isAdmin" class="badge badge-primary">Admin</p>
          <p v-if="usersStore.userDetails.isBot" class="badge badge-secondary">Bot</p>
        </div>
      </div>

      <div class="card">
        <div class="card-header">Ban History</div>
        <div class="card-body">
          <p v-if="usersStore.userDetails.bans.length === 0">No bans</p>
          <div v-else class="bans-list">
            <div v-for="ban in usersStore.userDetails.bans" :key="ban.id" class="ban-item">
              <span class="badge" :class="'badge-' + getBanStatusClass(ban.status)">{{ ban.status }}</span>
              <span class="text-muted">{{ formatDate(ban.createdAt) }}</span>
              <span>Votes: {{ ban.votesFor }}/{{ ban.votesAgainst }}</span>
            </div>
          </div>
        </div>
      </div>

      <div v-if="usersStore.userDetails.isBanned" class="card full-width">
        <button class="btn btn-success" @click="showConfirmUnban = true">Unban User</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useUsersStore } from '@/stores/users'
import { formatDate, getBanStatusClass } from '@/utils/formatters'
import PageHeader from '@/components/PageHeader.vue'
import LoadingSpinner from '@/components/LoadingSpinner.vue'
import ErrorAlert from '@/components/ErrorAlert.vue'
import ConfirmDialog from '@/components/ConfirmDialog.vue'

const route = useRoute()
const router = useRouter()
const usersStore = useUsersStore()

const showConfirmUnban = ref(false)

const chatId = computed(() => route.params.chatId)
const userId = computed(() => route.params.userId)

const loadUser = async () => {
  try {
    await usersStore.fetchUserDetails(chatId.value, userId.value)
  } catch {
    // error handled in store
  }
}

const performUnban = async () => {
  showConfirmUnban.value = false
  try {
    await usersStore.unbanUser(chatId.value, userId.value)
    await loadUser()
  } catch {
    // error handled in store
  }
}

const goBack = () => {
  router.push(`/chat/${chatId.value}/users`)
}

watch(
  () => [route.params.chatId, route.params.userId],
  () => loadUser(),
  { immediate: true },
)
</script>

<style scoped>
.user-details-container {
  max-width: 1200px;
  margin: 0 auto;
}

.details-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 20px;
}

.card.full-width {
  grid-column: 1 / -1;
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
