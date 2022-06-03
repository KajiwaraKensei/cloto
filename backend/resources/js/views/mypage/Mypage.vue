<template>
  <v-layout id="mypage">
    <Drawer />

    <v-flex>
      <!-- 強制的にコンポーネントを初期化（変数の初期化） -->
      <router-view :key="$route.name" />
    </v-flex>
  </v-layout>
</template>

<script>
import Drawer from '@/components/mypage/Drawer';

export default {
  components: {
    Drawer,
  },
};
</script>

<style lang="scss" scoped>
#mypage {
  min-height: calc(100vh - 64px);
}
</style>
