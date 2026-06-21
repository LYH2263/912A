<template>
  <div class="spec-matrix-editor">
    <div class="specs-section">
      <div class="section-header">
        <span class="section-title">规格管理</span>
        <el-button type="primary" size="small" @click="addSpec">添加规格</el-button>
      </div>
      
      <div v-for="(spec, specIndex) in specs" :key="specIndex" class="spec-item">
        <div class="spec-header">
          <el-input
            v-model="spec.name"
            placeholder="规格名称（如：颜色）"
            size="small"
            style="width: 150px"
          />
          <el-button type="danger" size="small" text @click="removeSpec(specIndex)">
            删除规格
          </el-button>
        </div>
        <div class="spec-values">
          <el-tag
            v-for="(value, valueIndex) in spec.values"
            :key="valueIndex"
            closable
            @close="removeSpecValue(specIndex, valueIndex)"
            style="margin-right: 8px"
          >
            {{ value }}
          </el-tag>
          <el-input
            v-model="newSpecValues[specIndex]"
            placeholder="添加规格值"
            size="small"
            style="width: 120px"
            @keyup.enter="addSpecValue(specIndex)"
          />
          <el-button size="small" @click="addSpecValue(specIndex)">添加</el-button>
        </div>
      </div>
    </div>

    <el-divider />

    <div class="skus-section">
      <div class="section-header">
        <span class="section-title">SKU 列表</span>
        <span class="section-tip">共 {{ skus.length }} 个 SKU</span>
      </div>

      <el-table v-if="skus.length > 0" :data="skus" border size="small">
        <el-table-column label="规格组合" min-width="200">
          <template #default="{ row }">
            <span class="spec-combo">
              {{ formatSpecCombo(row.spec_data) }}
            </span>
          </template>
        </el-table-column>
        <el-table-column label="SKU编码" width="180">
          <template #default="{ row }">
            <el-input v-model="row.sku" size="small" placeholder="请输入SKU" />
          </template>
        </el-table-column>
        <el-table-column label="售价" width="140">
          <template #default="{ row }">
            <el-input-number
              v-model="row.price"
              :min="0"
              :precision="2"
              size="small"
              controls-position="right"
              style="width: 100%"
            />
          </template>
        </el-table-column>
        <el-table-column label="成本价" width="140">
          <template #default="{ row }">
            <el-input-number
              v-model="row.cost_price"
              :min="0"
              :precision="2"
              size="small"
              controls-position="right"
              style="width: 100%"
            />
          </template>
        </el-table-column>
        <el-table-column label="库存" width="120">
          <template #default="{ row }">
            <el-input-number
              v-model="row.stock_quantity"
              :min="0"
              size="small"
              controls-position="right"
              style="width: 100%"
            />
          </template>
        </el-table-column>
        <el-table-column label="状态" width="100">
          <template #default="{ row }">
            <el-switch
              v-model="row.status"
              active-value="active"
              inactive-value="inactive"
              size="small"
            />
          </template>
        </el-table-column>
      </el-table>

      <el-empty v-else description="请添加规格和规格值以生成SKU" />
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, watch, nextTick } from 'vue'

const props = defineProps({
  modelValue: {
    type: Object,
    default: () => ({ specs: [], skus: [] }),
  },
})

const emit = defineEmits(['update:modelValue'])

const specs = ref([])
const skus = ref([])
const newSpecValues = reactive({})
const isInternalUpdate = ref(false)

const init = () => {
  isInternalUpdate.value = true
  if (props.modelValue && props.modelValue.specs) {
    specs.value = JSON.parse(JSON.stringify(props.modelValue.specs))
  }
  if (props.modelValue && props.modelValue.skus) {
    skus.value = JSON.parse(JSON.stringify(props.modelValue.skus))
  }
  syncNewSpecValues()
  nextTick(() => {
    isInternalUpdate.value = false
  })
}

const syncNewSpecValues = () => {
  specs.value.forEach((_, index) => {
    if (newSpecValues[index] === undefined) {
      newSpecValues[index] = ''
    }
  })
}

const addSpec = () => {
  specs.value.push({
    name: '',
    values: [],
  })
  syncNewSpecValues()
  generateSkus()
}

const removeSpec = (specIndex) => {
  specs.value.splice(specIndex, 1)
  delete newSpecValues[specIndex]
  generateSkus()
}

const addSpecValue = (specIndex) => {
  const value = newSpecValues[specIndex]?.trim()
  if (!value) return
  if (!specs.value[specIndex].values.includes(value)) {
    specs.value[specIndex].values.push(value)
  }
  newSpecValues[specIndex] = ''
  generateSkus()
}

const removeSpecValue = (specIndex, valueIndex) => {
  specs.value[specIndex].values.splice(valueIndex, 1)
  generateSkus()
}

const cartesianProduct = (arrays) => {
  if (arrays.length === 0) return []
  if (arrays.length === 1) {
    return arrays[0].map((item) => [item])
  }
  
  const result = []
  const firstArray = arrays[0]
  const restArrays = arrays.slice(1)
  const restProduct = cartesianProduct(restArrays)
  
  for (const item of firstArray) {
    for (const rest of restProduct) {
      result.push([item, ...rest])
    }
  }
  
  return result
}

const generateSkus = () => {
  const validSpecs = specs.value.filter((s) => s.name && s.values.length > 0)
  if (validSpecs.length === 0) {
    skus.value = []
    emitChange()
    return
  }

  const specValueArrays = validSpecs.map((spec) => spec.values)
  const combinations = cartesianProduct(specValueArrays)

  const existingSkuMap = {}
  skus.value.forEach((sku) => {
    const key = JSON.stringify(sku.spec_data)
    existingSkuMap[key] = sku
  })

  const newSkus = []
  combinations.forEach((combo) => {
    const specData = {}
    validSpecs.forEach((spec, index) => {
      specData[spec.name] = combo[index]
    })

    const key = JSON.stringify(specData)
    if (existingSkuMap[key]) {
      newSkus.push({ ...existingSkuMap[key] })
    } else {
      newSkus.push({
        sku: generateSkuCode(specData),
        price: 0,
        cost_price: null,
        stock_quantity: 0,
        image: null,
        spec_data: specData,
        status: 'active',
      })
    }
  })

  skus.value = newSkus
  emitChange()
}

const generateSkuCode = (specData) => {
  const values = Object.values(specData)
  return values.join('-')
}

const formatSpecCombo = (specData) => {
  if (!specData) return ''
  return Object.entries(specData)
    .map(([key, value]) => `${key}: ${value}`)
    .join('; ')
}

const emitChange = () => {
  if (isInternalUpdate.value) return
  emit('update:modelValue', {
    specs: specs.value,
    skus: skus.value,
  })
}

watch(
  () => props.modelValue,
  (newVal) => {
    if (newVal && !isInternalUpdate.value) {
      init()
    }
  },
  { deep: true }
)

watch(skus, emitChange, { deep: true })
watch(specs, emitChange, { deep: true })

init()
</script>

<style scoped>
.spec-matrix-editor {
  padding: 16px 0;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}

.section-title {
  font-size: 15px;
  font-weight: 600;
  color: #111827;
}

.section-tip {
  font-size: 12px;
  color: #6b7280;
}

.spec-item {
  padding: 12px;
  background: #f9fafb;
  border-radius: 8px;
  margin-bottom: 12px;
}

.spec-header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 10px;
}

.spec-values {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 8px;
}

.spec-combo {
  font-size: 13px;
  color: #374151;
}
</style>
