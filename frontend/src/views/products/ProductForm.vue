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
import { ref, reactive, onMounted, watch, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import { productApi } from '@/api/modules/product'
import { supplierApi } from '@/api/modules/supplier'
import SpecMatrixEditor from '@/components/product/SpecMatrixEditor.vue'

const route = useRoute()
const router = useRouter()
const formRef = ref(null)
const loading = ref(false)
const isEdit = ref(false)
const activeTab = ref('basic')
const enableSpecs = ref(false)
const categories = ref([])
const suppliers = ref([])

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
})

const specData = reactive({
  specs: [],
  skus: [],
})

const rules = {
  name: [{ required: true, message: '请输入商品名称', trigger: 'blur' }],
  sku: [{ required: true, message: '请输入SPU编码', trigger: 'blur' }],
}

const fetchCategories = async () => {
  try {
    const { default: Category } = await import('@/api/modules/product.js')
  } catch (e) {
  }
}

const fetchSuppliers = async () => {
  try {
    const res = await supplierApi.getAllSuppliers({ status: 'active' })
    suppliers.value = res.data
  } catch (e) {
    console.error('获取供应商列表失败', e)
  }
}

const handleSubmit = async () => {
  if (!formRef.value) return
  
  await formRef.value.validate(async (valid) => {
    if (valid) {
      loading.value = true
      try {
        const submitData = { ...form }
        
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
    } catch (error) {
      ElMessage.error('获取商品信息失败')
    }
  }
}

onMounted(() => {
  fetchSuppliers()
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
</style>
