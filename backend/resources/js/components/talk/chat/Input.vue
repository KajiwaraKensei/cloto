<template>
  <div id="talk-chat-input">
    <form @submit.prevent="submitForm">
      <div class="textarea-wrap">
        <textarea-autosize
          name="text"
          type="text"
          placeholder="Aa"
          v-model="text"
          :min-height="30"
          :max-height="150"
          ref="chat_input"
          autofocus
          @keydown.enter.native="onKeyDown"
        />
      </div>

      <div class="input-footer">
        <div></div>
        <button type="submit">送信</button>
      </div>
    </form>
  </div>
</template>

<script>
export const ChatInputComponent = {
  data() {
    return {
      text: '', // 友達/友達申請一覧
    };
  },
  methods: {
    submitForm() {
      this.$emit('submit-chat', this.text);
      this.text = '';
      this.$refs.chat_input.$el.focus();
    },
    onKeyDown(event) {
      if (event.ctrlKey || event.metaKey) {
        this.submitForm();
      }
    },
  },
};

export default ChatInputComponent;
</script>

<style lang="scss" scoped>
#talk-chat-input {
  display: flex;
  justify-content: center;
  position: sticky;
  bottom: 0;
  width: 100%;
  background-color: #333;
  padding: 0.5rem 0.5rem 0.5rem;

  > * {
    margin-right: 1rem;

    :last-child {
      margin-right: 0;
    }
  }

  form {
    flex: 1 0 20rem;
    max-height: none;
    margin: 0 1rem;
  }

  .textarea-wrap {
    padding: 0.5rem 0.5rem 0.25rem 0.5rem;
    background-color: #fff;
    border-radius: 0.3rem 0.3rem 0rem 0rem;
  }
  textarea {
    width: 100%;
    background-color: #fff;
    color: #666;
    font-size: 0.9rem;
    outline: none;
    border-radius: 0.3rem 0.3rem 0rem 0rem;
  }
  .input-footer {
    display: flex;
    background-color: #fff;
    border-radius: 0rem 0rem 0.3rem 0.3rem;
    padding: 0 0.5rem 0.5rem;
    & > div:first-child {
      flex: 1 1 100%;
      display: flex;
      padding: 0.25rem 0.5rem;
    }
    button[type='submit'] {
      flex: 0 0 auto;
      color: #fff;
      background-color: green;
      border-radius: 0.25rem;
      padding: 0.23rem 0.45rem;
      font-size: 0.83rem;
    }
  }
}
</style>