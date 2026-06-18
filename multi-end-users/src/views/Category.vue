<template>
  <div class="page">
    <!-- 左侧导航（大屏幕 >= 500px） -->
    <van-sidebar v-model="activeSidebar" class="sidebar-nav" @change="onSidebarChange">
      <van-sidebar-item title="首页" />
      <van-sidebar-item title="排行榜" />
      <van-sidebar-item title="影视分类" />
      <van-sidebar-item title="我的" />
    </van-sidebar>

    <!-- 右侧内容区域 -->
    <div class="content-wrapper">
      <van-nav-bar :title="pageTitle" left-arrow @click-left="goBack" />

      <!-- 筛选区域 -->
      <div class="filter-section">
        <!-- 排序筛选 -->
        <div class="filter-row">
          <div class="filter-options">
            <span
              class="filter-item"
              :class="{ active: filters.orderBy === 'update_time' }"
              @click="setFilter('orderBy', 'update_time')"
            >最新</span>
            <span
              class="filter-item"
              :class="{ active: filters.orderBy === 'hot' }"
              @click="setFilter('orderBy', 'hot')"
            >最热</span>
            <span
              class="filter-item"
              :class="{ active: filters.orderBy === 'rating' }"
              @click="setFilter('orderBy', 'rating')"
            >好评</span>
          </div>
        </div>

        <!-- 年份筛选 -->
        <div class="filter-row">
          <div class="filter-options">
            <span
              class="filter-item"
              :class="{ active: filters.year === 'all' }"
              @click="setFilter('year', 'all')"
            >全部</span>
            <span
              v-for="y in yearOptions"
              :key="y"
              class="filter-item"
              :class="{ active: filters.year === y }"
              @click="setFilter('year', y)"
            >{{ y }}</span>
          </div>
        </div>

        <!-- 地区筛选 -->
        <div class="filter-row">
          <div class="filter-options">
            <span
              class="filter-item"
              :class="{ active: filters.region === 'all' }"
              @click="setFilter('region', 'all')"
            >全部</span>
            <span
              v-for="r in regionOptions"
              :key="r"
              class="filter-item"
              :class="{ active: filters.region === r }"
              @click="setFilter('region', r)"
            >{{ r }}</span>
          </div>
        </div>

        <!-- 语种筛选 -->
        <div class="filter-row">
          <div class="filter-options">
            <span
              class="filter-item"
              :class="{ active: filters.language === 'all' }"
              @click="setFilter('language', 'all')"
            >全部</span>
            <span
              v-for="l in languageOptions"
              :key="l"
              class="filter-item"
              :class="{ active: filters.language === l }"
              @click="setFilter('language', l)"
            >{{ l }}</span>
          </div>
        </div>
      </div>

      <van-pull-refresh v-model="refreshing" @refresh="onRefresh">
        <div class="video-list">
          <div v-if="loading" class="loading-wrapper">
            <van-loading>加载中...</van-loading>
          </div>
          <div v-else>
            <div v-if="list.length" class="video-grid">
              <div v-for="item in list" :key="item.id" class="video-item" @click="goDetail(item.id)">
                <div class="video-cover">
                  <img :src="item.cover_url" :alt="item.title" />
                  <span v-if="item.is_vip" class="vip-tag">VIP</span>
                  <span class="play-count">{{ formatCount(item.play_count) }}</span>
                </div>
                <div class="video-title">{{ item.title }}</div>
                <div class="video-info">
                  <span>{{ item.release_year }}</span>
                  <span>{{ item.region }}</span>
                </div>
              </div>
            </div>
            <van-empty v-else description="暂无数据" />
          </div>
        </div>
      </van-pull-refresh>
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
import { ref, onMounted, computed, reactive, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { get } from '@/utils/request'
import { useSafeBack } from '@/utils/router'

const router = useRouter()
const route = useRoute()
const { safeBack } = useSafeBack()
const activeSidebar = ref(2) // 默认选中"影视分类"
const activeTab = ref(0) // 默认选中首页

const type = computed(() => +route.params.type)
const pageTitle = computed(() => {
  const titles = { 1: '电影', 2: '电视剧', 3: '动漫', 4: '短视频', 5: '纪录片' }
  return titles[type.value] || '分类'
})

// 筛选条件
const filters = reactive({
  orderBy: 'update_time',
  year: 'all',
  region: 'all',
  language: 'all'
})

// 年份选项（从当前年份往前推10年）
const currentYear = new Date().getFullYear()
const yearOptions = Array.from({ length: 10 }, (_, i) => String(currentYear - i))

// 地区选项
const regionOptions = ['内地', '香港', '台湾', '美国']

// 语种选项
const languageOptions = ['国语', '英语', '粤语']

const refreshing = ref(false)
const list = ref([])
const loading = ref(false)
const finished = ref(false)
let page = 1

// 左侧导航切换
const onSidebarChange = (index) => {
  if (index === 0) router.push('/')
  else if (index === 1) router.push('/rank')
  else if (index === 2) return // 影视分类，保持当前页
  else if (index === 3) router.push('/user')
}

// 底部导航切换
const onTabChange = (index) => {
  if (index === 0) router.push('/')
  else if (index === 1) router.push('/rank')
  else if (index === 2) router.push('/user')
}

// 设置筛选条件
const setFilter = (key, value) => {
  filters[key] = value
  page = 1
  loadList()
}

const formatCount = (count) => {
  if (count >= 10000) {
    return (count / 10000).toFixed(1) + '万'
  }
  return count
}

const loadList = async () => {
  if (loading.value) return
  loading.value = true
  
  const params = {
    type: type.value,
    page,
    limit: 20,
    order_by: filters.orderBy
  }
  
  // 添加筛选参数
  if (filters.year !== 'all') {
    params.year = filters.year
  }
  if (filters.region !== 'all') {
    params.region = filters.region
  }
  if (filters.language !== 'all') {
    params.language = filters.language
  }
  
  const res = await get('/video/list', params)
  
  const newList = res.data.list || []
  list.value = page === 1 ? newList : [...list.value, ...newList]
  finished.value = page >= res.data.pages
  page++
  loading.value = false
}

const onRefresh = async () => {
  page = 1
  finished.value = false
  await loadList()
  refreshing.value = false
}

const goDetail = (id) => router.push(`/detail/${id}`)
const goBack = () => safeBack('/')

// 监听路由变化，重新加载
watch(() => route.params.type, () => {
  page = 1
  loadList()
})

onMounted(() => loadList())
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

// 筛选区域
.filter-section {
  background: white;
  padding: 12px 16px;
  margin-bottom: 8px;
}

.filter-row {
  margin-bottom: 10px;
  
  &:last-child {
    margin-bottom: 0;
  }
}

.filter-options {
  display: flex;
  flex-wrap: nowrap;
  gap: 8px;
  overflow-x: auto;
  scrollbar-width: none;
  -ms-overflow-style: none;
  
  &::-webkit-scrollbar {
    display: none;
  }
}

.filter-item {
  padding: 4px 12px;
  font-size: 13px;
  color: #333;
  background: #f5f5f5;
  border-radius: 4px;
  cursor: pointer;
  white-space: nowrap;
  flex-shrink: 0;
  
  &:hover {
    background: #e8e8e8;
  }
  
  &.active {
    background: #1989fa;
    color: white;
  }
}

.video-list {
  min-height: 60vh;
}

.loading-wrapper {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 60vh;
  padding-top: 20px;
}

.video-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 6px;
  padding: 12px 16px;

  @media (min-width: 768px) {
    grid-template-columns: repeat(6, 1fr);
    gap: 8px;
  }
}

.video-item {
  .video-cover {
    position: relative;
    border-radius: 4px;
    overflow: hidden;

    img {
      width: 100%;
      aspect-ratio: 1/1.3;
      object-fit: cover;
    }

    .vip-tag {
      position: absolute;
      top: 2px;
      right: 2px;
      background: linear-gradient(135deg, #ff9500, #ff6a00);
      color: white;
      padding: 0 2px;
      border-radius: 2px;
      font-size: 8px;
    }

    .play-count {
      position: absolute;
      bottom: 2px;
      right: 2px;
      background: rgba(0, 0, 0, 0.6);
      color: white;
      padding: 0 2px;
      border-radius: 2px;
      font-size: 8px;
    }
  }

  .video-title {
    font-size: 11px;
    color: #333;
    margin-top: 3px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .video-info {
    display: flex;
    gap: 4px;
    font-size: 9px;
    color: #999;
    margin-top: 1px;
  }
}
</style>