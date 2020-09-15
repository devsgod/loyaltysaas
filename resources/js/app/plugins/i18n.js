import Vue from 'vue'
import VueI18n from 'vue-i18n'
import axios from 'axios'

Vue.use(VueI18n)

export const i18n = new VueI18n({
  locale: 'en',
  fallbackLocale: 'en'
})

const loadedLanguages = []

function setI18nLanguage (lang) {
  i18n.locale = lang
  axios.defaults.headers.common['Accept-Language'] = lang
  document.querySelector('html').setAttribute('lang', lang)
  return lang
}

export function loadLanguageAsync (lang) {
  if (loadedLanguages.includes(lang)) {
    if (i18n.locale !== lang) setI18nLanguage(lang)
    return Promise.resolve()
  }

  return axios({ url: `lang/app/${lang}.json`, baseURL: ''}).then(response => {
    let msgs = new Array
    msgs[lang] = response.data

    loadedLanguages.push(lang)
    i18n.setLocaleMessage(lang, msgs[lang])
    setI18nLanguage(lang)
  })
}

loadLanguageAsync('en')

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