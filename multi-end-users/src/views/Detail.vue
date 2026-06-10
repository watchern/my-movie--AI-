<template>
  <div class="page">
    <van-nav-bar :title="detail.title" left-arrow @click-left="goBack" :fixed="true" placeholder />

    <div v-if="loading" class="loading-wrapper">
      <van-loading>加载中...</van-loading>
    </div>
    <div v-else-if="detail.id">
      <!-- 视频播放器 -->
      <div class="player-wrapper">
        <video
          v-if="currentSource"
          ref="videoRef"
          class="video-player"
          controls
          :src="currentSource.play_url"
          :poster="detail.cover"
          @timeupdate="onTimeUpdate"
          @ended="onEnded"
        >
          <source :src="currentSource.play_url" type="video/mp4">
        </video>
      </div>

      <!-- 视频信息和选集 -->
      <div class="content">
        <!-- 标题 -->
        <div class="title-section">
          <div class="title-row">
            <van-icon 
              :name="isFavorited ? 'star' : 'star-o'" 
              size="20" 
              :color="isFavorited ? '#ff976a' : '#999'" 
              @click="toggleFav"
              class="fav-icon"
            />
            <h2>{{ detail.title }} <span v-if="currentSource">- {{ currentSource.name }}</span></h2>
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
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { get, post } from '@/utils/request'
import { useUserStore } from '@/stores/user'
import { useHistoryStore } from '@/stores/history'
import { useSafeBack } from '@/utils/router'

const router = useRouter()
const route = useRoute()
const userStore = useUserStore()
const historyStore = useHistoryStore()
const { safeBack } = useSafeBack()

const videoRef = ref(null)
const detail = ref({})
const episodes = ref([])
const currentSource = ref(null)
const isFavorited = ref(false)
const loading = ref(true)
let timer = null

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

const selectSource = (source) => {
  currentSource.value = source
  // 选择视频源后立即添加历史记录
  if (detail.value.id && source) {
    addHistoryRecord()
  }
}

// 添加历史记录
const addHistoryRecord = () => {
  if (!videoRef.value || !currentSource.value) {
    // 即使没有视频对象，也添加基本记录
    historyStore.addHistory({
      video_id: detail.value.id,
      episode_id: currentSource.value?.id || 0,
      title: detail.value.title,
      cover_url: detail.cover,
      last_position: 0,
      progress: 0
    })
    return
  }
  
  const progress = videoRef.value.duration > 0 
    ? videoRef.value.currentTime / videoRef.value.duration 
    : 0
  historyStore.addHistory({
    video_id: detail.value.id,
    episode_id: currentSource.value.id,
    title: detail.value.title,
    cover_url: detail.cover,
    last_position: videoRef.value.currentTime,
    progress: progress
  })
}

const toggleFav = async () => {
  if (!userStore.isLogin) {
    router.push('/login')
    return
  }
  const res = await post(isFavorited.value ? '/favorite/delete' : '/favorite/add', {
    video_id: detail.value.id
  })
  isFavorited.value = !isFavorited.value
}

const onTimeUpdate = () => {
  if (!timer) {
    timer = setTimeout(() => {
      if (videoRef.value && currentSource.value && detail.value.id) {
        const progress = videoRef.value.duration > 0 
          ? videoRef.value.currentTime / videoRef.value.duration 
          : 0
        
        historyStore.addHistory({
          video_id: detail.value.id,
          episode_id: currentSource.value.id,
          title: detail.value.title,
          cover_url: detail.cover,
          last_position: videoRef.value.currentTime,
          progress: progress
        })
        
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
    }, 3000) // 改为3秒更新一次
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
</script>

<style lang="scss" scoped>
.page {
  min-height: 100vh;
  background: #f5f5f5;
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
  aspect-ratio: 16/9;
}

.video-player {
  width: 100%;
  height: 100%;
  display: block;
  object-fit: contain;
}

.content {
  padding: 12px;

  .title-section {
    background: white;
    padding: 16px;
    border-radius: 8px;

    .title-row {
      display: flex;
      align-items: center;
      gap: 8px;

      .fav-icon {
        cursor: pointer;
        flex-shrink: 0;
      }

      h2 {
        font-size: 18px;
        font-weight: 600;
        margin: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
      }
    }
  }

  .episode-section {
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

  .video-info {
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
</style>