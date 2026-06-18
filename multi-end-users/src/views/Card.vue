<template>
  <div class="page">
    <!-- 左侧导航（大屏幕 >= 500px） -->
    <van-sidebar v-model="activeSidebar" class="sidebar-nav" @change="onSidebarChange">
      <van-sidebar-item title="首页" />
      <van-sidebar-item title="排行榜" />
      <van-sidebar-item title="我的" />
    </van-sidebar>

    <!-- 右侧内容区域 -->
    <div class="content-wrapper">
      <van-nav-bar title="卡密兑换" left-arrow @click-left="goBack" placeholder />

      <div class="content">
        <div class="tip">输入卡密兑换VIP时长</div>
        <van-field v-model="cardKey" placeholder="请输入卡密" clearable />
        <van-button type="primary" block :loading="loading" @click="onExchange">立即兑换</van-button>

        <div class="ad-section">
          <div class="title">或观看广告获得VIP</div>
          <van-button type="success" block @click="onAd">观看广告</van-button>
        </div>
      </div>
    </div>

    <!-- 底部导航栏（小屏幕 < 500px） -->
    <van-tabbar v-model="activeTab" class="bottom-tabbar" @change="onTabChange">
      <van-tabbar-item icon="wap-home">首页</van-tabbar-item>
      <van-tabbar-item icon="chart-trending-o">排行榜</van-tabbar-item>
      <van-tabbar-item icon="user-o">我的</van-tabbar-item>
    </van-tabbar>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { post } from '@/utils/request'
import { showToast } from 'vant'
import { useUserStore } from '@/stores/user'

const router = useRouter()
const userStore = useUserStore()
const activeSidebar = ref(2) // 默认选中"我的"
const activeTab = ref(2) // 默认选中"我的"

const cardKey = ref('')
const loading = ref(false)

// 底部导航切换
const onTabChange = (index) => {
  if (index === 0) router.push('/')
  else if (index === 1) router.push('/rank')
  else if (index === 2) router.push('/user')
}

// 左侧导航切换
const onSidebarChange = (index) => {
  if (index === 0) router.push('/')
  else if (index === 1) router.push('/rank')
  else if (index === 2) router.push('/user')
}

const goBack = () => router.push('/')

const onExchange = async () => {
  if (!cardKey.value.trim()) {
    showToast('请输入卡密')
    return
  }
  
  loading.value = true
  try {
    await post('/card/exchange', { card_key: cardKey.value })
    showToast('兑换成功')
    cardKey.value = ''
  } catch (e) {
    showToast(e.message || '兑换失败')
  } finally {
    loading.value = false
  }
}

const onAd = () => {
  showToast('广告功能开发中')
}

onMounted(() => {
  if (!userStore.isLogin) {
    router.replace('/login')
  }
})
</script>

<style lang="scss" scoped>
.page {
  display: flex;
  min-height: 100vh;
  overflow-x: hidden;
  width: 100%;
  
  @media (min-width: 500px) {
    gap: 8px;
  }
}

// 右侧内容区域
.content-wrapper {
  flex: 1;
  min-height: 100vh;
  background: #f5f5f5;
  overflow-x: hidden;
}

// 左侧导航（大屏幕 >= 500px）
.sidebar-nav {
  display: none;
  width: 100px;
  background: white;
  box-shadow: 2px 0 8px rgba(0, 0, 0, 0.1);
  flex-shrink: 0;
  
  @media (min-width: 500px) {
    display: block;
  }
  
  :deep(.van-sidebar-item) {
    height: 46px;
    line-height: 46px;
    padding: 0 12px;
    font-size: 14px;
    
    &.van-sidebar-item--select {
      color: #1989fa;
      font-weight: 500;
    }
  }
}

// 底部导航栏（小屏幕 < 500px）
.bottom-tabbar {
  display: none;
  
  @media (max-width: 499px) {
    display: flex;
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    z-index: 999;
  }
}

.content {
  padding: 16px;
}

.tip {
  text-align: center;
  font-size: 14px;
  color: #666;
  margin-bottom: 16px;
}

.ad-section {
  margin-top: 32px;
  text-align: center;
  
  .title {
    margin-bottom: 12px;
    color: #999;
  }
}
</style>
