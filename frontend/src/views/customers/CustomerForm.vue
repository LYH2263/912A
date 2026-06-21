<template>
  <div class="customer-form page-shell">
    <el-card>
      <template #header>
        <div class="card-header">
          <div class="card-header-text">
            <span class="card-title">{{ isEdit ? '编辑客户' : '新增客户' }}</span>
            <span class="card-subtitle">维护客户基础档案信息</span>
          </div>
        </div>
      </template>

      <el-form
        ref="formRef"
        :model="form"
        :rules="rules"
        label-width="100px"
        style="max-width: 600px"
      >
        <el-form-item label="客户姓名" prop="name">
          <el-input v-model="form.name" placeholder="请输入客户姓名" />
        </el-form-item>
        <el-form-item label="手机号码" prop="phone">
          <el-input v-model="form.phone" placeholder="请输入手机号码" />
        </el-form-item>
        <el-form-item label="收货地址" prop="address">
          <el-input
            v-model="form.address"
            type="textarea"
            :rows="3"
            placeholder="请输入收货地址"
          />
        </el-form-item>
        <el-form-item v-if="isEdit">
          <div class="stats-info">
            <el-descriptions :column="2" border size="small" style="width: 400px">
              <el-descriptions-item label="累计订单数">
                {{ customerStats.order_count }} 单
              </el-descriptions-item>
              <el-descriptions-item label="累计消费额">
                ¥{{ customerStats.total_spent.toFixed(2) }}
              </el-descriptions-item>
            </el-descriptions>
          </div>
        </el-form-item>
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
import { customerApi } from '@/api/modules/customer'

const route = useRoute()
const router = useRouter()
const formRef = ref(null)
const loading = ref(false)
const isEdit = ref(false)
const customerStats = reactive({
  order_count: 0,
  total_spent: 0,
})

const form = reactive({
  name: '',
  phone: '',
  address: '',
})

const rules = {
  name: [{ required: true, message: '请输入客户姓名', trigger: 'blur' }],
  phone: [
    { required: true, message: '请输入手机号码', trigger: 'blur' },
    {
      pattern: /^1[3-9]\d{9}$/,
      message: '请输入正确的手机号码',
      trigger: 'blur',
    },
  ],
}

const handleSubmit = async () => {
  if (!formRef.value) return

  await formRef.value.validate(async (valid) => {
    if (valid) {
      loading.value = true
      try {
        if (isEdit.value) {
          await customerApi.updateCustomer(route.params.id, form)
          ElMessage.success('更新成功')
        } else {
          await customerApi.createCustomer(form)
          ElMessage.success('创建成功')
        }
        router.push('/customers')
      } catch (error) {
        ElMessage.error(error.response?.data?.message || error.message || '操作失败')
      } finally {
        loading.value = false
      }
    }
  })
}

const loadCustomer = async () => {
  if (route.params.id) {
    isEdit.value = true
    try {
      const res = await customerApi.getCustomer(route.params.id)
      Object.assign(form, {
        name: res.data.name,
        phone: res.data.phone,
        address: res.data.address,
      })
      customerStats.order_count = res.data.order_count
      customerStats.total_spent = res.data.total_spent
    } catch (error) {
      ElMessage.error('获取客户信息失败')
    }
  }
}

onMounted(() => {
  loadCustomer()
})
</script>

<style scoped>
.customer-form {
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

.stats-info {
  padding: 8px 0;
}
</style>
