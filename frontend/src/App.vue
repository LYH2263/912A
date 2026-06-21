<template>
  <router-view />
</template>

<script setup>
</script>

<style>
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

:root {
  /* 全局主题色，偏向截图中的蓝紫色 */
  --el-color-primary: #4c6fff;
  --el-color-primary-light-3: #7c91ff;
  --el-color-primary-light-5: #a9b7ff;
  --el-color-primary-light-7: #d1ddff;
  --el-color-primary-light-9: #ecf2ff;

  --app-bg-start: #e7edff;
  --app-bg-end: #f6f8ff;
}

body {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
  background: linear-gradient(135deg, var(--app-bg-start) 0%, var(--app-bg-end) 40%, #ffffff 100%);
  color: #1f2933;
}

#app {
  min-height: 100vh;
}
</style>
