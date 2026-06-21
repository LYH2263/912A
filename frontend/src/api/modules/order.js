import request from '../request'

export const orderApi = {
  // 获取订单列表
  getOrders(params) {
    return request({
      url: '/orders',
      method: 'get',
      params,
    })
  },

  // 获取订单详情
  getOrder(id) {
    return request({
      url: `/orders/${id}`,
      method: 'get',
    })
  },

  // 创建订单
  createOrder(data) {
    return request({
      url: '/orders',
      method: 'post',
      data,
    })
  },

  // 更新订单状态
  updateOrderStatus(id, status) {
    return request({
      url: `/orders/${id}/status`,
      method: 'put',
      data: { status },
    })
  },
}
