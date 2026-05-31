<template>
  <div>
    <el-card>
      <template #header>
        资源采集配置
      </template>
      <el-form :model="form" label-width="120px">
        <el-form-item label="苹果CMS接口">
          <el-input v-model="form.api_url" style="width: 500px" />
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="test">测试连接</el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <el-card style="margin-top: 20px">
      <template #header>
        <div class="header">
          采集任务
          <el-button type="primary" @click="startCollect">开始采集</el-button>
        </div>
      </template>
      <el-table :data="list" border>
        <el-table-column prop="title" label="资源站名称" resizable />
        <el-table-column prop="status" label="状态" resizable>
          <template #default="{ row }">
            <el-tag :type="row.status === 'running' ? 'warning' : 'success'">
              {{ row.status === 'running' ? '采集中' : '完成' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="total" label="总数" width="80" resizable />
        <el-table-column prop="progress" label="进度" width="120" resizable>
          <template #default="{ row }">
            <el-progress :percentage="row.percent || 0" :stroke-width="8" />
          </template>
        </el-table-column>
      </el-table>
    </el-card>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { get, post } from '@/utils/request'
import { ElMessage } from 'element-plus'

const form = ref({ api_url: '' })
const list = ref([])

const test = async () => {
  try {
    await post('/collect/test', { api_url: form.value.api_url })
    ElMessage.success('连接成功')
  } catch (e) {}
}

const startCollect = async () => {
  ElMessage.success('已开始采集，请稍后刷新查看')
  await post('/collect/start', { api_url: form.value.api_url })
  loadList()
}

const loadList = async () => {
  list.value = []
}

onMounted(() => loadList())
</script>

<style lang="scss" scoped>
.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}
</style>
