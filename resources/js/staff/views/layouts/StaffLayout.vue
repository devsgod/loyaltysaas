<template>
  <v-app
    :style="{'background-color': backgroundColor}"
    >
    <v-navigation-drawer
      v-if="$vuetify.breakpoint.smAndDown"
      fixed
      clipped
      v-model="drawer"
      app
      right
      :style="{'background-color': campaign.theme.drawer.backgroundColor, 'color': campaign.theme.drawer.textColor}"
      >
      <div
  	    v-if="$auth.check()"
  	  	>
    		<v-flex
    			xs12
    			align-center
    			justify-center
    			layout
    			text-xs-center
    			>
          <router-link :to="{name: 'user.profile'}">
      			<v-avatar
      			  :tile="false"
      			  :size="80"
      			  class="mt-5 mb-4"
      			  >
      			  <img :src=$auth.user().avatar alt="avatar">
      			</v-avatar>
          </router-link>
    		</v-flex>
    		<v-flex
    			xs12
    			align-center
    			justify-center
    			layout
    			text-xs-center
    			>
          <router-link :to="{name: 'user.profile'}" :style="{'color': campaign.theme.drawer.textColor, 'text-decoration': 'none'}">
            <div class="title">{{ $auth.user().name }}</div>
            <div class="subtitle">{{ $auth.user().email }}</div>
          </router-link>
      	</v-flex>
  	  </div>
    <div
      v-if="! $auth.check()"
    	>
  		<v-flex
  			v-if="campaign.theme.logo"
  			xs12
  			align-center
  			justify-center
  			layout
  			text-xs-center
  			>
        <router-link :to="{name: 'home'}">
    			<v-avatar
    			  :tile="true"
    			  :size="128"
    			  class="mt-5 mb-4"
    			  >
    			  <img :src=campaign.theme.logo :alt="campaign.name">
    			</v-avatar>
        </router-link>
  		</v-flex>
  		<v-flex
  			xs12
  			align-center
  			justify-center
  			layout
  			text-xs-center
  			>
        <router-link :to="{name: 'dashboard'}" :style="{'color': campaign.theme.drawer.textColor, 'text-decoration': 'none'}">
          <div :class="{'mt-5' : ! campaign.theme.logo}" class="title">{{ campaign.title }}</div>
        </router-link>
    	 </v-flex>
	   </div>

      <v-list dense nav class="mt-4">
        <template v-for="(item, index) in nav">
          <v-divider
              v-if="item.divider"
              :inset="false"
              class="grey darken-2"
              :key="'nav_' + index"
            ></v-divider>
          <v-list-item 
            v-else
            :to="item.to"
            :style="item.route_name === $route.name ? highlighted : {}"
            :key="'nav_' + index"
            @click="(item.click == 'logout') ? $auth.logout() : null"
            >
            <v-list-item-icon v-if="!item.divider">
              <v-icon :style="{'color': item.route_name === $route.name ? highlighted.color : campaign.theme.drawer.textColor}" class="ml-4 nav-icon">{{ item.icon }}</v-icon>
            </v-list-item-icon>
            <v-list-item-title v-if="!item.divider" :style="{'color': item.route_name === $route.name ? highlighted.color : campaign.theme.drawer.textColor}">{{ item.name }}</v-list-item-title>
          </v-list-item>
        </template>
      </v-list>
    </v-navigation-drawer>

    <v-app-bar
      :color="campaign.theme.primaryColor"
      :style="{color: campaign.theme.primaryTextColor}"
      class="header"
      style="z-index:4"
      dense
      fixed
      clipped-right
      app
      flat
      height="96px"
      >
      <router-link v-if="campaign.theme.logo" :to="{name: 'dashboard'}" tag="img" :src="campaign.theme.logo" style="max-height:70%; max-width: 240px; cursor: pointer;" class="ml-2 mr-4" :alt="campaign.name"></router-link>
      <v-toolbar-title class="mr-5 align-center" v-if="campaign.headline || ! campaign.theme.logo">
        <v-spacer></v-spacer>
        <span class="title"><router-link :to="{name: 'dashboard'}" :style="{'color': campaign.theme.primaryTextColor, 'text-decoration': 'none'}">{{ campaign.title }}</router-link></span>
        <div class="subtitle-2" v-if="campaign.headline">{{ campaign.headline }}</div>
      </v-toolbar-title>
      <v-spacer></v-spacer>
      <v-toolbar-items>
        <v-btn class="hidden-sm-and-down" text :style="{color: campaign.theme.primaryTextColor}" v-for="(item, index) in topNav" v-if="!item.sideNavOnly" :key="'topNav_' + index" :to="item.to" exact>{{ item.name }}</v-btn>
      </v-toolbar-items>

      <v-menu bottom left nudge-bottom="60px" v-if="$vuetify.breakpoint.mdAndUp && $auth.check()" class="ml-3">
        <template v-slot:activator="{ on }">
          <v-btn
            icon
            large
            v-on="on"
          >
          <v-avatar
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
          <v-list-item @click="$router.push({name: 'user.profile'})">
            <v-list-item-content>
              <v-list-item-title>Profile</v-list-item-title>
            </v-list-item-content>
          </v-list-item>
          <v-list-item @click="$auth.logout()">
            <v-list-item-content>
              <v-list-item-title>Logout</v-list-item-title>
            </v-list-item-content>
          </v-list-item>
        </v-list>
      </v-menu>

      <v-app-bar-nav-icon class="hidden-md-and-up" @click.stop="drawer = !drawer" :style="{color: campaign.theme.primaryTextColor}"><v-icon v-if="!drawer">menu</v-icon><v-icon v-if="drawer">close</v-icon></v-app-bar-nav-icon>
    </v-app-bar>
    <v-content :style="{'padding-top': '96px !important', 'background-color': campaign.theme.backgroundColor}">
      <router-view name="primary"></router-view>
    </v-content>

    <v-footer absolute app class="pa-0" height="auto" :style="{'background-color': campaign.theme.drawer.backgroundColor, 'color': campaign.theme.drawer.textColor}">
    <v-card
      class="flex"
      text
      tile
    >
      <v-card-actions class="justify-center body-2" :style="{'background-color': campaign.theme.primaryColor, 'color': campaign.theme.primaryTextColor}">
        &copy;{{ new Date().getFullYear() }} — {{ campaign.business.name }} <span class="mx-2">&bull;</span> <span :style="{'color': campaign.theme.primaryTextColor}">Staff Area</span>
      </v-card-actions>
    </v-card>
    </v-footer>

    <confirm ref="confirm"></confirm>
    <snackbar ref="snackbar"></snackbar>
  </v-app>
</template>
<script>

  export default {
    name: 'app',
    data () {
      return {
        drawer: false
      }
    },
    created: function () {
      if (this.$auth.check() && typeof this.$auth.user().language === 'undefined') { 
        this.$auth.logout()
      }

      if (this.$auth.check() && typeof this.$auth.user().language !== 'undefined' && this.$auth.user().language !== 'undefined') { 
        this.$i18n.locale = this.$auth.user().language
      }
    },
    mounted() {
      this.$root.$confirm = this.$refs.confirm.open
      this.$root.$snackbar = this.$refs.snackbar.show

      // Remove loader
      document.getElementById('app-loader').remove();
    },
    computed: {
      topNav() {
        if (this.$auth.check()) {
          return [
            {
              name: this.$t('dashboard'),
              icon: 'dashboard',
              route_name: 'dashboard',
              to: {name: 'dashboard'}
            },
            {
              name: 'Points',
              icon: 'toll',
              route_name: 'points',
              to: {name: 'points'}
            },
            {
              name: 'Rewards',
              icon: 'fas fa-gift',
              route_name: 'rewards',
              to: {name: 'rewards'}
            }
          ]
        } else {
          return []
        }
      },
      nav() {
        let nav = Object.assign([], this.topNav);

        if (this.$auth.check()) {
          nav.push(
            { divider: true },
            {
              name: this.$t('profile'),
              icon: 'account_circle',
              route_name: 'user.profile',
              to: {name: 'user.profile'}
            },
            {
              name: this.$t('logout'),
              icon: 'power_settings_new',
              click: "logout"
            }
          )
        } else {
          nav.push(
            {
              name: this.$t('login'),
              icon: 'exit_to_app',
              route_name: 'login',
              to: {name: 'login'}
            }
          )
        }
        return nav
      },
      backgroundColor() {
        return this.$store.state.app.backgroundColor
      },
      campaign() {
        return this.$store.state.app.campaign
      },
      highlighted() {
        return {'background-color': this.$store.state.app.campaign.theme.drawer.highlightBackgroundColor, 'color': this.$store.state.app.campaign.theme.drawer.highlightTextColor}
      }
    }
  }
</script>
<style>
.nav-icon {
  position: relative;
  top:-1px;
  font-size: 16px !important;
}
.termsConsent .v-snack__content {
  height: auto !important;
}
</style>