<template>
  <div class="pb-5">
    <v-container grid-list-lg fluid class="pa-0">
      <v-layout row wrap>
        <v-flex class="py-0" xs12 :style="{'background-color': campaign.theme.secondaryColor, 'color': campaign.theme.secondaryTextColor}">
          <v-img
            :src="campaign.earn.headerImg || ''"
            :height="campaign.earn.headerHeight"
          >
            <v-overlay
              absolute
              :color="campaign.theme.secondaryColor"
              :opacity="campaign.earn.headerOpacity"
              z-index="1"
            >
            </v-overlay>
            <v-container fill-height>
              <v-layout row wrap align-center>
                <v-flex xs12 style="z-index: 2">
                  <h1 class="display-1 mb-2" v-html="campaign.earn.headerTitle"></h1>
                  <div v-html="campaign.earn.headerContent"></div>
                </v-flex>
              </v-layout>
            </v-container>
            <template v-slot:placeholder>
              <v-layout
                fill-height
                align-center
                justify-center
                ma-0
                v-if="campaign.earn.headerImg"
              >
                <v-progress-circular indeterminate :style="{'color': campaign.theme.secondaryTextColor}"></v-progress-circular>
              </v-layout>
            </template>
          </v-img>
        </v-flex>
      </v-layout>
    </v-container>

    <v-container grid-list-xl>
      <v-layout row wrap>
        <v-flex xs12>
          <div class="display-1 mb-3 mt-6" v-html="campaign.earn.pageTitle" :style="{'color': campaign.theme.textColor}"></div>
        </v-flex>
        <v-flex d-flex xs12 sm6 sm6 lg3 v-for="(item, index) in claimOptions" :key="index" v-if="item.active">
          <v-hover>
            <template v-slot:default="{ hover }">
              <v-card @click="dialog.claim[item.id] = ($auth.check()) ? true : false; dialog.authRequired = ($auth.check()) ? false : true;" class="w-100 card-link text-xs-center">
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

    <!-- Login dialog --------------------------------------------------------------------------------------------------------------------------------- -->

    <v-dialog v-model="dialog.authRequired" persistent max-width="320">
      <v-card>
        <v-card-text class="title pt-5">
          {{ $t('log_in_to_use_this_feature') }}
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="secondary" text @click="dialog.authRequired = false">{{ $t('close') }}</v-btn>
          <v-btn color="primary" :to="{name: 'login'}">{{ $t('log_in') }}</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Claim QR --------------------------------------------------------------------------------------------------------------------------------- -->

    <v-dialog v-model="dialog.claim.qr" persistent max-width="320">
      <v-card>
        <v-card-title class="headline">{{ $t('show_qr_to_merchant') }}</v-card-title>
        <v-card-text v-if="dialog.claim.qrVisible && connectionError === false && linkPointsCredited === null">
          <p class="body-1">{{ $t('keep_dialog_open_until_confirmation') }}</p>
          <qr-code 
            class="qr-100"
            :text="dialog.claim.qrUrl"
            color="#000000"
            bg-color="transparent" 
            error-level="Q"
          >
          </qr-code>
        </v-card-text>
        <v-card-text v-if="linkPointsCredited !== null">
          <p class="body-1" v-html="$t('received_points_close_dialog', {points: '<strong>' + linkPointsCredited + '</strong>'})"></p>
        </v-card-text>
        <v-card-text v-if="connectionError !== false">
          <p class="body-1">A connection error has occured ({{ connectionError }}). Please close this dialog and try again.</p>
        </v-card-text>
        <v-card-text v-if="! dialog.claim.qrVisible">
          <v-layout
            fill-height
            align-center
            justify-center
            style="height: 303px"
          >
            <v-progress-circular :size="64" indeterminate :style="{'color': campaign.theme.textColor}"></v-progress-circular>
          </v-layout>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="secondary" text @click="closeQrDialog">{{ $t('close') }}</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Claim - Enter Code --------------------------------------------------------------------------------------------------------------------------------- -->

    <v-dialog v-model="dialog.claim.code" persistent max-width="320">
      <v-card>
        <v-card-title class="headline">{{ $t('enter_code') }}</v-card-title>
        <v-form 
          data-vv-scope="customerCode"
          :model="customerCode" 
          @submit.prevent="verifyCustomerCode"
          autocomplete="off"
          method="post"
          accept-charset="UTF-8" 
        >
          <v-card-text v-if="!customerCode.verfied">
            <p class="body-1">{{ $t('enter_code_you_received') }}</p>
            <v-text-field
              :disabled="customerCode.verfied"
              v-model="customerCode.code"
              outline
              :label="$t('enter_9_digit_code')"
              data-vv-name="code"
              :data-vv-as="$t('code')"
              :error-messages="errors.collect('customerCode.code')"
              v-validate="'required|min:11|max:11'"
              placeholder="xxx-xxx-xxx"
              v-mask="'###-###-###'"
              prepend-inner-icon="textsms"
              class="title"
            ></v-text-field>
          </v-card-text>
          <v-card-text v-if="customerCode.verfied">
            <p class="body-1">{{ $t('code_verified_points_credited') }}</p>
          </v-card-text>
          <v-card-actions>
            <v-spacer></v-spacer>
            <v-btn color="primary" :loading="customerCode.loading" :disabled="customerCode.verfied" type="submit">{{ $t('verify') }}</v-btn>
            <v-btn color="secondary" text @click="dialog.claim.code = false; customerCode.verfied = false; customerCode.code = '';">{{ $t('close') }}</v-btn>
          </v-card-actions>
        </v-form>
      </v-card>
    </v-dialog>

    <!-- Claim - Merchant Enters Code --------------------------------------------------------------------------------------------------------------------------------- -->

    <v-dialog v-model="dialog.claim.merchant" persistent max-width="320">
      <v-card>
        <v-card-title class="headline">{{ $t('let_merchant_enter_code') }}</v-card-title>
        <v-form 
          v-show="!merchantCode.verfied"
          data-vv-scope="merchantCode"
          :model="merchantCode" 
          @submit.prevent="verifyMerchantCode"
          autocomplete="off"
          method="post"
          accept-charset="UTF-8" 
        >
          <v-card-text>
            <p class="body-1">{{ $t('hand_over_device_to_merchant') }}</p>
            <v-text-field
            v-model="merchantCode.code"
            data-vv-name="code"
            :data-vv-as="$t('code')"
            :type="dialog.claim.showMerchantCode ? 'text' : 'password'"
            :append-icon="dialog.claim.showMerchantCode ? 'visibility' : 'visibility_off'"
            @click:append="dialog.claim.showMerchantCode = !dialog.claim.showMerchantCode"
            v-validate="'required|max:64'"
            :error-messages="errors.collect('merchantCode.code')"
            outline
            :label="$t('enter_code_here')"
            class="title"
            ></v-text-field>
           </v-card-text>
          <v-card-actions>
            <v-spacer></v-spacer>
            <v-btn color="primary" :loading="merchantCode.loading" :disabled="merchantCode.verfied" type="submit">{{ $t('verify') }}</v-btn>
            <v-btn color="secondary" text @click="closeMerchantCode">{{ $t('close') }}</v-btn>
          </v-card-actions>
        </v-form>

        <v-form 
          v-if="merchantCode.verfied && !merchantCode.processed"
          data-vv-scope="merchantCodeVerified"
          :model="merchantCodeVerified" 
          @submit.prevent="processMerchantCode"
          autocomplete="off"
          method="post"
          accept-charset="UTF-8" 
        >
          <v-card-text>
            <p class="body-1" v-html="$t('code_correct_enter_points')"></p>
              <v-text-field
                type="number"
                v-model="merchantCodeVerified.points"
                outline
                label="Points to be credited"
                prepend-inner-icon="toll"
                data-vv-name="points"
                :data-vv-as="$t('points')"
                v-validate="'required|numeric|max_value:100000'"
                :error-messages="errors.collect('merchantCodeVerified.points')"
              ></v-text-field>
              <v-autocomplete
                v-if="Object.keys(segments).length > 0"
                v-model="merchantCodeVerified.segments"
                :items="segments"
                item-value="0" 
                item-text="1"
                :label="$t('segments') + ' ' + $t('_optional_')"
                :data-vv-as="$t('segments')"
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
            <v-btn color="primary" :loading="merchantCodeVerified.loading" :disabled="merchantCode.processed" type="submit">{{ $t('credit_customer') }}</v-btn>
            <v-btn color="secondary" text @click="closeMerchantCode">{{ $t('close') }}</v-btn>
          </v-card-actions>
        </v-form>
        <div v-if="merchantCode.verfied && merchantCode.processed">
          <v-card-text>
            <p class="body-1">{{ $t('points_credited_successfully') }}</p>
           </v-card-text>
          <v-card-actions>
            <v-spacer></v-spacer>
            <v-btn color="secondary" text @click="closeMerchantCode">{{ $t('close') }}</v-btn>
          </v-card-actions>
        </div>
      </v-card>
    </v-dialog>

    <!-- Claim - Provide Customer Number --------------------------------------------------------------------------------------------------------------------------------- -->

    <v-dialog v-model="dialog.claim.customerNumber" persistent max-width="320">
      <v-card>
        <v-card-title class="headline">{{ $t('customer_number') }}</v-card-title>
        <v-card-text>
          <p class="body-1">{{ $t('give_number_to_merchant') }}</p>
          <v-text-field
            type="text"
            class="title"
            outlined
            readonly
            id="customerNumber"
            :value="this.$auth.user().number"
            append-icon="filter_none"
            @click:append="copyElById('customerNumber')"
            required
          ></v-text-field>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="secondary" text @click="dialog.claim.customerNumber = false">{{ $t('close') }}</v-btn>
        </v-card-actions>
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
        socket: null,
        connectionError: false,
        linkPointsCredited: null,
        dialog: {
          authRequired: false,
          claim: {
            qr: false,
            qrVisible: false,
            qrUrl: '',
            code: false,
            merchant: false,
            showMerchantCode: false,
            customerNumber: false
          }
        },
        customerCode: {
          loading: false,
          verfied: false,
          code: ''
        },
        merchantCode: {
          loading: false,
          verfied: false,
          processed: false,
          code: ''
        },
        merchantCodeVerified: {
          loading: false,
          points: '',
          code: ''
        }
      }
    },
    methods: {
      copyElById,
      unmask,
      closeQrDialog () {
        this.socket.disconnect()
        this.dialog.claim.qr = false
        this.connectionError = false
      },
      generateClaimToken () {
        axios
          .post('/campaign/get-claim-points-token', {
              locale: this.$i18n.locale,
              campaign: this.$store.state.app.campaign.uuid
          })
          .then(response => {
            if (response.data.status === 'success') {
              let that = this
              let token = response.data.token
              let root = this.$store.state.app.campaign.root
              let scheme = this.$store.state.app.campaign.scheme
              let url = scheme + '://' + root + '/staff#/points/link?token=' + token
              this.dialog.claim.qrUrl = url
              this.dialog.claim.qrVisible = true

              console.log(url)

              if (this.socket === null || this.socket.connection.state == 'disconnected') {
                // Enable pusher logging - don't include this in production
                //Pusher.logToConsole = true

                //let channel_id = Math.random().toString(36).substr(2, 9)
                //let csrfToken = document.head.querySelector('meta[name="csrf-token"]').content

                this.socket = new Pusher(window.initConfig.pusher.key, {
                  cluster: window.initConfig.pusher.options.cluster,
                  forceTLS: window.initConfig.pusher.options.encrypted
                })

                let channel = this.socket.subscribe(token)

                this.socket.connection.bind( 'error', function( err ) {
                  this.connectionError = err.error.data.code
                });

                channel.bind('credited', function(data) {
                  that.linkPointsCredited = data.points
                })
              }
            } else {
              //
            }
          })
          .catch(error => {
            // Error
          })
      },
      verifyCustomerCode () {
        this.customerCode.loading = true
        // validation
        this.$validator.validateAll('customerCode').then((valid) => {
          if (! valid) {
            this.customerCode.loading = false
            return false
          } else {
            axios
              .post('/campaign/earn/verify-customer-code', {
                  locale: this.$i18n.locale,
                  campaign: this.$store.state.app.campaign.uuid,
                  code: this.unmask(this.customerCode.code, '###-###-###')
              })
              .then(response => {
                if (response.data.status === 'success') {
                  this.customerCode.code = ''
                  this.customerCode.loading = false
                  this.customerCode.verfied = true
                }
              })
              .catch(err => {
                let errors = err.response.data.errors || {}

                for (let field in errors) {
                  this.$validator.errors.add({
                    field: 'customerCode.' + field,
                    msg: errors[field]
                  })
                }
                this.customerCode.loading = false
              })
          }
        });
      },
      closeMerchantCode () {
        this.dialog.claim.merchant = false
        this.segments = []
        this.merchantCode.code = ''
        this.merchantCode.verfied = false
        this.merchantCode.processed = false
      },
      verifyMerchantCode () {
        this.merchantCode.loading = true
        // validation
        this.$validator.validateAll('merchantCode').then((valid) => {
          if (! valid) {
            this.merchantCode.loading = false
            return false
          } else {
            axios
              .post('/campaign/earn/verify-merchant-code', {
                  locale: this.$i18n.locale,
                  campaign: this.$store.state.app.campaign.uuid,
                  code: this.merchantCode.code
              })
              .then(response => {
                if (response.data.status === 'success') {
                  this.segments = _.toPairs(response.data.segments)
                  this.merchantCode.loading = false
                  this.merchantCode.verfied = true
                  this.merchantCodeVerified.code = this.merchantCode.code
                }
              })
              .catch(err => {
                let errors = err.response.data.errors || {}

                for (let field in errors) {
                  this.$validator.errors.add({
                    field: 'merchantCode.' + field,
                    msg: errors[field]
                  })
                }
                this.merchantCode.loading = false
              })
          }
        });
      },
      processMerchantCode () {
        this.merchantCodeVerified.loading = true
        // validation
        this.$validator.validateAll('merchantCodeVerified').then((valid) => {
          if (! valid) {
            this.merchantCodeVerified.loading = false
            return false
          } else {
            axios
              .post('/campaign/earn/process-merchant-entry', {
                  locale: this.$i18n.locale,
                  campaign: this.$store.state.app.campaign.uuid,
                  code: this.merchantCodeVerified.code,
                  points: this.merchantCodeVerified.points,
                  segments: this.merchantCodeVerified.segments
              })
              .then(response => {
                if (response.data.status === 'success') {
                  this.merchantCodeVerified.loading = false
                  this.merchantCode.processed = true
                }
              })
              .catch(err => {
                let errors = err.response.data.errors || {}

                for (let field in errors) {
                  this.$validator.errors.add({
                    field: 'merchantCodeVerified.' + field,
                    msg: errors[field]
                  })
                }
                this.merchantCodeVerified.loading = false
              })
          }
        });
      }
    },
    watch: {
      'dialog.claim.qr': function (val) {
        if (val === true) {
          this.dialog.claim.qrVisible = false
          this.generateClaimToken()
        }
      }
    },
    computed: {
      campaign () {
        return this.$store.state.app.campaign
      },
      claimOptions() {
        return  [
          { 
            active: (_.indexOf(this.campaign.claimOptions, 'qr') >= 0) ? true : false,
            id: 'qr',
            icon: 'fas fa-qrcode',
            title: this.$t('qr_code'),
            description: this.$t('qr_code_points_info')
          },
          { 
            active: (_.indexOf(this.campaign.claimOptions, 'code') >= 0) ? true : false,
            id: 'code',
            icon: 'textsms',
            title: this.$t('enter_code'),
            description: this.$t('enter_code_points_info')
          },
          { 
            active: (_.indexOf(this.campaign.claimOptions, 'merchant') >= 0) ? true : false,
            id: 'merchant',
            icon: 'fas fa-hand-holding',
            title: this.$t('merchant_enters_code'),
            description: this.$t('merchant_enters_code_points_info')
          },
          { 
            active: (_.indexOf(this.campaign.claimOptions, 'customerNumber') >= 0) ? true : false,
            id: 'customerNumber',
            icon: 'card_giftcard',
            title: this.$t('customer_number'),
            description: this.$t('customer_number_points_info')
          }
        ]
      }
    },
  };
</script>