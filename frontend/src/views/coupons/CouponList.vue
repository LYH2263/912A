<template>
  <div class="coupon-list page-shell">
    <el-card>
      <template #header>
        <div class="card-header">
          <div class="card-header-text">
            <span class="card-title">优惠券列表</span>
            <span class="card-subtitle">管理固定金额券与折扣比例券</span>
          </div>
          <el-button type="primary" @click="$router.push('/coupons/create')" round>
            新增优惠券
          </el-button>
        </div>
      </template>

      <div class="filter-bar">
        <el-input
          v-model="searchText"
          placeholder="搜索名称/代码"
          clearable
          style="width: 200px"
          @clear="fetchCoupons"
          @keyup.enter="fetchCoupons"
        />
        <el-select v-model="filterStatus" placeholder="状态" clearable style="width: 120px" @change="fetchCoupons">
          <el-option label="启用" value="active" />
          <el-option label="停用" value="inactive" />
        </el-select>
        <el-select v-model="filterType" placeholder="类型" clearable style="width: 120px" @change="fetchCoupons">
          <el-option label="固定金额" value="fixed" />
          <el-option label="折扣比例" value="percent" />
        </el-select>
        <el-button type="primary" @click="fetchCoupons">查询</el-button>
      </div>

      <el-table :data="coupons" v-loading="loading" style="width: 100%">
        <el-table-column prop="name" label="名称" width="160" />
        <el-table-column prop="code" label="代码" width="120" />
        <el-table-column prop="type" label="类型" width="100">
          <template #default="{ row }">
            <el-tag :type="row.type === 'fixed' ? '' : 'success'">
              {{ row.type === 'fixed' ? '固定金额' : '折扣比例' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="面值/折扣" width="120">
          <template #default="{ row }">
            <span v-if="row.type === 'fixed'">¥{{ row.value.toFixed(2) }}</span>
            <span v-else>{{ row.value }}%</span>
          </template>
        </el-table-column>
        <el-table-column label="最低消费" width="100">
          <template #default="{ row }">
            ¥{{ row.min_amount.toFixed(2) }}
          </template>
        </el-table-column>
        <el-table-column label="已领/总量" width="100">
          <template #default="{ row }">
            {{ row.used_quantity }}/{{ row.total_quantity }}
          </template>
        </el-table-column>
        <el-table-column label="每人限领" width="100">
          <template #default="{ row }">
            {{ row.per_user_limit }}次
          </template>
        </el-table-column>
        <el-table-column label="有效期" width="200">
          <template #default="{ row }">
            <span v-if="row.starts_at || row.expires_at">
              {{ row.starts_at || '不限' }} ~ {{ row.expires_at || '不限' }}
            </span>
            <span v-else>不限</span>
          </template>
        </el-table-column>
        <el-table-column prop="status" label="状态" width="80">
          <template #default="{ row }">
            <el-tag :type="row.status === 'active' ? 'success' : 'info'">
              {{ row.status === 'active' ? '启用' : '停用' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="操作" width="160">
          <template #default="{ row }">
            <el-button size="small" @click="handleEdit(row)">编辑</el-button>
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
import { couponApi } from '@/api/modules/coupon'

const router = useRouter()
const coupons = ref([])
const loading = ref(false)
const currentPage = ref(1)
const pageSize = ref(10)
const total = ref(0)
const searchText = ref('')
const filterStatus = ref('')
const filterType = ref('')

const fetchCoupons = async () => {
  loading.value = true
  try {
    const params = {
      page: currentPage.value,
      per_page: pageSize.value,
    }
    if (searchText.value) params.search = searchText.value
    if (filterStatus.value) params.status = filterStatus.value
    if (filterType.value) params.type = filterType.value

    const res = await couponApi.getCoupons(params)
    coupons.value = res.data
    total.value = res.meta.total
  } catch (error) {
    ElMessage.error('获取优惠券列表失败')
  } finally {
    loading.value = false
  }
}

const handleEdit = (row) => {
  router.push(`/coupons/${row.id}/edit`)
}

const handleDelete = async (row) => {
  try {
    await ElMessageBox.confirm('确定要删除该优惠券吗？', '提示', {
      type: 'warning',
    })
    await couponApi.deleteCoupon(row.id)
    ElMessage.success('删除成功')
    fetchCoupons()
  } catch (error) {
    if (error !== 'cancel') {
      ElMessage.error(error.response?.data?.message || '删除失败')
    }
  }
}

const handleSizeChange = () => {
  fetchCoupons()
}

const handleCurrentChange = () => {
  fetchCoupons()
}

onMounted(() => {
  fetchCoupons()
})
</script>

<style scoped>
.coupon-list {
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
  margin-bottom: 16px;
  align-items: center;
}
</style>
