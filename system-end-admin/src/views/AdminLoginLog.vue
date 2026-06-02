<template>
    <div>
        <el-card>
            <el-form :inline="true" :model="query">
                <el-form-item label="管理员">
                    <el-input v-model="query.keyword" placeholder="搜索管理员" clearable style="width: 200px" />
                </el-form-item>
                <el-form-item label="设备">
                    <el-select v-model="query.device" placeholder="全部" clearable style="width: 120px">
                        <el-option label="手机" value="mobile" />
                        <el-option label="平板" value="tablet" />
                        <el-option label="电脑" value="desktop" />
                        <el-option label="其他" value="other" />
                    </el-select>
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="loadList">搜索</el-button>
                </el-form-item>
            </el-form>

            <el-table :data="list" stripe border>
                <el-table-column prop="id" label="ID" width="80" resizable />
                <el-table-column prop="username" label="管理员" width="120" resizable>
                    <template #default="{ row }">
                        {{ row.nickname || row.username }}
                    </template>
                </el-table-column>
                <el-table-column prop="login_ip" label="登录IP" width="140" resizable />
                <el-table-column prop="device_name" label="设备" width="100" resizable />
                <el-table-column prop="device_info" label="设备信息" min-width="200" resizable show-overflow-tooltip />
                <el-table-column prop="login_at" label="登录时间" width="180" resizable />
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

const query = ref({ page: 1, limit: 20, keyword: '', device: '' })
const list = ref([])
const total = ref(0)

const loadList = async () => {
    const res = await get('/admin/loginLogs', query.value)
    list.value = res.data.list
    total.value = res.data.total
}

onMounted(() => loadList())
</script>

<style lang="scss" scoped>
</style>
