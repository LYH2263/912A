import request from '../request'

export const supplierApi = {
  getSuppliers(params) {
    return request({
      url: '/suppliers',
      method: 'get',
      params,
    })
  },

  getAllSuppliers(params) {
    return request({
      url: '/suppliers/all',
      method: 'get',
      params,
    })
  },

  getSupplier(id) {
    return request({
      url: `/suppliers/${id}`,
      method: 'get',
    })
  },

  createSupplier(data) {
    return request({
      url: '/suppliers',
      method: 'post',
      data,
    })
  },

  updateSupplier(id, data) {
    return request({
      url: `/suppliers/${id}`,
      method: 'put',
      data,
    })
  },

  deleteSupplier(id) {
    return request({
      url: `/suppliers/${id}`,
      method: 'delete',
    })
  },

  toggleStatus(id) {
    return request({
      url: `/suppliers/${id}/toggle-status`,
      method: 'post',
    })
  },

  getProductCount(id) {
    return request({
      url: `/suppliers/${id}/product-count`,
      method: 'get',
    })
  },
}
