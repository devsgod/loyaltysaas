<template>
  <data-table
    model="Platform\Models\Reward"
  >
    <template v-slot:empty-head>
      <v-icon size="72" :color="$store.getters.app.color_name">fas fa-gift</v-icon>
    </template>

    <template v-slot:empty-text>
      <p class="subheading">Customers can use their earned loyalty points to redeem a reward. A reward can be linked to one or more campaigns.</p>
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