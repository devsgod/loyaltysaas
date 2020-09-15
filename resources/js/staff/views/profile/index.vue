<template>
  <v-container fluid fill-height>
    <v-layout align-center justify-center row fill-height wrap>

      <v-flex xs10 sm8 md6 lg4 xl3>

        <v-form 
          data-vv-scope="form1"
          :model="form1"
          id="form1"
          lazy-validation
          @submit.prevent="submitForm('form1')"
          autocomplete="off"
          method="post"
          accept-charset="UTF-8" 
          enctype="multipart/form-data"
          >

          <v-card class="elevation-18">
            <v-toolbar tabs flat color="grey lighten-3">
              <v-toolbar-title>{{ $t('profile') }}</v-toolbar-title>
              <template v-slot:extension>
                <v-tabs
                  slot="extension"
                  v-model="selectedTab"
                  slider-color="grey darken-3"
                  color="grey darken-3"
                  show-arrows
                  >
                  <v-tab :href="'#general'">
                    General
                  </v-tab>
                  <v-tab :href="'#localization'">
                    Localization
                  </v-tab>
                  <v-tab :href="'#password'">
                    Change password
                  </v-tab>
                </v-tabs>
              </template>
            </v-toolbar>

            <v-divider class="grey lighten-2"></v-divider>

            <v-alert
              :value="form1.has_error && !form1.success"
              type="error"
              class="mx-3 mt-3"
            >
              <span v-if="form1.error == 'registration_validation_error'">{{ $t('server_error') }}</span>
              <span v-else>{{ $t('correct_errors') }}</span>
            </v-alert>

            <v-alert
              :value="form1.success"
              type="success"
              class="mx-3 mt-3"
            >
              {{ $t('update_success') }}
            </v-alert>

            <v-container fluid grid-list-xl>
              <v-layout>
                <v-flex
                  xs12
                >

                  <v-tabs-items v-model="selectedTab" :touchless="false">
                    <v-tab-item :value="'general'">

                        <v-btn v-if="showDeleteAvatar" small fab color="ma-0 red darken-2 white--text" @click="form1.avatar_media_url = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAIAAACQd1PeAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAA9JREFUeNpiuHbtGkCAAQAFCAKDZcGh3gAAAABJRU5ErkJggg=='; form1.avatar_media_changed = true; showDeleteAvatar = false" style="position: absolute; z-index: 9"><v-icon small>delete</v-icon></v-btn>
                        <v-img :src="form1.avatar_media_url" class="mb-3 img-rounded" style="width: 96px; height: 96px" @click="pickFile('avatar')"></v-img>

                        <input
                          type="file"
                          style="display: none"
                          id="avatar"
                          name="avatar"
                          accept="image/*"
                          @change="onFilePicked"
                        >

                        <v-text-field
                          v-model="form1.name"
                          data-vv-name="name"
                          v-validate="'required|min:2|max:32'"
                          :label="$t('name')"
                          :placeholder="$t('enter_your_name')"
                          :error-messages="errors.collect('form1.name')"
                          required
                        ></v-text-field>

                        <v-text-field
                          type="email"
                          v-model="form1.email"
                          data-vv-name="email"
                          v-validate="'required|max:64|email'"
                          :label="$t('email_address')"
                          :placeholder="$t('enter_email')"
                          :error-messages="errors.collect('form1.email')"
                          required
                        ></v-text-field>

                    </v-tab-item>
                    <v-tab-item :value="'localization'">

                      <v-autocomplete
                        v-model="form1.locale"
                        :items="locales"
                        item-value="0" 
                        item-text="1"
                        label="Locale"
                        data-vv-name="locale"
                        v-validate="'required'"
                        :error-messages="errors.collect('form1.locale')"
                        required
                      ></v-autocomplete>

                      <v-autocomplete
                        v-model="form1.timezone"
                        :items="timezones"
                        item-value="0" 
                        item-text="1"
                        label="Select timezone"
                        data-vv-name="timezone"
                        v-validate="'required'"
                        :error-messages="errors.collect('form1.timezone')"
                        required
                      ></v-autocomplete>

                    </v-tab-item>
                    <v-tab-item :value="'password'">
                      <v-text-field
                        class="mb-4"
                        v-model="form1.new_password"
                        data-vv-name="new_password"
                        :data-vv-as="$t('password').toLowerCase()"
                        v-validate="'min:8|max:24'"
                        :label="$t('change_password')"
                        :hint="$t('leave_empty_for_no_change')"
                        :persistent-hint="true"
                        :error-messages="errors.collect('form1.new_password')"
                        :type="show_new_password ? 'text' : 'password'"
                        :append-icon="show_new_password ? 'visibility' : 'visibility_off'"
                        @click:append="show_new_password = !show_new_password"
                      ></v-text-field>
                    </v-tab-item>
                  </v-tabs-items>

                  <v-text-field
                    outlined
                    class="mt-3"
                    v-model="form1.current_password"
                    data-vv-name="current_password"
                    :data-vv-as="$t('current_password').toLowerCase()"
                    v-validate="'required|min:8|max:24'"
                    :label="$t('current_password')"
                    :error-messages="errors.collect('form1.current_password')"
                    :type="show_current_password ? 'text' : 'password'"
                    :append-icon="show_current_password ? 'visibility' : 'visibility_off'"
                    @click:append="show_current_password = !show_current_password"
                    required
                  ></v-text-field>

                </v-flex>
              </v-layout>
            </v-container>

            <v-card-actions>
              <v-spacer></v-spacer>
                <v-btn color="primary" :loading="form1.loading" type="submit">{{ $t('update') }}</v-btn>
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
        activeFilePickerId: null,
        showDeleteAvatar: false,
        selectedTab: 'general',
        show_current_password: false,
        show_new_password: false,
        timezones: [],
        locales: [],
        form1: {
          uuid: this.$store.state.app.campaign.uuid,
          loading: false,
          name: this.$auth.user().name,
          email: this.$auth.user().email,
          avatar_media_url: this.$auth.user().avatar,
          avatar_media_changed: false,
          locale: this.$auth.user().locale,
          timezone: this.$auth.user().timezone,
          new_password: null,
          current_password: null,
          has_error: false,
          error: null,
          errors: {},
          success: false
        }
      }
    },
    mounted () {
      axios
        .get('/localization/locales', { params: { locale: this.$i18n.locale }})
        .then(response => {
          this.locales = _.toPairs(response.data)
        })
      axios
        .get('/localization/timezones', { params: { locale: this.$i18n.locale }})
        .then(response => {
          this.timezones = _.toPairs(response.data)
        })
    },
    created () {
        this.showDeleteAvatar = (_.startsWith(this.form1.avatar_media_url, 'data:image/png;base64')) ? false : true
    },
    methods: {
      submitForm(formName) {
        console.log(0);
        this[formName].success = false
        this[formName].has_error = false
        this[formName].loading = true

        this.$validator.validateAll(formName).then((valid) => {
          if (valid) {
            this.updateProfile(formName);
          } else {
            this[formName].loading = false
            return false;
          }
        });
      },
      updateProfile(formName) {
        var app = this[formName]

        let settings = { headers: { 'content-type': 'multipart/form-data' } }

        // Remove image urls
        let formModel = Object.assign({}, this.form1);
        formModel.avatar_media_url = null;

        let formData = new FormData(document.getElementById('form1'));

        for (let field in formModel) {
          formData.append(field, formModel[field])
        }

        axios
          .post('/staff/auth/profile', formData, settings)
          .then(response => {
            if (response.data.status === 'success') {
              app.success = true
              app.new_password = null
              app.current_password = null
              this.$nextTick(() => this.$validator.reset())

              // Update auth object
              this.$auth.user(response.data.user)
              this.form1.avatar_media_url = this.$auth.user().avatar
            }
          })
          .catch(error => {
            app.has_error = true

            if (error.response.data.status === 'error') {
              this.errorMsg = error.response.data.error
              app.errors = error.response.data.errors || {}

              for (let field in app.errors) {
                this.$validator.errors.add({
                  field: formName + '.' + field,
                  msg: app.errors[field][0]
                })
              }
            }
          })
          .finally(() => { 
            app.loading = false
          })
      },
      pickFile (id) {
        this.activeFilePickerId = id
        document.getElementById(id).click();
      },
      onFilePicked (e) {
        const files = e.target.files
        if(files[0] !== undefined) {
          if(files[0].name.lastIndexOf('.') <= 0) {
            return
          }
          const fr = new FileReader ()
          fr.readAsDataURL(files[0])
          fr.addEventListener('load', () => {
            this.form1[this.activeFilePickerId + '_media_url'] = fr.result
            this.form1[this.activeFilePickerId + '_media_file'] = files[0] // this is an image file that can be sent to server...
            this.form1[this.activeFilePickerId + '_media_changed'] = true
            this.showDeleteAvatar = true
          })
        } else {
          this.form1[this.activeFilePickerId + '_media_file'] = ''
          this.form1[this.activeFilePickerId + '_media_url'] = ''
          this.form1[this.activeFilePickerId + '_media_changed'] = true
        }
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