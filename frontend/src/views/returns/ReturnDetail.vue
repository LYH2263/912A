<template>
  <div class="return-detail page-shell">
    <el-card v-loading="loading">
      <template #header>
        <div class="card-header">
          <div class="card-header-text">
            <span class="card-title">退换货详情</span>
            <span class="card-subtitle">审核退换货申请，处理库存与退款</span>
          </div>
          <el-button @click="$router.back()" round>返回</el-button>
        </div>
      </template>

      <div v-if="returnData" class="return-info">
        <el-descriptions title="申请信息" :column="2" border>
          <el-descriptions-item label="申请单号">{{ returnData.return_no }}</el-descriptions-item>
          <el-descriptions-item label="申请状态">
            <el-tag :type="getStatusType(returnData.status)">
              {{ returnData.status_text }}
            </el-tag>
          </el-descriptions-item>
          <el-descriptions-item label="申请类型">
            <el-tag :type="returnData.type === 'return' ? 'danger' : 'warning'">
              {{ returnData.type_text }}
            </el-tag>
          </el-descriptions-item>
          <el-descriptions-item label="退货数量">{{ returnData.quantity }}</el-descriptions-item>
          <el-descriptions-item label="退款金额">
            <span class="amount-text">¥{{ returnData.refund_amount.toFixed(2) }}</span>
          </el-descriptions-item>
          <el-descriptions-item label="申请时间">{{ returnData.created_at }}</el-descriptions-item>
          <el-descriptions-item label="审核通过时间" v-if="returnData.approved_at">{{ returnData.approved_at }}</el-descriptions-item>
          <el-descriptions-item label="审核拒绝时间" v-if="returnData.rejected_at">{{ returnData.rejected_at }}</el-descriptions-item>
          <el-descriptions-item label="完成时间" v-if="returnData.completed_at">{{ returnData.completed_at }}</el-descriptions-item>
          <el-descriptions-item label="操作人" v-if="returnData.operator">
            {{ returnData.operator.name || returnData.operator.email }}
          </el-descriptions-item>
        </el-descriptions>

        <el-descriptions title="申请原因" :column="1" border style="margin-top: 20px">
          <el-descriptions-item label="原因">{{ returnData.reason }}</el-descriptions-item>
        </el-descriptions>

        <el-descriptions
          v-if="returnData.reject_reason"
          title="拒绝原因"
          :column="1"
          border
          style="margin-top: 20px"
        >
          <el-descriptions-item label="拒绝原因" class="reject-reason">
            {{ returnData.reject_reason }}
          </el-descriptions-item>
        </el-descriptions>

        <el-descriptions
          v-if="returnData.order"
          title="关联订单信息"
          :column="2"
          border
          style="margin-top: 20px"
        >
          <el-descriptions-item label="订单号">{{ returnData.order.order_no }}</el-descriptions-item>
          <el-descriptions-item label="订单状态">
            <el-tag :type="getOrderStatusType(returnData.order.status)">
              {{ getOrderStatusText(returnData.order.status) }}
            </el-tag>
          </el-descriptions-item>
          <el-descriptions-item label="收货人">{{ returnData.order.shipping_name }}</el-descriptions-item>
          <el-descriptions-item label="联系电话">{{ returnData.order.shipping_phone }}</el-descriptions-item>
          <el-descriptions-item label="收货地址" :span="2">{{ returnData.order.shipping_address }}</el-descriptions-item>
        </el-descriptions>

        <div style="margin-top: 20px">
          <h3>退换商品</h3>
          <el-table :data="[returnData.order_item]" border>
            <el-table-column prop="product_name" label="商品名称" />
            <el-table-column prop="product_sku" label="SKU" width="180">
              <template #default="{ row }">
                {{ row?.product_sku || '-' }}
              </template>
            </el-table-column>
            <el-table-column label="单价" width="120">
              <template #default="{ row }">
                ¥{{ (row?.product_price || 0).toFixed(2) }}
              </template>
            </el-table-column>
            <el-table-column label="原数量" width="100">
              <template #default="{ row }">
                {{ row?.quantity || 0 }}
              </template>
            </el-table-column>
            <el-table-column label="退换数量" width="120">
              <template #default>
                <el-tag type="danger">{{ returnData.quantity }}</el-tag>
              </template>
            </el-table-column>
            <el-table-column label="小计" width="140">
              <template #default="{ row }">
                ¥{{ ((row?.product_price || 0) * returnData.quantity).toFixed(2) }}
              </template>
            </el-table-column>
          </el-table>
        </div>

        <div class="actions" style="margin-top: 24px">
          <el-button
            v-if="returnData.status === 'pending'"
            type="success"
            @click="handleApprove"
          >
            审核通过
          </el-button>
          <el-button
            v-if="returnData.status === 'pending'"
            type="danger"
            @click="handleReject"
          >
            审核拒绝
          </el-button>
          <el-button
            v-if="returnData.status === 'approved'"
            type="primary"
            @click="handleComplete"
          >
            完成处理
          </el-button>
        </div>
      </div>
    </el-card>

    <el-dialog v-model="rejectDialogVisible" title="拒绝申请" width="500px">
      <el-form :model="rejectForm" :rules="rejectRules" ref="rejectFormRef" label-width="80px">
        <el-form-item label="拒绝原因" prop="reject_reason">
          <el-input
            v-model="rejectForm.reject_reason"
            type="textarea"
            :rows="4"
            placeholder="请输入拒绝原因"
          />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="rejectDialogVisible = false">取消</el-button>
        <el-button type="danger" @click="handleSubmitReject" :loading="rejecting">确认拒绝</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import { returnApi } from '@/api/modules/return'

const route = useRoute()
const router = useRouter()
const returnData = ref(null)
const loading = ref(false)

const rejectDialogVisible = ref(false)
const rejecting = ref(false)
const rejectFormRef = ref(null)
const rejectForm = reactive({
  reject_reason: '',
})
const rejectRules = {
  reject_reason: [{ required: true, message: '请输入拒绝原因', trigger: 'blur' }],
}

const getStatusType = (status) => {
  const map = {
    pending: 'warning',
    approved: 'info',
    rejected: 'danger',
    completed: 'success',
  }
  return map[status] || ''
}

const getOrderStatusType = (status) => {
  const map = {
    pending: 'warning',
    paid: 'info',
    shipped: '',
    completed: 'success',
    cancelled: 'danger',
  }
  return map[status] || ''
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

const fetchReturn = async () => {
  loading.value = true
  try {
    const res = await returnApi.getReturn(route.params.id)
    returnData.value = res.data
  } catch (error) {
    ElMessage.error('获取退换货详情失败')
    router.back()
  } finally {
    loading.value = false
  }
}

const handleApprove = async () => {
  try {
    await ElMessageBox.confirm('确定要审核通过该退换货申请吗？', '提示', {
      type: 'warning',
    })
    await returnApi.approveReturn(returnData.value.id)
    ElMessage.success('审核通过成功')
    fetchReturn()
  } catch (error) {
    if (error !== 'cancel') {
      ElMessage.error('审核通过失败')
    }
  }
}

const handleReject = () => {
  rejectForm.reject_reason = ''
  rejectDialogVisible.value = true
}

const handleSubmitReject = async () => {
  if (!rejectFormRef.value) return
  try {
    await rejectFormRef.value.validate()
    rejecting.value = true
    await returnApi.rejectReturn(returnData.value.id, rejectForm.reject_reason)
    ElMessage.success('已拒绝申请')
    rejectDialogVisible.value = false
    fetchReturn()
  } catch (error) {
    if (error !== false) {
      ElMessage.error('操作失败')
    }
  } finally {
    rejecting.value = false
  }
}

const handleComplete = async () => {
  const tip = returnData.value.type === 'return'
    ? '确定完成退货？将自动恢复对应商品库存。'
    : '确定完成换货处理吗？'
  try {
    await ElMessageBox.confirm(tip, '提示', {
      type: 'warning',
    })
    await returnApi.completeReturn(returnData.value.id)
    ElMessage.success('处理完成')
    fetchReturn()
  } catch (error) {
    if (error !== 'cancel') {
      ElMessage.error('操作失败')
    }
  }
}

onMounted(() => {
  fetchReturn()
})
</script>

<style scoped>
.return-detail {
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

.amount-text {
  font-size: 16px;
  font-weight: bold;
  color: #f56c6c;
}

.reject-reason {
  color: #f56c6c;
}

.actions {
  text-align: right;
}

.actions .el-button + .el-button {
  margin-left: 12px;
}
</style>
