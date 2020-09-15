import request from '../utils/request'

export function loginByUsername(username, password) {
  const data = {
    username,
    password
  }
  return request({
    url: '/campaign/api/login',
    method: 'post',
    data
  })
}

export function logout() {
  return request({
    url: '/campaign/api/logout',
    method: 'post'
  })
}

export function getUserInfo(token) {
  return request({
    url: '/campaign/api/user/info',
    method: 'get',
    params: { token }
  })
}
