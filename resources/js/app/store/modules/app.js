import Cookies from 'js-cookie'

const app = {
  state: {
    global: window.globalApp,
    loading: false,
    language: Cookies.get('language') || 'en',
  },
  mutations: {
    SET_LANGUAGE: (state, language) => {
      state.language = language
      Cookies.set('language', language)
    },
    SET_LOADING: (state, loading) => {
      state.loading = loading
    }
  },
  actions: {
    setLanguage({ commit }, language) {
      commit('SET_LANGUAGE', language)
    },
    setLoading({ commit }, loading) {
      commit('SET_LOADING', loading)
    }
  }
}

export default app