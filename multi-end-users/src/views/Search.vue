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
      <van-nav-bar title="搜索" left-arrow @click-left="goBack" placeholder />

      <div class="search-wrapper">
        <van-search
          v-model="keyword"
          placeholder="请输入关键词搜索"
          show-action
          @search="onSearch"
          @cancel="onCancel"
        />
        
        <!-- 搜索结果 -->
        <div v-if="hasSearched" class="search-result">
          <div v-if="loading" class="loading-wrapper">
            <van-loading>加载中...</van-loading>
          </div>
          <div v-else-if="list.length" class="video-grid">
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
          <van-empty v-else description="未找到相关视频" />
        </div>
        
        <!-- 搜索历史 -->
        <div v-else class="search-history">
          <div v-if="historyKeywords.length" class="history-header">
            <span>搜索历史</span>
            <van-icon name="delete-o" @click="clearHistory" />
          </div>
          <div v-if="historyKeywords.length" class="history-list">
            <van-tag v-for="kw in historyKeywords" :key="kw" size="large" @click="searchWithKeyword(kw)">
              {{ kw }}
            </van-tag>
          </div>
          
          <!-- 热门搜索 -->
          <div v-if="!historyKeywords.length" class="hot-search">
            <div class="hot-title">热门搜索</div>
            <div class="hot-list">
              <van-tag v-for="(item, index) in hotKeywords" :key="index" size="large" @click="searchWithKeyword(item)">
                {{ item }}
              </van-tag>
            </div>
          </div>
        </div>
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
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { get } from '@/utils/request'
import { useSafeBack } from '@/utils/router'

const router = useRouter()
const { safeBack } = useSafeBack()

const activeSidebar = ref(0)  // 左侧导航当前选中索引
const activeTab = ref('home')  // 底部导航当前选中
const keyword = ref('')
const list = ref([])
const loading = ref(false)
const hasSearched = ref(false)

// 搜索历史
const historyKeywords = ref([])

// 热门搜索词
const hotKeywords = ref(['热播电影', '最新电视剧', '热门动漫', '经典纪录片'])

const formatCount = (count) => {
  if (count >= 10000) return (count / 10000).toFixed(1) + '万'
  return count
}

const onSearch = () => {
  const kw = keyword.value.trim()
  if (!kw) return
  
  hasSearched.value = true
  saveSearchHistory(kw)
  loadSearchResult(kw)
}

const searchWithKeyword = (kw) => {
  keyword.value = kw
  onSearch()
}

const loadSearchResult = async (kw) => {
  loading.value = true
  const res = await get('/video/search', { keyword: kw, limit: 50 })
  list.value = res.data.list || []
  loading.value = false
}

const onCancel = () => {
  goBack()
}

const goBack = () => safeBack('/home')

const goDetail = (id) => router.push(`/detail/${id}`)

const onTabChange = (name) => {
  activeSidebar.value = name === 'home' ? 0 : name === 'rank' ? 1 : 2
  if (name === 'rank') router.push('/rank')
  else if (name === 'user') router.push('/user')
}

const onSidebarChange = (index) => {
  activeTab.value = index === 0 ? 'home' : index === 1 ? 'rank' : 'user'
  if (index === 1) router.push('/rank')
  else if (index === 2) router.push('/user')
  else router.push('/home')
}

// 保存搜索历史
const saveSearchHistory = (kw) => {
  let history = JSON.parse(localStorage.getItem('searchHistory') || '[]')
  // 去除重复
  history = history.filter(item => item !== kw)
  // 添加到开头
  history.unshift(kw)
  // 最多保存10条
  history = history.slice(0, 10)
  localStorage.setItem('searchHistory', JSON.stringify(history))
  historyKeywords.value = history
}

// 加载搜索历史
const loadSearchHistory = () => {
  historyKeywords.value = JSON.parse(localStorage.getItem('searchHistory') || '[]')
}

// 清除搜索历史
const clearHistory = () => {
  localStorage.removeItem('searchHistory')
  historyKeywords.value = []
}

onMounted(() => {
  loadSearchHistory()
  
  // 检查URL参数
  const urlParams = new URLSearchParams(window.location.hash.split('?')[1])
  const kw = urlParams.get('keyword')
  if (kw) {
    keyword.value = kw
    onSearch()
  }
})
</script>

<style lang="scss" scoped>
.page {
  display: flex;
  min-height: 100vh;
  overflow-x: hidden;
  width: 100%;
}

.content-wrapper {
  flex: 1;
  min-height: 100vh;
  background: #f5f5f5;
  overflow-x: hidden;
}

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

.search-wrapper {
  padding: 0;
}

.loading-wrapper {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 40vh;
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

.search-history {
  padding: 16px;
  
  .history-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
    font-size: 14px;
    font-weight: 600;
    color: #333;
    
    .van-icon {
      color: #999;
    }
  }
  
  .history-list,
  .hot-list {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    
    .van-tag {
      background: #f5f5f5;
      color: #666;
    }
  }
  
  .hot-search {
    .hot-title {
      font-size: 14px;
      font-weight: 600;
      color: #333;
      margin-bottom: 12px;
    }
  }
}

.search-result {
  min-height: 40vh;
}
</style>
