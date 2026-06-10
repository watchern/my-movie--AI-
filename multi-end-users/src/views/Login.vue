<template>
    <div class="page">
        <van-nav-bar title="登录" left-arrow @click-left="goBack" :fixed="true" placeholder />

        <div class="login">
            <div class="logo">
                <van-icon name="play-circle-o" size="64" color="#1989fa" />
            </div>
            <div class="form">
                <van-field v-model="form.email" label="邮箱" placeholder="请输入邮箱" clearable />
                <van-field v-model="form.password" :type="showPassword ? 'text' : 'password'" label="密码" placeholder="请输入密码" clearable>
                    <template #button>
                        <van-icon :name="showPassword ? 'closed-eye' : 'eye-o'" size="20" @click="showPassword = !showPassword" />
                    </template>
                </van-field>
                <van-button type="primary" block :loading="loading" @click="onLogin">登录</van-button>
            </div>
            <div class="link">
                <span @click="goRegister">没有账号？立即注册</span>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useUserStore } from '@/stores/user'
import { useSafeBack } from '@/utils/router'

const router = useRouter()
const userStore = useUserStore()
const { safeBack } = useSafeBack()

const form = ref({ email: '', password: '' })
const loading = ref(false)
const showPassword = ref(false)

const onLogin = async () => {
    if (!form.value.email || !form.value.password) {
        return
    }
    loading.value = true
    try {
        await userStore.login(form.value.email, form.value.password)
        router.replace('/user')
    } catch (e) {
    } finally {
        loading.value = false
    }
}

const goBack = () => safeBack('/')
const goRegister = () => router.replace('/register')
</script>

<style lang="scss" scoped>
.login {
    padding: 40px 24px;

    .logo {
        text-align: center;
        margin-bottom: 40px;
    }

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