import Vue from 'vue'
import Router from 'vue-router'
import store from './store'
import NProgress from 'nprogress'
import VueAnalytics from 'vue-analytics'

NProgress.configure({ showSpinner: false });

let campaign_slug = store.state.app.campaign.slug
let campaign_host = store.state.app.campaign.host
let campaign_prefix = '/campaign' 

let pathname = document.location.pathname

if (campaign_host !== null) {
  campaign_slug = ''
  campaign_prefix = ''
}

/* Layout */
import CampaignLayout from './views/layouts/CampaignLayout'

/* Public pages */
import Home from './views/home/index'
import Legal from './views/legal/index'
import EarnPoints from './views/earn/index'
import Rewards from './views/rewards/index'
import Contact from './views/contact/index'
import MyPoints from './views/points/index'
//import CardsList from './views/cards/list'

/* Auth */
import Register from './views/auth/register'
import Login from './views/auth/login'
import PasswordEmail from './views/auth/passwords/email'
import ResetEmail from './views/auth/passwords/reset'
import Profile from './views/profile/index'

/* Routes */
export const constantRouterMap = [
  {
    path: campaign_prefix,
    component: CampaignLayout,
    children: [{
      path: campaign_slug + '/register',
      name: 'register',
      components: {
        primary: Register
      }
    }],
    meta: {
      auth: false
    }
  },
  {
    path: campaign_prefix,
    component: CampaignLayout,
    children: [{
      path: campaign_slug + '/login',
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
    path: campaign_prefix,
    component: CampaignLayout,
    children: [{
      path: campaign_slug + '/password/reset',
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
    path: campaign_prefix,
    component: CampaignLayout,
    children: [{
      path: campaign_slug + '/password/reset/:token',
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
    path: campaign_prefix,
    component: CampaignLayout,
    children: [{
      path: campaign_slug + '/profile',
      name: 'user.profile',
      components: {
        primary: Profile
      }
    }],
    meta: {
      auth: {roles: [1,2,3], redirect: {name: 'login'}, forbiddenRedirect: '/403'}
    }
  },
  {
    path: campaign_prefix,
    component: CampaignLayout,
    children: [{
      path: campaign_slug + '/legal',
      name: 'legal',
      components: {
        primary: Legal
      }
    }],
    meta: {
      auth: undefined
    }
  },
  // Campaign routes
  {
    path: campaign_prefix,
    component: CampaignLayout,
    children: [{
      path: campaign_slug + '',
      name: 'home',
      components: {
        primary: Home
      }
    }],
    meta: {
      auth: undefined
    }
  },
  {
    path: campaign_prefix,
    component: CampaignLayout,
    children: [{
      path: campaign_slug + '/earn',
      name: 'earn',
      components: {
        primary: EarnPoints
      }
    }],
    meta: {
      auth: undefined
    }
  },
  {
    path: campaign_prefix,
    component: CampaignLayout,
    children: [{
      path: campaign_slug + '/rewards',
      name: 'rewards',
      components: {
        primary: Rewards
      }
    }],
    meta: {
      auth: undefined
    }
  },
  {
    path: campaign_prefix,
    component: CampaignLayout,
    children: [{
      path: campaign_slug + '/contact',
      name: 'contact',
      components: {
        primary: Contact
      }
    }],
    meta: {
      auth: undefined
    }
  },
  {
    path: campaign_prefix,
    component: CampaignLayout,
    children: [{
      path: campaign_slug + '/points',
      name: 'points',
      components: {
        primary: MyPoints
      }
    }],
    meta: {
      auth: {roles: [1,2,3], redirect: {name: 'login'}, forbiddenRedirect: '/403'}
    }
  }
]

const router = new Router({
  mode: 'history',
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
router.beforeEach((to, from, next) => {
  if (typeof to.name === 'undefined') {
    next({name: 'home'})
  }
  next()
})

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
