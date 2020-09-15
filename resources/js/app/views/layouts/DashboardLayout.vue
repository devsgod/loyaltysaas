<template>
  <v-app
    light
  >
    <v-navigation-drawer
      fixed
      :clipped="$vuetify.breakpoint.lgAndUp"
      :stateless="! $auth.check()"
      v-model="drawer"
      app
    >
      <v-list dense nav shaped key="navKey" class="pl-0">
        <v-list-item-group color="primary">
					<template v-for="(item, index) in nav">
						<v-layout
							v-if="item.heading"
							:key="item.heading"
							row
							align-center
						>
							<v-flex xs6>
								<v-subheader v-if="item.heading">
									{{ item.heading }}
								</v-subheader>
							</v-flex>
						</v-layout>
						<v-layout 
							row
							v-else-if="item.search"
							align-center
							:key="'nav_' + index"
						>
							<v-text-field
								text
								solo
								hide-details
								prepend-inner-icon="search"
								:placeholder="item.search"
								class="input-search"
							></v-text-field>
						</v-layout>

						<v-list-group
							v-else-if="item.children"
							:key="'nav_parent_' + index"
							:value="item.opened"
							no-action
							:sub-group="false"
							:prepend-icon="item.icon"
						>
							<template #activator>
								<v-list-item-content>
									<v-list-item-title>
									 {{ item.text }}
									</v-list-item-title>
								</v-list-item-content>
							</template>

							<v-list-item
								v-for="(child, i) in item.children"
								:key="'nav_child_' + i"
								:to="child.to"
								exact
							>
								<v-list-item-icon v-if="child.icon">
									<v-icon>{{ child.icon }}</v-icon>
								</v-list-item-icon>
								<v-list-item-content>
									<v-list-item-title>
										{{ child.text }}
									</v-list-item-title>
								</v-list-item-content>
							</v-list-item>
						</v-list-group>

						<v-list-item v-else :key="'nav_sub_' + index" :to="item.to" exact>
							<v-list-item-icon>
								<v-icon>{{ item.icon }}</v-icon>
							</v-list-item-icon>
							<v-list-item-title>
								{{ item.text }}
							</v-list-item-title>
						</v-list-item>

					</template>
        </v-list-item-group>
      </v-list>

    </v-navigation-drawer>
    <v-app-bar
      fixed
      clipped-left
      app
      flat
      class="darken-3 white--text"
      :color="app.app_color"
      height="64px"
      ___v-if="$auth.check()"
      >
      <v-toolbar-title style="margin-left:-5px">
        <v-app-bar-nav-icon @click.stop="drawer = !drawer" class="white--text" v-if="$auth.check()"><v-icon>menu</v-icon></v-app-bar-nav-icon>
        <span>{{ app.app_name }}</span><span class="overline" v-if="$auth.user().role == 1"> v{{ app.version }}</span>
        <v-progress-circular
          :width="2"
          :size="18"
          color="white"
          indeterminate
          class="ml-2"
          v-if="$store.state.app.loading"
        ></v-progress-circular>
      </v-toolbar-title>

      <v-spacer></v-spacer>
      <v-btn dark text v-if="!$auth.check()" :href="app.app_scheme + '://' + app.app_host">
        <v-icon color="white" size="15" class="mr-1">home</v-icon> Home
      </v-btn>
      <v-menu offset-y nudge-bottom="10px" v-if="$auth.check()">
        <template v-slot:activator="{ on }">
          <v-btn
            icon
            large
            v-on="on"
          >
          <v-avatar
            v-if="$auth.check()"
            :tile="false"
            :size="32"
            >
            <img :src=$auth.user().avatar alt="avatar">
          </v-avatar>
          </v-btn>
        </template>
        <v-list>
          <v-subheader>{{ $auth.user().email }}</v-subheader>
          <v-divider :inset="false" class="grey lighten-2"></v-divider>
          <v-list-item :to="{name: 'profile'}">
            <v-list-item-content>
              <v-list-item-title>Profile</v-list-item-title>
            </v-list-item-content>
          </v-list-item>
          <v-list-item v-if="$auth.user().role === 3" :to="{name: 'user.billing'}">
            <v-list-item-content>
              <v-list-item-title>Billing</v-list-item-title>
            </v-list-item-content>
          </v-list-item>
          <v-divider :inset="false" class="grey lighten-2"></v-divider>
          <v-list-item v-if="$auth.check() && !$auth.impersonating()" @click="$auth.logout()">
            <v-list-item-content>
              <v-list-item-title>Logout</v-list-item-title>
            </v-list-item-content>
          </v-list-item>
          <v-list-item v-if="$auth.check() && $auth.impersonating()" @click="$auth.unimpersonate({redirect: {name: 'admin.users'}})">
            <v-list-item-content>
              <v-list-item-title>Logout</v-list-item-title>
            </v-list-item-content>
          </v-list-item>
        </v-list>
      </v-menu>

    </v-app-bar>
    <v-content>
      <router-view name="primary"></router-view>
    </v-content>

    <confirm ref="confirm"></confirm>
    <snackbar ref="snackbar"></snackbar>
  </v-app>
</template>
<script>

  export default {
    name: 'app',
    data () {
      return {
        drawer: true,
        navKey: 1,
        menu: {
          analytics: false,
          merchants: false,
          programs: false,
          settings: false
        }
      }
    },
    mounted() {
      this.$root.$confirm = this.$refs.confirm.open
      this.$root.$snackbar = this.$refs.snackbar.show
    },
    watch: {
      $route (to, from) {

        this.drawer = (this.$vuetify.breakpoint.lgAndUp && this.$auth.check()) ? true : false

        if (to.meta.parentMenu) {
          this.menu.analytics = false
          this.menu.merchants = false
          this.menu.programs = false
          this.menu.settings = false

          this.menu[to.meta.parentMenu] = true
          this.navKey++;
        }
      }
    },
    created: function () {
      if (this.$auth.check() && typeof this.$auth.user().language === 'undefined') { 
        this.$auth.logout()
      }

      if (this.$route.path == '/dashboard' && this.$auth.user().role != 3) {
         this.$router.push({name: this.dashboard})
      }

      this.drawer = (this.$vuetify.breakpoint.lgAndUp && this.$auth.check()) ? true : false

      if (this.$auth.check() && typeof this.$auth.user().language !== 'undefined' && this.$auth.user().language !== 'undefined') { 
        this.$i18n.locale = this.$auth.user().language
      }

      // Open menu
      if (this.$route.meta.parentMenu) {
        this.menu[this.$route.meta.parentMenu] = true
        this.navKey++;
      }

      // Remove loader
      document.getElementById('app-loader').remove();
    },
    computed: {
      app() {
        return this.$store.getters.app
      },
      dashboard () {
        let dashboard ='user.dashboard'

        switch (parseInt(this.$auth.user().role)) {
          case 1: dashboard = 'admin.dashboard'; break;
        }
        return dashboard
      },
      nav () {
        // Not logged in
        if (this.$auth.check() === false) {
          return [
            {
              text: this.$t('login'),
              icon: 'fas fa-sign-in-alt',
              route_name: 'login',
              to: {name: 'login'}
            },
            {
              text: this.$t('sign_up'),
              icon: 'fas fa-user-plus',
              route_name: 'register',
              to: {name: 'register'}
            },
            {
              text: 'Reset password',
              icon: 'fas fa-key',
              route_name: 'password.email',
              to: {name: 'password.email'}
            }/*,
            {
              name: 'Privacy Policy',
              icon: 'fas fa-shield-alt',
              route_name: 'register',
              to: {name: 'register'}
            }*/
          ]
        }

        // Admin
        if (parseInt(this.$auth.user().role) === 1) {
          return [
            {
              text: this.$t('dashboard'),
              icon: 'dashboard',
              route_name: this.dashboard,
              to: {name: this.dashboard}
            },
            { heading: 'Admin' },
            {
              text: 'Users',
              icon: 'people',
              route_name: 'admin.users',
              to: {name: 'admin.users'}
            },
            {
              icon: 'settings',
              text: this.$t('settings'),
              opened: this.menu.settings,
              children: [
                {
                  text: this.$t('profile'),
                  icon: 'account_circle',
                  route_name: 'profile',
                  to: {name: 'profile'}
                },
                {
                  text: "Branding",
                  icon: 'style',
                  route_name: 'admin.settings.branding',
                  to: {name: 'admin.settings.branding'}
                },
                {
                  text: "Plans",
                  icon: 'credit_card',
                  route_name: 'admin.settings.plans',
                  to: {name: 'admin.settings.plans'}
                }
              ]
            }
          ]
        }

        // User
        if (parseInt(this.$auth.user().role) === 3) {
          return [
            /*{ search: 'Search customers' },*/
            {
              text: this.$t('home'),
              icon: 'home',
              route_name: this.dashboard,
              to: {name: this.dashboard}
            },
            { heading: this.$t('insights') },
            {
              text: this.$t('customers'),
              icon: 'contacts',
              route_name: 'user.customers',
              to: {name: 'user.customers'}
            },
            {
              icon: 'timeline',
              text: this.$t('analytics'),
              opened: this.menu.analytics,
              children: [
                {
                  text: this.$t('earning'),
                  icon: 'toll',
                  route_name: 'user.analytics.earning',
                  to: {name: 'user.analytics.earning'}
                },
                {
                  text: this.$t('spending'),
                  icon: 'fas fa-gift',
                  route_name: 'user.analytics.spending',
                  to: {name: 'user.analytics.spending'}
                }
              ]
            },
            { heading: 'Management' },
            {
              icon: 'store',
              text: this.$t('merchants'),
              opened: this.menu.merchants,
              children: [
                {
                  text: this.$t('businesses'),
                  icon: 'business',
                  route_name: 'user.businesses',
                  to: {name: 'user.businesses'}
                },
                {
                  text: this.$t('staff_members'),
                  icon: 'work',
                  route_name: 'user.staff',
                  to: {name: 'user.staff'}
                },
                {
                  text: this.$t('segments'),
                  icon: 'category',
                  route_name: 'user.segments',
                  to: {name: 'user.segments'}
                }
              ]
            },
            {
              icon: 'loyalty',
              text: 'Loyalty programs',
              opened: this.menu.programs,
              children: [
                {
                  text: this.$t('campaigns'),
                  icon: 'record_voice_over',
                  route_name: 'user.campaigns',
                  to: {name: 'user.campaigns'}
                },
                {
                  text: this.$t('rewards'),
                  icon: 'fas fa-gift',
                  route_name: 'user.rewards',
                  to: {name: 'user.rewards'}
                }
              ]
            },
            {
              icon: 'settings',
              text: this.$t('settings'),
              opened: this.menu.settings,
              children: [
                {
                  text: this.$t('profile'),
                  icon: 'account_circle',
                  route_name: 'profile',
                  to: {name: 'profile'}
                },
                {
                  text: this.$t('billing'),
                  icon: 'credit_card',
                  route_name: 'user.billing',
                  to: {name: 'user.billing'}
                }
              ]
            }
          ]
        }
      }
    }
  }
</script>
<style>
.v-application--is-ltr .v-list--dense.v-list--nav .v-list-group--no-action > .v-list-group__items > div > .v-list-item {
    padding-left: 48px;
}
.v-navigation-drawer .v-list-item__icon:not(.v-list-group__header__append-icon) {
  margin-right: 15px !important;
}
.v-list__group__header .v-list__group__header__prepend-icon{
  padding-right: 0px;
}
.v-navigation-drawer .v-subheader{
  text-transform: uppercase;
  font-size: 11px;
  letter-spacing: .8px;
  padding: 16px 0 8px 24px;
  color: rgba(0,0,0,0.54);
  font-weight: 500;
/*  letter-spacing: -0.06px;*/
}
.input-search input[type="text"]{
  font-size: 13px;
}
.input-search.v-text-field--solo .v-input__slot{
  padding-top:12px;
  padding-bottom: 12px;
  border-bottom: 1px solid rgba(0,0,0,.12);
}
.input-search .v-input__icon {
  min-width: 34px;
}
.input-search .v-icon {
  color: #777 !important;
  font-size: 22px !important;
}
.input-search input::-webkit-input-placeholder { /* Chrome/Opera/Safari */
  color: #777;
}
.input-search input::-moz-placeholder { /* Firefox 19+ */
  color: #777;
}
.input-search input:-ms-input-placeholder { /* IE 10+ */
  color: #777;
}
.v-list-group .v-list-item__icon.v-list-group__header__append-icon {
    min-width: 32px;
}
.v-list--nav .v-list-group .v-list-group__header .v-list-item {
    padding: 0 8px 0 0 !important;
}
</style>