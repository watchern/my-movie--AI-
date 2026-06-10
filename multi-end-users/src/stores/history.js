import { defineStore } from 'pinia'
import { ref, watch } from 'vue'
import { post } from '@/utils/request'

export const useHistoryStore = defineStore('history', () => {
  // 清理 null 和无效数据
  const cleanList = (list) => {
    if (!Array.isArray(list)) return []
    return list.filter(item => item && item.video_id).slice(0, 100)
  }
  
  const historyList = ref(cleanList(JSON.parse(localStorage.getItem('historyList') || '[]')))

  watch(historyList, (newVal) => {
    localStorage.setItem('historyList', JSON.stringify(newVal.slice(0, 100)))
  }, { deep: true })

  const addHistory = (item) => {
    if (!item || !item.video_id) return // 防御性检查
    
    const index = historyList.value.findIndex(h => h && h.video_id === item.video_id && h.episode_id === item.episode_id)
    if (index > -1) {
      // 更新现有记录
      historyList.value[index].last_position = item.last_position
      historyList.value[index].progress = item.progress
      historyList.value[index].watched_at = new Date().toISOString()
      // 将更新的记录移到最前面
      const updatedItem = historyList.value.splice(index, 1)[0]
      historyList.value.unshift(updatedItem)
    } else {
      historyList.value.unshift({
        ...item,
        watched_at: new Date().toISOString()
      })
    }
  }

  const removeHistory = (id) => {
    const index = historyList.value.findIndex(h => h.id === id)
    if (index > -1) historyList.value.splice(index, 1)
  }

  const clearHistory = () => {
    historyList.value = []
  }

  const getHistory = (videoId) => {
    return historyList.value.find(h => h.video_id === videoId)
  }

  // 同步本地历史到服务器
  const syncToServer = async () => {
    if (historyList.value.length === 0) return { synced: 0 }
    
    try {
      const res = await post('/history/sync', {
        items: historyList.value.map(item => ({
          video_id: item.video_id,
          episode_id: item.episode_id,
          progress: item.progress,
          last_position: item.last_position,
          duration: item.duration,
          watched_at: item.watched_at
        }))
      })
      return res.data || { synced: 0 }
    } catch (e) {
      console.error('同步历史记录失败', e)
      return { synced: 0 }
    }
  }

  // 合并服务器数据到本地（保留本地更新更快的记录）
  const mergeServerData = (serverList) => {
    if (!serverList || !Array.isArray(serverList)) return
    
    for (const serverItem of serverList) {
      const localIndex = historyList.value.findIndex(
        h => h.video_id === serverItem.video_id && h.episode_id === serverItem.episode_id
      )
      
      if (localIndex === -1) {
        // 本地没有，添加服务器数据
        historyList.value.push({
          id: serverItem.id,
          video_id: serverItem.video_id,
          episode_id: serverItem.episode_id,
          progress: serverItem.progress,
          last_position: serverItem.last_position,
          duration: serverItem.duration,
          watched_at: serverItem.watched_at
        })
      }
      // 如果本地已有，不覆盖（本地数据优先）
    }
    
    // 按时间排序
    historyList.value.sort((a, b) => new Date(b.watched_at) - new Date(a.watched_at))
  }

  return { historyList, addHistory, removeHistory, clearHistory, getHistory, syncToServer, mergeServerData }
})
