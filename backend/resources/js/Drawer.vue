<template>
  <v-navigation-drawer app dark temporary :value="value" @input="$emit('input', $event)">
    <v-list-item two-line class="px-3" v-if="authUser">
      <v-list-item-avatar>
        <img :src="$storage('icon') + authUser.icon" />
      </v-list-item-avatar>

      <v-list-item-content>
        <v-list-item-title>{{ authUser.handlename }}</v-list-item-title>
        <v-list-item-subtitle>{{ '@' + authUser.username }}</v-list-item-subtitle>
      </v-list-item-content>
    </v-list-item>

    <v-divider></v-divider>

    <v-list nav>
      <v-list-item exact :to="{ name: 'home' }">
        <v-list-item-icon>
          <v-icon>mdi-home</v-icon>
        </v-list-item-icon>
        <v-list-item-content>
          <v-list-item-title>ホーム</v-list-item-title>
        </v-list-item-content>
      </v-list-item>

      <!-- <v-list-item exact :to="{ name: 'mystudy' }">
            <v-list-item-icon>
              <v-icon>mdi-note-text</v-icon>
            </v-list-item-icon>
            <v-list-item-content>
              <v-list-item-title>マイスタディ</v-list-item-title>
            </v-list-item-content>
          </v-list-item> -->

      <v-list-item exact :to="{ name: 'mypage' }">
        <v-list-item-icon>
          <v-icon>mdi-account</v-icon>
        </v-list-item-icon>
        <v-list-item-content>
          <v-list-item-title>マイページ</v-list-item-title>
        </v-list-item-content>
      </v-list-item>
      <v-list-item-group color="success">
        <v-list-item-icon>
          <v-icon>mdi-AccountSchoolOutline</v-icon>
        </v-list-item-icon>
        <v-tooltip bottom v-for="floor in floors.slice().reverse()" :key="floor.id">
          <template v-slot:activator="{ on, attrs }">
            <v-list-item
              class="mb-4"
              v-bind="attrs"
              v-on="on"
              :to="{ name: 'floor', params: { roomId: floor.id } }"
            >
              <v-list-item-content>
                <v-list-item-title class="text-body-1 font-weight-bold">
                  GROW×VERSE {{ floor.id }}
                </v-list-item-title>
              </v-list-item-content>
            </v-list-item>
          </template>

          <span>{{ floor.userNum }}人在室中</span>
        </v-tooltip>
      </v-list-item-group>
      <div class="pa-2" v-if="$route.name === 'floor'">
        <v-btn
          depressed
          block
          color="#f6bf00"
          dark
          :to="{ name: 'room', params: { roomId: $route.params.roomId } }"
          :disabled="authUser.seat !== null"
        >
          入室
        </v-btn>
      </div>
    </v-list>

    <template v-slot:append>
      <div class="pa-2">
        <v-btn block @click="$emit('logout')">ログアウト</v-btn>
      </div>
    </template>
  </v-navigation-drawer>
</template>

<script>
export default {
  props: {
    // floors: Array,
    value: {
      type: Boolean,
      default: false,
    },
  },
  computed: {
    authCheck() {
      return this.$store.getters['auth/check'];
    },
    authUser() {
      return this.$store.getters['auth/user'];
    },

    value() {
      return this.$store.getters['drawer/isOpen'];
    },
    floors() {
      return this.$store.getters['drawer/floors'];
    },
  },
};
</script>

<style lang="scss" scoped>
a:hover {
  text-decoration: none;
}
</style>
