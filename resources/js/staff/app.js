/**
 * Bootstrap JavaScript dependencies that are available in the global scope.
 */

require('./bootstrap')

/**
 * Import libraries that are used by the application.
 */
import vuetify from './plugins/vuetify'
import Cookies from 'js-cookie'
import VueAuth from '@websanova/vue-auth'
import VueAxios from 'vue-axios'
import VueRouter from 'vue-router'
import Vuex from 'vuex'
import VueI18n from 'vue-i18n'
import VeeValidate from 'vee-validate'
import VueQRCodeComponent from 'vue-qrcode-component'
import VueGallery from 'vue-gallery'
import VueTheMask from 'vue-the-mask'

// Store
import store from './store'

// Localization
import enLang from './lang/en.js'

/**
 * Enable Vue libraries.
 */
Vue.use(Vuex)
Vue.use(VueI18n)
Vue.component('qr-code', VueQRCodeComponent)
Vue.use(VeeValidate)
Vue.component('vue-gallery', VueGallery)
Vue.use(VueTheMask)

// Set Vue router
import router from './routes.js'

Vue.router = router
Vue.use(VueRouter)

// Set Vue authentication
import auth from './api/auth.js'

Vue.use(VueAxios, axios)
Vue.use(VueAuth, auth)

/* Layout */
import App from './views/layouts/AppLayout.vue'

/**
 * Set translations for the app.
 * First Element UI translations, then custom translations.
 */
const i18n = new VueI18n({
  locale: navigator.language,
  fallbackLocale: 'en',
  messages: { 
    en: enLang 
  },
  silentTranslationWarn: true
})

// Custom components
import Confirm from './views/components/ui/Confirm.vue';
Vue.component('confirm', Confirm)

import Snackbar from './views/components/ui/Snackbar.vue';
Vue.component('snackbar', Snackbar)

// Changing the language
// https://medium.com/hypefactors/add-i18n-and-manage-translations-of-a-vue-js-powered-website-73b4511ca69c
// this.$i18n.locale = 'fr'

/**
 * Initialize the app.
 */
const app = new Vue({
  vuetify,
  i18n,
  router,
  store,
  render: h => h(App)
}).$mount('#app')