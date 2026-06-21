import request from '../request'

export const couponApi = {
  getCoupons(params) {
    return request({
      url: '/coupons',
      method: 'get',
      params,
    })
  },

  getCoupon(id) {
    return request({
      url: `/coupons/${id}`,
      method: 'get',
    })
  },

  createCoupon(data) {
    return request({
      url: '/coupons',
      method: 'post',
      data,
    })
  },

  updateCoupon(id, data) {
    return request({
      url: `/coupons/${id}`,
      method: 'put',
      data,
    })
  },

  deleteCoupon(id) {
    return request({
      url: `/coupons/${id}`,
      method: 'delete',
    })
  },

  calculate(data) {
    return request({
      url: '/coupons/calculate',
      method: 'post',
      data,
    })
  },

  getAvailable(params) {
    return request({
      url: '/coupons/available',
      method: 'get',
      params,
    })
  },
}
