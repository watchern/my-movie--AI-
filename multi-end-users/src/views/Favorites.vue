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
      <van-nav-bar title="我的收藏" :fixed="true" placeholder />

      <div v-if="loading" class="loading-wrapper">
        <van-loading>加载中...</van-loading>
      </div>
      <div v-else class="content-scroll">
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
const activeSidebar = ref(3) // 默认选中"我的"

const list = ref([])
const loading = ref(true)

// 导航方法
const goHome = () => router.push('/')
const goSearch = () => router.push('/search')
const goRank = () => router.push('/rank')
const goUser = () => router.push('/user')

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

.content-scroll {
  padding: 12px;
}

.loading-wrapper {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 60vh;
  padding-top: 20px;
}

.list {
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
