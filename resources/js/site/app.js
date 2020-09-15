/**
 * Bootstrap JavaScript dependencies that are available in the global scope.
 */

require('./bootstrap')

// https://github.com/asika32764/vue2-animate
require('vue2-animate/dist/vue2-animate.min.css')

/**
 * Import libraries that are used by the application.
 */
import vuetify from './plugins/vuetify'
import Cookies from 'js-cookie'
import VueAxios from 'vue-axios'
import VueRouter from 'vue-router'
import Vuex from 'vuex'
import VueI18n from 'vue-i18n'
import VeeValidate from 'vee-validate'
import VueGallery from 'vue-gallery'

// Store
import store from './store'

// Localization
import enLang from './lang/en.js'

/**
 * Enable Vue libraries.
 */
Vue.use(Vuex)
Vue.use(VueI18n)
Vue.use(VeeValidate)
Vue.use(VueAxios, axios)
Vue.component('vue-gallery', VueGallery)

// Set Vue router
import router from './routes.js'

Vue.router = router
Vue.use(VueRouter)

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