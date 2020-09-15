<template>
  <v-container fluid fill-height>
    <v-layout align-center justify-center>
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
              <v-toolbar-title>{{ $t('reset_password_head') }}</v-toolbar-title>
            </v-toolbar>
            <v-card-text>

              <v-alert
                :value="form1.has_error"
                type="error"
                class="mb-4"
                >
                {{ errorMsg }}
              </v-alert>

              <p class="body-1">{{ $t('reset_password_info') }}</p>

            <v-text-field
              type="email"
              v-model="form1.email"
              data-vv-name="email"
              v-validate="'required|email'"
              :label="$t('enter_email')"
              :error-messages="errors.collect('form1.email')"
              required
              prepend-inner-icon="email"
              ></v-text-field>

            </v-card-text>

            <v-card-actions>
              <v-btn :color="$store.getters.app.color_name" large block :loading="form1.loading" :disabled="form1.loading" type="submit">{{ $t('send_reset_link') }}</v-btn>
            </v-card-actions>
          </v-card>
          <v-btn @click="toLogin" :disabled="form1.loading" large block text class="no-caps"><v-icon size="16" class="mr-1">arrow_back</v-icon> {{ $t('back_to_login') }}</v-btn>
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
        errorMsg: '',
        form1: {
          loading: false,
          email: null,
          has_error: false,
        }
      }
    },
    methods: {
      toHome() {
        this.$router.push({name: 'home'})
      },
      toLogin() {
        this.$router.push({name: 'login'})
      },
      submitForm(formName) {
        this[formName].has_error = false
        this[formName].loading = true

        this.$validator.validateAll(formName).then((valid) => {
          if (valid) {
            this.reset(formName);
          } else {
            this[formName].loading = false
            return false;
          }
        });
      },
      reset(formName) {
        var app = this[formName]

        axios
          .post('/auth/password/reset', {
            locale: this.$i18n.locale,
            email: app.email
          })
          .then(response => {
            if (response.data.status === 'success') {
              this.$router.push({name: 'login', params: {successResetRedirect: true}})
            } else if (typeof response.data.error !== 'undefined') {
              app.has_error = true
              this.errorMsg = response.data.error
            }
          })
          .catch(error => {
            app.has_error = true
          })
          .finally(() => app.loading = false)
      }
    }
  }
</script>
<style scoped>
</style>