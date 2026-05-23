import axios from 'axios'
import { showToast, showLoadingToast, closeToast } from 'vant'
import { useUserStore } from '@/stores/user'

const service = axios.create({
  baseURL: '/api/v1',
  timeout: 30000
})

service.interceptors.request.use(
  config => {
    const userStore = useUserStore()
    if (userStore.token) {
      config.headers.Authorization = `Bearer ${userStore.token}`
    }
    return config
  },
  error => Promise.reject(error)
)

service.interceptors.response.use(
  response => {
    const res = response.data
    if (res.code !== 200) {
      showToast(res.msg || '请求失败')
      if (res.code === 401) {
        const userStore = useUserStore()
        userStore.logout()
      }
      return Promise.reject(new Error(res.msg || '请求失败'))
    }
    return res
  },
  error => {
    showToast(error.message || '网络错误')
    return Promise.reject(error)
  }
)

export default service

export const get = (url, params) => service.get(url, { params })
export const post = (url, data) => service.post(url, data)
