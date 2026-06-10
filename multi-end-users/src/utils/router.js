import { useRouter } from 'vue-router'

export function useSafeBack() {
  const router = useRouter()

  const safeBack = (fallbackPath = '/') => {
    // 判断是否有可返回的历史记录
    const canGoBack = window.history.length > 1 || window.history.state?.position > 0
    
    if (canGoBack) {
      window.history.back()
    } else {
      // 没有可返回的历史记录，跳转到指定页面
      router.replace(fallbackPath)
    }
  }

  return {
    safeBack
  }
}
