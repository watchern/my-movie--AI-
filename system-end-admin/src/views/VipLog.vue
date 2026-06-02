<template>
    <div>
        <el-card>
            <el-form :inline="true" :model="query">
                <el-form-item label="用户邮箱">
                    <el-input v-model="query.keyword" placeholder="搜索邮箱" clearable style="width: 200px" />
                </el-form-item>
                <el-form-item label="变动类型">
                    <el-select v-model="query.type" placeholder="全部" clearable style="width: 140px">
                        <el-option label="兑换码兑换" value="card" />
                        <el-option label="广告" value="ad" />
                        <el-option label="管理员调整" value="admin" />
                        <el-option label="兑换码生成" value="card_generate" />
                        <el-option label="兑换码失效" value="card_disable" />
                        <el-option label="兑换码删除" value="card_delete" />
                        <el-option label="其他" value="other" />
                    </el-select>
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="loadList">搜索</el-button>
                </el-form-item>
            </el-form>

            <el-table :data="list" stripe border>
                <el-table-column prop="id" label="ID" width="80" resizable />
                <el-table-column prop="email" label="用户邮箱" min-width="150" resizable />
                <el-table-column prop="type_name" label="变动类型" width="100" resizable>
                    <template #default="{ row }">
                        <el-tag :type="getTypeTag(row.type)">{{ row.type_name }}</el-tag>
                    </template>
                </el-table-column>
                <el-table-column prop="days" label="获得天数" width="100" resizable>
                    <template #default="{ row }">
                        <span v-if="row.days > 0">+{{ row.days }}天</span>
                        <span v-else>-</span>
                    </template>
                </el-table-column>
                <el-table-column prop="description" label="说明" min-width="200" resizable show-overflow-tooltip />
                <el-table-column prop="created_at" label="变动时间" width="180" resizable />
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
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { get } from '@/utils/request'

const query = ref({ page: 1, limit: 20, keyword: '', type: '' })
const list = ref([])
const total = ref(0)

const loadList = async () => {
    const res = await get('/config/vipLogs', query.value)
    list.value = res.data.list
    total.value = res.data.total
}

const getTypeTag = (type) => {
    const tagMap = {
        'card': 'success',
        'ad': 'warning',
        'admin': 'primary',
        'card_generate': 'success',
        'card_disable': 'warning',
        'card_delete': 'danger',
        'other': 'info'
    }
    return tagMap[type] || 'info'
}

onMounted(() => loadList())
</script>

<style lang="scss" scoped>
</style>
