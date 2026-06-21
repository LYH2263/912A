import request from '../request'

export const reviewApi = {
  getReviews(params) {
    return request({
      url: '/reviews',
      method: 'get',
      params,
    })
  },

  getReview(id) {
    return request({
      url: `/reviews/${id}`,
      method: 'get',
    })
  },

  createReview(data) {
    return request({
      url: '/reviews',
      method: 'post',
      data,
    })
  },

  updateReview(id, data) {
    return request({
      url: `/reviews/${id}`,
      method: 'put',
      data,
    })
  },

  deleteReview(id) {
    return request({
      url: `/reviews/${id}`,
      method: 'delete',
    })
  },

  approveReview(id) {
    return request({
      url: `/reviews/${id}/approve`,
      method: 'post',
    })
  },

  rejectReview(id) {
    return request({
      url: `/reviews/${id}/reject`,
      method: 'post',
    })
  },

  toggleVisibility(id) {
    return request({
      url: `/reviews/${id}/toggle-visibility`,
      method: 'post',
    })
  },

  getProductReviews(productId, params) {
    return request({
      url: `/reviews/products/${productId}`,
      method: 'get',
      params,
    })
  },

  getProductSummary(productId) {
    return request({
      url: `/reviews/products/${productId}/summary`,
      method: 'get',
    })
  },

  getProductsSummary(params) {
    return request({
      url: '/reviews/products-summary',
      method: 'get',
      params,
    })
  },

  getStatistics() {
    return request({
      url: '/reviews/statistics',
      method: 'get',
    })
  },
}
