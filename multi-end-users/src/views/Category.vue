<template>
  <div class="page">
    <van-nav-bar :title="pageTitle" left-arrow @click-left="goBack" :fixed="true" placeholder />

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
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { get } from '@/utils/request'
import { useSafeBack } from '@/utils/router'

const router = useRouter()
const route = useRoute()
const { safeBack } = useSafeBack()

const type = computed(() => +route.params.type)
const pageTitle = computed(() => {
  const titles = { 1: '电影', 2: '电视剧', 3: '动漫', 4: '短视频', 5: '纪录片' }
  return titles[type.value] || '分类'
})

const refreshing = ref(false)
const list = ref([])
const loading = ref(false)
const finished = ref(false)
let page = 1

const formatCount = (count) => {
  if (count >= 10000) {
    return (count / 10000).toFixed(1) + '万'
  }
  return count
}

const loadList = async () => {
  if (loading.value) return
  loading.value = true
  
  const res = await get('/video/list', {
    type: type.value,
    page,
    limit: 20
  })
  
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

onMounted(() => loadList())
</script>

<style lang="scss" scoped>
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
