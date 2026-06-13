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
      <van-nav-bar title="排行榜" placeholder />

      <div v-if="loading" class="loading-wrapper">
        <van-loading>加载中...</van-loading>
      </div>

      <div v-else>
        <van-tabs v-model:active="tabActive" sticky>
          <van-tab title="热播榜">
            <div class="rank-list">
              <div v-for="(item, index) in list" :key="item.id" class="rank-item" @click="goDetail(item.id)">
                <div class="rank-num" :class="'rank-' + Math.min(index, 3)">{{ index + 1 }}</div>
                <img :src="item.cover_url" :alt="item.title" />
                <div class="info">
                  <div class="title">{{ item.title }}</div>
                  <div class="counts">
                    <span>播放 {{ formatCount(item.play_count) }}</span>
                  </div>
                  <div class="desc">{{ item.desc || '暂无简介' }}</div>
                </div>
              </div>
            </div>
          </van-tab>
          <van-tab title="新上线">
            <div class="rank-list">
              <div v-for="(item, index) in newList" :key="item.id" class="rank-item" @click="goDetail(item.id)">
                <div class="rank-num" :class="'rank-' + Math.min(index, 3)">{{ index + 1 }}</div>
                <img :src="item.cover_url" :alt="item.title" />
                <div class="info">
                  <div class="title">{{ item.title }}</div>
                  <div class="counts">
                    <span>播放 {{ formatCount(item.play_count) }}</span>
                  </div>
                  <div class="desc">{{ item.desc || '暂无简介' }}</div>
                </div>
              </div>
            </div>
          </van-tab>
        </van-tabs>
      </div>
    </div>

    <!-- 底部导航（小屏幕 < 500px） -->
    <van-tabbar v-model="activeTab" class="bottom-tabbar" @change="onTabChange">
      <van-tabbar-item name="home" icon="home-o">首页</van-tabbar-item>
      <van-tabbar-item name="rank" icon="chart-trending-o">排行榜</van-tabbar-item>
      <van-tabbar-item name="user" icon="user-o">我的</van-tabbar-item>
    </van-tabbar>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { get } from '@/utils/request'

const router = useRouter()
const tabActive = ref(0)
const active = ref('rank')
const activeSidebar = ref(1)  // 左侧导航当前选中索引（排行榜=1）
const activeTab = ref('rank')  // 底部导航当前选中
const list = ref([])
const newList = ref([])
const loading = ref(true)

const loadList = async () => {
  loading.value = true
  const res = await get('/video/rank')
  list.value = res.data.list || []
  newList.value = res.data.new || []
  loading.value = false
}

const goDetail = (id) => router.push(`/detail/${id}`)
const onTabChange = (name) => {
  activeSidebar.value = name === 'home' ? 0 : name === 'rank' ? 1 : 2
  if (name === 'home') router.push('/home')
  else if (name === 'user') router.push('/user')
}

// 左侧导航切换
const onSidebarChange = (index) => {
  activeTab.value = index === 0 ? 'home' : index === 1 ? 'rank' : 'user'
  if (index === 0) router.push('/home')
  else if (index === 2) router.push('/user')
}

const formatCount = (count) => {
  if (count >= 10000) return (count / 10000).toFixed(1) + '万'
  return count
}

onMounted(() => loadList())
</script>

<style lang="scss" scoped>
.page {
  display: flex;
  min-height: 100vh;
}

// 右侧内容区域
.content-wrapper {
  flex: 1;
  min-height: 100vh;
  background: #f5f5f5;
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
    padding: 20px 12px;
    font-size: 14px;
    
    &.van-sidebar-item--select {
      color: #1989fa;
      font-weight: 500;
    }
  }
}

// 底部导航（小屏幕 < 500px）
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

.loading-wrapper {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 60vh;
  padding-top: 20px;
}

.rank-list {
  padding: 12px 16px;
}

.rank-item {
  display: flex;
  gap: 12px;
  padding: 12px 0;
  border-bottom: 1px solid #eee;

  .rank-num {
    width: 28px;
    text-align: center;
    font-size: 18px;
    font-weight: 600;
    color: #999;

    &.rank-0 { color: #ff3c00; }
    &.rank-1 { color: #ff9800; }
    &.rank-2 { color: #ffc107; }
  }

  img {
    width: 90px;
    height: 120px;
    border-radius: 6px;
    object-fit: cover;
  }

  .info {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    justify-content: flex-start;
    overflow: hidden;

    .title {
      font-size: 14px;
      font-weight: 500;
      color: #333;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
      max-width: 100%;
    }

    .counts {
      margin-top: 4px;
      font-size: 12px;
      color: #666;
    }

    .desc {
      margin-top: 4px;
      font-size: 12px;
      color: #999;
      overflow: hidden;
      text-overflow: ellipsis;
      display: -webkit-box;
      -webkit-line-clamp: 4;
      -webkit-box-orient: vertical;
      word-break: break-all;
    }
  }
}
</style>
