<template>
  <div class="review-list page-shell">
    <el-card>
      <template #header>
        <div class="card-header">
          <div class="card-header-text">
            <span class="card-title">评价管理</span>
            <span class="card-subtitle">管理商品评价、审核与展示状态</span>
          </div>
          <el-button type="primary" @click="openCreateDialog" round>
            代录评价
          </el-button>
        </div>
      </template>

      <el-row :gutter="16" class="stat-row">
        <el-col :span="6">
          <div class="stat-card pending">
            <div class="stat-label">待审核</div>
            <div class="stat-value">{{ statistics.pending || 0 }}</div>
          </div>
        </el-col>
        <el-col :span="6">
          <div class="stat-card approved">
            <div class="stat-label">已通过</div>
            <div class="stat-value">{{ statistics.approved || 0 }}</div>
          </div>
        </el-col>
        <el-col :span="6">
          <div class="stat-card rejected">
            <div class="stat-label">已拒绝</div>
            <div class="stat-value">{{ statistics.rejected || 0 }}</div>
          </div>
        </el-col>
        <el-col :span="6">
          <div class="stat-card avg">
            <div class="stat-label">平均评分</div>
            <div class="stat-value">
              {{ statistics.avg_rating || 0 }}
              <span class="stat-unit">星</span>
            </div>
          </div>
        </el-col>
      </el-row>

      <div class="filter-bar">
        <el-input
          v-model="searchText"
          placeholder="搜索评价内容/评价人"
          clearable
          style="width: 220px"
          @clear="fetchReviews"
          @keyup.enter="fetchReviews"
        />
        <el-select
          v-model="filterProductId"
          placeholder="选择商品"
          clearable
          filterable
          style="width: 220px"
          @change="fetchReviews"
        >
          <el-option
            v-for="product in productList"
            :key="product.id"
            :label="`${product.name} (${product.sku})`"
            :value="product.id"
          />
        </el-select>
        <el-select
          v-model="filterStatus"
          placeholder="状态"
          clearable
          style="width: 120px"
          @change="fetchReviews"
        >
          <el-option label="待审核" value="pending" />
          <el-option label="已通过" value="approved" />
          <el-option label="已拒绝" value="rejected" />
          <el-option label="已隐藏" value="hidden" />
        </el-select>
        <el-select
          v-model="filterRating"
          placeholder="评分"
          clearable
          style="width: 120px"
          @change="fetchReviews"
        >
          <el-option v-for="i in 5" :key="i" :label="`${i} 星`" :value="i" />
        </el-select>
        <el-button type="primary" @click="fetchReviews">查询</el-button>
      </div>

      <el-table :data="reviews" v-loading="loading" style="width: 100%">
        <el-table-column label="商品" width="200">
          <template #default="{ row }">
            <div v-if="row.product">
              <div class="product-name">{{ row.product.name }}</div>
              <div class="product-sku">{{ row.product.sku }}</div>
            </div>
          </template>
        </el-table-column>
        <el-table-column label="评分" width="140">
          <template #default="{ row }">
            <el-rate
              :model-value="row.rating"
              disabled
              :max="5"
              size="small"
            />
            <span class="rating-text">{{ row.rating }}星</span>
          </template>
        </el-table-column>
        <el-table-column label="评价人" width="140">
          <template #default="{ row }">
            {{ row.reviewer_name || (row.user ? row.user.name || row.user.email : '匿名') }}
          </template>
        </el-table-column>
        <el-table-column prop="content" label="评价内容" min-width="240" show-overflow-tooltip />
        <el-table-column label="状态" width="100">
          <template #default="{ row }">
            <el-tag :type="getStatusType(row.status)">
              {{ row.status_text }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="审核信息" width="160">
          <template #default="{ row }">
            <div v-if="row.reviewer">
              <div class="review-info">
                {{ row.reviewer.name || row.reviewer.email }}
              </div>
              <div class="review-time">{{ formatTime(row.reviewed_at) }}</div>
            </div>
            <span v-else>-</span>
          </template>
        </el-table-column>
        <el-table-column label="创建时间" width="160">
          <template #default="{ row }">
            {{ formatTime(row.created_at) }}
          </template>
        </el-table-column>
        <el-table-column label="操作" width="240" fixed="right">
          <template #default="{ row }">
            <template v-if="row.status === 'pending'">
              <el-button size="small" type="success" @click="handleApprove(row)">
                通过
              </el-button>
              <el-button size="small" type="warning" @click="handleReject(row)">
                拒绝
              </el-button>
            </template>
            <template v-else>
              <el-button
                size="small"
                :type="row.status === 'hidden' ? 'success' : 'info'"
                @click="handleToggleVisibility(row)"
              >
                {{ row.status === 'hidden' ? '展示' : '隐藏' }}
              </el-button>
            </template>
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
        style="margin-top: 20px; justify-content: flex-end"
      />
    </el-card>

    <el-dialog
      v-model="dialogVisible"
      :title="dialogTitle"
      width="600px"
      destroy-on-close
    >
      <el-form
        ref="formRef"
        :model="formData"
        :rules="formRules"
        label-width="90px"
      >
        <el-form-item label="关联商品" prop="product_id">
          <el-select
            v-model="formData.product_id"
            placeholder="请选择商品"
            filterable
            style="width: 100%"
          >
            <el-option
              v-for="product in productList"
              :key="product.id"
              :label="`${product.name} (${product.sku})`"
              :value="product.id"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="评价人" prop="reviewer_name">
          <el-input
            v-model="formData.reviewer_name"
            placeholder="评价人名称（代录必填）"
          />
        </el-form-item>
        <el-form-item label="评分" prop="rating">
          <el-rate v-model="formData.rating" :max="5" show-score />
        </el-form-item>
        <el-form-item label="评价内容" prop="content">
          <el-input
            v-model="formData.content"
            type="textarea"
            :rows="4"
            maxlength="1000"
            show-word-limit
            placeholder="请输入评价内容"
          />
        </el-form-item>
        <el-form-item label="状态" prop="status" v-if="!formId">
          <el-radio-group v-model="formData.status">
            <el-radio value="pending">待审核</el-radio>
            <el-radio value="approved">直接通过</el-radio>
          </el-radio-group>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="submitLoading" @click="handleSubmit">
          确定
        </el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { reviewApi } from '@/api/modules/review'
import { productApi } from '@/api/modules/product'
import dayjs from 'dayjs'

const reviews = ref([])
const loading = ref(false)
const currentPage = ref(1)
const pageSize = ref(10)
const total = ref(0)
const searchText = ref('')
const filterStatus = ref('')
const filterRating = ref('')
const filterProductId = ref('')
const statistics = ref({})
const productList = ref([])

const dialogVisible = ref(false)
const submitLoading = ref(false)
const formRef = ref(null)
const formId = ref(null)
const formData = reactive({
  product_id: null,
  reviewer_name: '',
  rating: 5,
  content: '',
  status: 'pending',
})

const dialogTitle = computed(() => (formId.value ? '编辑评价' : '代录评价'))

const formRules = {
  product_id: [{ required: true, message: '请选择商品', trigger: 'change' }],
  reviewer_name: [{ required: true, message: '请输入评价人名称', trigger: 'blur' }],
  rating: [{ required: true, message: '请选择评分', trigger: 'change' }],
}

const getStatusType = (status) => {
  const map = {
    pending: 'warning',
    approved: 'success',
    rejected: 'danger',
    hidden: 'info',
  }
  return map[status] || 'info'
}

const formatTime = (time) => {
  return time ? dayjs(time).format('YYYY-MM-DD HH:mm') : '-'
}

const fetchStatistics = async () => {
  try {
    const res = await reviewApi.getStatistics()
    statistics.value = res.data
  } catch (error) {
    console.error('获取评价统计失败', error)
  }
}

const fetchProductList = async () => {
  try {
    const res = await productApi.getProducts({ per_page: 1000 })
    productList.value = res.data
  } catch (error) {
    ElMessage.error('获取商品列表失败')
  }
}

const fetchReviews = async () => {
  loading.value = true
  try {
    const params = {
      page: currentPage.value,
      per_page: pageSize.value,
    }
    if (searchText.value) params.search = searchText.value
    if (filterStatus.value) params.status = filterStatus.value
    if (filterRating.value) params.rating = filterRating.value
    if (filterProductId.value) params.product_id = filterProductId.value

    const res = await reviewApi.getReviews(params)
    reviews.value = res.data
    total.value = res.meta.total
  } catch (error) {
    ElMessage.error('获取评价列表失败')
  } finally {
    loading.value = false
  }
}

const openCreateDialog = () => {
  formId.value = null
  Object.assign(formData, {
    product_id: null,
    reviewer_name: '',
    rating: 5,
    content: '',
    status: 'pending',
  })
  dialogVisible.value = true
}

const handleEdit = (row) => {
  formId.value = row.id
  Object.assign(formData, {
    product_id: row.product_id,
    reviewer_name: row.reviewer_name || '',
    rating: row.rating,
    content: row.content || '',
  })
  dialogVisible.value = true
}

const handleSubmit = async () => {
  if (!formRef.value) return
  await formRef.value.validate(async (valid) => {
    if (!valid) return
    submitLoading.value = true
    try {
      const payload = {
        product_id: formData.product_id,
        reviewer_name: formData.reviewer_name,
        rating: formData.rating,
        content: formData.content,
      }
      if (!formId.value) {
        payload.status = formData.status
        await reviewApi.createReview(payload)
        ElMessage.success('评价创建成功')
      } else {
        await reviewApi.updateReview(formId.value, payload)
        ElMessage.success('评价更新成功')
      }
      dialogVisible.value = false
      fetchReviews()
      fetchStatistics()
    } catch (error) {
      ElMessage.error(error.response?.data?.message || '操作失败')
    } finally {
      submitLoading.value = false
    }
  })
}

const handleApprove = async (row) => {
  try {
    await ElMessageBox.confirm('确定通过该评价吗？', '提示', { type: 'warning' })
    await reviewApi.approveReview(row.id)
    ElMessage.success('已通过')
    fetchReviews()
    fetchStatistics()
  } catch (error) {
    if (error !== 'cancel') {
      ElMessage.error(error.response?.data?.message || '操作失败')
    }
  }
}

const handleReject = async (row) => {
  try {
    await ElMessageBox.confirm('确定拒绝该评价吗？', '提示', { type: 'warning' })
    await reviewApi.rejectReview(row.id)
    ElMessage.success('已拒绝')
    fetchReviews()
    fetchStatistics()
  } catch (error) {
    if (error !== 'cancel') {
      ElMessage.error(error.response?.data?.message || '操作失败')
    }
  }
}

const handleToggleVisibility = async (row) => {
  try {
    await reviewApi.toggleVisibility(row.id)
    ElMessage.success(row.status === 'hidden' ? '已展示' : '已隐藏')
    fetchReviews()
    fetchStatistics()
  } catch (error) {
    ElMessage.error(error.response?.data?.message || '操作失败')
  }
}

const handleDelete = async (row) => {
  try {
    await ElMessageBox.confirm('确定要删除该评价吗？', '提示', { type: 'warning' })
    await reviewApi.deleteReview(row.id)
    ElMessage.success('删除成功')
    fetchReviews()
    fetchStatistics()
  } catch (error) {
    if (error !== 'cancel') {
      ElMessage.error(error.response?.data?.message || '删除失败')
    }
  }
}

const handleSizeChange = () => {
  currentPage.value = 1
  fetchReviews()
}

const handleCurrentChange = () => {
  fetchReviews()
}

onMounted(() => {
  fetchProductList()
  fetchReviews()
  fetchStatistics()
})
</script>

<style scoped>
.review-list {
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

.stat-row {
  margin-bottom: 20px;
}

.stat-card {
  padding: 16px 20px;
  border-radius: 12px;
  color: #fff;
}

.stat-card.pending {
  background: linear-gradient(135deg, #f59e0b, #fbbf24);
}

.stat-card.approved {
  background: linear-gradient(135deg, #10b981, #34d399);
}

.stat-card.rejected {
  background: linear-gradient(135deg, #ef4444, #f87171);
}

.stat-card.avg {
  background: linear-gradient(135deg, #6366f1, #818cf8);
}

.stat-label {
  font-size: 12px;
  opacity: 0.9;
  margin-bottom: 6px;
}

.stat-value {
  font-size: 24px;
  font-weight: 700;
}

.stat-unit {
  font-size: 14px;
  font-weight: 500;
  margin-left: 2px;
}

.filter-bar {
  display: flex;
  gap: 12px;
  margin-bottom: 16px;
  align-items: center;
  flex-wrap: wrap;
}

.product-name {
  font-size: 14px;
  font-weight: 500;
  color: #111827;
}

.product-sku {
  font-size: 12px;
  color: #9ca3af;
  margin-top: 2px;
}

.rating-text {
  margin-left: 8px;
  font-size: 12px;
  color: #6b7280;
}

.review-info {
  font-size: 12px;
  color: #374151;
}

.review-time {
  font-size: 11px;
  color: #9ca3af;
  margin-top: 2px;
}
</style>
