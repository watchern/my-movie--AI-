<template>
  <div class="page">
    <!-- 左侧导航（大屏幕 >= 500px） -->
    <van-sidebar v-model="activeSidebar" class="sidebar-nav" @change="onSidebarChange">
      <van-sidebar-item title="首页" />
      <van-sidebar-item title="搜索" />
      <van-sidebar-item title="排行榜" />
      <van-sidebar-item title="影视详情" />
      <van-sidebar-item title="我的" />
    </van-sidebar>

    <!-- 右侧内容区域 -->
    <div class="content-wrapper">
      <van-nav-bar :title="detail.title" left-arrow @click-left="goBack" />

      <div v-if="loading" class="loading-wrapper">
        <van-loading>加载中...</van-loading>
      </div>
      <div v-else-if="detail.id" class="content-scroll">
        <!-- 视频播放器 -->
        <div class="player-wrapper">
          <video
            ref="videoRef"
            class="video-js vjs-big-play-centered vjs-fluid"
          >
            <p class="vjs-no-js">请启用JavaScript以观看此视频</p>
          </video>
        </div>

        <!-- 视频信息和选集 -->
        <div class="content">
        <!-- 标题 -->
        <div class="title-section">
          <div class="title-row">
            <h2>{{ detail.title }} <span v-if="currentSource">- {{ currentSource.name }}</span></h2>
          </div>
          <!-- 操作按钮 -->
          <div class="action-buttons">
            <!-- 播放源选择 -->
            <div class="action-btn" v-if="sourceSites.length > 0" @click="showSourcePicker = true">
              <van-icon name="apps-o" size="24" />
              <span>换源</span>
            </div>
            <!-- 收藏 -->
            <div class="action-btn" @click="toggleFav">
              <van-icon :name="isFavorited ? 'star' : 'star-o'" size="24" :color="isFavorited ? '#ff976a' : '#666'" />
              <span>{{ isFavorited ? '已收藏' : '收藏' }}</span>
            </div>
            <!-- 分享 -->
            <div class="action-btn" @click="handleShare">
              <van-icon name="share-o" size="24" />
              <span>分享</span>
            </div>
          </div>
        </div>

        <!-- 选集列表 -->
        <div class="episode-section" v-if="episodes.length">
          <div class="section-title">选集</div>
          <div class="episode-list">
            <div
              v-for="ep in episodes"
              :key="ep.id"
              class="episode-item"
              :class="{ active: ep.id === currentSource.id }"
              @click="selectSource(ep)"
            >{{ ep.name }}</div>
          </div>
        </div>

        <!-- 视频详情信息 -->
        <div class="video-info">
          <div class="sub-info">
            <span>{{ detail.release_year }}</span>
            <span>{{ detail.region }}</span>
            <span>{{ detail.type_name }}</span>
            <span v-if="detail.is_vip" class="vip">VIP</span>
          </div>
          <div class="meta">
            <span>播放 {{ formatCount(detail.play_count) }}</span>
            <span>评分 {{ detail.rating }}</span>
          </div>
          <div class="tags">
            <van-tag type="primary" size="mini" v-for="t in (detail.tags || '').split(',')" :key="t">{{ t }}</van-tag>
          </div>
          <div class="description">{{ detail.description || '暂无简介' }}</div>
        </div>
      </div>
      </div>
    </div>

    <!-- 底部导航栏（小屏幕 < 500px） -->
    <van-tabbar v-model="activeTab" class="bottom-tabbar" @change="onTabChange">
      <van-tabbar-item icon="wap-home">首页</van-tabbar-item>
      <van-tabbar-item icon="chart-trending-o">排行榜</van-tabbar-item>
      <van-tabbar-item icon="user-o">我的</van-tabbar-item>
    </van-tabbar>

    <!-- 快捷登录弹窗 -->
    <QuickLogin ref="quickLoginRef" @success="onLoginSuccess" />

    <!-- 播放源选择弹窗 -->
    <van-popup v-model:show="showSourcePicker" position="center" round closeable>
      <div class="source-picker">
        <div class="picker-header">
          <span>选择播放源</span>
        </div>
        <div class="source-list">
          <div
            v-for="site in sourceSites"
            :key="site.id"
            class="source-item"
            :class="{ active: site.id === currentSourceSite?.id }"
            @click="selectSourceSite(site)"
          >
            <div class="source-name">{{ site.name }}</div>
            <div class="source-info">{{ site.episode_count }}集</div>
            <van-icon v-if="site.id === currentSourceSite?.id" name="success" size="18" color="#1989fa" />
          </div>
        </div>
      </div>
    </van-popup>

    <!-- 选集选择弹窗 -->
    <van-popup v-model:show="showEpisodePicker" position="center" round closeable>
      <div class="source-picker">
        <div class="picker-header">
          <span>选择剧集</span>
        </div>
        <div class="source-list episode-grid">
          <div
            v-for="ep in episodes"
            :key="ep.id"
            class="source-item"
            :class="{ active: ep.id === currentSource?.id }"
            @click="selectSource(ep)"
          >
            <div class="source-name">{{ ep.name }}</div>
            <van-icon v-if="ep.id === currentSource?.id" name="success" size="18" color="#1989fa" />
          </div>
        </div>
      </div>
    </van-popup>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { get, post } from '@/utils/request'
import { useUserStore } from '@/stores/user'
import { useHistoryStore } from '@/stores/history'
import { useSafeBack } from '@/utils/router'
import videojs from 'video.js'
import 'video.js/dist/video-js.css'
import QuickLogin from '@/components/QuickLogin.vue'

const router = useRouter()
const route = useRoute()
const userStore = useUserStore()
const historyStore = useHistoryStore()
const { safeBack } = useSafeBack()
const quickLoginRef = ref(null)
const activeSidebar = ref(3) // 默认选中"影视详情"
const activeTab = ref(0) // 默认选中首页

const videoRef = ref(null)
const player = ref(null)
const hlsInstance = ref(null)
const detail = ref({})
const sourceSites = ref([])
const currentSourceSite = ref(null)
const episodes = ref([])
const currentSource = ref(null)
const isFavorited = ref(false)
const loading = ref(true)
const showSourcePicker = ref(false)
const showEpisodePicker = ref(false)
let timer = null
let historyTimer = null

// 左侧导航切换
const onSidebarChange = (index) => {
  if (index === 0) router.push('/')
  else if (index === 1) router.push('/search')
  else if (index === 2) router.push('/rank')
  else if (index === 3) return // 影视详情，保持当前页
  else if (index === 4) router.push('/user')
}

// 底部导航切换
const onTabChange = (index) => {
  if (index === 0) router.push('/')
  else if (index === 1) router.push('/rank')
  else if (index === 2) router.push('/user')
}

const loadDetail = async () => {
  loading.value = true
  try {
    const params = { id: route.params.id }
    // 如果路由参数是episode_id格式，就传episode_id
    if (route.query.episode_id) {
      params.episode_id = route.query.episode_id
      params.id = null // 先不传id，让后端通过episode_id找
    }
    const res = await get('/video/detail', params)
    detail.value = res.data || {}
    sourceSites.value = res.data?.source_sites || []
    currentSourceSite.value = res.data?.current_source_site || null
    episodes.value = res.data?.episodes || []
    isFavorited.value = res.data?.is_favorited || false
    
    // 默认选中第一个源，或者根据返回的current_episode_id选中
    if (episodes.value.length > 0) {
      let targetSource = episodes.value[0]
      if (res.data?.current_episode_id) {
        targetSource = episodes.value.find(ep => ep.id === res.data.current_episode_id) || targetSource
      }
      selectSource(targetSource)
    }
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

// 切换资源站
const switchSourceSite = async (site) => {
  if (site.id === currentSourceSite.value?.id) return
  
  currentSourceSite.value = site
  currentSource.value = null
  
  // 加载该资源站的剧集
  try {
    const res = await get('/video/detail', {
      id: detail.value.id,
      source_site_id: site.id
    })
    episodes.value = res.data?.episodes || []
    if (episodes.value.length > 0) {
      selectSource(episodes.value[0])
    }
  } catch (e) {
    console.error('切换资源站失败', e)
  }
}

// 选择资源站（弹窗中调用）
const selectSourceSite = (site) => {
  showSourcePicker.value = false
  switchSourceSite(site)
}

// 分享功能
const handleShare = async () => {
  const shareText = `${detail.value.title}\n${window.location.href}`
  
  try {
    await navigator.clipboard.writeText(shareText)
    const { showDialog } = await import('vant')
    showDialog({
      title: '分享提示',
      message: '分享内容与复制，快去发送给好友吧！',
      confirmButtonText: '知道了',
      confirmButtonColor: '#1989fa',
    })
  } catch (e) {
    console.error('复制失败', e)
    const { showToast } = await import('vant')
    showToast('复制失败，请手动复制')
  }
}

const selectSource = (source) => {
  // 清除之前的延迟记录
  if (historyTimer) {
    clearTimeout(historyTimer)
    historyTimer = null
  }
  
  currentSource.value = source
  
  // 延迟2秒后再记录用户实际选择的选集
  if (detail.value.id && source) {
    historyTimer = setTimeout(() => {
      addHistoryRecord(source)
    }, 2000)
  }
  
  // 初始化视频播放（支持M3U8）
  initVideoPlayer(source)
}

// 初始化视频播放器（使用video.js）
const initVideoPlayer = (source) => {
  if (!videoRef.value || !source?.play_url) return
  
  const url = source.play_url
  const isM3U8 = url.includes('.m3u8')
  
  // 如果播放器已存在，直接切换源
  if (player.value) {
    player.value.src({
      src: url,
      type: isM3U8 ? 'application/x-mpegURL' : 'video/mp4'
    })
    player.value.play().catch(() => {})
    return
  }
  
  // video.js 配置
  const options = {
    controls: true,
    fluid: true,
    preload: 'auto',
    autoplay: true, // 添加 autoplay 配置
    poster: detail.value.cover,
    sources: [{
      src: url,
      type: isM3U8 ? 'application/x-mpegURL' : 'video/mp4'
    }]
  }
  
  // 使用 setTimeout 确保元素已在 DOM 中并渲染完成
  setTimeout(() => {
    if (!videoRef.value || player.value) return
    
    // 创建 video.js 播放器
    const p = videojs(videoRef.value, options, function() {
      // 播放就绪后的回调
      this.on('ended', onEnded)
      this.on('timeupdate', onTimeUpdate)
      
      // 播放器就绪后延迟播放
      setTimeout(() => {
        const playPromise = this.play()
        if (playPromise !== undefined) {
          playPromise.catch(() => {})
        }
      }, 300)
      
      // 添加自定义控制按钮
      addCustomButtons(this)
    })
    
    player.value = p
  }, 300)
}

// 添加自定义控制按钮到播放器
const addCustomButtons = (playerInstance) => {
  const Component = videojs.getComponent('Component')
  const dom = videojs.dom || videojs
  
  // 换源按钮组件
  class SourceButton extends Component {
    constructor(player, options) {
      super(player, options)
    }
    createEl() {
      const el = dom.createEl('div', {
        className: 'vjs-control vjs-button source-btn',
        innerHTML: '<span class="vjs-icon-placeholder"></span><span class="btn-text">换源</span>'
      })
      el.addEventListener('click', () => {
        showSourcePicker.value = true
      })
      return el
    }
  }
  SourceButton.prototype.controlText = '换源'
  videojs.registerComponent('SourceButton', SourceButton)
  
  // 选集按钮组件
  class EpisodeButton extends Component {
    constructor(player, options) {
      super(player, options)
    }
    createEl() {
      const el = dom.createEl('div', {
        className: 'vjs-control vjs-button episode-btn',
        innerHTML: '<span class="vjs-icon-placeholder"></span><span class="btn-text">选集</span>'
      })
      el.addEventListener('click', () => {
        if (episodes.value.length > 0) {
          showEpisodePicker.value = true
        }
      })
      return el
    }
  }
  EpisodeButton.prototype.controlText = '选集'
  videojs.registerComponent('EpisodeButton', EpisodeButton)
  
  // 将按钮添加到控制栏
  playerInstance.addChild('SourceButton')
  playerInstance.addChild('EpisodeButton')
}

// 销毁Hls实例
const destroyHls = () => {
  if (hlsInstance.value) {
    hlsInstance.value.destroy()
    hlsInstance.value = null
  }
}

// 销毁 video.js 播放器
const destroyPlayer = () => {
  if (player.value) {
    try {
      player.value.dispose()
    } catch (e) {
      console.error('销毁播放器失败', e)
    }
    player.value = null
  }
  // 同时清理 Hls
  destroyHls()
}

// 添加历史记录
const addHistoryRecord = (source) => {
  if (!source) return // 没有选集时不添加
  
  // 确保 detail 有数据
  if (!detail.value.id) return
  
  // 优先使用 source.name，如果没有则从 episodes 中查找同名
  let episodeName = source.name || ''
  if (!episodeName && source.id) {
    const found = episodes.value.find(e => e.id === source.id)
    if (found) {
      episodeName = found.name || ''
    }
  }
  // 再次回退：使用 source 的 sort_order 或 id 推断
  if (!episodeName) {
    episodeName = source.sort_order ? `第${source.sort_order}集` : ''
  }
  
  historyStore.addHistory({
    video_id: detail.value.id,
    episode_id: source.id,
    episode_name: episodeName,
    title: detail.value.title,
    cover_url: detail.value.cover,
    last_position: 0,
    progress: 0
  })
}

const toggleFav = async () => {
  if (!userStore.isLogin) {
    // 弹出快捷登录框
    quickLoginRef.value?.open()
    return
  }
  try {
    const res = await post(isFavorited.value ? '/favorite/remove' : '/favorite/add', {
      video_id: detail.value.id
    })
    isFavorited.value = !isFavorited.value
    const { showToast } = await import('vant')
    showToast(isFavorited.value ? '收藏成功' : '已取消收藏')
  } catch (e) {
    console.error('收藏操作失败', e)
  }
}

// 登录成功后刷新收藏状态
const onLoginSuccess = async () => {
  // 重新检查收藏状态
  try {
    const res = await get('/favorite/check', { video_id: route.params.id })
    isFavorited.value = res.data?.is_favorited || false
  } catch (e) {
    console.error('检查收藏状态失败', e)
  }
}

const onTimeUpdate = () => {
  if (!timer) {
    timer = setTimeout(() => {
      if (videoRef.value && currentSource.value && detail.value.id) {
        const progress = videoRef.value.duration > 0 
          ? videoRef.value.currentTime / videoRef.value.duration 
          : 0
        
        // 只更新播放进度，不使用 addHistory（避免覆盖 episode_name）
        const existing = historyStore.getHistory(detail.value.id)
        if (existing) {
          existing.last_position = videoRef.value.currentTime
          existing.progress = progress
          existing.watched_at = new Date().toISOString()
        }
        
        // 登录用户同步到服务器
        if (userStore.isLogin) {
          post('/history/add', {
            video_id: detail.value.id,
            episode_id: currentSource.value.id,
            progress: Math.round(progress * 100),
            last_position: Math.round(videoRef.value.currentTime),
            duration: Math.round(videoRef.value.duration)
          }).catch(e => console.error('同步历史失败', e))
        }
      }
      timer = null
    }, 3000)
  }
}

const onEnded = () => {
  const idx = episodes.value.findIndex(e => e.id === currentSource.value.id)
  if (idx < episodes.value.length - 1) {
    selectSource(episodes.value[idx + 1])
  }
}

const formatCount = (count) => {
  if (count >= 10000) return (count / 10000).toFixed(1) + '万'
  return count
}

const goBack = () => safeBack('/')

onMounted(() => loadDetail())

// 组件卸载时清理Hls实例
onBeforeUnmount(() => {
  destroyPlayer()
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

.player-wrapper {
  background: #000;
  width: 100%;
}

.content {
  :deep(.title-section) {
    background: white;
    padding: 16px;
    border-radius: 8px;

    .title-row {
      h2 {
        font-size: 18px;
        font-weight: 600;
        margin: 0 0 12px 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
      }
    }

    .action-buttons {
      display: flex;
      gap: 24px;
      justify-content: space-around;

      .action-btn {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 6px;
        cursor: pointer;
        flex: 1;

        span {
          font-size: 13px;
          color: #666;
        }
      }
    }
  }

  :deep(.episode-section) {
    margin-top: 12px;
    background: white;
    padding: 16px;
    border-radius: 8px;

    .section-title {
      font-size: 16px;
      font-weight: 600;
      margin-bottom: 12px;
    }

    .episode-list {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 8px;

      .episode-item {
        padding: 8px;
        background: #f5f5f5;
        border-radius: 6px;
        text-align: center;
        font-size: 13px;
        color: #333;
        cursor: pointer;

        &.active {
          background: #1989fa;
          color: white;
        }
      }
    }
  }

  :deep(.source-site-section) {
    display: none;
  }

  :deep(.video-info) {
    margin-top: 12px;
    background: white;
    padding: 16px;
    border-radius: 8px;

    .sub-info {
      display: flex;
      gap: 12px;
      font-size: 13px;
      color: #666;
      margin-bottom: 8px;

      .vip {
        color: #ff6a00;
        font-weight: 500;
      }
    }

    .meta {
      font-size: 13px;
      color: #999;
      margin-bottom: 12px;

      span {
        margin-right: 12px;
      }
    }

    .tags {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
      margin-bottom: 12px;
    }

    .description {
      font-size: 14px;
      color: #666;
      line-height: 1.8;
    }
  }
}

// video.js 自定义按钮样式
:deep(.vjs-control-bar) {
  .source-btn,
  .episode-btn {
    display: flex !important;
    align-items: center;
    justify-content: center;
    min-width: 50px !important;
    padding: 0 8px;
    
    .btn-text {
      font-size: 12px;
      color: #fff;
      margin-left: 2px;
    }
    
    &:hover {
      background: rgba(255, 255, 255, 0.2);
    }
  }
}

// 播放源选择弹窗
.source-picker {
  width: 33vw;
  min-width: 280px;
  max-width: 360px;
  max-height: 33vh;
  min-height: 200px;
  padding: 16px;

  .picker-header {
    display: flex;
    justify-content: center;
    align-items: center;
    margin-bottom: 16px;
    padding-bottom: 12px;
    border-bottom: 1px solid #eee;

    span {
      font-size: 16px;
      font-weight: 600;
    }
  }

  .source-list {
    max-height: calc(33vh - 80px);
    overflow-y: auto;
  }

  .source-item {
    display: flex;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid #f5f5f5;
    cursor: pointer;

    &:last-child {
      border-bottom: none;
    }

    &.active {
      color: #1989fa;
    }

    .source-name {
      flex: 1;
      font-size: 15px;
    }

    .source-info {
      font-size: 13px;
      color: #999;
      margin-right: 8px;
    }
  }
  
  // 选集网格布局
  .episode-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 8px;
    
    .source-item {
      padding: 8px;
      justify-content: center;
    }
  }
}
</style>