<template>
  <el-container>
    <el-aside width="200px">
      <div class="logo">管理后台</div>
      <el-menu :default-active="route.path" router>
        <el-menu-item index="/dashboard"><el-icon><DataLine /></el-icon>数据看板</el-menu-item>

        <el-sub-menu index="content">
          <template #title><el-icon><VideoCamera /></el-icon>内容管理</template>
          <el-menu-item index="/video">视频管理</el-menu-item>
          <el-menu-item index="/banner">轮播图管理</el-menu-item>
          <el-menu-item index="/collect">资源采集</el-menu-item>
        </el-sub-menu>

        <el-sub-menu index="user">
          <template #title><el-icon><User /></el-icon>用户管理</template>
          <el-menu-item index="/user">用户管理</el-menu-item>
          <el-menu-item index="/card">兑换码管理</el-menu-item>
          <el-menu-item index="/loginLog">用户登录日志</el-menu-item>
        </el-sub-menu>

        <el-sub-menu index="record">
          <template #title><el-icon><Coin /></el-icon>记录查询</template>
          <el-menu-item index="/watchHistory">观看历史</el-menu-item>
          <el-menu-item index="/favorite">收藏记录</el-menu-item>
          <el-menu-item index="/vipLog">VIP变动记录</el-menu-item>
        </el-sub-menu>

        <el-sub-menu index="system">
          <template #title><el-icon><Setting /></el-icon>系统管理</template>
          <el-menu-item index="/adminManager">管理员管理</el-menu-item>
          <el-menu-item index="/adminLog">管理员操作记录</el-menu-item>
          <el-menu-item index="/systemConfig">系统配置</el-menu-item>
        </el-sub-menu>
      </el-menu>
    </el-aside>

    <el-container>
      <el-header>
        <div class="header-right">
          <span>{{ adminName }}</span>
          <el-button type="primary" link @click="logout">退出</el-button>
        </div>
      </el-header>
      <el-main>
        <router-view />
      </el-main>
    </el-container>
  </el-container>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { DataLine, VideoCamera, User, Coin, Setting } from '@element-plus/icons-vue'

const router = useRouter()
const route = useRoute()
const adminName = ref(localStorage.getItem('adminName') || '管理员')

const logout = () => {
  localStorage.removeItem('adminToken')
  router.replace('/login')
}
</script>

<style lang="scss" scoped>
.el-container {
  height: 100%;
}

.el-aside {
  background: #001529;
  color: #fff;

  .logo {
    height: 60px;
    line-height: 60px;
    text-align: center;
    font-size: 18px;
    font-weight: 600;
    color: #fff;
    background: #002140;
  }
}

.el-menu {
  border: none;
  background: #001529;
}

:deep(.el-sub-menu__title) {
  color: rgba(255, 255, 255, 0.85);
  
  &:hover {
    color: #fff;
    background: #1890ff;
  }
}

:deep(.el-sub-menu .el-menu) {
  background: #000c17;
}

:deep(.el-menu-item) {
  color: rgba(255, 255, 255, 0.85);
  background: transparent;
  
  &:hover {
    color: #fff;
    background: #1890ff;
  }
  
  &.is-active {
    color: #fff;
    background: #1890ff;
  }
  
  .el-icon {
    color: inherit;
  }
}

.el-header {
  background: white;
  border-bottom: 1px solid #eee;
  display: flex;
  align-items: center;
  justify-content: flex-end;

  .header-right {
    display: flex;
    align-items: center;
    gap: 16px;
  }
}

.el-main {
  background: #f0f2f5;
  padding: 20px;
}
</style>
