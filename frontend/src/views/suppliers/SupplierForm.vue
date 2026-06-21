<template>
  <div class="supplier-form page-shell">
    <el-card>
      <template #header>
        <div class="card-header">
          <div class="card-header-text">
            <span class="card-title">{{ isEdit ? '编辑供应商' : '新增供应商' }}</span>
            <span class="card-subtitle">维护供应商基础信息</span>
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
        <el-form-item label="供应商名称" prop="name">
          <el-input v-model="form.name" placeholder="请输入供应商名称" />
        </el-form-item>
        <el-form-item label="联系人" prop="contact">
          <el-input v-model="form.contact" placeholder="请输入联系人姓名" />
        </el-form-item>
        <el-form-item label="联系电话" prop="phone">
          <el-input v-model="form.phone" placeholder="请输入联系电话" />
        </el-form-item>
        <el-form-item label="地址" prop="address">
          <el-input
            v-model="form.address"
            type="textarea"
            :rows="3"
            placeholder="请输入地址"
          />
        </el-form-item>
        <el-form-item label="合作状态" prop="status">
          <el-select v-model="form.status" placeholder="请选择合作状态">
            <el-option label="合作中" value="active" />
            <el-option label="已暂停" value="inactive" />
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
import { supplierApi } from '@/api/modules/supplier'

const route = useRoute()
const router = useRouter()
const formRef = ref(null)
const loading = ref(false)
const isEdit = ref(false)

const form = reactive({
  name: '',
  contact: '',
  phone: '',
  address: '',
  status: 'active',
})

const rules = {
  name: [{ required: true, message: '请输入供应商名称', trigger: 'blur' }],
}

const handleSubmit = async () => {
  if (!formRef.value) return

  await formRef.value.validate(async (valid) => {
    if (valid) {
      loading.value = true
      try {
        if (isEdit.value) {
          await supplierApi.updateSupplier(route.params.id, form)
          ElMessage.success('更新成功')
        } else {
          await supplierApi.createSupplier(form)
          ElMessage.success('创建成功')
        }
        router.push('/suppliers')
      } catch (error) {
        ElMessage.error(error.response?.data?.message || error.message || '操作失败')
      } finally {
        loading.value = false
      }
    }
  })
}

const loadSupplier = async () => {
  if (route.params.id) {
    isEdit.value = true
    try {
      const res = await supplierApi.getSupplier(route.params.id)
      Object.assign(form, res.data)
    } catch (error) {
      ElMessage.error('获取供应商信息失败')
    }
  }
}

onMounted(() => {
  loadSupplier()
})
</script>

<style scoped>
.supplier-form {
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
