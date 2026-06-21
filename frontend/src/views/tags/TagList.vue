<template>
  <div class="tag-list page-shell">
    <el-card>
      <template #header>
        <div class="card-header">
          <div class="card-header-text">
            <span class="card-title">标签管理</span>
            <span class="card-subtitle">管理商品标签、颜色与排序</span>
          </div>
          <el-button type="primary" @click="openCreateDialog" round>
            新增标签
          </el-button>
        </div>
      </template>

      <div class="filter-bar">
        <el-input
          v-model="searchQuery"
          placeholder="搜索标签名称"
          clearable
          style="width: 260px"
          @keyup.enter="handleSearch"
          @clear="handleSearch"
        >
          <template #prefix>
            <el-icon><Search /></el-icon>
          </template>
        </el-input>
      </div>

      <el-table :data="tags" v-loading="loading" style="width: 100%; margin-top: 16px">
        <el-table-column label="标签" width="200">
          <template #default="{ row }">
            <div class="tag-cell">
              <el-tag :style="{ backgroundColor: row.color, borderColor: row.color }" size="large" effect="dark">
                {{ row.name }}
              </el-tag>
            </div>
          </template>
        </el-table-column>
        <el-table-column label="颜色标识" width="180">
          <template #default="{ row }">
            <div class="color-cell">
              <span class="color-dot" :style="{ backgroundColor: row.color }" />
              <span class="color-code">{{ row.color }}</span>
            </div>
          </template>
        </el-table-column>
        <el-table-column prop="sort_order" label="排序" width="120" />
        <el-table-column label="关联商品数" width="140">
          <template #default="{ row }">
            <el-tag type="primary" size="small">
              {{ row.product_count || 0 }} 个商品
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="操作" width="220" fixed="right">
          <template #default="{ row }">
            <el-button size="small" type="primary" plain @click="openEditDialog(row)">编辑</el-button>
            <el-button size="small" type="danger" @click="handleDelete(row)">删除</el-button>
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
        style="margin-top: 20px; justify-content: flex-end;"
      />
    </el-card>

    <el-dialog
      v-model="dialogVisible"
      :title="dialogTitle"
      width="480px"
      :close-on-click-modal="false"
    >
      <el-form
        ref="formRef"
        :model="form"
        :rules="rules"
        label-width="100px"
      >
        <el-form-item label="标签名称" prop="name">
          <el-input v-model="form.name" placeholder="请输入标签名称，如：热销、新品、清仓" maxlength="50" show-word-limit />
        </el-form-item>
        <el-form-item label="颜色标识" prop="color">
          <div class="color-picker-row">
            <el-color-picker v-model="form.color" />
            <el-input v-model="form.color" placeholder="#409EFF" style="width: 160px; margin-left: 12px" />
          </div>
        </el-form-item>
        <el-form-item label="排序" prop="sort_order">
          <el-input-number v-model="form.sort_order" :min="0" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="submitting" @click="handleSubmit">
          保存
        </el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Search } from '@element-plus/icons-vue'
import { tagApi } from '@/api/modules/tag'

const tags = ref([])
const loading = ref(false)
const currentPage = ref(1)
const pageSize = ref(10)
const total = ref(0)
const searchQuery = ref('')

const dialogVisible = ref(false)
const dialogTitle = ref('新增标签')
const submitting = ref(false)
const formRef = ref(null)
const editingId = ref(null)

const presetColors = [
  '#f56c6c', '#e6a23c', '#67c23a', '#409eff', '#909399',
  '#ff6b9d', '#8e44ad', '#16a085', '#d35400', '#2980b9',
]

const form = reactive({
  name: '',
  color: '#409EFF',
  sort_order: 0,
})

const rules = {
  name: [{ required: true, message: '请输入标签名称', trigger: 'blur' }],
  color: [{ required: true, message: '请选择颜色', trigger: 'change' }],
}

const resetForm = () => {
  form.name = ''
  form.color = '#409EFF'
  form.sort_order = 0
  editingId.value = null
  formRef.value?.resetFields()
}

const openCreateDialog = () => {
  resetForm()
  dialogTitle.value = '新增标签'
  dialogVisible.value = true
}

const openEditDialog = (row) => {
  resetForm()
  editingId.value = row.id
  form.name = row.name
  form.color = row.color
  form.sort_order = row.sort_order
  dialogTitle.value = '编辑标签'
  dialogVisible.value = true
}

const fetchTags = async () => {
  loading.value = true
  try {
    const params = {
      page: currentPage.value,
      per_page: pageSize.value,
    }
    if (searchQuery.value) {
      params.search = searchQuery.value
    }
    const res = await tagApi.getTags(params)
    tags.value = res.data
    total.value = res.meta.total
  } catch (error) {
    ElMessage.error('获取标签列表失败')
  } finally {
    loading.value = false
  }
}

const handleSearch = () => {
  currentPage.value = 1
  fetchTags()
}

const handleSubmit = async () => {
  if (!formRef.value) return
  await formRef.value.validate(async (valid) => {
    if (valid) {
      submitting.value = true
      try {
        if (editingId.value) {
          await tagApi.updateTag(editingId.value, form)
          ElMessage.success('更新成功')
        } else {
          await tagApi.createTag(form)
          ElMessage.success('创建成功')
        }
        dialogVisible.value = false
        fetchTags()
      } catch (error) {
        ElMessage.error(error.response?.data?.message || error.message || '操作失败')
      } finally {
        submitting.value = false
      }
    }
  })
}

const handleDelete = async (row) => {
  try {
    await ElMessageBox.confirm(
      `确定要删除标签「${row.name}」吗？已关联的商品标签也会被解除。`,
      '提示',
      { type: 'warning' }
    )
    await tagApi.deleteTag(row.id)
    ElMessage.success('删除成功')
    fetchTags()
  } catch (error) {
    if (error !== 'cancel') {
      ElMessage.error('删除失败')
    }
  }
}

const handleSizeChange = () => {
  currentPage.value = 1
  fetchTags()
}

const handleCurrentChange = () => {
  fetchTags()
}

onMounted(() => {
  fetchTags()
})
</script>

<style scoped>
.tag-list {
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

.filter-bar {
  display: flex;
  gap: 12px;
  align-items: center;
}

.tag-cell {
  display: flex;
  align-items: center;
}

.color-cell {
  display: flex;
  align-items: center;
  gap: 10px;
}

.color-dot {
  width: 24px;
  height: 24px;
  border-radius: 6px;
  border: 1px solid #e5e7eb;
  display: inline-block;
}

.color-code {
  font-size: 13px;
  color: #4b5563;
  font-family: 'Monaco', 'Consolas', monospace;
}

.color-picker-row {
  display: flex;
  align-items: center;
}
</style>
