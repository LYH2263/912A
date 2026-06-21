import request from '../request'

export const inventoryApi = {
  // 获取库存列表
  getInventory(params) {
    return request({
      url: '/inventory',
      method: 'get',
      params,
    })
  },

  // 获取商品库存详情
  getInventoryDetail(productId) {
    return request({
      url: `/inventory/${productId}`,
      method: 'get',
    })
  },

  // 更新商品总库存
  updateInventory(productId, data) {
    return request({
      url: `/inventory/${productId}`,
      method: 'put',
      data,
    })
  },

  // 更新SKU库存
  updateSkuInventory(skuId, data) {
    return request({
      url: `/inventory/sku/${skuId}`,
      method: 'put',
      data,
    })
  },
}
