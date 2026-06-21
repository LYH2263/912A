<template>
  <div class="supplier-list page-shell">
    <el-card>
      <template #header>
        <div class="card-header">
          <div class="card-header-text">
            <span class="card-title">供应商列表</span>
            <span class="card-subtitle">管理供应商信息与合作状态</span>
          </div>
          <el-button type="primary" @click="$router.push('/suppliers/create')" round>
            新增供应商
          </el-button>
        </div>
      </template>

      <div class="filter-bar">
        <el-input
          v-model="searchQuery"
          placeholder="搜索供应商名称、联系人、电话"
          clearable
          style="width: 300px"
          @keyup.enter="handleSearch"
          @clear="handleSearch"
        >
          <template #prefix>
            <el-icon><Search /></el-icon>
          </template>
        </el-input>
        <el-select v-model="statusFilter" placeholder="合作状态" clearable style="width: 140px" @change="handleSearch">
          <el-option label="合作中" value="active" />
          <el-option label="已暂停" value="inactive" />
        </el-select>
      </div>

      <el-table :data="suppliers" v-loading="loading" style="width: 100%; margin-top: 16px">
        <el-table-column prop="name" label="供应商名称" width="200">
          <template #default="{ row }">
            <div class="supplier-name-cell">
              <span class="name">{{ row.name }}</span>
            </div>
          </template>
        </el-table-column>
        <el-table-column prop="contact" label="联系人" width="120" />
        <el-table-column prop="phone" label="联系电话" width="140" />
        <el-table-column prop="address" label="地址" />
        <el-table-column label="商品数量" width="120">
          <template #default="{ row }">
            <el-tag type="primary" size="small">
              {{ row.product_count }} 个商品
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="status" label="合作状态" width="120">
          <template #default="{ row }">
            <el-tag :type="getStatusType(row.status)">
              {{ getStatusText(row.status) }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="操作" width="260" fixed="right">
          <template #default="{ row }">
            <el-button size="small" @click="handleToggleStatus(row)">
              {{ row.status === 'active' ? '暂停合作' : '恢复合作' }}
            </el-button>
            <el-button size="small" type="primary" plain @click="handleEdit(row)">编辑</el-button>
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
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Search } from '@element-plus/icons-vue'
import { supplierApi } from '@/api/modules/supplier'

const router = useRouter()
const suppliers = ref([])
const loading = ref(false)
const currentPage = ref(1)
const pageSize = ref(10)
const total = ref(0)
const searchQuery = ref('')
const statusFilter = ref('')

const getStatusType = (status) => {
  const map = {
    active: 'success',
    inactive: 'info',
  }
  return map[status] || 'info'
}

const getStatusText = (status) => {
  const map = {
    active: '合作中',
    inactive: '已暂停',
  }
  return map[status] || status
}

const fetchSuppliers = async () => {
  loading.value = true
  try {
    const params = {
      page: currentPage.value,
      per_page: pageSize.value,
    }
    if (searchQuery.value) {
      params.search = searchQuery.value
    }
    if (statusFilter.value) {
      params.status = statusFilter.value
    }
    const res = await supplierApi.getSuppliers(params)
    suppliers.value = res.data
    total.value = res.meta.total
  } catch (error) {
    ElMessage.error('获取供应商列表失败')
  } finally {
    loading.value = false
  }
}

const handleSearch = () => {
  currentPage.value = 1
  fetchSuppliers()
}

const handleEdit = (row) => {
  router.push(`/suppliers/${row.id}/edit`)
}

const handleToggleStatus = async (row) => {
  try {
    await ElMessageBox.confirm(
      `确定要${row.status === 'active' ? '暂停' : '恢复'}与「${row.name}」的合作吗？`,
      '提示',
      { type: 'warning' }
    )
    await supplierApi.toggleStatus(row.id)
    ElMessage.success('状态更新成功')
    fetchSuppliers()
  } catch (error) {
    if (error !== 'cancel') {
      ElMessage.error('操作失败')
    }
  }
}

const handleDelete = async (row) => {
  try {
    await ElMessageBox.confirm('确定要删除该供应商吗？', '提示', {
      type: 'warning',
    })
    await supplierApi.deleteSupplier(row.id)
    ElMessage.success('删除成功')
    fetchSuppliers()
  } catch (error) {
    if (error !== 'cancel') {
      ElMessage.error('删除失败')
    }
  }
}

const handleSizeChange = () => {
  currentPage.value = 1
  fetchSuppliers()
}

const handleCurrentChange = () => {
  fetchSuppliers()
}

onMounted(() => {
  fetchSuppliers()
})
</script>

<style scoped>
.supplier-list {
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

.supplier-name-cell .name {
  font-size: 14px;
  color: #111827;
  font-weight: 500;
}
</style>
