<template>
  <el-container class="layout-container">
    <el-aside width="240px" class="sidebar">
      <div class="logo">
        <div class="logo-icon">GM</div>
        <div class="logo-text">
          <div class="logo-title">商品管理</div>
          <div class="logo-subtitle">Ecommerce Console</div>
        </div>
      </div>
      <div class="sidebar-body">
        <el-menu
          :default-active="activeMenu"
          router
          class="sidebar-menu"
        >
          <el-menu-item index="/dashboard">
            <el-icon><DataBoard /></el-icon>
            <span>仪表盘</span>
          </el-menu-item>
          <el-menu-item index="/products">
            <el-icon><Goods /></el-icon>
            <span>商品管理</span>
          </el-menu-item>
          <el-menu-item index="/orders">
            <el-icon><Document /></el-icon>
            <span>订单管理</span>
          </el-menu-item>
          <el-menu-item index="/inventory">
            <el-icon><Box /></el-icon>
            <span>库存管理</span>
          </el-menu-item>
        </el-menu>
      </div>
      <div
        class="sidebar-footer"
        @mouseenter="showLogout = true"
        @mouseleave="showLogout = false"
      >
        <div class="sidebar-user">
          <div class="sidebar-user-main">
            <div class="sidebar-avatar">{{ avatarInitial }}</div>
            <div class="sidebar-user-text">
              <div class="sidebar-user-name">{{ displayName }}</div>
              <div class="sidebar-user-role">管理员</div>
            </div>
          </div>
          <transition name="fade-slide">
            <el-button
              v-if="showLogout"
              class="sidebar-logout-btn"
              size="small"
              type="primary"
              plain
              round
              @click="handleLogout"
            >
              退出登录
            </el-button>
          </transition>
        </div>
      </div>
    </el-aside>
    <el-container>
      <el-header class="header">
        <div class="header-left">
          <span class="header-breadcrumb">控制台</span>
        </div>
      </el-header>
      <el-main class="main-content">
        <router-view />
      </el-main>
    </el-container>
  </el-container>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { DataBoard, Goods, Document, Box } from '@element-plus/icons-vue'

const route = useRoute()
const router = useRouter()

const activeMenu = computed(() => route.path)

const currentUser = ref(null)
const showLogout = ref(false)

const displayName = computed(() => {
  if (currentUser.value?.email) return currentUser.value.email
  if (currentUser.value?.name) return currentUser.value.name
  return 'Admin User'
})

const avatarInitial = computed(() => {
  const source =
    (currentUser.value?.name || currentUser.value?.email || 'A').trim()
  return source.charAt(0).toUpperCase()
})

onMounted(() => {
  try {
    const raw = localStorage.getItem('user')
    currentUser.value = raw ? JSON.parse(raw) : null
  } catch {
    currentUser.value = null
  }
})

const handleLogout = async () => {
  try {
    const { authApi } = await import('@/api/modules/auth')
    await authApi.logout()
  } catch (error) {
    console.error('登出失败', error)
  } finally {
    localStorage.removeItem('token')
    localStorage.removeItem('user')
    router.push('/login')
  }
}
</script>

<style scoped>
.layout-container {
  height: 100vh;
  background: transparent;
}

.sidebar {
  background: linear-gradient(180deg, #f4f6ff 0%, #eef2ff 40%, #e9efff 100%);
  color: #4b5563;
  display: flex;
  flex-direction: column;
  border-right: 1px solid rgba(191, 219, 254, 0.9);
  box-shadow: 6px 0 26px rgba(148, 163, 184, 0.45);
}

.logo {
  height: 72px;
  display: flex;
  align-items: center;
  padding: 0 20px;
  gap: 12px;
  background: linear-gradient(135deg, #4f46e5, #6366f1);
  letter-spacing: 0.04em;
  border-bottom: 1px solid rgba(255, 255, 255, 0.06);
}

.sidebar-body {
  flex: 1;
  display: flex;
  flex-direction: column;
  padding-top: 10px;
}

.logo-icon {
  width: 40px;
  height: 40px;
  border-radius: 14px;
  background: rgba(255, 255, 255, 0.15);
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 16px;
  color: #f9fafb;
}

.logo-text {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.logo-title {
  font-size: 17px;
  font-weight: 600;
  color: #f9fafb;
}

.logo-subtitle {
  font-size: 11px;
  color: #e5e7eb;
}

.sidebar-menu {
  border: none;
  background-color: transparent;
}

.sidebar-menu .el-menu-item {
  color: #6b7280;
  border-radius: 6px;
  margin-inline: 12px;
  margin-block: 4px;
  height: 44px;
  line-height: 44px;
}

.sidebar-menu .el-menu-item:hover {
  background-color: rgba(255, 255, 255, 0.9);
}

.sidebar-menu .el-menu-item.is-active {
  background: linear-gradient(135deg, #4c6fff, #7c91ff);
  color: #ffffff;
  box-shadow: 0 10px 24px rgba(148, 163, 184, 0.65);
}

.sidebar-footer {
  padding: 16px 16px 20px;
}

.sidebar-user {
  display: flex;
  flex-direction: column;
  gap: 8px;
  padding: 10px 12px 12px;
  border-radius: 18px;
  background-color: #ffffff;
  box-shadow: 0 10px 22px rgba(148, 163, 184, 0.45);
}

.sidebar-user-main {
  display: flex;
  align-items: center;
  gap: 10px;
}

.sidebar-avatar {
  width: 32px;
  height: 32px;
  border-radius: 999px;
  background: linear-gradient(135deg, #4c6fff, #7c91ff);
  display: flex;
  align-items: center;
  justify-content: center;
  color: #ffffff;
  font-weight: 600;
  font-size: 14px;
}

.sidebar-user-text {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.sidebar-user-name {
  font-size: 13px;
  font-weight: 600;
  color: #111827;
}

.sidebar-user-role {
  font-size: 11px;
  color: #9ca3af;
}

.sidebar-logout-btn {
  align-self: flex-end;
  padding: 4px 12px;
  font-size: 12px;
}

.fade-slide-enter-active,
.fade-slide-leave-active {
  transition: all 0.18s ease-out;
}

.fade-slide-enter-from,
.fade-slide-leave-to {
  opacity: 0;
  transform: translateY(4px);
}

.header {
  background-color: #fff;
  border-bottom: 1px solid #e4e7ed;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 24px;
  backdrop-filter: blur(10px);
}

.header-left {
  font-size: 14px;
  color: #6b7280;
}

.header-breadcrumb {
  padding: 6px 10px;
  border-radius: 999px;
  background-color: #f3f4ff;
  color: #4f46e5;
  font-weight: 500;
}

.header-right {
  display: flex;
  align-items: center;
}

.user-info {
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 8px;
}

.main-content {
  background-color: transparent;
  padding: 24px;
  display: flex;
  flex-direction: column;
}

.main-content :deep(> .el-card),
.main-content :deep(> .page-shell) {
  border-radius: 20px;
  box-shadow: 0 20px 40px rgba(148, 163, 184, 0.45);
  border: 1px solid rgba(148, 163, 184, 0.22);
  background: rgba(255, 255, 255, 0.96);
}
</style>
