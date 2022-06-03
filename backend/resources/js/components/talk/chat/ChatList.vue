<template>
  <div id="chat-list">
    <div v-for="chat in chatList" :key="chat.id">
      <TextMessageComponent :chat="chat" />
    </div>
  </div>
</template>

<script>
import { TextMessageComponent } from './TextMessage.vue';

export const ChatListComponent = {
  components: {
    TextMessageComponent,
  },
  props: {
    chatList: Array,
  },
};

export default ChatListComponent;
</script>

<style lang="scss" scoped>
#chat-list {
  margin-bottom: 12rem;
}
</style>