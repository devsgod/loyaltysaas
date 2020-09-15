<template>
    <v-snackbar
      v-model="snackbar"
      :bottom="y === 'bottom'"
      :left="x === 'left'"
      :multi-line="mode === 'multi-line'"
      :right="x === 'right'"
      :timeout="timeout"
      :top="y === 'top'"
      :vertical="mode === 'vertical'"
      v-bind:style="{ zIndex: options.zIndex }"
    >
      {{ text }}
      <v-btn
        :color="options.color"
        text icon
        @click="snackbar = false"
      >
        <v-icon>close</v-icon>
      </v-btn>
    </v-snackbar>
</template>
<script>
  /**
   * Vuetify Snackbar component
   *
   * Insert component where you want to use it:
   * <snackbar ref="snackbar"></snackbar>
   *
   * Call it:
   * this.$refs.snackbar.show('Item is deleted', { color: 'red' })
   *
   * Alternatively you can place it in main App component and access it globally via this.$root.$confirm
   * <template>
   *   <v-app>
   *     ...
   *     <snackbar ref="snackbar"></snackbar>
   *   </v-app>
   * </template>
   *
   * mounted() {
   *   this.$root.$snackbar = this.$refs.snackbar.show
   * }
   */
  export default {
    data: () => ({
      snackbar: false,
      text: null,
      y: 'bottom',
      x: 'left',
      mode: '',
      timeout: 6000,
      options: {
        color: 'white',
        zIndex: 100000
      }
    }),
    methods: {
      show(text, options) {
        this.snackbar = true
        this.text = text
        this.timeout = 6000
        this.options = Object.assign(this.options, options)
      }
    }
  }
</script>