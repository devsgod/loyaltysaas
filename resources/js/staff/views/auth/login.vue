<template>
  <v-container fluid fill-height>
    <v-layout align-center justify-center row fill-height wrap>

      <v-flex xs10 sm7 md5 lg3 xl2>

        <v-form 
          data-vv-scope="form1"
          :model="form1" 
          lazy-validation
          @submit.prevent="submitForm('form1')"
          autocomplete="off"
          method="post"
          >
          <v-card class="elevation-18 my-4">
            <v-toolbar flat color="transparent">
              <v-toolbar-title>{{ $t('login_head') }}</v-toolbar-title>
            </v-toolbar>
            
            <v-card-text>

            <v-alert
              :value="form1.has_error"
              type="error"
              class="mb-4"
              >
              {{ $t('login_not_recognized') }}
            </v-alert>

            <v-alert
              :value="successResetRedirect"
              type="success"
              class="mb-4"
              >
              {{ $t('reset_email_sent') }}
            </v-alert>

            <v-alert
              :value="successResetUpdateRedirect"
              type="success"
              class="mb-4"
              >
              {{ $t('password_reset_success') }}
            </v-alert>

            <v-text-field
              type="email"
              v-model="form1.email"
              data-vv-name="email"
              v-validate="'required|email'"
              :label="$t('email_address')"
              :error-messages="errors.collect('form1.email')"
              required
              prepend-inner-icon="email"
            ></v-text-field>

            <v-text-field
              type="password"
              v-model="form1.password"
              data-vv-name="password"
              v-validate="'required|min:8|max:24'"
              :label="$t('password')"
              :error-messages="errors.collect('form1.password')"
              prepend-inner-icon="lock"
              required
            ></v-text-field>

            <v-layout align-center justify-end row>
              <v-btn text small :to="{name: 'password.email'}" tabindex="-1" color="grey" class="no-caps">{{ $t('forgot_password') }}</v-btn>
            </v-layout>

            <v-switch
              name="rememberMe"
              v-model="form1.rememberMe"
              :label="$t('remember_me')"
              ></v-switch>

            </v-card-text>
            <v-card-actions>
              <v-btn color="primary" large block :loading="form1.loading" :disabled="form1.loading" type="submit">{{ $t('login') }}</v-btn>
            </v-card-actions>
          </v-card>
        </v-form>
      </v-flex>
    </v-layout>
  </v-container>
</template>
<script>
  export default {
    $_veeValidate: {
      validator: 'new'
    },
    data() {
      return {
        successResetRedirect: false,
        successResetUpdateRedirect: false,
        form1: {
          loading: false,
          email: null,
          password: null,
          rememberMe: true,
          has_error: false,
        }
      }
    },
    created() {
      this.successResetRedirect = this.$route.params.successResetRedirect || false
      this.successResetUpdateRedirect = this.$route.params.successResetUpdateRedirect || false
    },
    methods: {
      submitForm(formName) {
        this[formName].has_error = false
        this[formName].loading = true

        this.$validator.validateAll(formName).then((valid) => {
          if (valid) {
            this.login(formName)
          } else {
            this[formName].loading = false
            return false;
          }
        });
      },
      login(formName) {
        // get the redirect object
        var redirect = this.$auth.redirect()
        var app = this[formName]

        this.$auth.login({
          redirect: { name: redirect ? redirect.from.name : 'dashboard',  query: redirect ? redirect.from.query : null },
          rememberMe: app.rememberMe,
          fetchUser: true,
          params: {
            locale: this.$i18n.locale,
            uuid: this.$store.state.app.campaign.uuid,
            email: app.email,
            password: app.password,
            remember: app.rememberMe
          },
          success: function() {
          },
          error: function() {
            app.has_error = true
            app.loading = false
          }
        })
      }
    },
    computed: {
      campaign () {
        return this.$store.state.app.campaign
      }
    },
  }
</script>
<style scoped>
</style>