<template>
  <div class="alert-list page-shell">
    <el-card>
      <template #header>
        <div class="card-header">
          <div class="card-header-text">
            <span class="card-title">通知中心</span>
            <span class="card-subtitle">库存预警通知与消息管理</span>
          </div>
          <div class="card-header-actions">
            <el-button type="primary" plain @click="handleScan" :loading="scanning">
              <el-icon><Refresh /></el-icon>
              扫描预警
            </el-button>
            <el-button
              type="primary"
              @click="handleMarkAllRead"
              :loading="markingAll"
              :disabled="unreadCount === 0"
            >
              全部标记已读
            </el-button>
          </div>
        </div>
      </template>

      <!-- Tab 切换 -->
      <el-tabs v-model="activeTab" class="alert-tabs">
        <el-tab-pane label="未读" name="unread">
          <span v-if="unreadCount > 0" class="tab-badge">{{ unreadCount }}</span>
        </el-tab-pane>
        <el-tab-pane label="已读" name="read" />
      </el-tabs>

      <!-- 预警列表 -->
      <el-table :data="alerts" v-loading="loading" style="width: 100%">
        <el-table-column label="状态" width="80">
          <template #default="{ row }">
            <el-tag :type="row.status === 'unread' ? 'danger' : 'info'" size="small">
              {{ row.status === 'unread' ? '未读' : '已读' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="商品信息" min-width="240">
          <template #default="{ row }">
            <div class="product-info" @click="goToProduct(row.product_id)">
              <div class="product-name">{{ row.product?.name || '未知商品' }}</div>
              <div class="product-sku" v-if="row.product">SKU: {{ row.product.sku }}</div>
            </div>
          </template>
        </el-table-column>
        <el-table-column label="当前库存" width="120">
          <template #default="{ row }">
            <span class="stock-low">{{ row.current_stock }}</span>
          </template>
        </el-table-column>
        <el-table-column label="预警阈值" width="120">
          <template #default="{ row }">
            <span>{{ row.threshold }}</span>
          </template>
        </el-table-column>
        <el-table-column label="预警时间" width="180">
          <template #default="{ row }">
            {{ formatDate(row.created_at) }}
          </template>
        </el-table-column>
        <el-table-column label="已读时间" width="180">
          <template #default="{ row }">
            {{ row.read_at ? formatDate(row.read_at) : '-' }}
          </template>
        </el-table-column>
        <el-table-column label="操作" width="200" fixed="right">
          <template #default="{ row }">
            <el-button
              v-if="row.status === 'unread'"
              size="small"
              type="primary"
              @click="handleMarkRead(row)"
              :loading="markingId === row.id"
            >
              标记已读
            </el-button>
            <el-button
              size="small"
              @click="goToProduct(row.product_id)"
            >
              查看商品
            </el-button>
          </template>
        </el-table-column>
      </el-table>

      <!-- 空状态 -->
      <el-empty v-if="!loading && alerts.length === 0" description="暂无预警通知" />

      <!-- 分页 -->
      <el-pagination
        v-if="total > 0"
        v-model:current-page="currentPage"
        v-model:page-size="pageSize"
        :total="total"
        :page-sizes="[10, 20, 50, 100]"
        layout="total, sizes, prev, pager, next, jumper"
        @size-change="handleSizeChange"
        @current-change="handleCurrentChange"
        style="margin-top: 20px; justify-content: flex-end"
      />
    </el-card>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import { ElMessage } from 'element-plus'
import { Refresh } from '@element-plus/icons-vue'
import { useRouter } from 'vue-router'
import { inventoryAlertApi } from '@/api/modules/inventoryAlert'

const router = useRouter()

const alerts = ref([])
const loading = ref(false)
const currentPage = ref(1)
const pageSize = ref(10)
const total = ref(0)
const activeTab = ref('unread')
const unreadCount = ref(0)
const markingId = ref(null)
const markingAll = ref(false)
const scanning = ref(false)

const fetchAlerts = async () => {
  loading.value = true
  try {
    const res = await inventoryAlertApi.getAlerts({
      page: currentPage.value,
      per_page: pageSize.value,
      status: activeTab.value,
    })
    alerts.value = res.data
    total.value = res.meta.total
  } catch (error) {
    ElMessage.error('获取预警列表失败')
  } finally {
    loading.value = false
  }
}

const fetchUnreadCount = async () => {
  try {
    const res = await inventoryAlertApi.getUnreadCount()
    unreadCount.value = res.data.count
  } catch (error) {
    console.error('获取未读数量失败', error)
  }
}

const handleMarkRead = async (row) => {
  markingId.value = row.id
  try {
    await inventoryAlertApi.markAsRead(row.id)
    ElMessage.success('已标记为已读')
    await fetchAlerts()
    await fetchUnreadCount()
  } catch (error) {
    ElMessage.error('标记失败')
  } finally {
    markingId.value = null
  }
}

const handleMarkAllRead = async () => {
  markingAll.value = true
  try {
    await inventoryAlertApi.markAllAsRead()
    ElMessage.success('已全部标记为已读')
    await fetchAlerts()
    await fetchUnreadCount()
  } catch (error) {
    ElMessage.error('操作失败')
  } finally {
    markingAll.value = false
  }
}

const handleScan = async () => {
  scanning.value = true
  try {
    const res = await inventoryAlertApi.scanAlerts()
    const { created, total_low_stock } = res.data
    ElMessage.success(`扫描完成，共检测到 ${total_low_stock} 个低库存商品，新增 ${created} 条预警`)
    await fetchAlerts()
    await fetchUnreadCount()
  } catch (error) {
    ElMessage.error('扫描失败')
  } finally {
    scanning.value = false
  }
}

const goToProduct = (productId) => {
  router.push(`/products/${productId}/edit`)
}

const formatDate = (dateStr) => {
  if (!dateStr) return '-'
  const date = new Date(dateStr)
  return date.toLocaleString('zh-CN', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  })
}

const handleSizeChange = () => {
  currentPage.value = 1
  fetchAlerts()
}

const handleCurrentChange = () => {
  fetchAlerts()
}

watch(activeTab, () => {
  currentPage.value = 1
  fetchAlerts()
})

onMounted(() => {
  fetchAlerts()
  fetchUnreadCount()
})
</script>

<style scoped>
.alert-list {
  padding: 24px;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.card-header-text {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.card-title {
  font-size: 18px;
  font-weight: 600;
  color: #111827;
}

.card-subtitle {
  font-size: 12px;
  color: #6b7280;
}

.card-header-actions {
  display: flex;
  gap: 8px;
}

.alert-tabs :deep(.el-tabs__item) {
  position: relative;
}

.tab-badge {
  position: absolute;
  top: 6px;
  right: -8px;
  background: #f56c6c;
  color: white;
  border-radius: 10px;
  padding: 0 6px;
  font-size: 12px;
  line-height: 18px;
  min-width: 18px;
  text-align: center;
}

.product-info {
  cursor: pointer;
}

.product-info:hover {
  color: #409eff;
}

.product-name {
  font-size: 14px;
  font-weight: 500;
  color: #111827;
}

.product-sku {
  font-size: 12px;
  color: #6b7280;
  margin-top: 2px;
}

.stock-low {
  color: #f56c6c;
  font-weight: 600;
}
</style>
