<template>
  <div v-if="totalPages > 1" class="pagination">
    <button class="btn btn-secondary" :disabled="currentPage === 0" @click="$emit('prev')">
      &larr; Previous
    </button>
    <span class="pagination-info">
      Page {{ currentPage + 1 }} of {{ totalPages }}
      <template v-if="totalItems"> ({{ totalItems }} total)</template>
    </span>
    <button class="btn btn-secondary" :disabled="!hasMore" @click="$emit('next')">
      Next &rarr;
    </button>
  </div>
</template>

<script setup>
defineProps({
  currentPage: {
    type: Number,
    required: true,
  },
  totalPages: {
    type: Number,
    default: 1,
  },
  totalItems: {
    type: Number,
    default: 0,
  },
  hasMore: {
    type: Boolean,
    default: false,
  },
})

defineEmits(['prev', 'next'])
</script>

<style scoped>
.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 15px;
  margin-top: 20px;
  padding-top: 15px;
  border-top: 1px solid #e9ecef;
}

.pagination-info {
  color: #666;
  font-size: 14px;
  min-width: 140px;
  text-align: center;
}
</style>
