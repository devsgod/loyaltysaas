<template>
  <div class="pb-5">
    <v-container grid-list-lg fluid class="pa-0">
      <v-layout row wrap>
        <v-flex class="py-0" xs12 :style="{'background-color': campaign.theme.secondaryColor, 'color': campaign.theme.secondaryTextColor}">
          <v-img
            :src="campaign.rewards.headerImg || ''"
            :height="campaign.rewards.headerHeight"
          >
            <v-overlay
              absolute
              :color="campaign.theme.secondaryColor"
              :opacity="campaign.rewards.headerOpacity"
              z-index="1"
            >
            </v-overlay>
            <v-container fill-height>
              <v-layout row wrap align-center>
                <v-flex xs12 style="z-index: 2">
                  <h1 class="display-1 mb-2" v-html="campaign.rewards.headerTitle"></h1>
                  <div v-html="campaign.rewards.headerContent"></div>
                </v-flex>
              </v-layout>
            </v-container>
            <template v-slot:placeholder>
              <v-layout
                fill-height
                align-center
                justify-center
                ma-0
                v-if="campaign.rewards.headerImg"
              >
                <v-progress-circular indeterminate :style="{'color': campaign.theme.secondaryTextColor}"></v-progress-circular>
              </v-layout>
            </template>
          </v-img>
        </v-flex>

        <vue-gallery :images="gallery_images" :index="selected_gallery_image" @close="selected_gallery_image = null" :ref="'gallery'"></vue-gallery>

        <v-flex xs12>
          <v-container grid-list-xl>
            <v-layout row wrap>
              <v-flex xs12 sm6 lg4 xl3 v-for="(reward, reward_index) in campaign.rewards.list" :key="'campaign_rewards_' + reward_index" class="mt-2">
                <v-card style="height: 100%">
                  <v-img v-if="reward.images.length > 0"
                    :src="reward.images[0].thumb"
                    :key="'card_reward_' + reward_index + '_images_sub_0'"
                    @click="gallery_images = reward.images; selected_gallery_image = 0"
                    :aspect-ratio="campaign.rewards.imageRatio"
                    style="cursor: pointer;"
                  >
                    <template v-slot:placeholder>
                      <v-layout
                        fill-height
                        align-center
                        justify-center
                        ma-0
                      >
                        <v-progress-circular indeterminate :style="{'color': campaign.theme.textColor}"></v-progress-circular>
                      </v-layout>
                    </template>
                  </v-img>
                  <v-container 
                    class="pa-3"
                    grid-list-lg
                    fluid 
                    v-if="reward.images.length > 1"
                  >
                    <v-layout row wrap>
                      <v-flex
                        v-for="(image, index) in reward.images"
                        v-if="index > 0"
                        :key="'campaign_reward_images_sub_' + index"
                        @click="gallery_images = reward.images; selected_gallery_image = index"
                        xs3
                        d-flex
                        :class="{'xs12': reward.image_grid_size === 1, 'xs6': reward.image_grid_size === 2, 'xs4': reward.image_grid_size === 3, 'xs3': reward.image_grid_size === 4, 'xs2': reward.image_grid_size === 6}"
                        >
                        <v-card flat tile class="d-flex">
                          <v-img
                            :src="image.thumb"
                            aspect-ratio="1"
                            style="cursor: pointer;"
                          >
                            <template v-slot:placeholder>
                              <v-layout
                                fill-height
                                align-center
                                justify-center
                                ma-0
                              >
                                <v-progress-circular indeterminate :style="{'color': campaign.theme.textColor}"></v-progress-circular>
                              </v-layout>
                            </template>
                          </v-img>
                        </v-card>
                      </v-flex>
                    </v-layout>
                  </v-container>
                  <v-card-title primary-title class="pt-0">
                    <div>
                      <div class="title mb-2" v-html="reward.title"></div>
                      <div style="opacity:0.75" class="subtitle-2 mb-1"><v-icon size="15" style="position: relative; top:-0.5px;" :style="{'color': campaign.theme.textColor}">toll</v-icon> <span v-html="$t('n_points', {points: formatNumber(reward.points)})"></span></div>
                      <div style="opacity:0.75" class="subtitle-2" v-if="typeof reward.expires !== 'undefined' && reward.expires !== null && reward.expiresInMonths <= 1"><v-icon size="15" style="position: relative; top:-0.5px;" :style="{'color': campaign.theme.textColor}">date_range</v-icon> <span v-html="'Expires ' + getDate(reward.expires)"></span></div>
                    </div>
                  </v-card-title>
                  <v-card-actions class="mx-2 mb-2" v-if="Object.keys(campaign.redeemOptions).length > 0">
                    <v-btn color="light" v-if="! $auth.check()" :to="{name: 'login'}" block large>{{ $t('log_in_to_redeem') }}</v-btn>
                    <v-menu 
                      v-if="$auth.check()"
                      bottom
                      transition="slide-y-transition"
                    >
                      <template v-slot:activator="{ on }">
                        <v-btn
                          :disabled="((points / reward.points) * 100) < 100"
                          :loading="points === null"
                          block 
                          large
                          v-on="on"
                        >
                          <span v-if="points >= reward.points">{{ $t('redeem') }}</span>
                          <span v-if="points < reward.points">{{ $t('you_need_n_more_points', {points: formatNumber(reward.points - points)}) }}</span>
                        </v-btn>
                      </template>
                      <v-list dense three-line subheader>
                        <v-subheader>
                          {{ $t('redeem_this_reward') }}
                        </v-subheader>
                        <div v-for="(item, index) in redeemOptions" :key="'campaign_reward_redeem_' + index" :class="{'mb-2': index == Object.keys(campaign.redeemOptions).length - 1}">
                          <v-list-item
                            v-if="item.active"
                            @click="selectedReward = reward.uuid; dialog.redeem[item.id] = ($auth.check()) ? true : false; dialog.authRequired = ($auth.check()) ? false : true;"
                            three-line
                          >
                            <v-list-item-avatar>
                              <v-icon>{{ item.icon }}</v-icon>
                            </v-list-item-avatar>
                            <v-list-item-content>
                              <v-list-item-title>{{ item.title }}</v-list-item-title>
                              <v-list-item-subtitle>{{ item.description }}</v-list-item-subtitle>
                            </v-list-item-content>
                          </v-list-item>
                          <v-divider inset class="grey lighten-2 my-2" v-if="index < Object.keys(campaign.redeemOptions).length - 1"></v-divider>
                        </div>
                      </v-list>
                    </v-menu>
                  </v-card-actions>
                  <v-card-text class="pt-0 mt-0 pb-1 mb-0">
                    <div v-html="reward.description" class="body-1"></div>
                  </v-card-text>
                </v-card>
              </v-flex>
            </v-layout>
          </v-container>
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

    <v-dialog v-model="dialog.redeem.qr" persistent max-width="320">
      <v-card>
        <v-card-title class="headline">{{ $t('show_qr_to_merchant') }}</v-card-title>
        <v-card-text v-if="dialog.redeem.qrVisible && connectionError === false && rewardRedeemed === null">
          <p class="body-1">{{ $t('keep_dialog_open_until_confirmation') }}</p>
          <qr-code 
            class="qr-100"
            :text="dialog.redeem.qrUrl"
            color="#000000"
            bg-color="transparent" 
            error-level="Q"
          >
          </qr-code>
        </v-card-text>
        <v-card-text v-if="rewardRedeemed !== null">
          <p class="body-1" v-html="$t('reward_successfully_redeemed', {rewardRedeemed: '<strong>' + rewardRedeemed + '</strong>'})"></p>
          <p class="body-1" v-html="$t('find_rewards_history_tab')"></p>
        </v-card-text>
        <v-card-text v-if="connectionError !== false">
          <p class="body-1">A connection error has occured ({{ connectionError }}). Please close this dialog and try again.</p>
        </v-card-text>
        <v-card-text v-if="! dialog.redeem.qrVisible">
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

    <!-- Claim - Merchant Enters Code --------------------------------------------------------------------------------------------------------------------------------- -->

    <v-dialog v-model="dialog.redeem.merchant" persistent max-width="320">
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
            :type="dialog.redeem.showMerchantCode ? 'text' : 'password'"
            :append-icon="dialog.redeem.showMerchantCode ? 'visibility' : 'visibility_off'"
            @click:append="dialog.redeem.showMerchantCode = !dialog.redeem.showMerchantCode"
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
            <p class="body-1" v-html="$t('code_correct_select_reward')"></p>
              <v-autocomplete
                v-model="merchantCodeVerified.reward"
                :items="rewards"
                item-value="0" 
                item-text="1"
                :label="$t('reward')"
                :data-vv-as="$t('reward')"
                data-vv-name="reward"
                hide-no-data
                hide-selected
                prepend-inner-icon="fas fa-gift"
                v-validate="'required'"
                :error-messages="errors.collect('merchantCodeVerified.reward')"
              ></v-autocomplete>
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
            <v-btn color="primary" :loading="merchantCodeVerified.loading" :disabled="merchantCode.processed" type="submit">{{ $t('redeem_reward') }}</v-btn>
            <v-btn color="secondary" text @click="closeMerchantCode">{{ $t('close') }}</v-btn>
          </v-card-actions>
        </v-form>
        <div v-if="merchantCode.verfied && merchantCode.processed">
          <v-card-text>
          <p class="body-1" v-html="$t('reward_successfully_redeemed', {rewardRedeemed: '<strong>' + redeemedReward + '</strong>'})"></p>
          <p class="body-1" v-html="$t('find_rewards_history_tab')"></p>
           </v-card-text>
          <v-card-actions>
            <v-spacer></v-spacer>
            <v-btn color="secondary" text @click="closeMerchantCode">{{ $t('close') }}</v-btn>
          </v-card-actions>
        </div>
      </v-card>
    </v-dialog>

    <!-- Claim - Provide Customer Number --------------------------------------------------------------------------------------------------------------------------------- -->

    <v-dialog v-model="dialog.redeem.customerNumber" persistent max-width="320">
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
          <v-btn color="secondary" text @click="dialog.redeem.customerNumber = false">{{ $t('close') }}</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>
<script>
  import {copyElById} from '../../utils/helpers';

  export default {
    $_veeValidate: {
      validator: 'new'
    },
    mounted () {
      let locale = Intl.DateTimeFormat().resolvedOptions().locale || 'en'
      locale = (this.$auth.check()) ? this.$auth.user().locale : locale
      this.locale = locale

      moment.locale(this.locale.substr(0,2))

      axios
        .get('/campaign/points', { params: { locale: this.$i18n.locale, uuid: this.$store.state.app.campaign.uuid }})
        .then(response => {
          this.points = response.data
        })
    },
    data () {
      return {
        locale: 'en',
        points: null,
        segments: [],
        rewards: [],
        socket: null,
        connectionError: false,
        selectedReward: null,
        rewardRedeemed: null,
        redeemedReward: null,
        gallery_images: [],
        selected_gallery_image: null,
        dialog: {
          authRequired: false,
          redeem: {
            qr: false,
            qrVisible: false,
            qrUrl: '',
            merchant: false,
            showMerchantCode: false,
            customerNumber: false
          }
        },
        merchantCode: {
          loading: false,
          verfied: false,
          processed: false,
          code: ''
        },
        merchantCodeVerified: {
          loading: false,
          reward: '',
          code: ''
        }
      }
    },
    methods: {
      copyElById,
      formatNumber (number) {
        return new Intl.NumberFormat(this.locale.replace('_', '-')).format(number)
      },
      getDate(date) {
        return moment(date).format("ll");
      },
      closeQrDialog () {
        this.socket.disconnect()
        this.dialog.redeem.qr = false
        this.connectionError = false
      },
      generateRedeemToken () {
        axios
          .post('/campaign/get-redeem-reward-token', {
              locale: this.$i18n.locale,
              campaign: this.$store.state.app.campaign.uuid,
              reward: this.selectedReward
          })
          .then(response => {
            if (response.data.status === 'success') {
              let that = this
              let token = response.data.token
              let root = this.$store.state.app.campaign.root
              let scheme = this.$store.state.app.campaign.scheme
              let url = scheme + '://' + root + '/staff#/rewards/link?token=' + token
              this.dialog.redeem.qrUrl = url
              this.dialog.redeem.qrVisible = true

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

                channel.bind('redeemed', function(data) {
                  that.rewardRedeemed = data.reward
                  that.points = data.points
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
      closeMerchantCode () {
        this.dialog.redeem.merchant = false
        this.segments = []
        this.rewards = []
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
              .post('/campaign/reward/verify-merchant-code', {
                  locale: this.$i18n.locale,
                  campaign: this.$store.state.app.campaign.uuid,
                  code: this.merchantCode.code
              })
              .then(response => {
                if (response.data.status === 'success') {
                  this.rewards = _.toPairs(response.data.rewards)
                  this.segments = _.toPairs(response.data.segments)
                  this.merchantCode.loading = false
                  this.merchantCode.verfied = true
                  this.merchantCodeVerified.code = this.merchantCode.code
                  this.merchantCodeVerified.reward = this.selectedReward
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
        })
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
              .post('/campaign/reward/process-merchant-entry', {
                  locale: this.$i18n.locale,
                  campaign: this.$store.state.app.campaign.uuid,
                  code: this.merchantCodeVerified.code,
                  reward: this.merchantCodeVerified.reward,
                  segments: this.merchantCodeVerified.segments
              })
              .then(response => {
                if (response.data.status === 'success') {
                  this.redeemedReward = response.data.reward
                  this.points = response.data.points
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
        })
      }
    },
    watch: {
      'dialog.redeem.qr': function (val) {
        if (val === true) {
          this.dialog.redeem.qrVisible = false
          this.generateRedeemToken()
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
            title: this.$t('qr_code'),
            description: this.$t('qr_code_info')
          },
          { 
            active: (_.indexOf(this.campaign.redeemOptions, 'merchant') >= 0) ? true : false,
            id: 'merchant',
            icon: 'fas fa-hand-holding',
            title: this.$t('merchant_enters_code'),
            description: this.$t('merchant_enters_code_info')
          },
          { 
            active: (_.indexOf(this.campaign.redeemOptions, 'customerNumber') >= 0) ? true : false,
            id: 'customerNumber',
            icon: 'card_giftcard',
            title: this.$t('customer_number'),
            description: this.$t('customer_number_info')
          }
        ]
      }
    },
  };
</script>