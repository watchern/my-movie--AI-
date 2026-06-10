<template>
  <div class="page">
    <van-nav-bar title="我的收藏" left-arrow @click-left="goBack" :fixed="true" placeholder />

    <div v-if="loading" class="loading-wrapper">
      <van-loading>加载中...</van-loading>
    </div>
    <div v-else>
      <div v-if="list.length" class="grid">
        <div v-for="item in list" :key="item.id" class="item" @click="goDetail(item.video_id)">
          <img :src="item.cover_url" :alt="item.title" />
          <div class="title">{{ item.title }}</div>
        </div>
      </div>
      <div v-else class="empty">
        <van-empty description="暂无收藏" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { get } from '@/utils/request'
import { useSafeBack } from '@/utils/router'

const router = useRouter()
const { safeBack } = useSafeBack()
const list = ref([])
const loading = ref(true)

const loadList = async () => {
  loading.value = true
  const res = await get('/favorite/list')
  list.value = res.data || []
  loading.value = false
}

const goDetail = (id) => router.push(`/detail/${id}`)
const goBack = () => safeBack('/')

onMounted(() => loadList())
</script>

<style lang="scss" scoped>
.loading-wrapper {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 60vh;
  padding-top: 20px;
}

.grid {
  padding: 12px 16px;
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 12px;
}

.item {
  img {
    width: 100%;
    aspect-ratio: 3/4;
    object-fit: cover;
    border-radius: 6px;
  }

  .title {
    margin-top: 6px;
    font-size: 14px;
    color: #333;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
}

.empty {
  padding-top: 80px;
}
</style>
