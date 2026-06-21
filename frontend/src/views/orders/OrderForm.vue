<template>
  <div class="order-form page-shell">
    <el-card>
      <template #header>
        <div class="card-header">
          <div class="card-header-text">
            <span class="card-title">创建订单</span>
            <span class="card-subtitle">选择商品、设置数量并填写收货信息</span>
          </div>
          <el-button @click="$router.back()" round>返回</el-button>
        </div>
      </template>

      <el-form
        ref="formRef"
        :model="form"
        :rules="rules"
        label-width="120px"
      >
        <el-form-item label="订单商品" prop="items">
          <el-table :data="form.items" border style="width: 100%">
            <el-table-column label="商品" width="280">
              <template #default="{ row, $index }">
                <el-select
                  v-model="row.product_id"
                  placeholder="请选择商品"
                  filterable
                  @change="handleProductChange($index)"
                  style="width: 100%"
                >
                  <el-option
                    v-for="product in availableProducts"
                    :key="product.id"
                    :label="`${product.name} (${product.sku})`"
                    :value="product.id"
                    :disabled="product.stock_quantity === 0 || product.status !== 'active'"
                  />
                </el-select>
              </template>
            </el-table-column>
            <el-table-column label="规格" width="220">
              <template #default="{ row, $index }">
                <el-select
                  v-if="hasSpecs(row.product_id)"
                  v-model="row.product_sku_id"
                  placeholder="请选择规格"
                  @change="handleSkuChange($index)"
                  style="width: 100%"
                >
                  <el-option
                    v-for="sku in getProductSkus(row.product_id)"
                    :key="sku.id"
                    :label="`${sku.spec_text || sku.sku} - 库存:${sku.stock_quantity}`"
                    :value="sku.id"
                    :disabled="sku.stock_quantity === 0 || sku.status !== 'active'"
                  />
                </el-select>
                <span v-else class="no-spec-tip">单规格</span>
              </template>
            </el-table-column>
            <el-table-column label="单价" width="120">
              <template #default="{ row }">
                <span v-if="getItemPrice(row) !== null">
                  ¥{{ getItemPrice(row).toFixed(2) }}
                </span>
                <span v-else>-</span>
              </template>
            </el-table-column>
            <el-table-column label="数量" width="150">
              <template #default="{ row, $index }">
                <el-input-number
                  v-model="row.quantity"
                  :min="1"
                  :max="getItemMaxStock(row)"
                  @change="calculateTotal"
                />
              </template>
            </el-table-column>
            <el-table-column label="小计" width="120">
              <template #default="{ row }">
                <span v-if="row.product_id && row.quantity">
                  ¥{{ (getItemPrice(row) * row.quantity).toFixed(2) }}
                </span>
                <span v-else>-</span>
              </template>
            </el-table-column>
            <el-table-column label="操作" width="100">
              <template #default="{ $index }">
                <el-button
                  type="danger"
                  size="small"
                  @click="removeItem($index)"
                  :disabled="form.items.length === 1"
                >
                  删除
                </el-button>
              </template>
            </el-table-column>
          </el-table>
          <el-button
            type="primary"
            @click="addItem"
            style="margin-top: 10px"
          >
            添加商品
          </el-button>
        </el-form-item>

        <el-form-item label="选择优惠券">
          <el-select
            v-model="form.coupon_id"
            placeholder="请选择可用优惠券"
            clearable
            style="width: 400px"
            @change="handleCouponChange"
          >
            <el-option
              v-for="coupon in availableCoupons"
              :key="coupon.id"
              :label="getCouponLabel(coupon)"
              :value="coupon.id"
            />
          </el-select>
          <div v-if="selectedCouponInfo" class="coupon-info">
            <el-tag type="success" size="small">
              {{ selectedCouponInfo.type === 'fixed' ? '固定金额' : '折扣比例' }}
            </el-tag>
            <span>
              {{ selectedCouponInfo.type === 'fixed' ? `减 ¥${selectedCouponInfo.value.toFixed(2)}` : `${selectedCouponInfo.value}% OFF` }}
            </span>
            <span v-if="selectedCouponInfo.min_amount > 0" style="color: #e6a23c; font-size: 12px">
              (满 ¥{{ selectedCouponInfo.min_amount.toFixed(2) }} 可用)
            </span>
          </div>
          <div v-if="calculationError" class="coupon-error">
            {{ calculationError }}
          </div>
        </el-form-item>

        <el-form-item label="订单金额">
          <div style="font-size: 16px">
            <span>商品总额：¥{{ totalAmount.toFixed(2) }}</span>
            <el-divider direction="vertical" />
            <span>优惠券抵扣：
              <span v-if="calculationResult && calculationResult.discount_amount > 0" style="color: #67c23a">-¥{{ calculationResult.discount_amount.toFixed(2) }}</span>
              <span v-else style="color: #909399">¥0.00</span>
            </span>
            <el-divider direction="vertical" />
            <span style="font-weight: bold; color: #f56c6c; font-size: 18px">
              实付金额：¥{{ finalAmount.toFixed(2) }}
            </span>
          </div>
        </el-form-item>

        <el-divider content-position="left">收货信息</el-divider>
        <el-form-item label="选择客户">
          <el-select
            v-model="form.customer_id"
            placeholder="搜索客户姓名或手机号"
            filterable
            remote
            clearable
            reserve-keyword
            :remote-method="searchCustomers"
            :loading="customerSearchLoading"
            style="width: 400px"
            @change="handleCustomerSelect"
            @clear="handleCustomerClear"
          >
            <el-option
              v-for="customer in customerOptions"
              :key="customer.id"
              :label="`${customer.name} - ${customer.phone}`"
              :value="customer.id"
            >
              <div class="customer-option">
                <span class="customer-name">{{ customer.name }}</span>
                <span class="customer-phone">{{ customer.phone }}</span>
                <span v-if="customer.order_count" class="customer-order-count">
                  ({{ customer.order_count }}单)
                </span>
              </div>
            </el-option>
          </el-select>
          <div v-if="selectedCustomerInfo" class="selected-customer-info">
            <el-tag type="info" size="small">
              {{ selectedCustomerInfo.order_count || 0 }} 单 / ¥{{ (selectedCustomerInfo.total_spent || 0).toFixed(2) }}
            </el-tag>
            <el-button type="primary" link size="small" @click="fillCustomerInfo">
              填充客户地址信息
            </el-button>
          </div>
        </el-form-item>
        <el-form-item label="收货人" prop="shipping_name">
          <el-input v-model="form.shipping_name" placeholder="请输入收货人姓名" />
        </el-form-item>
        <el-form-item label="联系电话" prop="shipping_phone">
          <el-input v-model="form.shipping_phone" placeholder="请输入联系电话" />
        </el-form-item>
        <el-form-item label="收货地址" prop="shipping_address">
          <el-input
            v-model="form.shipping_address"
            type="textarea"
            :rows="3"
            placeholder="请输入收货地址"
          />
        </el-form-item>
        <el-form-item label="备注">
          <el-input
            v-model="form.remark"
            type="textarea"
            :rows="3"
            placeholder="请输入备注信息（可选）"
          />
        </el-form-item>

        <el-form-item>
          <el-button type="primary" @click="handleSubmit" :loading="loading">
            创建订单
          </el-button>
          <el-button @click="$router.back()">取消</el-button>
        </el-form-item>
      </el-form>
    </el-card>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed, watch } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import { orderApi } from '@/api/modules/order'
import { productApi } from '@/api/modules/product'
import { couponApi } from '@/api/modules/coupon'
import { customerApi } from '@/api/modules/customer'

const router = useRouter()
const formRef = ref(null)
const loading = ref(false)
const products = ref([])
const availableCoupons = ref([])
const productDetailMap = ref({})
const customerOptions = ref([])
const customerSearchLoading = ref(false)
const customerSearchTimeout = ref(null)
const selectedCustomerInfo = ref(null)
const calculationResult = ref(null)
const calculationError = ref('')
const calculating = ref(false)

const form = reactive({
  items: [
    {
      product_id: null,
      product_sku_id: null,
      quantity: 1,
    },
  ],
  customer_id: null,
  coupon_id: null,
  discount_amount: 0,
  shipping_name: '',
  shipping_phone: '',
  shipping_address: '',
  remark: '',
})

const rules = {
  items: [
    {
      validator: (rule, value, callback) => {
        if (!value || value.length === 0) {
          callback(new Error('请至少添加一个商品'))
          return
        }
        for (let i = 0; i < value.length; i++) {
          if (!value[i].product_id) {
            callback(new Error('请选择商品'))
            return
          }
          if (hasSpecs(value[i].product_id) && !value[i].product_sku_id) {
            callback(new Error('请选择商品规格'))
            return
          }
          if (!value[i].quantity || value[i].quantity < 1) {
            callback(new Error('请输入有效的数量'))
            return
          }
        }
        callback()
      },
      trigger: 'change',
    },
  ],
  shipping_name: [
    { required: true, message: '请输入收货人姓名', trigger: 'blur' },
  ],
  shipping_phone: [
    { required: true, message: '请输入联系电话', trigger: 'blur' },
    {
      pattern: /^1[3-9]\d{9}$/,
      message: '请输入正确的手机号码',
      trigger: 'blur',
    },
  ],
  shipping_address: [
    { required: true, message: '请输入收货地址', trigger: 'blur' },
  ],
}

const availableProducts = computed(() => {
  return products.value.filter(
    (p) => p.status === 'active' && p.stock_quantity > 0
  )
})

const totalAmount = computed(() => {
  let total = 0
  form.items.forEach((item) => {
    const price = getItemPrice(item)
    if (price !== null && item.quantity) {
      total += price * item.quantity
    }
  })
  return total
})

const finalAmount = computed(() => {
  if (calculationResult.value && calculationResult.value.final_amount !== undefined) {
    return calculationResult.value.final_amount
  }
  return totalAmount.value
})

const selectedCouponInfo = computed(() => {
  if (!form.coupon_id) return null
  return availableCoupons.value.find((c) => c.id === form.coupon_id) || null
})

const hasSpecs = (productId) => {
  if (!productId) return false
  const product = products.value.find((p) => p.id === productId)
  return product ? product.has_specs : false
}

const getProductSkus = (productId) => {
  if (!productId) return []
  const product = productDetailMap.value[productId]
  return product ? product.skus || [] : []
}

const getItemPrice = (item) => {
  if (!item.product_id) return null
  
  if (item.product_sku_id) {
    const skus = getProductSkus(item.product_id)
    const sku = skus.find((s) => s.id === item.product_sku_id)
    return sku ? sku.price : null
  }
  
  const product = products.value.find((p) => p.id === item.product_id)
  return product ? product.price : null
}

const getItemMaxStock = (item) => {
  if (!item.product_id) return 999999
  
  if (item.product_sku_id) {
    const skus = getProductSkus(item.product_id)
    const sku = skus.find((s) => s.id === item.product_sku_id)
    return sku ? Math.max(sku.stock_quantity, 1) : 999999
  }
  
  const product = products.value.find((p) => p.id === item.product_id)
  return product ? Math.max(product.stock_quantity, 1) : 999999
}

const getCouponLabel = (coupon) => {
  const discount = coupon.type === 'fixed' ? `¥${coupon.value.toFixed(2)}` : `${coupon.value}%`
  const minStr = coupon.min_amount > 0 ? `(满${coupon.min_amount}可用)` : '(无门槛)'
  return `${coupon.name} - ${discount} ${minStr}`
}

const handleProductChange = async (index) => {
  const item = form.items[index]
  item.product_sku_id = null
  
  if (item.product_id && !productDetailMap.value[item.product_id]) {
    try {
      const res = await productApi.getProduct(item.product_id)
      productDetailMap.value[item.product_id] = res.data
    } catch (e) {
      console.error('加载商品详情失败', e)
    }
  }
  
  const stock = getItemMaxStock(item)
  if (item.quantity > stock) {
    item.quantity = stock
    ElMessage.warning('数量不能超过库存')
  }
  await calculateTotal()
}

const handleSkuChange = async (index) => {
  const item = form.items[index]
  const stock = getItemMaxStock(item)
  if (item.quantity > stock) {
    item.quantity = stock
    ElMessage.warning('数量不能超过库存')
  }
  await calculateTotal()
}

const handleCouponChange = async () => {
  calculationError.value = ''
  await calculateFromServer()
}

const calculateFromServer = async () => {
  if (!form.coupon_id) {
    calculationResult.value = null
    return
  }
  if (totalAmount.value <= 0) {
    calculationResult.value = null
    return
  }
  calculating.value = true
  calculationError.value = ''
  try {
    const res = await couponApi.calculate({
      coupon_id: form.coupon_id,
      order_amount: totalAmount.value,
      customer_id: form.customer_id || null,
    })
    calculationResult.value = res.data
  } catch (error) {
    calculationResult.value = null
    calculationError.value = error.response?.data?.message || '优惠券计算失败'
  } finally {
    calculating.value = false
  }
}

const searchCustomers = (query) => {
  if (customerSearchTimeout.value) {
    clearTimeout(customerSearchTimeout.value)
  }
  if (!query || query.trim().length === 0) {
    customerOptions.value = []
    return
  }
  customerSearchLoading.value = true
  customerSearchTimeout.value = setTimeout(async () => {
    try {
      const res = await customerApi.searchCustomers(query.trim(), 30)
      customerOptions.value = res.data || []
    } catch (e) {
      customerOptions.value = []
    } finally {
      customerSearchLoading.value = false
    }
  }, 300)
}

const handleCustomerSelect = async (customerId) => {
  if (!customerId) {
    selectedCustomerInfo.value = null
    return
  }
  try {
    const res = await customerApi.getCustomer(customerId)
    selectedCustomerInfo.value = res.data
    if (!form.shipping_name) {
      form.shipping_name = res.data.name
    }
    if (!form.shipping_phone) {
      form.shipping_phone = res.data.phone
    }
    if (!form.shipping_address && res.data.address) {
      form.shipping_address = res.data.address
    }
    if (form.coupon_id) {
      await calculateFromServer()
    }
  } catch (e) {
    console.error('加载客户信息失败', e)
  }
}

const handleCustomerClear = () => {
  selectedCustomerInfo.value = null
  if (form.coupon_id) {
    calculateFromServer()
  }
}

const fillCustomerInfo = () => {
  if (!selectedCustomerInfo.value) return
  form.shipping_name = selectedCustomerInfo.value.name
  form.shipping_phone = selectedCustomerInfo.value.phone
  form.shipping_address = selectedCustomerInfo.value.address || ''
  ElMessage.success('已填充客户信息')
}

const addItem = () => {
  form.items.push({
    product_id: null,
    product_sku_id: null,
    quantity: 1,
  })
}

const removeItem = async (index) => {
  if (form.items.length > 1) {
    form.items.splice(index, 1)
    await calculateTotal()
  }
}

const calculateTotal = async () => {
  if (form.coupon_id && totalAmount.value > 0) {
    await calculateFromServer()
  }
}

const fetchProducts = async () => {
  try {
    const res = await productApi.getProducts({ per_page: 1000, status: 'active' })
    products.value = res.data
  } catch (error) {
    ElMessage.error('获取商品列表失败')
  }
}

const fetchAvailableCoupons = async () => {
  try {
    const res = await couponApi.getAvailable({ order_amount: totalAmount.value })
    availableCoupons.value = res.data || []
  } catch (error) {
    availableCoupons.value = []
  }
}

watch(totalAmount, () => {
  if (totalAmount.value > 0) {
    fetchAvailableCoupons()
    if (form.coupon_id) {
      calculateFromServer()
    }
  } else {
    calculationResult.value = null
  }
})

const handleSubmit = async () => {
  if (!formRef.value) return

  if (form.coupon_id && !calculationResult.value) {
    ElMessage.error('请等待优惠券计算完成')
    return
  }

  if (calculationError.value) {
    ElMessage.error(calculationError.value)
    return
  }

  await formRef.value.validate(async (valid) => {
    if (!valid) return

    loading.value = true
    try {
      const orderData = {
        items: form.items.map((item) => ({
          product_id: item.product_id,
          product_sku_id: item.product_sku_id || null,
          quantity: item.quantity,
        })),
        customer_id: form.customer_id || null,
        coupon_id: form.coupon_id || null,
        discount_amount: calculationResult.value?.discount_amount || 0,
        shipping_name: form.shipping_name,
        shipping_phone: form.shipping_phone,
        shipping_address: form.shipping_address,
        remark: form.remark || '',
      }

      await orderApi.createOrder(orderData)
      ElMessage.success('订单创建成功')
      router.push('/orders')
    } catch (error) {
      ElMessage.error(error.response?.data?.message || '订单创建失败')
    } finally {
      loading.value = false
    }
  })
}

onMounted(() => {
  fetchProducts()
})
</script>

<style scoped>
.order-form {
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

.no-spec-tip {
  font-size: 13px;
  color: #9ca3af;
}

.coupon-info {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-top: 6px;
  font-size: 13px;
  color: #374151;
}

.coupon-error {
  margin-top: 6px;
  font-size: 13px;
  color: #f56c6c;
}

.customer-option {
  display: flex;
  align-items: center;
  gap: 8px;
}

.customer-option .customer-name {
  font-weight: 500;
  color: #111827;
}

.customer-option .customer-phone {
  color: #6b7280;
  font-size: 12px;
}

.customer-option .customer-order-count {
  color: #6366f1;
  font-size: 12px;
}

.selected-customer-info {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-top: 6px;
}
</style>
