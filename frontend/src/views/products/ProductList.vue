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

      <div class="filter-bar">
        <el-select v-model="supplierFilter" placeholder="供应商" clearable style="width: 200px" @change="handleSearch">
          <el-option
            v-for="supplier in suppliers"
            :key="supplier.id"
            :label="supplier.name"
            :value="supplier.id"
          />
        </el-select>
      </div>
      
      <el-table :data="products" v-loading="loading" style="width: 100%; margin-top: 16px">
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
        <el-table-column label="供应商" width="160">
          <template #default="{ row }">
            <span v-if="row.supplier" class="supplier-name">
              {{ row.supplier.name }}
            </span>
            <span v-else class="supplier-empty">
              未关联
            </span>
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
        <el-table-column label="评价汇总" width="200">
          <template #default="{ row }">
            <div class="review-summary-cell" @click="openReviewDrawer(row)">
              <div class="review-score">
                <el-rate
                  :model-value="getReviewSummary(row.id).avg_rating"
                  disabled
                  :max="5"
                  size="small"
                />
                <span class="avg-rating">{{ getReviewSummary(row.id).avg_rating || 0 }}</span>
              </div>
              <div class="review-count">
                {{ getReviewSummary(row.id).total_count || 0 }} 条评价
              </div>
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
        <el-table-column label="操作" width="240" fixed="right">
          <template #default="{ row }">
            <el-button size="small" type="primary" plain @click="openReviewDrawer(row)">
              评价
            </el-button>
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

    <el-drawer
      v-model="drawerVisible"
      :title="drawerTitle"
      direction="rtl"
      size="480px"
    >
      <div v-if="currentProduct" class="review-drawer">
        <div class="drawer-product-info">
          <div class="product-name">{{ currentProduct.name }}</div>
          <div class="product-sku">{{ currentProduct.sku }}</div>
        </div>

        <div class="summary-card">
          <div class="summary-main">
            <div class="avg-score-box">
              <span class="avg-score">{{ reviewSummary.avg_rating || 0 }}</span>
              <span class="score-unit">/5.0</span>
            </div>
            <div class="avg-rate">
              <el-rate
                :model-value="reviewSummary.avg_rating"
                disabled
                :max="5"
                size="default"
              />
            </div>
            <div class="total-count">共 {{ reviewSummary.total_count || 0 }} 条评价</div>
          </div>
          <div class="summary-distribution">
            <div
              v-for="(item, star) in reviewSummary.distribution"
              :key="star"
              class="distribution-item"
            >
              <span class="star-label">{{ star }}星</span>
              <el-progress
                :percentage="item.percent"
                :show-text="false"
                :stroke-width="8"
                color="#f59e0b"
              />
              <span class="count-label">{{ item.count }}</span>
            </div>
          </div>
        </div>

        <div class="review-list-section">
          <div class="section-title">用户评价</div>
          <div v-if="reviewLoading" class="loading-wrap">
            <el-skeleton :rows="3" animated />
          </div>
          <div v-else-if="productReviews.length === 0" class="empty-wrap">
            <el-empty description="暂无评价" />
          </div>
          <div v-else class="review-items">
            <div
              v-for="review in productReviews"
              :key="review.id"
              class="review-item"
            >
              <div class="review-header">
                <div class="reviewer-info">
                  <div class="reviewer-avatar">
                    {{ getAvatarInitial(review) }}
                  </div>
                  <div class="reviewer-text">
                    <div class="reviewer-name">
                      {{ review.reviewer_name || (review.user ? (review.user.name || review.user.email) : '匿名用户') }}
                    </div>
                    <div class="review-time">{{ formatTime(review.created_at) }}</div>
                  </div>
                </div>
                <el-rate
                  :model-value="review.rating"
                  disabled
                  :max="5"
                  size="small"
                />
              </div>
              <div v-if="review.content" class="review-content">
                {{ review.content }}
              </div>
            </div>
          </div>
          <el-pagination
            v-if="reviewTotal > 10"
            v-model:current-page="reviewPage"
            v-model:page-size="reviewPageSize"
            :total="reviewTotal"
            layout="prev, pager, next"
            small
            @current-change="fetchProductReviews"
            style="margin-top: 16px; justify-content: center"
          />
        </div>
      </div>
    </el-drawer>
  </div>
</template>

<script setup>
import { ref, onMounted, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import { productApi } from '@/api/modules/product'
import { reviewApi } from '@/api/modules/review'
import { supplierApi } from '@/api/modules/supplier'
import dayjs from 'dayjs'

const router = useRouter()
const products = ref([])
const suppliers = ref([])
const loading = ref(false)
const currentPage = ref(1)
const pageSize = ref(10)
const total = ref(0)
const supplierFilter = ref('')

const productsSummaryMap = reactive({})
const drawerVisible = ref(false)
const currentProduct = ref(null)
const reviewSummary = ref({ distribution: {} })
const productReviews = ref([])
const reviewLoading = ref(false)
const reviewPage = ref(1)
const reviewPageSize = ref(10)
const reviewTotal = ref(0)

const drawerTitle = ref('商品评价详情')

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

const getReviewSummary = (productId) => {
  return productsSummaryMap[productId] || { avg_rating: 0, total_count: 0, distribution: {} }
}

const getAvatarInitial = (review) => {
  const name = review.reviewer_name || (review.user ? (review.user.name || review.user.email) : 'A')
  return name.charAt(0).toUpperCase()
}

const formatTime = (time) => {
  return time ? dayjs(time).format('YYYY-MM-DD HH:mm') : ''
}

const fetchSuppliers = async () => {
  try {
    const res = await supplierApi.getAllSuppliers({ status: 'active' })
    suppliers.value = res.data
  } catch (error) {
    console.error('获取供应商列表失败', error)
  }
}

const fetchProducts = async () => {
  loading.value = true
  try {
    const params = {
      page: currentPage.value,
      per_page: pageSize.value,
    }
    if (supplierFilter.value) {
      params.supplier_id = supplierFilter.value
    }
    const res = await productApi.getProducts(params)
    products.value = res.data
    total.value = res.meta.total

    const productIds = products.value.map((p) => p.id)
    if (productIds.length > 0) {
      fetchProductsSummary(productIds)
    }
  } catch (error) {
    ElMessage.error('获取商品列表失败')
  } finally {
    loading.value = false
  }
}

const handleSearch = () => {
  currentPage.value = 1
  fetchProducts()
}

const fetchProductsSummary = async (productIds) => {
  try {
    const res = await reviewApi.getProductsSummary({ product_ids: productIds.join(',') })
    res.data.forEach((item) => {
      productsSummaryMap[item.product_id] = item
    })
  } catch (error) {
    console.error('获取商品评价汇总失败', error)
  }
}

const openReviewDrawer = async (product) => {
  currentProduct.value = product
  drawerTitle.value = `${product.name} - 评价详情`
  drawerVisible.value = true
  reviewPage.value = 1

  try {
    const [summaryRes] = await Promise.all([
      reviewApi.getProductSummary(product.id),
    ])
    reviewSummary.value = summaryRes.data
  } catch (error) {
    ElMessage.error('获取评价汇总失败')
  }

  fetchProductReviews()
}

const fetchProductReviews = async () => {
  if (!currentProduct.value) return
  reviewLoading.value = true
  try {
    const res = await reviewApi.getProductReviews(currentProduct.value.id, {
      page: reviewPage.value,
      per_page: reviewPageSize.value,
    })
    productReviews.value = res.data
    reviewTotal.value = res.meta.total
  } catch (error) {
    ElMessage.error('获取评价列表失败')
  } finally {
    reviewLoading.value = false
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
  currentPage.value = 1
  fetchProducts()
}

const handleCurrentChange = () => {
  fetchProducts()
}

onMounted(() => {
  fetchSuppliers()
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

.filter-bar {
  display: flex;
  gap: 12px;
  align-items: center;
}

.supplier-name {
  font-size: 14px;
  color: #4c6fff;
  font-weight: 500;
}

.supplier-empty {
  font-size: 14px;
  color: #9ca3af;
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

.review-summary-cell {
  cursor: pointer;
  padding: 4px 0;
}

.review-summary-cell:hover {
  opacity: 0.8;
}

.review-score {
  display: flex;
  align-items: center;
  gap: 6px;
}

.avg-rating {
  font-size: 14px;
  font-weight: 600;
  color: #f59e0b;
}

.review-count {
  font-size: 12px;
  color: #6b7280;
  margin-top: 2px;
}

.review-drawer {
  padding: 0 4px;
}

.drawer-product-info {
  padding-bottom: 16px;
  border-bottom: 1px solid #e5e7eb;
  margin-bottom: 20px;
}

.drawer-product-info .product-name {
  font-size: 18px;
  font-weight: 600;
  color: #111827;
}

.drawer-product-info .product-sku {
  font-size: 13px;
  color: #9ca3af;
  margin-top: 4px;
}

.summary-card {
  background: linear-gradient(135deg, #fff7ed 0%, #fef3c7 100%);
  border-radius: 16px;
  padding: 20px;
  margin-bottom: 24px;
}

.summary-main {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-bottom: 16px;
}

.avg-score-box {
  display: flex;
  align-items: baseline;
}

.avg-score {
  font-size: 40px;
  font-weight: 700;
  color: #d97706;
  line-height: 1;
}

.score-unit {
  font-size: 14px;
  color: #9ca3af;
  margin-left: 2px;
}

.avg-rate {
  flex: 1;
}

.total-count {
  font-size: 13px;
  color: #6b7280;
}

.summary-distribution {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.distribution-item {
  display: flex;
  align-items: center;
  gap: 10px;
}

.star-label {
  font-size: 12px;
  color: #6b7280;
  width: 36px;
  flex-shrink: 0;
}

.distribution-item .el-progress {
  flex: 1;
}

.count-label {
  font-size: 12px;
  color: #9ca3af;
  width: 30px;
  flex-shrink: 0;
  text-align: right;
}

.review-list-section .section-title {
  font-size: 15px;
  font-weight: 600;
  color: #111827;
  margin-bottom: 16px;
}

.loading-wrap {
  padding: 20px 0;
}

.empty-wrap {
  padding: 40px 0;
}

.review-items {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.review-item {
  padding: 16px;
  background: #f9fafb;
  border-radius: 12px;
}

.review-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 10px;
}

.reviewer-info {
  display: flex;
  align-items: center;
  gap: 10px;
}

.reviewer-avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: linear-gradient(135deg, #4c6fff, #7c91ff);
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  font-size: 14px;
  flex-shrink: 0;
}

.reviewer-text {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.reviewer-name {
  font-size: 14px;
  font-weight: 500;
  color: #111827;
}

.review-time {
  font-size: 12px;
  color: #9ca3af;
}

.review-content {
  font-size: 14px;
  color: #4b5563;
  line-height: 1.6;
  padding: 8px 12px;
  background: #fff;
  border-radius: 8px;
}
</style>
