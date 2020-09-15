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

    <v-card flat color="transparent" v-if="!loading">
      <v-toolbar flat color="transparent">
        <v-toolbar-title>Billing</v-toolbar-title>
      </v-toolbar>

      <v-card-text v-if="$auth.user().demo">
        <v-alert :color="app.color_name" class="mb-0">
          This account is in demo mode. You can't purchase or update a subscription.
        </v-alert>
      </v-card-text>

      <v-card-text v-if="$auth.user().expired">
        <v-alert dark color="red" class="mb-0">
          This account has expired. It is not possible to visit other pages until this has been resolved.
        </v-alert>
      </v-card-text>

      <v-card-text v-if="externalBuyLinkOpened">
        <p class="subtitle-2">You have opened an external link. If you want to undo this operation, you can refresh this page.</p>
        <p class="subtitle-2">After going through the payment steps and receiving the payment confirmation, your subscription will be upgraded automatically.</p>
      </v-card-text>

      <v-card-text v-if="(!remoteCustomer && !subscriptionCancelled) || (!externalBuyLinkOpened && !subscriptionCancelled && !paymentSucceeded && $auth.user().plan_id == null)">
        <div class="title">You are currently on the {{ $auth.user().plan_name }} plan.</div>
        <div class="subtitle-1 mb-3">{{ expires }}</div>

        <div class="subtitle-1 mb-3" v-if="paymentProvider === null">Please contact <a :href="'mailto:' + appContact ">{{ appContact }}</a> if you want to change your subscription.</div>

        <v-data-table 
          class="elevation-2" 
          :headers="priceHeaders" 
          :items="plans" 
          :items-per-page="5"
          :mobile-breakpoint="0"
        >
          <template slot="item.price" slot-scope="props">
            <strong>{{ props.item.price }}</strong>
          </template>
          <template slot="item.buy" slot-scope="props" v-if="paymentProvider !== null">
            <v-btn color="success" :disabled="$auth.user().demo === 1 ? true : false" small @click="openCheckout(props.item)">Buy now</v-btn>
          </template>
        </v-data-table>

      </v-card-text>

      <v-card-text v-if="remoteCustomer && !subscriptionCancelled && !paymentSucceeded && $auth.user().plan_id != null">
        <div class="title">You are currently on the {{ $auth.user().plan_name }} plan.</div>
        <div class="subtitle-1 mb-3">{{ expires }}</div>

        <div class="subtitle-1 mb-3" v-if="paymentProvider === null">Please contact <a :href="'mailto:' + appContact ">{{ appContact }}</a> if you want to change your subscription.</div>

        <p class="subtitle-1" v-if="paymentProvider !== null">If you want to upgrade or downgrade your subscription, cancel your current subscription and purchase a new one. No data or service will be lost during cancellation.</p>

        <p class="subtitle-1" v-if="paymentProvider == '2checkout'">Cancel your subscription on <a href="https://secure.2co.com/myaccount/" target="_blank">2Checkout</a>. For more information <a :href="'https://secure.2checkout.com/support/faq.php?merchant=' + vendorId + '&template=&question=68#actualContent'" target="_blank">click here</a>.</p>

        <v-data-table 
          class="elevation-2" 
          :headers="priceHeaders" 
          :items="plans" 
          :items-per-page="5"
          :mobile-breakpoint="0"
        >
          <template slot="item.price" slot-scope="props">
            <strong>{{ props.item.price }}</strong>
          </template>
          <template slot="item.buy" slot-scope="props" v-if="paymentProvider !== null">
            <v-btn color="red white--text" small :disabled="!props.item.active" @click="cancelSubscription" v-if="paymentProvider == 'paddle' || paymentProvider == 'stripe'">Cancel subscription</v-btn>
          </template>
        </v-data-table>

      </v-card-text>

      <v-card-text v-if="paymentSucceeded">
        <p class="title">Thank you for your purchase!</p>
        <p class="subtitle-2">You will receive an e-mail when your payment is processed. You can then refresh this page, or log in again.</p>
        <v-btn x-large color="green white--text" @click="paymentSucceeded = false">OK</v-btn>
      </v-card-text>

      <v-card-text v-if="subscriptionCancelled">
        <p class="title">Your subscription has been cancelled.</p>
        <p class="subtitle-2">Thank you for using our service, you will not be charged again.</p>
        <v-btn x-large color="green white--text" @click="subscriptionCancelled = false">OK</v-btn>
      </v-card-text>

    </v-card>

    <v-overlay :value="overlay">
      <v-progress-circular indeterminate size="64"></v-progress-circular>
    </v-overlay>
  </div>
</template>
<script>
  export default {
    $_veeValidate: {
      validator: 'new'
    },
    data () {
      return {
        locale: 'en',
        appContact: null,
        paymentProvider: null,
        paymentTestMode: false,
        remoteCustomer: false,
        vendorId: null,
        affiliateId: null,
        paymentSucceeded: false,
        subscriptionCancelled: false,
        externalBuyLinkOpened: false,
        stripeHandler: null,
        selectedPlanId: null,
        selectedRemotePlanId: null,
        plans: [],
        subscription: null,
        overlay: true,
        loading: true,
        priceHeaders: [
          { text: 'Customers', value: 'customers', align: 'center', sortable: false},
          { text: 'Campaigns', value: 'campaigns', align: 'center', sortable: false},
          { text: 'Rewards', value: 'rewards', align: 'center', sortable: false},
          { text: 'Businesses', value: 'businesses', align: 'center', sortable: false},
          { text: 'Staff members', value: 'staff', align: 'center', sortable: false},
          { text: 'Segments', value: 'segments', align: 'center', sortable: false},
          { text: 'Price', value: 'price', align: 'center', sortable: false},
          { value: 'buy', align: 'center', sortable: false},
        ]
      }
    },
    created () {
      this.getData()
        .then(() => {
          if (this.paymentProvider == '2checkout') {
            // Do nothing
          }

          if (this.paymentProvider == 'stripe') {
            var JavaScript = {
              load: function(src, callback) {
                var script = document.createElement('script'),
                    loaded;
                script.setAttribute('src', src)
                if (callback) {
                  script.onreadystatechange = script.onload = function() {
                    if (!loaded) {
                      callback()
                    }
                    loaded = true;
                  }
                }
                document.getElementsByTagName('head')[0].appendChild(script)
              }
            }

            let that = this
            let vendorId = this.vendorId

            JavaScript.load("//checkout.stripe.com/checkout.js", function() {
              that.stripeHandler = window.StripeCheckout.configure({
                key: vendorId,
                image: null,
                locale: 'auto',
                token: function(token) {
                  // You can access the token ID with `token.id`.
                  // Get the token ID to your server-side code for use.
                  if (that.selectedRemotePlanId !== null) {
                    
                    that.overlay = true
                    axios.post('/user/stripe/token', {
                      token: token.id, 
                      email: token.email, 
                      type: token.type, 
                      plan_id: that.selectedPlanId, 
                      stripe_plan_id: that.selectedRemotePlanId
                    })
                    .then(function (response) {
                      that.remoteCustomer = true
                      that.paymentSucceeded = true
                      that.overlay = false
                    })
                    .catch(function (error) {
                      that.$root.$snackbar("An unknow error has occured. Please refresh this page and try again. Contact us if the error persists.")
                      that.overlay = false
                      console.log(error)
                    })
                  }
                }
              })
            })
          }

          if (this.paymentProvider == 'paddle') {
            var JavaScript = {
              load: function(src, callback) {
                var script = document.createElement('script'),
                    loaded;
                script.setAttribute('src', src)
                if (callback) {
                  script.onreadystatechange = script.onload = function() {
                    if (!loaded) {
                      callback()
                    }
                    loaded = true;
                  }
                }
                document.getElementsByTagName('head')[0].appendChild(script)
              }
            }

            let vendorId = this.vendorId

            JavaScript.load("//cdn.paddle.com/paddle/paddle.js", function() {
              Paddle.Setup({ vendor: parseInt(vendorId) })
            });
          }
        })
    },
    mounted () {
      let locale = Intl.DateTimeFormat().resolvedOptions().locale || 'en'
      locale = (this.$auth.check()) ? this.$auth.user().locale : locale

      moment.locale(this.locale.substr(0,2))
    },
    methods: {
      getDate(date) {
        return moment(date).format("lll")
      },
      getDateFrom(date) {
        return moment(date).from()
      },
      openCheckout (item) {
        let that = this

        if (this.paymentProvider == 'stripe') {
          if (item.remote_id === null) {
              this.$root.$snackbar("The remote ID has not been configured.")
              return;
          }

          // Set global vars
          this.selectedPlanId = item.id
          this.selectedRemotePlanId = item.remote_id

          // Open Checkout with further options:
          this.stripeHandler.open({
            name: item.price,
            description: null,
            currency: item.currency,
            amount: item.amount
          })

        }

        if (this.paymentProvider == '2checkout') {
          let qs = ''
          if (this.affiliateId !== null) qs += '&AFFILIATE=' + this.affiliateId
          if (this.paymentTestMode) qs += '&DOTEST=1'

          window.open('https://secure.2checkout.com/order/checkout.php?PRODS=' + item.remote_id + '&QTY=1&CART=1&CUSTOMERID=' + this.$auth.user().uuid + '&CARD=1' + qs, '_billing')
          this.externalBuyLinkOpened = true
        }

       if (this.paymentProvider == 'paddle') {
          Paddle.Checkout.open({
            product: item.remote_id,
            email: this.$auth.user().email,
            passthrough: "{\"uuid\": \"" + this.$auth.user().uuid + "\"}",
            successCallback: function(data) {
              that.remoteCustomer = true
              that.paymentSucceeded = true
            },
            closeCallback: function(data) {
            }
          })
        }
      },
      cancelSubscription () {
        let that = this

        if (this.paymentProvider == 'stripe') {
          this.$root.$confirm("Cancel subscription", "Do you want to cancel your subscription? Your account will stay active for the period you have paid for and no data will be lost.")
          .then((confirm) => {
            if (confirm) {
              that.overlay = true

              axios.post('/user/stripe/cancel')
                .then(function (response) {
                  that.subscriptionCancelled = true
                  that.remoteCustomer = false
                  that.overlay = false
                })
                .catch(function (error) {
                  console.log(error)
                  that.overlay = false
                })
            }
          })
        }

        if (this.paymentProvider == '2checkout') {
          window.open('https://secure.avangate.com/support/faq.php?merchant=' + this.vendorId + '&template=&lang=en&question=68&category=0#actualContent', '_billing')
        }

        if (this.paymentProvider == 'paddle') {
          this.$root.$confirm("Cancel subscription", "Do you want to cancel your subscription? Your account will stay active for the period you have paid for and no data will be lost.")
          .then((confirm) => {
            if (confirm) {
                this.subscriptionCancelled = true
                this.remoteCustomer = false
                window.open(this.subscription.subscription_cancel_url, '_billing')
            }
          })
        }
      },
      getData () {
        this.overlay = true

        return new Promise((resolve, reject) => {
          axios
            .get('/user/plans', {params: { 
              locale: this.$i18n.locale
            }})
            .then(response => {
              this.appContact = response.data.app_contact
              this.plans = response.data.plans
              this.subscription = response.data.subscription
              this.paymentProvider = response.data.payment_provider
              this.paymentTestMode = response.data.payment_test_mode
              this.remoteCustomer = response.data.remote_customer
              this.vendorId = response.data.vendor_id
              this.affiliateId = response.data.affiliate_id

              this.loading = false
              this.overlay = false
              resolve()
            })
        })
      },
      formatNumber (number) {
        return new Intl.NumberFormat(this.locale.replace('_', '-')).format(number)
      }
    },
    computed: {
      app () {
        return this.$store.getters.app
      },
      expires () {
        let expires = this.$auth.user().expires_at
        let expired = this.$auth.user().expired
        let expired_text = (expired === true) ? 'Expired' : 'Expires'
        expires = (expires === null) ? 'never' : this.getDate(expires)
        expires = (this.$auth.user().plan_id == null) ? expired_text + ' ' + expires + '.' : 'Next billing date is ' + expires + '.'
        return expires
      }
    }
  }
</script>
<style>
</style>