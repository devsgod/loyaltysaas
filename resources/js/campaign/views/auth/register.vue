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
              <v-toolbar-title>{{ $t('register_head') }}</v-toolbar-title>
            </v-toolbar>
            <v-card-text>
              <v-alert
                :value="form1.has_error && !form1.success"
                type="error"
                class="mb-4"
              >
                <span v-if="form1.error == 'registration_validation_error'">{{ $t('server_error') }}</span>
                <span v-else-if="form1.error == 'limitation_reached'">{{ $t('campaign_customer_sign_up_limit') }}</span>
                <span v-else>{{ $t('correct_errors') }}</span>
              </v-alert>
              <v-text-field
                v-model="form1.name"
                data-vv-name="name"
                v-validate="'required|min:2|max:32'"
                :label="$t('enter_your_name')"
                :data-vv-as="$t('name')"
                :error-messages="errors.collect('form1.name')"
                required
                prepend-inner-icon="person"
              ></v-text-field>
              <v-text-field
                type="email"
                v-model="form1.email"
                data-vv-name="email"
                v-validate="'required|max:64|email'"
                :label="$t('enter_email')"
                :data-vv-as="$t('email_address')"
                :error-messages="errors.collect('form1.email')"
                required
                prepend-inner-icon="email"
              ></v-text-field>
              <v-text-field
                v-model="form1.password"
                data-vv-name="password"
                v-validate="'required|min:8|max:24'"
                :label="$t('enter_password')"
                :data-vv-as="$t('password')"
                :error-messages="errors.collect('form1.password')"
                :type="show_password ? 'text' : 'password'"
                :append-icon="show_password ? 'visibility' : 'visibility_off'"
                @click:append="show_password = !show_password"
                required
                prepend-inner-icon="lock"
              ></v-text-field>
              <v-checkbox 
                type="checkbox"
                v-model="form1.terms"
                data-vv-name="terms"
                v-validate="'required'"
                :label="$t('agree_to_terms')"
                :data-vv-as="$t('terms')"
                :error-messages="errors.collect('form1.terms')"
                value="1"
                required
              >
                  <template v-slot:label>
                    <div>
                      {{ $t('i_agree_to') }}
                      <v-tooltip bottom>
                        <template v-slot:activator="{ on }">
                          <a
                            target="_blank"
                            :href="$router.resolve({name: 'legal'}).href"
                            @click.stop
                            v-on="on"
                          >
                            {{ $t('terms_and_policy').toLowerCase() }}
                          </a>
                        </template>
                        {{ $t('opens_in_new_window') }}
                      </v-tooltip>
                    </div>
                  </template>
              </v-checkbox>
            </v-card-text>
            <v-card-actions>
              <v-btn color="primary" large block :loading="form1.loading" :disabled="form1.loading" type="submit" class="ml-0">{{ $t('create_account') }}</v-btn>
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
        show_password: false,
        form1: {
          loading: false,
          terms: '',
          name: '',
          email: '',
          password: '',
          locale: '',
          timezone: '',
          has_error: false,
          error: '',
          errors: {},
          success: false
        }
      }
    },
    created () {
      this.form1.locale = Intl.DateTimeFormat().resolvedOptions().locale || null
      this.form1.timezone = Intl.DateTimeFormat().resolvedOptions().timeZone || null
    },
    computed: {
      campaign () {
        return this.$store.state.app.campaign
      }
    },
    methods: {
      toLogin() {
        this.$router.push({name: 'login'})
      },
      submitForm(formName) {
        this[formName].has_error = false
        this[formName].loading = true

        this.$validator.validateAll(formName).then((valid) => {
          if (valid) {
            this.register(formName);
          } else {
            this[formName].loading = false
            return false;
          }
        });
      },
      register(formName) {
        var app = this[formName]
        this.$auth.register({
          data: {
            language: this.$i18n.locale,
            uuid: this.$store.state.app.campaign.uuid,
            name: app.name,
            email: app.email,
            password: app.password,
            locale: app.locale,
            timezone: app.timezone,
            terms: app.terms
          },
					success: function () {
						app.success = true

						this.$auth.login({
							rememberMe: true,
							fetchUser: true,
							params: {
								locale: this.$i18n.locale,
            		uuid: this.$store.state.app.campaign.uuid,
								email: app.email,
								password: app.password,
								remember: true
							},
							success () {
								// Handle redirection
								this.$router.push({name: 'points'})
							}
						})
					},
          error: function (res) {
            app.has_error = true
            app.error = res.response.data.error
            app.errors = res.response.data.errors || {}

            if (app.error == 'limitation_reached') {
              app.name = ''
              app.email = ''
              app.password = ''
            }

            for (let field in app.errors) {
              this.$validator.errors.add({
                field: formName + '.' + field,
                msg: app.errors[field][0]
              })
            }
            app.loading = false
          }
        })
      }
    },
  }
</script>