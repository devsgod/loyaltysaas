<template>
  <data-table
    model="Platform\Models\Plan"
  >
    <template v-slot:empty-head>
      <v-icon size="72" :color="$store.getters.app.color_name">credit_card</v-icon>
    </template>

    <template v-slot:empty-text>
      <p class="subheading">Users can subscribe to a plan.</p>
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