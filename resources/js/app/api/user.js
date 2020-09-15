import request from '../utils/request'

export function getUserInfo(token) {
  return request({
    url: '/api/auth/user',
    method: 'get',
    params: { token }
  })
}