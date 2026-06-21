<template>
  <div class="inventory-list page-shell">
    <el-card>
      <template #header>
        <div class="card-header">
          <div class="card-header-text">
            <span class="card-title">库存管理</span>
            <span class="card-subtitle">按商品与库存状态快速排查缺货与低库存</span>
          </div>
        </div>
      </template>

      <!-- 筛选条件 -->
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

      <!-- 库存表格 -->
      <el-table :data="inventory" v-loading="loading" style="width: 100%">
        <el-table-column prop="name" label="商品名称" width="200" />
        <el-table-column prop="sku" label="SKU" width="150" />
        <el-table-column prop="stock_quantity" label="库存数量" width="120" sortable>
          <template #default="{ row }">
            <span :class="getStockClass(row)">
              {{ row.stock_quantity }}
            </span>
          </template>
        </el-table-column>
        <el-table-column prop="price" label="单价" width="100">
          <template #default="{ row }">
            ¥{{ row.price }}
          </template>
        </el-table-column>
        <el-table-column label="库存价值" width="120">
          <template #default="{ row }">
            ¥{{ (row.price * row.stock_quantity).toFixed(2) }}
          </template>
        </el-table-column>
        <el-table-column prop="status" label="商品状态" width="100">
          <template #default="{ row }">
            <el-tag :type="getStatusType(row.status)">
              {{ getStatusText(row.status) }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="操作" width="200" fixed="right">
          <template #default="{ row }">
            <el-button size="small" @click="handleAdjust(row)">调整库存</el-button>
          </template>
        </el-table-column>
      </el-table>

      <!-- 分页 -->
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

    <!-- 调整库存对话框 -->
    <el-dialog v-model="adjustDialogVisible" title="调整库存" width="500px">
      <el-form :model="adjustForm" label-width="100px">
        <el-form-item label="商品名称">
          <el-input v-model="adjustForm.product_name" disabled />
        </el-form-item>
        <el-form-item label="当前库存">
          <el-input v-model="adjustForm.current_stock" disabled />
        </el-form-item>
        <el-form-item label="调整后库存" prop="quantity">
          <el-input-number
            v-model="adjustForm.quantity"
            :min="0"
            style="width: 100%"
          />
        </el-form-item>
        <el-form-item label="调整原因">
          <el-input
            v-model="adjustForm.remark"
            type="textarea"
            :rows="3"
            placeholder="请输入调整原因"
          />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="adjustDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="handleSubmitAdjust" :loading="adjustLoading">
          确定
        </el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { inventoryApi } from '@/api/modules/inventory'
import { productApi } from '@/api/modules/product'

const inventory = ref([])
const loading = ref(false)
const currentPage = ref(1)
const pageSize = ref(10)
const total = ref(0)
const adjustDialogVisible = ref(false)
const adjustLoading = ref(false)

const filters = reactive({
  search: '',
  status: '',
})

const adjustForm = reactive({
  product_id: null,
  product_name: '',
  current_stock: 0,
  quantity: 0,
  remark: '',
})

const getStockClass = (row) => {
  if (row.stock_quantity === 0) {
    return 'stock-out'
  }
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
      // 这里需要后端支持搜索，暂时使用前端过滤
      const res = await productApi.getProducts({ per_page: 1000 })
      let allProducts = res.data
      
      // 先按商品名称或SKU过滤
      allProducts = allProducts.filter(
        (p) =>
          p.name.includes(filters.search) || p.sku.includes(filters.search)
      )
      
      // 再按库存状态过滤
      if (filters.status === 'out_of_stock') {
        allProducts = allProducts.filter((p) => p.stock_quantity === 0)
      } else if (filters.status === 'low_stock') {
        allProducts = allProducts.filter((p) => p.stock_quantity > 0 && p.stock_quantity <= 10)
      } else if (filters.status === 'sufficient') {
        allProducts = allProducts.filter((p) => p.stock_quantity > 10)
      }
      
      inventory.value = allProducts
      total.value = inventory.value.length
    } else {
      const res = await inventoryApi.getInventory(params)
      inventory.value = res.data
      total.value = res.meta.total
    }
  } catch (error) {
    ElMessage.error('获取库存列表失败')
  } finally {
    loading.value = false
  }
}

const handleSearch = () => {
  currentPage.value = 1
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
  adjustForm.product_id = row.id
  adjustForm.product_name = row.name
  adjustForm.current_stock = row.stock_quantity
  adjustForm.quantity = row.stock_quantity
  adjustForm.remark = ''
  adjustDialogVisible.value = true
}

const handleSubmitAdjust = async () => {
  if (adjustForm.quantity < 0) {
    ElMessage.warning('库存数量不能为负数')
    return
  }

  adjustLoading.value = true
  try {
    await inventoryApi.updateInventory(adjustForm.product_id, {
      quantity: adjustForm.quantity,
      remark: adjustForm.remark,
    })
    ElMessage.success('库存调整成功')
    adjustDialogVisible.value = false
    fetchInventory()
  } catch (error) {
    ElMessage.error('库存调整失败')
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
</style>
