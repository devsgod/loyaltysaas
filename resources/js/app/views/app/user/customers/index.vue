<template>
  <data-table
    model="App\Customer"
  >
    <template v-slot:empty-head>
      <v-icon size="72" :color="$store.getters.app.color_name">contacts</v-icon>
    </template>

    <template v-slot:empty-text>
      <p class="subheading">Customers will appear in this list when they sign up for a <router-link :to="{name: 'user.campaigns'}">campaign</router-link>.</p>
    </template>

  </data-table>
</template>
<script>
  export default {
    data() {
      return {
      }
    },
    methods: {
    }
  }
</script>