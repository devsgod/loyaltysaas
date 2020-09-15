<template>
  <div class="pb-5">
    <v-container grid-list-lg fluid class="pa-0">
      <v-layout row wrap>
        <v-flex class="py-0" xs12 :style="{'background-color': campaign.theme.secondaryColor, 'color': campaign.theme.secondaryTextColor}">
            <v-container fill-height>
              <v-layout row wrap align-center>
                <v-flex xs12 style="z-index: 2">
                <div class="mt-3 mb-7 pa-0">
                  <h1 class="display-1 mb-1">Give customers points</h1>
                  <div>Below are the options that the customer has to earn points.</div>
                </div>
              </v-flex>
            </v-layout>
          </v-container>
        </v-flex>
      </v-layout>
    </v-container>

    <v-container grid-list-lg class="mt-7">
      <v-layout row wrap>

        <v-flex d-flex xs12 sm6 sm6 lg3 v-for="(item, index) in claimOptions" :key="index" v-if="item.active">

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
            <li>This opens a webpage where you can give points.</li>
          </ol>

        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="secondary" text @click="dialog.claim.qr = false">Close</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Claim - Enter Code --------------------------------------------------------------------------------------------------------------------------------- -->

    <v-dialog v-model="dialog.claim.code" persistent max-width="380">
      <v-card>

        <v-tabs 
          v-model="customerCode.tab" 
          slider-color="grey darken-3"
          color="grey darken-3"
          >
          <v-tab :href="'#generate'">
            Generate code
          </v-tab>
          <v-tab :href="'#active'">
            Active codes
          </v-tab>
        </v-tabs>

        <v-tabs-items v-model="customerCode.tab" :touchless="false" class="mx-2">
          <v-tab-item :value="'generate'">

            <v-form 
              data-vv-scope="customerCode"
              :model="customerCode" 
              @submit.prevent="generateCustomerCode"
              autocomplete="off"
              method="post"
              accept-charset="UTF-8" 
            >
              <v-card-text>
                <p class="body-1">Generate a code you can give to the customer. This code can be used only once.</p>

                  <v-text-field
                    :disabled="customerCode.code !== false"
                    type="number"
                    v-model="customerCode.points"
                    outline
                    label="Points to be credited"
                    prepend-inner-icon="toll"
                    data-vv-name="points"
                    v-validate="'required|numeric|max_value:100000'"
                    :error-messages="errors.collect('customerCode.points')"
                  ></v-text-field>

                  <v-select
                    :disabled="customerCode.code !== false"
                    v-model="customerCode.expiresSelected"
                    :items="expires"
                    :return-object="false"
                    hide-no-data
                    prepend-inner-icon="calendar_today"
                  ></v-select>

                  <v-autocomplete
                    :disabled="customerCode.code !== false"
                    v-if="Object.keys(segments).length > 0"
                    v-model="customerCode.segments"
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

                  <v-text-field
                    :key="customerCodeCounter"
                    v-if="customerCode.code !== false"
                    type="text"
                    outlined
                    readonly
                    class="title mt-3"
                    id="generatedCustomerCode"
                    v-model="customerCode.code"
                    append-icon="filter_none"
                    @click:append="copyElById('generatedCustomerCode')"
                    required
                  ></v-text-field>

                  <a href="javascript:void(0);" v-if="customerCode.code !== false" @click="customerCode.code = false">Generate a new code</a>

               </v-card-text>
              <v-card-actions>
                <v-spacer></v-spacer>
                <v-btn color="primary" :loading="customerCode.loading" :disabled="customerCode.code !== false" type="submit">Generate</v-btn>
                <v-btn color="secondary" text @click="dialog.claim.code = false">Close</v-btn>
              </v-card-actions>

            </v-form>
          </v-tab-item>
          <v-tab-item :value="'active'">

              <v-card-text v-if="Object.keys(activeCustomerCodes).length == 0">
                <p class="body-1">There are no active codes.</p>
              </v-card-text>

              <v-card-text v-if="Object.keys(activeCustomerCodes).length > 0">
                <p class="body-1">These codes are not expired and have not been claimed by a customer.</p>

                <v-simple-table>
                  <thead>
                    <tr>
                      <th class="text-xs-left">Code</th>
                      <th class="text-xs-right">Points</th>
                      <th class="text-xs-left">Expires</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="item in activeCustomerCodes" :key="item.uuid">
                      <td>{{ item.formatted_code }}</td>
                      <td class="text-xs-right">{{ item.points }}</td>
                      <td>{{ getExpirationFromNow(item.expires_at) }}</td>
                    </tr>
                  </tbody>
                </v-simple-table>

              </v-card-text>
              <v-card-actions>
                <v-spacer></v-spacer>
                <v-btn color="secondary" text @click="dialog.claim.code = false">Close</v-btn>
              </v-card-actions>

          </v-tab-item>
        </v-tabs-items>
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
        <v-card-title class="headline">Credit a customer with number</v-card-title>
          <v-form 
            data-vv-scope="customerNumber"
            :model="customerNumber" 
            @submit.prevent="creditCustomer"
            autocomplete="off"
            method="post"
            accept-charset="UTF-8" 
          >
            <v-card-text>
              <p class="body-1" v-if="customerNumber.credited === false">Enter the amount of points and a customer number to credit the customer.</p>
              <p class="body-1" v-if="customerNumber.credited !== false">Customer successfully credited.</p>

              <div class="mb-3">
                <a href="javascript:void(0);" v-if="customerNumber.credited !== false" @click="customerNumber.credited = false">Add another credit</a>
              </div>

              <v-text-field
                :disabled="customerNumber.credited !== false"
                type="number"
                v-model="customerNumber.points"
                outline
                label="Points to be credited"
                prepend-inner-icon="toll"
                data-vv-name="points"
                v-validate="'required|numeric|max_value:100000'"
                :error-messages="errors.collect('customerNumber.points')"
              ></v-text-field>

              <v-text-field
                :disabled="customerNumber.credited !== false"
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
                :disabled="customerNumber.credited !== false"
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
            <v-btn color="primary" :loading="customerNumber.loading" :disabled="customerNumber.credited !== false" type="submit">Credit customer</v-btn>
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
        expires: [
          {value: 'hour', text: 'Expires in one hour'},
          {value: 'day', text: 'Expires in one day'},
          {value: 'week', text: 'Expires expires in one week'},
          {value: 'month', text: 'Expires expires in one month'}
        ],
        customerCode: {
          tab: 'generate',
          loading: false,
          points: null,
          segments: [],
          code: false,
          expiresSelected: 'week',
        },
        activeCustomerCodes: [],
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
          credited: false,
          points: null,
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

      this.getActiveCustomerCodes()
      this.getActiveMerchantCode()
    },
    created () {
      moment.locale(this.$auth.user().locale)
    },
    methods: {
      copyElById,
      unmask,
      generateCustomerCode () {
        this.customerCode.loading = true
        // validation
        this.$validator.validateAll('customerCode').then((valid) => {
          if (! valid) {
            this.customerCode.loading = false
            return false
          } else {
            axios
              .post('/staff/points/customer/generate-code', {
                  locale: this.$i18n.locale,
                  campaign: this.$store.state.app.campaign.uuid,
                  points: this.customerCode.points,
                  expires: this.customerCode.expiresSelected,
                  segments: this.customerCode.segments
              })
              .then(response => {
                if (response.data.status === 'success') {
                  this.customerCode.code = response.data.code
                  this.customerCode.loading = false
                  this.getActiveCustomerCodes()
                }
              })
              .catch(error => {
                // Error
              })
          }
        });
      },
      getActiveCustomerCodes () {
        // Get currently active customer codes
        axios
          .get('/staff/points/customer/active-codes', { params: { locale: this.$i18n.locale, campaign: this.$store.state.app.campaign.uuid }})
          .then(response => {
            this.activeCustomerCodes = response.data
          })
      },
      generateMerchantCode () {
        this.merchant.loading = true
        // validation
        this.$validator.validateAll('merchant').then((valid) => {
          if (! valid) {
            this.merchant.loading = false
            return false
          } else {
            axios
              .post('/staff/points/merchant/generate-code', {
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
          .get('/staff/points/merchant/active-code', { params: { locale: this.$i18n.locale, campaign: this.$store.state.app.campaign.uuid }})
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
              .post('/staff/points/customer/credit', {
                  locale: this.$i18n.locale,
                  campaign: this.$store.state.app.campaign.uuid,
                  points: this.customerNumber.points,
                  number: this.unmask(this.customerNumber.number, '###-###-###'),
                  segments: this.customerNumber.segments
              })
              .then(response => {
                if (response.data.status === 'success') {
                  this.customerNumber.credited = true
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
      claimOptions () {
        return  [
          { 
            active: (_.indexOf(this.campaign.claimOptions, 'qr') >= 0) ? true : false,
            id: 'qr',
            icon: 'fas fa-qrcode',
            title: 'QR Code',
            description: 'The customer displays a QR that can be scanned by a staff member.'
          },
          { 
            active: (_.indexOf(this.campaign.claimOptions, 'code') >= 0) ? true : false,
            id: 'code',
            icon: 'textsms',
            title: 'Enter Code',
            description: 'Generate a code that you can give to the customer.'
          },
          { 
            active: (_.indexOf(this.campaign.claimOptions, 'merchant') >= 0) ? true : false,
            id: 'merchant',
            icon: 'fas fa-hand-holding',
            title: 'Merchant Enters Code',
            description: 'Generate a code that a staff member can enter on the customer\'s phone.'
          },
          { 
            active: (_.indexOf(this.campaign.claimOptions, 'customerNumber') >= 0) ? true : false,
            id: 'customerNumber',
            icon: 'card_giftcard',
            title: 'Customer Number',
            description: 'Add points to a customer account using a customer number.'
          }
        ]
      }
    }
  }
</script>
<style scoped>
</style>