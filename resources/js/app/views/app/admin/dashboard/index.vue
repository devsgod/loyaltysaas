<template>
  <div style="height: 100%">
    <v-container fluid v-if="loading" style="height: 100%">
      <v-layout align-center justify-center row fill-height class="text-xs-center" style="height: 100%">
        <v-progress-circular
          :size="50"
          :color="app.color_name"
          indeterminate
          class="ma-5"
        ></v-progress-circular>
      </v-layout>
    </v-container>

    <v-container fluid grid-list-xl v-if="!loading">
      <v-layout row>
        <v-flex lg12 xl10>
          <v-layout row wrap>
            <v-flex xs12 md12>
              <h1 class="main-title mb-3">{{ app.app_name }} homepage</h1>
              <v-card>
                <div class="tabs-s1">
                  <v-tabs
                    v-model="statTabs"
                    color="grey"
                    light
                    icons-and-text
                    :slider-color="app.color_name"
                  >
                    <v-tab :href="'#users'">
                      <div class="tabs__item__title">Users ({{ formatNumber(stats.total.users) }})</div>
                      <div class="tabs__item__count">{{ formatNumber(stats.users.signupsCurrentPeriodTotal) }} <v-icon size="16" class="tab-icon">person</v-icon></div>
                      <div v-if="stats.users.signupsChange == 0" class="tabs__item__conversion-rate">This period</div>
                      <div v-if="stats.users.signupsChange != 0" class="tabs__item__conversion-rate" :class="{'red--text': stats.users.signupsChange < 0, 'green--text': stats.users.signupsChange > 0}"><v-icon size="14" v-if="stats.users.signupsChange < 0">arrow_downward</v-icon><v-icon size="14" v-if="stats.users.signupsChange > 0">arrow_upward</v-icon> {{ formatNumber(stats.users.signupsChange) }}</div>
                      <div v-if="stats.users.signupsChange != 0" class="tabs__item__sub-title">vs. past 7 days</div>
                    </v-tab>
                  </v-tabs>

                  <v-tabs-items v-model="statTabs">
                    <v-tab-item :value="'users'">
                      <v-card flat>
                        <v-card-text :style="{'height': (chartOptions.height + 32) + 'px'}">
                          <GChart
                            type="AreaChart"
                            :data="userChartData"
                            :options="chartOptions"
                          />
                        </v-card-text>
                      </v-card>
                    </v-tab-item>
                  </v-tabs-items>
                </div>
                <v-card-actions class="pt-0">
                  <v-spacer></v-spacer>
                    <v-btn text class="white" :to="{name: 'admin.users'}" v-if="statTabs=='users'">Users <v-icon dark>keyboard_arrow_right</v-icon></v-btn>
                  </v-card-actions>
              </v-card>
            </v-flex>
          </v-layout>
        </v-flex>
      </v-layout>
    </v-container>
  </div>
</template>
<script>
  export default {
    $_veeValidate: {
      validator: 'new'
    },
    mounted () {
      let locale = Intl.DateTimeFormat().resolvedOptions().locale || 'en'
      locale = (this.$auth.check()) ? this.$auth.user().locale : locale
      this.locale = locale

      moment.locale(this.locale.substr(0,2))

      this.onResize()
      window.addEventListener('resize', this.onResize, { passive: true })
    },
    data () {
      return {
        locale: 'en',
        overlay: true,
        loading: true,
        stats: null,
        dnsErrorMessage: null,
        statTabs: 'users',
        userChartData: [],
        chartOptions: {
          width: '100%',
          height: 343,
          chartArea: {
            left: 50, top: 20, bottom: 30, width:'100%'
          },
          hAxis: {
            format: 'MMM d',
            gridlines: {color: '#ebebeb', count: 7}
          }
        },
        chartPeriods: ['Last 7 days','Last 28 days', 'Last 90 days']
      }
    },
    created () {
      axios
        .get('/admin/stats', { params: { locale: this.$i18n.locale }})
        .then(response => {
          let stats = response.data
          this.stats = stats

          let userChartData = _.map(stats.users.signupsCurrentPeriod, (count, date) => {
            return [moment(date).toDate(), count]
          });

          userChartData.unshift(["Date", "Registrations"])
          this.userChartData = userChartData

          this.overlay = false
          this.loading = false
        })
    },
    methods: {
      formatNumber (number) {
        return new Intl.NumberFormat(this.locale.replace('_', '-')).format(number)
      },
      onResize () {
        switch (this.$vuetify.breakpoint.name) {
            case 'xs': this.chartOptions.height = 320; break;
            case 'sm': this.chartOptions.height = 320; break;
            default: this.chartOptions.height = 439; break;
        }
      }
    },
    computed: {
      app() {
        return this.$store.getters.app
      },
       _(){
           return _;
      }
    }
  }
</script>
<style>
</style>