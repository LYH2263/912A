<template>
  <div class="order-detail page-shell">
    <el-card v-loading="loading">
      <template #header>
        <div class="card-header">
          <div class="card-header-text">
            <span class="card-title">订单详情</span>
            <span class="card-subtitle">查看订单金额、状态与收货信息</span>
          </div>
          <el-button @click="$router.back()" round>返回</el-button>
        </div>
      </template>

      <div v-if="order" class="order-info">
        <!-- 订单基本信息 -->
        <el-descriptions title="订单信息" :column="2" border>
          <el-descriptions-item label="订单号">{{ order.order_no }}</el-descriptions-item>
          <el-descriptions-item label="订单状态">
            <el-tag :type="getStatusType(order.status)">
              {{ getStatusText(order.status) }}
            </el-tag>
          </el-descriptions-item>
          <el-descriptions-item label="订单金额">¥{{ order.total_amount.toFixed(2) }}</el-descriptions-item>
          <el-descriptions-item label="优惠金额">¥{{ order.discount_amount.toFixed(2) }}</el-descriptions-item>
          <el-descriptions-item label="实付金额">
            <span class="final-amount">¥{{ order.final_amount.toFixed(2) }}</span>
          </el-descriptions-item>
          <el-descriptions-item label="创建时间">{{ order.created_at }}</el-descriptions-item>
          <el-descriptions-item label="支付时间" v-if="order.paid_at">{{ order.paid_at }}</el-descriptions-item>
          <el-descriptions-item label="发货时间" v-if="order.shipped_at">{{ order.shipped_at }}</el-descriptions-item>
          <el-descriptions-item label="完成时间" v-if="order.completed_at">{{ order.completed_at }}</el-descriptions-item>
          <el-descriptions-item label="取消时间" v-if="order.cancelled_at">{{ order.cancelled_at }}</el-descriptions-item>
        </el-descriptions>

        <!-- 收货信息 -->
        <el-descriptions title="收货信息" :column="2" border style="margin-top: 20px">
          <el-descriptions-item label="收货人">{{ order.shipping_name }}</el-descriptions-item>
          <el-descriptions-item label="联系电话">{{ order.shipping_phone }}</el-descriptions-item>
          <el-descriptions-item label="收货地址" :span="2">{{ order.shipping_address }}</el-descriptions-item>
          <el-descriptions-item label="备注" :span="2">{{ order.remark || '无' }}</el-descriptions-item>
        </el-descriptions>

        <!-- 订单商品 -->
        <div style="margin-top: 20px">
          <h3>订单商品</h3>
          <el-table :data="order.order_items" border>
            <el-table-column prop="product_name" label="商品名称" />
            <el-table-column prop="product_sku" label="SKU" width="150" />
            <el-table-column prop="product_price" label="单价" width="120">
              <template #default="{ row }">
                ¥{{ row.product_price.toFixed(2) }}
              </template>
            </el-table-column>
            <el-table-column prop="quantity" label="数量" width="100" />
            <el-table-column prop="subtotal" label="小计" width="120">
              <template #default="{ row }">
                ¥{{ row.subtotal.toFixed(2) }}
              </template>
            </el-table-column>
          </el-table>
        </div>

        <!-- 操作按钮 -->
        <div class="actions" style="margin-top: 20px">
          <el-button
            v-if="order.status === 'pending'"
            type="success"
            @click="handleUpdateStatus('paid')"
          >
            标记已支付
          </el-button>
          <el-button
            v-if="order.status === 'paid'"
            type="warning"
            @click="handleUpdateStatus('shipped')"
          >
            标记已发货
          </el-button>
          <el-button
            v-if="order.status === 'shipped'"
            type="primary"
            @click="handleUpdateStatus('completed')"
          >
            标记已完成
          </el-button>
          <el-button
            v-if="['pending', 'paid'].includes(order.status)"
            type="danger"
            @click="handleUpdateStatus('cancelled')"
          >
            取消订单
          </el-button>
        </div>
      </div>
    </el-card>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import { orderApi } from '@/api/modules/order'

const route = useRoute()
const router = useRouter()
const order = ref(null)
const loading = ref(false)

const getStatusType = (status) => {
  const map = {
    pending: 'warning',
    paid: 'info',
    shipped: '',
    completed: 'success',
    cancelled: 'danger',
  }
  return map[status] || ''
}

const getStatusText = (status) => {
  const map = {
    pending: '待支付',
    paid: '已支付',
    shipped: '已发货',
    completed: '已完成',
    cancelled: '已取消',
  }
  return map[status] || status
}

const fetchOrder = async () => {
  loading.value = true
  try {
    const res = await orderApi.getOrder(route.params.id)
    order.value = res.data
  } catch (error) {
    ElMessage.error('获取订单详情失败')
    router.back()
  } finally {
    loading.value = false
  }
}

const handleUpdateStatus = async (status) => {
  try {
    const statusText = getStatusText(status)
    await ElMessageBox.confirm(`确定要将订单状态更新为"${statusText}"吗？`, '提示', {
      type: 'warning',
    })
    await orderApi.updateOrderStatus(order.value.id, status)
    ElMessage.success('订单状态更新成功')
    fetchOrder()
  } catch (error) {
    if (error !== 'cancel') {
      ElMessage.error('订单状态更新失败')
    }
  }
}

onMounted(() => {
  fetchOrder()
})
</script>

<style scoped>
.order-detail {
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

.final-amount {
  font-size: 18px;
  font-weight: bold;
  color: #f56c6c;
}

.actions {
  text-align: right;
}
</style>
