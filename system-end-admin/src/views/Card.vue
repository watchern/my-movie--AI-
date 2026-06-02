<template>
  <div>
    <el-card>
      <template #header>
        <el-button type="primary" @click="showGenerate = true">生成兑换码</el-button>
        <el-button type="warning" @click="handleDisable" :disabled="selectedIds.length === 0">设置失效</el-button>
        <el-button type="danger" @click="handleDelete" :disabled="selectedIds.length === 0">删除</el-button>
      </template>

      <el-form :inline="true" :model="query">
        <el-form-item label="兑换码">
          <el-input v-model="query.code" placeholder="兑换码" clearable />
        </el-form-item>
        <el-form-item label="状态">
          <el-select v-model="query.status" placeholder="全部" clearable style="width: 100px">
            <el-option label="未使用" :value="0" />
            <el-option label="已使用" :value="1" />
            <el-option label="已失效" :value="2" />
          </el-select>
        </el-form-item>
        <el-form-item label="类型">
          <el-select v-model="query.type" placeholder="全部" clearable style="width: 100px">
            <el-option label="天卡" :value="1" />
            <el-option label="周卡" :value="7" />
            <el-option label="月卡" :value="30" />
            <el-option label="季卡" :value="90" />
            <el-option label="年卡" :value="365" />
          </el-select>
        </el-form-item>
        <el-form-item label="使用人">
          <el-input v-model="query.used_user_id" placeholder="使用人ID" clearable />
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="loadList">搜索</el-button>
        </el-form-item>
      </el-form>

      <el-table :data="list" stripe border @selection-change="handleSelectionChange">
        <el-table-column type="selection" width="50" />
        <el-table-column prop="id" label="ID" width="70" resizable />
        <el-table-column prop="code" label="兑换码" min-width="160" show-overflow-tooltip resizable />
        <el-table-column prop="type_name" label="类型" width="100" align="center" resizable>
          <template #default="{ row }">
            <el-tooltip :content="`${row.days}天`" placement="top">
              <span>{{ row.type_name }}</span>
            </el-tooltip>
          </template>
        </el-table-column>
        <el-table-column prop="status" label="状态" width="90" align="center" resizable>
          <template #default="{ row }">
            <el-tag v-if="row.status === 0" type="success" size="small">未使用</el-tag>
            <el-tag v-else-if="row.status === 1" type="info" size="small">已使用</el-tag>
            <el-tag v-else-if="row.status === 2" type="danger" size="small">已失效</el-tag>
            <el-tag v-else type="warning" size="small">未知</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="used_user_id" label="使用人" width="100" align="center" resizable>
          <template #default="{ row }">
            {{ row.used_user_id || '-' }}
          </template>
        </el-table-column>
        <el-table-column prop="used_at" label="使用时间" width="160" resizable />
        <el-table-column prop="created_at" label="创建时间" width="160" resizable />
      </el-table>

      <el-pagination
        v-model:current-page="query.page"
        v-model:page-size="query.limit"
        :total="total"
        :page-sizes="[10, 20, 50, 100]"
        layout="total, sizes, prev, pager, next"
        @current-change="loadList"
        @size-change="loadList"
        style="margin-top: 20px; justify-content: flex-end;"
      />
    </el-card>

    <el-dialog v-model="showGenerate" title="生成兑换码" width="500px">
      <el-form :model="form" label-width="100px">
        <el-form-item label="生成数量">
          <el-input-number v-model="form.count" :min="1" :max="100" />
        </el-form-item>
        <el-form-item label="VIP类型">
          <el-select v-model="form.days">
            <el-option label="天卡(1天)" :value="1" />
            <el-option label="周卡(7天)" :value="7" />
            <el-option label="月卡(30天)" :value="30" />
            <el-option label="季卡(90天)" :value="90" />
            <el-option label="年卡(365天)" :value="365" />
          </el-select>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="showGenerate = false">取消</el-button>
        <el-button type="primary" @click="generate">生成</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { get, post } from '@/utils/request'
import { ElMessage } from 'element-plus'

const query = ref({ page: 1, limit: 20, code: '', status: '', type: '', used_user_id: '' })
const list = ref([])
const total = ref(0)
const showGenerate = ref(false)
const selectedIds = ref([])
const form = ref({ count: 10, days: 30 })

const loadList = async () => {
  const res = await get('/user/cardList', query.value)
  list.value = res.data.list
  total.value = res.data.total
}

const handleSelectionChange = (selection) => {
  selectedIds.value = selection.map(item => item.id)
}

const handleDisable = async () => {
  if (selectedIds.value.length === 0) {
    ElMessage.warning('请选择要失效的兑换码')
    return
  }
  await post('/user/disableCard', { ids: selectedIds.value })
  ElMessage.success('设置成功')
  selectedIds.value = []
  loadList()
}

const handleDelete = async () => {
  if (selectedIds.value.length === 0) {
    ElMessage.warning('请选择要删除的兑换码')
    return
  }
  await post('/user/deleteCard', { ids: selectedIds.value })
  ElMessage.success('删除成功')
  selectedIds.value = []
  loadList()
}

const generate = async () => {
  await post('/user/generateCard', form.value)
  showGenerate.value = false
  loadList()
}

onMounted(() => loadList())
</script>

<style lang="scss" scoped>
</style>
