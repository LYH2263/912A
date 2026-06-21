<template>
  <div class="batch-list page-shell">
    <el-card>
      <template #header>
        <div class="card-header">
          <div class="card-header-text">
            <span class="card-title">批次管理</span>
            <span class="card-subtitle">入库登记批次号、生产日期、保质期天数，系统按 FIFO 批次扣减库存</span>
          </div>
          <div class="card-header-actions">
            <el-button type="primary" @click="handleCreate">
              <el-icon><Plus /></el-icon>
              批次入库
            </el-button>
            <el-button @click="handleScan">
              <el-icon><Refresh /></el-icon>
              扫描临期/过期
            </el-button>
          </div>
        </div>
      </template>

      <el-form :inline="true" :model="filters" class="filter-form">
        <el-form-item label="批次号">
          <el-input v-model="filters.batch_no" placeholder="请输入批次号" clearable style="width: 180px" />
        </el-form-item>
        <el-form-item label="商品名称">
          <el-select
            v-model="filters.product_id"
            filterable
            remote
            placeholder="搜索商品"
            clearable
            style="width: 220px"
            :remote-method="searchProducts"
            :loading="productLoading"
          >
            <el-option
              v-for="p in productOptions"
              :key="p.id"
              :label="`${p.name} (${p.sku})`"
              :value="p.id"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="状态">
          <el-select v-model="filters.status" placeholder="请选择" clearable style="width: 140px">
            <el-option label="全部" value="" />
            <el-option label="正常" value="normal" />
            <el-option label="临期" value="expiring_soon" />
            <el-option label="已过期" value="expired" />
          </el-select>
        </el-form-item>
        <el-form-item label="库存">
          <el-select v-model="filters.has_stock" placeholder="请选择" clearable style="width: 140px">
            <el-option label="全部" value="" />
            <el-option label="有库存" :value="1" />
            <el-option label="无库存" :value="0" />
          </el-select>
        </el-form-item>
        <el-form-item label="到期日">
          <el-date-picker
            v-model="filters.expiry_range"
            type="daterange"
            range-separator="至"
            start-placeholder="开始日期"
            end-placeholder="结束日期"
            value-format="YYYY-MM-DD"
            style="width: 280px"
          />
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="handleSearch">查询</el-button>
          <el-button @click="handleReset">重置</el-button>
        </el-form-item>
      </el-form>

      <el-table :data="batches" v-loading="loading" stripe style="width: 100%">
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
              <span class="curr-qty" :class="{ 'qty-zero': row.quantity === 0 }">{{ row.quantity }}</span>
              <span class="qty-divider">/</span>
              <span class="init-qty">{{ row.initial_quantity }}</span>
            </div>
            <el-progress
              :percentage="row.usage_rate"
              :stroke-width="4"
              :color="getProgressColor(row.usage_rate)"
              style="margin-top: 4px"
            />
          </template>
        </el-table-column>
        <el-table-column prop="production_date" label="生产日期" width="120" />
        <el-table-column prop="shelf_life_days" label="保质期(天)" width="100" align="center" />
        <el-table-column label="到期日" width="160">
          <template #default="{ row }">
            <div class="expiry-info">
              <div :class="getExpiryClass(row)">{{ row.expiry_date }}</div>
              <div class="days-left" :class="getExpiryDaysClass(row)">
                {{ formatDaysLeft(row.days_until_expiry) }}
              </div>
            </div>
          </template>
        </el-table-column>
        <el-table-column label="状态" width="100" align="center">
          <template #default="{ row }">
            <el-tag :type="getStatusType(row.status)" size="small">
              {{ getStatusText(row.status) }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="可售" width="70" align="center">
          <template #default="{ row }">
            <el-tag v-if="row.is_sellable" type="success" size="small">是</el-tag>
            <el-tag v-else type="danger" size="small">否</el-tag>
          </template>
        </el-table-column>
        <el-table-column label="操作" width="220" fixed="right" align="center">
          <template #default="{ row }">
            <el-button size="small" @click="handleAdjust(row)">调整</el-button>
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
        v-model:current-page="currentPage"
        v-model:page-size="pageSize"
        :total="total"
        :page-sizes="[10, 20, 50, 100]"
        layout="total, sizes, prev, pager, next, jumper"
        @size-change="fetchBatches"
        @current-change="fetchBatches"
        style="margin-top: 20px; justify-content: flex-end"
      />
    </el-card>

    <el-dialog v-model="createDialogVisible" title="批次入库登记" width="640px" @close="resetCreateForm">
      <el-form :model="createForm" :rules="createRules" ref="createFormRef" label-width="110px">
        <el-form-item label="选择商品" prop="product_id">
          <el-select
            v-model="createForm.product_id"
            filterable
            remote
            placeholder="搜索商品"
            style="width: 100%"
            :remote-method="searchProducts"
            :loading="productLoading"
            @change="onProductChange"
          >
            <el-option
              v-for="p in productOptions"
              :key="p.id"
              :label="`${p.name} (${p.sku})`"
              :value="p.id"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="规格SKU" v-if="skuOptions.length > 0">
          <el-select
            v-model="createForm.sku_id"
            placeholder="选择规格（不选则为主商品）"
            clearable
            style="width: 100%"
          >
            <el-option
              v-for="s in skuOptions"
              :key="s.id"
              :label="`${s.sku} ${formatSpecData(s.spec_data)}`"
              :value="s.id"
            />
          </el-select>
        </el-form-item>
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="批次号" prop="batch_no">
              <el-input v-model="createForm.batch_no" placeholder="如：B20260621001" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="入库数量" prop="quantity">
              <el-input-number v-model="createForm.quantity" :min="1" :max="99999" style="width: 100%" />
            </el-form-item>
          </el-col>
        </el-row>
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="生产日期" prop="production_date">
              <el-date-picker
                v-model="createForm.production_date"
                type="date"
                placeholder="选择生产日期"
                value-format="YYYY-MM-DD"
                style="width: 100%"
              />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="保质期(天)" prop="shelf_life_days">
              <el-input-number
                v-model="createForm.shelf_life_days"
                :min="1"
                :max="3650"
                style="width: 100%"
                @change="calculateExpiryDate"
              />
            </el-form-item>
          </el-col>
        </el-row>
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="到期日">
              <el-input v-model="createForm.expiry_date_display" disabled>
                <template #append>自动计算</template>
              </el-input>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="入库单价(元)">
              <el-input-number
                v-model="createForm.unit_cost"
                :min="0"
                :precision="2"
                :step="0.5"
                style="width: 100%"
              />
            </el-form-item>
          </el-col>
        </el-row>
        <el-form-item label="备注">
          <el-input v-model="createForm.remark" type="textarea" :rows="2" placeholder="选填" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="createDialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="createLoading" @click="submitCreateForm">确认入库</el-button>
      </template>
    </el-dialog>

    <el-dialog v-model="adjustDialogVisible" title="调整批次库存" width="480px">
      <el-form :model="adjustForm" label-width="100px">
        <el-form-item label="批次号">
          <el-input v-model="adjustForm.batch_no" disabled />
        </el-form-item>
        <el-form-item label="商品名称">
          <el-input v-model="adjustForm.product_name" disabled />
        </el-form-item>
        <el-form-item label="当前库存">
          <el-input v-model="adjustForm.current_qty" disabled />
        </el-form-item>
        <el-form-item label="调整后数量" prop="quantity">
          <el-input-number v-model="adjustForm.quantity" :min="0" :max="99999" style="width: 100%" />
        </el-form-item>
        <el-form-item label="调整原因">
          <el-input v-model="adjustForm.remark" type="textarea" :rows="2" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="adjustDialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="adjustLoading" @click="submitAdjustForm">确认调整</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Plus, Refresh } from '@element-plus/icons-vue'
import { useRoute } from 'vue-router'
import { batchApi } from '@/api/modules/batch'
import { productApi } from '@/api/modules/product'

const route = useRoute()

const loading = ref(false)
const batches = ref([])
const currentPage = ref(1)
const pageSize = ref(10)
const total = ref(0)
const productLoading = ref(false)
const productOptions = ref([])
const skuOptions = ref([])

const filters = reactive({
  batch_no: '',
  product_id: '',
  status: '',
  has_stock: '',
  expiry_range: [],
})

const createDialogVisible = ref(false)
const createLoading = ref(false)
const createFormRef = ref(null)
const createForm = reactive({
  product_id: null,
  sku_id: null,
  batch_no: '',
  quantity: 1,
  production_date: '',
  shelf_life_days: 365,
  unit_cost: null,
  remark: '',
})

const createRules = {
  product_id: [{ required: true, message: '请选择商品', trigger: 'change' }],
  batch_no: [{ required: true, message: '请输入批次号', trigger: 'blur' }],
  quantity: [{ required: true, message: '请输入入库数量', trigger: 'blur' }],
  production_date: [{ required: true, message: '请选择生产日期', trigger: 'change' }],
  shelf_life_days: [{ required: true, message: '请输入保质期', trigger: 'blur' }],
}

const expiry_date_display = computed(() => {
  if (!createForm.production_date || !createForm.shelf_life_days) return ''
  const d = new Date(createForm.production_date)
  d.setDate(d.getDate() + Number(createForm.shelf_life_days))
  return d.toISOString().split('T')[0]
})

const adjustDialogVisible = ref(false)
const adjustLoading = ref(false)
const adjustForm = reactive({
  id: null,
  batch_no: '',
  product_name: '',
  current_qty: 0,
  quantity: 0,
  remark: '',
})

const formatSpecData = (specData) => {
  if (!specData) return ''
  return Object.entries(specData).map(([k, v]) => `${k}:${v}`).join(' ')
}

const getProgressColor = (percent) => {
  if (percent >= 90) return '#f56c6c'
  if (percent >= 60) return '#e6a23c'
  return '#67c23a'
}

const getStatusType = (status) => {
  const map = {
    normal: 'success',
    expiring_soon: 'warning',
    expired: 'danger',
  }
  return map[status] || 'info'
}

const getStatusText = (status) => {
  const map = {
    normal: '正常',
    expiring_soon: '临期',
    expired: '已过期',
  }
  return map[status] || status
}

const getExpiryClass = (row) => {
  if (row.days_until_expiry < 0) return 'expiry-expired'
  if (row.days_until_expiry <= 7) return 'expiry-danger'
  if (row.days_until_expiry <= 30) return 'expiry-warning'
  return ''
}

const getExpiryDaysClass = (row) => {
  if (row.days_until_expiry < 0) return 'days-expired'
  if (row.days_until_expiry <= 7) return 'days-danger'
  if (row.days_until_expiry <= 30) return 'days-warning'
  return 'days-normal'
}

const formatDaysLeft = (days) => {
  if (days < 0) return `已过期 ${Math.abs(days)} 天`
  if (days === 0) return '今日到期'
  return `剩 ${days} 天`
}

const searchProducts = async (query) => {
  if (!query) return
  productLoading.value = true
  try {
    const res = await productApi.getProducts({ per_page: 50 })
    productOptions.value = res.data.filter(
      (p) => p.name.includes(query) || p.sku.includes(query)
    )
  } catch (e) {
    console.error(e)
  } finally {
    productLoading.value = false
  }
}

const onProductChange = async (productId) => {
  createForm.sku_id = null
  if (!productId) {
    skuOptions.value = []
    return
  }
  try {
    const res = await productApi.getProduct(productId)
    skuOptions.value = res.data.skus || []
  } catch (e) {
    skuOptions.value = []
  }
}

const calculateExpiryDate = () => {
  // 触发 computed 重新计算
}

const fetchBatches = async () => {
  loading.value = true
  try {
    const params = {
      page: currentPage.value,
      per_page: pageSize.value,
    }
    if (filters.batch_no) params.batch_no = filters.batch_no
    if (filters.product_id) params.product_id = filters.product_id
    if (filters.status) params.status = filters.status
    if (filters.has_stock !== '') params.has_stock = filters.has_stock
    if (filters.expiry_range && filters.expiry_range.length === 2) {
      params.expiry_date_start = filters.expiry_range[0]
      params.expiry_date_end = filters.expiry_range[1]
    }

    const res = await batchApi.getBatches(params)
    batches.value = res.data
    total.value = res.meta.total
  } catch (e) {
    ElMessage.error('获取批次列表失败')
  } finally {
    loading.value = false
  }
}

const handleSearch = () => {
  currentPage.value = 1
  fetchBatches()
}

const handleReset = () => {
  Object.assign(filters, {
    batch_no: '',
    product_id: '',
    status: '',
    has_stock: '',
    expiry_range: [],
  })
  currentPage.value = 1
  fetchBatches()
}

const handleCreate = () => {
  createDialogVisible.value = true
}

const resetCreateForm = () => {
  createFormRef.value?.resetFields()
  Object.assign(createForm, {
    product_id: null,
    sku_id: null,
    batch_no: '',
    quantity: 1,
    production_date: '',
    shelf_life_days: 365,
    unit_cost: null,
    remark: '',
  })
  skuOptions.value = []
}

const submitCreateForm = async () => {
  if (!createFormRef.value) return
  await createFormRef.value.validate(async (valid) => {
    if (!valid) return
    createLoading.value = true
    try {
      await batchApi.createBatch({
        ...createForm,
      })
      ElMessage.success('批次入库成功')
      createDialogVisible.value = false
      fetchBatches()
    } catch (e) {
      ElMessage.error(e.response?.data?.message || '入库失败')
    } finally {
      createLoading.value = false
    }
  })
}

const handleAdjust = (row) => {
  Object.assign(adjustForm, {
    id: row.id,
    batch_no: row.batch_no,
    product_name: row.product?.name || '',
    current_qty: row.quantity,
    quantity: row.quantity,
    remark: '',
  })
  adjustDialogVisible.value = true
}

const submitAdjustForm = async () => {
  adjustLoading.value = true
  try {
    await batchApi.adjustBatchQuantity(adjustForm.id, {
      quantity: adjustForm.quantity,
      remark: adjustForm.remark,
    })
    ElMessage.success('调整成功')
    adjustDialogVisible.value = false
    fetchBatches()
  } catch (e) {
    ElMessage.error(e.response?.data?.message || '调整失败')
  } finally {
    adjustLoading.value = false
  }
}

const handleMarkUnsellable = async (row) => {
  try {
    await ElMessageBox.confirm(
      `确定将批次 ${row.batch_no} 标记为不可售吗？该批次剩余库存将不再参与销售。`,
      '确认停售',
      {
        type: 'warning',
        confirmButtonText: '确认停售',
        cancelButtonText: '取消',
      }
    )
    await batchApi.markBatchUnsellable(row.id, { remark: '手动标记不可售' })
    ElMessage.success('已标记为不可售')
    fetchBatches()
  } catch (e) {
    if (e !== 'cancel') {
      ElMessage.error(e.response?.data?.message || '操作失败')
    }
  }
}

const handleScan = async () => {
  try {
    const res = await batchApi.scanBatchStatuses()
    const { expired, expiring_soon, back_to_normal } = res.data
    const messages = []
    if (expired > 0) messages.push(`${expired} 个批次已标记过期`)
    if (expiring_soon > 0) messages.push(`${expiring_soon} 个批次已标记临期`)
    if (back_to_normal > 0) messages.push(`${back_to_normal} 个批次恢复正常`)
    ElMessage.success(messages.length > 0 ? `扫描完成：${messages.join('，')}` : '扫描完成，无状态更新')
    fetchBatches()
  } catch (e) {
    ElMessage.error('扫描失败')
  }
}

onMounted(() => {
  if (route.query.product_id) {
    filters.product_id = Number(route.query.product_id)
  }
  fetchBatches()
})
</script>

<style scoped>
.batch-list {
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
  gap: 10px;
}

.filter-form {
  margin-bottom: 20px;
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

.expiry-info {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.expiry-danger {
  color: #f56c6c;
  font-weight: 600;
}

.expiry-warning {
  color: #e6a23c;
  font-weight: 500;
}

.expiry-expired {
  color: #909399;
  text-decoration: line-through;
}

.days-left {
  font-size: 12px;
}

.days-normal {
  color: #67c23a;
}

.days-warning {
  color: #e6a23c;
}

.days-danger {
  color: #f56c6c;
  font-weight: 600;
}

.days-expired {
  color: #909399;
  font-style: italic;
}
</style>
