<template>
  <div class="price-trend page-shell">
    <el-card>
      <template #header>
        <div class="card-header">
          <div class="card-header-text">
            <span class="card-title">价格趋势</span>
            <span class="card-subtitle">查看商品近90天价格变动趋势</span>
          </div>
        </div>
      </template>

      <div class="filter-section">
        <el-form :inline="true" :model="filterForm" class="filter-form">
          <el-form-item label="选择商品">
            <el-select
              v-model="filterForm.product_id"
              placeholder="请选择商品"
              filterable
              remote
              :remote-method="searchProducts"
              :loading="searchLoading"
              style="width: 360px"
              @change="handleProductChange"
            >
              <el-option
                v-for="product in productOptions"
                :key="product.id"
                :label="`${product.name} (${product.sku})`"
                :value="product.id"
              />
            </el-select>
          </el-form-item>
          <el-form-item label="时间范围">
            <el-select v-model="filterForm.days" style="width: 140px" @change="fetchTrendData">
              <el-option label="近7天" :value="7" />
              <el-option label="近30天" :value="30" />
              <el-option label="近60天" :value="60" />
              <el-option label="近90天" :value="90" />
            </el-select>
          </el-form-item>
        </el-form>
      </div>

      <div v-if="trendData" class="chart-section">
        <div class="stats-cards">
          <el-card class="stat-card" shadow="never">
            <div class="stat-label">当前价格</div>
            <div class="stat-value current">¥{{ trendData.current_price?.toFixed(2) || '0.00' }}</div>
          </el-card>
          <el-card class="stat-card" shadow="never">
            <div class="stat-label">商品名称</div>
            <div class="stat-value product-name">{{ trendData.product_name || '-' }}</div>
          </el-card>
          <el-card class="stat-card" shadow="never">
            <div class="stat-label">价格变动次数</div>
            <div class="stat-value">{{ priceChangeCount }}</div>
          </el-card>
          <el-card class="stat-card" shadow="never">
            <div class="stat-label">最高价 / 最低价</div>
            <div class="stat-value">
              <span class="price-high">¥{{ maxPrice?.toFixed(2) || '0.00' }}</span>
              <span class="price-sep">/</span>
              <span class="price-low">¥{{ minPrice?.toFixed(2) || '0.00' }}</span>
            </div>
          </el-card>
        </div>

        <div class="chart-wrapper">
          <v-chart
            class="price-chart"
            :option="chartOption"
            autoresize
          />
        </div>
      </div>

      <el-empty
        v-else-if="!loading && !filterForm.product_id"
        description="请选择商品查看价格趋势"
        class="empty-state"
      />
      <el-empty
        v-else-if="!loading && !hasPriceData"
        description="该商品暂无价格变动记录"
        class="empty-state"
      />
      <div v-if="loading" class="loading-state">
        <el-skeleton :rows="8" animated />
      </div>
    </el-card>
  </div>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue'
import { productApi } from '@/api/modules/product'
import { use } from 'echarts/core'
import VChart from 'vue-echarts'
import { CanvasRenderer } from 'echarts/renderers'
import { LineChart } from 'echarts/charts'
import {
  TitleComponent,
  TooltipComponent,
  GridComponent,
  LegendComponent,
  DataZoomComponent,
} from 'echarts/components'

use([
  CanvasRenderer,
  LineChart,
  TitleComponent,
  TooltipComponent,
  GridComponent,
  LegendComponent,
  DataZoomComponent,
])

const filterForm = reactive({
  product_id: null,
  days: 90,
})

const productOptions = ref([])
const searchLoading = ref(false)
const trendData = ref(null)
const loading = ref(false)

const searchProducts = async (query) => {
  if (!query) {
    productOptions.value = []
    return
  }
  searchLoading.value = true
  try {
    const res = await productApi.getProducts({ search: query, per_page: 20 })
    productOptions.value = res.data || []
  } catch (e) {
    console.error('搜索商品失败', e)
  } finally {
    searchLoading.value = false
  }
}

const fetchTrendData = async () => {
  if (!filterForm.product_id) return
  loading.value = true
  try {
    const res = await productApi.getPriceTrend(filterForm.product_id, {
      days: filterForm.days,
    })
    trendData.value = res.data
  } catch (e) {
    console.error('获取价格趋势数据失败', e)
  } finally {
    loading.value = false
  }
}

const handleProductChange = () => {
  trendData.value = null
  fetchTrendData()
}

const hasPriceData = computed(() => {
  return trendData.value && trendData.value.dates && trendData.value.dates.length > 0
})

const priceChangeCount = computed(() => {
  if (!trendData.value?.prices) return 0
  const prices = trendData.value.prices
  let count = 0
  for (let i = 1; i < prices.length; i++) {
    if (Math.abs(prices[i] - prices[i - 1]) > 0.0001) {
      count++
    }
  }
  return count
})

const maxPrice = computed(() => {
  if (!trendData.value?.prices) return null
  return Math.max(...trendData.value.prices)
})

const minPrice = computed(() => {
  if (!trendData.value?.prices) return null
  return Math.min(...trendData.value.prices)
})

const chartOption = computed(() => {
  if (!hasPriceData.value) return {}

  return {
    title: {
      text: '价格趋势图',
      left: 'center',
      textStyle: {
        fontSize: 16,
        fontWeight: 600,
        color: '#111827',
      },
    },
    tooltip: {
      trigger: 'axis',
      backgroundColor: 'rgba(255, 255, 255, 0.98)',
      borderColor: '#e5e7eb',
      borderWidth: 1,
      textStyle: {
        color: '#374151',
      },
      formatter: (params) => {
        const data = params[0]
        return `
          <div style="font-weight: 600; margin-bottom: 4px;">${data.axisValue}</div>
          <div>价格: <span style="color: #4f46e5; font-weight: 600;">¥${data.value.toFixed(2)}</span></div>
        `
      },
    },
    grid: {
      left: '3%',
      right: '4%',
      bottom: '15%',
      top: '15%',
      containLabel: true,
    },
    xAxis: {
      type: 'category',
      boundaryGap: false,
      data: trendData.value.dates,
      axisLine: {
        lineStyle: {
          color: '#e5e7eb',
        },
      },
      axisLabel: {
        color: '#6b7280',
        fontSize: 12,
      },
    },
    yAxis: {
      type: 'value',
      axisLine: {
        show: false,
      },
      axisTick: {
        show: false,
      },
      splitLine: {
        lineStyle: {
          color: '#f3f4f6',
          type: 'dashed',
        },
      },
      axisLabel: {
        color: '#6b7280',
        fontSize: 12,
        formatter: '¥{value}',
      },
    },
    dataZoom: [
      {
        type: 'inside',
        start: 0,
        end: 100,
      },
      {
        type: 'slider',
        start: 0,
        end: 100,
        height: 20,
        bottom: 10,
        borderColor: '#e5e7eb',
        fillerColor: 'rgba(99, 102, 241, 0.15)',
        handleStyle: {
          color: '#6366f1',
        },
      },
    ],
    series: [
      {
        name: '价格',
        type: 'line',
        smooth: true,
        symbol: 'circle',
        symbolSize: 6,
        data: trendData.value.prices,
        lineStyle: {
          width: 3,
          color: {
            type: 'linear',
            x: 0,
            y: 0,
            x2: 1,
            y2: 0,
            colorStops: [
              { offset: 0, color: '#6366f1' },
              { offset: 1, color: '#8b5cf6' },
            ],
          },
        },
        itemStyle: {
          color: '#6366f1',
          borderColor: '#fff',
          borderWidth: 2,
        },
        areaStyle: {
          color: {
            type: 'linear',
            x: 0,
            y: 0,
            x2: 0,
            y2: 1,
            colorStops: [
              { offset: 0, color: 'rgba(99, 102, 241, 0.25)' },
              { offset: 1, color: 'rgba(99, 102, 241, 0.02)' },
            ],
          },
        },
        markPoint: {
          data: [
            { type: 'max', name: '最高价' },
            { type: 'min', name: '最低价' },
          ],
          itemStyle: {
            color: '#f59e0b',
          },
        },
      },
    ],
  }
})
</script>

<style scoped>
.price-trend {
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

.filter-section {
  padding: 8px 0 24px;
  border-bottom: 1px solid #f3f4f6;
  margin-bottom: 24px;
}

.filter-form {
  margin: 0;
}

.stats-cards {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
  margin-bottom: 24px;
}

.stat-card {
  border-radius: 12px;
  border: 1px solid #f3f4f6;
}

.stat-label {
  font-size: 12px;
  color: #6b7280;
  margin-bottom: 8px;
}

.stat-value {
  font-size: 20px;
  font-weight: 600;
  color: #111827;
}

.stat-value.current {
  color: #4f46e5;
  font-size: 24px;
}

.stat-value.product-name {
  font-size: 14px;
  font-weight: 500;
  color: #374151;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.price-high {
  color: #ef4444;
  font-size: 16px;
}

.price-sep {
  color: #9ca3af;
  margin: 0 4px;
  font-size: 14px;
}

.price-low {
  color: #10b981;
  font-size: 16px;
}

.chart-wrapper {
  background: linear-gradient(180deg, #fafbff 0%, #f9fafb 100%);
  border-radius: 16px;
  padding: 20px;
  border: 1px solid #f3f4f6;
}

.price-chart {
  width: 100%;
  height: 420px;
}

.empty-state {
  padding: 80px 0;
}

.loading-state {
  padding: 40px 0;
}
</style>
