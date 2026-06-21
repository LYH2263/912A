import request from '../request'

export const dashboardApi = {
  // 获取仪表盘汇总数据
  getSummary() {
    return request({
      url: '/dashboard/summary',
      method: 'get',
    })
  },

  // 获取图表数据
  getCharts(params) {
    return request({
      url: '/dashboard/charts',
      method: 'get',
      params,
    })
  },
}
