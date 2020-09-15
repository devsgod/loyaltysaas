<template>
  <v-container fluid fill-height>
    <v-layout align-center justify-center row fill-height wrap>

      <v-flex xs10 sm8 md6 lg4 xl3>
        <v-card class="elevation-18">

          <v-card-text v-if="validToken === null">
            <v-progress-linear
              indeterminate
              color="primary"
            ></v-progress-linear>
          </v-card-text>

          <div  v-if="validToken === false">
            <v-card-text>
              <p class="title">Token is not valid or has expired.</p>
            </v-card-text>
            <v-card-actions>
              <v-spacer></v-spacer>
              <v-btn color="primary" to="{name: 'dashboard'}">Dashboard</v-btn>
            </v-card-actions>
          </div>

          <div v-if="validToken === true && redeemed === false">
            <v-list three-line>
              <v-list-item>
                <v-list-item-avatar class="mt-6 ml-2">
                  <v-avatar
                    class="ma-2"
                    :size="56"
                    color="grey"
                  >
                    <v-img :src="customer.avatar"></v-img>
                  </v-avatar>
                </v-list-item-avatar>

                <v-list-item-content>
                  <v-list-item-title v-html="customer.name"></v-list-item-title>
                  <v-list-item-subtitle v-html="customer.number"></v-list-item-subtitle>
                  <v-list-item-subtitle><v-icon size="17">toll</v-icon> <span v-html="new Intl.NumberFormat(this.$auth.user().locale.replace('_', '-')).format(customer.points)"></span></v-list-item-subtitle>
                </v-list-item-content>
              </v-list-item>
            </v-list>

            <v-form 
              data-vv-scope="redeemReward"
              :model="redeemReward" 
              @submit.prevent="creditCustomer"
              autocomplete="off"
              method="post"
              accept-charset="UTF-8" 
            >
              <v-card-text>
                <p class="body-1">You can redeem the reward below.</p>

                  <v-autocomplete
                    :disabled="redeemReward.code !== false"
                    v-model="redeemReward.reward"
                    :items="rewards"
                    item-value="0" 
                    item-text="1"
                    label="Reward"
                    hide-no-data
                    hide-selected
                    prepend-inner-icon="fas fa-gift"
                    :error-messages="errors.collect('redeemReward.reward')"
                  ></v-autocomplete>

                  <v-autocomplete
                    :disabled="redeemReward.code !== false"
                    v-if="Object.keys(segments).length > 0"
                    v-model="redeemReward.segments"
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
                <v-btn color="primary" :loading="redeemReward.loading" :disabled="redeemReward.code !== false" type="submit">Redeem reward</v-btn>
              </v-card-actions>

            </v-form>
          </div>

          <div v-if="redeemed === true">
            <v-layout row align-start>
              <v-list-item>
                <v-list three-line>
                  <v-list-item>
                    <v-list-item-avatar class="mt-6 ml-2">
                      <v-avatar
                        class="ma-2"
                        :size="56"
                        color="grey"
                      >
                        <v-img :src="customer.avatar"></v-img>
                      </v-avatar>
                    </v-list-item-avatar>
                    <v-list-item-content>
                      <v-list-item-title v-html="customer.name"></v-list-item-title>
                      <v-list-item-subtitle v-html="customer.number"></v-list-item-subtitle>
                      <v-list-item-subtitle><v-icon size="17">toll</v-icon> <span v-html="new Intl.NumberFormat(this.$auth.user().locale.replace('_', '-')).format(customer.points)"></span></v-list-item-subtitle>
                    </v-list-item-content>
                  </v-list-item>
                </v-list>
              </v-list-item>
            </v-layout>

            <v-card-text>
              <p class="body-1">The reward has been redeemed.</p>

             </v-card-text>
            <v-card-actions>
              <v-spacer></v-spacer>
              <v-btn color="primary" to="{name: 'dashboard'}">Dashboard</v-btn>
            </v-card-actions>

          </div>

          </v-card>

        </v-flex>

      </v-layout>
    </v-container>
</template>
<script>
  export default {
    $_veeValidate: {
      validator: 'new'
    },
    data () {
      return {
        token: null,
        validToken: null,
        customer: null,
        redeemed: false,
        segments: [],
        rewards: [],
        redeemReward: {
          loading: false,
          reward: null,
          segments: [],
          code: false,
        },
      }
    },
    methods: {
      creditCustomer () {
        this.redeemReward.loading = true
        // validation
        this.$validator.validateAll('redeemReward').then((valid) => {
          if (! valid) {
            this.redeemReward.loading = false
            return false
          } else {
            axios
              .post('/staff/rewards/push/redemption', {
                  locale: this.$i18n.locale,
                  campaign: this.$store.state.app.campaign.uuid,
                  token: this.token,
                  reward: this.redeemReward.reward,
                  segments: this.redeemReward.segments
              })
              .then(response => {
                if (response.data.status === 'success') {
                  this.customer.points = response.data.points
                  this.redeemed = true
                }
                this.redeemReward.loading = false
              })
              .catch(err => {

                let errors = err.response.data.errors || {}

                for (let field in errors) {
                  this.$validator.errors.add({
                    field: 'redeemReward.' + field,
                    msg: errors[field]
                  })
                }
                this.redeemReward.loading = false
              })
          }
        });
      }
    },
    created () {
      this.token = this.$route.query.token

      axios
        .get('/staff/rewards', { params: { locale: this.$i18n.locale, campaign: this.$store.state.app.campaign.uuid }})
        .then(response => {
          this.rewards = _.toPairs(response.data)

          axios
            .post('/staff/rewards/validate-link-token', { campaign: this.$store.state.app.campaign.uuid, token: this.token })
            .then(response => {
              this.validToken = response.data.tokenIsValid
              this.customer = response.data.customer
              this.redeemReward.reward = response.data.reward
            })

        })

      axios
        .get('/staff/segments', { params: { locale: this.$i18n.locale, campaign: this.$store.state.app.campaign.uuid }})
        .then(response => {
          this.segments = _.toPairs(response.data)
        })

    },
    computed: {
      campaign () {
        return this.$store.state.app.campaign
      }
    }
  };
</script>
<style scoped>
</style>