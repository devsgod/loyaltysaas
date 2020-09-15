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

    <v-container fluid grid-list-xl v-if="!loading && stats.total.onboardingStep < 6">
      <v-layout row>
        <v-flex lg12 xl10>
          <v-layout row wrap>
            <v-flex xs12 lg7>
              <h1 class="main-title mb-3">Getting started with your first loyalty campaign</h1>

              <v-stepper v-model="stats.total.onboardingStep" vertical>
                <v-stepper-step :complete="stats.total.onboardingStep > 1" step="1" :color="app.color_name">Create a business</v-stepper-step>

                <v-stepper-content step="1">
                  <v-icon :color="app.color_name" size="64" class="mb-2">business</v-icon>
                  <p class="subheading">A business is an entity to which you can link staff members and loyalty campaigns. Let's start by setting up a business.</p>
                  <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn :color="app.color_name" text :to="{name: 'user.businesses'}">Create a business <v-icon>keyboard_arrow_right</v-icon></v-btn>
                  </v-card-actions>
                </v-stepper-content>

                <v-stepper-step :complete="stats.total.onboardingStep > 2" step="2" :color="app.color_name">Create a staff member</v-stepper-step>

                <v-stepper-content step="2">
                  <v-icon :color="app.color_name" size="64" class="mb-2">supervised_user_circle</v-icon>
                  <p class="subheading">A staff member can give customers loyalty points and redeem rewards. Staff members belong to one or more businesses.</p>
                  <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn :color="app.color_name" text :to="{name: 'user.staff'}">Create a staff member <v-icon>keyboard_arrow_right</v-icon></v-btn>
                  </v-card-actions>
                </v-stepper-content>

                <v-stepper-step :complete="stats.total.onboardingStep > 3" step="3" :color="app.color_name">Create a reward</v-stepper-step>

                <v-stepper-content step="3">
                  <v-icon :color="app.color_name" size="64" class="mb-2">fas fa-gift</v-icon>
                  <p class="subheading">Customers can exchange their points for rewards. A campaign can have one or more rewards.</p>
                  <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn :color="app.color_name" text :to="{name: 'user.rewards'}">Create a reward <v-icon>keyboard_arrow_right</v-icon></v-btn>
                  </v-card-actions>
                </v-stepper-content>

                <v-stepper-step :complete="stats.total.onboardingStep > 4" step="4" :color="app.color_name">Create a loyalty campaign</v-stepper-step>

                <v-stepper-content step="4">
                  <v-icon :color="app.color_name" size="64" class="mb-2">record_voice_over</v-icon>
                  <p class="subheading">A campaign is a website where customers can sign up, earn points and redeem rewards.</p>
                  <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn :color="app.color_name" text :to="{name: 'user.campaigns'}">Create a campaign <v-icon>keyboard_arrow_right</v-icon></v-btn>
                  </v-card-actions>
                </v-stepper-content>

                <v-stepper-step step="5" :color="app.color_name">Onboard your first customer</v-stepper-step>

                <v-stepper-content step="5">
                  <v-icon :color="app.color_name" size="64" class="mb-2">contacts</v-icon>
                  <p class="subheading">Promote your campaign and grow your customer base!</p>
                  <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn :color="app.color_name" text :to="{name: 'user.customers'}">View customers <v-icon>keyboard_arrow_right</v-icon></v-btn>
                  </v-card-actions>
                </v-stepper-content>
              </v-stepper>

            </v-flex>

          </v-layout>
        </v-flex>
      </v-layout>
    </v-container>

    <v-container fluid grid-list-xl v-if="!loading && stats.total.onboardingStep >= 6">
      <v-layout row>
        <v-flex lg12 xl10>
          <v-layout row wrap>
            <v-flex xs12 md7>
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
                    <v-tab :href="'#customers'">
                      <div class="tabs__item__title">Customers</div>
                      <div class="tabs__item__count">{{ formatNumber(stats.customers.signupsCurrentPeriodTotal) }} <v-icon size="16" class="tab-icon">person</v-icon></div>
                      <div v-if="stats.customers.signupsChange != 0" class="tabs__item__conversion-rate" :class="{'red--text': stats.customers.signupsChange < 0, 'green--text': stats.customers.signupsChange > 0}"><v-icon size="14" v-if="stats.customers.signupsChange < 0">arrow_downward</v-icon><v-icon size="14" v-if="stats.customers.signupsChange > 0">arrow_upward</v-icon> {{ formatNumber(stats.customers.signupsChange) }}</div>
                      <div v-if="stats.customers.signupsChange != 0" class="tabs__item__sub-title">vs. past 7 days</div>
                    </v-tab>
                    <v-tab :href="'#earning'">
                      <div class="tabs__item__title">Earning</div>
                      <div class="tabs__item__count">{{ formatNumber(stats.earnings.earningsTotal) }} <v-icon size="16" class="tab-icon">toll</v-icon></div>
                      <div v-if="stats.earnings.earningsChange != 0" class="tabs__item__conversion-rate" :class="{'red--text': stats.earnings.earningsChange < 0, 'green--text': stats.earnings.earningsChange > 0}"><v-icon size="14" v-if="stats.earnings.earningsChange < 0">arrow_downward</v-icon><v-icon size="14" v-if="stats.earnings.earningsChange > 0">arrow_upward</v-icon> {{ formatNumber(stats.earnings.earningsChange) }}</div>
                      <div v-if="stats.earnings.earningsChange != 0" class="tabs__item__sub-title">vs. past 7 days</div>
                    </v-tab>
                    <v-tab :href="'#spending'">
                      <div class="tabs__item__title">Spending</div>
                      <div class="tabs__item__count">{{ formatNumber(stats.spendings.spendingsTotal) }} <v-icon size="16" class="tab-icon">toll</v-icon></div>
                      <div v-if="stats.spendings.spendingsChange != 0" class="tabs__item__conversion-rate" :class="{'red--text': stats.spendings.spendingsChange < 0, 'green--text': stats.spendings.spendingsChange > 0}"><v-icon size="14" v-if="stats.spendings.spendingsChange < 0">arrow_downward</v-icon><v-icon size="14">arrow_upward</v-icon> {{ formatNumber(stats.spendings.spendingsChange) }}</div>
                      <div v-if="stats.spendings.spendingsChange != 0" class="tabs__item__sub-title">vs. past 7 days</div>
                    </v-tab>
                  </v-tabs>

                  <v-tabs-items v-model="statTabs">
                    <v-tab-item :value="'customers'">
                      <v-card flat>
                        <v-card-text :style="{'height': (chartOptions.height + 32) + 'px'}">
                          <GChart
                            type="AreaChart"
                            :data="customerChartData"
                            :options="chartOptions"
                          />
                        </v-card-text>
                      </v-card>
                    </v-tab-item>
                    <v-tab-item :value="'earning'">
                      <v-card flat>
                        <v-card-text>
                          <GChart
                            type="AreaChart"
                            :data="earningChartData"
                            :options="chartOptions"
                          />
                        </v-card-text>
                      </v-card>
                    </v-tab-item>
                    <v-tab-item :value="'spending'">
                      <v-card flat>
                        <v-card-text>
                          <GChart
                            type="AreaChart"
                            :data="spendingChartData"
                            :options="chartOptions"
                          />
                        </v-card-text>
                      </v-card>
                    </v-tab-item>
                  </v-tabs-items>
                </div>
                <v-card-actions class="pt-0">
                  <!--
                  <v-select
                      :items="items"
                      label="Last 7 days"
                      single-line
                      class="ml-3"
                    ></v-select>
                  -->
                  <v-spacer></v-spacer>
                    <v-btn text class="white" :to="{name: 'user.customers'}" v-if="statTabs=='customers'">Customers <v-icon dark>keyboard_arrow_right</v-icon></v-btn>
                    <v-btn text class="white" :to="{name: 'user.analytics.earning'}" v-if="statTabs=='earning'">Earning <v-icon dark>keyboard_arrow_right</v-icon></v-btn>
                    <v-btn text class="white" :to="{name: 'user.analytics.spending'}" v-if="statTabs=='spending'">Spending <v-icon dark>keyboard_arrow_right</v-icon></v-btn>
                  </v-card-actions>
              </v-card>
            </v-flex>
            <v-flex xs12 md5>
              <h1 class="main-title mb-3">Intelligence</h1>
              <v-card :color="app.color_name" class="current--status">
                    <v-container fluid grid-list-lg>
                      <v-layout row>
                        <v-flex xs12>
                          <div class="statics">
                            <div class="body-1">Total customers</div>
                            <div class="lg-text">{{ formatNumber(stats.total.customers) }}</div>
                          </div>
                          <div class="mb-5">
                            <div class="d-flex body-1 custom-caption">Rewards
                              <v-spacer></v-spacer>
                              <span class="text-xs-right">{{ formatNumber(stats.total.rewards) }}</span>
                            </div>
                            <div class="d-flex body-1 custom-caption">Segments
                              <v-spacer></v-spacer>
                              <span class="text-xs-right">{{ formatNumber(stats.total.segments) }}</span>
                            </div>
                            <div class="d-flex body-1 custom-caption">Campaigns
                              <v-spacer></v-spacer>
                              <span class="text-xs-right">{{ formatNumber(stats.total.campaigns) }}</span>
                            </div>
                            <div class="d-flex body-1 custom-caption">Businesses
                              <v-spacer></v-spacer>
                              <span class="text-xs-right">{{ formatNumber(stats.total.businesses) }}</span>
                            </div>
                            <div class="d-flex body-1 custom-caption">Staff members
                              <v-spacer></v-spacer>
                              <span class="text-xs-right">{{ formatNumber(stats.total.staff) }}</span>
                            </div>
                          </div>
                          <div v-if="Object.keys(stats.popularRewards).length > 0">
                            <div class="d-flex caption custom-caption">Popular rewards
                              <v-spacer></v-spacer>
                              <span class="text-xs-right">Redemptions</span>
                            </div>
                            <div class="d-flex body-1 custom-caption" v-for="(reward, index) in stats.popularRewards" :key="index">
                              {{ reward.title }}
                              <v-spacer></v-spacer>
                              <span class="text-xs-right">{{ formatNumber(reward.number_of_times_redeemed) }}</span>
                            </div>
                          </div>
                        </v-flex>
                      </v-layout>
                    </v-container>
                    <v-card-actions v-if="Object.keys(stats.popularRewards).length > 0">
                      <v-spacer></v-spacer>
                      <v-btn text :color="app.text_color_name" :to="{name: 'user.rewards'}">All rewards
                        <v-icon dark>keyboard_arrow_right</v-icon>
                      </v-btn>
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
        loading: true,
        stats: null,
        statTabs: 'customers',
        customerChartData: [],
        earningChartData: [],
        spendingChartData: [],
        chartOptions: {
          width: '100%',
          height: 342,
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
      //this.$store.dispatch('setLoading', true)
      axios
        .get('/user/stats', { params: { locale: this.$i18n.locale }})
        .then(response => {
          let stats = response.data
          this.stats = stats

          let customerChartData = _.map(stats.customers.signupsCurrentPeriod, (count, date) => {
            return [moment(date).toDate(), count]
          });

          customerChartData.unshift(["Date", "Registrations"])
          this.customerChartData = customerChartData

          let earningChartData = _.map(stats.earnings.earnings, (count, date) => {
            return [moment(date).toDate(), count]
          });

          earningChartData.unshift(["Date", "Points earned"])
          this.earningChartData = earningChartData

          let spendingChartData = _.map(stats.spendings.spendings, (count, date) => {
            return [moment(date).toDate(), count]
          });

          spendingChartData.unshift(["Date", "Points redeemed"])
          this.spendingChartData = spendingChartData

          this.loading = false

          //this.$store.dispatch('setLoading', false)
        })
    },
    methods: {
      formatNumber(number) {
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
      app () {
        return this.$store.getters.app
      }
    }
  }
</script>
<style>
</style>