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
        <!-- 订单商品 -->
        <el-form-item label="订单商品" prop="items">
          <el-table :data="form.items" border style="width: 100%">
            <el-table-column label="商品" width="300">
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
                    :label="`${product.name} (${product.sku}) - 库存: ${product.stock_quantity}`"
                    :value="product.id"
                    :disabled="product.stock_quantity === 0 || product.status !== 'active'"
                  />
                </el-select>
              </template>
            </el-table-column>
            <el-table-column label="单价" width="120">
              <template #default="{ row }">
                <span v-if="row.product_id">
                  ¥{{ getProductPrice(row.product_id).toFixed(2) }}
                </span>
                <span v-else>-</span>
              </template>
            </el-table-column>
            <el-table-column label="数量" width="150">
              <template #default="{ row, $index }">
                <el-input-number
                  v-model="row.quantity"
                  :min="1"
                  :max="row.product_id ? Math.max(getProductStock(row.product_id), 1) : 999999"
                  @change="calculateTotal"
                />
              </template>
            </el-table-column>
            <el-table-column label="小计" width="120">
              <template #default="{ row }">
                <span v-if="row.product_id && row.quantity">
                  ¥{{ (getProductPrice(row.product_id) * row.quantity).toFixed(2) }}
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

        <!-- 优惠券选择 -->
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
        </el-form-item>

        <!-- 订单金额 -->
        <el-form-item label="订单金额">
          <div style="font-size: 16px">
            <span>商品总额：¥{{ totalAmount.toFixed(2) }}</span>
            <el-divider direction="vertical" />
            <span>优惠券抵扣：
              <span v-if="form.coupon_id" style="color: #67c23a">-¥{{ couponDiscount.toFixed(2) }}</span>
              <span v-else style="color: #909399">¥0.00</span>
            </span>
            <el-divider direction="vertical" />
            <span style="font-weight: bold; color: #f56c6c; font-size: 18px">
              实付金额：¥{{ finalAmount.toFixed(2) }}
            </span>
          </div>
        </el-form-item>

        <!-- 收货信息 -->
        <el-divider content-position="left">收货信息</el-divider>
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

const router = useRouter()
const formRef = ref(null)
const loading = ref(false)
const products = ref([])
const availableCoupons = ref([])

const form = reactive({
  items: [
    {
      product_id: null,
      quantity: 1,
    },
  ],
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
    if (item.product_id && item.quantity) {
      const price = getProductPrice(item.product_id)
      total += price * item.quantity
    }
  })
  return total
})

const couponDiscount = computed(() => {
  if (!form.coupon_id) return 0
  const coupon = availableCoupons.value.find((c) => c.id === form.coupon_id)
  if (!coupon) return 0
  if (coupon.min_amount > totalAmount.value) return 0
  if (coupon.type === 'fixed') {
    return Math.min(coupon.value, totalAmount.value)
  }
  return Math.round(totalAmount.value * (coupon.value / 100) * 100) / 100
})

const finalAmount = computed(() => {
  return Math.max(0, totalAmount.value - couponDiscount.value)
})

const selectedCouponInfo = computed(() => {
  if (!form.coupon_id) return null
  return availableCoupons.value.find((c) => c.id === form.coupon_id) || null
})

const getProductPrice = (productId) => {
  const product = products.value.find((p) => p.id === productId)
  return product ? product.price : 0
}

const getProductStock = (productId) => {
  const product = products.value.find((p) => p.id === productId)
  return product ? product.stock_quantity : 0
}

const getCouponLabel = (coupon) => {
  const discount = coupon.type === 'fixed' ? `¥${coupon.value.toFixed(2)}` : `${coupon.value}%`
  const minStr = coupon.min_amount > 0 ? `(满${coupon.min_amount}可用)` : '(无门槛)'
  return `${coupon.name} - ${discount} ${minStr}`
}

const handleProductChange = (index) => {
  const item = form.items[index]
  if (item.product_id) {
    const stock = getProductStock(item.product_id)
    if (item.quantity > stock) {
      item.quantity = stock
      ElMessage.warning('数量不能超过库存')
    }
  }
  calculateTotal()
}

const handleCouponChange = () => {
  if (form.coupon_id) {
    const coupon = availableCoupons.value.find((c) => c.id === form.coupon_id)
    if (coupon && coupon.min_amount > totalAmount.value) {
      ElMessage.warning(`该优惠券需要满 ¥${coupon.min_amount.toFixed(2)} 才可使用`)
    }
  }
}

const addItem = () => {
  form.items.push({
    product_id: null,
    quantity: 1,
  })
}

const removeItem = (index) => {
  if (form.items.length > 1) {
    form.items.splice(index, 1)
    calculateTotal()
  }
}

const calculateTotal = () => {

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
  }
})

const handleSubmit = async () => {
  if (!formRef.value) return

  await formRef.value.validate(async (valid) => {
    if (!valid) return

    loading.value = true
    try {
      const orderData = {
        items: form.items.map((item) => ({
          product_id: item.product_id,
          quantity: item.quantity,
        })),
        coupon_id: form.coupon_id || null,
        discount_amount: couponDiscount.value || 0,
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

.coupon-info {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-top: 6px;
  font-size: 13px;
  color: #374151;
}
</style>
