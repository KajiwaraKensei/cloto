<template>
  <v-app>
    <!-- オフライン時の操作無効化用オーバーレイ -->
    <v-overlay z-index="9999" opacity="0.9" v-if="isOffline">
      <h1 class="font-weight-bold">インターネットに接続してください。</h1>
    </v-overlay>

    <!-- アラート -->
    <v-alert
      :value="alert.show"
      :type="alert.type"
      dark
      dense
      dismissible
      transition="scale-transition"
    >
      {{ alert.message }}
      <template v-slot:close>
        <v-icon class="ml-2" @click="$store.dispatch('alert/hide')">mdi-close-circle</v-icon>
      </template>
    </v-alert>

    <!-- メッセージオーバーレイ-->
    <v-overlay
      z-index="9997"
      opacity="0.9"
      :value="alert.overlay.show"
      :color="alert.overlay.color"
      dark
    >
      <p class="text-h1 text-center mb-0">{{ alert.overlay.message }}</p>
      <p class="text-h5 text-center mb-0 mt-12" v-if="alert.overlay.description">
        {{ alert.overlay.description }}
      </p>
    </v-overlay>

    <!-- アナウンスオーバーレイ-->
    <v-overlay z-index="9996" opacity="0.9" :value="announce !== ''" color="primary">
      <v-container>
        <p class="text-h4 text-center mb-12">{{ announce }}</p>
        <v-row justify="center">
          <v-btn @click="announce = ''">閉じる</v-btn>
        </v-row>
      </v-container>
    </v-overlay>

    <!-- ヘッダー -->
    <Header @open-drawer="isOpenDrawer = true" />

    <!-- ドロワーメニュー -->
    <Drawer v-model="isOpenDrawer" @logout="logout" />

    <!-- メイン -->
    <v-main id="main">
      <router-view />
    </v-main>

    <!-- フッター -->
    <Footer @logout="logout" />

    <!-- 問い合わせ -->

    <!-- チュートリアル -->
    <Tutorial v-if="authCheck" />

    <!-- データ詳細ダイアログ -->
    <ProfileDialog />
    <KarteDialog />
    <PostDialog />
    <QuestionDialog />
  </v-app>
</template>

<script>
import Header from './Header';
import Drawer from './Drawer';
import Footer from './Footer';
import Inquiry from './Inquiry';
import Tutorial from './Tutorial';
import ProfileDialog from '@/components/commons/ProfileDialog';
import KarteDialog from '@/components/commons/KarteDialog';
import PostDialog from '@/components/commons/PostDialog';
import QuestionDialog from '@/components/commons/QuestionDialog';
import { OK, NOT_FOUND, UNPROCESSABLE_ENTITY, INTERNAL_SERVER_ERROR } from '@/consts/status';
import { ANNOUNCE_SOUND } from '@/consts/sound';

export default {
  components: {
    Header,
    Drawer,
    Footer,
    Inquiry,
    Tutorial,
    ProfileDialog,
    KarteDialog,
    PostDialog,
    QuestionDialog,
  },
  data() {
    return {
      issuedTabId: false, // 複数タブ制御フラグ
      isOffline: false, // オフライン状態
      setOnlineTimer: null, // オンライン状態の通知制御
      //isOpenDrawer: false, // ドロワーメニューの表示制御
      announce: '', // アナウンス内容
    };
  },
  computed: {
    isDebug() {
      return process.env.MIX_APP_DEBUG === 'true' ? true : false;
    },
    alert() {
      return this.$store.state.alert;
    },
    authCheck() {
      return this.$store.getters['auth/check'];
    },
    authUser() {
      return this.$store.getters['auth/user'];
    },
    isOpenDrawer: {
      get() {
        return this.$store.getters['drawer/isOpen'];
      },
      set(value) {
        console.log(value);
        if (this.$store.getters['drawer/isOpen'] !== value)
          this.$store.dispatch('drawer/event', value);
      },
    },
  },
  watch: {
    authCheck: function (check) {
      // ログイン時の処理
      if (check) {
        // ログアウトの検知
        axios.interceptors.response.use((error) => {
          if (error.status === 401) {
            alert('ログアウトされました。');
            window.location.pathname = '/login';
            throw new Error(error.status);
          }

          return error;
        });

        // ログイン中は5分毎にオンライン状態を通知
        this.setOnlineTimer = setInterval(() => {
          axios.post('/api/status');
        }, 300000);
      }
    },
    'authUser.room': async function (newRoom, oldRoom) {
      if (newRoom != null && oldRoom == null) {
        /**
         * 着席時
         */
        Echo.channel('room.' + newRoom.id)
          .listen('Announced', (event) => {
            // アナウンスイベントの受信開始
            if (this.$store.getters['alert/isSoundOn']) {
              ANNOUNCE_SOUND.play();
            }
            this.announce = event.message;
          })
          .listen('RoomStatusChanged', (event) => {
            // 時間割イベントの受信開始
            if (event.status === 'study') {
              // 自習時間
              this.$store.dispatch('alert/showOverlay', {
                color: '#ff4500',
                message: '自習時間です！',
                description: '自習室に戻りましょう。',
              });
            } else if (event.status === 'break') {
              // 休憩時間
              this.$store.dispatch('alert/showOverlay', {
                color: '#4169e1',
                message: '休憩時間です！',
                description: '休憩室に移動しましょう。',
              });
            }
          });
      } else if (newRoom == null && oldRoom != null) {
        /**
         * 退席時
         */
        // 部屋イベントの受信終了
        Echo.leave('room.' + oldRoom.id);
      }
    },
  },
  methods: {
    /**
     * 複数タブ操作の禁止
     */
    disallowMultiTabs: function () {
      // タブ単位での記憶
      let sessionTabId = sessionStorage.getItem('tabID');
      // ブラウザ単位での記憶
      let localTabId = localStorage.getItem('tabID');

      if (sessionTabId === null || (sessionTabId !== null && !this.issuedTabId)) {
        // 新規タブのオープン，タブ複製時にIDを発行
        let tabId = this.$moment().format('HHmmssSSS');
        sessionStorage.setItem('tabID', tabId);
        localStorage.setItem('tabID', tabId);
        this.issuedTabId = true;
      } else if (sessionTabId !== localTabId) {
        // タブを無効化
        window.open('about:blank', '_self').close();
      }
    },

    /**
     * イベントの設定
     */
    setupEvents: function () {
      // オフライン時のイベント
      window.addEventListener('offline', this.offlineEvent);

      // オンライン復帰時のイベント
      window.addEventListener('online', this.onlineEvent);

      // 戻るボタンの無効化
      history.pushState(null, null, location.href);
      window.addEventListener('popstate', this.stopBackButtonEvent);

      // 戻るボタンでページに復帰した時のイベント
      window.addEventListener('pageshow', (event) => {
        this.pageBackEvent(event);
      });

      // エラー発生時のイベント
      Vue.config.errorHandler = (error) => {
        this.$slack(
          'エラーBot',
          ':poop:',
          this.authUser.username + '様のデバイスでエラー発生！\n' + '```' + error + '```'
        );
        this.$store.dispatch('alert/error');
      };

      // エラー発生時のイベント
      window.addEventListener('error', (error) => {
        if (error.message !== 'ResizeObserver loop limit exceeded') {
          this.$slack(
            'エラーBot',
            ':poop:',
            this.authUser.username +
              '様のデバイスでエラー発生！\n' +
              '```' +
              error.type +
              ': ' +
              error.message +
              '```'
          );
          this.$store.dispatch('alert/error');
        }
      });

      // エラー発生時のイベント
      window.addEventListener('unhandledrejection', (error) => {
        this.$slack(
          'エラーBot',
          ':poop:',
          this.authUser.username + '様のデバイスでエラー発生！\n' + '```' + error.reason + '```'
        );
        this.$store.dispatch('alert/error');
      });
    },

    /**
     * オフライン時のイベント
     */
    offlineEvent: function () {
      this.isOffline = true;

      if (this.authUser.seat !== null) {
        alert(
          'インターネットに接続してください。5分以上接続が復帰しない場合は自動的に退席します。'
        );
      }
    },

    /**
     * オンライン復帰時のイベント
     */
    onlineEvent: function () {
      if (this.authCheck) {
        if (this.authUser.seat !== null && this.$route.name === 'room') {
          // 着席中の場合はリロード
          alert('接続が復帰しました。再読み込みします。');
          this.$router.go();
        } else {
          // オンライン状態を通知
          axios.post('/api/online');
        }
      }

      this.isOffline = false;
    },

    /**
     * 戻るボタンの無効化
     */
    stopBackButtonEvent: function () {
      this.$store.dispatch('alert/error', '戻るボタンでの操作は禁止されています。');
      this.$router.go(1);
    },

    /**
     * 戻るボタンでページに復帰した時のイベント
     *
     * @param {Event} event
     */
    pageBackEvent: function (event) {
      let historyTraversal =
        event.persisted ||
        (typeof window.performance !== 'undefined' && window.performance.navigation.type === 2);
      if (historyTraversal) {
        // リロード（戻るボタンでアクセスすると休憩室に入室できない）
        this.$router.go();
      }
    },

    /**
     * ログアウト処理
     */
    logout: async function () {
      this.isOpenDrawer = false;

      // ログアウト処理
      await this.$store.dispatch('auth/logout');
      // ログインページへリダイレクト
      window.location.pathname = '/login';
    },
  },
  created() {
    // 複数タブ操作の禁止
    setInterval(this.disallowMultiTabs, 1000);

    // 処理結果アラート表示処理の定義
    axios.interceptors.response.use((response) => {
      if (response.status === OK) {
        // メッセージが存在する場合のみ表示
        if (response.data.message) {
          this.$store.dispatch('alert/success', response.data.message);
        }
      } else if (response.status === NOT_FOUND) {
        // エラー発生時
        this.$store.dispatch('alert/error', 'データが見つかりませんでした。');
      } else if (
        response.status === UNPROCESSABLE_ENTITY ||
        response.status === INTERNAL_SERVER_ERROR
      ) {
        // 新規登録，ログイン時を除く
        if (!response.config.url.match(/register|login/)) {
          // エラー発生時
          this.$store.dispatch('alert/error', response.data.message || null);
        }
      }

      return response;
    });

    // システムイベントの受信開始
    Echo.channel('system').listen('SystemDowned', () => {
      // メンテナンスモード開始
      this.$router.go();
    });
  },
  mounted() {
    // イベントの設定
    if (!this.isDebug) {
      this.setupEvents();
    }
  },
};
</script>

<style lang="scss" scoped>
.v-alert {
  position: fixed;
  z-index: 9998;
  top: 15px;
  left: 50%;
  transform: translateX(-50%);
  -webkit-transform: translateX(-50%);
  -ms-transform: translateX(-50%);
}

#main {
  min-height: 100vh;
}
</style>
