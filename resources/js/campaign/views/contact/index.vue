<template>
  <div class="pb-5">
    <v-container grid-list-lg fluid class="pa-0">
      <v-layout row wrap>
        <v-flex class="py-0" xs12 :style="{'background-color': campaign.theme.secondaryColor, 'color': campaign.theme.secondaryTextColor}">
          <v-img
            :src="campaign.contact.headerImg || ''"
            :height="campaign.contact.headerHeight"
          >
            <v-overlay
              absolute
              :color="campaign.theme.secondaryColor"
              :opacity="campaign.contact.headerOpacity"
              z-index="1"
            >
            </v-overlay>
            <v-container fill-height>
              <v-layout row wrap align-center>
                <v-flex xs12 style="z-index: 2">
                  <h1 class="display-1 mb-2" v-html="campaign.contact.headerTitle"></h1>
                  <div v-html="campaign.contact.headerContent"></div>
                </v-flex>
              </v-layout>
            </v-container>
            <template v-slot:placeholder>
              <v-layout
                fill-height
                align-center
                justify-center
                ma-0
                v-if="campaign.contact.headerImg"
              >
                <v-progress-circular indeterminate :style="{'color': campaign.theme.secondaryTextColor}"></v-progress-circular>
              </v-layout>
            </template>
          </v-img>
        </v-flex>
        <v-flex>
          <v-container grid-list-lg>
            <v-layout row wrap>
              <v-flex xs12>
                <v-list two-line class="mt-3 pa-0 elevation-1 white">
                  <div v-for="(item, index) in campaign.contact.methods">
                    <v-list-item :href="item.link" :target="item.target">
                      <v-list-item-avatar>
                        <v-icon class="grey darken-3 white--text">{{ item.icon }}</v-icon>
                      </v-list-item-avatar>
                      <v-list-item-content>
                        <v-list-item-title class="grey--text text--darken-3">{{ item.title }}</v-list-item-title>
                        <v-list-item-subtitle v-if="item.translate !== false">{{ $t(item.subtitle) }}</v-list-item-subtitle>
                        <v-list-item-subtitle v-else>{{ item.subtitle }}</v-list-item-subtitle>
                      </v-list-item-content>
                      <v-list-item-action>
                        <v-icon color="grey darken-1">launch</v-icon>
                      </v-list-item-action>
                    </v-list-item>
                    <v-divider inset class="grey lighten-2" v-if="index < campaign.contact.methods.length - 1"></v-divider>
                  </div>
                </v-list>
                <v-container fluid class="pa-0 elevation-1 white mt-5 mb-1" v-if="campaign.contact.features && campaign.contact.features.length > 0">
                  <v-layout row wrap>
                    <v-flex
                      xs12 lg6
                      v-for="(item, index) in campaign.contact.features"
                      :key="'card_about_features_' + index"
                    >
                      <v-list-item>
                        <v-list-item-action>
                          <v-icon class="grey--text text--darken-3">{{ item.icon }}</v-icon>
                        </v-list-item-action>
                        <v-list-item-content>
                          <v-list-item-title v-html="item.title + ':'" :title="item.title" class="grey--text text--darken-3"></v-list-item-title>
                        </v-list-item-content>
                        <v-list-item-action v-html="item.value" class="grey--text text--darken-3"></v-list-item-action>
                      </v-list-item>
                    </v-flex>
                  </v-layout>
                </v-container>
              </v-flex>
            </v-layout>
          </v-container>
        </v-flex>
      </v-layout>
    </v-container>
  </div>
</template>
<script>
  export default {
    name: 'app',
    methods: {
    },
    data () {
      return {
      }
    },
    computed: {
      campaign () {
        return this.$store.state.app.campaign
      }
    },
  };
</script>