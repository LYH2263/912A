<template>
  <div class="product-list page-shell">
    <el-card>
      <template #header>
        <div class="card-header">
          <div class="card-header-text">
            <span class="card-title">商品列表</span>
            <span class="card-subtitle">管理在售商品、状态与库存情况</span>
          </div>
          <el-button type="primary" @click="$router.push('/products/create')" round>
            新增商品
          </el-button>
        </div>
      </template>
      
      <el-table :data="products" v-loading="loading" style="width: 100%">
        <el-table-column prop="name" label="商品名称" width="200">
          <template #default="{ row }">
            <div class="product-name-cell">
              <span class="name">{{ row.name }}</span>
              <span class="spu-code">{{ row.sku }}</span>
            </div>
          </template>
        </el-table-column>
        <el-table-column label="规格" width="120">
          <template #default="{ row }">
            <el-tag v-if="row.has_specs" type="success" size="small">
              {{ row.sku_count }} 个SKU
            </el-tag>
            <el-tag v-else type="info" size="small">
              单规格
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="价格" width="140">
          <template #default="{ row }">
            <div v-if="row.has_specs" class="price-range">
              <span class="price">¥{{ row.min_price?.toFixed(2) }}</span>
              <span class="price-sep">~</span>
              <span class="price">¥{{ row.max_price?.toFixed(2) }}</span>
            </div>
            <div v-else class="price-single">
              <span class="price">¥{{ row.price?.toFixed(2) }}</span>
            </div>
          </template>
        </el-table-column>
        <el-table-column label="库存" width="120">
          <template #default="{ row }">
            <div class="stock-info">
              <span :class="{ 'low-stock': row.stock_quantity <= row.low_stock_threshold }">
                {{ row.stock_quantity }}
              </span>
              <span v-if="row.has_specs" class="stock-sub">
                ({{ row.sku_count }}个SKU)
              </span>
            </div>
          </template>
        </el-table-column>
        <el-table-column prop="status" label="状态" width="100">
          <template #default="{ row }">
            <el-tag :type="getStatusType(row.status)">
              {{ getStatusText(row.status) }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="操作" width="200">
          <template #default="{ row }">
            <el-button size="small" @click="handleEdit(row)">编辑</el-button>
            <el-button size="small" type="danger" @click="handleDelete(row)">删除</el-button>
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
        style="margin-top: 20px; justify-content: flex-end;"
      />
    </el-card>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import { productApi } from '@/api/modules/product'

const router = useRouter()
const products = ref([])
const loading = ref(false)
const currentPage = ref(1)
const pageSize = ref(10)
const total = ref(0)

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

const fetchProducts = async () => {
  loading.value = true
  try {
    const res = await productApi.getProducts({
      page: currentPage.value,
      per_page: pageSize.value,
    })
    products.value = res.data
    total.value = res.meta.total
  } catch (error) {
    ElMessage.error('获取商品列表失败')
  } finally {
    loading.value = false
  }
}

const handleEdit = (row) => {
  router.push(`/products/${row.id}/edit`)
}

const handleDelete = async (row) => {
  try {
    await ElMessageBox.confirm('确定要删除该商品吗？', '提示', {
      type: 'warning',
    })
    await productApi.deleteProduct(row.id)
    ElMessage.success('删除成功')
    fetchProducts()
  } catch (error) {
    if (error !== 'cancel') {
      ElMessage.error('删除失败')
    }
  }
}

const handleSizeChange = () => {
  fetchProducts()
}

const handleCurrentChange = () => {
  fetchProducts()
}

onMounted(() => {
  fetchProducts()
})
</script>

<style scoped>
.product-list {
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

.product-name-cell {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.product-name-cell .name {
  font-size: 14px;
  color: #111827;
  font-weight: 500;
}

.product-name-cell .spu-code {
  font-size: 12px;
  color: #9ca3af;
}

.price-range {
  display: flex;
  align-items: baseline;
  gap: 4px;
}

.price {
  font-size: 14px;
  font-weight: 500;
  color: #f56c6c;
}

.price-sep {
  color: #9ca3af;
  font-size: 12px;
}

.price-single .price {
  font-size: 14px;
  font-weight: 500;
  color: #f56c6c;
}

.stock-info {
  display: flex;
  align-items: center;
  gap: 4px;
}

.stock-info .low-stock {
  color: #e6a23c;
  font-weight: 500;
}

.stock-sub {
  font-size: 12px;
  color: #9ca3af;
}
</style>
