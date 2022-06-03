<template>
  <div id="friend-list">
    <UserListComponent :users="users" :emphasis="selectUser" @click-user="handleClickUser" />
    <FriendChatComponent :user="selectUser" />
  </div>
</template>

<script>
import { UserListComponent } from '@/components/mypage/UserList';
import { showFriends } from '@/utils/friends';
import { FriendChatComponent } from './chat/FriendChat.vue';
export const FriendListComponent = {
  components: {
    UserListComponent,
    FriendChatComponent,
  },
  data() {
    return {
      users: null, // 友達/友達申請一覧
      selectUser: null,
    };
  },
  methods: {
    handleClickUser(user) {
      this.selectUser = user;
    },
  },
  computed: {
    authUser() {
      return this.$store.getters['auth/user'];
    },
  },

  created() {
    showFriends(this.authUser.id).then((friends) => {
      this.users = friends;
    });
  },
};

export default FriendListComponent;
</script>

<style lang="scss" scoped>
#friend-list {
  width: 100%;
  height: 100%;
  display: flex;
}
#user-list {
  flex: 0 0 20rem;
  background-color: inherit;
  min-height: 100%;
}
#friend-chat {
  flex: 1 0 30rem;
  background-color: inherit;
  padding-left: 0;
  height: calc(100vh - 64px);
}
* {
  box-sizing: border-box;
}
</style>