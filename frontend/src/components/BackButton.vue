<template>
  <button class="btn-back" :title="title" @click="$emit('click')">
    <svg
      width="20"
      height="20"
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      stroke-width="2"
    >
      <path d="M19 12H5M12 19l-7-7 7-7" />
    </svg>
  </button>
</template>

<script setup>
defineProps({
  title: {
    type: String,
    default: 'Go back',
  },
})

defineEmits(['click'])
</script>

<style scoped>
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
</style>
