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
}
