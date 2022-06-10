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
      selectId: null,
    };
  },
  methods: {
    handleClickUser(user) {
      const pathname = document.location.pathname.split('/');
      const userId = pathname[pathname.length - 1];

      if (userId !== String(user.id)) {
        this.$router.push('/mypage/talk/' + user.id).catch((err) => {});
        this.selectUser = user;
        this.selectId = user.id;
      }
    },
    handlePopstate() {
      const pathname = document.location.pathname.split('/');
      const userId = pathname[pathname.length - 1];

      console.log(userId, this.$route.params.userId);
      userId &&
        this.users.forEach((user) => {
          if (String(user.id) === String(userId)) {
            this.selectUser = user;
            this.selectId = user.id;
          }
        });
    },
  },
  computed: {
    authUser() {
      return this.$store.getters['auth/user'];
    },
  },
  mounted() {
    window.addEventListener('popstate', this.handlePopstate);
    showFriends(this.authUser.id).then((friends) => {
      this.users = friends;
      this.$route.params.userId &&
        this.users.forEach((user) => {
          if (String(user.id) === String(this.$route.params.userId)) {
            this.selectUser = user;
            this.selectId = user.id;
          }
        });
    });
  },

  beforeDestroy() {
    window.removeEventListener('popstate', this.handlePopstate);
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
