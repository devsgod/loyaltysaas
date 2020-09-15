<template>
  <v-card>
    <v-toolbar :tabs="tabCount > 1" flat v-if="!loadingForm">
      <v-toolbar-title>{{ (uuid === null) ? translations.create_item : translations.edit_item }}</v-toolbar-title>
      <v-spacer></v-spacer>
      <v-btn icon @click="$emit('data-list-events', {'closeDialog': true, 'reload': false})">
        <v-icon>close</v-icon>
      </v-btn>
      <template v-slot:extension v-if="tabCount > 1 && ! limitReached">
        <v-tabs
          slot="extension"
          v-model="selectedTab"
          :slider-color="app.color_name"
          color="grey darken-3"
          show-arrows
          >
          <v-tab :key="'tab_' + tab_index" :href="'#' + tab_index" v-for="(tab, tab_index) in form.items" v-if="tabCount > 1">{{ tab.text }}</v-tab>

        </v-tabs>
      </template>
    </v-toolbar>

    <div v-if="loadingForm" class="px-4 py-3">
      <v-progress-linear :indeterminate="true" :color="app.color_name"></v-progress-linear>
    </div>

    <div v-if="!loadingForm && limitReached">
      <v-card-text class="pt-0">

        <p class="subtitle-1">You have reached the maximum of {{ max }} {{ translations.items_lowercase }}. If you want to create more {{ translations.items_lowercase }}, you have to upgrade your subscription.</p>

        
      </v-card-text>
      <v-card-actions>
        <v-spacer></v-spacer>
        <v-btn large text :color="app.color_name" :to="{'name': 'user.billing'}">Manage subscription</v-btn>
      </v-card-actions>
    </div>

    <v-form 
      data-vv-scope="formModel"
      :model="formModel" 
      v-if="!loadingForm && ! limitReached"
      @submit.prevent="submitForm('formModel')"
      autocomplete="off"
      method="post"
      id="formModel"
      accept-charset="UTF-8" 
      enctype="multipart/form-data"
      >

      <v-divider class="grey lighten-2"></v-divider>

      <v-card-text :style="{ 'height': settings.dialog_height + 'px' || 'auto', 'max-width': '800px', 'overflow-y': 'auto' }">
        <v-tabs-items v-model="selectedTab" :touchless="true" class="mx-2">
          <v-tab-item :value="tab_index" v-for="(tab, tab_index) in form.items" :key="tab_index" :eager="true">

            <div v-if="tab.description" v-html="tab.description" class="subtitle-1 mb-3"></div>

            <v-card v-if="Object.keys(form.items[tab_index].subs).length > 1" class="mb-3 elevation-1">
              <v-tabs
                v-if="Object.keys(form.items[tab_index].subs).length > 1"
                v-model="selectedSubTab[tab_index]"
                hide-slider
                :color="app.color_name"
                background-color="grey lighten-4"
                show-arrows
                >
                <v-tab :key="'sub_tab_' + sub_index" :href="'#' + sub_index" v-for="(sub, sub_index) in tab.subs">{{ sub.text }}</v-tab>
              </v-tabs>
            </v-card>

              <v-tabs-items v-model="selectedSubTab[tab_index]" :touchless="true">
                <v-tab-item :value="sub_index" v-for="(sub, sub_index) in tab.subs" :key="sub_index" :eager="true">

                  <div v-if="sub.description" v-html="sub.description" class="subtitle-1 mb-3"></div>
<!--
              <div v-for="(sub, sub_index) in tab.subs">
                <div class="headline mt-2 mb-3" v-if="sub.text">{{ sub.text }}</div>
-->
                <div v-for="(form_item, form_index) in sub.items" :key="form_index">

                  <div v-if="form_item.type == 'description'">
                     <v-sheet
                        v-html="form_item.text"
                        class="pa-3 mb-3 subtitle-1 elevation-1"
                        color="grey lighten-4"
                      >
                    </v-sheet>
                  </div>

                  <div v-if="form_item.type == 'text' || form_item.type == 'email'">
                    <v-text-field
                      :type="form_item.type"
                      ___autofocus="form_index == 0 && uuid == null"
                      v-model="formModel[form_item.column]"
                      :ref="form_item.column"
                      :data-vv-name="form_item.column"
                      :data-vv-as="form_item.text.toLowerCase()"
                      v-validate="form_item.validate"
                      :label="(form_item.required) ? form_item.text + $t('_required_') : form_item.text"
                      :error-messages="errors.collect('formModel.' + form_item.column)"
                      :prepend-inner-icon="form_item.icon"
                      :placeholder="form_item.placeholder"
                      :hint="form_item.hint"
                      :persistent-hint="true"
                      :prefix="form_item.prefix"
                      :suffix="form_item.suffix"
                    ></v-text-field>
                  </div>

                  <div v-if="form_item.type == 'password'">
                    <v-text-field
                      :type="formModel[form_item.column + '_show'] ? 'text' : 'password'"
                      :append-icon="formModel[form_item.column + '_show'] ? 'visibility' : 'visibility_off'"
                      @click:append="formModel[form_item.column + '_show'] = !formModel[form_item.column + '_show']"
                      ___autofocus="form_index == 0 && uuid == null"
                      v-model="formModel[form_item.column]"
                      :ref="form_item.column"
                      :data-vv-name="form_item.column"
                      :data-vv-as="form_item.text.toLowerCase()"
                      v-validate="form_item.validate"
                      :label="(form_item.required) ? form_item.text + $t('_required_') : form_item.text"
                      :error-messages="errors.collect('formModel.' + form_item.column)"
                      :prepend-inner-icon="form_item.icon"
                      :placeholder="form_item.placeholder"
                      :hint="form_item.hint"
                      :persistent-hint="true"
                      :prefix="form_item.prefix"
                      :suffix="form_item.suffix"
                    ></v-text-field>
                  </div>

                  <div v-if="form_item.type == 'wysiwyg'">
                    <ckeditor 
                      :editor="ClassicEditor" 
                      v-model="formModel[form_item.column]"
                      :ref="form_item.column"
                      :data-vv-name="form_item.column"
                      :data-vv-as="form_item.text.toLowerCase()"
                      v-validate="form_item.validate"
                      :error-messages="errors.collect('formModel.' + form_item.column)"
                      :config="form_item.config"
                    >
                    </ckeditor>
                  </div>

                  <div v-if="form_item.type == 'number'">
                    <v-text-field
                      :type="form_item.type"
                      ___autofocus="form_index == 0 && uuid == null"
                      v-model="formModel[form_item.column]"
                      :ref="form_item.column"
                      :data-vv-name="form_item.column"
                      :data-vv-as="form_item.text.toLowerCase()"
                      v-validate="form_item.validate"
                      :label="(form_item.required) ? form_item.text + $t('_required_') : form_item.text"
                      :error-messages="errors.collect('formModel.' + form_item.column)"
                      :prepend-inner-icon="form_item.icon"
                      :placeholder="form_item.placeholder"
                      :hint="form_item.hint"
                      :persistent-hint="true"
                      :prefix="form_item.prefix"
                      :suffix="form_item.suffix"
                    ></v-text-field>
                  </div>

                  <div v-if="form_item.type == 'currency'">
                    <v-text-field
                      type="number"
                      ___autofocus="form_index == 0 && uuid == null"
                      v-model="formModel[form_item.column]"
                      :ref="form_item.column"
                      :data-vv-name="form_item.column"
                      :data-vv-as="form_item.text.toLowerCase()"
                      v-validate="form_item.validate"
                      :label="(form_item.required) ? form_item.text + $t('_required_') : form_item.text"
                      :error-messages="errors.collect('formModel.' + form_item.column)"
                      :prepend-inner-icon="form_item.icon"
                      :placeholder="form_item.placeholder"
                      :hint="form_item.hint"
                      :persistent-hint="true"
                      :prefix="form_item.prefix"
                      :suffix="form_item.suffix"
                    ></v-text-field>
                  </div>

                  <div v-if="form_item.type == 'date_time'">
                    <date-time-picker
                      ___autofocus="form_index == 0 && uuid == null"
                      v-model="formModel[form_item.column]"
                      :ref="form_item.column"
                      :data-vv-name="form_item.column"
                      :data-vv-as="form_item.text.toLowerCase()"
                      v-validate="form_item.validate"
                      :label="(form_item.required) ? form_item.text + $t('_required_') : form_item.text"
                      :error-messages="errors.collect('formModel.' + form_item.column)"
                      :prepend-inner-icon="form_item.icon"
                      :placeholder="form_item.placeholder"
                      :hint="form_item.hint"
                      :locale="$auth.user().locale.substring(0,2)"
                      :format="form_item.format"
                      timePickerFormat="ampm"
                      :persistent-hint="true"
                      :prefix="form_item.prefix"
                      :suffix="form_item.suffix"
                    >
                      <template slot="actions" slot-scope="{ parent }">
                        <v-btn :color="app.color_name" text @click="parent.okHandler">OK</v-btn>
                        <v-btn color="red" text @click.native="parent.clearHandler">Clear</v-btn>
                        <v-btn color="secondary" text @click.native="parent.display = false">Close</v-btn>
                      </template>
                    </date-time-picker>
                  </div>

                  <div v-if="form_item.type == 'color'">

                    <color-picker
                      ___autofocus="form_index == 0 && uuid == null"
                      v-model="formModel[form_item.column]"
                      :color="formModel[form_item.column]"
                      :mode="form_item.mode || 'hexa'"
                      :ref="form_item.column"
                      :data-vv-name="form_item.column"
                      :data-vv-as="form_item.text.toLowerCase()"
                      v-validate="form_item.validate"
                      :label="(form_item.required) ? form_item.text + $t('_required_') : form_item.text"
                      :error-messages="errors.collect('formModel.' + form_item.column)"
                      :prepend-inner-icon="form_item.icon"
                      :placeholder="form_item.placeholder"
                      :hint="form_item.hint"
                      :persistent-hint="true"
                      :prefix="form_item.prefix"
                      :suffix="form_item.suffix"
                    >
                    </color-picker>
    <!--
                    <v-text-field
                      type="text"
                      ___autofocus="form_index == 0 && uuid == null"
                      v-model="formModel[form_item.column]"
                      :ref="form_item.column"
                      :data-vv-name="form_item.column"
                      :data-vv-as="form_item.text.toLowerCase()"
                      v-validate="form_item.validate"
                      :label="(form_item.required) ? form_item.text + $t('_required_') : form_item.text"
                      :error-messages="errors.collect('formModel.' + form_item.column)"
                      :prepend-inner-icon="form_item.icon"
                      :placeholder="form_item.placeholder"
                      :hint="form_item.hint"
                      :persistent-hint="true"
                      :prefix="form_item.prefix"
                      :suffix="form_item.suffix"
                    >

                      <template slot="prepend">
                          <v-icon @click="formModel[form_item.column + '_picker'] = (formModel[form_item.column + '_picker']) ? false : true;">color_lens</v-icon>
                      </template>

                    </v-text-field>

                    <v-color-picker
                      v-if="formModel[form_item.column + '_picker']"
                      v-model="formModel[form_item.column]"
                      :mode="form_item.mode || 'hexa'"
                      :hide-canvas="false"
                      :hide-inputs="false"
                      :hide-mode-switch="true"
                      :show-swatches="false"
                      class="mb-3"
                    ></v-color-picker>
    -->
                  </div>

                  <div v-if="form_item.type == 'slider'">
                    <v-slider
                      ___autofocus="form_index == 0 && uuid == null"
                      v-model="formModel[form_item.column]"
                      :ref="form_item.column"
                      :min="form_item.min"
                      :max="form_item.max"
                      :step="form_item.step"
                      :data-vv-name="form_item.column"
                      :data-vv-as="form_item.text.toLowerCase()"
                      v-validate="form_item.validate"
                      :label="(form_item.required) ? form_item.text + $t('_required_') : form_item.text"
                      :error-messages="errors.collect('formModel.' + form_item.column)"
                      :prepend-inner-icon="form_item.icon"
                      :placeholder="form_item.placeholder"
                      :hint="form_item.hint"
                      :persistent-hint="true"
                      :prefix="form_item.prefix"
                      :suffix="form_item.suffix"
                    >

                      <template #append>
                        <v-text-field
                          v-model="formModel[form_item.column]"
                          class="pt-0 mt-0"
                          hide-details
                          single-line
                          type="number"
                          style="width: 60px"
                        ></v-text-field>
                      </template>

                    </v-slider>
                  </div>

                  <div v-if="form_item.type == 'boolean'">
                    <v-checkbox
                      type="checkbox"
                      ___autofocus="form_index == 0 && uuid == null"
                      v-model="formModel[form_item.column]"
                      :ref="form_item.column"
                      :data-vv-name="form_item.column"
                      :data-vv-as="form_item.text.toLowerCase()"
                      v-validate="form_item.validate"
                      :label="(form_item.required) ? form_item.text + $t('_required_') : form_item.text"
                      :error-messages="errors.collect('formModel.' + form_item.column)"
                      :hint="form_item.hint"
                      :persistent-hint="true"
                      class="mt-0"
                    ></v-checkbox>
                  </div>

                  <div v-if="form_item.type == 'relation' && (form_item.relation.type == 'hasOne' || form_item.relation.type == 'belongsTo')">
                    <v-autocomplete
                      dense
                      ___autofocus="form_index == 0 && uuid == null"
                      v-model="formModel[form_item.column]"
                      :ref="form_item.column"
                      :data-vv-name="form_item.column"
                      :data-vv-as="form_item.text.toLowerCase()"
                      v-validate="form_item.validate"
                      :label="(form_item.required) ? form_item.text + $t('_required_') : form_item.text"
                      :error-messages="errors.collect('formModel.' + form_item.column)"
                      :hint="form_item.hint"
                      :persistent-hint="true"
                      :placeholder="form_item.placeholder"
                      :prepend-inner-icon="form_item.icon"
                      :items="relations[form_item.column].items"
                      :loading="formModel[form_item.column + '_loading']"
                      :prefix="form_item.prefix"
                      :suffix="form_item.suffix"
                      item-text="val"
                      item-value="pk"
                      hide-no-data
                      hide-selected
                      clearable
                    ></v-autocomplete>
                  </div>

                  <div v-if="form_item.type == 'relation' && form_item.relation.type == 'belongsToMany'">
                    <v-autocomplete
                      dense
                      ___autofocus="form_index == 0 && uuid == null"
                      v-model="formModel[form_item.relation.with]"
                      :ref="form_item.relation.with"
                      :data-vv-name="form_item.relation.with"
                      :data-vv-as="form_item.text.toLowerCase()"
                      v-validate="form_item.validate"
                      :label="(form_item.required) ? form_item.text + $t('_required_') : form_item.text"
                      :error-messages="errors.collect('formModel.' + form_item.relation.with)"
                      :hint="form_item.hint"
                      :persistent-hint="true"
                      :placeholder="form_item.placeholder"
                      :prepend-inner-icon="form_item.icon"
                      :items="relations[form_item.relation.with].items"
                      :prefix="form_item.prefix"
                      :suffix="form_item.suffix"
                      item-text="val"
                      item-value="pk"
                      hide-no-data
                      hide-selected
                      chips
                      multiple
                      deletable-chips
                    ></v-autocomplete>
                  </div>

                  <div v-if="form_item.type == 'image' || form_item.type == 'avatar'">
                    <v-img :src="formModel[form_item.column + '_media_url']" class="mt-3 mb-1" :class="form_item.class" contain :style="{'width': form_item.image.thumb_width, 'height': form_item.image.thumb_height, 'max-width': form_item.image.thumb_max_width, 'max-height': form_item.image.thumb_max_height}" v-if="formModel[form_item.column + '_media_url']"></v-img>
                    <v-text-field
                      @click="pickFile(form_item.column)"
                      type="text"
                      readonly
                      v-model="formModel[form_item.column + '_media_name']"
                      :ref="form_item.column"
                      :data-vv-name="form_item.column"
                      :data-vv-as="form_item.text.toLowerCase()"
                      v-validate="form_item.validate"
                      :label="(form_item.required) ? form_item.text + $t('_required_') : form_item.text"
                      :error-messages="errors.collect('formModel.' + form_item.column)"
                      :prepend-inner-icon="form_item.icon"
                      :placeholder="form_item.placeholder"
                      :hint="form_item.hint"
                      :persistent-hint="true"
                      :prefix="form_item.prefix"
                      :suffix="form_item.suffix"
                    >
                      <template slot="append">
                          <v-icon v-if="formModel[form_item.column + '_media_name'] != ''" @click="formModel[form_item.column + '_media_name'] = ''; formModel[form_item.column + '_media_url'] = ''; formModel[form_item.column + '_media_changed'] = true;">delete</v-icon>
                      </template>
                    </v-text-field>

                    <input
                      type="file"
                      style="display: none"
                      :id="form_item.column"
                      :name="form_item.column"
                      accept="image/*"
                      @change="onFilePicked"
                    >
                  </div>

                </div>

              </v-tab-item>
            </v-tabs-items>

<!--              </div>-->

          </v-tab-item>
        </v-tabs-items>
      </v-card-text>

      <v-divider v-if="settings.dialog_height" class="grey lighten-2"></v-divider>

      <v-card-actions v-if="!loadingForm">
        <v-spacer></v-spacer>
        <v-btn :color="app.color_name" text _loading="form.loading" :disabled="form.loading" large type="submit" class="ml-0">{{ (uuid === null) ? $t('create') : $t('save') }}</v-btn>
        <v-btn color="secondary" text :disabled="form.loading" large @click="$emit('data-list-events', {'closeDialog': true, 'reload': false})">{{ $t('close') }}</v-btn>
      </v-card-actions>
    </v-form>
    <v-overlay :value="form.loading" v-if="!loadingForm">
      <v-progress-circular indeterminate size="64"></v-progress-circular>
    </v-overlay>
  </v-card>
</template>
<script>
  import ClassicEditor from '@ckeditor/ckeditor5-build-classic';
  //console.log(ClassicEditor.builtinPlugins.map( plugin => plugin.pluginName ))

  export default {
    $_veeValidate: {
      validator: 'new'
    },
    data: () => {
      return {
        ClassicEditor: ClassicEditor,
        tabCount: 1,
        selectedTab: 'tab1',
        selectedSubTab: {
          tab1: 'sub1',
          tab2: 'sub1',
          tab3: 'sub1',
          tab4: 'sub1',
          tab5: 'sub1',
          tab6: 'sub1',
          tab7: 'sub1',
          tab8: 'sub1',
          tab9: 'sub1',
          tab10: 'sub1',
          tab11: 'sub1',
          tab12: 'sub1'
        },
        count: null,
        max: null,
        limitReached: null,
        activeFilePickerColumn: null,
        formModel: {},
        loadingForm: true,
        loading: true,
        settings: [],
        form: [],
        translations: [],
        relations: []
      }
    },
    props: {
      api: {
        default: '/app/data-form',
        required: false,
        type: String
      },
      model: {
        default: '',
        required: false,
        type: String
      },
      uuid: {
        default: null,
        required: false,
        type: String
      }
    },
    computed: {
      app () {
        return this.$store.getters.app
      }
    },
    created() {
      this.$validator.extend('nullable', {
        validate: (value, [testProp]) => { return true;  }
      });
      this.$validator.extend('unique', {
        validate: (value, [testProp]) => { return true;  }
      });
      this.$validator.extend('not_in', {
        validate: (value, [testProp]) => { return true;  }
      });
      moment.locale(this.$auth.user().locale)
    },
    beforeMount () {
      this.getDataFromApi()
        .then(data => {
          this.form = data.form
          this.tabCount = Object.keys(this.form.items).length
        })
    },
    methods: {
      submitForm(scope) {
        this.form.has_error = false
        this.form.loading = true

        if (this.tabCount > 1) {
          for (let i = 2; i <= this.tabCount; i++) {

          }
        }

        this.$validator.validateAll(scope).then((valid) => {
          if (valid) {
            this.saveForm()
          } else {
            // Get first error and select tab where error occurs
            let field = this.errors.items[0].field
            let el = (typeof this.$refs[field] !== 'undefined') ? this.$refs[field] : null
            let subtab = (el !== null) ? el[0].$parent.$vnode.key : null
            let tab = (el !== null) ? el[0].$parent.$parent.$parent.$vnode.key : null

            if (tab !== null) this.selectedTab = tab
            if (tab !== null && subtab !== null) this.selectedSubTab[tab] = subtab

            this.form.loading = false
            return false
          }
        });
      },
      saveForm() {
        this.loading = true
        let that = this

        let settings = { headers: { 'content-type': 'multipart/form-data' } }

        // Remove image urls
        let formModel = Object.assign({}, this.formModel);
        for (let field in this.formModel) {
          if (_.endsWith(field, '_media_url') || _.endsWith(field, '_media_name') || _.endsWith(field, '_media_file') || field == 'avatar') {
            formModel[field] = null;
          }
        }

        let formData = new FormData(document.getElementById('formModel'));
        formData.append('locale', this.$i18n.locale)
        formData.append('model', this.model)
        formData.append('formModel', JSON.stringify(formModel))
        formData.append('uuid', this.uuid)

        axios.post(this.api + '/save', formData, settings)
        .then(res => {
          if (res.data.status === 'success') {
            let action = (this.uuid === null) ? 'item_created' : 'item_saved'
            this.$root.$snackbar(this.$t(action))
            this.$emit('data-list-events', {'closeDialog': true, 'reload': true})
          }
        })
        .catch(err => {
          if (typeof err.response !== 'undefined') {
            if (typeof err.response.status !== 'undefined' && typeof err.response.data.msg !== 'undefined' && err.response.data.msg !== '') {
              if (err.response.status == 422) {
                this.$root.$snackbar(err.response.data.msg)
                return
              }
            }
            this.formModel.has_error = true
            this.formModel.error = err.response.data.error
            this.formModel.errors = err.response.data.errors || {}

            // Get first error and select tab where error occurs
            let field = Object.keys(this.formModel.errors)[0]
            let el = (typeof this.$refs[field] !== 'undefined') ? this.$refs[field] : null
            let tab = (el !== null) ? el[0].$parent.$vnode.key : null
            if (tab !== null) this.selectedTab = tab

            for (let field in this.formModel.errors) {
              this.$validator.errors.add({
                field: 'formModel.' + field,
                msg: this.formModel.errors[field][0]
              })
            }
          }
        })
        .finally(() => {
          that.loading = false
          that.form.loading = false
        })
      },
      getDataFromApi () {
        this.loading = true
        return new Promise((resolve, reject) => {
          let that = this
          axios.get(this.api, {
            params: {
              locale: this.$i18n.locale,
              model: this.model,
              uuid: this.uuid
            }
            })
          .then(res => {
            if (res.data.status === 'success') {
              let form = {}

              form.items = res.data.form
              form.loading = false
              form.error = ''
              form.errors = {}
              form.has_error = false
              form.success = false

              that.settings = res.data.settings
              that.formModel = res.data.values
              that.translations = res.data.translations
              that.relations = res.data.relations
              that.count = res.data.count
              that.max = res.data.max
              that.limitReached = res.data.limitReached
              that.loading = false
              that.loadingForm = false

              // Dates
              for (let date of res.data.dates) {
                if (that.formModel[date] !== null) {
                  that.formModel[date] =  new Date(that.formModel[date])
                }
              }

              // Relations
              /*
              for (let relation of res.data.relations) {
                this.getRelatedData(relation.column, relation)
              }*/

              resolve({
                form
              })
            }
          })
          .catch(err => console.log(err.response.data))
          .finally(() => that.loading = false)
        })
      },
      pickFile (column) {
        this.activeFilePickerColumn = column
        document.getElementById(column).click();
      },
      onFilePicked (e) {
        const files = e.target.files
        if(files[0] !== undefined) {
          this.formModel[this.activeFilePickerColumn + '_media_name'] = files[0].name
          if(this.formModel[this.activeFilePickerColumn + '_media_name'].lastIndexOf('.') <= 0) {
            return
          }
          const fr = new FileReader ()
          fr.readAsDataURL(files[0])
          fr.addEventListener('load', () => {
            this.formModel[this.activeFilePickerColumn + '_media_url'] = fr.result
            this.formModel[this.activeFilePickerColumn + '_media_file'] = files[0] // this is an image file that can be sent to server...
            this.formModel[this.activeFilePickerColumn + '_media_changed'] = true
          })
        } else {
          this.formModel[this.activeFilePickerColumn + '_media_name'] = ''
          this.formModel[this.activeFilePickerColumn + '_media_file'] = ''
          this.formModel[this.activeFilePickerColumn + '_media_url'] = ''
          this.formModel[this.activeFilePickerColumn + '_media_changed'] = true
        }
      },
      getRelatedData (column, relation) {
        let that = this
        axios.post(this.api + '/relation', {
          locale: this.$i18n.locale,
          model: this.model,
          uuid: this.uuid,
          relation: relation
        })
        .then(res => {
          if (res.data.status === 'success') {
            that.formModel[column + '_items'] = res.data.fields
            that.formModel[column + '_loading'] = false
          }
        })
        .catch(err => console.log(err.response.data))
      }
    }
  }
</script>
<style scoped>
</style>