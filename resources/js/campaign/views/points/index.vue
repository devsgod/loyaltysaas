<template>
  <v-container fluid fill-height>
    <v-layout align-center justify-center row fill-height wrap>
      <v-flex xs11 sm8 md6 lg4 xl3>
        <v-card class="elevation-18 my-5">
          <v-tabs
            v-model="selectedTab"
            slider-color="grey darken-3"
            color="grey darken-3"
          >
            <v-tab :href="'#points'">
              {{ $t('balance') }}
            </v-tab>
            <v-tab :href="'#history'" v-if="points > 0">
              {{ $t('history') }}
            </v-tab>
          </v-tabs>
          <v-divider class="grey lighten-2"></v-divider>
          <v-tabs-items v-model="selectedTab">
            <v-tab-item :value="'points'">
              <v-card-text>
                <v-progress-linear
                  v-if="points === null"
                  indeterminate
                  color="primary"
                ></v-progress-linear>
                <div class="ma-2 display-1" v-if="points !== null" v-html="$t('n_points', {points: formatNumber(points)})"></div>
              </v-card-text>
            </v-tab-item>
            <v-tab-item :value="'history'">
              <v-card-text>
                <v-timeline
                  align-top
                  dense
                >
                  <v-timeline-item
                    v-for="(item, index) in history" 
                    :key="index"
                    :color="item.color"
                    :icon="item.icon"
                    fill-dot
                    :small="item.icon_size == 'small'"
                    :large="item.icon_size == 'large'"
                  >
                    <v-layout>
                      <v-flex>
                        <strong>{{ (item.points > 0) ? '+' : '' }}{{ $t('n_points', {points: formatNumber(item.points)}) }}</strong>,
                        <v-tooltip top>
                          <template v-slot:activator="{ on }">
                            <span v-on="on"><strong>{{ getDateFrom(item.created_at) }}</strong></span>
                          </template>
                          <span>{{ getDate(item.created_at) }}</span>
                        </v-tooltip>
                        <div class="caption" v-if="item.reward_title !== null">{{ item.reward_title }}</div>
                        <div class="caption">{{ item.description }}</div>
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
    name: 'app',
    methods: {
      getDate(date) {
        return moment(date).format("lll")
      },
      getDateFrom(date) {
        return moment(date).from()
      },
      formatNumber (number) {
        return new Intl.NumberFormat(this.$i18n.locale).format(number)
      }
    },
    data () {
      return {
        locale: 'en',
        selectedTab: null,
        points: null,
        history: []
      }
    },
    mounted () {
      moment.locale(this.$i18n.locale)

      axios
        .get('/campaign/points', { params: { locale: this.$i18n.locale, uuid: this.$store.state.app.campaign.uuid }})
        .then(response => {
          this.points = response.data
        })

      axios
        .get('/campaign/history', { params: { locale: this.$i18n.locale, uuid: this.$store.state.app.campaign.uuid }})
        .then(response => {
          this.history = response.data
        })
    },
    computed: {
      campaign () {
        return this.$store.state.app.campaign
      }
    },
  };
</script>