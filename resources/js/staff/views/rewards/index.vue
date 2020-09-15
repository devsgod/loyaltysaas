<template>
  <div class="pb-5">
    <v-container grid-list-lg fluid class="pa-0">
      <v-layout row wrap>
        <v-flex class="py-0" xs12 :style="{'background-color': campaign.theme.secondaryColor, 'color': campaign.theme.secondaryTextColor}">
            <v-container fill-height>
              <v-layout row wrap align-center>
                <v-flex xs12 style="z-index: 2">
                <div class="mt-3 mb-7 pa-0">
                  <h1 class="display-1 mb-1">Redeem rewards</h1>
                  <div>Below are the options the customer has to redeem a reward.</div>
                </div>
              </v-flex>
            </v-layout>
          </v-container>
        </v-flex>
      </v-layout>
    </v-container>

    <v-container grid-list-lg class="mt-7">
      <v-layout row wrap>
        <v-flex d-flex xs12 sm6 sm6 lg3 v-for="(item, index) in redeemOptions" :key="index" v-if="item.active">

          <v-hover>
            <template v-slot:default="{ hover }">

              <v-card @click="dialog.claim[item.id] = true;" class="w-100 card-link text-xs-center">
                <div class="overlay-highlight"></div>
                <v-icon size="64" class="mt-5 grey--text text--darken-3">{{ item.icon }}</v-icon>
                <v-card-title primary-title style="display: block">
                  <div>
                    <h3 class="title grey--text text--darken-3 mb-2" v-html="item.title"></h3>
                    <div class="grey--text text--darken-1 subtitle-1 mb-2" v-html="item.description"></div>
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


    <!-- Claim QR --------------------------------------------------------------------------------------------------------------------------------- -->

    <v-dialog v-model="dialog.claim.qr" persistent max-width="380">
      <v-card>
        <v-card-title class="headline">QR code</v-card-title>
        <v-card-text>
          <ol class="body-1">
            <li>The customer shows a QR code.</li>
            <li>Scan this code with any QR scanner app on your mobile device.</li>
            <li>This opens a webpage where you can redeem the reward.</li>
          </ol>

        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="secondary" text @click="dialog.claim.qr = false">Close</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Claim - Merchant Enters Code --------------------------------------------------------------------------------------------------------------------------------- -->

    <v-dialog v-model="dialog.claim.merchant" persistent max-width="380">
      <v-card>
        <v-form 
          data-vv-scope="customerCode"
          :model="merchant" 
          @submit.prevent="generateMerchantCode"
          autocomplete="off"
          method="post"
          accept-charset="UTF-8" 
        >
          <v-card-title class="headline">Generate code to enter on customer's device</v-card-title>
          <v-card-text>
              <p class="body-1">You can use this code for as many customers as you want, as long as it is not expired.</p>

              <v-select
                :disabled="!merchant.generateNew"
                v-model="merchant.expiresSelected"
                :items="expires"
                :return-object="false"
                hide-no-data
                prepend-inner-icon="calendar_today"
              ></v-select>

              <v-text-field
                :key="merchantCodeCounter"
                v-if="!merchant.generateNew"
                type="text"
                outlined
                readonly
                class="title mt-3"
                id="generatedMerchantCode"
                v-model="merchant.code"
                append-icon="filter_none"
                @click:append="copyElById('generatedMerchantCode')"
                :label="getExpirationFromNow (activeMerchantCodeExpires, 'Expires ')"
                persistent-hint
                required
              ></v-text-field>

              <div v-if="!merchant.generateNew">
                <a href="javascript:void(0);" @click="merchant.generateNew = true">Generate a new code</a> 
                <br>
                This will revoke the current code once it's generated.
              </div>

           </v-card-text>
          <v-card-actions>
            <v-spacer></v-spacer>
            <v-btn color="primary" :loading="merchant.loading" :disabled="!merchant.generateNew" type="submit">Generate</v-btn>
            <v-btn color="secondary" text @click="dialog.claim.merchant = false; if (merchant.code != '') merchant.generateNew = false">Close</v-btn>
          </v-card-actions>

        </v-form>
      </v-card>
    </v-dialog>

    <!-- Claim - Provide Customer Number --------------------------------------------------------------------------------------------------------------------------------- -->

    <v-dialog v-model="dialog.claim.customerNumber" persistent max-width="380">
      <v-card>
        <v-card-title class="headline">Redeem reward with customer number</v-card-title>
          <v-form 
            data-vv-scope="customerNumber"
            :model="customerNumber" 
            @submit.prevent="creditCustomer"
            autocomplete="off"
            method="post"
            accept-charset="UTF-8" 
          >
            <v-card-text>
              <p class="body-1" v-if="customerNumber.redeemed === false">Select a reward and enter a customer number to redeem a reward for the customer.</p>
              <p class="body-1" v-if="customerNumber.redeemed !== false">Reward successfully redeemed.</p>

              <div class="mb-3">
                <a href="javascript:void(0);" v-if="customerNumber.redeemed !== false" @click="customerNumber.redeemed = false">Redeem another reward</a>
              </div>

              <v-autocomplete
                :disabled="customerNumber.redeemed !== false"
                v-model="customerNumber.reward"
                :items="rewards"
                item-value="0" 
                item-text="1"
                label="Reward"
                data-vv-name="reward"
                hide-no-data
                hide-selected
                prepend-inner-icon="fas fa-gift"
                v-validate="'required'"
                :error-messages="errors.collect('customerNumber.reward')"
              ></v-autocomplete>

              <v-text-field
                :disabled="customerNumber.redeemed !== false"
                type="text"
                v-model="customerNumber.number"
                outline
                label="Customer number"
                data-vv-name="number"
                v-validate="'required|min:11|max:11'"
                placeholder="xxx-xxx-xxx"
                v-mask="'###-###-###'"
                prepend-inner-icon="person"
                :error-messages="errors.collect('customerNumber.number')"
              ></v-text-field>

              <v-autocomplete
                :disabled="customerNumber.redeemed !== false"
                v-if="Object.keys(segments).length > 0"
                v-model="customerNumber.segments"
                :items="segments"
                item-value="0" 
                item-text="1"
                label="Segments (optional)"
                hide-no-data
                hide-selected
                chips
                multiple
                prepend-inner-icon="category"
                deletable-chips
              ></v-autocomplete>


           </v-card-text>
          <v-card-actions>
            <v-spacer></v-spacer>
            <v-btn color="primary" :loading="customerNumber.loading" :disabled="customerNumber.redeemed !== false" type="submit">Redeem reward</v-btn>
            <v-btn color="secondary" text @click="dialog.claim.customerNumber = false">Close</v-btn>
          </v-card-actions>

        </v-form>

      </v-card>
    </v-dialog>

  </div>
</template>
<script>
  import {copyElById, unmask} from '../../utils/helpers';

  export default {
    $_veeValidate: {
      validator: 'new'
    },
    data () {
      return {
        segments: [],
        rewards: [],
        expires: [
          {value: 'hour', text: 'Expires in one hour'},
          {value: 'day', text: 'Expires in one day'},
          {value: 'week', text: 'Expires expires in one week'},
          {value: 'month', text: 'Expires expires in one month'}
        ],
        customerCodeCounter: 1,
        activeMerchantCodeExpires: null,
        merchant: {
          generateNew: true,
          loading: false,
          code: '',
          expiresSelected: 'day'
        },
        merchantCodeGenerated: false,
        merchantCodeCounter: 1,
        customerNumber: {
          loading: false,
          redeemed: false,
          reward: null,
          segments: [],
          number: null
        },
        dialog: {
          claim: {
            qr: false,
            code: false,
            merchant: false,
            customerNumber: false
          }
        }
      }
    },
    mounted () {
      axios
        .get('/staff/segments', { params: { locale: this.$i18n.locale, campaign: this.$store.state.app.campaign.uuid }})
        .then(response => {
          this.segments = _.toPairs(response.data)
        })

      axios
        .get('/staff/rewards', { params: { locale: this.$i18n.locale, campaign: this.$store.state.app.campaign.uuid }})
        .then(response => {
          this.rewards = _.toPairs(response.data)
        })

      this.getActiveMerchantCode()
    },
    created () {
      moment.locale(this.$auth.user().locale)
    },
    methods: {
      copyElById,
      unmask,
      generateMerchantCode () {
        this.merchant.loading = true
        // validation
        this.$validator.validateAll('merchant').then((valid) => {
          if (! valid) {
            this.merchant.loading = false
            return false
          } else {
            axios
              .post('/staff/rewards/merchant/generate-code', {
                  locale: this.$i18n.locale,
                  campaign: this.$store.state.app.campaign.uuid,
                  expires: this.merchant.expiresSelected
              })
              .then(response => {
                if (response.data.status === 'success') {
                  this.merchant.code = response.data.code
                  this.merchant.loading = false

                  // Get currently active merchant code
                  this.getActiveMerchantCode()
                }
              })
              .catch(error => {
                // Error
              })
          }
        });
      },
      getActiveMerchantCode () {
        // Get currently active merchant code
        axios
          .get('/staff/rewards/merchant/active-code', { params: { locale: this.$i18n.locale, campaign: this.$store.state.app.campaign.uuid }})
          .then(response => {
            if (typeof response.data.code !== 'undefined') {
              this.merchant.code = response.data.code
              this.merchant.generateNew = false
              this.activeMerchantCodeExpires = response.data.expires_at
              this.merchantCodeCounter ++
            }
          })

      },
      creditCustomer () {
        this.customerNumber.loading = true
        // validation
        this.$validator.validateAll('customerNumber').then((valid) => {
          if (! valid) {
            this.customerNumber.loading = false
            return false
          } else {
            axios
              .post('/staff/rewards/customer/credit', {
                  locale: this.$i18n.locale,
                  campaign: this.$store.state.app.campaign.uuid,
                  reward: this.customerNumber.reward,
                  number: this.unmask(this.customerNumber.number, '###-###-###'),
                  segments: this.customerNumber.segments
              })
              .then(response => {
                if (response.data.status === 'success') {
                  this.customerNumber.redeemed = true
                }
                this.customerNumber.loading = false
              })
              .catch(err => {

                let errors = err.response.data.errors || {}

                for (let field in errors) {
                  this.$validator.errors.add({
                    field: 'customerNumber.' + field,
                    msg: errors[field]
                  })
                }
                this.customerNumber.loading = false
              })
          }
        });
      },
      getExpirationFromNow (expires, prefix = '') {
          if (expires !== null) {
            return prefix + moment(expires).fromNow()
          } else {
            return
          }
      }
    },
    computed: {
      campaign () {
        return this.$store.state.app.campaign
      },
      redeemOptions() {
        return  [
          { 
            active: (_.indexOf(this.campaign.redeemOptions, 'qr') >= 0) ? true : false,
            id: 'qr',
            icon: 'fas fa-qrcode',
            title: 'QR Code',
            description: 'The customer displays a QR that can be scanned by a staff member.'
          },
          { 
            active: (_.indexOf(this.campaign.redeemOptions, 'merchant') >= 0) ? true : false,
            id: 'merchant',
            icon: 'fas fa-hand-holding',
            title: 'Merchant Enters Code',
            description: 'Generate a code that a staff member can enter on the customer\'s phone.'
          },
          { 
            active: (_.indexOf(this.campaign.redeemOptions, 'customerNumber') >= 0) ? true : false,
            id: 'customerNumber',
            icon: 'card_giftcard',
            title: 'Customer Number',
            description: 'Redeem a reward with a customer number.'
          }
        ]
      },
    },
  };
</script>
<style scoped>
</style>