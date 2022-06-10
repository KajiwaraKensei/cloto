import axios from 'axios';

export function PostMessage(from, to, text) {
  return axios.post('/api/post_message', {
    type: 'text',
    user_id: from,
    to,
    content: text,
  });
}

export function getMessage(from, to) {
  return axios.get('/api/post_message', {
    params: {
      user_id: from,
      to,
    },
  });
}

export function shapingMessages(messages, me, to) {
  const next = [];
  messages.forEach((message) => {
    next.push({
      id: message.id,
      type: me.id === message.user_id ? 'right' : 'left',
      mode: message.type,
      content: message.content,
      ts: message.updated_at,
      user: me.id === message.user_id ? me : to,
    });
  });
  return next;
}
