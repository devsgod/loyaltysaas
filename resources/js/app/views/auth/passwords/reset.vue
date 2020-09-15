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
                :value="invalid_token"
                type="error"
                >
                {{ $t('invalid_token') }}
              </v-alert>

              <el-alert
                type="error"
                v-if="form1.has_error && !form1.success"
                class="mb-1"
                show-icon>
                <p>{{ $t('correct_errors') }}</p>
              </el-alert>

              <div
                v-if="!invalid_token"
                >

                <v-text-field
                  :type="show_password ? 'text' : 'password'"
                  :append-icon="show_password ? 'visibility' : 'visibility_off'"
                  @click:append="show_password = !show_password"
                  v-model="form1.password"
                  data-vv-name="password"
                  v-validate="'required|min:8|max:24'"
                  :label="$t('enter_new_password')"
                  :error-messages="errors.collect('form1.password')"
                  required
                  prepend-inner-icon="lock"
                  ></v-text-field>
              </div>

            </v-card-text>

            <v-card-actions v-if="!invalid_token">
              <v-btn :color="$store.getters.app.color_name" :loading="form1.loading" :disabled="form1.loading" large block type="submit">{{ $t('update_password') }}</v-btn>
            </v-card-actions>
          </v-card>
        </v-form>
      </v-flex>
    </v-layout>
  </v-container>
</template>
<script>
  export default {
    data() {
      return {
        show_password: false,
        invalid_token: false,
        form1: {
          loading: false,
          password: '',
          has_error: false,
          errors: {},
          success: false
        }
      }
    },
    beforeDestroy: function() {
      this.$store.dispatch('setBackgroundColor', this.$store.state.app.originalBackgroundColor)
    }, 
    mounted() {
      this.$store.dispatch('setBackgroundColor', '#ffffff')
    },
    created() {
      // Verify token
      let token = this.$route.params.token
      axios
        .post('/auth/password/reset/validate-token', {
          locale: this.$i18n.locale,
          token: token
        })
        .then(response => {
          if (response.data.status === 'success') {
            this.invalid_token = false
          } else {
            this.invalid_token = true
          }
        })
        .catch(error => {
          this.invalid_token = true
        })
    },
    methods: {
      toHome() {
        this.$router.push({name: 'home'})
      },
      submitForm(formName) {
        this[formName].invalid_token = false
        this[formName].loading = true

        this.$validator.validateAll(formName).then((valid) => {
          if (valid) {
            this.update(formName)
          } else {
            this[formName].loading = false
            return false;
          }
        });
      },
      update(formName) {
        let app = this[formName]

        axios
          .post('/auth/password/update', {
            locale: this.$i18n.locale,
            password: app.password,
            token: this.$route.params.token
          })
          .then(response => {
            if (response.data.status === 'success') {
              this.$router.push({name: 'login', params: {successResetUpdateRedirect: true}})
            }
          })
          .catch(error => {
            app.has_error = true
            app.errors = error.response.data.errors || {}
          })
          .finally(() => app.loading = false)
      }
    }
  }
</script>
<style scoped>
</style>