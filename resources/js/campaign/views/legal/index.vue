<template>
  <v-container grid-list-lg fluid class="pa-5">
    <v-layout row wrap>
      <v-flex class="elevation-1 white pa-5">
        <div class="text-xs-center">
          <v-progress-circular indeterminate class="ma-5" v-if="!disclaimer"></v-progress-circular>
        </div>
        <div v-html="disclaimer"></div>
      </v-flex>
    </v-layout>
  </v-container>
</template>
<script>
  export default {
    name: 'app',
    methods: {
    },
    mounted () {
      axios
        .get('/get/terms?locale=' + encodeURIComponent(this.$i18n.locale) + '&company=' + encodeURIComponent(this.campaign.business.name))
        .then(response => (this.disclaimer = response.data))
    },
    data () {
      return {
        disclaimer: null
      }
    },
    computed: {
      campaign () {
        return this.$store.state.app.campaign
      }
    },
  };
</script>