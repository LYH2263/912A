import request from '../request'

export const batchApi = {
  getBatches(params) {
    return request({
      url: '/product-batches',
      method: 'get',
      params,
    })
  },

  getBatchDetail(batchId) {
    return request({
      url: `/product-batches/${batchId}`,
      method: 'get',
    })
  },

  createBatch(data) {
    return request({
      url: '/product-batches',
      method: 'post',
      data,
    })
  },

  updateBatch(batchId, data) {
    return request({
      url: `/product-batches/${batchId}`,
      method: 'put',
      data,
    })
  },

  deleteBatch(batchId) {
    return request({
      url: `/product-batches/${batchId}`,
      method: 'delete',
    })
  },

  adjustBatchQuantity(batchId, data) {
    return request({
      url: `/product-batches/${batchId}/adjust-quantity`,
      method: 'post',
      data,
    })
  },

  markBatchUnsellable(batchId, data) {
    return request({
      url: `/product-batches/${batchId}/mark-unsellable`,
      method: 'post',
      data,
    })
  },

  getExpiringSoon(params) {
    return request({
      url: '/product-batches/expiring-soon',
      method: 'get',
      params,
    })
  },

  getExpired(params) {
    return request({
      url: '/product-batches/expired',
      method: 'get',
      params,
    })
  },

  getBatchSummary() {
    return request({
      url: '/product-batches/summary',
      method: 'get',
    })
  },

  scanBatchStatuses() {
    return request({
      url: '/product-batches/scan-statuses',
      method: 'post',
    })
  },

  getProductBatches(productId, params) {
    return request({
      url: `/product-batches/of-product/${productId}`,
      method: 'get',
      params,
    })
  },
}
