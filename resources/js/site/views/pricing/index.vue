<template>
  <div>
    <v-container grid-list-lg fluid class="pb-0">
      <v-layout row wrap>
        <v-flex>
          <v-container style="max-width: 990px" class="pb-0">
            <v-layout row wrap class="mb-5 mt-3">
              <v-flex sm12 md7 lg7>

                <transition enter-active-class="zoomIn">
                  <div v-show="animateTransition" style="animation-duration: 0.4s" class="display-3 text-xs-center text-md-left">

                    <v-img src="/images/site/visuals/mobile-rewards.png" class="mb-0" style="max-width: 400px; margin: auto">
                      <template v-slot:placeholder>
                        <v-layout
                          fill-height
                          align-center
                          justify-center
                          ma-0
                          >
                          <v-progress-circular indeterminate :color="$store.state.app.account.app_color"></v-progress-circular>
                        </v-layout>
                      </template>
                    </v-img>

                  </div>
                </transition>

              </v-flex>
              <v-flex sm12 md5 lg5>

                <div class="mx-5">
                  <transition enter-active-class="bounceInLeft">
                    <div v-show="animateTransition" style="animation-duration: 0.8s" :class="{'display-1' : $vuetify.breakpoint.xs, 'display-3' : $vuetify.breakpoint.sm, 'display-3' : $vuetify.breakpoint.smAndUp, 'mt-5' : $vuetify.breakpoint.mdAndUp}" class="text-xs-center text-md-left mb-4">Simple, predictable pricing</div>
                  </transition>

                  <transition enter-active-class="fadeIn">
                    <div class="text-xs-center text-md-left" v-show="animateTransition">
                      <div class="body-1 mt-3 mb-2">Get started with a free trial today and test all features.</div>
                      <div class="mt-3">
                        <v-btn :color="website.theme.outlined" outlined style="border-width: 2px" href="/go#/register">Sign up for free</v-btn>
                      </div>
                    </div>
                  </transition>
                </div>

              </v-flex>

            </v-layout>
          </v-container>

          <v-container style="max-width: 990px" grid-list-lg class="py-0">
            <v-layout row wrap>
              <v-flex xs12>
                <transition enter-active-class="fadeIn">
                  <div v-show="animateTransition" style="animation-duration: 0.6s">
                    <div class="body-1">You can use a custom domain for each campaign website in your package.</div>
                  </div>
                </transition>
              </v-flex>
            </v-layout>
          </v-container>

          <v-container style="max-width: 990px" grid-list-lg class="mb-5">
            <v-layout row wrap>
              <v-flex xs12>
                <transition enter-active-class="zoomIn">
                  <v-data-table 
                    v-show="animateTransition" 
                    style="animation-duration: 0.2s" 
                    class="elevation-2" 
                    :headers="priceHeaders" 
                    :items="website.plans" 
                    :items-per-page="10"
                    :mobile-breakpoint="680"
                  >
                    <template slot="item.price" slot-scope="props">
                      <strong>{{ props.item.price }}</strong>
                    </template>
                  </v-data-table>
                </transition>
              </v-flex>
            </v-layout>
          </v-container>

          <v-container style="max-width: 990px" grid-list-lg class="py-0">
            <v-layout row wrap class="mt-5">
              <v-flex xs12>
                <transition enter-active-class="fadeIn">
                  <div v-show="animateTransition" style="animation-duration: 0.6s">
                    <div class="headline">Frequently Asked Questions</div>
                  </div>
                </transition>
              </v-flex>
            </v-layout>
          </v-container>

          <v-container style="max-width: 990px" grid-list-lg class="pt-0">
            <v-layout row wrap class="my-5">
              <v-flex xs12>
                <v-expansion-panels>
                  <v-expansion-panel
                    v-for="(item, i) in _.toPairs(website.pricingFaq)"
                    :key="i"
                  >
                    <v-expansion-panel-header>{{ item[0] }}</v-expansion-panel-header>
                    <v-expansion-panel-content>
                      <div v-html="item[1]"></div>
                    </v-expansion-panel-content>
                  </v-expansion-panel>
                </v-expansion-panels>
              </v-flex>
            </v-layout>
          </v-container>

        </v-flex>
      </v-layout>
    </v-container>

    <v-container grid-list-lg fluid :class="website.theme.primary" class="pa-0" style="position: relative;">

      <svg version="1.1" x="0px" y="0px" viewBox="0 0 2979 108" style="enable-background:new 0 0 2979 108;display:inline-block;height:110px;position: absolute; left:0 ;top: -10px" xml:space="preserve">
        <polyline :fill="website.theme.backgroundColor" points="2979,0 0,108 0,0 "/>
      </svg>

      <v-layout row wrap>
        <v-flex>

          <v-container style="max-width: 990px">
            <v-layout row wrap>
              <v-flex sm12 md7 lg7>

                <transition enter-active-class="zoomIn">
                  <div v-show="animateTransition" style="animation-duration: 0.5s" class="display-3 text-xs-center text-md-left">
                    <v-img src="/images/site/visuals/clothing-home.png" class="elevation-20">
                      <template v-slot:placeholder>
                        <v-layout
                          fill-height
                          align-center
                          justify-center
                          ma-0
                          >
                          <v-progress-circular indeterminate color="white"></v-progress-circular>
                        </v-layout>
                      </template>
                    </v-img>
                  </div>
                </transition>

              </v-flex>
              <v-flex sm12 md5 lg5>

                <div class="mx-6 my-7">
                  <transition enter-active-class="bounceInRight">
                    <div v-show="animateTransition" style="animation-duration: 0.8s">
                      <div class="subtitle-1 font-weight-thin text-uppercase text-xs-center text-md-left pt-12">Suitable for all types of businesses</div>
                      <div class="display-1 my-2 text-xs-center text-md-left">Create loyalty websites with rewards where customers can save for</div>
                      <div class="body-1 text-xs-center text-md-left">Setting up a high quality campaign website requires little effort.  Our intuitive interface takes you by the hand, leading you to a successful loyalty campaign.</div>
                    </div>
                  </transition>
                  <transition enter-active-class="fadeIn">
                    <div class="mt-3 text-xs-center text-md-right" v-show="animateTransition">
                      <v-btn text dark class="mr-2" :to="{'name': 'features'}">View features <v-icon>arrow_forward</v-icon></v-btn>
                    </div>
                  </transition>
                </div>

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
    data () {
      return {
        locale: 'en',
        animateTransition: false,
        priceHeaders: [
          { text: 'Customers', value: 'customers', align: 'center', sortable: false},
          { text: 'Campaigns', value: 'campaigns', align: 'center', sortable: false},
          { text: 'Rewards', value: 'rewards', align: 'center', sortable: false},
          { text: 'Businesses', value: 'businesses', align: 'center', sortable: false},
          { text: 'Staff members', value: 'staff', align: 'center', sortable: false},
          { text: 'Segments', value: 'segments', align: 'center', sortable: false},
          { text: 'Price', value: 'price', align: 'center', sortable: false},
        ],
      }
    },
    methods: {
    },
    mounted () {
      this.animateTransition = true
    },
    computed: {
      website () {
        return this.$store.state.app.website
      },
      _ () {
        return _;
      }
    }
  }
</script>
<style scoped>
</style>