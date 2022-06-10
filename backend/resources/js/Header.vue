<template>
  <v-app-bar app dark height="64px" v-if="!isSmartphone">
    <v-app-bar-nav-icon @click.stop="$emit('open-drawer')" v-if="authCheck" />

    <router-link :to="{ name: 'index' }">
      <img :src="$storage('system') + 'header_logo.svg'" />
    </router-link>

    <span class="mt-4 mr-3 text-h4 font-weight-bold">β1.2</span>

    <v-btn
      text
      x-large
      class="font-weight-bold"
      href="https://cloto-web.github.io/product.html"
      target="_blank"
    >
      使い方とQ&A
    </v-btn>

    <v-spacer></v-spacer>

    <v-btn
      text
      x-large
      class="font-weight-bold"
      :to="{ name: 'login' }"
      v-if="!authCheck && $route.name !== 'login'"
    >
      ログイン
    </v-btn>

    <v-btn
      depressed
      color="primary"
      :to="{ name: 'room', params: { roomId: authUser.room.id } }"
      v-if="authCheck && authUser.seat && $route.name !== 'room'"
    >
      ルームへ戻る
    </v-btn>

    <!-- ステータス -->
    <v-select
      v-model="status"
      :items="statuses"
      :color="$statusColor(status)"
      :item-color="$statusColor(status)"
      class="ml-9 mt-5"
      @change="updateStatus(status)"
      v-if="authCheck"
    >
      <template v-slot:selection="{ item }">
        <span :class="$statusColor(status) + '--text'">{{ item.text }}</span>
      </template>
    </v-select>

    <!-- 通知音ボタン -->
    <v-btn icon class="mx-6" @click="$store.dispatch('alert/switchSound')" v-if="authCheck">
      <v-icon large>
        {{ $store.getters['alert/isSoundOn'] ? 'mdi-music-note' : 'mdi-music-note-off' }}
      </v-icon>
    </v-btn>

    <!-- 通知一覧ボタン -->
    <div v-if="authCheck">
      <v-menu offset-y>
        <template v-slot:activator="{ on, attrs }">
          <v-badge
            color="#f6bf00"
            offset-x="43"
            offset-y="23"
            :content="unreadNotificationsCount"
            :value="unreadNotificationsCount"
          >
            <v-btn icon v-bind="attrs" v-on="on" class="mr-6">
              <v-icon large>mdi-bell</v-icon>
            </v-btn>
          </v-badge>
        </template>
        <v-list>
          <div v-for="notification in notifications" :key="notification.id">
            <!-- フォロー/友達通知 -->
            <v-list-item
              :style="{
                'background-color': notification.read_at ? '' : 'rgba(246, 191, 0, 0.2)',
              }"
              v-if="
                notification.type === 'UserFollowed' ||
                notification.type === 'UserFriend' ||
                notification.type === 'UserFriendAgree'
              "
              @click="showItem('user', notification.username)"
            >
              <v-list-item-title>
                {{ notification.message }}
              </v-list-item-title>
            </v-list-item>

            <!-- カルテへの通知 -->
            <v-list-item
              :style="{
                'background-color': notification.read_at ? '' : 'rgba(246, 191, 0, 0.2)',
              }"
              v-else-if="
                notification.type === 'KarteCommentPosted' ||
                notification.type === 'KarteFavorited' ||
                notification.type === 'CommentToKarteFavorited'
              "
              @click="showItem('karte', notification.karte_id)"
            >
              <v-list-item-title>
                {{ notification.message }}
              </v-list-item-title>
            </v-list-item>

            <!-- 投稿への通知 -->
            <v-list-item
              :style="{
                'background-color': notification.read_at ? '' : 'rgba(246, 191, 0, 0.2)',
              }"
              v-else-if="
                notification.type === 'PostCommentPosted' ||
                notification.type === 'PostFavorited' ||
                notification.type === 'CommentToPostFavorited'
              "
              @click="showItem('post', notification.post_id)"
            >
              <v-list-item-title>
                {{ notification.message }}
              </v-list-item-title>
            </v-list-item>

            <!-- 質問への通知 -->
            <v-list-item
              :style="{
                'background-color': notification.read_at ? '' : 'rgba(246, 191, 0, 0.2)',
              }"
              v-else-if="notification.type === 'PostQuestion'"
              @click="showItem('question', notification.question_id)"
            >
              <v-list-item-title>
                {{ notification.message }}
              </v-list-item-title>
            </v-list-item>

            <v-list-item
              :style="{
                'background-color': notification.read_at ? '' : 'rgba(246, 191, 0, 0.2)',
              }"
              v-if="notification.type === 'PostMessaged'"
              @click="
                showItem('postMessage', {
                  id: notification.user.id,
                })
              "
            >
              <v-list-item-title>
                {{ notification.user.username }}さんからメッセージが届いています
              </v-list-item-title>
            </v-list-item>
          </div>
        </v-list>
      </v-menu>
    </div>
  </v-app-bar>

  <v-app-bar app dark height="64px" v-else>
    <img :src="$storage('system') + 'header_logo.svg'" />
  </v-app-bar>
</template>

<script>
import { NOTIFICATION_SOUND } from '@/consts/sound';

export default {
  data() {
    return {
      status: null, // 現在のステータス
      statuses: [
        // ステータス一覧
        { text: '雑談OK', value: 'free' },
        { text: '自習中', value: 'busy' },
        { text: '離席中', value: 'away' },
      ],
      notifications: [], // 通知一覧
      unreadNotificationsCount: 0, // 未読通知数
    };
  },

  computed: {
    isSmartphone() {
      if (navigator.userAgent.match(/iPhone|Android.+Mobile/)) {
        return true;
      } else {
        return false;
      }
    },

    authCheck() {
      return this.$store.getters['auth/check'];
    },

    authUser() {
      return this.$store.getters['auth/user'];
    },
  },

  watch: {
    authCheck: async function (check) {
      if (check) {
        // ステータスの更新
        await this.updateStatus(this.authUser.status || 'free');
        this.status = this.authUser.status;

        // 通知の取得
        this.getNotifications();

        //質問の通知作成
        Echo.channel('question').listen('NotificationQuestion', (event) => {
          this.notifications.unshift({
            type: 'PostQuestion',
            question_id: event.id,
            message: event.user.handlename + 'さんから質問が届きました。',
          });
          this.unreadNotificationsCount += 1;
        });
        // 通知イベントの受信開始
        Echo.channel('user.' + this.authUser.id).listen('NotificationPosted', (event) => {
          this.notifications = event.notifications;
          this.unreadNotificationsCount = event.unread_notifications_count;

          if (this.$store.getters['alert/isSoundOn']) {
            NOTIFICATION_SOUND.play();
          }
        });
        Echo.channel('user.' + this.authUser.id).listen('PostMessaged', (event) => {
          this.unreadNotificationsCount += 1;
          console.table(event.user);
          this.notifications = [
            {
              type: 'PostMessaged',
              user: event.user,
            },
            ...this.notifications,
          ];
          if (this.$store.getters['alert/isSoundOn']) {
            NOTIFICATION_SOUND.play();
          }
          this.$store.dispatch('alert/postMessage', event.result);
        });
      }
    },
  },

  methods: {
    /**
     * ステータスの更新
     *
     * @param {String} status - ステータス
     */
    updateStatus: async function (status) {
      await axios.post('/api/status/' + status);
      this.$store.dispatch('auth/syncAuthUser');
    },

    /**
     * 通知の取得
     */
    getNotifications: async function () {
      let response = await axios.get('/api/user/notifications');
      this.notifications = response.data.notifications;
      this.unreadNotificationsCount = response.data.unread_notifications_count;
    },

    /**
     * 通知の既読
     */
    markNotificationsAsRead: async function () {
      let response = await axios.post('/api/user/notifications');
      this.notifications = response.data.notifications;
      this.unreadNotificationsCount = response.data.unread_notifications_count;
    },

    /**
     * アイテムの表示
     *
     * @param {String} type - タイプ
     * @param {String|Number} item - 詳細を表示するアイテム
     */
    showItem: function (type, item) {
      if (type === 'user') {
        this.$store.dispatch('dialog/open', { type: type, username: item });
      }
      if (type === 'karte' || type === 'post') {
        this.$store.dispatch('dialog/open', { type: type, id: item });
      }
      if (type === 'question') {
        this.$store.dispatch('dialog/open', { type: type, id: item });
      }
      if (type === 'postMessage') {
        this.$router.push('/mypage/talk/' + item.id);
      }
      this.markNotificationsAsRead();
    },
  },
};
</script>

<style lang="scss" scoped>
img {
  max-height: 64px;
  padding: 10px;
}

a:hover {
  text-decoration: none;
}

.v-select {
  max-width: 150px;

  span {
    margin-left: 10px;
    font-size: 20px;
    font-weight: bold;
  }
}

.v-list {
  padding: 0;
}
</style>
