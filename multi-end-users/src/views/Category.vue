<template>
  <div class="page">
    <van-nav-bar :title="pageTitle" left-arrow @click-left="goBack" :fixed="true" placeholder />

    <van-pull-refresh v-model="refreshing" @refresh="onRefresh">
      <div class="video-list">
        <div class="video-grid">
          <div v-for="item in list" :key="item.id" class="video-item" @click="goDetail(item.id)">
            <div class="video-cover">
              <img :src="item.cover_url" :alt="item.title" />
              <span v-if="item.is_vip" class="vip-tag">VIP</span>
            </div>
            <div class="video-title">{{ item.title }}</div>
          </div>
        </div>
        <van-loading v-if="loading" class="loading-center" />
        <van-empty v-if="!loading && list.length === 0" description="暂无数据" />
      </div>
    </van-pull-refresh>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { get } from '@/utils/request'

const router = useRouter()
const route = useRoute()

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
const goBack = () => router.back()

onMounted(() => loadList())
</script>

<style lang="scss" scoped>
.video-list {
  min-height: 60vh;
}

.video-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 12px;
  padding: 12px 16px;
}

.video-item {
  .video-cover {
    position: relative;
    border-radius: 6px;
    overflow: hidden;

    img {
      width: 100%;
      aspect-ratio: 3/4;
      object-fit: cover;
    }

    .vip-tag {
      position: absolute;
      top: 6px;
      right: 6px;
      background: linear-gradient(135deg, #ff9500, #ff6a00);
      color: white;
      padding: 2px 6px;
      border-radius: 4px;
      font-size: 12px;
    }
  }

  .video-title {
    margin-top: 6px;
    font-size: 14px;
    color: #333;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
}

.loading-center {
  display: flex;
  justify-content: center;
  padding: 20px;
}
</style>
