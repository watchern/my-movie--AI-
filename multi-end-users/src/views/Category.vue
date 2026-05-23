<template>
  <div class="page">
    <van-nav-bar :title="pageTitle" left-arrow @click-left="goBack" :fixed="true" placeholder />

    <van-tabs v-model:active="tabActive" sticky>
      <van-tab v-for="cat in categories" :key="cat.id" :title="cat.name">
        <van-list v-model:loading="loading" :finished="finished" @load="loadList">
          <div class="video-grid">
            <div v-for="item in list" :key="item.id" class="video-item" @click="goDetail(item.id)">
              <div class="video-cover">
                <img :src="item.cover_url" :alt="item.title" />
                <span v-if="item.is_vip" class="vip-tag">VIP</span>
              </div>
              <div class="video-title">{{ item.title }}</div>
            </div>
          </div>
        </van-list>
      </van-tab>
    </van-tabs>
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
  const titles = { 1: '电影', 2: '电视剧', 3: '动漫', 4: '短视频' }
  return titles[type.value] || '分类'
})

const tabActive = ref(0)
const categories = ref([])
const list = ref([])
const loading = ref(false)
const finished = ref(false)
let page = 1

const loadList = async () => {
  const res = await get('/video/list', {
    type: type.value,
    category_id: categories.value[tabActive.value]?.id,
    page,
    limit: 20
  })
  list.value = page === 1 ? res.data.list : [...list.value, ...res.data.list]
  finished.value = page >= res.data.total_pages
  page++
  loading.value = false
}

const goDetail = (id) => router.push(`/detail/${id}`)
const goBack = () => router.back()

onMounted(() => loadList())
</script>

<style lang="scss" scoped>
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
</style>
