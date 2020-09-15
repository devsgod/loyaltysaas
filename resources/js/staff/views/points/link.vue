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

          <div v-if="validToken === true && credited === false">

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
              data-vv-scope="creditPoints"
              :model="creditPoints" 
              @submit.prevent="creditCustomer"
              autocomplete="off"
              method="post"
              accept-charset="UTF-8" 
            >
              <v-card-text>
                <p class="body-1">You can credit the customer's points below.</p>

                  <v-text-field
                    :disabled="creditPoints.code !== false"
                    type="number"
                    v-model="creditPoints.points"
                    outline
                    label="Points to be credited"
                    prepend-inner-icon="toll"
                    data-vv-name="points"
                    v-validate="'required|numeric|max_value:100000'"
                    :error-messages="errors.collect('creditPoints.points')"
                  ></v-text-field>

                  <v-autocomplete
                    :disabled="creditPoints.code !== false"
                    v-if="Object.keys(segments).length > 0"
                    v-model="creditPoints.segments"
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
                <v-btn color="primary" :loading="creditPoints.loading" :disabled="creditPoints.code !== false" type="submit">Credit points</v-btn>
              </v-card-actions>

            </v-form>
          </div>

          <div v-if="credited === true">
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
              <p class="body-1">The customer has been successfully credited.</p>

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
        credited: false,
        segments: [],
        creditPoints: {
          loading: false,
          points: null,
          segments: [],
          code: false,
        },
      }
    },
    methods: {
      creditCustomer () {
        this.creditPoints.loading = true
        // validation
        this.$validator.validateAll('creditPoints').then((valid) => {
          if (! valid) {
            this.creditPoints.loading = false
            return false
          } else {
            axios
              .post('/staff/points/push/credit', {
                  locale: this.$i18n.locale,
                  campaign: this.$store.state.app.campaign.uuid,
                  token: this.token,
                  points: this.creditPoints.points,
                  segments: this.creditPoints.segments
              })
              .then(response => {
                if (response.data.status === 'success') {
                  this.customer.points += parseInt(this.creditPoints.points)
                  this.credited = true
                }
                this.creditPoints.loading = false
              })
              .catch(err => {

                let errors = err.response.data.errors || {}

                for (let field in errors) {
                  this.$validator.errors.add({
                    field: 'creditPoints.' + field,
                    msg: errors[field]
                  })
                }
                this.creditPoints.loading = false
              })
          }
        });
      }
    },
    created () {
      this.token = this.$route.query.token

      axios
        .post('/staff/points/validate-link-token', { campaign: this.$store.state.app.campaign.uuid, token: this.token })
        .then(response => {
          this.validToken = response.data.tokenIsValid
          this.customer = response.data.customer
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