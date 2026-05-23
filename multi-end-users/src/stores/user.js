import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { post } from '@/utils/request'

export const useUserStore = defineStore('user', () => {
    const token = ref(localStorage.getItem('token') || '')
    const userInfo = ref(JSON.parse(localStorage.getItem('userInfo') || '{}'))

    const isLogin = computed(() => !!token.value)
    const isVip = computed(() => userInfo.value.vip_status === 1)
    const vipExpire = computed(() => userInfo.value.vip_expire_time)

    const setToken = (newToken, refreshToken) => {
        token.value = newToken
        localStorage.setItem('token', newToken)
        if (refreshToken) localStorage.setItem('refreshToken', refreshToken)
    }

    const setUserInfo = (info) => {
        userInfo.value = info
        localStorage.setItem('userInfo', JSON.stringify(info))
    }

    const logout = () => {
        token.value = ''
        userInfo.value = {}
        localStorage.removeItem('token')
        localStorage.removeItem('refreshToken')
        localStorage.removeItem('userInfo')
    }

    const login = async (email, password) => {
        const res = await post('/auth/login', { email, password })
        setToken(res.data.token, res.data.refreshToken)
        setUserInfo(res.data)
        return res
    }

    const register = async (email, password, phone = '') => {
        const res = await post('/auth/register', { email, password, phone })
        setToken(res.data.token, res.data.refreshToken)
        setUserInfo(res.data)
        return res
    }

    const refreshUser = async () => {
        // TODO: 调用API获取用户信息
    }

    return {
        token,
        userInfo,
        isLogin,
        isVip,
        vipExpire,
        setToken,
        setUserInfo,
        logout,
        login,
        register,
        refreshUser
    }
})