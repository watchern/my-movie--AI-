<template>
  <div class="page">
    <van-nav-bar :title="detail.title" left-arrow @click-left="goBack" :fixed="true" placeholder />

    <div v-if="detail.id" class="detail">
      <div class="detail-header">
        <img :src="detail.cover" :alt="detail.title" />
        <div class="info">
          <div class="title">{{ detail.title }}</div>
          <div class="sub">
            <span>{{ detail.release_year }}</span>
            <span>{{ detail.region }}</span>
            <span v-if="detail.is_vip" class="vip">VIP</span>
          </div>
          <div class="tags">
            <van-tag type="primary" size="mini" v-for="t in (detail.tags || '').split(',')" :key="t">{{ t }}</van-tag>
          </div>
        </div>
      </div>

      <div class="section">
        <div class="section-title">简介</div>
        <div class="desc">{{ detail.description || '暂无简介' }}</div>
      </div>

      <div class="section" v-if="episodes.length">
        <div class="section-title">选集</div>
        <div class="episode-list">
          <van-tag
            v-for="ep in episodes"
            :key="ep.id"
            type="primary"
            plain
            @click="goPlay(ep.id)"
          >{{ ep.title }}</van-tag>
        </div>
      </div>

      <div class="section">
        <div class="section-title">猜你喜欢</div>
        <div class="video-grid">
          <div v-for="item in related" :key="item.id" class="video-item" @click="goDetail(item.id)">
            <div class="video-cover">
              <img :src="item.cover_url" :alt="item.title" />
            </div>
            <div class="video-title">{{ item.title }}</div>
          </div>
        </div>
      </div>

      <van-goods-action>
        <van-goods-action-icon icon="star-o" :badge="isFavorited ? '' : ''" @click="toggleFav">
          {{ isFavorited ? '已收藏' : '收藏' }}
        </van-goods-action-icon>
        <van-goods-action-button type="primary" @click="playFirst">
          立即播放
        </van-goods-action-button>
      </van-goods-action>
    </div>

    <div v-else class="loading-wrapper">
      <van-loading>加载中...</van-loading>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { get, post } from '@/utils/request'
import { useUserStore } from '@/stores/user'

const router = useRouter()
const route = useRoute()
const userStore = useUserStore()

const detail = ref({})
const episodes = ref([])
const related = ref([])
const isFavorited = ref(false)

const loadDetail = async () => {
  const res = await get('/video/detail', { id: route.params.id })
  detail.value = res.data || {}
  episodes.value = res.data?.episodes || []
  // 猜你喜欢暂时使用空数组，后端未提供该接口
  related.value = []
  isFavorited.value = res.data?.is_favorited || false
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

const playFirst = () => {
  if (episodes.value.length) {
    goPlay(episodes.value[0].id)
  }
}

const goPlay = (epId) => router.push(`/play/${epId}`)
const goDetail = (id) => router.push(`/detail/${id}`)
const goBack = () => router.back()

onMounted(() => loadDetail())
</script>

<style lang="scss" scoped>
.loading-wrapper {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 60vh;
  padding-top: 20px;
}

.detail-header {
  display: flex;
  gap: 16px;
  padding: 16px;
  background: white;

  img {
    width: 120px;
    height: 160px;
    border-radius: 8px;
    object-fit: cover;
  }

  .info {
    flex: 1;

    .title {
      font-size: 18px;
      font-weight: 600;
      color: #333;
    }

    .sub {
      margin-top: 8px;
      font-size: 13px;
      color: #666;

      span {
        margin-right: 12px;

        &.vip {
          color: #ff6a00;
          font-weight: 500;
        }
      }
    }

    .tags {
      margin-top: 12px;
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
    }
  }
}

.section {
  margin-top: 12px;
  padding: 16px;
  background: white;

  .section-title {
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 12px;
  }

  .desc {
    color: #666;
    font-size: 14px;
    line-height: 1.8;
  }

  .episode-list {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
  }
}

.video-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 12px;

  .video-item {
    .video-cover {
      img {
        width: 100%;
        aspect-ratio: 3/4;
        object-fit: cover;
        border-radius: 6px;
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
}
</style>
