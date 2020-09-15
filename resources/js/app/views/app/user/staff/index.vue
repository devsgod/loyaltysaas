<template>
  <data-table
    model="App\Staff"
  >
    <template v-slot:empty-head>
      <v-icon size="72" :color="$store.getters.app.color_name">work</v-icon>
    </template>

    <template v-slot:empty-text>
      <p class="subheading">Staff members are linked to a business. They have access to the staff application, and can give customers loyalty points and redeem rewards.</p>
      <p class="subheading">They can do this for the loyalty campaigns that are linked to the business(es) to which they are linked.</p>
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