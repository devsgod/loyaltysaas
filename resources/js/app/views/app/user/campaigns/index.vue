<template>
  <data-table
    model="Platform\Models\Campaign"
  >
    <template v-slot:empty-head>
      <v-icon size="72" :color="$store.getters.app.color_name">record_voice_over</v-icon>
    </template>

    <template v-slot:empty-text>
      <p class="subheading">A campaign is a website where customers can sign up to earn loyalty points, and exchange points for rewards.</p>
      <p class="subheading">You can test the campaign on a url you get from us, and when you are ready you can launch it on any domain or subdomain you own. For example, a subdomain like <strong>loyalty.mybusiness.com</strong>, or a top-level domain like <strong>mybusinessextras.com</strong>.</p>
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