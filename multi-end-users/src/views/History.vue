<template>
  <div class="page">
    <van-nav-bar title="观看历史" left-arrow @click-left="goBack" :fixed="true" placeholder />

    <div v-if="loading" class="loading-wrapper">
      <van-loading>加载中...</van-loading>
    </div>
    <div v-else>
      <div v-if="list.length" class="list">
        <div v-for="item in list" :key="item.id" class="item" @click="goPlay(item.episode_id)">
          <img :src="item.cover_url" :alt="item.title" />
          <div class="info">
            <div class="title">{{ item.title }}</div>
            <div class="time">{{ item.watched_at }}</div>
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
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { get } from '@/utils/request'
import { useHistoryStore } from '@/stores/history'
import { useUserStore } from '@/stores/user'

const router = useRouter()
const historyStore = useHistoryStore()
const userStore = useUserStore()
const list = ref([])
const loading = ref(true)

const loadList = async () => {
  loading.value = true
  if (userStore.isLogin) {
    const res = await get('/history/list')
    list.value = res.data || []
  } else {
    list.value = historyStore.historyList
  }
  loading.value = false
}

const goPlay = (epId) => router.push(`/play/${epId}`)
const goBack = () => router.back()

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

.list {
  padding: 12px 16px;
}

.item {
  display: flex;
  gap: 12px;
  background: white;
  padding: 12px;
  border-radius: 8px;
  margin-bottom: 12px;

  img {
    width: 100px;
    height: 140px;
    border-radius: 6px;
    object-fit: cover;
  }

  .info {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;

    .title {
      font-size: 16px;
      font-weight: 500;
    }

    .time {
      margin-top: 8px;
      font-size: 13px;
      color: #999;
    }
  }
}

.empty {
  padding-top: 80px;
}
</style>
