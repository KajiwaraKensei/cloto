/**
 * ルーティングの取得方法
 * <router-link :to="{ name: 'user', params: { userId: 123 }}">User</router-link>
 * this.$router.push({ name: 'user', params: { userId: 123 }});
 * let path = this.$router.resolve({ name: 'user', params: { userId: 123 }}).href;
 */

import Vue from 'vue';
import VueRouter from 'vue-router';
Vue.use(VueRouter);

import store from './store';

import index from '@/views/Index';
import terms from '@/views/service/Terms';
import login from '@/views/Login';
import home from '@/views/home/Home';
import timeline from '@/views/home/Timeline';
import floor from '@/views/home/Floor';
import room from '@/views/room/Room';
import mypage from '@/views/mypage/Mypage';
import profile from '@/views/mypage/Profile';
import follower from '@/views/mypage/Follower';
import friend from '@/views/mypage/Friends';
import post from '@/views/mypage/Post';
import mystudy from '@/views/mystudy/Mystudy';
import progress from '@/views/mystudy/Progress';
import karte from '@/views/mystudy/Karte';
import docs from '@/views/mystudy/docs/Docs';
import notFound from '@/views/errors/NotFound';
import talkView from '@/views/talk';
const router = new VueRouter({
  mode: 'history',
  routes: [
    {
      path: '/',
      name: 'index',
      component: index,
      meta: { isPublic: true },
    },
    {
      path: '/terms',
      name: 'terms',
      component: terms,
      meta: { isPublic: true },
    },
    {
      path: '/login',
      name: 'login',
      component: login,
      meta: { isPublic: true },
    },
    {
      path: '/home',
      component: home,
      children: [
        {
          path: 'timeline',
          name: 'home',
          component: timeline,
        },
        {
          path: 'floor/:roomId',
          name: 'floor',
          component: floor,
        },
      ],
    },
    {
      path: '/room/:roomId',
      name: 'room',
      component: room,
    },
    {
      path: '/mypage',
      component: mypage,
      children: [
        {
          path: 'profile',
          name: 'mypage',
          component: profile,
        },
        {
          path: 'followers',
          name: 'followers',
          component: follower,
        },
        {
          path: 'talk',
          name: 'talk',
          component: talkView,
        },
        {
          path: 'friends',
          name: 'friends',
          component: friend,
        },
        {
          path: 'karte',
          name: 'karte',
          component: karte,
        },
        {
          path: 'post',
          name: 'post',
          component: post,
        },
      ],
    },
    {
      path: '*',
      name: notFound,
      component: notFound,
      meta: { isPublic: true },
    },
  ],
});

router.beforeEach(async (to, from, next) => {
  // ログイン状態の更新
  await store.dispatch('auth/syncAuthUser');

  if (!store.getters['auth/check'] && to.matched.some((record) => !record.meta.isPublic)) {
    // 未ログイン時のリダイレクト
    next({ name: 'login' });
  }

  if (store.getters['auth/check']) {
    if (store.getters['auth/user'].email_verified_at === null) {
      // 未認証時のリダイレクト
      window.location.pathname = '/email/verify';
    }

    if (to.name === 'login') {
      // ログイン時のリダイレクト
      next({ name: 'home' });
    }

    if (to.name === 'docs') {
      if (!store.getters['auth/user'].roadmaps.length) {
        // ロードマップ未開始時
        next({ name: 'mystudy' });
      } else if (
        store.getters['auth/user'].roadmaps.length &&
        to.params.class > store.getters['auth/user'].roadmaps[0].in_progress
      ) {
        // 取り組み中のクラスよりも先のコンテンツへのアクセス時のリダイレクト
        next({
          name: 'docs',
          params: {
            roadName: 'javascript',
            class: store.getters['auth/user'].roadmaps[0].in_progress,
          },
        });
      }
    }
  }

  next();
});

export default router;
