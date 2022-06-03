<template>
  <div
    id="text-message"
    :class="{
      'text-message-left': chat.type === 'left',
      'text-message-right': chat.type === 'right',
    }"
  >
    <v-list-item-avatar class="content-img">
      <v-img :src="$storage('icon') + chat.user.icon"></v-img>
    </v-list-item-avatar>
    <v-list-item-content class="content-text">
      <small>{{ chat.content }}</small>
    </v-list-item-content>
    <v-list-item-content class="content-date">
      <small>{{ getTimeStamp }}</small>
    </v-list-item-content>
  </div>
</template>

<script>
import { getTime } from '@/utils/date.js';
export const TextMessageComponent = {
  props: {
    chat: Object,
  },
  computed: {
    getTimeStamp: function () {
      return getTime(this.chat.ts);
    },
  },
};

export default TextMessageComponent;
</script>

<style lang="scss" scoped>
#text-message {
  display: flex;
  align-items: flex-start;
}

.content-img,
.content-date {
  align-self: flex-start;
}

.content-text {
  max-width: 20rem;
  display: inline-block;
  flex: 0 1 auto;
  box-shadow: 3px 3px 5px 0px rgb(51 51 51 / 22%);
  border-radius: 0.3rem;
  padding: 0.5rem 1rem;
  word-break: break-word;
  white-space: pre-wrap;
}

.content-date {
  flex: 0 0 3rem;
  font-size: 0.8rem;
  text-align: center;
}

.text-message-right {
  .v-avatar {
    margin-left: 10px;
    margin-right: 0px;
  }
  flex-direction: row-reverse;
}
</style>