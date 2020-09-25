<template>
  <div class="w-full flex flex-wrap -mx-2">
    <div v-for="field in config.fields" class="w-1/4 px-2 mb-2">
      <label class="block capitalize mb-1">{{ field.display || field.handle }}</label>
      <v-select
          class="w-full"
          :options="config.keys"
          :value="mapping[field.handle]"
          @input="(value) => setMapping(field.handle, value)"
      />
      <input type="hidden" :name="'mapping[' + field.handle + ']'" :value="mapping[field.handle]">
    </div>
  </div>
</template>

<script>
export default {
  mixins: [ Fieldtype ],

  props: ['config'],

  data: function() {
    return {
      mapping: {}
    };
  },

  mounted() {
    this.config.keys.forEach(key => {
      const field = this.config.fields.filter(field => field.handle === key)[0];

      if (field) {
        this.$set(this.mapping, field.handle, key);
      }
    })
  },

  methods: {
    setMapping: function (handle, value) {
      this.$set(this.mapping, handle, value);
    }
  }
}
</script>
