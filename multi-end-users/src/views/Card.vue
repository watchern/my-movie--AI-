<template>
  <div class="page">
    <!-- 左侧导航（大屏幕 >= 500px） -->
    <van-sidebar v-model="activeSidebar" class="sidebar-nav">
      <van-sidebar-item title="首页" @click="goHome" />
      <van-sidebar-item title="搜索" @click="goSearch" />
      <van-sidebar-item title="排行榜" @click="goRank" />
      <van-sidebar-item title="我的" @click="goUser" />
    </van-sidebar>

    <!-- 右侧内容区域 -->
    <div class="content-wrapper">
      <van-nav-bar title="卡密兑换" :fixed="true" placeholder />

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
const activeSidebar = ref(3) // 默认选中"我的"

const cardKey = ref('')
const loading = ref(false)

// 导航方法
const goHome = () => router.push('/')
const goSearch = () => router.push('/search')
const goRank = () => router.push('/rank')
const goUser = () => router.push('/user')

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
  background: #f5f5f5;
  
  @media (min-width: 500px) {
    gap: 8px;
  }
}

.sidebar-nav {
  @media (min-width: 500px) {
    :deep(.van-sidebar-item) {
      height: 46px;
      line-height: 46px;
      padding: 0 12px;
      font-size: 14px;
    }
  }
}

.content-wrapper {
  flex: 1;
  min-height: 100vh;
  
  @media (min-width: 500px) {
    background: white;
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
