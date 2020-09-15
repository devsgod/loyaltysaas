import Vue from 'vue'
import Router from 'vue-router'
import VueAnalytics from 'vue-analytics'

/* Layout */
import AppLayout from './views/layouts/AppLayout'
import DashboardLayout from './views/layouts/DashboardLayout'

/* Auth */
import AuthRegister from './views/auth/register'
import AuthLogin from './views/auth/login'
import AuthPasswordEmail from './views/auth/passwords/email'
import AuthResetEmail from './views/auth/passwords/reset'

/* Generic pages */
import AuthProfile from './views/app/profile/index'

/* Admin pages */
import AdminDashboard from './views/app/admin/dashboard/index'
import AdminUsers from './views/app/admin/users/index'
import AdminSettingsBranding from './views/app/admin/settings/branding'
import AdminSettingsPlans from './views/app/admin/settings/plans'

/* User pages */
import UserDashboard from './views/app/user/dashboard/index'
import UserCustomers from './views/app/user/customers/index'

import UserAnalyticsEarning from './views/app/user/analytics/earning'
import UserAnalyticsSpending from './views/app/user/analytics/spending'

import UserBusinesses from './views/app/user/businesses/index'
import UserSegments from './views/app/user/segments/index'
import UserStaff from './views/app/user/staff/index'

import UserCampaigns from './views/app/user/campaigns/index'
import UserBilling from './views/app/user/billing/index'
import UserRewards from './views/app/user/rewards/index'

/* Routes */
export const constantRouterMap = [
  // Generic routes
  { path: '/', redirect: '/login', hidden: true }, // Redirect root to login
  {
    path: '/',
    component: DashboardLayout,
    children: [{
      path: '/register',
      name: 'register',
      components: {
        primary: AuthRegister
      }
    }],
    meta: {
      auth: false
    }
  },
  {
    path: '/',
    component: DashboardLayout,
    children: [{
      path: '/login',
      name: 'login',
      components: {
        primary: AuthLogin
      }
    }],
    meta: {
      auth: false
    }
  },
  {
    path: '/',
    component: DashboardLayout,
    children: [{
      path: '/password/reset',
      name: 'password.email',
      components: {
        primary: AuthPasswordEmail
      }
    }],
    meta: {
      auth: false
    }
  },
  {
    path: '/',
    component: DashboardLayout,
    children: [{
      path: '/password/reset/:token',
      name: 'password.reset',
      components: {
        primary: AuthResetEmail
      }
    }],
    meta: {
      auth: false
    }
  },
  // Generic routes
  {
    path: '/',
    component: DashboardLayout,
    children: [{
      path: 'profile',
      name: 'profile',
      components: {
        primary: AuthProfile
      },
      meta: {
        parentMenu: 'settings'
      }
    }],
    meta: {
      auth: true
    }
  },
  // User routes
  {
    path: '/',
    component: DashboardLayout,
    children: [{
      path: 'dashboard',
      name: 'user.dashboard',
      components: {
        primary: UserDashboard
      }
    }],
    meta: {
      auth: true
    }
  },
  {
    path: '/',
    component: DashboardLayout,
    children: [{
      path: 'customers',
      name: 'user.customers',
      components: {
        primary: UserCustomers
      }
    }],
    meta: {
      auth: true
    }
  },
  {
    path: '/',
    component: DashboardLayout,
    children: [{
      path: 'analytics/earning',
      name: 'user.analytics.earning',
      components: {
        primary: UserAnalyticsEarning
      },
      meta: {
        parentMenu: 'analytics'
      }
    }],
    meta: {
      auth: true
    }
  },
  {
    path: '/',
    component: DashboardLayout,
    children: [{
      path: 'analytics/spending',
      name: 'user.analytics.spending',
      components: {
        primary: UserAnalyticsSpending
      },
      meta: {
        parentMenu: 'analytics'
      }
    }],
    meta: {
      auth: true
    }
  },
  {
    path: '/',
    component: DashboardLayout,
    children: [{
      path: 'businesses',
      name: 'user.businesses',
      components: {
        primary: UserBusinesses
      },
      meta: {
        parentMenu: 'merchants'
      }
    }],
    meta: {
      auth: true,
    }
  },
  {
    path: '/',
    component: DashboardLayout,
    children: [{
      path: 'segments',
      name: 'user.segments',
      components: {
        primary: UserSegments
      },
      meta: {
        parentMenu: 'merchants'
      }
    }],
    meta: {
      auth: true
    }
  },
  {
    path: '/',
    component: DashboardLayout,
    children: [{
      path: 'staff',
      name: 'user.staff',
      components: {
        primary: UserStaff
      },
      meta: {
        parentMenu: 'merchants'
      }
    }],
    meta: {
      auth: true
    }
  },
  {
    path: '/',
    component: DashboardLayout,
    children: [{
      path: 'campaigns',
      name: 'user.campaigns',
      components: {
        primary: UserCampaigns
      },
      meta: {
        parentMenu: 'programs'
      }
    }],
    meta: {
      auth: true
    }
  },
  {
    path: '/',
    component: DashboardLayout,
    children: [{
      path: 'rewards',
      name: 'user.rewards',
      components: {
        primary: UserRewards
      },
      meta: {
        parentMenu: 'programs'
      }
    }],
    meta: {
      auth: true
    }
  },
  {
    path: '/',
    component: DashboardLayout,
    children: [{
      path: 'billing',
      name: 'user.billing',
      components: {
        primary: UserBilling
      },
      meta: {
        parentMenu: 'settings'
      }
    }],
    meta: {
      auth: true
    }
  },
  // Admin routes
  {
    path: '/admin',
    component: DashboardLayout,
    children: [{
      path: 'dashboard',
      name: 'admin.dashboard',
      components: {
        primary: AdminDashboard
      }
    }],
    meta: {
      auth: {roles: [1], redirect: {name: 'login'}, forbiddenRedirect: '/403'}
    }
  },
  {
    path: '/admin',
    component: DashboardLayout,
    children: [{
      path: 'users',
      name: 'admin.users',
      components: {
        primary: AdminUsers
      }
    }],
    meta: {
      auth: {roles: [1], redirect: {name: 'login'}, forbiddenRedirect: '/403'}
    }
  },
  {
    path: '/admin',
    component: DashboardLayout,
    children: [{
      path: 'settings/branding',
      name: 'admin.settings.branding',
      components: {
        primary: AdminSettingsBranding
      },
      meta: {
        parentMenu: 'settings'
      }
    }],
    meta: {
      auth: {roles: [1], redirect: {name: 'login'}, forbiddenRedirect: '/403'}
    }
  },
  {
    path: '/admin',
    component: DashboardLayout,
    children: [{
      path: 'settings/plans',
      name: 'admin.settings.plans',
      components: {
        primary: AdminSettingsPlans
      },
      meta: {
        parentMenu: 'settings'
      }
    }],
    meta: {
      auth: {roles: [1], redirect: {name: 'login'}, forbiddenRedirect: '/403'}
    }
  },
  { path: '*', redirect: '/login', hidden: true } // Catch unkown routes
]

const router = new Router({
  //mode: 'history',
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
	if (Vue.auth.check() && Vue.auth.user().expired && to.name !== 'user.billing') {
		next('/billing')
	} else {
  	next()
	}
})

router.beforeResolve((to, from, next) => {
  if (to.name) {
  }
  next()
})

router.afterEach((to, from) => {
})

export default router