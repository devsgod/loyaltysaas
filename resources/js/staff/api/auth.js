import bearer from '@websanova/vue-auth/drivers/auth/bearer'
import axios from '@websanova/vue-auth/drivers/http/axios.1.x'
import router from '@websanova/vue-auth/drivers/router/vue-router.2.x'
import store from '../store'

let campaign_uuid = store.state.app.campaign.uuid

// Auth base configuration some of this options
// can be override in method calls
const config = {
  auth: bearer,
  http: axios,
  router: router,
  tokenDefaultName: 'staff-' + campaign_uuid,
  tokenStore: ['localStorage'],
  rolesVar: 'role',
  loginData: {url: 'staff/auth/login', method: 'POST', redirect: '/', fetchUser: true},
  logoutData: {url: 'staff/auth/logout', method: 'POST', redirect: '/', makeRequest: true},
  fetchData: {url: 'staff/auth/user?uuid=' + campaign_uuid, method: 'GET', enabled: true},
  refreshData: {url: 'staff/auth/refresh?uuid=' + campaign_uuid, method: 'GET', enabled: true, interval: 30},
  notFoundRedirect: {name: 'dashboard'}, // https://github.com/websanova/vue-auth/blob/master/docs/Privileges.md
}
export default config