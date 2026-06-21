<template>
  <div class="expiry-alert page-shell">
    <el-row :gutter="20" class="summary-row">
      <el-col :span="6">
        <el-card class="summary-card expired-card" shadow="hover">
          <div class="summary-content">
            <div class="summary-icon">
              <el-icon :size="36"><CircleClose /></el-icon>
            </div>
            <div class="summary-info">
              <div class="summary-label">已过期批次</div>
              <div class="summary-value">{{ summary.expired_count || 0 }}</div>
              <div class="summary-sub">商品数量：{{ summary.expired_quantity || 0 }}</div>
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card class="summary-card danger-card" shadow="hover">
          <div class="summary-content">
            <div class="summary-icon">
              <el-icon :size="36"><Warning /></el-icon>
            </div>
            <div class="summary-info">
              <div class="summary-label">7天内到期</div>
              <div class="summary-value">{{ summary.expiring_in_7_days_count || 0 }}</div>
              <div class="summary-sub">商品数量：{{ summary.expiring_in_7_days_quantity || 0 }}</div>
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card class="summary-card warning-card" shadow="hover">
          <div class="summary-content">
            <div class="summary-icon">
              <el-icon :size="36"><Clock /></el-icon>
            </div>
            <div class="summary-info">
              <div class="summary-label">30天内到期</div>
              <div class="summary-value">{{ summary.expiring_in_30_days_count || 0 }}</div>
              <div class="summary-sub">商品数量：{{ summary.expiring_in_30_days_quantity || 0 }}</div>
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card class="summary-card normal-card" shadow="hover">
          <div class="summary-content">
            <div class="summary-icon">
              <el-icon :size="36"><Box /></el-icon>
            </div>
            <div class="summary-info">
              <div class="summary-label">活跃批次总数</div>
              <div class="summary-value">{{ summary.total_active_count || 0 }}</div>
              <div class="summary-sub">商品数量：{{ summary.total_active_quantity || 0 }}</div>
            </div>
          </div>
        </el-card>
      </el-col>
    </el-row>

    <el-card class="tab-card">
      <el-tabs v-model="activeTab" @tab-change="handleTabChange">
        <el-tab-pane label="临期预警 (30天内)" name="expiring">
          <div class="tab-toolbar">
            <el-input
              v-model="expiringFilters.batch_no"
              placeholder="搜索批次号"
              clearable
              style="width: 200px"
              @keyup.enter="fetchExpiring"
            />
            <el-button type="primary" @click="fetchExpiring">
              <el-icon><Search /></el-icon>
              查询
            </el-button>
            <el-button @click="handleScan">
              <el-icon><Refresh /></el-icon>
              刷新状态
            </el-button>
          </div>
          <el-table :data="expiringBatches" v-loading="expiringLoading" stripe style="width: 100%">
            <el-table-column prop="batch_no" label="批次号" width="140" fixed="left" />
            <el-table-column label="商品信息" width="220">
              <template #default="{ row }">
                <div class="product-info">
                  <div class="product-name">{{ row.product?.name || '-' }}</div>
                  <div class="product-sku">{{ row.product?.sku || '-' }}{{ row.sku?.sku ? ` / ${row.sku.sku}` : '' }}</div>
                </div>
              </template>
            </el-table-column>
            <el-table-column prop="quantity" label="剩余库存" width="100" align="center" sortable />
            <el-table-column prop="production_date" label="生产日期" width="120" />
            <el-table-column prop="shelf_life_days" label="保质期(天)" width="100" align="center" />
            <el-table-column label="到期日" width="140">
              <template #default="{ row }">
                <div class="expiry-date danger">{{ row.expiry_date }}</div>
              </template>
            </el-table-column>
            <el-table-column label="剩余天数" width="130" align="center">
              <template #default="{ row }">
                <el-tag :type="getUrgencyTagType(row.days_until_expiry)" size="large" effect="dark">
                  {{ row.days_until_expiry <= 0 ? `已过期${Math.abs(row.days_until_expiry)}天` : `剩 ${row.days_until_expiry} 天` }}
                </el-tag>
              </template>
            </el-table-column>
            <el-table-column label="紧急程度" width="110" align="center">
              <template #default="{ row }">
                <el-tag :type="getUrgencyTagType(row.days_until_expiry)" size="small" effect="plain">
                  {{ getUrgencyText(row.days_until_expiry) }}
                </el-tag>
              </template>
            </el-table-column>
            <el-table-column label="操作" width="150" fixed="right" align="center">
              <template #default="{ row }">
                <el-button
                  v-if="row.is_sellable && row.status !== 'expired'"
                  size="small"
                  type="warning"
                  @click="handleMarkUnsellable(row)"
                >
                  停售
                </el-button>
              </template>
            </el-table-column>
          </el-table>
          <el-pagination
            v-model:current-page="expiringPage"
            v-model:page-size="expiringPageSize"
            :total="expiringTotal"
            :page-sizes="[10, 20, 50]"
            layout="total, sizes, prev, pager, next, jumper"
            @size-change="fetchExpiring"
            @current-change="fetchExpiring"
            style="margin-top: 16px; justify-content: flex-end"
          />
        </el-tab-pane>

        <el-tab-pane label="已过期批次" name="expired">
          <div class="tab-toolbar">
            <el-input
              v-model="expiredFilters.batch_no"
              placeholder="搜索批次号"
              clearable
              style="width: 200px"
              @keyup.enter="fetchExpired"
            />
            <el-button type="primary" @click="fetchExpired">
              <el-icon><Search /></el-icon>
              查询
            </el-button>
          </div>
          <el-table :data="expiredBatches" v-loading="expiredLoading" stripe style="width: 100%">
            <el-table-column prop="batch_no" label="批次号" width="140" fixed="left" />
            <el-table-column label="商品信息" width="220">
              <template #default="{ row }">
                <div class="product-info">
                  <div class="product-name">{{ row.product?.name || '-' }}</div>
                  <div class="product-sku">{{ row.product?.sku || '-' }}{{ row.sku?.sku ? ` / ${row.sku.sku}` : '' }}</div>
                </div>
              </template>
            </el-table-column>
            <el-table-column label="批次数量" width="160">
              <template #default="{ row }">
                <div class="batch-quant">
                  <span class="curr-qty qty-zero">{{ row.quantity }}</span>
                  <span class="qty-divider">/</span>
                  <span class="init-qty">{{ row.initial_quantity }}</span>
                </div>
              </template>
            </el-table-column>
            <el-table-column prop="production_date" label="生产日期" width="120" />
            <el-table-column prop="shelf_life_days" label="保质期(天)" width="100" align="center" />
            <el-table-column label="到期日" width="140">
              <template #default="{ row }">
                <div class="expiry-date expired">{{ row.expiry_date }}</div>
              </template>
            </el-table-column>
            <el-table-column label="已过期天数" width="120" align="center">
              <template #default="{ row }">
                <el-tag type="danger" size="large" effect="dark">
                  {{ Math.abs(row.days_until_expiry) }} 天
                </el-tag>
              </template>
            </el-table-column>
            <el-table-column label="可售状态" width="100" align="center">
              <template #default="{ row }">
                <el-tag type="danger" size="small">已停售</el-tag>
              </template>
            </el-table-column>
          </el-table>
          <el-pagination
            v-model:current-page="expiredPage"
            v-model:page-size="expiredPageSize"
            :total="expiredTotal"
            :page-sizes="[10, 20, 50]"
            layout="total, sizes, prev, pager, next, jumper"
            @size-change="fetchExpired"
            @current-change="fetchExpired"
            style="margin-top: 16px; justify-content: flex-end"
          />
        </el-tab-pane>
      </el-tabs>
    </el-card>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Warning, Clock, CircleClose, Box, Search, Refresh } from '@element-plus/icons-vue'
import { batchApi } from '@/api/modules/batch'

const activeTab = ref('expiring')
const summary = reactive({
  expired_count: 0,
  expired_quantity: 0,
  expiring_in_7_days_count: 0,
  expiring_in_7_days_quantity: 0,
  expiring_in_30_days_count: 0,
  expiring_in_30_days_quantity: 0,
  total_active_count: 0,
  total_active_quantity: 0,
})

const expiringLoading = ref(false)
const expiringBatches = ref([])
const expiringPage = ref(1)
const expiringPageSize = ref(10)
const expiringTotal = ref(0)
const expiringFilters = reactive({ batch_no: '' })

const expiredLoading = ref(false)
const expiredBatches = ref([])
const expiredPage = ref(1)
const expiredPageSize = ref(10)
const expiredTotal = ref(0)
const expiredFilters = reactive({ batch_no: '' })

const getUrgencyTagType = (days) => {
  if (days <= 0) return 'danger'
  if (days <= 7) return 'danger'
  if (days <= 15) return 'warning'
  return 'warning'
}

const getUrgencyText = (days) => {
  if (days <= 0) return '已过期'
  if (days <= 7) return '紧急'
  if (days <= 15) return '较紧急'
  return '需关注'
}

const fetchSummary = async () => {
  try {
    const res = await batchApi.getBatchSummary()
    Object.assign(summary, res.data)
  } catch (e) {
    console.error('获取汇总失败', e)
  }
}

const fetchExpiring = async () => {
  expiringLoading.value = true
  try {
    const params = {
      page: expiringPage.value,
      per_page: expiringPageSize.value,
      days: 30,
    }
    if (expiringFilters.batch_no) params.batch_no = expiringFilters.batch_no

    const res = await batchApi.getExpiringSoon(params)
    expiringBatches.value = res.data
    expiringTotal.value = res.meta.total
  } catch (e) {
    ElMessage.error('获取临期批次失败')
  } finally {
    expiringLoading.value = false
  }
}

const fetchExpired = async () => {
  expiredLoading.value = true
  try {
    const params = {
      page: expiredPage.value,
      per_page: expiredPageSize.value,
    }
    if (expiredFilters.batch_no) params.batch_no = expiredFilters.batch_no

    const res = await batchApi.getExpired(params)
    expiredBatches.value = res.data
    expiredTotal.value = res.meta.total
  } catch (e) {
    ElMessage.error('获取过期批次失败')
  } finally {
    expiredLoading.value = false
  }
}

const handleTabChange = (tab) => {
  if (tab === 'expiring') {
    fetchExpiring()
  } else if (tab === 'expired') {
    fetchExpired()
  }
}

const handleMarkUnsellable = async (row) => {
  try {
    await ElMessageBox.confirm(
      `批次 ${row.batch_no} 还剩 ${row.days_until_expiry} 天到期，确定将其标记为不可售吗？`,
      '确认停售',
      {
        type: 'warning',
        confirmButtonText: '确认停售',
        cancelButtonText: '取消',
      }
    )
    await batchApi.markBatchUnsellable(row.id, { remark: '临期主动停售' })
    ElMessage.success('已标记为不可售')
    fetchSummary()
    fetchExpiring()
  } catch (e) {
    if (e !== 'cancel') {
      ElMessage.error(e.response?.data?.message || '操作失败')
    }
  }
}

const handleScan = async () => {
  try {
    const res = await batchApi.scanBatchStatuses()
    const { expired, expiring_soon } = res.data
    ElMessage.success(
      `扫描完成：${expired || 0} 个标记过期，${expiring_soon || 0} 个标记临期`
    )
    fetchSummary()
    fetchExpiring()
    fetchExpired()
  } catch (e) {
    ElMessage.error('扫描失败')
  }
}

onMounted(() => {
  fetchSummary()
  fetchExpiring()
})
</script>

<style scoped>
.expiry-alert {
  padding: 24px;
}

.summary-row {
  margin-bottom: 20px;
}

.summary-card {
  border-radius: 16px;
  border: none;
  overflow: hidden;
}

.summary-card.expired-card {
  background: linear-gradient(135deg, #fff5f5 0%, #ffe4e4 100%);
}

.summary-card.danger-card {
  background: linear-gradient(135deg, #fef3f2 0%, #fecaca 100%);
}

.summary-card.warning-card {
  background: linear-gradient(135deg, #fffbeb 0%, #fde68a 100%);
}

.summary-card.normal-card {
  background: linear-gradient(135deg, #f0fdf4 0%, #bbf7d0 100%);
}

.summary-content {
  display: flex;
  align-items: center;
  gap: 16px;
}

.summary-icon {
  opacity: 0.7;
}

.expired-card .summary-icon {
  color: #dc2626;
}

.danger-card .summary-icon {
  color: #ef4444;
}

.warning-card .summary-icon {
  color: #d97706;
}

.normal-card .summary-icon {
  color: #16a34a;
}

.summary-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.summary-label {
  font-size: 13px;
  color: #6b7280;
}

.summary-value {
  font-size: 28px;
  font-weight: 700;
  color: #1f2937;
  line-height: 1.2;
}

.summary-sub {
  font-size: 12px;
  color: #6b7280;
}

.tab-card {
  border-radius: 20px;
}

.tab-toolbar {
  display: flex;
  gap: 10px;
  margin-bottom: 16px;
}

.product-info {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.product-name {
  font-size: 14px;
  color: #1f2937;
  font-weight: 500;
}

.product-sku {
  font-size: 12px;
  color: #9ca3af;
}

.batch-quant {
  display: flex;
  align-items: baseline;
  gap: 4px;
  font-family: 'SF Mono', Consolas, monospace;
}

.curr-qty {
  font-size: 16px;
  font-weight: 600;
  color: #1f2937;
}

.curr-qty.qty-zero {
  color: #d1d5db;
}

.qty-divider {
  color: #9ca3af;
  font-size: 12px;
}

.init-qty {
  font-size: 12px;
  color: #9ca3af;
}

.expiry-date {
  font-weight: 500;
}

.expiry-date.danger {
  color: #f56c6c;
}

.expiry-date.expired {
  color: #909399;
  text-decoration: line-through;
}
</style>
