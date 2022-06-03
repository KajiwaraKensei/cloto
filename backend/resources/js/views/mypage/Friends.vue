<template>
  <v-container>
    <v-tabs color="#f6bf00">
      <v-tab class="font-weight-bold" @click="showTrueFriends()">友達一覧</v-tab>
      <v-tab class="font-weight-bold" @click="showWantFriends()">友達申請一覧</v-tab>
    </v-tabs>

    <v-list v-if="friends.length">
      <v-list-item
        v-for="friend in friends"
        :key="friend.id"
        @click="$store.dispatch('dialog/open', { type: 'user', username: friend.username })"
      >
        <v-list-item-avatar>
          <v-img :src="$storage('icon') + friend.icon"></v-img>
        </v-list-item-avatar>

        <v-list-item-content>
          <v-list-item-title>
            {{ friend.handlename }}
            <small>@{{ friend.username }}</small>
          </v-list-item-title>
          <v-list-item-subtitle>
            {{ friend.introduction || '自己紹介が入力されていません。' }}
          </v-list-item-subtitle>
        </v-list-item-content>
      </v-list-item>
    </v-list>
    <p v-else>まだ誰もいないようです。</p>
  </v-container>
</template>

<script>
export default {
  head: {
    title() {
      return {
        inner: '友達一覧',
      };
    },
  },
  data() {
    return {
      friends: [], // 友達/友達申請一覧
    };
  },
  computed: {
    authUser() {
      return this.$store.getters['auth/user'];
    },
  },
  methods: {
    /**
     * 友達登録一覧の表示
     */
    showTrueFriends: async function () {
      this.friends = [];
      let response1 = await axios.get('/api/users/' + this.authUser.id + '/friends');
      let response2 = await axios.get('/api/users/' + this.authUser.id + '/maybeFriends');
      let data1 = [...response1.data]
      let data2 = [...response2.data]
      data1.forEach((arr1) => {
        data2.forEach((arr2) => {
          if(arr1.id===arr2.id){
            this.friends.push(arr1)
          }
        });
      });
    },
    /**
     * 友達かも一覧の表示
     */
    showWantFriends: async function () {
      this.friends = [];
      let response1 = await axios.get('/api/users/' + this.authUser.id + '/friends');
      let response2 = await axios.get('/api/users/' + this.authUser.id + '/maybeFriends');
      let data1 = [...response1.data]
      let data2 = [...response2.data]
      let flag = false;
      data2.forEach((arr1) => {
        flag = false;
        data1.forEach((arr2) => {
          if(arr1.id===arr2.id){
            flag = true;
          }
        });
        if(!flag){
          this.friends.push(arr1)
        }
      });
    },
  },
  created() {
    this.showTrueFriends();
  },
};
</script>
