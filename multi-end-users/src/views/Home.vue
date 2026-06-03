<template>
    <div class="page">
        <van-pull-refresh v-model="refreshing" @refresh="onRefresh">
            <van-nav-bar title="影视系统" :fixed="true" placeholder>
                <template #right>
                    <van-icon name="search" size="20" @click="goSearch" />
                </template>
            </van-nav-bar>

            <div class="banner-section">
                <van-swipe :autoplay="3000" indicator-color="white" height="180" loop>
                    <van-swipe-item v-for="item in banners" :key="item.id" @click="goDetail(item.id)">
                        <div class="banner-item">
                            <img :src="item.cover_url" :alt="item.title" />
                        </div>
                    </van-swipe-item>
                </van-swipe>
            </div>

            <div class="category-nav">
                <div class="nav-item" @click="goCategory(1)">
                    <van-icon name="video" size="32" color="#1989fa" />
                    <span>电影</span>
                </div>
                <div class="nav-item" @click="goCategory(2)">
                    <van-icon name="tv-o" size="32" color="#ff976a" />
                    <span>电视剧</span>
                </div>
                <div class="nav-item" @click="goCategory(3)">
                    <van-icon name="fire" size="32" color="#ee0a24" />
                    <span>动漫</span>
                </div>
                <div class="nav-item" @click="goCategory(4)">
                    <van-icon name="play-circle-o" size="32" color="#07c160" />
                    <span>短视频</span>
                </div>
                <div class="nav-item" @click="goCategory(5)">
                    <van-icon name="location-o" size="32" color="#1989fa" />
                    <span>纪录片</span>
                </div>
            </div>

            <div v-if="hotMovies.length" class="section">
                <div class="section-title">
                    <span>热门电影</span>
                    <span class="more" @click="goCategory(1)">更多 ></span>
                </div>
                <div class="video-grid">
                    <div v-for="item in hotMovies" :key="item.id" class="video-item" @click="goDetail(item.id)">
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
            </div>

            <div v-if="hotTvs.length" class="section">
                <div class="section-title">
                    <span>热门电视剧</span>
                    <span class="more" @click="goCategory(2)">更多 ></span>
                </div>
                <div class="video-grid">
                    <div v-for="item in hotTvs" :key="item.id" class="video-item" @click="goDetail(item.id)">
                        <div class="video-cover">
                            <img :src="item.cover_url" :alt="item.title" />
                            <span v-if="item.is_vip" class="vip-tag">VIP</span>
                        </div>
                        <div class="video-title">{{ item.title }}</div>
                    </div>
                </div>
            </div>

            <div v-if="hotAnimes.length" class="section">
                <div class="section-title">
                    <span>热门动漫</span>
                    <span class="more" @click="goCategory(3)">更多 ></span>
                </div>
                <div class="video-grid">
                    <div v-for="item in hotAnimes" :key="item.id" class="video-item" @click="goDetail(item.id)">
                        <div class="video-cover">
                            <img :src="item.cover_url" :alt="item.title" />
                            <span v-if="item.is_vip" class="vip-tag">VIP</span>
                        </div>
                        <div class="video-title">{{ item.title }}</div>
                    </div>
                </div>
            </div>
        </van-pull-refresh>

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
const refreshing = ref(false)
const active = ref('home')

const banners = ref([])
const hotMovies = ref([])
const hotTvs = ref([])
const hotAnimes = ref([])

const loadData = async () => {
    const res = await get('/video/home')
    banners.value = res.data.banners || []
    hotMovies.value = res.data.hot_movies || []
    hotTvs.value = res.data.hot_tvs || []
    hotAnimes.value = res.data.hot_animes || []
}

const onRefresh = async () => {
    await loadData()
    refreshing.value = false
}

const onTabChange = (name) => {
    if (name === 'rank') router.push('/rank')
    else if (name === 'user') router.push('/user')
}

const goDetail = (id) => router.push(`/detail/${id}`)
const goCategory = (type) => router.push(`/category/${type}`)
const goSearch = () => {
    console.log('search')
}

const formatCount = (count) => {
    if (count >= 10000) return (count / 10000).toFixed(1) + '万'
    return count
}

onMounted(() => loadData())
</script>

<style lang="scss" scoped>
.page {
    padding-top: 46px;
}

.banner-section {
    margin: 12px 16px;
    border-radius: 8px;
    overflow: hidden;

    .banner-item {
        width: 100%;
        height: 100%;

        img {
            width: 100%;
            height: 180px;
            object-fit: cover;

            @media (min-width: 768px) {
                height: 280px;
            }

            @media (min-width: 1024px) {
                height: 360px;
            }
        }
    }

    :deep(.van-swipe__indicators) {
        bottom: 10px;
    }

    :deep(.van-swipe__indicator) {
        width: 6px;
        height: 6px;
        opacity: 0.5;

        &.van-swipe__indicator--active {
            width: 16px;
            opacity: 1;
        }
    }
}

.category-nav {
    display: flex;
    justify-content: space-around;
    padding: 20px 0;
    background: white;
    margin: 0 16px;
    border-radius: 8px;
    margin-top: 16px;

    .nav-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 6px;

        span {
            font-size: 13px;
            color: #333;
        }
    }
}

.video-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 8px;
}

.video-item {
    .video-cover {
        position: relative;
        border-radius: 6px;
        overflow: hidden;

        img {
            width: 100%;
            aspect-ratio: 1/1.5;
            object-fit: cover;
        }

        .vip-tag {
            position: absolute;
            top: 3px;
            right: 3px;
            background: linear-gradient(135deg, #ff9500, #ff6a00);
            color: white;
            padding: 1px 3px;
            border-radius: 2px;
            font-size: 9px;
        }

        .play-count {
            position: absolute;
            bottom: 3px;
            right: 3px;
            background: rgba(0, 0, 0, 0.6);
            color: white;
            padding: 1px 3px;
            border-radius: 2px;
            font-size: 9px;
        }
    }

    .video-title {
        font-size: 12px;
        color: #333;
        margin-top: 4px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .video-info {
        display: flex;
        gap: 6px;
        font-size: 10px;
        color: #999;
        margin-top: 2px;
    }
}
</style>