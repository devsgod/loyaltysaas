<template>
  <v-container fluid fill-height class="px-0 pt-0 pb-5">
    <v-layout align-center justify-center row fill-height wrap>

      <v-flex xs10 sm8 md7 lg5 xl4 class="mt-5">

        <v-card class="elevation-18">
          <v-tabs
            v-model="selectedTab"
            slider-color="grey darken-3"
            color="grey darken-3"
            >
            <v-tab :href="'#history'">
              Recent activity
            </v-tab>
          </v-tabs>

          <v-divider class="grey lighten-2"></v-divider>

          <v-tabs-items v-model="selectedTab">
            <v-tab-item :value="'history'">
              <v-card-text>

                <v-progress-linear
                  v-if="loading"
                  indeterminate
                  color="primary"
                ></v-progress-linear>

                <p class="title ma-2" v-if="loading === false && Object.keys(history).length == 0">There is no activity for this campaign yet.</p>

                <v-timeline
                  v-if="Object.keys(history).length > 0"
                  align-top
                  dense
                >
                  <v-timeline-item
                    v-for="(item, index) in history" 
                    :key="index"
                    :color="item.color"
                    :icon="item.icon"
                    fill-dot
                    large
                  >
                  <template v-slot:icon>
                    <v-avatar>
                      <img :src="item.customer_details.avatar">
                    </v-avatar>
                  </template>

                  <v-layout>
                    <v-flex>

                      <strong>{{ (item.points > 0) ? '+' : '' }}{{ new Intl.NumberFormat(locale.replace('_', '-')).format(item.points) }} points</strong>,

                      <v-tooltip top>
                        <template v-slot:activator="{ on }">
                          <span v-on="on"><strong>{{ getDateFrom(item.created_at) }}</strong></span>
                        </template>
                        <span>{{ getDate(item.created_at) }}</span>
                      </v-tooltip>

                      <div class="caption">{{ item.customer_details.name }} ({{ item.customer_details.number }})</div>
                      <div class="caption" v-if="item.reward_title !== null">{{ item.reward_title }}</div>
                      <div class="caption">{{ item.description }}</div>

                      <v-chip 
                        v-for="(segment, index) in item.segment_details" 
                        :key="index"
                        small
                        class="mr-2 mt-2"
                      >{{ segment.name }}</v-chip>
                    </v-flex>
                  </v-layout>

                  </v-timeline-item>
                </v-timeline>

              </v-card-text>
            </v-tab-item>
          </v-tabs-items>

        </v-card>

      </v-flex>

    </v-layout>
  </v-container>
</template>
<script>
  export default {
    data () {
      return {
        locale: 'en',
        loading: true,
        selectedTab: null,
        history: []
      }
    },
    methods: {
      getDate(date) {
        return moment(date).format("lll")
      },
      getDateFrom(date) {
        return moment(date).from()
      } 
    },
    mounted () {
      this.locale = this.$auth.user().locale
      moment.locale(this.locale)

      axios
        .get('/staff/history', { params: { locale: this.$i18n.locale, uuid: this.campaign.uuid }})
        .then(response => {
          this.history = response.data
          this.loading = false
        })
    },
    computed: {
      campaign () {
        return this.$store.state.app.campaign
      }
    }
  }
</script>
<style scoped>
</style>