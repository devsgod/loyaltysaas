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
    <div v-if="! $auth.check()">
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
        <router-link :to="{name: 'home'}" :style="{'color': campaign.theme.drawer.textColor, 'text-decoration': 'none'}">
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
            exact
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
<!--
    <v-system-bar app height="36" :style="{'background-color': campaign.theme.primaryColor, 'color': campaign.theme.primaryTextColor}" v-if="Object.keys(campaign.externalUrls).length > 0">
      <span v-for="(item, index) in campaign.externalUrls" :key="'topHeader_' + index">
        <a :href="item.href" :style="{'color': campaign.theme.primaryTextColor}">{{ item.text }}</a>
        <span class="mx-2 bull" v-if="index < Object.keys(campaign.externalUrls).length - 1">&bull;</span>
      </span>
    </v-system-bar>
-->
    <v-app-bar
      :color="campaign.theme.primaryColor"
      :style="{color: campaign.theme.primaryTextColor}"
      style="z-index: 4;"
      class="header"
      dense
      fixed
      clipped-right
      app
      flat
      height="96px"
    >
      <router-link v-if="campaign.theme.logo" :to="{name: 'home'}" tag="img" :src="campaign.theme.logo" style="max-height:70%; max-width: 240px; cursor: pointer;" class="ml-2 mr-4" :alt="campaign.name"></router-link>
      <v-toolbar-title class="mr-5 align-center" v-if="campaign.headline || ! campaign.theme.logo">
        <v-spacer></v-spacer>
        <span class="title"><router-link :to="{name: 'home'}" :style="{'color': campaign.theme.primaryTextColor, 'text-decoration': 'none'}">{{ campaign.title }}</router-link></span>
        <div class="subtitle-2" v-if="campaign.headline">{{ campaign.headline }}</div>
      </v-toolbar-title>
      <v-spacer></v-spacer>
      <v-toolbar-items>
        <v-btn class="hidden-sm-and-down" text :style="{color: campaign.theme.primaryTextColor}" v-for="(item, index) in topNav" v-if="!item.sideNavOnly" :key="'topNav_' + index" :to="item.to" exact>{{ item.name }}</v-btn>
      </v-toolbar-items>
      <v-menu bottom left nudge-bottom="60px" v-if="$vuetify.breakpoint.mdAndUp && $auth.check()">
        <template v-slot:activator="{ on }">
          <v-btn
            icon
            large
            v-on="on"
            class="ml-3"
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
              <v-list-item-title>{{ $t('profile') }}</v-list-item-title>
            </v-list-item-content>
          </v-list-item>
          <v-list-item @click="$auth.logout()">
            <v-list-item-content>
              <v-list-item-title>{{ $t('logout') }}</v-list-item-title>
            </v-list-item-content>
          </v-list-item>
        </v-list>
      </v-menu>
      <v-menu bottom left nudge-bottom="60px" v-if="languages !== null && languages.length > 1">
        <template v-slot:activator="{ on }">
          <v-btn
            icon
            large
            v-on="on"
            class="ml-3"
            :style="{'color': campaign.theme.primaryTextColor}"
          >
            {{ $t('language_abbr') }}
          </v-btn>
        </template>
        <v-list>
          <v-list-item @click="changeLang(item.code)" v-for="(item, index) in languages" :key="'languages_' + index">
            <v-list-item-content>
              <v-list-item-title>{{ item.title }}</v-list-item-title>
            </v-list-item-content>
          </v-list-item>
        </v-list>
      </v-menu>
      <v-app-bar-nav-icon class="hidden-md-and-up mr-2" @click.stop="drawer = !drawer" :style="{color: campaign.theme.primaryTextColor}"><v-icon v-if="!drawer">menu</v-icon><v-icon v-if="drawer">close</v-icon></v-app-bar-nav-icon>
    </v-app-bar>
    <v-content :style="{'background-color': campaign.theme.backgroundColor}">
      <router-view name="primary"></router-view>
    </v-content>
    <v-footer absolute app class="pa-0" height="auto" :style="{'background-color': campaign.theme.drawer.backgroundColor, 'color': campaign.theme.drawer.textColor}">
      <v-card
        class="flex"
        text
        tile
      >
        <v-card-title :style="{'background-color': campaign.theme.secondaryColor, 'color': campaign.theme.secondaryTextColor}" class="pa-2" v-if="Object.keys(campaign.footer.links).length > 0">
          <strong class="subtitle-1" v-html="campaign.footer.text"></strong>
          <v-spacer></v-spacer>
          <v-btn
            v-for="item in campaign.footer.links"
            :key="item.icon"
            class="mr-2"
            :href="item.href"
            target="_blank"
            :style="{'color': campaign.theme.secondaryTextColor}"
            icon
          >
            <v-icon size="20px">{{ item.icon }}</v-icon>
          </v-btn>
        </v-card-title>
        <div class="body-2 text-xs-center py-2" :style="{'background-color': campaign.theme.primaryColor, 'color': campaign.theme.primaryTextColor}">
          &copy;{{ new Date().getFullYear() }} — {{ campaign.business.name }} <span class="mx-2">&bull;</span> <router-link :to="{name: 'legal'}" :style="{'color': campaign.theme.primaryTextColor}">{{ $t('terms_and_conditions') }}</router-link>
          <span v-if="Object.keys(campaign.externalUrls).length > 0">
            <span class="mx-2">&bull;</span> 
            <span v-for="(item, index) in campaign.externalUrls" :key="index">
              <a :href="item.href" :style="{'color': campaign.theme.primaryTextColor}">{{ item.text }}</a>
              <span class="mx-2" v-if="index < Object.keys(campaign.externalUrls).length - 1">&bull;</span>
            </span>
          </span>
        </div>
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
      {{ $t('legal_agreement_confirmation') }}
      <v-btn
        color="white"
        text
        :to="{ name: 'legal' }"
      >
      {{ $t('terms') }}
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
import { getAvailableLanguages, loadLanguageAsync } from '../../plugins/i18n'

export default {
  name: 'app',
  data () {
    return {
      drawer: false,
      languages: null
    }
  },
  mounted() {
    this.$root.$confirm = this.$refs.confirm.open
    this.$root.$snackbar = this.$refs.snackbar.show

    let appBarContent = document.querySelector('.v-app-bar .v-toolbar__content')
    appBarContent.classList.add('container');

    /* Log out user when JWT key changed or session expired */
    if (this.$auth.check() && typeof this.$auth.user().language === 'undefined') { 
      this.$auth.logout()
    }

    /* Get available translations */
    getAvailableLanguages().then(result => this.languages = result)

    /* Set language */
    let language = this.$route.query.l || null
    if (language !== null) {
      loadLanguageAsync(language)
    }

    // Remove loader
    document.getElementById('app-loader').remove();
  },
  methods: {
    changeLang(language) {
      loadLanguageAsync(language)
      this.$store.dispatch('setLanguage', language)
    }
  },
  computed: {
    showCookieConsent () {
      return this.$store.state.app.showCookieConsent
    },
    topNav() {
        return [/*
          {
            name: this.$t('home'),
            icon: 'home',
            route_name: 'home',
            to: {name: 'home'},
            sideNavOnly: true
          },*/
          {
            name: this.$t('earn_points'),
            icon: 'card_giftcard',
            route_name: 'earn',
            to: {name: 'earn'}
          },
          {
            name: this.$t('redeem_points'),
            icon: 'fas fa-gift',
            route_name: 'rewards',
            to: {name: 'rewards'}
          },
          {
            name: this.$t('contact_us'),
            icon: 'location_on',
            route_name: 'contact',
            to: {name: 'contact'}
          },
          {
            name: this.$t('my_points'),
            icon: 'toll',
            route_name: 'points',
            to: {name: 'points'}
          }
        ]
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
          { divider: true },
          {
            name: this.$t('login'),
            icon: 'exit_to_app',
            route_name: 'login',
            to: {name: 'login'}
          }
        )
      }

      nav.unshift(
        {
          name: this.$t('home'),
          icon: 'home',
          route_name: 'home',
          to: {name: 'home'}
        }
      )

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
.v-overlay--absolute {
  z-index: 2 !important;
}
.v-system-bar {
  white-space: nowrap;
  padding-left: 16px;
}
.v-system-bar a {
  text-decoration: none;
}
.termsConsent .v-snack__content {
  height: auto !important;
}
</style>