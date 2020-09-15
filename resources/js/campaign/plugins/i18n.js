import Vue from 'vue'
import VueI18n from 'vue-i18n'
import axios from 'axios'
import { Validator } from 'vee-validate'
import store from '../store'

Vue.use(VueI18n)

const fallbackLocale = 'en'
const loadedLanguages = []

export const i18n = new VueI18n({
  silentTranslationWarn: true,
  locale: store.state.app.language,
  fallbackLocale: fallbackLocale
})

export function getAvailableLanguages () {
  return axios({ url: 'i18n/campaign_translations', baseURL: '/api'})
    .then(response => {
      let languages = response.data
      return languages
    })
    .catch (function (error) {
      console.log('getAvailableLanguages error' + error); 
    })
}

function setI18nLanguage (lang) {
  i18n.locale = lang
  i18n.fallbackLocale = fallbackLocale
  axios.defaults.headers.common['Accept-Language'] = lang
  document.querySelector('html').setAttribute('lang', lang)

  const VeeValidate = import('vee-validate/dist/locale/' + lang)
    .then(response => {
      Validator.localize(lang, response)
    })
    .catch (function (error) {
      console.log('setI18nLanguage VeeValidate' + error); 
    })

  return lang
}

export function loadLanguageAsync (lang, onlyLoadTranslation = false) {
  if (loadedLanguages.includes(lang)) {
    if (i18n.locale !== lang) setI18nLanguage(lang)
    return Promise.resolve()
  }

  return axios({ url: `i18n/campaign/${lang}`, baseURL: '/api'})
    .then(response => {
      let msgs = new Array
      msgs[lang] = response.data

      loadedLanguages.push(lang)
      i18n.setLocaleMessage(lang, msgs[lang])

      if (! onlyLoadTranslation) {
        setI18nLanguage(lang)
      }
    })
}

/* Always load fallback lanuage */
loadLanguageAsync(fallbackLocale, true)

/* Load and set user selected language */
loadLanguageAsync(store.state.app.language)

// Change language
/*
import { loadLanguageAsync } from '../../../../plugins/i18n'
loadLanguageAsync('en')
*/

// router.js
/*
router.beforeEach((to, from, next) => {
  const lang = to.params.lang
  loadLanguageAsync(lang).then(() => next())
})
*/