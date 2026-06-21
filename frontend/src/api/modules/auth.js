import request from '../request'

export const authApi = {
  // 登录
  login(data) {
    return request({
      url: '/login',
      method: 'post',
      data,
    })
  },

  // 登出
  logout() {
    return request({
      url: '/logout',
      method: 'post',
    })
  },

  // 获取当前用户信息
  getMe() {
    return request({
      url: '/me',
      method: 'get',
    })
  },
}
