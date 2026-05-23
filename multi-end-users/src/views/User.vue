<template>
    <div class="page">
        <van-nav-bar title="我的" :fixed="true" placeholder />

        <div class="user-header" @click="goLogin">
            <div class="avatar">
                <van-icon name="user-o" size="48" />
            </div>
            <div class="info">
                <div class="name">{{ userStore.isLogin ? userStore.userInfo.email : '点击登录' }}</div>
                <div v-if="userStore.isVip" class="vip">VIP会员</div>
            </div>
        </div>

        <div class="menu-list">
            <div class="menu-item" @click="goHistory">
                <van-icon name="play-circle-o" />
                <span>观看历史</span>
                <van-icon name="arrow" />
            </div>
            <div class="menu-item" @click="goFavorites">
                <van-icon name="star-o" />
                <span>我的收藏</span>
                <van-icon name="arrow" />
            </div>
            <div class="menu-item" @click="goCard">
                <van-icon name="coupon-o" />
                <span>卡密兑换</span>
                <van-icon name="arrow" />
            </div>
            <div v-if="userStore.isLogin" class="menu-item" @click="logout">
                <van-icon name="log-out" />
                <span>退出登录</span>
                <van-icon name="arrow" />
            </div>
        </div>

        <van-tabbar v-model="active" @change="onTabChange">
            <van-tabbar-item name="home" icon="home-o">首页</van-tabbar-item>
            <van-tabbar-item name="rank" icon="chart-trending-o">排行榜</van-tabbar-item>
            <van-tabbar-item name="user" icon="user-o">我的</van-tabbar-item>
        </van-tabbar>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useUserStore } from '@/stores/user'
import { showConfirmDialog } from 'vant'

const router = useRouter()
const userStore = useUserStore()
const active = ref('user')

const goLogin = () => {
    if (!userStore.isLogin) router.push('/login')
}

const goHistory = () => router.push('/history')
const goFavorites = () => router.push('/favorites')
const goCard = () => router.push('/card')

const onTabChange = (name) => {
    if (name === 'home') router.push('/home')
    else if (name === 'rank') router.push('/rank')
}

const logout = () => {
    showConfirmDialog({
        title: '提示',
        message: '确定退出登录吗？'
    }).then(() => {
        userStore.logout()
        router.replace('/home')
    }).catch(() => {})
}
</script>

<style lang="scss" scoped>
.user-header {
    display: flex;
    gap: 16px;
    align-items: center;
    padding: 20px 16px;
    background: white;

    .avatar {
        width: 72px;
        height: 72px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f5f5f5;
    }

    .info {
        .name {
            font-size: 20px;
            font-weight: 600;
        }

        .vip {
            margin-top: 6px;
            color: #ff6a00;
            font-size: 14px;
        }
    }
}

.menu-list {
    margin-top: 12px;
    background: white;
    padding: 0 16px;

    .menu-item {
        display: flex;
        align-items: center;
        padding: 16px 0;
        border-bottom: 1px solid #f5f5f5;
        font-size: 15px;
        color: #333;

        &:last-child {
            border-bottom: none;
        }

        .van-icon {
            margin-right: 12px;
            color: #666;
        }

        .van-icon:last-child {
            margin-left: auto;
            margin-right: 0;
        }
    }
}
</style>