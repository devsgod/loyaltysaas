<template>
  <v-app
    :style="{'background-color': website.theme.backgroundColor}"
    >
    <v-navigation-drawer
      v-if="$vuetify.breakpoint.smAndDown"
      fixed
      clipped
      v-model="drawer"
      app
      right
      :style="{'background-color': website.theme.drawer.backgroundColor, 'color': website.theme.drawer.textColor}"
      >
  		<v-flex
  			v-if="website.theme.logo"
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
    			  <img :src=website.theme.logo :alt="account.app_name">
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
        <router-link :to="{name: 'home'}" :style="{'color': website.theme.drawer.textColor, 'text-decoration': 'none'}">
          <div :class="{'mt-5' : ! website.theme.logo}" class="title">{{ account.app_name }}</div>
        </router-link>
    	 </v-flex>

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
            exact
            :style="item.route_name === $route.name ? highlighted : {}"
            :key="'nav_' + index"
            >
            <v-list-item-icon v-if="!item.divider">
              <v-icon :style="{'color': item.route_name === $route.name ? highlighted.color : website.theme.drawer.textColor}" class="ml-4 nav-icon">{{ item.icon }}</v-icon>
            </v-list-item-icon>
            <v-list-item-title v-if="!item.divider" :style="{'color': item.route_name === $route.name ? highlighted.color : website.theme.drawer.textColor}">{{ item.name }}</v-list-item-title>
          </v-list-item>
        </template>
      </v-list>
    </v-navigation-drawer>

    <v-app-bar
      :color="website.theme.primaryColor"
      :style="{color: website.theme.primaryTextColor}"
      :class="{'elevation-5': offsetTop > 60}"
      style="z-index:4"
      dense
      fixed
      clipped-right
      app
      flat
      height="96px"
      >
      <router-link v-if="website.theme.logo" :to="{name: 'home'}" tag="img" :src="website.theme.logo" style="max-height:70%; max-width: 240px; cursor: pointer;" class="ml-2 mr-4" :alt="account.app_name"></router-link>
      <v-toolbar-title class="mr-5 align-center" v-if="account.app_headline || ! website.theme.logo">
        <v-spacer></v-spacer>
        <span class="title"><router-link :to="{name: 'home'}" :style="{'color': website.theme.primaryTextColor, 'text-decoration': 'none'}">{{ account.app_name }}</router-link></span>
        <div class="subtitle-2" v-if="account.app_headline">{{ account.app_headline }}</div>
      </v-toolbar-title>
      <v-spacer></v-spacer>
      <v-toolbar-items>
        <v-btn class="hidden-sm-and-down" text :style="{color: website.theme.primaryTextColor}" v-for="(item, index) in topNav" v-if="!item.sideNavOnly" :key="'topNav_' + index" :to="item.to" exact>{{ item.name }}</v-btn>
      </v-toolbar-items>

      <v-btn :color="website.theme.primary" small rounded class="ml-3 px-4 py-3" href="go#/login">
        Login
      </v-btn>

      <v-app-bar-nav-icon class="hidden-md-and-up" @click.stop="drawer = !drawer" :style="{color: website.theme.primaryTextColor}"><v-icon v-if="!drawer">menu</v-icon><v-icon v-if="drawer">close</v-icon></v-app-bar-nav-icon>
    </v-app-bar>

    <v-content :style="{'padding-top': '96px !important', 'background-color': website.theme.backgroundColor}" v-scroll="onScroll">
      <router-view name="primary"></router-view>
    </v-content>

    <v-footer absolute app class="pa-0" height="auto" :style="{'background-color': website.theme.drawer.backgroundColor, 'color': website.theme.drawer.textColor}">
    <v-card
      class="flex"
      text
      tile
    >
      <v-card-actions class="justify-center body-2" :style="{'background-color': website.theme.primaryColor, 'color': website.theme.primaryTextColor}">
        &copy;{{ new Date().getFullYear() }} — {{ account.app_name }} <span class="mx-2">&bull;</span> <router-link :to="{name: 'legal'}" :style="{'color': website.theme.primaryTextColor}">Terms & Conditions</router-link>
        <span class="mx-2">&bull;</span> <span v-html="account.app_contact"></span>
      </v-card-actions>
    </v-card>
    </v-footer>

    <v-snackbar
      v-model="showCookieConsent"
      :multi-line="true"
      :timeout="0"
      :bottom="true"
      :vertical="false"
      class="termsConsent"
    >
      By using our site, you acknowledge that you have read and understand our Terms and Conditions.
      <v-btn
        color="white"
        text
        :to="{ name: 'legal' }"
      >
        Terms
      </v-btn>
      <v-btn
        color="white"
        text
        icon
        @click="$store.dispatch('setCookieConsent', false)"
      >
        <v-icon>close</v-icon>
      </v-btn>
    </v-snackbar>

    <confirm ref="confirm"></confirm>
    <snackbar ref="snackbar"></snackbar>
  </v-app>
</template>
<script>

  export default {
    name: 'app',
    data () {
      return {
        offsetTop: 0,
        drawer: false
      }
    },
    created: function () {
    },
    mounted() {
      this.$root.$confirm = this.$refs.confirm.open
      this.$root.$snackbar = this.$refs.snackbar.show

      let appBarContent = document.querySelector('.v-app-bar .v-toolbar__content')
      appBarContent.classList.add('container');

      // Remove loader
      document.getElementById('app-loader').remove();
    },
    methods: {
      onScroll (e) {
        this.offsetTop = window.pageYOffset
      }
    },
    computed: {
      showCookieConsent () {
        return this.$store.state.app.showCookieConsent
      },
      topNav() {
        return [
          {
            name: 'Features',
            icon: 'star',
            route_name: 'features',
            to: {name: 'features'}
          },
          {
            name: 'Pricing',
            icon: 'toll',
            route_name: 'pricing',
            to: {name: 'pricing'}
          }
        ]
      },
      nav() {
        let nav = Object.assign([], this.topNav);

        nav.unshift(
          {
            name: 'Home',
            icon: 'home',
            route_name: 'home',
            to: {name: 'home'}
          }
        )

        return nav
      },
      account() {
        return this.$store.state.app.account
      },
      website() {
        return this.$store.state.app.website
      },
      highlighted() {
        return {'background-color': this.$store.state.app.website.theme.drawer.highlightBackgroundColor, 'color': this.$store.state.app.website.theme.drawer.highlightTextColor}
      }
    }
  }
</script>
<style>
.v-app-bar .v-toolbar__content {
  margin: auto;
  max-width: 990px;
}
</style>