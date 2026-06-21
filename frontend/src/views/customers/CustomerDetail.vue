<template>
  <div class="customer-detail page-shell">
    <el-card v-loading="loading">
      <template #header>
        <div class="card-header">
          <div class="card-header-text">
            <span class="card-title">客户详情</span>
            <span class="card-subtitle">查看客户档案信息与历史订单</span>
          </div>
          <div>
            <el-button @click="$router.back()" round>返回</el-button>
            <el-button type="primary" @click="handleEdit" round style="margin-left: 8px">
              编辑客户
            </el-button>
          </div>
        </div>
      </template>

      <div v-if="customer" class="customer-info">
        <el-descriptions title="基本信息" :column="2" border>
          <el-descriptions-item label="客户姓名">{{ customer.name }}</el-descriptions-item>
          <el-descriptions-item label="手机号码">{{ customer.phone }}</el-descriptions-item>
          <el-descriptions-item label="收货地址" :span="2">{{ customer.address || '无' }}</el-descriptions-item>
          <el-descriptions-item label="累计订单数">
            <el-tag type="primary" size="small">{{ customer.order_count }} 单</el-tag>
          </el-descriptions-item>
          <el-descriptions-item label="累计消费额">
            <span class="total-spent">¥{{ customer.total_spent.toFixed(2) }}</span>
          </el-descriptions-item>
          <el-descriptions-item label="建档时间">{{ customer.created_at }}</el-descriptions-item>
          <el-descriptions-item label="更新时间">{{ customer.updated_at }}</el-descriptions-item>
        </el-descriptions>

        <div style="margin-top: 24px">
          <h3>历史订单</h3>
          <el-empty v-if="!customer.orders || customer.orders.length === 0" description="暂无历史订单" />
          <el-table v-else :data="customer.orders" border style="width: 100%; margin-top: 12px">
            <el-table-column prop="order_no" label="订单号" width="200">
              <template #default="{ row }">
                <el-link type="primary" @click="goToOrder(row.id)">
                  {{ row.order_no }}
                </el-link>
              </template>
            </el-table-column>
            <el-table-column label="订单状态" width="120">
              <template #default="{ row }">
                <el-tag :type="getStatusType(row.status)">
                  {{ getStatusText(row.status) }}
                </el-tag>
              </template>
            </el-table-column>
            <el-table-column label="商品数量" width="120">
              <template #default="{ row }">
                {{ (row.order_items || []).length }} 件
              </template>
            </el-table-column>
            <el-table-column label="订单金额" width="140">
              <template #default="{ row }">¥{{ row.total_amount.toFixed(2) }}</template>
            </el-table-column>
            <el-table-column label="实付金额" width="140">
              <template #default="{ row }">
                <span class="final-amount">¥{{ row.final_amount.toFixed(2) }}</span>
              </template>
            </el-table-column>
            <el-table-column prop="created_at" label="下单时间" width="180" />
            <el-table-column label="操作" width="100" fixed="right">
              <template #default="{ row }">
                <el-button size="small" type="primary" plain @click="goToOrder(row.id)">
                  查看
                </el-button>
              </template>
            </el-table-column>
          </el-table>
        </div>
      </div>
    </el-card>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import { customerApi } from '@/api/modules/customer'

const route = useRoute()
const router = useRouter()
const customer = ref(null)
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

const fetchCustomer = async () => {
  loading.value = true
  try {
    const res = await customerApi.getCustomer(route.params.id)
    customer.value = res.data
  } catch (error) {
    ElMessage.error('获取客户详情失败')
    router.back()
  } finally {
    loading.value = false
  }
}

const handleEdit = () => {
  router.push(`/customers/${route.params.id}/edit`)
}

const goToOrder = (id) => {
  router.push(`/orders/${id}`)
}

onMounted(() => {
  fetchCustomer()
})
</script>

<style scoped>
.customer-detail {
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

.total-spent {
  font-weight: 600;
  color: #f56c6c;
  font-size: 16px;
}

.final-amount {
  font-weight: 600;
  color: #f56c6c;
}

h3 {
  font-size: 16px;
  font-weight: 600;
  color: #111827;
  margin: 0 0 12px 0;
}
</style>
