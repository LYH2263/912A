<template>
  <div class="dashboard page-shell">
    <div class="dashboard-header">
      <div>
        <h2 class="page-title">仪表盘总览</h2>
        <p class="page-subtitle">一眼掌握商品、订单与库存的核心数据</p>
      </div>
    </div>

    <el-row :gutter="20" class="stats-row">
      <el-col :span="cardSpan" v-for="stat in allStats" :key="stat.title">
        <el-card
          class="stat-card"
          :style="{ '--accent-color': stat.color }"
          @click="stat.clickHandler && stat.clickHandler()"
          :class="{ clickable: !!stat.clickHandler }"
        >
          <div class="stat-content">
            <div class="stat-text">
              <div class="stat-label">{{ stat.title }}</div>
              <div
                class="stat-value"
                :class="{ 'alert-value': stat.isAlert && stat.rawValue > 0 }"
              >
                {{ stat.value }}
              </div>
              <div v-if="stat.sub" class="stat-sub">{{ stat.sub }}</div>
            </div>
            <div class="stat-icon">
              <el-icon :size="40"><component :is="stat.icon" /></el-icon>
            </div>
          </div>
        </el-card>
      </el-col>
    </el-row>

    <el-row :gutter="20" v-if="showBatchCards" class="stats-row batch-row">
      <el-col :span="6">
        <el-card
          class="batch-card"
          style="--accent-color: #dc2626"
          @click="goToExpiryAlerts"
        >
          <div class="batch-card-content">
            <div class="batch-icon-box expired">
              <el-icon :size="28"><CircleClose /></el-icon>
            </div>
            <div class="batch-text">
              <div class="batch-label">已过期批次</div>
              <div class="batch-value">{{ batchSummary.expired_count || 0 }}</div>
              <div class="batch-sub">数量: {{ batchSummary.expired_quantity || 0 }}</div>
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card
          class="batch-card"
          style="--accent-color: #ef4444"
          @click="goToExpiryAlerts"
        >
          <div class="batch-card-content">
            <div class="batch-icon-box danger">
              <el-icon :size="28"><Warning /></el-icon>
            </div>
            <div class="batch-text">
              <div class="batch-label">7天内到期</div>
              <div class="batch-value">{{ batchSummary.expiring_in_7_days_count || 0 }}</div>
              <div class="batch-sub">数量: {{ batchSummary.expiring_in_7_days_quantity || 0 }}</div>
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card
          class="batch-card"
          style="--accent-color: #f59e0b"
          @click="goToExpiryAlerts"
        >
          <div class="batch-card-content">
            <div class="batch-icon-box warning">
              <el-icon :size="28"><Clock /></el-icon>
            </div>
            <div class="batch-text">
              <div class="batch-label">30天内到期</div>
              <div class="batch-value">{{ batchSummary.expiring_in_30_days_count || 0 }}</div>
              <div class="batch-sub">数量: {{ batchSummary.expiring_in_30_days_quantity || 0 }}</div>
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card
          class="batch-card"
          style="--accent-color: #10b981"
          @click="goToBatches"
        >
          <div class="batch-card-content">
            <div class="batch-icon-box normal">
              <el-icon :size="28"><Collection /></el-icon>
            </div>
            <div class="batch-text">
              <div class="batch-label">活跃批次</div>
              <div class="batch-value">{{ batchSummary.total_active_count || 0 }}</div>
              <div class="batch-sub">数量: {{ batchSummary.total_active_quantity || 0 }}</div>
            </div>
          </div>
        </el-card>
      </el-col>
    </el-row>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { Goods, Document, Box, Warning, CircleClose, Clock, Collection } from '@element-plus/icons-vue'
import { dashboardApi } from '@/api/modules/dashboard'
import { batchApi } from '@/api/modules/batch'

const router = useRouter()

const alertCount = ref(0)

const stats = ref([
  { title: '商品总数', value: 0, rawValue: 0, icon: Goods, color: '#409EFF', sub: '', isAlert: false, clickHandler: null },
  { title: '今日订单', value: 0, rawValue: 0, icon: Document, color: '#67C23A', sub: '', isAlert: false, clickHandler: null },
  { title: '库存总价值', value: 0, rawValue: 0, icon: Box, color: '#E6A23C', sub: '', isAlert: false, clickHandler: null },
])

const alertStat = computed(() => ({
  title: '库存预警',
  value: alertCount.value,
  rawValue: alertCount.value,
  icon: Warning,
  color: '#F56C6C',
  sub: '',
  isAlert: true,
  clickHandler: goToAlerts,
}))

const batchSummary = ref({
  expired_count: 0,
  expired_quantity: 0,
  expiring_in_7_days_count: 0,
  expiring_in_7_days_quantity: 0,
  expiring_in_30_days_count: 0,
  expiring_in_30_days_quantity: 0,
  total_active_count: 0,
  total_active_quantity: 0,
})

const showBatchCards = computed(() => {
  const s = batchSummary.value
  return (s.expired_count || 0) > 0
    || (s.expiring_in_7_days_count || 0) > 0
    || (s.expiring_in_30_days_count || 0) > 0
    || (s.total_active_count || 0) > 0
})

const allStats = computed(() => {
  const result = [...stats.value]
  if (alertCount.value > 0) {
    result.push(alertStat.value)
  }
  return result
})

const cardSpan = computed(() => {
  return alertCount.value > 0 ? 6 : 8
})

function goToAlerts() {
  router.push('/alerts')
}

function goToExpiryAlerts() {
  router.push('/expiry-alerts')
}

function goToBatches() {
  router.push('/batches')
}

onMounted(async () => {
  try {
    const res = await dashboardApi.getSummary()
    const data = res.data

    stats.value[0].value = data.products.total
    stats.value[0].rawValue = data.products.total

    stats.value[1].value = data.orders.today_count
    stats.value[1].rawValue = data.orders.today_count

    const totalValue = Number(data.inventory?.total_value ?? 0)
    stats.value[2].value = `¥${totalValue.toFixed(2)}`
    stats.value[2].rawValue = totalValue

    alertCount.value = data.alerts?.unread_count ?? 0

    if (data.batches) {
      Object.assign(batchSummary.value, data.batches)
    } else {
      try {
        const batchRes = await batchApi.getBatchSummary()
        Object.assign(batchSummary.value, batchRes.data)
      } catch (_) {}
    }
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

.batch-row {
  margin-top: 8px;
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

.stat-sub {
  font-size: 11px;
  color: #9ca3af;
}

.stat-icon {
  color: var(--accent-color);
  opacity: 0.26;
}

.stat-card.clickable {
  cursor: pointer;
}

.stat-card.clickable:hover {
  transform: translateY(-6px) !important;
  box-shadow: 0 28px 60px rgba(15, 23, 42, 0.28) !important;
}

.alert-value {
  color: #f56c6c !important;
  animation: pulse 2s infinite;
}

@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.6;
  }
}

.batch-card {
  position: relative;
  border-radius: 14px;
  border: none;
  background: #ffffff;
  box-shadow: 0 12px 28px rgba(15, 23, 42, 0.12);
  cursor: pointer;
  transition: transform 0.18s ease-out, box-shadow 0.18s ease-out;
  overflow: hidden;
}

.batch-card::before {
  content: '';
  position: absolute;
  inset: 0;
  border-radius: inherit;
  border-left: 4px solid var(--accent-color);
  opacity: 0.85;
}

.batch-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 18px 38px rgba(15, 23, 42, 0.18);
}

.batch-card-content {
  position: relative;
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 14px 16px;
}

.batch-icon-box {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  color: #ffffff;
}

.batch-icon-box.expired {
  background: linear-gradient(135deg, #ef4444, #dc2626);
}

.batch-icon-box.danger {
  background: linear-gradient(135deg, #f87171, #ef4444);
}

.batch-icon-box.warning {
  background: linear-gradient(135deg, #fbbf24, #f59e0b);
}

.batch-icon-box.normal {
  background: linear-gradient(135deg, #34d399, #10b981);
}

.batch-text {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.batch-label {
  font-size: 12px;
  color: #6b7280;
  line-height: 1.2;
}

.batch-value {
  font-size: 22px;
  font-weight: 700;
  color: #1f2937;
  line-height: 1.2;
}

.batch-sub {
  font-size: 11px;
  color: #9ca3af;
  line-height: 1.2;
}
</style>
