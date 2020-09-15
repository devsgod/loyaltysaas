import Cookies from 'js-cookie'

const app = {
  state: {
    account: window.initAccount,
    website: window.initWebsite,
    language: Cookies.get('language') || 'en',
    showCookieConsent: (Cookies.get('showCookieConsent') === 'false') ? false : true || true,
  },
  mutations: {
    SET_LANGUAGE: (state, language) => {
      state.language = language
      Cookies.set('language', language)
    },
    SET_COOKIE_CONSENT: (state, showCookieConsent) => {
      state.showCookieConsent = showCookieConsent
      Cookies.set('showCookieConsent', showCookieConsent, { expires: 360, path: '/' })
    },
    UPDATE_ACCOUNT: (state, account) => {
      state.account = account
    }
  },
  actions: {
    setLanguage({ commit }, language) {
      commit('SET_LANGUAGE', language)
    },
    setCookieConsent({ commit }, showCookieConsent) {
      commit('SET_COOKIE_CONSENT', showCookieConsent)
    }
  }
}

export default app