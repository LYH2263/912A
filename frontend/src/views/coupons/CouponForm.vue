<template>
  <div class="coupon-form page-shell">
    <el-card>
      <template #header>
        <div class="card-header">
          <div class="card-header-text">
            <span class="card-title">{{ isEdit ? '编辑优惠券' : '新增优惠券' }}</span>
            <span class="card-subtitle">设置优惠券类型、面值与使用限制</span>
          </div>
          <el-button @click="$router.back()" round>返回</el-button>
        </div>
      </template>

      <el-form
        ref="formRef"
        :model="form"
        :rules="rules"
        label-width="120px"
        style="max-width: 600px"
      >
        <el-form-item label="优惠券名称" prop="name">
          <el-input v-model="form.name" placeholder="请输入优惠券名称" />
        </el-form-item>
        <el-form-item label="优惠券代码" prop="code">
          <el-input v-model="form.code" placeholder="如 SAVE20" :disabled="isEdit" />
        </el-form-item>
        <el-form-item label="优惠券类型" prop="type">
          <el-select v-model="form.type" placeholder="请选择类型" :disabled="isEdit" @change="handleTypeChange">
            <el-option label="固定金额" value="fixed" />
            <el-option label="折扣比例" value="percent" />
          </el-select>
        </el-form-item>
        <el-form-item :label="form.type === 'fixed' ? '优惠金额' : '折扣比例'" prop="value">
          <el-input-number
            v-model="form.value"
            :min="form.type === 'fixed' ? 0.01 : 1"
            :max="form.type === 'percent' ? 100 : 999999"
            :precision="form.type === 'fixed' ? 2 : 0"
            :step="form.type === 'fixed' ? 1 : 1"
          />
          <span style="margin-left: 8px; color: #6b7280">
            {{ form.type === 'fixed' ? '元' : '%' }}
          </span>
        </el-form-item>
        <el-form-item label="最低消费" prop="min_amount">
          <el-input-number v-model="form.min_amount" :min="0" :precision="2" />
          <span style="margin-left: 8px; color: #6b7280">元，0 表示无门槛</span>
        </el-form-item>
        <el-form-item label="总发行量" prop="total_quantity">
          <el-input-number v-model="form.total_quantity" :min="1" />
          <span style="margin-left: 8px; color: #6b7280">张</span>
        </el-form-item>
        <el-form-item label="每人限领" prop="per_user_limit">
          <el-input-number v-model="form.per_user_limit" :min="1" />
          <span style="margin-left: 8px; color: #6b7280">次</span>
        </el-form-item>
        <el-form-item label="开始时间" prop="starts_at">
          <el-date-picker
            v-model="form.starts_at"
            type="datetime"
            placeholder="可选"
            format="YYYY-MM-DD HH:mm:ss"
            value-format="YYYY-MM-DD HH:mm:ss"
          />
        </el-form-item>
        <el-form-item label="结束时间" prop="expires_at">
          <el-date-picker
            v-model="form.expires_at"
            type="datetime"
            placeholder="可选"
            format="YYYY-MM-DD HH:mm:ss"
            value-format="YYYY-MM-DD HH:mm:ss"
          />
        </el-form-item>
        <el-form-item label="状态" prop="status">
          <el-select v-model="form.status" placeholder="请选择状态">
            <el-option label="启用" value="active" />
            <el-option label="停用" value="inactive" />
          </el-select>
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
import { couponApi } from '@/api/modules/coupon'

const route = useRoute()
const router = useRouter()
const formRef = ref(null)
const loading = ref(false)
const isEdit = ref(false)

const form = reactive({
  name: '',
  code: '',
  type: 'fixed',
  value: 10,
  min_amount: 0,
  total_quantity: 100,
  per_user_limit: 1,
  starts_at: '',
  expires_at: '',
  status: 'active',
})

const rules = {
  name: [{ required: true, message: '请输入优惠券名称', trigger: 'blur' }],
  code: [
    { required: true, message: '请输入优惠券代码', trigger: 'blur' },
    { pattern: /^[A-Za-z0-9_]+$/, message: '代码只允许字母、数字和下划线', trigger: 'blur' },
  ],
  type: [{ required: true, message: '请选择优惠券类型', trigger: 'change' }],
  value: [{ required: true, message: '请输入优惠面值', trigger: 'blur' }],
  total_quantity: [{ required: true, message: '请输入总发行量', trigger: 'blur' }],
  per_user_limit: [{ required: true, message: '请输入每人限领次数', trigger: 'blur' }],
}

const handleTypeChange = () => {
  if (form.type === 'percent') {
    form.value = Math.min(form.value, 100)
  }
}

const handleSubmit = async () => {
  if (!formRef.value) return

  await formRef.value.validate(async (valid) => {
    if (!valid) return

    loading.value = true
    try {
      const payload = { ...form }
      if (!payload.starts_at) delete payload.starts_at
      if (!payload.expires_at) delete payload.expires_at

      if (isEdit.value) {
        await couponApi.updateCoupon(route.params.id, payload)
        ElMessage.success('更新成功')
      } else {
        await couponApi.createCoupon(payload)
        ElMessage.success('创建成功')
      }
      router.push('/coupons')
    } catch (error) {
      ElMessage.error(error.response?.data?.message || '操作失败')
    } finally {
      loading.value = false
    }
  })
}

onMounted(async () => {
  if (route.params.id) {
    isEdit.value = true
    try {
      const res = await couponApi.getCoupon(route.params.id)
      const data = res.data
      Object.assign(form, {
        name: data.name,
        code: data.code,
        type: data.type,
        value: data.value,
        min_amount: data.min_amount,
        total_quantity: data.total_quantity,
        per_user_limit: data.per_user_limit,
        starts_at: data.starts_at || '',
        expires_at: data.expires_at || '',
        status: data.status,
      })
    } catch (error) {
      ElMessage.error('获取优惠券信息失败')
    }
  }
})
</script>

<style scoped>
.coupon-form {
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
</style>
