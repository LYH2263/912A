<template>
  <div class="product-form page-shell">
    <el-card>
      <template #header>
        <div class="card-header">
          <div class="card-header-text">
            <span class="card-title">{{ isEdit ? '编辑商品' : '新增商品' }}</span>
            <span class="card-subtitle">维护商品基础信息与库存状态</span>
          </div>
        </div>
      </template>
      
      <el-form
        ref="formRef"
        :model="form"
        :rules="rules"
        label-width="100px"
      >
        <el-tabs v-model="activeTab">
          <el-tab-pane label="基础信息" name="basic">
            <el-form-item label="商品名称" prop="name">
              <el-input v-model="form.name" placeholder="请输入商品名称" />
            </el-form-item>
            <el-form-item label="SPU编码" prop="sku">
              <el-input v-model="form.sku" placeholder="请输入SPU编码" />
            </el-form-item>
            <el-form-item label="分类" prop="category_id">
              <el-select v-model="form.category_id" placeholder="请选择分类" clearable style="width: 300px">
                <el-option
                  v-for="category in categories"
                  :key="category.id"
                  :label="category.name"
                  :value="category.id"
                />
              </el-select>
            </el-form-item>
            <el-form-item label="供应商" prop="supplier_id">
              <el-select v-model="form.supplier_id" placeholder="请选择供应商" clearable style="width: 300px">
                <el-option
                  v-for="supplier in suppliers"
                  :key="supplier.id"
                  :label="supplier.name"
                  :value="supplier.id"
                />
              </el-select>
            </el-form-item>
            <el-form-item label="商品描述" prop="description">
              <el-input
                v-model="form.description"
                type="textarea"
                :rows="4"
                placeholder="请输入商品描述"
              />
            </el-form-item>
            <el-form-item label="状态" prop="status">
              <el-select v-model="form.status" placeholder="请选择状态">
                <el-option label="上架" value="active" />
                <el-option label="下架" value="inactive" />
                <el-option label="售罄" value="sold_out" />
              </el-select>
            </el-form-item>
            <el-form-item label="商品标签">
              <el-select
                v-model="selectedTagIds"
                multiple
                filterable
                collapse-tags
                collapse-tags-tooltip
                placeholder="选择标签（可多选）"
                style="width: 100%"
              >
                <el-option
                  v-for="tag in allTags"
                  :key="tag.id"
                  :label="tag.name"
                  :value="tag.id"
                >
                  <span class="tag-option">
                    <span class="tag-color" :style="{ backgroundColor: tag.color }" />
                    {{ tag.name }}
                  </span>
                </el-option>
              </el-select>
              <div class="tag-hint">
                未找到合适的标签？<el-button type="primary" link @click="goToTagManager">前往管理标签</el-button>
              </div>
            </el-form-item>
          </el-tab-pane>

          <el-tab-pane label="规格与SKU" name="specs">
            <div class="spec-switch">
              <span>启用多规格</span>
              <el-switch v-model="enableSpecs" />
            </div>
            
            <div v-if="enableSpecs" class="spec-editor-wrapper">
              <SpecMatrixEditor v-model="specData" />
            </div>
            
            <div v-else class="no-spec-info">
              <el-alert
                title="未启用多规格"
                type="info"
                description="启用多规格后，可以配置颜色、尺码等规格维度，每个SKU独立管理库存与价格。"
                show-icon
                :closable="false"
              />
              <el-form-item label="价格" prop="price" style="margin-top: 20px">
                <el-input-number v-model="form.price" :min="0" :precision="2" />
              </el-form-item>
              <el-form-item label="成本价" prop="cost_price">
                <el-input-number v-model="form.cost_price" :min="0" :precision="2" />
              </el-form-item>
              <el-form-item label="库存数量" prop="stock_quantity">
                <el-input-number v-model="form.stock_quantity" :min="0" />
              </el-form-item>
              <el-form-item label="低库存预警" prop="low_stock_threshold">
                <el-input-number v-model="form.low_stock_threshold" :min="0" />
              </el-form-item>
            </div>

            <el-form-item v-if="isEdit" label="变更原因" prop="price_reason" class="price-reason-item">
              <el-input
                v-model="form.price_reason"
                type="textarea"
                :rows="2"
                placeholder="请输入本次价格变更的原因（选填）"
                maxlength="500"
                show-word-limit
              />
            </el-form-item>
          </el-tab-pane>

          <el-tab-pane v-if="isEdit" label="调价历史" name="history">
            <div class="price-history-section">
              <div class="history-header">
                <span class="history-title">价格变动记录</span>
                <el-button type="primary" link @click="loadPriceHistories">
                  <el-icon><Refresh /></el-icon>
                  刷新
                </el-button>
              </div>
              <el-table
                :data="priceHistories"
                v-loading="historyLoading"
                stripe
                style="width: 100%"
              >
                <el-table-column prop="created_at" label="变更时间" width="180">
                  <template #default="{ row }">
                    {{ formatDateTime(row.created_at) }}
                  </template>
                </el-table-column>
                <el-table-column label="SKU" width="200">
                  <template #default="{ row }">
                    <span v-if="row.sku">
                      <span class="sku-code">{{ row.sku.sku }}</span>
                      <span class="sku-spec" v-if="row.sku.spec_text">{{ row.sku.spec_text }}</span>
                    </span>
                    <span v-else class="no-sku">商品主价</span>
                  </template>
                </el-table-column>
                <el-table-column prop="old_price" label="旧价格" width="120" align="right">
                  <template #default="{ row }">
                    ¥{{ row.old_price.toFixed(2) }}
                  </template>
                </el-table-column>
                <el-table-column prop="new_price" label="新价格" width="120" align="right">
                  <template #default="{ row }">
                    ¥{{ row.new_price.toFixed(2) }}
                  </template>
                </el-table-column>
                <el-table-column label="变动" width="140" align="right">
                  <template #default="{ row }">
                    <span :class="row.price_change >= 0 ? 'price-up' : 'price-down'">
                      {{ row.price_change >= 0 ? '+' : '' }}{{ row.price_change.toFixed(2) }}
                      ({{ row.price_change_percent >= 0 ? '+' : '' }}{{ row.price_change_percent }}%)
                    </span>
                  </template>
                </el-table-column>
                <el-table-column prop="reason" label="变更原因" min-width="180">
                  <template #default="{ row }">
                    <span v-if="row.reason">{{ row.reason }}</span>
                    <span v-else class="text-muted">-</span>
                  </template>
                </el-table-column>
                <el-table-column label="操作人" width="120">
                  <template #default="{ row }">
                    <span v-if="row.operator">{{ row.operator.name }}</span>
                    <span v-else class="text-muted">系统</span>
                  </template>
                </el-table-column>
              </el-table>
              <div v-if="priceHistories.length === 0 && !historyLoading" class="empty-history">
                <el-empty description="暂无调价记录" />
              </div>
            </div>
          </el-tab-pane>
        </el-tabs>

        <el-form-item>
          <el-button type="primary" @click="handleSubmit" :loading="loading">
            保存
          </el-button>
          <el-button @click="$router.back()">取消</el-button>
        </el-form-item>
      </el-form>
    </el-card>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import { Refresh } from '@element-plus/icons-vue'
import { productApi } from '@/api/modules/product'
import { supplierApi } from '@/api/modules/supplier'
import { tagApi } from '@/api/modules/tag'
import SpecMatrixEditor from '@/components/product/SpecMatrixEditor.vue'
import dayjs from 'dayjs'

const route = useRoute()
const router = useRouter()
const formRef = ref(null)
const loading = ref(false)
const isEdit = ref(false)
const activeTab = ref('basic')
const enableSpecs = ref(false)
const categories = ref([])
const suppliers = ref([])
const allTags = ref([])
const selectedTagIds = ref([])
const priceHistories = ref([])
const historyLoading = ref(false)

const form = reactive({
  name: '',
  sku: '',
  category_id: null,
  supplier_id: null,
  description: '',
  price: 0,
  cost_price: null,
  image: '',
  images: [],
  stock_quantity: 0,
  low_stock_threshold: 10,
  weight: null,
  status: 'active',
  price_reason: '',
})

const specData = reactive({
  specs: [],
  skus: [],
})

const rules = {
  name: [{ required: true, message: '请输入商品名称', trigger: 'blur' }],
  sku: [{ required: true, message: '请输入SPU编码', trigger: 'blur' }],
}

const formatDateTime = (dateStr) => {
  return dateStr ? dayjs(dateStr).format('YYYY-MM-DD HH:mm:ss') : '-'
}

const fetchSuppliers = async () => {
  try {
    const res = await supplierApi.getAllSuppliers({ status: 'active' })
    suppliers.value = res.data
  } catch (e) {
    console.error('获取供应商列表失败', e)
  }
}

const fetchAllTags = async () => {
  try {
    const res = await tagApi.getAllTags()
    allTags.value = res.data
  } catch (e) {
    console.error('获取标签列表失败', e)
  }
}

const goToTagManager = () => {
  const routeData = router.resolve({ path: '/tags' })
  window.open(routeData.href, '_blank')
}

const loadPriceHistories = async () => {
  if (!route.params.id) return
  historyLoading.value = true
  try {
    const res = await productApi.getPriceHistories(route.params.id, { per_page: 50 })
    priceHistories.value = res.data || []
  } catch (e) {
    console.error('获取调价历史失败', e)
  } finally {
    historyLoading.value = false
  }
}

const handleSubmit = async () => {
  if (!formRef.value) return
  
  await formRef.value.validate(async (valid) => {
    if (valid) {
      loading.value = true
      try {
        const submitData = { ...form }
        
        submitData.tag_ids = selectedTagIds.value

        if (enableSpecs.value) {
          if (specData.specs.length === 0) {
            ElMessage.warning('请至少添加一个规格')
            loading.value = false
            return
          }
          if (specData.skus.length === 0) {
            ElMessage.warning('请添加规格值以生成SKU')
            loading.value = false
            return
          }
          
          const invalidSku = specData.skus.find((sku) => !sku.sku)
          if (invalidSku) {
            ElMessage.warning('请填写所有SKU编码')
            loading.value = false
            return
          }
          
          submitData.specs = specData.specs
          submitData.skus = specData.skus
          
          const prices = specData.skus.map((s) => parseFloat(s.price))
          submitData.price = Math.min(...prices)
          submitData.stock_quantity = specData.skus.reduce((sum, s) => sum + parseInt(s.stock_quantity || 0), 0)
        }

        if (isEdit.value) {
          await productApi.updateProduct(route.params.id, submitData)
          ElMessage.success('更新成功')
        } else {
          await productApi.createProduct(submitData)
          ElMessage.success('创建成功')
        }
        router.push('/products')
      } catch (error) {
        ElMessage.error(error.response?.data?.message || error.message || '操作失败')
      } finally {
        loading.value = false
      }
    }
  })
}

const loadProduct = async () => {
  if (route.params.id) {
    isEdit.value = true
    try {
      const res = await productApi.getProduct(route.params.id)
      const product = res.data
      Object.assign(form, product)
      
      if (product.tags && product.tags.length > 0) {
        selectedTagIds.value = product.tags.map((t) => t.id)
      }

      if (product.specs && product.specs.length > 0) {
        enableSpecs.value = true
        specData.specs = product.specs.map((s) => ({
          name: s.name,
          values: s.values.map((v) => v.value),
        }))
        specData.skus = product.skus || []
      } else {
        enableSpecs.value = false
      }

      loadPriceHistories()
    } catch (error) {
      ElMessage.error('获取商品信息失败')
    }
  }
}

onMounted(() => {
  fetchSuppliers()
  fetchAllTags()
  loadProduct()
})
</script>

<style scoped>
.product-form {
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

.spec-switch {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 16px;
  font-size: 14px;
  font-weight: 500;
  color: #374151;
}

.spec-editor-wrapper {
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 0 16px;
  background: #fafafa;
}

.no-spec-info {
  max-width: 600px;
}

.tag-option {
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.tag-color {
  width: 14px;
  height: 14px;
  border-radius: 3px;
  display: inline-block;
}

.tag-hint {
  margin-top: 6px;
  font-size: 12px;
  color: #6b7280;
}

.price-reason-item {
  margin-top: 24px;
}

.price-history-section {
  padding: 8px 0;
}

.history-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.history-title {
  font-size: 14px;
  font-weight: 600;
  color: #374151;
}

.sku-code {
  font-family: monospace;
  font-size: 12px;
  color: #4f46e5;
  background: #eef2ff;
  padding: 2px 6px;
  border-radius: 4px;
  margin-right: 8px;
}

.sku-spec {
  font-size: 12px;
  color: #6b7280;
}

.no-sku {
  font-size: 12px;
  color: #9ca3af;
}

.price-up {
  color: #ef4444;
  font-weight: 500;
}

.price-down {
  color: #10b981;
  font-weight: 500;
}

.text-muted {
  color: #9ca3af;
}

.empty-history {
  padding: 40px 0;
}
</style>
