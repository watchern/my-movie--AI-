<template>
  <div class="page">
    <van-nav-bar title="卡密兑换" left-arrow @click-left="goBack" :fixed="true" placeholder />

    <div class="content">
      <div class="tip">输入卡密兑换VIP时长</div>
      <van-field v-model="cardKey" placeholder="请输入卡密" clearable />
      <van-button type="primary" block :loading="loading" @click="onExchange">立即兑换</van-button>

      <div class="ad-section">
        <div class="title">或观看广告获得VIP</div>
        <van-button type="success" block @click="onAd">观看广告</van-button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { post } from '@/utils/request'
import { showToast } from 'vant'
import { useUserStore } from '@/stores/user'
import { useSafeBack } from '@/utils/router'

const router = useRouter()
const userStore = useUserStore()
const { safeBack } = useSafeBack()

const cardKey = ref('')
const loading = ref(false)

const onExchange = async () => {
  if (!cardKey.value) {
    showToast('请输入卡密')
    return
  }
  loading.value = true
  try {
    const res = await post('/card/exchange', { card_key: cardKey.value })
    showToast(res.msg || '兑换成功')
    await userStore.refreshUser()
  } catch (e) {
  } finally {
    loading.value = false
  }
}

const onAd = () => {
  showToast('广告功能开发中')
}

const goBack = () => safeBack('/')
</script>

<style lang="scss" scoped>
.content {
  padding: 24px 16px;

  .tip {
    font-size: 14px;
    color: #666;
    margin-bottom: 12px;
  }

  .ad-section {
    margin-top: 40px;

    .title {
      font-size: 14px;
      color: #666;
      margin-bottom: 12px;
    }
  }
}
</style>
