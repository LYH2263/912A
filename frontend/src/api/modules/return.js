import request from '../request'

export const returnApi = {
  getReturns(params) {
    return request({
      url: '/returns',
      method: 'get',
      params,
    })
  },

  getReturn(id) {
    return request({
      url: `/returns/${id}`,
      method: 'get',
    })
  },

  createReturn(data) {
    return request({
      url: '/returns',
      method: 'post',
      data,
    })
  },

  approveReturn(id) {
    return request({
      url: `/returns/${id}/approve`,
      method: 'post',
    })
  },

  rejectReturn(id, rejectReason) {
    return request({
      url: `/returns/${id}/reject`,
      method: 'post',
      data: { reject_reason: rejectReason },
    })
  },

  completeReturn(id) {
    return request({
      url: `/returns/${id}/complete`,
      method: 'post',
    })
  },
}
