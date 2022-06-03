/**
 * 友達の取得
 * @param userId {string | number} 友達を取得したいユーザーId
 * @returns 友達一覧
 */
export const showFriends = async function (userId) {
  const friends = [];
  const friendsResponse = await axios.get('/api/users/' + userId + '/friends');
  const maybeFriendsResponse = await axios.get('/api/users/' + userId + '/maybeFriends');
  friendsResponse.data.forEach((friend) => {
    maybeFriendsResponse.data.forEach((maybeFriend) => {
      friend.id === maybeFriend.id && friends.push(friend);
    });
  });
  return friends;
};
