<template>
  <div class="play-page">
    <div class="player-wrapper">
      <video
        ref="videoRef"
        class="video-js vjs-big-play-centered"
        controls
        :poster="detail.cover_url"
        @timeupdate="onTimeUpdate"
        @ended="onEnded"
      >
        <source :src="playUrl" type="application/x-mpegURL">
      </video>
    </div>

    <div class="content">
      <div class="video-info">
        <h2>{{ detail.title }} - {{ episode.title }}</h2>
        <div class="meta">
          <span>播放 {{ formatCount(detail.play_count) }}</span>
        </div>
      </div>

      <div class="episode-section">
        <div class="title">选集</div>
        <div class="episode-list">
          <div
            v-for="ep in episodes"
            :key="ep.id"
            class="episode-item"
            :class="{ active: ep.id === episode.id }"
            @click="changeEpisode(ep.id)"
          >{{ ep.title }}</div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, nextTick } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { get, post } from '@/utils/request'
import { useHistoryStore } from '@/stores/history'
import { useUserStore } from '@/stores/user'
import videojs from 'video.js'

const router = useRouter()
const route = useRoute()
const historyStore = useHistoryStore()
const userStore = useUserStore()

const videoRef = ref(null)
const player = ref(null)
const detail = ref({})
const episode = ref({})
const episodes = ref([])
const playUrl = ref('')
let timer = null

const loadData = async () => {
  const res = await get(`/video/play/${route.params.id}`)
  detail.value = res.data.detail || {}
  episode.value = res.data.episode || {}
  episodes.value = res.data.episodes || []
  playUrl.value = res.data.play_url || ''

  await nextTick()
  player.value = videojs(videoRef.value, {
    fluid: true,
    aspectRatio: '16:9',
    preload: 'auto'
  })

  player.value.src(playUrl.value)
}

const onTimeUpdate = () => {
  if (!timer) {
    timer = setTimeout(() => {
      const progress = videoRef.value.currentTime / videoRef.value.duration
      historyStore.addHistory({
        video_id: detail.value.id,
        episode_id: episode.value.id,
        title: detail.value.title,
        cover_url: detail.value.cover_url,
        last_position: videoRef.value.currentTime,
        progress: progress
      })
      if (userStore.isLogin) {
        post('/history/sync', {
          video_id: detail.value.id,
          episode_id: episode.value.id,
          last_position: videoRef.value.currentTime
        })
      }
      timer = null
    }, 5000)
  }
}

const onEnded = () => {
  const idx = episodes.value.findIndex(e => e.id === episode.value.id)
  if (idx < episodes.value.length - 1) {
    changeEpisode(episodes.value[idx + 1].id)
  }
}

const changeEpisode = (epId) => {
  router.replace(`/play/${epId}`)
  loadData()
}

const formatCount = (count) => {
  if (count >= 10000) return (count / 10000).toFixed(1) + '万'
  return count
}

onMounted(() => loadData())
</script>

<style lang="scss" scoped>
.play-page {
  min-height: 100vh;
  background: #f5f5f5;
}

.player-wrapper {
  background: #000;

  .video-js {
    width: 100%;
  }
}

.content {
  padding: 16px;

  .video-info {
    background: white;
    padding: 16px;
    border-radius: 8px;

    h2 {
      font-size: 18px;
      font-weight: 600;
    }

    .meta {
      margin-top: 8px;
      font-size: 13px;
      color: #999;
    }
  }

  .episode-section {
    margin-top: 12px;
    background: white;
    padding: 16px;
    border-radius: 8px;

    .title {
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

        &.active {
          background: #1989fa;
          color: white;
        }
      }
    }
  }
}
</style>
