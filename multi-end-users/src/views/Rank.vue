<template>
  <div class="page">
    <van-nav-bar title="排行榜" :fixed="true" placeholder />

    <van-tabs v-model:active="tabActive" sticky>
      <van-tab title="热播榜">
        <van-list v-model:loading="loading" @load="loadList">
          <div class="rank-list">
            <div v-for="(item, index) in list" :key="item.id" class="rank-item" @click="goDetail(item.id)">
              <div class="rank-num" :class="'rank-' + Math.min(index, 3)">{{ index + 1 }}</div>
              <img :src="item.cover_url" :alt="item.title" />
              <div class="info">
                <div class="title">{{ item.title }}</div>
                <div class="counts">
                  <span>播放 {{ formatCount(item.play_count) }}</span>
                </div>
                <div class="desc">{{ item.desc || '暂无简介' }}</div>
              </div>
            </div>
          </div>
        </van-list>
      </van-tab>
      <van-tab title="新上线">
        <div class="rank-list">
          <div v-for="(item, index) in newList" :key="item.id" class="rank-item" @click="goDetail(item.id)">
            <div class="rank-num" :class="'rank-' + Math.min(index, 3)">{{ index + 1 }}</div>
            <img :src="item.cover_url" :alt="item.title" />
            <div class="info">
              <div class="title">{{ item.title }}</div>
              <div class="counts">
                <span>播放 {{ formatCount(item.play_count) }}</span>
              </div>
              <div class="desc">{{ item.desc || '暂无简介' }}</div>
            </div>
          </div>
        </div>
      </van-tab>
    </van-tabs>

    <van-tabbar v-model="active" @change="onTabChange">
      <van-tabbar-item name="home" icon="home-o">首页</van-tabbar-item>
      <van-tabbar-item name="rank" icon="chart-trending-o">排行榜</van-tabbar-item>
      <van-tabbar-item name="user" icon="user-o">我的</van-tabbar-item>
    </van-tabbar>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { get } from '@/utils/request'

const router = useRouter()
const tabActive = ref(0)
const active = ref('rank')
const list = ref([])
const newList = ref([])
const loading = ref(false)

const loadList = async () => {
  const res = await get('/video/rank')
  list.value = res.data.list || []
  newList.value = res.data.new || []
  loading.value = false
}

const goDetail = (id) => router.push(`/detail/${id}`)
const onTabChange = (name) => {
  if (name === 'home') router.push('/home')
  else if (name === 'user') router.push('/user')
}

const formatCount = (count) => {
  if (count >= 10000) return (count / 10000).toFixed(1) + '万'
  return count
}

onMounted(() => loadList())
</script>

<style lang="scss" scoped>
.rank-list {
  padding: 12px 16px;
}

.rank-item {
  display: flex;
  gap: 12px;
  padding: 12px 0;
  border-bottom: 1px solid #eee;

  .rank-num {
    width: 28px;
    text-align: center;
    font-size: 18px;
    font-weight: 600;
    color: #999;

    &.rank-0 { color: #ff3c00; }
    &.rank-1 { color: #ff9800; }
    &.rank-2 { color: #ffc107; }
  }

  img {
    width: 90px;
    height: 120px;
    border-radius: 6px;
    object-fit: cover;
  }

  .info {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: flex-start;

    .title {
      font-size: 14px;
      font-weight: 500;
      color: #333;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    .counts {
      margin-top: 4px;
      font-size: 12px;
      color: #666;
    }

    .desc {
      margin-top: 4px;
      font-size: 12px;
      color: #999;
      overflow: hidden;
      text-overflow: ellipsis;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
    }
  }
}
</style>
