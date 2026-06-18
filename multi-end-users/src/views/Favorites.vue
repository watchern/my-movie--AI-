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
      <van-nav-bar title="我的收藏" left-arrow @click-left="goBack" placeholder />

      <div v-if="loading" class="loading-wrapper">
        <van-loading>加载中...</van-loading>
      </div>
      <div v-else>
        <div v-if="list.length" class="list">
          <div v-for="item in list" :key="item.id" class="item" @click="goDetail(item.video_id)">
            <img :src="item.cover" :alt="item.title" />
            <div class="info">
              <div class="title">{{ item.title }}</div>
            </div>
          </div>
        </div>
        <div v-else class="empty">
          <van-empty description="暂无收藏" />
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
import { get } from '@/utils/request'
import { useSafeBack } from '@/utils/router'
import { useUserStore } from '@/stores/user'

const router = useRouter()
const userStore = useUserStore()
const { safeBack } = useSafeBack()
const activeSidebar = ref(2) // 默认选中"我的"
const activeTab = ref(2) // 默认选中"我的"

const list = ref([])
const loading = ref(true)

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

const goBack = () => safeBack('/')

const loadList = async () => {
  loading.value = true
  try {
    const res = await get('/favorite/list')
    list.value = res.data?.list || []
  } catch (e) {
    console.error('加载收藏失败', e)
    list.value = []
  }
  loading.value = false
}

const goDetail = (id) => router.push(`/detail/${id}`)

// 页面加载时检查登录状态
onMounted(() => {
    if (!userStore.isLogin) {
        router.replace('/login')
        return
    }
    loadList()
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

.loading-wrapper {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 60vh;
  padding-top: 20px;
}

.list {
  padding: 12px 16px;
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
  gap: 12px;
}

.item {
  display: flex;
  flex-direction: column;
  background: white;
  border-radius: 8px;
  overflow: hidden;
  cursor: pointer;

  img {
    width: 100%;
    aspect-ratio: 2/3;
    object-fit: cover;
  }

  .info {
    padding: 8px;

    .title {
      font-size: 14px;
      font-weight: 500;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }
  }
}

.empty {
  padding-top: 80px;
}
</style>
