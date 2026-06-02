<template>
  <el-container>
    <el-aside width="200px">
      <div class="logo">管理后台</div>
      <el-menu :default-active="route.path" router>
        <el-menu-item index="/dashboard"><el-icon><DataLine /></el-icon>数据看板</el-menu-item>
        <el-menu-item index="/video"><el-icon><VideoCamera /></el-icon>视频管理</el-menu-item>
        <el-menu-item index="/user"><el-icon><User /></el-icon>用户管理</el-menu-item>
        <el-menu-item index="/card"><el-icon><Tickets /></el-icon>兑换码管理</el-menu-item>
        <el-menu-item index="/collect"><el-icon><Download /></el-icon>资源采集</el-menu-item>
        <el-menu-item index="/loginLog"><el-icon><Clock /></el-icon>登录日志</el-menu-item>
        <el-menu-item index="/vipLog"><el-icon><Coin /></el-icon>VIP变动记录</el-menu-item>
        <el-menu-item index="/systemConfig"><el-icon><Setting /></el-icon>系统配置</el-menu-item>
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
import { DataLine, VideoCamera, User, Tickets, Download, Clock, Coin, Setting } from '@element-plus/icons-vue'

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

:deep(.el-menu-item) {
  color: rgba(255, 255, 255, 0.85);
  
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
