<template>
  <div>
    <v-container grid-list-lg fluid>
      <v-layout row wrap>
        <v-flex>
          <v-container style="max-width: 990px" grid-list-lg>
            <v-layout row wrap>
              <v-flex xs12>
                <transition enter-active-class="fadeIn">
                  <div v-show="animateTransition" style="animation-duration: 0.6s" class="body-1" v-html="disclaimer"></div>
                </transition>
              </v-flex>
            </v-layout>
          </v-container>
        </v-flex>
      </v-layout>
    </v-container>
    <v-container grid-list-lg fluid :class="website.theme.primary" class="pa-0" style="position: relative; height: 300px" v-show="animateTransition">
      <svg version="1.1" x="0px" y="0px" viewBox="0 0 2979 108" style="enable-background:new 0 0 2979 108;display:inline-block;height:110px;position: absolute; left:0 ;top: -10px" xml:space="preserve">
        <polyline :fill="website.theme.backgroundColor" points="2979,0 0,108 0,0 "/>
      </svg>
    </v-container>
  </div>
</template>
<script>
  export default {
    mounted () {
      console.log(this.account.app_name)
      axios
        .get('/get/site/terms?company=' + encodeURIComponent(this.account.app_name))
        .then(response => {
          this.disclaimer = response.data
          this.animateTransition = true
        })
    },
    data () {
      return {
        locale: 'en',
        animateTransition: false,
        disclaimer: null
      }
    },
    computed: {
      account () {
        return this.$store.state.app.account
      },
      website () {
        return this.$store.state.app.website
      }
    }
  }
</script>
<style scoped>
</style>