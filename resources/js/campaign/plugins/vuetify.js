import '@mdi/font/css/materialdesignicons.css'
import Vue from 'vue'
import Vuetify from 'vuetify'

Vue.use(Vuetify, {
  icons: {
    iconfont: 'mdi',
    values: {
    	sort: 'arrow_drop_down'
    }
  }
})

export default new Vuetify()