<template>
  <div class="return-list page-shell">
    <el-card>
      <template #header>
        <div class="card-header">
          <div class="card-header-text">
            <span class="card-title">退换货管理</span>
            <span class="card-subtitle">管理退货与换货申请，处理审核与库存恢复</span>
          </div>
          <el-button type="primary" @click="handleCreate" round>
            发起申请
          </el-button>
        </div>
      </template>

      <el-form :inline="true" :model="filters" class="filter-form">
        <el-form-item label="申请单号">
          <el-input v-model="filters.return_no" placeholder="请输入申请单号" clearable style="width: 220px" />
        </el-form-item>
        <el-form-item label="关联订单">
          <el-input v-model="filters.order_no" placeholder="请输入订单号" clearable style="width: 220px" />
        </el-form-item>
        <el-form-item label="类型">
          <el-select v-model="filters.type" placeholder="请选择类型" clearable style="width: 120px">
            <el-option label="退货" value="return" />
            <el-option label="换货" value="exchange" />
          </el-select>
        </el-form-item>
        <el-form-item label="状态">
          <el-select v-model="filters.status" placeholder="请选择状态" clearable style="width: 140px">
            <el-option label="待审核" value="pending" />
            <el-option label="已通过" value="approved" />
            <el-option label="已拒绝" value="rejected" />
            <el-option label="已完成" value="completed" />
          </el-select>
        </el-form-item>
        <el-form-item label="日期范围">
          <el-date-picker
            v-model="dateRange"
            type="daterange"
            range-separator="至"
            start-placeholder="开始日期"
            end-placeholder="结束日期"
            value-format="YYYY-MM-DD"
            @change="handleDateChange"
            style="width: 260px"
          />
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="handleSearch">查询</el-button>
          <el-button @click="handleReset">重置</el-button>
        </el-form-item>
      </el-form>

      <el-table :data="returns" v-loading="loading" style="width: 100%">
        <el-table-column prop="return_no" label="申请单号" width="200" />
        <el-table-column label="关联订单" width="180">
          <template #default="{ row }">
            {{ row.order?.order_no || '-' }}
          </template>
        </el-table-column>
        <el-table-column label="类型" width="100">
          <template #default="{ row }">
            <el-tag :type="row.type === 'return' ? 'danger' : 'warning'">
              {{ row.type_text }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="商品" min-width="180">
          <template #default="{ row }">
            <div>
              <div>{{ row.order_item?.product_name }}</div>
              <div class="sku-text" v-if="row.order_item?.product_sku">
                SKU: {{ row.order_item.product_sku }}
              </div>
            </div>
          </template>
        </el-table-column>
        <el-table-column prop="quantity" label="数量" width="80" />
        <el-table-column label="退款金额" width="120">
          <template #default="{ row }">
            ¥{{ row.refund_amount.toFixed(2) }}
          </template>
        </el-table-column>
        <el-table-column label="状态" width="120">
          <template #default="{ row }">
            <el-tag :type="getStatusType(row.status)">
              {{ row.status_text }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="申请时间" width="180" />
        <el-table-column label="操作" width="200" fixed="right">
          <template #default="{ row }">
            <div class="action-buttons">
              <el-button size="small" @click="handleView(row)">详情</el-button>
            </div>
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

    <el-dialog v-model="createDialogVisible" title="发起退换货申请" width="600px">
      <el-form :model="createForm" :rules="createRules" ref="createFormRef" label-width="100px">
        <el-form-item label="选择订单" prop="order_id">
          <el-select
            v-model="createForm.order_id"
            placeholder="请选择订单（仅已支付/已发货）"
            filterable
            style="width: 100%"
            @change="handleOrderChange"
          >
            <el-option
              v-for="order in availableOrders"
              :key="order.id"
              :label="order.order_no + ' - ' + order.status_text"
              :value="order.id"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="选择商品" prop="order_item_id" v-if="createForm.order_id">
          <el-select v-model="createForm.order_item_id" placeholder="请选择商品" style="width: 100%">
            <el-option
              v-for="item in currentOrderItems"
              :key="item.id"
              :label="item.product_name + (item.product_sku ? ' (' + item.product_sku + ')' : '') + ' x' + item.quantity"
              :value="item.id"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="类型" prop="type">
          <el-radio-group v-model="createForm.type">
            <el-radio label="return">退货</el-radio>
            <el-radio label="exchange">换货</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item label="退货数量" prop="quantity">
          <el-input-number v-model="createForm.quantity" :min="1" :max="maxQuantity" />
        </el-form-item>
        <el-form-item label="退款金额" prop="refund_amount">
          <el-input-number v-model="createForm.refund_amount" :min="0" :precision="2" :step="1" />
          <div class="form-tip">最大可退：¥{{ maxRefund.toFixed(2) }}</div>
        </el-form-item>
        <el-form-item label="原因" prop="reason">
          <el-input v-model="createForm.reason" type="textarea" :rows="3" placeholder="请输入退换货原因" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="createDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="handleSubmitCreate" :loading="creating">提交</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import { returnApi } from '@/api/modules/return'
import { orderApi } from '@/api/modules/order'

const router = useRouter()
const returns = ref([])
const loading = ref(false)
const currentPage = ref(1)
const pageSize = ref(10)
const total = ref(0)
const dateRange = ref(null)

const filters = reactive({
  return_no: '',
  order_no: '',
  type: '',
  status: '',
  start_date: '',
  end_date: '',
})

const createDialogVisible = ref(false)
const creating = ref(false)
const createFormRef = ref(null)
const availableOrders = ref([])
const currentOrderItems = ref([])

const createForm = reactive({
  order_id: null,
  order_item_id: null,
  type: 'return',
  quantity: 1,
  refund_amount: 0,
  reason: '',
})

const createRules = {
  order_id: [{ required: true, message: '请选择订单', trigger: 'change' }],
  order_item_id: [{ required: true, message: '请选择商品', trigger: 'change' }],
  type: [{ required: true, message: '请选择类型', trigger: 'change' }],
  quantity: [{ required: true, message: '请输入数量', trigger: 'blur' }],
  refund_amount: [{ required: true, message: '请输入退款金额', trigger: 'blur' }],
  reason: [{ required: true, message: '请输入原因', trigger: 'blur' }],
}

const maxQuantity = computed(() => {
  const item = currentOrderItems.value.find(i => i.id === createForm.order_item_id)
  return item?.quantity || 1
})

const maxRefund = computed(() => {
  const item = currentOrderItems.value.find(i => i.id === createForm.order_item_id)
  if (!item) return 0
  return (item.product_price || 0) * createForm.quantity
})

const getStatusType = (status) => {
  const map = {
    pending: 'warning',
    approved: 'info',
    rejected: 'danger',
    completed: 'success',
  }
  return map[status] || ''
}

const handleDateChange = (dates) => {
  if (dates && dates.length === 2) {
    filters.start_date = dates[0]
    filters.end_date = dates[1]
  } else {
    filters.start_date = ''
    filters.end_date = ''
  }
}

const handleSearch = () => {
  currentPage.value = 1
  fetchReturns()
}

const handleReset = () => {
  Object.assign(filters, {
    return_no: '',
    order_no: '',
    type: '',
    status: '',
    start_date: '',
    end_date: '',
  })
  dateRange.value = null
  handleSearch()
}

const fetchReturns = async () => {
  loading.value = true
  try {
    const params = {
      page: currentPage.value,
      per_page: pageSize.value,
      ...filters,
    }
    const res = await returnApi.getReturns(params)
    returns.value = res.data
    total.value = res.meta.total
  } catch (error) {
    ElMessage.error('获取退换货列表失败')
  } finally {
    loading.value = false
  }
}

const handleView = (row) => {
  router.push(`/returns/${row.id}`)
}

const handleCreate = async () => {
  try {
    const res = await orderApi.getOrders({ per_page: 100, status: '' })
    const allOrders = res.data
    availableOrders.value = allOrders
      .filter(o => ['paid', 'shipped'].includes(o.status))
      .map(o => ({ ...o, status_text: getOrderStatusText(o.status) }))
    createDialogVisible.value = true
  } catch (error) {
    ElMessage.error('获取可退订单失败')
  }
}

const getOrderStatusText = (status) => {
  const map = {
    pending: '待支付',
    paid: '已支付',
    shipped: '已发货',
    completed: '已完成',
    cancelled: '已取消',
  }
  return map[status] || status
}

const handleOrderChange = (orderId) => {
  const order = availableOrders.value.find(o => o.id === orderId)
  currentOrderItems.value = order?.order_items || []
  createForm.order_item_id = null
  createForm.quantity = 1
  createForm.refund_amount = 0
}

const handleSubmitCreate = async () => {
  if (!createFormRef.value) return
  try {
    await createFormRef.value.validate()
    creating.value = true
    await returnApi.createReturn(createForm)
    ElMessage.success('退换货申请创建成功')
    createDialogVisible.value = false
    resetCreateForm()
    fetchReturns()
  } catch (error) {
    if (error !== false) {
      ElMessage.error(error?.message || '创建失败')
    }
  } finally {
    creating.value = false
  }
}

const resetCreateForm = () => {
  Object.assign(createForm, {
    order_id: null,
    order_item_id: null,
    type: 'return',
    quantity: 1,
    refund_amount: 0,
    reason: '',
  })
  currentOrderItems.value = []
}

const handleSizeChange = () => {
  fetchReturns()
}

const handleCurrentChange = () => {
  fetchReturns()
}

onMounted(() => {
  fetchReturns()
})
</script>

<style scoped>
.return-list {
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

.action-buttons {
  display: flex;
  flex-direction: row;
  align-items: center;
  gap: 6px;
  flex-wrap: nowrap;
}

.sku-text {
  font-size: 12px;
  color: #6b7280;
}

.form-tip {
  font-size: 12px;
  color: #9ca3af;
  margin-top: 4px;
}
</style>
