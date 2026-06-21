<template>
  <div class="inventory-list page-shell">
    <el-card>
      <template #header>
        <div class="card-header">
          <div class="card-header-text">
            <span class="card-title">库存管理</span>
            <span class="card-subtitle">按商品与库存状态快速排查缺货与低库存，支持SKU维度调整</span>
          </div>
          <div class="card-header-actions">
            <el-button type="primary" @click="goToBatches">
              <el-icon><Collection /></el-icon>
              批次管理
            </el-button>
          </div>
        </div>
      </template>

      <el-form :inline="true" :model="filters" class="filter-form">
        <el-form-item label="商品名称">
          <el-input v-model="filters.search" placeholder="请输入商品名称或SKU" clearable style="width: 220px" />
        </el-form-item>
        <el-form-item label="库存状态">
          <el-select v-model="filters.status" placeholder="请选择状态" clearable style="width: 140px">
            <el-option label="全部" value="" />
            <el-option label="充足" value="sufficient" />
            <el-option label="低库存" value="low_stock" />
            <el-option label="缺货" value="out_of_stock" />
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="handleSearch">查询</el-button>
          <el-button @click="handleReset">重置</el-button>
        </el-form-item>
      </el-form>

      <el-table
        :data="inventory"
        v-loading="loading"
        style="width: 100%"
        row-key="id"
        :expand-row-keys="expandRowKeys"
        @expand-change="handleExpandChange"
      >
        <el-table-column type="expand" width="50">
          <template #default="{ row }">
            <div v-if="row.skus && row.skus.length > 0" class="sku-expand">
              <div class="sku-expand-header">
                <span class="sku-expand-title">规格明细（{{ row.skus.length }} 个SKU）</span>
              </div>
              <el-table :data="row.skus" size="small" border style="width: 100%">
                <el-table-column label="规格" min-width="180">
                  <template #default="{ row: sku }">
                    <span class="spec-text">{{ formatSpecText(sku.spec_data) }}</span>
                  </template>
                </el-table-column>
                <el-table-column prop="sku" label="SKU编码" width="150" />
                <el-table-column label="单价" width="100">
                  <template #default="{ row: sku }">
                    <span class="price">¥{{ Number(sku.price).toFixed(2) }}</span>
                  </template>
                </el-table-column>
                <el-table-column label="库存" width="120">
                  <template #default="{ row: sku }">
                    <span :class="getSkuStockClass(sku)">
                      {{ sku.stock_quantity }}
                    </span>
                  </template>
                </el-table-column>
                <el-table-column label="库存价值" width="110">
                  <template #default="{ row: sku }">
                    ¥{{ (Number(sku.price) * sku.stock_quantity).toFixed(2) }}
                  </template>
                </el-table-column>
                <el-table-column label="状态" width="80">
                  <template #default="{ row: sku }">
                    <el-tag :type="sku.status === 'active' ? 'success' : 'info'" size="small">
                      {{ sku.status === 'active' ? '在售' : '停用' }}
                    </el-tag>
                  </template>
                </el-table-column>
                <el-table-column label="操作" width="120" fixed="right">
                  <template #default="{ row: sku }">
                    <el-button size="small" type="primary" link @click="handleAdjustSku(row, sku)">
                      调整库存
                    </el-button>
                  </template>
                </el-table-column>
              </el-table>
            </div>
            <div v-else class="sku-expand-empty">
              <el-empty description="该商品无多规格配置" :image-size="60" />
            </div>
          </template>
        </el-table-column>

        <el-table-column prop="name" label="商品名称" width="200" />
        <el-table-column label="规格数" width="80" align="center">
          <template #default="{ row }">
            <el-tag v-if="row.sku_count > 0" type="warning" size="small">
              {{ row.sku_count }}
            </el-tag>
            <el-tag v-else type="info" size="small">单规格</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="sku" label="SPU/SKU" width="150" />
        <el-table-column label="库存数量" width="130" sortable>
          <template #default="{ row }">
            <span :class="getStockClass(row)">
              {{ row.stock_quantity }}
              <el-tag v-if="row.sku_count > 0" type="info" size="small" class="stock-sum-tag">
                合计
              </el-tag>
            </span>
          </template>
        </el-table-column>
        <el-table-column label="单价" width="110">
          <template #default="{ row }">
            <template v-if="row.sku_count > 0">
              <span class="price-range">
                ¥{{ Number(row.min_price ?? row.price).toFixed(2) }}
                <span v-if="row.max_price && row.max_price !== row.min_price">
                  ~ ¥{{ Number(row.max_price).toFixed(2) }}
                </span>
              </span>
            </template>
            <template v-else>
              ¥{{ Number(row.price).toFixed(2) }}
            </template>
          </template>
        </el-table-column>
        <el-table-column label="库存价值" width="120">
          <template #default="{ row }">
            ¥{{ (Number(row.price) * row.stock_quantity).toFixed(2) }}
          </template>
        </el-table-column>
        <el-table-column prop="status" label="商品状态" width="100">
          <template #default="{ row }">
            <el-tag :type="getStatusType(row.status)">
              {{ getStatusText(row.status) }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="操作" width="260" fixed="right">
          <template #default="{ row }">
            <el-button size="small" @click="handleAdjust(row)">
              {{ row.sku_count > 0 ? '调整总库存' : '调整库存' }}
            </el-button>
            <el-button
              v-if="row.sku_count > 0"
              size="small"
              type="warning"
              plain
              @click="toggleExpand(row)"
            >
              {{ isRowExpanded(row) ? '收起' : '展开SKU' }}
            </el-button>
            <el-button size="small" type="primary" plain @click="handleViewBatches(row)">
              查看批次
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
        @size-change="handleSizeChange"
        @current-change="handleCurrentChange"
        style="margin-top: 20px; justify-content: flex-end"
      />
    </el-card>

    <el-dialog v-model="adjustDialogVisible" :title="adjustDialogTitle" width="520px">
      <el-form :model="adjustForm" label-width="110px">
        <el-form-item label="商品名称">
          <el-input v-model="adjustForm.product_name" disabled />
        </el-form-item>
        <el-form-item v-if="adjustForm.is_sku" label="规格">
          <el-input v-model="adjustForm.spec_text" disabled />
        </el-form-item>
        <el-form-item v-if="adjustForm.is_sku" label="SKU编码">
          <el-input v-model="adjustForm.sku_code" disabled />
        </el-form-item>
        <el-form-item label="当前库存">
          <el-input v-model="adjustForm.current_stock" disabled />
        </el-form-item>
        <el-form-item label="调整后库存" prop="quantity">
          <el-input-number
            v-model="adjustForm.quantity"
            :min="0"
            :max="999999"
            style="width: 100%"
          />
        </el-form-item>
        <el-form-item label="变动量">
          <el-tag :type="getChangeType()">
            {{ getChangeText() }}
          </el-tag>
        </el-form-item>
        <el-form-item label="调整原因">
          <el-input
            v-model="adjustForm.remark"
            type="textarea"
            :rows="3"
            placeholder="请输入调整原因（建议必填）"
          />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="adjustDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="handleSubmitAdjust" :loading="adjustLoading">
          确定调整
        </el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { Collection } from '@element-plus/icons-vue'
import { useRouter } from 'vue-router'
import { inventoryApi } from '@/api/modules/inventory'

const router = useRouter()

const inventory = ref([])
const loading = ref(false)
const currentPage = ref(1)
const pageSize = ref(10)
const total = ref(0)
const expandRowKeys = ref([])

const filters = reactive({
  search: '',
  status: '',
})

const adjustDialogVisible = ref(false)
const adjustLoading = ref(false)
const adjustForm = reactive({
  is_sku: false,
  product_id: null,
  sku_id: null,
  product_name: '',
  spec_text: '',
  sku_code: '',
  current_stock: 0,
  quantity: 0,
  remark: '',
})

const adjustDialogTitle = ref('调整库存')

const formatSpecText = (specData) => {
  if (!specData) return '-'
  if (typeof specData === 'string') {
    try {
      specData = JSON.parse(specData)
    } catch {
      return specData
    }
  }
  if (Array.isArray(specData)) {
    return specData.map((s) => `${s.name}: ${s.value}`).join(' / ')
  }
  if (typeof specData === 'object') {
    return Object.entries(specData).map(([k, v]) => `${k}: ${v}`).join(' / ')
  }
  return '-'
}

const getStockClass = (row) => {
  if (row.stock_quantity === 0) return 'stock-out'
  if (row.stock_quantity <= 10) return 'stock-low'
  return 'stock-normal'
}

const getSkuStockClass = (sku) => {
  if (sku.stock_quantity === 0) return 'stock-out'
  if (sku.stock_quantity <= 10) return 'stock-low'
  return 'stock-normal'
}

const getStatusType = (status) => {
  const map = {
    active: 'success',
    inactive: 'info',
    sold_out: 'danger',
  }
  return map[status] || 'info'
}

const getStatusText = (status) => {
  const map = {
    active: '上架',
    inactive: '下架',
    sold_out: '售罄',
  }
  return map[status] || status
}

const fetchInventory = async () => {
  loading.value = true
  try {
    const params = {
      page: currentPage.value,
      per_page: pageSize.value,
    }

    if (filters.status === 'low_stock') {
      params.low_stock = 1
    } else if (filters.status === 'out_of_stock') {
      params.out_of_stock = 1
    } else if (filters.status === 'sufficient') {
      params.sufficient = 1
    }

    if (filters.search) {
      params.search = filters.search
    }

    const res = await inventoryApi.getInventory(params)
    inventory.value = res.data
    total.value = res.meta.total
  } catch (error) {
    ElMessage.error('获取库存列表失败')
  } finally {
    loading.value = false
  }
}

const handleSearch = () => {
  currentPage.value = 1
  expandRowKeys.value = []
  fetchInventory()
}

const handleReset = () => {
  Object.assign(filters, {
    search: '',
    status: '',
  })
  handleSearch()
}

const handleAdjust = (row) => {
  adjustForm.is_sku = false
  adjustForm.product_id = row.id
  adjustForm.sku_id = null
  adjustForm.product_name = row.name
  adjustForm.spec_text = ''
  adjustForm.sku_code = row.sku
  adjustForm.current_stock = row.stock_quantity
  adjustForm.quantity = row.stock_quantity
  adjustForm.remark = ''
  adjustDialogTitle.value = row.sku_count > 0 ? '调整商品总库存' : '调整库存'
  adjustDialogVisible.value = true
}

const handleAdjustSku = (product, sku) => {
  adjustForm.is_sku = true
  adjustForm.product_id = product.id
  adjustForm.sku_id = sku.id
  adjustForm.product_name = product.name
  adjustForm.spec_text = formatSpecText(sku.spec_data)
  adjustForm.sku_code = sku.sku
  adjustForm.current_stock = sku.stock_quantity
  adjustForm.quantity = sku.stock_quantity
  adjustForm.remark = ''
  adjustDialogTitle.value = '调整SKU库存'
  adjustDialogVisible.value = true
}

const getChangeType = () => {
  const diff = adjustForm.quantity - adjustForm.current_stock
  if (diff > 0) return 'success'
  if (diff < 0) return 'danger'
  return 'info'
}

const getChangeText = () => {
  const diff = adjustForm.quantity - adjustForm.current_stock
  if (diff > 0) return `+${diff}（增加）`
  if (diff < 0) return `${diff}（减少）`
  return '0（无变化）'
}

const handleSubmitAdjust = async () => {
  if (adjustForm.quantity < 0) {
    ElMessage.warning('库存数量不能为负数')
    return
  }
  if (!adjustForm.remark || adjustForm.remark.trim() === '') {
    ElMessage.warning('请填写调整原因')
    return
  }

  adjustLoading.value = true
  try {
    if (adjustForm.is_sku) {
      await inventoryApi.updateSkuInventory(adjustForm.sku_id, {
        quantity: adjustForm.quantity,
        remark: adjustForm.remark,
      })
      ElMessage.success('SKU库存调整成功，商品总库存已同步更新')
    } else {
      await inventoryApi.updateInventory(adjustForm.product_id, {
        quantity: adjustForm.quantity,
        remark: adjustForm.remark,
      })
      ElMessage.success('库存调整成功')
    }
    adjustDialogVisible.value = false
    fetchInventory()
  } catch (error) {
    ElMessage.error(error?.response?.data?.message || '库存调整失败')
  } finally {
    adjustLoading.value = false
  }
}

const handleSizeChange = () => {
  fetchInventory()
}

const handleCurrentChange = () => {
  fetchInventory()
}

const goToBatches = () => {
  router.push('/batches')
}

const handleViewBatches = (row) => {
  router.push({
    path: '/batches',
    query: { product_id: row.id },
  })
}

const isRowExpanded = (row) => expandRowKeys.value.includes(row.id)

const toggleExpand = (row) => {
  const idx = expandRowKeys.value.indexOf(row.id)
  if (idx > -1) {
    expandRowKeys.value.splice(idx, 1)
  } else {
    expandRowKeys.value.push(row.id)
  }
}

const handleExpandChange = (row, expandedRows) => {
  expandRowKeys.value = expandedRows.map((r) => r.id)
}

onMounted(() => {
  fetchInventory()
})
</script>

<style scoped>
.inventory-list {
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

.filter-form {
  margin-bottom: 20px;
}

.stock-out {
  color: #f56c6c;
  font-weight: bold;
}

.stock-low {
  color: #e6a23c;
  font-weight: bold;
}

.stock-normal {
  color: #67c23a;
}

.stock-sum-tag {
  margin-left: 4px;
}

.price-range {
  color: #409eff;
}

.price {
  color: #f56c6c;
}

.spec-text {
  color: #303133;
}

.sku-expand {
  padding: 8px 16px 16px 40px;
  background: #fafafa;
}

.sku-expand-header {
  margin-bottom: 12px;
}

.sku-expand-title {
  font-weight: 600;
  font-size: 14px;
  color: #303133;
}

.sku-expand-empty {
  padding: 20px 0;
}

:deep(.el-table__expanded-cell) {
  padding: 0 !important;
}
</style>
