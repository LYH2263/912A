<template>
  <div class="customer-list page-shell">
    <el-card>
      <template #header>
        <div class="card-header">
          <div class="card-header-text">
            <span class="card-title">客户列表</span>
            <span class="card-subtitle">管理客户档案信息与消费记录</span>
          </div>
          <el-button type="primary" @click="$router.push('/customers/create')" round>
            新增客户
          </el-button>
        </div>
      </template>

      <div class="filter-bar">
        <el-input
          v-model="searchQuery"
          placeholder="搜索客户姓名、手机、地址"
          clearable
          style="width: 300px"
          @keyup.enter="handleSearch"
          @clear="handleSearch"
        >
          <template #prefix>
            <el-icon><Search /></el-icon>
          </template>
        </el-input>
      </div>

      <el-table :data="customers" v-loading="loading" style="width: 100%; margin-top: 16px">
        <el-table-column label="客户信息" width="260">
          <template #default="{ row }">
            <div class="customer-info-cell">
              <div class="customer-name">{{ row.name }}</div>
              <div class="customer-phone">{{ row.phone }}</div>
            </div>
          </template>
        </el-table-column>
        <el-table-column prop="address" label="收货地址" />
        <el-table-column label="累计订单数" width="140">
          <template #default="{ row }">
            <el-tag type="primary" size="small">
              {{ row.order_count }} 单
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="累计消费额" width="160">
          <template #default="{ row }">
            <span class="total-spent">¥{{ row.total_spent.toFixed(2) }}</span>
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="建档时间" width="180" />
        <el-table-column label="操作" width="220" fixed="right">
          <template #default="{ row }">
            <el-button size="small" type="primary" plain @click="handleDetail(row)">详情</el-button>
            <el-button size="small" type="primary" @click="handleEdit(row)">编辑</el-button>
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
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Search } from '@element-plus/icons-vue'
import { customerApi } from '@/api/modules/customer'

const router = useRouter()
const customers = ref([])
const loading = ref(false)
const currentPage = ref(1)
const pageSize = ref(10)
const total = ref(0)
const searchQuery = ref('')

const fetchCustomers = async () => {
  loading.value = true
  try {
    const params = {
      page: currentPage.value,
      per_page: pageSize.value,
    }
    if (searchQuery.value) {
      params.search = searchQuery.value
    }
    const res = await customerApi.getCustomers(params)
    customers.value = res.data
    total.value = res.meta.total
  } catch (error) {
    ElMessage.error('获取客户列表失败')
  } finally {
    loading.value = false
  }
}

const handleSearch = () => {
  currentPage.value = 1
  fetchCustomers()
}

const handleDetail = (row) => {
  router.push(`/customers/${row.id}`)
}

const handleEdit = (row) => {
  router.push(`/customers/${row.id}/edit`)
}

const handleDelete = async (row) => {
  try {
    await ElMessageBox.confirm('确定要删除该客户吗？关联订单不会被删除，只会解除关联。', '提示', {
      type: 'warning',
    })
    await customerApi.deleteCustomer(row.id)
    ElMessage.success('删除成功')
    fetchCustomers()
  } catch (error) {
    if (error !== 'cancel') {
      ElMessage.error('删除失败')
    }
  }
}

const handleSizeChange = () => {
  currentPage.value = 1
  fetchCustomers()
}

const handleCurrentChange = () => {
  fetchCustomers()
}

onMounted(() => {
  fetchCustomers()
})
</script>

<style scoped>
.customer-list {
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

.customer-info-cell {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.customer-name {
  font-size: 14px;
  color: #111827;
  font-weight: 500;
}

.customer-phone {
  font-size: 12px;
  color: #6b7280;
}

.total-spent {
  font-weight: 600;
  color: #f56c6c;
}
</style>
