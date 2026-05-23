import { defineStore } from 'pinia'
import { ref, watch } from 'vue'

export const useHistoryStore = defineStore('history', () => {
  const historyList = ref(JSON.parse(localStorage.getItem('historyList') || '[]'))

  watch(historyList, (newVal) => {
    localStorage.setItem('historyList', JSON.stringify(newVal.slice(0, 100)))
  }, { deep: true })

  const addHistory = (item) => {
    const index = historyList.value.findIndex(h => h.video_id === item.video_id && h.episode_id === item.episode_id)
    if (index > -1) {
      historyList.value[index].last_position = item.last_position
      historyList.value[index].progress = item.progress
      historyList.value[index].watched_at = new Date().toISOString()
      historyList.value.splice(index, 1)
      historyList.value.unshift(historyList.value[index])
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

  return { historyList, addHistory, removeHistory, clearHistory, getHistory }
})
