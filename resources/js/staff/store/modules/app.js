import Cookies from 'js-cookie'

const app = {
  state: {
    campaign: window.initCampaign,
    language: Cookies.get('language') || 'en',
  },
  mutations: {
    SET_LANGUAGE: (state, language) => {
      state.language = language
      Cookies.set('language', language)
    },
    UPDATE_CAMPAIGN: (state, campaign) => {
      state.campaign = campaign
    }
  },
  actions: {
    setLanguage({ commit }, language) {
      commit('SET_LANGUAGE', language)
    },
    updateCampaign({ commit }, campaign) {
      commit('UPDATE_CAMPAIGN', campaign)
    }
  }
}

export default app