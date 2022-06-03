<template>
  <v-card class="mx-auto" width="250" flat tile>
    <v-navigation-drawer permanent>
      <v-list>
        <v-list-item color="success" :to="{ name: 'home' }">
          <v-list-item-content>
            <v-list-item-title class="text-h5 font-weight-bold">ホーム</v-list-item-title>
          </v-list-item-content>
        </v-list-item>
      </v-list>
      <v-list>
        <v-list-item color="success" :to="{ name: 'timeline' }">
          <v-list-item-content>
            <v-list-item-title class="text-h5 font-weight-bold">タイムライン</v-list-item-title>
          </v-list-item-content>
        </v-list-item>
      </v-list>
      <!--<v-list>
        <v-list-item color="success" :to="{ name: '' }">
          <v-list-item-content>
            <v-list-item-title class="text-h5 font-weight-bold">トーク</v-list-item-title>
          </v-list-item-content>
        </v-list-item>
      </v-list>
      <v-list>
        <v-list-item color="success" :to="{ name: '' }">
          <v-list-item-content>
            <v-list-item-title class="text-h5 font-weight-bold">グループ</v-list-item-title>
          </v-list-item-content>
        </v-list-item>
      </v-list>-->
      <v-list>
        <v-list-item color="success" :to="{ name: 'mypage' }">
          <v-list-item-content>
            <v-list-item-title class="text-h5 font-weight-bold">プロフィール</v-list-item-title>
          </v-list-item-content>
        </v-list-item>
      </v-list>

    </v-navigation-drawer>
  </v-card>
</template>

<script>
export default {
  props: {
    floors: Array,
  },
  computed: {
    authUser() {
      return this.$store.getters['auth/user'];
    },
  },
};
</script>

<style lang="scss" scoped>
a:hover {
  text-decoration: none;
}
</style>
