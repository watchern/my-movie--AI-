<template>
  <div>
    <el-card>
      <template #header>
        <div class="header">
          <span>资源站点管理</span>
          <el-button type="primary" @click="showAddDialog = true">添加站点</el-button>
        </div>
      </template>
      
        <el-table :data="list" border stripe>
        <el-table-column prop="id" label="ID" width="80" resizable />
        <el-table-column prop="name" label="站点名称" min-width="120" resizable />
        <el-table-column prop="description" label="资源描述" min-width="200" resizable show-overflow-tooltip>
          <template #default="{ row }">
            {{ row.description || '-' }}
          </template>
        </el-table-column>
        <el-table-column prop="api_url" label="接口地址" min-width="250" resizable show-overflow-tooltip />
        <el-table-column prop="site_type" label="类型" width="100" resizable>
          <template #default="{ row }">
            {{ siteTypeOptions[row.site_type] || '苹果CMS' }}
          </template>
        </el-table-column>
        <el-table-column prop="page_count" label="总页数" width="80" resizable />
         <el-table-column prop="status" label="状态" width="120" resizable fixed="right">
           <template #default="{ row }">
             <el-switch :model-value="row.status" @change="toggleStatus(row)" />
           </template>
         </el-table-column>
         <el-table-column label="操作" width="150" resizable fixed="right">
           <template #default="{ row }">
             <el-tooltip content="编辑" placement="top">
               <el-button link type="primary" @click="handleEdit(row)"><el-icon><Edit /></el-icon></el-button>
             </el-tooltip>
             <el-tooltip content="测试连接" placement="top">
               <el-button link type="success" @click="testConnection(row)"><el-icon><Connection /></el-icon></el-button>
             </el-tooltip>
             <el-tooltip content="重置采集" placement="top">
               <el-button link type="warning" @click="resetCollectSite(row)"><el-icon><Refresh /></el-icon></el-button>
             </el-tooltip>
             <el-tooltip content="删除" placement="top">
               <el-button link type="danger" @click="handleDelete(row)"><el-icon><Delete /></el-icon></el-button>
             </el-tooltip>
           </template>
         </el-table-column>
      </el-table>
    </el-card>

    <el-card style="margin-top: 20px">
      <template #header>
        <div class="header">
          <span>采集任务</span>
          <div>
            <el-button type="danger" style="margin-right: 10px;" @click="resetCollect">强制重置采集</el-button>
            <el-button type="primary" @click="startCollectAll">采集全部</el-button>
          </div>
        </div>
      </template>
      
      <el-alert
        title="勾选要采集的站点，然后点击「采集全部」开始采集"
        type="info"
        :closable="false"
        style="margin-bottom: 15px;"
      />

      <el-table :data="list.filter(item => item.status)" border stripe>
        <el-table-column width="50" resizable>
          <template #default="{ row }">
            <el-checkbox v-model="row.selected" />
          </template>
        </el-table-column>
        <el-table-column prop="name" label="站点名称" min-width="120" resizable />
        <el-table-column prop="status" label="状态" width="80" resizable fixed="right">
          <template #default="{ row }">
            <el-tag 
              :type="row.collect_status === 'running' ? 'warning' : (row.collect_status === 'pending' ? 'info' : 'success')" 
              size="small"
            >
              {{ row.collect_status === 'running' ? '采集中' : (row.collect_status === 'pending' ? '排队中' : '就绪') }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="total" label="总数" width="80" resizable />
        <el-table-column label="当前视频" min-width="150" resizable show-overflow-tooltip>
          <template #default="{ row }">
            <span v-if="row.vod_name" style="color: #606266; font-size: 13px;">{{ row.vod_name }}</span>
            <span v-else style="color: #c0c4cc;">-</span>
          </template>
        </el-table-column>
        <el-table-column label="进度" width="150" resizable>
          <template #default="{ row }">
            <el-progress :percentage="row.percent || 0" :stroke-width="8" />
          </template>
        </el-table-column>
         <el-table-column label="操作" width="160" resizable fixed="right">
           <template #default="{ row }">
             <el-tooltip content="开始采集" placement="top">
               <el-button link type="primary" @click="startCollect(row)"><el-icon><VideoPlay /></el-icon></el-button>
             </el-tooltip>
             <el-tooltip content="处理下一个" placement="top">
               <el-button link type="success" @click="processNextOne(row)"><el-icon><SortDown /></el-icon></el-button>
             </el-tooltip>
             <el-tooltip content="刷新状态" placement="top">
               <el-button link type="info" @click="fetchProgress(row)"><el-icon><RefreshRight /></el-icon></el-button>
             </el-tooltip>
           </template>
         </el-table-column>
      </el-table>
    </el-card>

    <!-- 添加/编辑对话框 -->
    <el-dialog 
      v-model="showAddDialog" 
      :title="isEdit ? '编辑站点' : '添加站点'" 
      width="500px"
    >
      <el-form :model="form" label-width="100px">
        <el-form-item label="站点名称" required>
          <el-input v-model="form.name" placeholder="前台展示的名称，如：资源站A" />
        </el-form-item>
        <el-form-item label="资源描述">
          <el-input 
            v-model="form.description" 
            type="textarea" 
            :rows="3" 
            :maxlength="2000"
            show-word-limit
            placeholder="资源站点描述" 
          />
        </el-form-item>
        <el-form-item label="接口地址" required>
          <el-input v-model="form.api_url" placeholder="如：http://xxx.com/api.php" />
        </el-form-item>
        <el-form-item label="站点类型">
          <el-select v-model="form.site_type" style="width: 100%">
            <el-option :value="1" label="苹果CMS" />
            <el-option :value="2" label="其他类型" />
          </el-select>
        </el-form-item>
        <el-form-item label="状态">
          <el-switch v-model="form.status" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="showAddDialog = false">取消</el-button>
        <el-button type="primary" @click="saveSite">保存</el-button>
        <el-button type="success" @click="testConnection(form)">测试连接</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { get, post } from '@/utils/request'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Edit, Delete, Connection, Refresh, VideoPlay, RefreshRight, SortDown } from '@element-plus/icons-vue'

const list = ref([])
const showAddDialog = ref(false)
const isEdit = ref(false)
const form = ref({
  id: 0,
  name: '',
  description: '',
  api_url: '',
  site_type: 1,
  status: true,
  selected: false,
  collect_status: '',
  total: 0,
  percent: 0,
  vod_name: '',
})

const siteTypeOptions = {
  1: '苹果CMS',
  2: '其他类型',
}

const loadList = async () => {
  const res = await get('/collectSource/list')
  list.value = (res.data.list || []).map(item => ({
    ...item,
    status: Boolean(item.status),
    selected: false,
    collect_status: '',
    total: 0,
    percent: 0,
    vod_name: '',
  }))

  // 恢复真实的采集状态
  await restoreCollectStatus()
}

const restoreCollectStatus = async () => {
  if (list.value.length === 0) return

  for (const row of list.value) {
    try {
      const res = await get('/video/collectProgress', { source_id: row.id })
      const progress = res.data || {}

      if (progress.status === 'running' || progress.status === 'pending') {
        row.collect_status = 'running'
        row.total = progress.total || 0
        row.percent = progress.percent || 0
      } else if (progress.status === 'completed') {
        row.collect_status = 'completed'
        row.total = progress.total || 0
        row.percent = 100
      } else {
        row.collect_status = ''
      }
    } catch (e) {
      console.error('恢复采集状态失败', e)
    }
  }
}

const handleEdit = (row) => {
  isEdit.value = true
  form.value = { 
    id: row.id,
    name: row.name,
    description: row.description || '',
    api_url: row.api_url,
    site_type: row.site_type,
    status: Boolean(row.status),
  }
  showAddDialog.value = true
}

const saveSite = async () => {
  if (!form.value.name.trim()) {
    ElMessage.warning('请输入站点名称')
    return
  }
  if (!form.value.api_url.trim()) {
    ElMessage.warning('请输入接口地址')
    return
  }
  
  try {
    if (isEdit.value) {
      await post('/collectSource/edit', {
        id: form.value.id,
        name: form.value.name,
        description: form.value.description,
        api_url: form.value.api_url,
        site_type: form.value.site_type,
        status: form.value.status ? 1 : 0,
      })
      ElMessage.success('编辑成功')
    } else {
      await post('/collectSource/add', {
        name: form.value.name,
        description: form.value.description,
        api_url: form.value.api_url,
        site_type: form.value.site_type,
        status: form.value.status ? 1 : 0,
      })
      ElMessage.success('添加成功')
    }
    showAddDialog.value = false
    loadList()
  } catch (e) {
    // 错误已在请求拦截器处理
  }
}

const handleDelete = (row) => {
  ElMessageBox.confirm(`确定删除站点"${row.name}"吗？`, '提示').then(async () => {
    await post('/collectSource/delete', { id: row.id })
    ElMessage.success('删除成功')
    loadList()
  }).catch(() => {})
}

const testConnection = async (row) => {
  const url = row.api_url || form.value.api_url
  if (!url) {
    ElMessage.warning('请先填写接口地址')
    return
  }

  try {
    const params = { api_url: url }
    if (row && row.id) {
      params.id = row.id
    }
    const res = await post('/collectSource/test', params)
    const pagecount = res.data?.pagecount || 0
    const listCount = res.data?.list_count || 0
    if (pagecount > 0) {
      ElMessage.success(`连接成功，共 ${pagecount} 页，第1页 ${listCount} 条`)
    } else {
      ElMessage.success(`连接成功，第1页 ${listCount} 条`)
    }

    if (row && row.id) {
      row.page_count = pagecount
    }
  } catch (e) {
    ElMessage.error(e.message || '连接失败')
  }
}

const toggleStatus = async (row) => {
  await post('/collectSource/toggleStatus', { id: row.id })
  ElMessage.success(row.status ? '已禁用' : '已启用')
  loadList()
}

const resetCollectSite = async (row) => {
  await post('/collectSource/resetCollect', { id: row.id })
  row.page_count = 0
  row.last_collected_page = 0
  ElMessage.success('采集状态已重置，下次将从全量开始采集')
}

const startCollect = async (row) => {
  row.collect_status = 'running'
  row.total = 0
  row.percent = 0

  try {
    await get('/video/collectBySourceId', { source_id: row.id })
    ElMessage.success('采集任务已启动')
    await fetchProgress(row)
  } catch (e) {
    row.collect_status = ''
    ElMessage.error(e.message || '采集失败')
  }
}

const startCollectAll = async () => {
  const selectedList = list.value.filter(item => item.selected && item.status)
  if (selectedList.length === 0) {
    ElMessage.warning('请先选择要采集的站点')
    return
  }

  const running = list.value.find(item => item.collect_status === 'running' || item.collect_status === 'pending')
  if (running) {
    ElMessage.warning('已有站点在采集中，请等待完成后再启动')
    return
  }

  ElMessage.success(`串行采集 ${selectedList.length} 个站点`)

  for (const row of selectedList) {
    try {
      await startCollect(row)
    } catch (e) {
      console.error('站点采集失败:', row.name, e)
    }
  }
}

const processNextOne = async (row) => {
  try {
    const res = await get('/video/collectBySourceId', { source_id: row.id })
    const result = res.data || {}

    if (result.status === 'completed') {
      row.collect_status = 'completed'
      row.total = result.total || row.total
      row.percent = 100
      row.vod_name = result.vod_name || ''
      ElMessage.success(result.msg || '采集完成')
      return
    }

    if (result.status === 'failed') {
      row.collect_status = ''
      ElMessage.error(result.msg || '采集失败')
      return
    }

    if (result.status === 'idle') {
      row.collect_status = ''
      return
    }

    row.total = result.total || 0
    row.percent = result.percent || 0
    row.collect_status = 'running'
    row.vod_name = result.vod_name || ''
  } catch (e) {
    console.error('驱动采集处理失败', e)
  }
}

const fetchProgress = async (row) => {
  try {
    const res = await get('/video/collectProgress', { source_id: row.id })
    const progress = res.data || {}

    row.total = progress.total || 0
    row.percent = progress.percent || 0
    row.vod_name = progress.vod_name || ''

    if (progress.status === 'running' || progress.status === 'pending') {
      row.collect_status = 'running'
    } else if (progress.status === 'completed') {
      row.collect_status = 'completed'
      row.percent = 100
    } else {
      row.collect_status = ''
    }
  } catch (e) {
    console.error('获取采集进度失败', e)
  }
}

const resetCollect = () => {
  ElMessageBox.confirm(
    '确定强制重置采集任务吗？这会清除任务队列、worker锁和采集进度。',
    '强制重置',
    { confirmButtonText: '确定重置', cancelButtonText: '取消', type: 'warning' }
  ).then(async () => {
    await post('/video/collectReset', {})

    list.value.forEach(row => {
      row.collect_status = ''
      row.total = 0
      row.percent = 0
      row.vod_name = ''
    })

    ElMessage.success('采集任务已重置')
  }).catch(() => {})
}

onMounted(() => loadList())

onUnmounted(() => {})
</script>

<style lang="scss" scoped>
.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}
</style>
