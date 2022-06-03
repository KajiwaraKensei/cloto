<template>
  <v-dialog
    v-model="dialog"
    fullscreen
    persistent
    no-click-animation
    transition="dialog-bottom-transition"
  >
    <!-- 権限確認画面 -->
    <v-overlay :value="permissionOverlay" z-index="7" class="text-center" opacity="1">
      <div class="arrow"></div>
      <p class="text-h4">権限を許可してください。</p>
      <p class="text-body-2 mt-6 mr-8">（一度許可した方は必要ありません。）</p>
      <p class="text-h5 mt-12">通話を伴いますのでマイクとカメラを使用します。</p>
      <p class="text-h6 mb-0 mt-6 mr-8">（ミュート、カメラオフで接続されます。）</p>
    </v-overlay>

    <!-- ローディング画面 -->
    <v-overlay :value="loading" z-index="6" class="text-center" opacity="0.9">
      <p class="text-h5 mb-5">接続中</p>
      <v-progress-linear
        indeterminate
        height="10"
        color="green"
        class="progress-linear mb-12"
      ></v-progress-linear>

      <p class="text-body-2 mb-0 mt-12">カメラランプが10秒ほど点灯する場合があります．．．</p>
    </v-overlay>

    <v-layout class="px-2" ref="container">
      <v-flex>
        <v-container fluid py-0>
          <!--*** 画面共有ON ***-->
          <v-row justify="center" v-if="screenSharing.stream">
            <!-- 自分のビデオ -->
            <v-sheet color="rgba(0, 0, 0, 1)" width="208" height="117" class="video ma-2">
              <!-- オフ -->
              <v-sheet
                color="black"
                width="208"
                height="117"
                class="d-flex justify-center align-center"
                v-if="isVideoLoading || isVideoOff || localStream === null"
              >
                <v-avatar size="50">
                  <img :src="$storage('icon') + authUser.icon" />
                </v-avatar>
              </v-sheet>

              <!-- オン -->
              <video
                width="208"
                height="117"
                muted="true"
                autoplay
                :srcObject.prop="localStream"
                v-else
              ></video>

              <p class="handlename" style="font-size: 0.8em">{{ authUser.handlename }}</p>

              <p class="is-mute" v-if="isAudioLoading || isMute">
                <v-icon color="red">mdi-microphone-off</v-icon>
              </p>
            </v-sheet>

            <!-- 参加者のビデオ -->
            <v-hover
              v-slot="{ hover }"
              v-for="participant in notPinnedParticipants"
              :key="participant.peerId"
            >
              <v-sheet
                color="rgba(0, 0, 0, 1)"
                width="208"
                height="117"
                :class="['video', 'ma-2', speakerId === participant.peerId ? 'speaker' : '']"
              >
                <!-- オフ -->
                <v-sheet
                  color="black"
                  width="208"
                  height="117"
                  class="d-flex justify-center align-center"
                  v-if="participant.loading || participant.isVideoOff"
                >
                  <!-- ローディング中 -->
                  <v-progress-circular
                    size="40"
                    width="4"
                    color="green"
                    indeterminate
                    v-if="participant.loading || participant.icon === null"
                  ></v-progress-circular>

                  <v-avatar size="50" v-else>
                    <img :src="$storage('icon') + participant.icon" />
                  </v-avatar>

                  <audio autoplay :srcObject.prop="participant.stream"></audio>
                </v-sheet>

                <!-- オン -->
                <video
                  width="208"
                  height="117"
                  autoplay
                  :srcObject.prop="participant.stream"
                  v-else
                ></video>

                <p class="handlename" style="font-size: 0.8em">{{ participant.handlename }}</p>

                <p class="is-mute" v-if="participant.isMute">
                  <v-icon color="red">mdi-microphone-off</v-icon>
                </p>

                <!-- hover -->
                <v-fade-transition>
                  <v-overlay absolute opacity="0.7" v-if="hover">
                    <v-sheet color="rgba(0, 0, 0, 0)" width="208" height="117">
                      <v-btn icon x-large class="pin-button" @click="pin(participant)">
                        <v-icon> mdi-pin </v-icon>
                      </v-btn>

                      <v-btn
                        icon
                        x-large
                        class="account-button"
                        @click="
                          $store.dispatch('dialog/open', {
                            type: 'user',
                            username: participant.username,
                          })
                        "
                      >
                        <v-icon> mdi-account </v-icon>
                      </v-btn>
                    </v-sheet>
                  </v-overlay>
                </v-fade-transition>
              </v-sheet>
            </v-hover>

            <!-- 画面共有 -->
            <v-row justify="center" class="mt-3">
              <video autoplay :srcObject.prop="screenSharing.stream" id="screen-sharing"></video>
            </v-row>
          </v-row>

          <!--*** ピン留め時 ***-->
          <v-row justify="center" class="mt-3" v-if="pinnedParticipant">
            <v-hover v-slot="{ hover }">
              <v-sheet
                color="rgba(0, 0, 0, 1)"
                :width="videoSize.width"
                :height="videoSize.height"
                :class="['video', 'mx-1', speakerId === pinnedParticipant.peerId ? 'speaker' : '']"
              >
                <!-- オフ -->
                <v-sheet
                  color="black"
                  :width="videoSize.width"
                  :height="videoSize.height"
                  class="d-flex justify-center align-center"
                  v-if="pinnedParticipant.loading || pinnedParticipant.isVideoOff"
                >
                  <!-- ローディング中 -->
                  <v-progress-circular
                    size="70"
                    width="4"
                    color="green"
                    indeterminate
                    v-if="pinnedParticipant.loading || pinnedParticipant.icon === null"
                  ></v-progress-circular>

                  <v-avatar size="150" v-else>
                    <img :src="$storage('icon') + pinnedParticipant.icon" />
                  </v-avatar>

                  <audio autoplay :srcObject.prop="pinnedParticipant.stream"></audio>
                </v-sheet>

                <!-- オン -->
                <video
                  :width="videoSize.width"
                  :height="videoSize.height"
                  autoplay
                  :srcObject.prop="pinnedParticipant.stream"
                  v-else
                ></video>

                <p class="handlename">{{ pinnedParticipant.handlename }}</p>

                <p class="is-mute" v-if="pinnedParticipant.isMute">
                  <v-icon color="red">mdi-microphone-off</v-icon>
                </p>

                <!-- hover -->
                <v-fade-transition>
                  <v-overlay absolute opacity="0.7" v-if="hover">
                    <v-sheet
                      color="rgba(0, 0, 0, 0)"
                      :width="videoSize.width"
                      :height="videoSize.height"
                    >
                      <v-btn
                        icon
                        x-large
                        class="pin-button"
                        @click="pinnedParticipant.isPinned = false"
                      >
                        <v-icon> mdi-pin-off </v-icon>
                      </v-btn>

                      <v-btn
                        icon
                        x-large
                        class="account-button"
                        @click="
                          $store.dispatch('dialog/open', {
                            type: 'user',
                            username: pinnedParticipant.username,
                          })
                        "
                      >
                        <v-icon> mdi-account </v-icon>
                      </v-btn>
                    </v-sheet>
                  </v-overlay>
                </v-fade-transition>
              </v-sheet>
            </v-hover>
          </v-row>

          <!--*** 通常時（画面共有OFF） ***-->
          <v-row justify="center" class="normal-container" v-if="!screenSharing.stream">
            <!-- 自分のビデオ -->
            <v-col sm="6" md="6" lg="6">
              <v-row justify="center">
                <v-sheet
                  color="rgba(0, 0, 0, 1)"
                  :width="videoShowWidth"
                  :height="videoShowHeight"
                  class="video ma-2"
                >
                  <!-- オフ -->
                  <v-sheet
                    color="black"
                    :width="videoShowWidth"
                    :height="videoShowHeight"
                    class="d-flex justify-center align-center"
                    v-if="isVideoLoading || isVideoOff || localStream === null"
                  >
                    <v-avatar size="80">
                      <img :src="$storage('icon') + authUser.icon" />
                    </v-avatar>
                  </v-sheet>

                  <!-- オン -->
                  <video
                    :width="videoShowWidth"
                    :height="videoShowHeight"
                    muted="true"
                    autoplay
                    :srcObject.prop="localStream"
                    v-else
                  ></video>

                  <p class="handlename">{{ authUser.handlename }}</p>

                  <p class="is-mute" v-if="isAudioLoading || isMute">
                    <v-icon color="red">mdi-microphone-off</v-icon>
                  </p>
                </v-sheet>
              </v-row>
            </v-col>

            <!-- 参加者のビデオ -->
            <v-col
              sm="6"
              md="6"
              lg="6"
              v-for="participant in notPinnedParticipants"
              :key="participant.peerId"
            >
              <v-row justify="center">
                <v-hover v-slot="{ hover }">
                  <v-sheet
                    color="rgba(0, 0, 0, 1)"
                    :width="videoShowWidth"
                    :height="videoShowHeight"
                    :class="['video', 'ma-2', speakerId === participant.peerId ? 'speaker' : '']"
                  >
                    <!-- オフ -->
                    <v-sheet
                      color="black"
                      :width="videoShowWidth"
                      :height="videoShowHeight"
                      class="d-flex justify-center align-center"
                      v-if="participant.loading || participant.isVideoOff"
                    >
                      <!-- ローディング中 -->
                      <v-progress-circular
                        size="70"
                        width="4"
                        color="green"
                        indeterminate
                        v-if="participant.loading || participant.icon === null"
                      ></v-progress-circular>

                      <v-avatar size="80" v-else>
                        <img :src="$storage('icon') + participant.icon" />
                      </v-avatar>

                      <audio autoplay :srcObject.prop="participant.stream"></audio>
                    </v-sheet>

                    <!-- オン -->
                    <video
                      :width="videoShowWidth"
                      :height="videoShowHeight"
                      autoplay
                      :srcObject.prop="participant.stream"
                      v-else
                    ></video>

                    <p class="handlename">{{ participant.handlename }}</p>

                    <p class="is-mute" v-if="participant.isMute">
                      <v-icon color="red">mdi-microphone-off</v-icon>
                    </p>

                    <!-- hover -->
                    <v-fade-transition>
                      <v-overlay absolute opacity="0.7" v-if="hover">
                        <v-sheet
                          color="rgba(0, 0, 0, 0)"
                          :width="videoShowWidth"
                          :height="videoShowHeight"
                        >
                          <v-btn icon x-large class="pin-button" @click="pin(participant)">
                            <v-icon> mdi-pin </v-icon>
                          </v-btn>

                          <v-btn
                            icon
                            x-large
                            class="account-button"
                            @click="
                              $store.dispatch('dialog/open', {
                                type: 'user',
                                username: participant.username,
                              })
                            "
                          >
                            <v-icon> mdi-account </v-icon>
                          </v-btn>
                        </v-sheet>
                      </v-overlay>
                    </v-fade-transition>
                  </v-sheet>
                </v-hover>
              </v-row>
            </v-col>
          </v-row>
        </v-container>
      </v-flex>

      <!-- チャットエリア -->
      <v-flex xs3 v-show="chat.isOpen">
        <v-card color="grey lighten-2" class="mx-auto" id="chat">
          <div
            class="overflow-y-auto"
            :style="{ height: $windowHeight - 200 + 'px' }"
            ref="chatScrollArea"
          >
            <v-card-text v-for="(message, index) in chat.messages" :key="index">
              <span class="text-caption">{{ message.handlename }}</span>
              <span class="text-body-1 font-weight-bold" v-html="message.text"></span>
            </v-card-text>
          </div>

          <v-card-actions>
            <v-text-field
              v-model="chat.localText"
              class="px-2"
              label="入力"
              @keydown.enter="sendMessage"
            ></v-text-field>
            <v-btn icon @click="sendMessage">
              <v-icon>mdi-send</v-icon>
            </v-btn>
          </v-card-actions>
        </v-card>
      </v-flex>
    </v-layout>

    <!-- ツールバー -->
    <v-app-bar
      color="yellow darken-4"
      fixed
      bottom
      height="100"
      :class="['app-bar', appBar.isShow ? 'show' : '']"
    >
      <v-row>
        <v-col md="4" sm="4" align-self="center">
          <v-row justify="start" class="mt-8">
            <v-col md="6" sm="6">
              <v-select disabled label="ミュート" v-if="isMute"></v-select>
              <v-select
                v-model="selectedAudio"
                :items="audioDevices"
                item-text="label"
                item-value="deviceId"
                @change="changeDevice()"
                label="マイク"
                v-else
              >
              </v-select>
            </v-col>

            <v-col md="6" sm="6">
              <v-select disabled label="ビデオオフ" v-if="isVideoOff"></v-select>
              <v-select
                v-model="selectedVideo"
                :items="videoDevices"
                item-text="label"
                item-value="deviceId"
                @change="changeDevice()"
                label="ビデオ"
                v-else
              >
              </v-select>
            </v-col>
          </v-row>
        </v-col>

        <v-col md="4" sm="4" align-self="center">
          <v-row justify="center">
            <!-- ミュートボタン -->
            <v-btn
              :color="!isMute ? 'white' : 'red'"
              :disabled="isAudioLoading || isVideoLoading"
              fab
              depressed
              :large="$vuetify.breakpoint.lg"
              :class="[
                $vuetify.breakpoint.xl ? 'mx-10' : '',
                $vuetify.breakpoint.lg ? 'mx-8' : '',
                $vuetify.breakpoint.md ? 'mx-5' : 'mx-1',
              ]"
              @click="mute()"
            >
              <v-icon large>{{ !isMute ? 'mdi-microphone' : 'mdi-microphone-off' }}</v-icon>
            </v-btn>

            <!-- ビデオオフボタン -->
            <v-btn
              :color="!isVideoOff ? 'white' : 'red'"
              :disabled="isAudioLoading || isVideoLoading || !video"
              fab
              depressed
              :large="$vuetify.breakpoint.lg"
              :class="[
                $vuetify.breakpoint.xl ? 'mx-10' : '',
                $vuetify.breakpoint.lg ? 'mx-8' : '',
                $vuetify.breakpoint.md ? 'mx-5' : 'mx-1',
              ]"
              @click="videoOff()"
            >
              <v-icon large>{{ !isVideoOff ? 'mdi-video' : 'mdi-video-off' }}</v-icon>
            </v-btn>

            <!-- 画面共有ボタン -->
            <v-btn
              :color="screenSharing.stream && screenSharing.isLocal ? 'red' : 'white'"
              :disabled="screenSharing.stream && !screenSharing.isLocal"
              fab
              depressed
              :large="$vuetify.breakpoint.lg"
              :class="[
                $vuetify.breakpoint.xl ? 'mx-10' : '',
                $vuetify.breakpoint.lg ? 'mx-8' : '',
                $vuetify.breakpoint.md ? 'mx-5' : 'mx-1',
              ]"
              @click="!screenSharing.stream ? startScreenSharing() : stopScreenSharing()"
            >
              <v-icon large>
                {{
                  screenSharing.stream && screenSharing.isLocal
                    ? 'mdi-window-close'
                    : 'mdi-window-restore'
                }}
              </v-icon>
            </v-btn>
          </v-row>
        </v-col>

        <v-col md="4" sm="4" align-self="center">
          <v-row justify="end">
            <!-- 通知音ボタン -->
            <v-btn color="white" icon class="mr-6" @click="$store.dispatch('alert/switchSound')">
              <v-icon large>
                {{ isNotificationOn ? 'mdi-music-note' : 'mdi-music-note-off' }}
              </v-icon>
            </v-btn>

            <!-- チャットボタン -->
            <v-badge
              bordered
              dot
              color="deep-purple accent-4"
              :value="chat.notification"
              offset-x="35"
              offset-y="13"
            >
              <v-btn
                color="white"
                icon
                :class="['mr-5', chat.notification ? 'notification' : '']"
                @click="toggleChat()"
              >
                <v-icon large>mdi-message-text</v-icon>
              </v-btn>
            </v-badge>

            <!-- 部屋閲覧ボタン -->
            <v-btn
              color="white"
              icon
              class="mr-6"
              @mousedown="toggleShowRoom(true)"
              @mouseup="toggleShowRoom(false)"
              @mouseleave="toggleShowRoom(false)"
            >
              <v-icon large>mdi-chevron-down</v-icon>
            </v-btn>

            <!-- 通話終了ボタン -->
            <v-btn
              color="error"
              depressed
              :x-large="$vuetify.breakpoint.xl || $vuetify.breakpoint.lg"
              :large="$vuetify.breakpoint.md"
              :class="[$vuetify.breakpoint.xl || $vuetify.breakpoint.lg ? 'mx-12' : 'mx-5']"
              @click="leaveCall()"
            >
              自習室に戻る
            </v-btn>
          </v-row>
        </v-col>
      </v-row>
    </v-app-bar>
  </v-dialog>
</template>

<script>
import voiceDetection from 'voice-activity-detection';
import { JOIN_CALL_SOUND, LEAVE_CALL_SOUND, RECEIVE_MESSAGE_SOUND } from '@/consts/sound';

const API_KEY = process.env.MIX_SKYWAY_API_KEY;

export default {
  props: {
    callId: Number,
    capacity: Number,
    video: Boolean,
  },
  data() {
    return {
      dialog: true, // 入室制御
      dialogElement: null, // ダイアログ要素
      permissionOverlay: false, // 権限確認画面
      loading: false, // ローディング制御
      appBar: {
        timer: null, // ツールバー表示タイマー
        isShow: false, // ツールバー表示制御
      },

      //*** 通話 ***//
      participants: [], // 参加者
      roomMode: 'mesh', // 接続モード
      peer: null, // Peer接続オブジェクト
      localStream: null, // 自分の送信データ
      call: null, // 接続プロパティ
      screenSharing: {
        isLocal: false, // 自分の画面共有か
        peer: null, // Peer接続オブジェクト（画面共有用）
        stream: null, // 画面共有データ
      },

      //*** 入力デバイス ***//
      audioDevices: [], // 音声入力デバイス一覧
      videoDevices: [], // 映像入力デバイス一覧
      selectedAudio: null, // 選択されている音声入力
      selectedVideo: null, // 選択されている映像入力
      videoSize: {
        width: 640, // ビデオ取得サイズ（横）
        height: 360, // ビデオ取得サイズ（縦）
      },
      isAudioLoading: true, // マイク接続ローディング制御
      isMute: false, // ミュート制御
      isVideoLoading: true, // ビデオ接続ローディング制御
      isVideoOff: false, // ビデオオフ制御

      //*** 音声検出 ***//
      voiceDetectionObject: null, // 音声検出オブジェクト
      speakerId: null, // 話し中ユーザーのIDß

      //*** チャット ***//
      chat: {
        isOpen: false, // チャットエリア表示制御
        notification: false, // 通知制御
        localText: '', // 送信するメッセージ
        messages: [], // メッセージ一覧
      },
    };
  },
  computed: {
    authUser() {
      return this.$store.getters['auth/user'];
    },
    isNotificationOn() {
      return this.$store.getters['alert/isSoundOn'];
    },
    videoShowWidth() {
      return this.$windowWidth / 3;
    },
    videoShowHeight() {
      return (this.videoShowWidth / 16) * 9;
    },
    pinnedParticipant() {
      // ピン留めされている参加者（一人）
      return this.participants.filter((participant) => {
        return participant.isPinned === true;
      })[0];
    },
    notPinnedParticipants() {
      // ピン留めされていない参加者
      return this.participants.filter((participant) => {
        return participant.isPinned === false;
      });
    },
  },
  methods: {
    /**
     * 通話室から退室
     */
    leaveCall: async function () {
      await this.exitCall();
      this.dialog = false;
      this.$emit('leave-call');
    },

    /**
     * 通話の開始
     */
    makeCall: async function () {
      this.call = this.peer.joinRoom(this.callId, {
        mode: this.roomMode,
        stream: this.localStream,
      });

      this.setupCallEvents();
    },

    /**
     * 通話のイベント
     */
    setupCallEvents: function () {
      // 他ユーザー参加イベント
      this.call.on('stream', (stream) => {
        this.joinUser(stream);
      });

      // データ到着イベント
      this.call.on('data', async ({ data, src }) => {
        // 送信者が取得されるまで待機
        let sender;
        for (let i = 0; i < 50; i++) {
          // 参加直後，this.participantsへの追加前に検索されるので回避
          // 新規ユーザーのstreamよりも先にdataが届く
          // 存在しない場合の対策として上限を5秒に設定
          try {
            sender = await new Promise((resolve, reject) => {
              // 送信者を検索（参加者のPeerIDを確認）
              let participant = this.participants.filter((participant) => {
                return src === participant.peerId;
              })[0];

              if (typeof participant === 'undefined') {
                // 再処理へ
                reject();
              } else {
                // 終了
                resolve(participant);
              }
            });

            break;
          } catch (error) {
            // 0.1秒待機
            await new Promise((resolve) => setTimeout(resolve, 100));
            // 再処理
            continue;
          }
        }

        switch (data.type) {
          case 'joinUserData':
            // 参加したユーザーのデータの受信
            sender.username = data.content.username;
            sender.handlename = data.content.handlename;
            sender.icon = data.content.icon;
            break;

          case 'loadingEvent':
            // ローディングイベント
            sender.loading = data.content;
            break;

          case 'audioEvent':
            // ミュートイベント
            sender.isMute = data.content;
            break;

          case 'videoEvent':
            // ビデオオフイベント
            sender.isVideoOff = data.content;
            break;

          case 'message':
            // メッセージの受信
            this.addMessage(sender.handlename, data.content);

            if (!this.chat.isOpen) {
              // 通知音
              if (this.isNotificationOn) {
                RECEIVE_MESSAGE_SOUND.play();
              }

              // 通知の表示
              this.chat.notification = true;
              this.showAppBar();
            }
            break;
        }
      });

      // 他ユーザー退出イベント
      this.call.on('peerLeave', (peerId) => {
        if (this.screenSharing.peer !== null && peerId === this.screenSharing.peer.id) {
          // 自分の画面共有が停止された場合
          this.screenSharing.peer = null;
        } else if (
          this.screenSharing.stream !== null &&
          peerId === this.screenSharing.stream.peerId
        ) {
          // 参加者の画面共有が停止された場合
          this.screenSharing.stream = null;
        } else {
          // 参加者の退出
          this.leaveUser(peerId);
        }
      });
    },

    /**
     * 通話の終了
     */
    exitCall: async function () {
      // 自分の画面共有が有効な場合
      if (this.screenSharing.isLocal) {
        // 画面共有の停止
        await this.stopScreenSharing();
      }

      if (this.peer !== null) {
        // 音声検知の終了
        this.stopVoiceDetection();

        // デバイスの使用を停止
        if (this.localStream !== null) {
          this.localStream.getTracks().forEach((track) => track.stop());
          this.localStream = null;
        }

        // 通話の接続を終了
        await this.peer.destroy();
        this.peer = null;
      }
    },

    /**
     * 参加者の追加
     *
     * @param {MediaStream} stream - 参加したユーザーのストリーム
     */
    joinUser: async function (stream) {
      // 参加者がいるか確認
      // ミュートやビデオの切替時にもストリームが置き換わるため発火する場合がある
      // 同一のPeerIDが存在しないことを確認する
      let isJoin = !this.participants.some((participant) => participant.peerId === stream.peerId);

      if (isJoin) {
        // ユーザーが参加した場合
        if (stream.getAudioTracks().length > 0) {
          // 現在の自分の状態を送信（新規参加者に現在の状態を通知）
          this.call.send({ type: 'joinUserData', content: this.authUser });
          this.call.send({ type: 'loadingEvent', content: this.loading });
          this.call.send({ type: 'audioEvent', content: this.isMute });
          this.call.send({ type: 'videoEvent', content: this.isVideoOff });

          // 通知音
          if (this.isNotificationOn) {
            JOIN_CALL_SOUND.play();
          }

          // 参加者の追加
          this.participants.push({
            isPinned: false, // ピン留めしているか
            username: '', // ユーザー名
            handlename: '', // 表示名
            icon: null, // アイコン
            loading: true, // 接続待ち状態
            isMute: true, // ミュート状態
            isVideoOff: true, // ビデオオフ状態
            peerId: stream.peerId,
            stream: stream,
          });

          // 音声検知の開始
          this.startVoiceDetection(stream);
        } else {
          // 画面共有が参加した場合
          if (!this.screenSharing.isLocal) {
            // 自分が画面共有していない場合（他人が画面共有を開始）
            this.screenSharing.stream = stream;
          }
        }
      }
    },

    /**
     * 参加者の退出
     *
     * @param {String} peerId - 退出したユーザーのPeerID
     */
    leaveUser: function (peerId) {
      // 通知音
      if (this.isNotificationOn) {
        LEAVE_CALL_SOUND.play();
      }

      this.participants = this.participants.filter((participant) => {
        // 退出したユーザーのpeerId以外を残す
        return participant.peerId !== peerId;
      });
    },

    /**
     * 画面共有開始
     */
    startScreenSharing: async function () {
      if (this.screenSharing.stream === null) {
        try {
          this.screenSharing.peer = new Peer({ key: API_KEY });

          this.screenSharing.stream = await navigator.mediaDevices.getDisplayMedia({
            audio: false,
            video: true,
          });

          this.screenSharing.peer.joinRoom(this.callId, {
            mode: this.roomMode,
            stream: this.screenSharing.stream,
          });

          this.screenSharing.isLocal = true;

          this.setupScreenSharingEvents();
        } catch (error) {
          // キャンセルボタン押下時
          this.$store.dispatch('alert/error', '画面共有がキャンセルされました．．．');
        }
      }
    },

    /**
     * 画面共有のイベント
     */
    setupScreenSharingEvents: function () {
      // 画面共有終了イベント（Chromeの共有を停止ボタン押下時の処理）
      this.screenSharing.stream.getVideoTracks()[0].onended = () => {
        this.stopScreenSharing();
      };
    },

    /**
     * 画面共有停止
     */
    stopScreenSharing: async function () {
      if (this.screenSharing.stream !== null) {
        this.screenSharing.isLocal = false;

        // デバイスの画面共有を停止
        this.screenSharing.stream.getTracks().forEach((track) => track.stop());
        this.screenSharing.stream = null;

        // 画面共有用の接続を終了
        await this.screenSharing.peer.destroy();
      }
    },

    /**
     * 通話デバイスへのアクセス
     */
    accessDevice: async function () {
      //** 権限確認 */
      this.permissionOverlay = true;

      if (this.video) {
        // ビデオ通話
        const stream = await navigator.mediaDevices.getUserMedia({
          audio: true,
          video: true,
        });
        // デバイスの停止
        stream.getTracks().forEach((track) => track.stop());

        //** デバイスの一覧を取得 */
        const devices = await navigator.mediaDevices.enumerateDevices();
        // マイクデバイスの一覧を取得
        this.audioDevices = devices.filter((device) => {
          return (
            device.kind === 'audioinput' &&
            device.deviceId !== 'default' &&
            device.deviceId !== 'communications'
          );
        });
        // カメラデバイスの一覧を取得
        this.videoDevices = devices.filter((device) => {
          return (
            device.kind === 'videoinput' &&
            device.deviceId !== 'default' &&
            device.deviceId !== 'communications'
          );
        });

        // 初期値の設定
        this.selectedAudio = this.audioDevices[0].deviceId;
        this.selectedVideo = this.videoDevices[0].deviceId;
        this.permissionOverlay = false;
      } else {
        // 音声通話
        const stream = await navigator.mediaDevices.getUserMedia({
          audio: true,
          video: false,
        });
        // デバイスの停止
        stream.getTracks().forEach((track) => track.stop());

        //** デバイスの一覧を取得 */
        const devices = await navigator.mediaDevices.enumerateDevices();
        // マイクデバイスの一覧を取得
        this.audioDevices = devices.filter((device) => {
          return (
            device.kind === 'audioinput' &&
            device.deviceId !== 'default' &&
            device.deviceId !== 'communications'
          );
        });

        // 初期値の設定
        this.selectedAudio = this.audioDevices[0].deviceId;
        this.permissionOverlay = false;
      }
    },

    /**
     * 通話デバイスへの接続
     */
    connectDevice: async function () {
      // Audioは常に残す（どちらかを定義しておく必要あり）
      let constraints = {
        audio: this.selectedAudio ? { deviceId: this.selectedAudio } : false,
        video: this.selectedVideo && !this.isVideoOff ? { deviceId: this.selectedVideo } : false,
      };

      // 録画サイズの設定
      if (constraints.video) {
        constraints.video.width = {
          min: this.videoSize.width,
          max: this.videoSize.width,
        };
        constraints.video.height = {
          min: this.videoSize.height,
          max: this.videoSize.height,
        };
      }

      try {
        // デバイスの接続
        this.localStream = await navigator.mediaDevices.getUserMedia(constraints);

        // 起動時はすぐにカメラを停止する
        if (this.loading && constraints.video) {
          // 接続時にはenabledで停止
          // デバイスを停止すると，相手にvideoストリームが届かない
          this.localStream.getVideoTracks()[0].enabled = false;
        }

        // ストリーム再作成時にミュートが解除される（ビデオ切り替え時など）
        if (this.isMute) {
          this.localStream.getAudioTracks()[0].enabled = false;
        }
      } catch (error) {
        // 他のアプリがデバイスを使用している場合
        this.errorEvent(
          'マイクまたはカメラが認識できませんでした。ブラウザの設定から権限を有効化してから再読み込みしてください。'
        );
      }
    },

    /**
     * 通話デバイスの切り替え
     */
    changeDevice: async function () {
      await this.connectDevice();
      // ストリームの置き換え
      this.call.replaceStream(this.localStream);
    },

    /**
     * ミュートの切り替え
     */
    mute: async function () {
      this.isAudioLoading = true;

      this.isMute = !this.isMute;

      // Audio，Videoのいずれかを残しておく必要があるので，enableで制御
      this.localStream.getAudioTracks()[0].enabled = !this.isMute;

      // 通知
      this.call.send({ type: 'audioEvent', content: this.isMute });

      this.isAudioLoading = false;
    },

    /**
     * ビデオのオン/オフ切り替え
     */
    videoOff: async function () {
      this.isVideoLoading = true;

      this.isVideoOff = !this.isVideoOff;

      if (this.isVideoOff) {
        // ビデオデバイスの停止
        this.localStream.getVideoTracks().forEach((track) => track.stop());
      } else {
        // ビデオデバイスの再接続
        await this.changeDevice();
      }

      // 通知
      this.call.send({ type: 'videoEvent', content: this.isVideoOff });

      this.isVideoLoading = false;
    },

    /**
     * 音声検知の開始
     *
     * @param {MediaStream} stream - 音声検知するユーザーのストリーム
     */
    startVoiceDetection: function (stream) {
      const AudioContext = window.AudioContext || window.webkitAudioContext;
      const audioCtx = new AudioContext();

      // 音声検知時のイベント
      const options = {
        // 検知開始（発声開始）
        onVoiceStart: () => {
          // 発言者の枠点灯
          this.speakerUpdate(stream.peerId);
        },

        // 検知終了（発声終了）
        onVoiceStop: () => {
          // 発言者の枠消灯
          this.speakerUpdate(null);
        },
      };

      // 音声検出開始
      this.voiceDetectionObject = voiceDetection(audioCtx, stream, options);
    },

    /**
     * 発言者の更新
     *
     * @param {String} peerId - 発言中のユーザーのPeerID
     */
    speakerUpdate(peerId) {
      this.speakerId = peerId;
    },

    /**
     * 音声検知の終了
     */
    stopVoiceDetection() {
      // 音声検知終了
      if (this.voiceDetectionObject) {
        this.voiceDetectionObject.destroy();
      }
    },

    /**
     * チャットエリアの表示切り替え
     */
    toggleChat: function () {
      this.chat.isOpen = !this.chat.isOpen;
      this.chat.notification = false;
    },

    /**
     * メッセージの送信処理
     *
     * @param {Event} event - クリック or キーボードイベント
     */
    sendMessage: function (event) {
      // クリックまたは日本語変換以外のEnter押下時に発火
      if (event.type === 'click' || (event.type === 'keydown' && event.keyCode === 13)) {
        if (this.chat.localText !== '') {
          // メッセージの送信
          this.call.send({ type: 'message', content: this.chat.localText });

          // 自分の画面を更新
          this.addMessage(this.authUser.handlename, this.chat.localText);
          this.chat.localText = '';
        }
      }
    },

    /**
     * メッセージの追加処理
     *
     * @param {String} handlename - 表示名
     * @param {String} text - 内容
     */
    addMessage: function (handlename, text) {
      // メッセージの追加
      this.chat.messages.push({
        handlename: handlename,
        text: this.$formatStr(text),
      });

      // 最下部へスクロール（メッセージのDOM挿入後に実行）
      this.$nextTick(() => {
        if (this.$refs.chatScrollArea) {
          this.$refs.chatScrollArea.scrollTop = this.$refs.chatScrollArea.scrollHeight;
        }
      });
    },

    /**
     * ピン留め
     *
     * @param {Object} participant - ピン留めする参加者
     */
    pin: function (participant) {
      // 既にピン留めされているユーザーを解除
      if (this.pinnedParticipant) {
        this.pinnedParticipant.isPinned = false;
      }

      participant.isPinned = true;
    },

    /**
     * ツールバーの表示制御
     *
     * @param {Event} event - マウス移動イベント
     */
    showAppBar: function (event = null) {
      // マウスが動作すると表示
      this.appBar.isShow = true;
      clearTimeout(this.appBar.timer);

      let hide = true;
      if (event !== null) {
        // マウスがツールバー上にある場合，非表示にしない
        let cursorFromBottom = this.$windowHeight - event.clientY;
        if (cursorFromBottom <= 100) {
          hide = false;
        }
      }

      if (hide) {
        this.appBar.timer = setTimeout(() => {
          // 停止1秒後に隠す
          this.appBar.isShow = false;
        }, 1000);
      }
    },

    /**
     * 部屋の表示切り替え
     */
    toggleShowRoom: function (show) {
      if (show) {
        this.$refs.container.style.opacity = 0;
        this.dialogElement.style.backgroundImage = null;
      } else {
        this.$refs.container.style.opacity = 1;
        this.dialogElement.style.backgroundImage =
          'url("' + this.$storage('seat') + 'seat_' + this.authUser.seat.id + '.png")';
      }
    },

    /**
     * エラー発生時のイベント
     *
     * @param {String} message - エラーメッセージ
     */
    errorEvent: function (message) {
      this.$store.dispatch('alert/error', message);
      this.leaveCall();
    },
  },

  async created() {
    this.loading = true;

    // 15秒間接続できなければ終了
    const timeout = () => {
      // 権限確認中の場合，再度待機
      if (this.permissionOverlay) {
        setTimeout(timeout, 15000);
      } else {
        if (this.loading) {
          this.errorEvent('エラーが発生しました。再読み込みしてください。');
        }
      }
    };
    setTimeout(timeout, 15000);

    // エラー発生時のイベント
    Vue.config.errorHandler = (error) => {
      this.$slack(
        'エラーBot',
        ':poop:',
        this.authUser.username + '様のデバイスでエラー発生！\n' + '```' + error + '```'
      );
      this.errorEvent('エラーが発生しました。再読み込みしてください。');
    };

    // エラー発生時のイベント
    window.addEventListener('error', (error) => {
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
      this.errorEvent('エラーが発生しました。再読み込みしてください。');
    });

    // エラー発生時のイベント
    window.addEventListener('unhandledrejection', (error) => {
      this.$slack(
        'エラーBot',
        ':poop:',
        this.authUser.username + '様のデバイスでエラー発生！\n' + '```' + error.reason + '```'
      );
      this.errorEvent('エラーが発生しました。再読み込みしてください。');
    });

    // 定員が4人より多い場合はSFU方式を利用
    if (this.capacity > 4) {
      this.roomMode = 'sfu';
    }

    // Peerの作成
    this.peer = new Peer({ key: API_KEY });

    // Peerの生成を待機
    await new Promise((resolve) => setTimeout(resolve, 1000));

    if (typeof this.peer.id !== 'undefined') {
      // デバイスの接続
      await this.accessDevice();
      await this.connectDevice();

      // 通話開始
      await this.makeCall();

      // 通話開始時はミュート/ビデオオフに設定
      this.mute();
      setTimeout(() => {
        // ビデオオフを遅延させないと，videoストリームが相手に接続できない
        this.videoOff();

        // 通知音の有効化
        this.$store.dispatch('alert/switchSound', {
          isOn: true,
          sound: JOIN_CALL_SOUND,
        });

        this.loading = false;
        this.call.send({ type: 'loadingEvent', content: false });
      }, 5000);
    }
  },

  async mounted() {
    // ユーザーデータの同期
    await this.$store.dispatch('auth/syncAuthUser');

    // 背景の設定
    this.dialogElement = document.getElementsByClassName('v-dialog')[0];
    this.dialogElement.style.backgroundImage =
      'url("' + this.$storage('seat') + 'seat_' + this.authUser.seat.id + '.png")';

    // ツールバー表示制御の設定
    window.addEventListener('mousemove', this.showAppBar);
  },

  beforeDestroy() {
    // イベントの削除
    window.removeEventListener('mousemove', this.showAppBar);
    clearTimeout(this.appBar.timer);

    // 念の為
    this.exitCall();
  },
};
</script>

<style lang="scss" scoped>
.arrow {
  display: inline-block;
  height: 80px;
  width: 40px;
  background-color: #5bc0de;
  position: relative;
  top: 60px;
  left: -300px;
}

.arrow:before {
  position: absolute;
  content: '';
  width: 0px;
  height: 0px;
  border: 60px solid transparent;
  border-bottom: 60px solid #5bc0de;
  left: -40px;
  top: -120px;
}

.progress-linear {
  width: 430px;
}

.video {
  position: relative;

  // v-hover要素に適用
  .v-overlay {
    z-index: 0 !important;
  }

  .handlename {
    position: absolute;
    background-color: black;
    color: white;
    line-height: 1em;
    bottom: 0;
    left: 0;
    margin: 0;
    padding: 2px;
  }

  .is-mute {
    position: absolute;
    color: white;
    line-height: 1em;
    bottom: 0;
    right: 0;
    margin: 0;
    padding: 2px;
  }

  .pin-button {
    position: absolute;
    top: 50%;
    left: 40%;
    transform: translate(-50%, -50%);
    -webkit-transform: translate(-50%, -50%);
    -ms-transform: translate(-50%, -50%);
  }

  .account-button {
    position: absolute;
    top: 50%;
    right: 40%;
    transform: translate(50%, -50%);
    -webkit-transform: translate(50%, -50%);
    -ms-transform: translate(50%, -50%);
  }

  &.speaker {
    outline: 5px solid #f6bf00;
  }
}

#screen-sharing {
  max-width: 80%;
}

.normal-container {
  height: 100vh;
}

#chat {
  position: -webkit-sticky;
  position: sticky;
  top: 8px;

  .overflow-y-auto {
    height: 500px;
    background-color: white;
  }
}

.app-bar {
  transition: 0.8s;
  opacity: 0;
  pointer-events: none;

  &.show {
    opacity: 1;
    pointer-events: auto;
  }

  // 通知
  .notification {
    animation: scaleChange 2s infinite ease-out;
    transform-origin: 50% 50%;
    animation-play-state: running;

    @keyframes scaleChange {
      0% {
        transform: scale(0.8, 0.8);
      }
      5% {
        transform: scale(1.2, 1.2);
      }
      10% {
        transform: scale(1, 1);
      }
      15% {
        transform: scale(1.1, 1.1);
      }
      20% {
        transform: scale(1, 1);
      }
      100% {
        transform: scale(1, 1);
      }
    }
  }
}
</style>

<style lang="scss">
.v-dialog {
  background-size: cover;
}
</style>
