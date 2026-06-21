<template>
  <div class="dashboard page-shell">
    <div class="dashboard-header">
      <div>
        <h2 class="page-title">仪表盘总览</h2>
        <p class="page-subtitle">一眼掌握商品、订单与库存的核心数据</p>
      </div>
    </div>

    <el-row :gutter="20" class="stats-row">
      <el-col :span="8" v-for="stat in stats" :key="stat.title">
        <el-card class="stat-card" :style="{ '--accent-color': stat.color }">
          <div class="stat-content">
            <div class="stat-text">
              <div class="stat-label">{{ stat.title }}</div>
            <div class="stat-value">{{ stat.value }}</div>
          </div>
            <div class="stat-icon">
            <el-icon :size="40"><component :is="stat.icon" /></el-icon>
            </div>
          </div>
        </el-card>
      </el-col>
    </el-row>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { Goods, Document, Box } from '@element-plus/icons-vue'
import { dashboardApi } from '@/api/modules/dashboard'

const stats = ref([
  { title: '商品总数', value: 0, icon: Goods, color: '#409EFF' },
  { title: '今日订单', value: 0, icon: Document, color: '#67C23A' },
  { title: '库存总价值', value: 0, icon: Box, color: '#E6A23C' },
])

onMounted(async () => {
  try {
    const res = await dashboardApi.getSummary()
    const data = res.data
    
    stats.value[0].value = data.products.total
    stats.value[1].value = data.orders.today_count
    const totalValue = Number(data.inventory?.total_value ?? 0)
    stats.value[2].value = `¥${totalValue.toFixed(2)}`
  } catch (error) {
    console.error('获取数据失败', error)
  }
})
</script>

<style scoped>
.dashboard {
  padding: 24px 24px 20px;
}

.dashboard-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  margin-bottom: 20px;
}

.page-title {
  font-size: 22px;
  font-weight: 600;
  color: #1f2933;
  letter-spacing: 0.02em;
}

.page-subtitle {
  margin-top: 4px;
  font-size: 13px;
  color: #6b7280;
}

.stats-row {
  margin-top: 4px;
}

.stat-card {
  position: relative;
  margin-bottom: 20px;
  border-radius: 16px;
  border: none;
  background: radial-gradient(circle at top left, rgba(255, 255, 255, 0.96) 0%, #f5f7ff 40%, #eef3ff 100%);
  box-shadow: 0 18px 40px rgba(15, 23, 42, 0.18);
  overflow: hidden;
  transition: transform 0.18s ease-out, box-shadow 0.18s ease-out;
}

.stat-card::before {
  content: '';
  position: absolute;
  inset: 0;
  border-radius: inherit;
  border-top: 3px solid var(--accent-color);
  opacity: 0.9;
}

.stat-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 24px 55px rgba(15, 23, 42, 0.22);
}

.stat-content {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 18px 20px 16px;
}

.stat-text {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.stat-label {
  font-size: 13px;
  color: #6b7280;
}

.stat-value {
  font-size: 30px;
  font-weight: 700;
  color: #111827;
}

.stat-icon {
  color: var(--accent-color);
  opacity: 0.26;
}
</style>
