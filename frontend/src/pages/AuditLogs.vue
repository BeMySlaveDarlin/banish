<template>
  <div class="logs-wrapper">
    <LoadingSpinner v-if="auditLogsStore.loading && auditLogsStore.logs.length === 0" />

    <div v-else class="logs-content">
      <PageHeader
        title="Audit Logs"
        :subtitle="`Chat #${chatId}${auditLogsStore.chatTitle ? ' -- ' + auditLogsStore.chatTitle : ''}`"
        show-back
        back-title="Go back to chat details"
        @back="goBack"
      />

      <ErrorAlert :message="auditLogsStore.error" @dismiss="auditLogsStore.clearError()" />

      <div v-if="auditLogsStore.logs.length === 0" class="empty-state">
        <p>No logs found</p>
      </div>

      <div v-else class="table-responsive">
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
            <tr v-for="log in auditLogsStore.logs" :key="log.id">
              <td><small>{{ formatDateTime(log.createdAt) }}</small></td>
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

        <Pagination
          :current-page="page"
          :total-pages="totalPages"
          :total-items="auditLogsStore.totalLogs"
          :has-more="auditLogsStore.hasMore"
          @prev="prevPage"
          @next="nextPage"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuditLogsStore } from '@/stores/auditLogs'
import { formatDateTime, formatAction, getActionColor } from '@/utils/formatters'
import PageHeader from '@/components/PageHeader.vue'
import LoadingSpinner from '@/components/LoadingSpinner.vue'
import ErrorAlert from '@/components/ErrorAlert.vue'
import Pagination from '@/components/Pagination.vue'

const route = useRoute()
const router = useRouter()
const auditLogsStore = useAuditLogsStore()

const page = ref(0)

const chatId = computed(() => route.params.chatId)
const totalPages = computed(() => Math.ceil(auditLogsStore.totalLogs / auditLogsStore.PAGE_SIZE))

const loadLogs = async () => {
  const offset = page.value * auditLogsStore.PAGE_SIZE
  try {
    await auditLogsStore.fetchLogs(chatId.value, {
      limit: auditLogsStore.PAGE_SIZE,
      offset,
    })
  } catch {
    // error handled in store
  }
}

const nextPage = async () => {
  if (auditLogsStore.hasMore) {
    page.value++
    await loadLogs()
  }
}

const prevPage = async () => {
  if (page.value > 0) {
    page.value--
    await loadLogs()
  }
}

const goBack = () => {
  router.push(`/chat/${chatId.value}`)
}

watch(() => route.params.chatId, () => {
  page.value = 0
  loadLogs()
}, { immediate: true })
</script>

<style scoped>
.logs-wrapper {
  max-width: 1200px;
  margin: 0 auto;
}

.table-responsive {
  overflow-x: auto;
}

.empty-state {
  text-align: center;
  padding: 40px 20px;
}

.table small {
  color: #666;
}

@media (max-width: 768px) {
  .table {
    font-size: 12px;
  }

  .table th,
  .table td {
    padding: 8px 6px;
  }
}
</style>
