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
        <el-table-column prop="status" label="状态" width="80" resizable fixed="right">
          <template #default="{ row }">
            <el-tag :type="row.status ? 'success' : 'info'" size="small">
              {{ row.status ? '启用' : '禁用' }}
            </el-tag>
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
            <el-tooltip :content="row.status ? '禁用' : '启用'" placement="top">
              <el-button link type="warning" @click="toggleStatus(row)"><el-icon><Switch /></el-icon></el-button>
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
          <el-button type="primary" @click="startCollectAll">采集全部</el-button>
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
        <el-table-column prop="status" label="状态" width="80" resizable>
          <template #default="{ row }">
            <el-tag :type="row.collect_status === 'running' ? 'warning' : 'success'" size="small">
              {{ row.collect_status === 'running' ? '采集中' : '就绪' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="total" label="总数" width="80" resizable />
        <el-table-column prop="progress" label="进度" width="150" resizable>
          <template #default="{ row }">
            <el-progress :percentage="row.percent || 0" :stroke-width="8" />
          </template>
        </el-table-column>
        <el-table-column label="操作" width="100" resizable>
          <template #default="{ row }">
            <el-button 
              type="primary" 
              size="small" 
              :loading="row.collect_status === 'running'"
              @click="startCollect(row)"
            >
              {{ row.collect_status === 'running' ? '采集中' : '开始采集' }}
            </el-button>
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
import { Edit, Delete, Connection, Switch } from '@element-plus/icons-vue'

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
  }))
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
    await post('/collectSource/test', { api_url: url })
    ElMessage.success('连接成功')
  } catch (e) {
    ElMessage.error(e.message || '连接失败')
  }
}

const toggleStatus = async (row) => {
  await post('/collectSource/toggleStatus', { id: row.id })
  ElMessage.success(row.status ? '已禁用' : '已启用')
  loadList()
}

// 轮询定时器
const progressTimers = ref({})

const startCollect = async (row) => {
  row.collect_status = 'running'
  row.total = 0
  row.percent = 0

  try {
    await post('/video/collect', {
      source_id: row.id,
      api_url: row.api_url
    })
    ElMessage.success('采集任务已启动')
    // 启动真实进度轮询
    startProgressPolling(row)
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

  ElMessage.success(`已启动 ${selectedList.length} 个站点的采集任务`)

  for (const row of selectedList) {
    startCollect(row)
  }
}

// 启动进度轮询
const startProgressPolling = (row) => {
  // 清除已有定时器
  if (progressTimers.value[row.id]) {
    clearInterval(progressTimers.value[row.id])
  }

  // 立即获取一次
  fetchProgress(row)

  // 每 2 秒轮询一次
  progressTimers.value[row.id] = setInterval(() => {
    fetchProgress(row)
  }, 2000)
}

// 获取采集进度
const fetchProgress = async (row) => {
  try {
    const res = await get('/video/collectProgress', { source_id: row.id })
    const progress = res.data || {}

    row.total = progress.total || 0
    row.percent = progress.percent || 0

    // 任务结束或失败时停止轮询
    if (progress.status === 'completed' || progress.status === 'failed' || progress.status === 'idle') {
      if (progress.status === 'completed') {
        ElMessage.success(progress.msg || '采集完成')
      } else if (progress.status === 'failed') {
        ElMessage.error(progress.msg || '采集失败')
      }
      stopProgressPolling(row)
    }
  } catch (e) {
    console.error('获取采集进度失败', e)
  }
}

// 停止进度轮询
const stopProgressPolling = (row) => {
  if (progressTimers.value[row.id]) {
    clearInterval(progressTimers.value[row.id])
    delete progressTimers.value[row.id]
  }
  row.collect_status = ''
}

onMounted(() => loadList())

onUnmounted(() => {
  // 页面卸载时清除所有定时器
  Object.keys(progressTimers.value).forEach(id => {
    clearInterval(progressTimers.value[id])
  })
  progressTimers.value = {}
})
</script>

<style lang="scss" scoped>
.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}
</style>
