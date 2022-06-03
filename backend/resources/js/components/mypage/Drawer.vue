<template>
  <v-card class="mx-auto" width="250" flat tile>
    <v-navigation-drawer permanent>
      <v-list>
        <v-list-item>
          <v-list-item-content>
            <v-list-item-title class="text-h5">マイページ</v-list-item-title>
          </v-list-item-content>
        </v-list-item>
      </v-list>

      <v-list nav dense>
        <v-list-item-group color="#f6bf00">
          <v-list-item class="mb-4" :to="{ name: 'mypage' }">
            <v-list-item-content>
              <v-list-item-title class="text-body-1 font-weight-bold">
                プロフィール
              </v-list-item-title>
            </v-list-item-content>
          </v-list-item>

          <v-list-item class="mb-4" :to="{ name: 'talk' }">
            <v-list-item-content>
              <v-list-item-title class="text-body-1 font-weight-bold">
                トーク
              </v-list-item-title>
            </v-list-item-content>
          </v-list-item>

          <v-list-item class="mb-4" :to="{ name: 'followers' }">
            <v-list-item-content>
              <v-list-item-title class="text-body-1 font-weight-bold">
                フォロー/フォロワー
              </v-list-item-title>
            </v-list-item-content>
          </v-list-item>

          <v-list-item class="mb-4" :to="{ name: 'friends' }">
            <v-list-item-content>
              <v-list-item-title class="text-body-1 font-weight-bold">
                友達一覧
              </v-list-item-title>
            </v-list-item-content>
          </v-list-item>

          <v-list-item class="mb-4" :to="{ name: 'karte' }">
            <v-list-item-content>
              <v-list-item-title class="text-body-1 font-weight-bold">カルテ</v-list-item-title>
            </v-list-item-content>
          </v-list-item>

          <v-list-item class="mb-4" :to="{ name: 'post' }">
            <v-list-item-content>
              <v-list-item-title class="text-body-1 font-weight-bold">つぶやき</v-list-item-title>
            </v-list-item-content>
          </v-list-item>
        </v-list-item-group>
      </v-list>
    </v-navigation-drawer>
  </v-card>
</template>

<style lang="scss" scoped>
a:hover {
  text-decoration: none;
}
</style>
