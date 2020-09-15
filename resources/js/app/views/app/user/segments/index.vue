<template>
  <data-table
    model="Platform\Models\Segment"
  >
    <template v-slot:empty-head>
      <v-icon size="72" :color="$store.getters.app.color_name">category</v-icon>
    </template>

    <template v-slot:empty-text>
      <p class="subheading">Segments allow you to gain insight into the behaviour of your customers. When a customer claims loyalty points or redeems a reward, a staff member can select one or more segments. This can be any label relevant to your business.</p>
      <p class="subheading">For example, you can have segments such as "Vegetarian", "Male" or "Female", or a location. The use of segments is optional, and segments are not visible to customers.</p>
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