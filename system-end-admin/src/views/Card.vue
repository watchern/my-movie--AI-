<template>
  <div>
    <el-card>
      <template #header>
        <el-button type="primary" @click="showGenerate = true">生成卡密</el-button>
      </template>
      <el-table :data="list" stripe>
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="card_key" label="卡密" />
        <el-table-column prop="vip_days" label="VIP天数" width="100" />
        <el-table-column prop="status" label="状态" width="80">
          <template #default="{ row }">
            <el-tag :type="row.status === 0 ? 'success' : 'info'">{{ row.status === 0 ? '未使用' : '已使用' }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="创建时间" width="180" />
      </el-table>

      <el-pagination
        v-model:current-page="query.page"
        v-model:page-size="query.limit"
        :total="total"
        layout="total, prev, pager, next"
        @current-change="loadList"
        style="margin-top: 20px; justify-content: flex-end;"
      />
    </el-card>

    <el-dialog v-model="showGenerate" title="生成卡密" width="500px">
      <el-form :model="form" label-width="100px">
        <el-form-item label="生成数量">
          <el-input-number v-model="form.count" :min="1" :max="100" />
        </el-form-item>
        <el-form-item label="VIP天数">
          <el-select v-model="form.days">
            <el-option label="1天" :value="1" />
            <el-option label="7天" :value="7" />
            <el-option label="30天" :value="30" />
            <el-option label="90天" :value="90" />
            <el-option label="365天" :value="365" />
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

const query = ref({ page: 1, limit: 20 })
const list = ref([])
const total = ref(0)
const showGenerate = ref(false)
const form = ref({ count: 10, days: 30 })

const loadList = async () => {
  const res = await get('/user/cardList', query.value)
  list.value = res.data.list
  total.value = res.data.total
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
