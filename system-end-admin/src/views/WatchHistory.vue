<template>
    <div>
        <el-card>
            <el-form :inline="true" :model="query">
                <el-form-item label="关键词">
                    <el-input v-model="query.keyword" placeholder="搜索用户邮箱或视频标题" clearable style="width: 250px" />
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="loadList">搜索</el-button>
                </el-form-item>
            </el-form>

            <el-table :data="list" stripe border>
                <el-table-column prop="id" label="ID" width="80" resizable />
                <el-table-column prop="email" label="用户邮箱" min-width="150" resizable />
                <el-table-column prop="video_title" label="视频标题" min-width="200" resizable show-overflow-tooltip />
                <el-table-column prop="progress" label="观看进度" min-width="150" resizable />
                <el-table-column prop="watched_at" label="观看时间" width="180" resizable />
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
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { get } from '@/utils/request'

const query = ref({ page: 1, limit: 20, keyword: '' })
const list = ref([])
const total = ref(0)

const loadList = async () => {
    const res = await get('/user/watchHistory', query.value)
    list.value = res.data.list
    total.value = res.data.total
}

onMounted(() => loadList())
</script>

<style lang="scss" scoped>
</style>
