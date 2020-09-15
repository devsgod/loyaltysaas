<template>
  <div class="pb-5">
    <v-container grid-list-lg fluid class="pa-0">
      <v-layout>
        <v-flex class="py-0" xs12 :style="{'background-color': campaign.theme.secondaryColor, 'color': campaign.theme.secondaryTextColor}">
          <v-container class="py-0">
            <v-layout>
              <v-flex xs12 md6>
                <v-container class="pa-0">
                  <v-layout :justify-center="$vuetify.breakpoint.smAndDown">
                    <v-flex xs10 md10>
                      <div class="my-5 py-3">
                        <h1 class="display-1 mb-3" v-html="campaign.home.headerTitle"></h1>
                        <div v-html="campaign.home.headerContent"></div>
                      </div>
                    </v-flex>
                  </v-layout>
                </v-container>
              </v-flex>
              <v-flex xs6 md v-if="$vuetify.breakpoint.mdAndUp">
                <v-img
                  v-if="campaign.home.headerImg"
                  :src="campaign.home.headerImg"
                  :height="campaign.home.headerHeight"
                >
                  <svg version="1.1" x="0px" y="0px" viewBox="0 0 35 216" style="enable-background:new 0 0 35 216;height:100%;float:right" xml:space="preserve">
                    <polygon :fill="campaign.theme.secondaryColor" points="35,0 0,216 35,216 "/>
                  </svg>
                  <svg version="1.1" x="0px" y="0px" viewBox="0 0 35 216" style="enable-background:new 0 0 35 216;height:100%;float:left" xml:space="preserve">
                    <polygon :fill="campaign.theme.secondaryColor" points="0,216 35,0 0,0 "/>
                  </svg>
                  <template v-slot:placeholder>
                    <v-layout
                      fill-height
                      align-center
                      justify-center
                      ma-0
                    >
                      <v-progress-circular indeterminate :style="{'color': campaign.theme.secondaryTextColor}"></v-progress-circular>
                    </v-layout>
                  </template>
                </v-img>
              </v-flex>
            </v-layout>
          </v-container>
        </v-flex>
      </v-layout>
    </v-container>
    <v-container grid-list-lg>
      <v-layout row wrap>
        <v-flex xs12>
          <div class="display-1 mb-2 mt-5" v-html="campaign.home.rewardsTitle" :style="{'color': campaign.theme.textColor}"></div>
        </v-flex>
      </v-layout>
    </v-container>
    <v-container grid-list-lg>
      <v-layout row wrap>
        <v-flex xs12 sm6 md3 v-for="(item, index) in campaign.home.rewards" :key="'reward_' + index">
          <v-hover>
            <template v-slot:default="{ hover }">
              <v-card @click="$router.push({name: 'rewards'})" class="card-link" style="position: relative;">
                <v-img
                  v-if="item.img"
                  :src="item.img"
                  :aspect-ratio="campaign.home.rewardsImgRatio"
                >
                  <template v-slot:placeholder>
                    <v-layout
                      fill-height
                      align-center
                      justify-center
                      ma-0
                    >
                      <v-progress-circular indeterminate :style="{'color': campaign.theme.textColor}"></v-progress-circular>
                    </v-layout>
                  </template>
                </v-img>
                <v-card-title primary-title>
                  <div class="text-truncate">
                    <h3 class="subtitle-1 grey--text text--darken-3 mb-2 text-truncate" v-html="item.title"></h3>
                    <div class="grey--text text--darken-1 caption mt-2 mb-2 text-truncate" v-if="item.description" v-html="item.description"></div>
                  </div>
                </v-card-title>
                 <v-fade-transition>
                  <v-overlay
                    v-if="hover"
                    absolute
                    color="#000"
                  >
                  </v-overlay>
                </v-fade-transition>
              </v-card>
            </template>
          </v-hover>
        </v-flex>
      </v-layout>
    </v-container>
    <v-container grid-list-lg class="elevation-1 white mt-5 pa-5" v-if="Object.keys(campaign.home.blocks).length > 0 || campaign.home.blocksTitle">
      <v-layout row wrap v-if="campaign.home.blocksTitle">
        <v-flex xs12>
          <div class="display-1 mt-0 mb-4" v-html="campaign.home.blocksTitle"></div>
        </v-flex>
      </v-layout>
      <v-layout row wrap v-if="Object.keys(campaign.home.blocks).length > 0">
        <v-flex xs12 sm4 class="pa-2" v-for="(item, index) in campaign.home.blocks" :key="'content_' + index">
          <v-img
            v-if="item.img"
            :src="item.img"
            :aspect-ratio="campaign.home.blocksImgRatio"
            class="elevation-1"
          >
            <template v-slot:placeholder>
              <v-layout
                fill-height
                align-center
                justify-center
                ma-0
              >
                <v-progress-circular indeterminate :style="{'color': campaign.theme.textColor}"></v-progress-circular>
              </v-layout>
            </template>
          </v-img>
          <v-card-title primary-title class="pa-0 my-3">
            <div>
              <h3 class="title mb-2 grey--text text--darken-3" v-html="item.title"></h3>
              <div class="grey--text text--darken-1 body-1" v-html="item.text"></div>
            </div>
          </v-card-title>
        </v-flex>
      </v-layout>
    </v-container>
  </div>
</template>
<script>
  export default {
    name: 'app',
    computed: {
      campaign () {
        return this.$store.state.app.campaign
      }
    }
  }
</script>