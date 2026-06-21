import request from '../request'

export const productApi = {
  // 获取商品列表
  getProducts(params) {
    return request({
      url: '/products',
      method: 'get',
      params,
    })
  },

  // 获取商品详情
  getProduct(id) {
    return request({
      url: `/products/${id}`,
      method: 'get',
    })
  },

  // 创建商品
  createProduct(data) {
    return request({
      url: '/products',
      method: 'post',
      data,
    })
  },

  // 更新商品
  updateProduct(id, data) {
    return request({
      url: `/products/${id}`,
      method: 'put',
      data,
    })
  },

  // 删除商品
  deleteProduct(id) {
    return request({
      url: `/products/${id}`,
      method: 'delete',
    })
  },
}
