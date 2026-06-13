<template>
    <div class="page">
        <!-- 左侧导航（大屏幕 >= 500px） -->
        <van-sidebar v-model="activeSidebar" class="sidebar-nav" @change="onSidebarChange">
            <van-sidebar-item title="首页" />
            <van-sidebar-item title="排行榜" />
            <van-sidebar-item title="我的" />
        </van-sidebar>

        <!-- 右侧内容区域 -->
        <div class="content-wrapper">
            <van-pull-refresh v-model="refreshing" @refresh="onRefresh">
                <van-nav-bar title="影视系统" placeholder>
                    <template #right>
                        <van-icon name="search" size="20" @click="goSearch" />
                    </template>
                </van-nav-bar>

                <div v-if="loading" class="loading-wrapper">
                    <van-loading>加载中...</van-loading>
                </div>

                <div v-else>
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

                    <div class="banner-section">
                        <van-swipe :autoplay="3000" indicator-color="white" :height="180" loop>
                            <van-swipe-item v-for="item in banners" :key="item.id" @click="handleBannerClick(item)">
                                <img :src="item.cover_url" :alt="item.title" />
                            </van-swipe-item>
                        </van-swipe>
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
                                <div class="video-info">
                                    <span>{{ item.release_year }}</span>
                                    <span>{{ item.region }}</span>
                                </div>
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
                                <div class="video-info">
                                    <span>{{ item.release_year }}</span>
                                    <span>{{ item.region }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </van-pull-refresh>
        </div>

        <!-- 底部导航（小屏幕 < 500px） -->
        <van-tabbar v-model="activeTab" class="bottom-tabbar" @change="onTabChange">
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
const activeSidebar = ref(0)  // 左侧导航当前选中索引
const activeTab = ref('home')  // 底部导航当前选中
const loading = ref(true)

const banners = ref([])
const hotMovies = ref([])
const hotTvs = ref([])
const hotAnimes = ref([])

const loadData = async () => {
    loading.value = true
    const res = await get('/video/home')
    banners.value = res.data.banners || []
    hotMovies.value = res.data.hot_movies || []
    hotTvs.value = res.data.hot_tvs || []
    hotAnimes.value = res.data.hot_animes || []
    loading.value = false
}

const onRefresh = async () => {
    await loadData()
    refreshing.value = false
}

const onTabChange = (name) => {
    activeSidebar.value = name === 'home' ? 0 : name === 'rank' ? 1 : 2
    if (name === 'rank') router.push('/rank')
    else if (name === 'user') router.push('/user')
}

// 左侧导航切换
const onSidebarChange = (index) => {
    activeTab.value = index === 0 ? 'home' : index === 1 ? 'rank' : 'user'
    if (index === 1) router.push('/rank')
    else if (index === 2) router.push('/user')
}

const goDetail = (id) => router.push(`/detail/${id}`)
const goCategory = (type) => router.push(`/category/${type}`)
const goSearch = () => {
    console.log('search')
}

const handleBannerClick = (item) => {
    if (item.type === 'ad' && item.link_url) {
        window.open(item.link_url, '_blank')
    } else {
        goDetail(item.id)
    }
}

const formatCount = (count) => {
    if (count >= 10000) return (count / 10000).toFixed(1) + '万'
    return count
}

onMounted(() => loadData())
</script>

<style lang="scss" scoped>
.page {
    display: flex;
    min-height: 100vh;
    overflow-x: hidden;
    width: 100%;
}

// 右侧内容区域
.content-wrapper {
    flex: 1;
    min-height: 100vh;
    background: #f5f5f5;
    overflow-x: hidden;
}

// 左侧导航（大屏幕 >= 500px）
.sidebar-nav {
    display: none;
    width: 100px;
    background: white;
    box-shadow: 2px 0 8px rgba(0, 0, 0, 0.1);
    flex-shrink: 0;
    
    @media (min-width: 500px) {
        display: block;
    }
    
    :deep(.van-sidebar-item) {
        padding: 20px 12px;
        font-size: 14px;
        
        &.van-sidebar-item--select {
            color: #1989fa;
            font-weight: 500;
        }
    }
}

// 底部导航（小屏幕 < 500px）
.bottom-tabbar {
    display: none;
    
    @media (max-width: 499px) {
        display: flex;
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        z-index: 999;
    }
}

.loading-wrapper {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 60vh;
    padding-top: 20px;
}

.banner-section {
    margin: 12px 16px;
    border-radius: 8px;
    overflow: hidden;

    :deep(.van-swipe) {
        height: 180px;
    }

    :deep(.van-swipe-item) {
        display: block;
        height: 180px;

        img {
            width: 100%;
            height: 100%;
            object-fit: cover;
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

.section-title {
    overflow: hidden;

    :deep(.van-swipe) {
        height: 180px;
    }

    :deep(.van-swipe-item) {
        display: block;
        height: 180px;

        img {
            width: 100%;
            height: 100%;
            object-fit: cover;
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

.section-title {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 16px 10px;
    font-size: 16px;
    font-weight: 600;
    color: #333;

    .more {
        font-size: 12px;
        font-weight: normal;
        color: #999;
    }
}

.video-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 6px;
    padding: 0 16px;

    @media (min-width: 768px) {
        grid-template-columns: repeat(6, 1fr);
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