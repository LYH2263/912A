import request from '../request'

export const inventoryAlertApi = {
  getAlerts(params) {
    return request({
      url: '/inventory-alerts',
      method: 'get',
      params,
    })
  },

  getUnreadCount() {
    return request({
      url: '/inventory-alerts/unread-count',
      method: 'get',
    })
  },

  markAsRead(id) {
    return request({
      url: `/inventory-alerts/${id}/mark-read`,
      method: 'post',
    })
  },

  markAllAsRead() {
    return request({
      url: '/inventory-alerts/mark-all-read',
      method: 'post',
    })
  },

  scanAlerts() {
    return request({
      url: '/inventory-alerts/scan',
      method: 'post',
    })
  },
}
