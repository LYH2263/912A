import request from '../request'

export const productApi = {
  getProducts(params) {
    return request({
      url: '/products',
      method: 'get',
      params,
    })
  },

  getProduct(id) {
    return request({
      url: `/products/${id}`,
      method: 'get',
    })
  },

  createProduct(data) {
    return request({
      url: '/products',
      method: 'post',
      data,
    })
  },

  updateProduct(id, data) {
    return request({
      url: `/products/${id}`,
      method: 'put',
      data,
    })
  },

  deleteProduct(id) {
    return request({
      url: `/products/${id}`,
      method: 'delete',
    })
  },

  getProductSkus(productId) {
    return request({
      url: `/products/${productId}`,
      method: 'get',
    })
  },

  batchAttachTags(data) {
    return request({
      url: '/products/batch/attach-tags',
      method: 'post',
      data,
    })
  },

  batchDetachTags(data) {
    return request({
      url: '/products/batch/detach-tags',
      method: 'post',
      data,
    })
  },

  getPriceHistories(productId, params) {
    return request({
      url: `/products/${productId}/price-histories`,
      method: 'get',
      params,
    })
  },

  getPriceTrend(productId, params) {
    return request({
      url: `/products/${productId}/price-trend`,
      method: 'get',
      params,
    })
  },
}
