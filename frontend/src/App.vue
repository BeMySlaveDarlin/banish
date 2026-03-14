<template>
  <router-view :key="$route.fullPath" />
</template>

<script setup>
</script>

<style>
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

html {
  font-size: 16px;
}

body {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica', 'Arial', sans-serif;
  background-color: #f5f5f5;
  color: #333;
}

#app {
  width: 100%;
  min-height: 100vh;
}
</style>
