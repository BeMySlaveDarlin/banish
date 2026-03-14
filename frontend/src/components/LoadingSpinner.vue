<template>
  <div class="loading-state">
    <div class="spinner"></div>
    <p v-if="text">{{ text }}</p>
  </div>
</template>

<script setup>
defineProps({
  text: {
    type: String,
    default: '',
  },
})
</script>

<style scoped>
.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px 20px;
  gap: 15px;
}

.loading-state p {
  color: #777;
  font-size: 14px;
}
</style>
