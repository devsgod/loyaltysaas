<template>
    <v-dialog
      v-model="display"
      @keydown.esc="display = false"
      eager
      persistent
      :width="width"
      :disabled="disabled"
     >
        <template v-slot:activator="{ on }">
          <v-text-field
            v-on="on"
            :label="label"
            :value="color"
            :disabled="disabled"
            :loading="loading"
            :error-messages="errorMessages"
            :error-count="errorCount"
            :error="error"
            :hide-details="hideDetails"
            :append-icon="appendIcon"
            :prepend-icon="prependIcon"
            readonly
          >
            <template slot="prepend">
              <div @click="display = true;" style="width: 29px; height: 29px; display: inline-block; border: 1px solid #949494; border-radius: 4px;" :style="{'background-color': color}"></div>
            </template>
          </v-text-field>
        </template>

        <v-card>
            <v-card-text style="padding: 10px 10px 0">

              <v-color-picker
                v-model="thisColor"
                :mode="mode"
                flat
                :hide-canvas="false"
                :hide-inputs="false"
                :hide-mode-switch="true"
                :show-swatches="false"
              ></v-color-picker>

            </v-card-text>
            <v-card-actions>
              <v-spacer></v-spacer>
              <slot name="actions"
                :parent="this"
              >
                <v-btn color="primary" text @click="okHandler">{{okText}}</v-btn>
                <v-btn color="red" text @click.native="clearHandler">{{clearText}}</v-btn>
                <v-btn color="secondary" text @click.native="display = false">Close</v-btn>
              </slot>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>

<script>
  const DEFAULT_COLOR = '#FFFFFF'

  export default {
    name: 'color-picker',
    model: {
      prop: 'color',
      event: 'input'
    },
    props: {
      color: {
        type: [String],
        default: null
      },
      label: {
        type: String,
        default: ''
      },
      mode: {
        type: String,
        default: 'hexa'
      },
      width: {
        type: Number,
        default: 320
      },
      clearText: {
        type: String,
        default: 'CLEAR'
      },
      okText: {
        type: String,
        default: 'OK'
      },
      disabled: {
        type: Boolean,
        default: false
      },
      loading: {
        type: Boolean,
        default: false
      },
      errorMessages: {
        type: [String, Array],
        default: () => []
      },
      errorCount: {
        type: [Number, String],
        default: 1
      },
      error: {
        type: Boolean,
        default: false
      },
      hideDetails: {
        type: Boolean,
        default: false
      },
      appendIcon: {
        type: String
      },
      prependIcon: {
        type: String
      }
    },
    data () {
      return {
        display: false,
        colorSelected: false,
        selectedColor: null
      }
    },
    created () {
      this.selectedColor = this.color || DEFAULT_COLOR
    },
    computed: {
      thisColor: {
        get () {
          return this[this.mode] || this.selectedColor
          //let val = this.selectedColor
          //return val
        },
        set (val) {
          this.colorSelected = true
          this.selectedColor = val[this.mode] || val
        }
      }
    },
    methods: {
      okHandler () {
        this.display = false

        this.$emit('input', this.selectedColor)
      },
      clearHandler () {
        this.display = false

        this.$emit('input', null)
      }
    }
  }
</script>