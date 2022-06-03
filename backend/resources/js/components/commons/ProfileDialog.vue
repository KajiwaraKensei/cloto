<template>
  <v-dialog v-model="dialog" @click:outside="close()" width="1400">
    <v-card :color="color" dark v-if="user">
      <v-container>
        <v-row justify="end">
          <v-btn fab x-small depressed color="error" class="mr-4" @click="close()">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </v-row>

        <v-container class="d-flex">
          <v-row class="text-center" justify="center">
            <v-col class="pr-0" align-self="center">
              <v-avatar size="100"><img :src="$storage('icon') + user.icon" /></v-avatar>
              <v-row class="text-h5 mt-2" justify="center">{{ user.handlename }}</v-row>
              <v-row class="text-body-2" justify="center">{{ '@' + user.username }}</v-row>
              <v-row class="mt-2 text-body-2" justify="center">
                登録日：{{ $moment(user.created_at).format('YYYY年MM月DD日') }}
              </v-row>
              <v-row class="mt-2 text-body-2" justify="center">
                累計着席時間：{{ Math.floor(user.sitting_time / 60) }}時間
              </v-row>
              <v-row class="mt-2 text-body-2" justify="center">
                <v-icon class="mr-1 text-body-2">mdi-star</v-icon>{{ user.given_stars_count }}
              </v-row>

              <v-row class="mt-3" justify="center" v-if="user.sns || user.web">
                <v-btn
                  icon
                  color="#00acee"
                  :href="'https://twitter.com/' + user.sns.twitter"
                  target="_blank"
                  v-if="user.sns.twitter"
                >
                  <v-icon>mdi-twitter</v-icon>
                </v-btn>

                <v-btn
                  icon
                  color="#000000"
                  :href="'https://github.com/' + user.sns.github"
                  target="_blank"
                  v-if="user.sns.github"
                >
                  <v-icon>mdi-github</v-icon>
                </v-btn>

                <v-btn
                  icon
                  :href="'https://qiita.com/' + user.sns.qiita"
                  target="_blank"
                  v-if="user.sns.qiita"
                >
                  <v-avatar size="20" color="white">
                    <v-img :src="$storage('system') + 'qiita.png'"></v-img>
                  </v-avatar>
                </v-btn>

                <v-btn icon color="#ffffff" :href="user.web" target="_blank" v-if="user.web">
                  <v-icon>mdi-home</v-icon>
                </v-btn>
              </v-row>

              <v-row class="mt-3" justify="center" v-if="user.id !== authUser.id">
                <v-btn
                  :color="!user.following ? 'primary' : 'error'"
                  :loading="followLoading"
                  @click="follow()"
                >
                  {{ !user.following ? 'フォロー' : 'フォロー解除' }}
                </v-btn>
                <v-btn
                  :color="user.friended ? 'error' : 'primary'"
                  :loading="friend1Loading"
                  @click="friend()"
                >
                  {{ user.friending ? (user.friended ? '友達をやめる' : '友達申請承諾') : (user.friended ? '申請取消' : '友達申請') }}
                </v-btn>
                <v-btn
                  v-if="user.friending && !user.friended"
                  :color="'error'"
                  :loading="friend2Loading"
                  @click="notFriend()"
                >
                  申請拒否
                </v-btn>
              </v-row>

              <p class="mt-3 mb-0" v-if="user.followed">フォローされています</p>
              <p class="mt-3 mb-0">
                {{ user.friending ? (user.friended ? '友達です' : '友達申請されています') : (user.friended ? '友達申請中です' : '') }}
              </p>
              <v-card
                light
                flat
                class="mt-3 mr-2 overflow-y-auto"
                width="240"
                min-height="240"
                max-height="400"
              >
                <pre class="ma-3 text-body-1 text-left"
                  >{{ user.introduction ? user.introduction : '自己紹介が未記入です' }}
              </pre
                >
              </v-card>
            </v-col>
          </v-row>

          <v-row justify="center">
            <v-card light flat width="90%" class="mx-6 mt-2 text-center">
              <v-card-text class="pt-3 pl-3 pb-0 black--text">目標</v-card-text>

              <pre class="ma-3 text-body-1">{{ user.vision || 'がんばる' }}</pre>
            </v-card>
            <v-card light flat width="90%" class="mx-6 mt-2 text-center">
              <v-card-text class="pt-3 pl-3 pb-0 black--text"
                >いまやっていること
                <small v-if="user.seat">in {{ user.room.name }}</small>
                <small v-else>未着席</small>
              </v-card-text>

              <pre class="ma-3 text-body-1">{{ user.in_progress || '集中しています！' }}</pre>
            </v-card>

            <!-- タグ別のカルテ数の表示を行うグラフ -->
            <v-card light flat width="90%" class="mx-6 mt-2 text-center">
              <bar-chart :height="90" :graph-data="graphData" v-if="graphShow"></bar-chart>
            </v-card>

            <!-- 日別のカルテ数の表示を行うグラフ -->
            <v-card light flat width="90%" class="mx-6 mt-2 p-2">
              <heatmap :map-data="graphData" v-if="graphShow"></heatmap>
            </v-card>
          </v-row>
        </v-container>

        <v-row class="my-6" justify="center">
          <v-spacer></v-spacer>

          <v-col md="3" class="select pa-0" @click="showFollows()">
            <v-card class="py-4" :color="color" :elevation="show === 'follow' ? 1 : 3">
              <p class="text-center">フォロー</p>
              <p class="text-center mb-0">{{ user.follows_count }}</p>
            </v-card>
          </v-col>

          <v-spacer></v-spacer>

          <v-col md="3" class="select pa-0" @click="showFollowers()">
            <v-card class="py-4" :color="color" :elevation="show === 'follower' ? 1 : 3">
              <p class="text-center">フォロワー</p>
              <p class="text-center mb-0">{{ user.followers_count }}</p>
            </v-card>
          </v-col>

          <v-spacer></v-spacer>

          <v-col md="3" class="select pa-0" @click="showTrueFriends()">
            <v-card class="py-4" :color="color" :elevation="show === 'friends' ? 1 : 3">
              <p class="text-center">友達</p>
              <p class="text-center mb-0">{{ friendCount }}</p>
            </v-card>
          </v-col>

          <v-spacer></v-spacer>

          <v-col md="3" class="select pa-0" @click="showKartes()">
            <v-card class="py-4" :color="color" :elevation="show === 'karte' ? 1 : 3">
              <p class="text-center">カルテ</p>
              <p class="text-center mb-0"><v-icon>mdi-chevron-down</v-icon></p>
            </v-card>
          </v-col>

          <v-spacer></v-spacer>
        </v-row>

        <!-- フォロー/フォロワー一覧 -->
        <v-list
          :color="color"
          v-if="(show === 'follow' || show === 'follower') && followers.length"
        >
          <v-list-item
            v-for="follower in followers"
            :key="follower.id"
            @click="$store.dispatch('dialog/open', { type: 'user', username: follower.username })"
          >
            <v-list-item-avatar>
              <v-img :src="$storage('icon') + follower.icon"></v-img>
            </v-list-item-avatar>

            <v-list-item-content>
              <v-list-item-title>
                {{ follower.handlename }}
                <small>@{{ follower.username }}</small>
              </v-list-item-title>
              <v-list-item-subtitle>
                {{ follower.introduction || '自己紹介が入力されていません。' }}
              </v-list-item-subtitle>
            </v-list-item-content>
          </v-list-item>
        </v-list>

        <v-list
          :color="color"
          v-if="(show === 'follow' || show === 'follower') && !followers.length"
        >
          <v-list-item>
            <span class="mx-auto">誰よりも早くフォローしよう！</span>
          </v-list-item>
        </v-list>

        <!-- カルテ一覧 -->
        <v-list :color="color" v-if="show === 'karte' && kartes.length">
          <v-list-item
            v-for="karte in kartes"
            :key="karte.id"
            @click="$store.dispatch('dialog/open', { type: 'karte', id: karte.id })"
          >
            <v-img
              max-width="80"
              height="40"
              contain
              class="mr-4 my-2"
              :src="karte.path + karte.image"
              v-if="karte.image"
            ></v-img>
            <v-sheet
              color="grey lighten-2"
              width="80"
              height="40"
              class="mr-4 my-2"
              v-else
            ></v-sheet>

            <v-list-item-content class="text-truncate">
              {{ karte.body }}
            </v-list-item-content>
          </v-list-item>
        </v-list>

        <v-list :color="color" v-if="show === 'karte' && !kartes.length">
          <v-list-item>
            <span class="mx-auto">まだカルテがないようです．．．</span>
          </v-list-item>
        </v-list>
      </v-container>
    </v-card>
  </v-dialog>
</template>

<script>
import BarChart from './ProfileDialog/BarChart.vue';
import Heatmap from './ProfileDialog/Heatmap.vue';

export default {
  components: {
    BarChart,
    Heatmap,
  },
  data() {
    return {
      dialog: false,
      friend1Loading: false, // ローディング制御
      friend2Loading: false, // ローディング制御
      followLoading: false, // ローディング制御
      user: null, // 表示するプロフィール
      show: null, // フォロー/フォロワーどちらを表示するか
      followers: [], // フォロー/フォロワー一覧
      kartes: [], // カルテ一覧
      graphShow: true, //カルテ別の割合、日別のカルテ数を表すグラフの表示の有無
      friendCount:null,
    };
  },

  computed: {
    authUser() {
      return this.$store.getters['auth/user'];
    },

    username() {
      return this.$store.getters['dialog/username'];
    },

    color() {
      return '#9e9e9e';
      // return this.$classColor(this.user.roadmaps.length ? this.user.roadmaps[0].in_progress : '');
    },

    /**
     * ユーザーのカルテのデータを子コンポーネントへ渡す
     */
    graphData: async function () {
      // ユーザーのカルテのに関するデータの取得。
      const graphData = await this.getGraphData();

      return graphData;
    },
  },
  watch: {
    username: function (username) {
      // 値がセットされたらオープン
      if (username) {
        this.open();
      }
    },
  },

  methods: {
    /**
     * ダイアログのオープン
     */
    open: async function () {
      await this.getUser();
      this.dialog = true;
      // タグ別のカルテの割合、日別のカルテ数のグラフの生成
      this.graphShow = true;
    },

    /**
     * ダイアログのクローズ
     */
    close: function () {
      // タグ別のカルテの割合、日別のカルテ数のグラフの削除。これをしないと、他のユーザーのプロフィールを開いたときに、前のユーザーのデータがグラフに残るため
      this.graphShow = false;
      this.dialog = false;
      this.show = null;
      this.$store.dispatch('dialog/close', 'user');
    },

    /**
     * ユーザーデータの取得
     */
    getUser: async function () {
      let response = await axios.get('/api/users/' + this.username);
      this.user = response.data;
      this.showTrueFriends();
      
      // フォロー一覧などから新たなユーザーのグラフのデータを表示する際のデータの初期化
      this.graphShow = false;
      // フォロー一覧などから新たなユーザーのフォロー・フォロワー一覧を見る際に、それまで見ていたユーザーの情報を残さないために一覧を非表示にする
      this.show = null;
    },

    /**
     * フォロー処理
     */
    follow: async function () {
      this.followLoading = true;

      let response = await axios.post('/api/users/' + this.user.id + '/follow');
      this.user = response.data;

      this.followLoading = false;
    },

    /**
     * フォロー一覧の表示
     */
    showFollows: async function () {
      let response = await axios.get('/api/users/' + this.user.id + '/follows');
      this.followers = response.data;
      this.show = 'follow';
    },

    /**
     * フォロワー一覧の表示
     */
    showFollowers: async function () {
      let response = await axios.get('/api/users/' + this.user.id + '/followers');
      this.followers = response.data;
      this.show = 'follower';
    },

    /**
     * 友達追加処理
     */
    friend: async function () {
      this.friend1Loading = true;
      let response = await axios.post('/api/users/' + this.user.id + '/friend');
      this.user = response.data;

      this.friend1Loading = false;
    },
    /**
     * 友達拒否処理
     */
    notFriend: async function () {
      this.friend2Loading = true;
      await axios.delete('/api/users/' + this.user.id + '/notFriend');
      await this.getUser();
      this.friend2Loading = false;
    },
    /**
     * 友達登録一覧の表示
     */
    showTrueFriends: async function () {
      this.friendCount = 0;
      let response1 = await axios.get('/api/users/' + this.authUser.id + '/friends');
      let response2 = await axios.get('/api/users/' + this.authUser.id + '/maybeFriends');
      let data1 = [...response1.data]
      let data2 = [...response2.data]
      data1.forEach((arr1) => {
        data2.forEach((arr2) => {
          if(arr1.id===arr2.id){
            this.friendCount += 1;
          }
        });
      });
    },
    

    /**
     * カルテの取得
     */
    getGraphData: async function () {
      let response = await axios.get('/api/graph/user/' + this.user.id);
      return response.data;
    },

    /**
     * カルテ一覧の表示
     */
    showKartes: async function () {
      let response = await axios.get('/api/kartes/user/' + this.user.id);
      this.kartes = response.data;
      this.show = 'karte';
    },
  },
};
</script>

<style lang="scss" scoped>
a:hover {
  text-decoration: none;
}

pre {
  white-space: pre-wrap;
}

.select {
  cursor: pointer;
}
</style>
