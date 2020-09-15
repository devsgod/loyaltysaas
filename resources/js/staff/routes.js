import Vue from 'vue'
import Router from 'vue-router'
import NProgress from 'nprogress'
import VueAnalytics from 'vue-analytics'

NProgress.configure({ showSpinner: false });

/* Layout */
import StaffLayout from './views/layouts/StaffLayout'

/* Public pages */
import Dashboard from './views/dashboard/index'

/* Auth */
import Login from './views/auth/login'
import PasswordEmail from './views/auth/passwords/email'
import ResetEmail from './views/auth/passwords/reset'
import Profile from './views/profile/index'

import Points from './views/points/index'
import PointsLink from './views/points/link'
import Rewards from './views/rewards/index'
import RewardsLink from './views/rewards/link'

/* Routes */
export const constantRouterMap = [
  {
    path: '/',
    component: StaffLayout,
    children: [{
      path: '',
      name: 'login',
      components: {
        primary: Login
      }
    }],
    meta: {
      auth: false
    }
  },
  {
    path: '/',
    component: StaffLayout,
    children: [{
      path: 'password/reset',
      name: 'password.email',
      components: {
        primary: PasswordEmail
      }
    }],
    meta: {
      auth: false
    }
  },
  {
    path: '/',
    component: StaffLayout,
    children: [{
      path: 'password/reset/:token',
      name: 'password.reset',
      components: {
        primary: ResetEmail
      }
    }],
    meta: {
      auth: false
    }
  },
  // Generic routes
  {
    path: '/',
    component: StaffLayout,
    children: [{
      path: 'profile',
      name: 'user.profile',
      components: {
        primary: Profile
      }
    }],
    meta: {
      auth: true
    }
  },
  // Staff routes
  {
    path: '/',
    component: StaffLayout,
    children: [{
      path: 'dashboard',
      name: 'dashboard',
      components: {
        primary: Dashboard
      }
    }],
    meta: {
      auth: true
    }
  },
  {
    path: '/',
    component: StaffLayout,
    children: [{
      path: 'points',
      name: 'points',
      components: {
        primary: Points
      }
    }],
    meta: {
      auth: true
    }
  },
  {
    path: '/',
    component: StaffLayout,
    children: [{
      path: 'points/link',
      name: 'points.link',
      components: {
        primary: PointsLink
      }
    }],
    meta: {
      auth: true
    }
  },
  {
    path: '/',
    component: StaffLayout,
    children: [{
      path: 'rewards/link',
      name: 'rewards.link',
      components: {
        primary: RewardsLink
      }
    }],
    meta: {
      auth: true
    }
  },
  {
    path: '/',
    component: StaffLayout,
    children: [{
      path: 'rewards',
      name: 'rewards',
      components: {
        primary: Rewards
      }
    }],
    meta: {
      auth: true
    }
  },
  { path: '*', redirect: '/', hidden: true } // Catch unkown routes
]

const router = new Router({
  scrollBehavior: () => ({ y: 0 }),
  routes: constantRouterMap
})

/*
Vue.use(VueAnalytics, {
  id: 'UA-xxxxxxxxx-x',
  router
})
*/

// This callback runs before every route change, including on page load.
/*
router.beforeEach((to, from, next) => {
  next()
})
*/

router.beforeResolve((to, from, next) => {
  if (to.name) {
      NProgress.start()
  }
  next()
})

router.afterEach((to, from) => {
  NProgress.done()
})

export default router
