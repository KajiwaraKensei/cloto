<template>
  <v-container id="friend-chat">
    <div class="chat-main" ref="chatList">
      <ChatListComponent v-if="!!user" :chatList="chatList" />
      <div class="no-select-chat" v-else>ユーザーを選択してください</div>
    </div>
    <ChatInputComponent v-if="!!user" :chatList="chatList" @submit-chat="handleSubmitChat" />
  </v-container>
</template>

<script>
import { ChatListComponent } from './ChatList';
import { ChatInputComponent } from './Input.vue';
import { PostMessage } from '@/utils/postMessage';
import { getMessage, shapingMessages } from '@/utils/postMessage';
export const FriendChatComponent = {
  components: {
    ChatListComponent,
    ChatInputComponent,
  },
  props: {
    user: [Object, undefined],
  },
  data() {
    return {
      chatList: [], // 友達/友達申請一覧
    };
  },
  watch: {
    chatList: function () {
      this.scrollToEnd();
    },
    user: async function () {
      const res = await getMessage(this.authUser.id, this.user.id);
      this.chatList = shapingMessages(res.data || [], this.authUser, this.user);
    },
    header: async function () {
      console.log(this.header);
    },
  },
  computed: {
    authUser() {
      return this.$store.getters['auth/user'];
    },
    header() {
      return this.$store.getters['alert/eventType'];
    },
  },
  mounted() {
    this.$store.subscribe(
      function (mutation, state) {
        console.log(mutation.type, state.alert.eventType?.user_id, this.user?.id);
        if (
          mutation.type === 'alert/setEventType' &&
          state.alert.eventType?.user_id === this.user.id
        ) {
          this.chatList = [
            ...this.chatList,
            ...shapingMessages([state.alert.eventType], this.authUser, this.user),
          ];
        }
      }.bind(this)
    );
  },
  methods: {
    async handleSubmitChat(text) {
      await PostMessage(this.authUser.id, this.user.id, text);
      this.chatList = [
        ...this.chatList,
        {
          id: new Date().toLocaleString(),
          type: 'right',
          mode: 'text',
          user: this.authUser,
          content: text,
          ts: Date.now(),
        },
      ];
    },
    scrollToEnd() {
      this.$nextTick(() => {
        const chatLog = this.$refs.chatList;
        if (!chatLog) return;
        chatLog.scrollTop = chatLog.scrollHeight;
      });
    },
  },
};

export default FriendChatComponent;
</script>

<style lang="scss" scoped>
#friend-chat {
  display: flex;
  flex-direction: column;
  height: 100%;
  position: relative;
  .chat-main {
    flex: 0 1 100%;
    background: #fff;
    overflow-y: scroll;
    height: 100%;
  }
  .no-select-chat {
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100%;
    width: 100%;
  }
}
#talk-chat-input {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
}
</style>