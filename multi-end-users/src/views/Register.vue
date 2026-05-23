<template>
    <div class="page">
        <van-nav-bar title="注册" left-arrow @click-left="goBack" :fixed="true" placeholder />

        <div class="register">
            <div class="form">
                <van-field v-model="form.email" label="邮箱" placeholder="请输入邮箱" clearable />
                <van-field v-model="form.password" type="password" label="密码" placeholder="请输入密码" clearable />
                <van-field v-model="form.password2" type="password" label="确认密码" placeholder="请再次输入密码" clearable />
                <van-button type="primary" block :loading="loading" @click="onRegister">注册</van-button>
            </div>
            <div class="link">
                <span @click="goLogin">已有账号？立即登录</span>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useUserStore } from '@/stores/user'
import { showToast } from 'vant'

const router = useRouter()
const userStore = useUserStore()

const form = ref({ email: '', password: '', password2: '' })
const loading = ref(false)

const onRegister = async () => {
    if (!form.value.email || !form.value.password || !form.value.password2) {
        showToast('请填写完整')
        return
    }
    if (form.value.password !== form.value.password2) {
        showToast('两次密码不一致')
        return
    }
    loading.value = true
    try {
        await userStore.register(form.value.email, form.value.password)
        showToast('注册成功')
        router.replace('/user')
    } catch (e) {
    } finally {
        loading.value = false
    }
}

const goBack = () => router.back()
const goLogin = () => router.replace('/login')
</script>

<style lang="scss" scoped>
.register {
    padding: 40px 24px;

    .form {
        .van-field {
            margin-bottom: 16px;
        }

        .van-button {
            margin-top: 24px;
            height: 44px;
            font-size: 16px;
        }
    }

    .link {
        margin-top: 24px;
        text-align: center;

        span {
            color: #1989fa;
            font-size: 14px;
        }
    }
}
</style>