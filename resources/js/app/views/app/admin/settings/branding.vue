<template>
  <v-card>
    <v-toolbar tabs flat>
      <v-toolbar-title>Branding</v-toolbar-title>
      <v-spacer></v-spacer>
      <template v-slot:extension>
        <v-flex>
          <v-tabs 
            v-model="selectedTab" 
            :slider-color="app.color_name"
            color="grey darken-3"
            show-arrows
            >
            <v-tab :href="'#general'">
              General
            </v-tab>
            <v-tab :href="'#email'">
              E-mail
            </v-tab>
            <v-tab :href="'#domain'">
              Domain
            </v-tab>
          </v-tabs>
        </v-flex>
      </template>
    </v-toolbar>

    <v-form 
      data-vv-scope="form1"
      :model="form1" 
      id="form1"
      lazy-validation
      @submit.prevent="submitForm('form1')"
      autocomplete="off"
      method="post"
      accept-charset="UTF-8" 
      >

      <v-divider class="grey lighten-2"></v-divider>

      <v-card-text v-if="$auth.user().demo">
        <v-alert :color="app.color_name" class="mb-0 mt-2">
          This account is in demo mode. You can't save any settings on this page.
        </v-alert>
      </v-card-text>

      <v-card-text>
        <v-alert
          :value="form1.has_error && !form1.success"
          type="error"
          class="mb-4"
        >
          <span v-if="form1.error == 'registration_validation_error'">{{ $t('server_error') }}</span>
          <span v-else>{{ $t('correct_errors') }}</span>
        </v-alert>

        <v-alert
          :value="form1.success"
          type="success"
          class="mb-4"
        >
          {{ $t('update_success') }}
        </v-alert>

        <v-tabs-items v-model="selectedTab" :touchless="false" class="mx-2">
          <v-tab-item key="general" :value="'general'" :eager="true">
            <v-text-field
              v-model="form1.app_name"
              data-vv-name="app_name"
              ref="app_name"
              label="App name"
              v-validate="'required|min:3|max:64'"
              :error-messages="errors.collect('form1.app_name')"
              class="mb-3"
              hint="The app name is the title that is visible in the top bar."
              persistent-hint
            ></v-text-field>
            <v-text-field
              v-model="form1.app_contact"
              data-vv-name="app_contact"
              ref="app_contact"
              label="Contact e-mail address"
              :error-messages="errors.collect('form1.app_contact')"
              v-validate="'required|max:64|email'"
              class="mb-3"
              hint="E-mail address visible on website for visitors to contact you."
              persistent-hint
            ></v-text-field>
          </v-tab-item>

          <v-tab-item key="email" :value="'email'" :eager="true">
            <v-text-field
              v-model="form1.app_mail_name_from"
              data-vv-name="app_mail_name_from"
              ref="app_mail_name_from"
              label="App e-mail name"
              :error-messages="errors.collect('form1.app_mail_name_from')"
              v-validate="'required|min:3|max:64'"
              class="mb-3"
              hint="Sender name for sending automated e-mails to users on your behalf."
              persistent-hint
            ></v-text-field>
            <v-text-field
              v-model="form1.app_mail_address_from"
              data-vv-name="app_mail_address_from"
              ref="app_mail_address_from"
              label="App e-mail address"
              :error-messages="errors.collect('form1.app_mail_address_from')"
              v-validate="'required|max:64|email'"
              class="mb-3"
              hint="Sender e-mail address for sending automated e-mails to users on your behalf."
              persistent-hint
            ></v-text-field>
          </v-tab-item>

          <v-tab-item key="domain" :value="'domain'" :eager="true">

            <p class="subtitle-2">Be careful when changing this domain. The website will become unusable on the current domain, and only become visible on the new domain if the DNS settings are correct.</p>

            <v-text-field
              v-model="form1.domain"
              data-vv-name="domain"
              ref="domain"
              label="Domain"
              :error-messages="errors.collect('form1.domain')"
              v-validate="'required|min:3|max:64'"
              hint=""
              persistent-hint
            ></v-text-field>

            <p class="subtitle-2">DNS changes may take a couple of hours to propagate.</p>
          </v-tab-item>

        </v-tabs-items>

      </v-card-text>

      <v-card-actions class="mx-2">
        <v-spacer></v-spacer>
        <v-btn :color="app.color_name" large :loading="form1.loading" type="submit" class="mb-2">{{ $t('update') }}</v-btn>
      </v-card-actions>
    </v-form>
  </v-card>
</template>
<script>
  export default {
    $_veeValidate: {
      validator: 'new'
    },
    data() {
      return {
        selectedTab: 'general',
        account_host: '',
        form1: {
          loading: false,
          app_name: '',
          app_contact: '',
          app_mail_name_from: '',
          app_mail_address_from: '',
          domain: '',
          has_error: false,
          error: null,
          errors: {},
          success: false
        }
      }
    },
    created () {
      axios
        .get('/admin/branding', { params: { locale: this.$i18n.locale }})
        .then(response => {
          this.form1.app_name = response.data.app_name
          this.form1.app_contact = response.data.app_contact
          this.form1.app_mail_name_from = response.data.app_mail_name_from
          this.form1.app_mail_address_from = response.data.app_mail_address_from
          this.form1.app_name = response.data.app_name
          this.form1.domain = response.data.app_host
          this.account_host = response.data.account_host

          this.loading = false
        })
    },
    methods: {
      submitForm(formName) {
        this[formName].success = false
        this[formName].has_error = false
        this[formName].loading = true

        this.$validator.validateAll(formName).then((valid) => {
          if (valid) {
            this.updateProfile(formName);
          } else {
            // Get first error and select tab where error occurs
            let field = this.errors.items[0].field
            let el = (typeof this.$refs[field] !== 'undefined') ? this.$refs[field] : null
            let tab = (el !== null) ? el.$parent.$vnode.key : null
            if (tab !== null) this.selectedTab = tab

            this[formName].loading = false
            return false;
          }
        });
      },
      updateProfile(formName) {
        var app = this[formName]

        axios
          .post('/admin/branding', { 
            locale: this.$i18n.locale, 
            app_name: app.app_name, 
            app_contact: app.app_contact, 
            app_mail_address_from: app.app_mail_address_from, 
            app_mail_name_from: app.app_mail_name_from, 
            domain: app.domain
          })
          .then(response => {
            if (response.data.status === 'success') {
                app.success = true
            }
            app.loading = false
          })
          .catch(err => {
            let errors = err.response.data.errors || {}
            let i = 0
            for (let field in errors) {
              if (i ==0) {
                // Get first error and select tab where error occurs
                let el = (typeof this.$refs[field] !== 'undefined') ? this.$refs[field] : null
                let tab = (el !== null) ? el.$parent.$vnode.key : null
                if (tab !== null) this.selectedTab = tab
              }
              i++
              this.$validator.errors.add({
                field: formName + '.' + field,
                msg: errors[field][0]
              })
            }
            app.loading = false
          })
      }
    },
    computed: {
      app () {
        return this.$store.getters.app
      },
       _(){
           return _;
      }
    }
  }
</script>
<style scoped>
</style>