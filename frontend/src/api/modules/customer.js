import request from '../request'

export const customerApi = {
  getCustomers(params) {
    return request({
      url: '/customers',
      method: 'get',
      params,
    })
  },

  getAllCustomers(params) {
    return request({
      url: '/customers/all',
      method: 'get',
      params,
    })
  },

  searchCustomers(keyword, limit = 20) {
    return request({
      url: '/customers/search',
      method: 'get',
      params: { keyword, limit },
    })
  },

  getCustomer(id) {
    return request({
      url: `/customers/${id}`,
      method: 'get',
    })
  },

  createCustomer(data) {
    return request({
      url: '/customers',
      method: 'post',
      data,
    })
  },

  updateCustomer(id, data) {
    return request({
      url: `/customers/${id}`,
      method: 'put',
      data,
    })
  },

  deleteCustomer(id) {
    return request({
      url: `/customers/${id}`,
      method: 'delete',
    })
  },
}
