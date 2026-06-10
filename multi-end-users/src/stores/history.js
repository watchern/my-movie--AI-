import { defineStore } from 'pinia'
import { ref, watch } from 'vue'
import { post } from '@/utils/request'

export const useHistoryStore = defineStore('history', () => {
  // 清理 null 和无效数据，并去重（同一视频只保留一条）
  const cleanList = (list) => {
    if (!Array.isArray(list)) return []
    const seen = new Set()
    const result = []
    for (const item of list) {
      if (!item || !item.video_id) continue
      if (seen.has(item.video_id)) continue // 跳过重复
      seen.add(item.video_id)
      result.push(item)
    }
    return result.slice(0, 100)
  }
  
  const historyList = ref(cleanList(JSON.parse(localStorage.getItem('historyList') || '[]')))

  watch(historyList, (newVal) => {
    localStorage.setItem('historyList', JSON.stringify(newVal.slice(0, 100)))
  }, { deep: true })

  const addHistory = (item) => {
    if (!item || !item.video_id) return // 防御性检查
    
    // 只根据 video_id 判断是否重复（同一视频只保留一条记录）
    const index = historyList.value.findIndex(h => h && h.video_id === item.video_id)
    if (index > -1) {
      // 更新现有记录（使用新的选集信息）
      historyList.value[index].episode_id = item.episode_id
      // 优先使用新的 episode_name，如果为空则尝试从 episode_id 推断
      historyList.value[index].episode_name = item.episode_name || `第${item.episode_id}集` || ''
      historyList.value[index].last_position = item.last_position
      historyList.value[index].progress = item.progress
      historyList.value[index].watched_at = new Date().toISOString()
      // 如果需要更新封面或标题
      if (item.cover_url) historyList.value[index].cover_url = item.cover_url
      if (item.title) historyList.value[index].title = item.title
      // 将更新的记录移到最前面
      const updatedItem = historyList.value.splice(index, 1)[0]
      historyList.value.unshift(updatedItem)
    } else {
      historyList.value.unshift({
        ...item,
        episode_name: item.episode_name || `第${item.episode_id}集` || '',
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
          title: serverItem.title,
          cover_url: serverItem.cover_url,
          episode_name: serverItem.episode_title || `第${serverItem.episode_id}集`,
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
