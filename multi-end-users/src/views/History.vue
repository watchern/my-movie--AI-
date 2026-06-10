<template>
  <div class="page">
    <van-nav-bar title="观看历史" left-arrow @click-left="goBack" :fixed="true" placeholder />

    <div v-if="loading" class="loading-wrapper">
      <van-loading>加载中...</van-loading>
    </div>
    <div v-else>
      <div v-if="validList.length" class="list">
        <div v-for="(item, index) in validList" :key="item.id || `local-${item.video_id}-${item.episode_id}`" class="item" @click="goPlay(item.episode_id)">
          <div class="img-wrapper">
            <img :src="item.cover_url" :alt="item.title" />
            <div class="time-badge">{{ formatProgress(item) }}</div>
          </div>
          <div class="info">
            <div class="title">{{ item.title }}</div>
          </div>
        </div>
      </div>
      <div v-else class="empty">
        <van-empty description="暂无观看历史" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { get } from '@/utils/request'
import { useHistoryStore } from '@/stores/history'
import { useUserStore } from '@/stores/user'
import { useSafeBack } from '@/utils/router'
import { showToast } from 'vant'

const router = useRouter()
const historyStore = useHistoryStore()
const userStore = useUserStore()
const { safeBack } = useSafeBack()
const list = ref([])
const loading = ref(true)
const syncing = ref(false)

const validList = computed(() => {
  return list.value.filter(item => item && item.video_id)
})

const formatTime = (time) => {
  if (!time) return ''
  const date = new Date(time)
  const now = new Date()
  const diff = now - date
  if (diff < 60000) return '刚刚'
  if (diff < 3600000) return `${Math.floor(diff / 60000)} 分钟前`
  if (diff < 86400000) return `${Math.floor(diff / 3600000)} 小时前`
  if (diff < 604800000) return `${Math.floor(diff / 86400000)} 天前`
  return date.toLocaleDateString()
}

const formatProgress = (item) => {
  const lastPos = item.last_position || 0
  const mins = Math.floor(lastPos / 60)
  const secs = Math.floor(lastPos % 60)
  return `${mins}:${secs.toString().padStart(2, '0')}`
}

const loadHistory = async () => {
  loading.value = true
  try {
    if (userStore.isLogin) {
      // 如果有本地数据，先同步到服务器
      if (historyStore.historyList.length > 0) {
        await syncLocalToServer()
      }
      
      // 从服务器获取数据
      const res = await get('/history/list')
      list.value = res.data?.list || []
    } else {
      list.value = historyStore.historyList || []
    }
  } catch (e) {
    list.value = []
  }
  loading.value = false
}

// 同步本地数据到服务器
const syncLocalToServer = async () => {
  if (syncing.value) return
  syncing.value = true
  
  try {
    const result = await historyStore.syncToServer()
    if (result.synced > 0) {
      showToast(`已同步 ${result.synced} 条记录`)
    }
  } catch (e) {
    console.error('同步失败', e)
  } finally {
    syncing.value = false
  }
}

const goPlay = (epId) => router.push({ name: 'Detail', params: { id: '0' }, query: { episode_id: epId } })
const goBack = () => safeBack('/')

onMounted(() => loadHistory())
</script>

<style lang="scss" scoped>
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

  .img-wrapper {
    position: relative;
    
    img {
      width: 100%;
      aspect-ratio: 2/3;
      object-fit: cover;
    }
    
    .time-badge {
      position: absolute;
      bottom: 6px;
      right: 6px;
      background: rgba(0, 0, 0, 0.65);
      color: white;
      font-size: 12px;
      padding: 2px 6px;
      border-radius: 4px;
    }
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
