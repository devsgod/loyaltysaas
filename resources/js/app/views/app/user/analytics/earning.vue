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

    <v-card flat color="transparent" v-if="!loading && stats.total.onboardingStep < 6">
      <v-toolbar flat color="transparent">
        <v-toolbar-title>Earning</v-toolbar-title>
      </v-toolbar>
      <div  class="text-xs-center">
        <div>
          <v-icon size="72" :color="$store.getters.app.color_name">toll</v-icon>
        </div>
        <h1 class="title my-4">There are no analytics available yet</h1>
        <div class="mx-5 pb-4">
          <p class="subheading">Earning analytics will show here when customers redeem points.</p>
        </div>
      </div>
    </v-card>

    <v-card flat color="transparent" v-if="!loading && stats.total.onboardingStep >= 6">

      <v-overlay :value="overlay">
        <v-progress-circular indeterminate size="64"></v-progress-circular>
      </v-overlay>

      <v-toolbar flat color="transparent">
        <v-toolbar-title>Earning</v-toolbar-title>

        <v-spacer></v-spacer>

        <date-range-picker
          v-model="range"
          display-format="DD-MM-YYYY"
          no-title
          :input-props="{ solo: false, 'prepend-inner-icon': 'date_range', style: { width: '260px' }}"
          :menu-props="{ offsetY: true, closeOnContentClick: false }"
          :presets="dateRangePresets"
          v-on:menu-closed="getData"
        ></date-range-picker>

      </v-toolbar>

      <v-container fluid grid-list-xl>
        <v-layout row>
          <v-flex xl12>
            <v-card color="white">
              <v-container fluid grid-list-xs class="py-0 px-3">
                <v-layout row wrap>
                  <v-flex xs12 md6 xl3>

                    <v-autocomplete
                      @change="changeFilter"
                      v-model="selected.campaigns"
                      :items="filter.campaigns"
                      placeholder="All campaigns"
                      prepend-inner-icon="record_voice_over"
                      item-value="0" 
                      item-text="1"
                      hide-selected
                      flat
                      dense
                      chips
                      multiple
                      solo
                      clearable
                      hide-no-data
                      hide-details
                      deletable-chips
                    ></v-autocomplete>

                  </v-flex>
                  <v-flex xs12 md6 xl3>

                    <v-autocomplete
                      @change="changeFilter"
                      v-model="selected.businesses"
                      :items="filter.businesses"
                      placeholder="All businesses"
                      prepend-inner-icon="business"
                      item-value="0" 
                      item-text="1"
                      hide-selected
                      flat
                      dense
                      chips
                      multiple
                      solo
                      clearable
                      hide-no-data
                      hide-details
                      deletable-chips
                    ></v-autocomplete>

                  </v-flex>
                  <v-flex xs12 md6 xl3>

                    <v-autocomplete
                      @change="changeFilter"
                      v-model="selected.segments"
                      :items="filter.segments"
                      placeholder="All segments"
                      prepend-inner-icon="category"
                      item-value="0" 
                      item-text="1"
                      hide-selected
                      flat
                      dense
                      chips
                      multiple
                      solo
                      clearable
                      hide-no-data
                      hide-details
                      deletable-chips
                    ></v-autocomplete>

                  </v-flex>
                  <v-flex xs12 md6 xl3>

                    <v-autocomplete
                      @change="changeFilter"
                      v-model="selected.staff"
                      :items="filter.staff"
                      placeholder="All staff members"
                      prepend-inner-icon="supervised_user_circle"
                      item-value="0" 
                      item-text="1"
                      hide-selected
                      flat
                      dense
                      chips
                      multiple
                      solo
                      clearable
                      hide-no-data
                      hide-details
                      deletable-chips
                    ></v-autocomplete>

                  </v-flex>
                </v-layout>
              </v-container>

            </v-card>

            <v-card color="white" class="mt-6">
              <v-card-text v-if="this.range.start !== this.range.end">
                <GChart
                  type="AreaChart"
                  :data="chartData"
                  :options="chartOptions"
                  style="min-height:300px"
                />
              </v-card-text>

              <v-divider></v-divider>

              <v-data-table
                :headers="stats.tableHeaders"
                :items="tableData"
                :items-per-page="5"
                class="elevation-2 white"
              ></v-data-table>
            </v-card>

          </v-flex>
        </v-layout>
      </v-container>
  </v-card>
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
    },
    data () {
      return {
        range: {},
        dateRangePresets: [
          {
            label: 'Today',
            range: [
              moment().format('YYYY-MM-DD'),
              moment().format('YYYY-MM-DD')
            ]
          },
          {
            label: 'Yesterday',
            range: [
              moment().add(-1, 'days').format('YYYY-MM-DD'),
              moment().add(-1, 'days').format('YYYY-MM-DD')
            ]
          },
          {
            label: 'Last 7 Days',
            range: [
              moment().add(-7, 'days').format('YYYY-MM-DD'),
              moment().add(-1, 'days').format('YYYY-MM-DD')
            ]
          },
          {
            label: 'Last 30 Days',
            range: [
              moment().add(-30, 'days').format('YYYY-MM-DD'),
              moment().add(-1, 'days').format('YYYY-MM-DD')
            ]
          },
          {
            label: 'Last 90 Days',
            range: [
              moment().add(-90, 'days').format('YYYY-MM-DD'),
              moment().add(-1, 'days').format('YYYY-MM-DD')
            ]
          }
        ],
        locale: 'en',
        overlay: true,
        loading: true,
        stats: null,
        chartData: [],
        tableData: [],
        chartOptions: {
          width: '100%',
          height: 343,
          chartArea: {
            left: 80, top: 20, bottom: 30, width:'100%'
          },
          hAxis: {
            format: 'MMM d',
            gridlines: {color: '#ebebeb', count: 7}
          }
        },
        filter: {
          campaigns: [],
          businesses: [],
          segments: [],
          staff: []
        },
        selected: {
          campaigns: [],
          businesses: [],
          segments: [],
          staff: []
        }
      }
    },
    created () {
      this.getData()
    },
    methods: {
      getData () {
        this.overlay = true
        axios
          .post('/user/analytics/earning', { 
            locale: this.$i18n.locale,
            start: this.range.start || null,
            end: this.range.end || null,
            campaigns: this.selected.campaigns,
            businesses: this.selected.businesses,
            segments: this.selected.segments,
            staff: this.selected.staff 
          })
          .then(response => {
            let stats = response.data
            this.stats = stats

            // Filters
            this.filter.campaigns = _.toPairs(this.stats.campaigns)
            this.filter.businesses = _.toPairs(this.stats.businesses)
            this.filter.segments = _.toPairs(this.stats.segments)
            this.filter.staff = _.toPairs(this.stats.staff)

            // Parse date for charts
            let chartData = _.map(stats.chart, (o, i) => {
              if (i == 0) {
                return o
              } else {
                for (let index = 0; index < Object.keys(o).length; index++) {
                  if (index == 0) {
                    o[index] = moment(o[index]).toDate()
                  }
                }
                return o
              }
            });

            this.chartData = chartData

            // Parse date for table
            let tableData = _.map(stats.table, (o, i) => {
              for (let index = 0; index < Object.keys(o).length; index++) {
                let column = Object.keys(o)[index]
                if (column == 'created_at') {
                  o[column] = moment(o[column]).format('lll')
                }
              }
              return o
            });

            this.tableData = tableData

            this.range = {start: this.stats.start, end: this.stats.end};

            this.loading = false
            this.overlay = false
          })
      },
      formatNumber (number) {
        return new Intl.NumberFormat(this.locale.replace('_', '-')).format(number)
      },
      changeFilter () {
        this.getData()
      }
    },
    computed: {
      app() {
        return this.$store.getters.app
      }
    }
  }
</script>
<style>
</style>