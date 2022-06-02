import axios from 'axios';

export function PostMessage(from, to, text) {
  return axios.post('/api/post_message', {
    type: 'text',
    user_id: from,
    to,
    content: text,
  });
}
