<template>
  <data-table
    model="App\User"
  >
    <template v-slot:empty-head>
      <v-icon size="72" :color="$store.getters.app.color_name">people</v-icon>
    </template>

    <template v-slot:empty-text>
      <p class="subheading">Users will appear in this list when they sign up.</p>
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