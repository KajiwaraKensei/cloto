<template>
  <v-container id="user-list">
    <div>
      <v-list v-if="Array.isArray(users) ">
        <v-list-item
          v-for="user in users || []"
          :key="user.id"
          :class="{'emphasis-color': emphasis && (user.id === emphasis.id)}"
          @click="$emit('click-user', user)"
        >
          <v-list-item-avatar>
            <v-img :src="$storage('icon') + user.icon"></v-img>
          </v-list-item-avatar>

          <v-list-item-content>
            <v-list-item-title>
              {{ user.handlename }}
              <small>@{{ user.username }}</small>
            </v-list-item-title>
            <v-list-item-subtitle>
              {{ user.introduction || '' }}
            </v-list-item-subtitle>
          </v-list-item-content>
        </v-list-item>
      </v-list>
      <p v-else-if="users === null">取得中</p>
      <p v-else-if="users !== null">まだ誰もいないようです。</p>
    </div>
  </v-container>
</template>

<script>
export const UserListComponent =  {
  props: {
    users: [Array, null],
    emphasis: [Object, undefined]
  }
}

export default UserListComponent
</script>

<style lang="scss" scoped>
#user-list{
  height: 100%;
  > div{
    height: 100%;
    background-color: #fff;
  }
  .emphasis-color{
    background-color: #ffe89a;
    cursor: default;
  }
}

  
</style>