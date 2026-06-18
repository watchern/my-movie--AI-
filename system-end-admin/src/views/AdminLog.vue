<template>
    <div>
        <el-card>
            <el-form :inline="true" :model="query" class="search-form">
                <el-input v-model="query.keyword" placeholder="搜索管理员" clearable style="width: 200px">
                    <template #prefix><el-icon><User /></el-icon></template>
                </el-input>
                <el-select v-model="query.type" placeholder="操作类型" clearable style="width: 150px">
                    <el-option label="登录" value="login" />
                    <el-option label="登出" value="logout" />
                    <el-option label="添加管理员" value="add_admin" />
                    <el-option label="编辑管理员" value="edit_admin" />
                    <el-option label="删除管理员" value="delete_admin" />
                    <el-option label="禁用管理员" value="disable_admin" />
                    <el-option label="启用管理员" value="enable_admin" />
                    <el-option label="修改密码" value="change_password" />
                    <el-option label="添加视频" value="add_video" />
                    <el-option label="编辑视频" value="edit_video" />
                    <el-option label="删除视频" value="delete_video" />
                    <el-option label="添加分类" value="add_category" />
                    <el-option label="编辑分类" value="edit_category" />
                    <el-option label="删除分类" value="delete_category" />
                    <el-option label="生成兑换码" value="add_vip_card" />
                    <el-option label="删除兑换码" value="delete_vip_card" />
                    <el-option label="禁用兑换码" value="disable_vip_card" />
                    <el-option label="修改配置" value="edit_config" />
                    <el-option label="其他操作" value="other" />
                </el-select>
                <el-button type="primary" @click="loadList">搜索</el-button>
            </el-form>

            <el-table :data="list" stripe border>
                <el-table-column prop="id" label="ID" width="80" resizable />
                <el-table-column prop="username" label="管理员" width="120" resizable>
                    <template #default="{ row }">
                        {{ row.nickname || row.username }}
                    </template>
                </el-table-column>
                <el-table-column prop="type_name" label="操作类型" width="120" resizable />
                <el-table-column prop="detail" label="操作详情" min-width="200" resizable>
                    <template #default="{ row }">
                        <el-tooltip effect="dark" placement="top" :show-after="300">
                            <template #content>
                                <div class="tooltip-content">{{ row.detail }}</div>
                            </template>
                            <div class="cell-content">{{ row.detail }}</div>
                        </el-tooltip>
                    </template>
                </el-table-column>
                <el-table-column prop="ip" label="IP" width="140" resizable />
                <el-table-column prop="device_info" label="设备信息" min-width="200" resizable>
                    <template #default="{ row }">
                        <el-tooltip effect="dark" placement="top" :show-after="300">
                            <template #content>
                                <div class="tooltip-content">{{ row.device_info }}</div>
                            </template>
                            <div class="cell-content">{{ row.device_info }}</div>
                        </el-tooltip>
                    </template>
                </el-table-column>
                <el-table-column prop="created_at" label="操作时间" width="180" resizable />
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
    const res = await get('/admin/logs', query.value)
    list.value = res.data.list
    total.value = res.data.total
}

onMounted(() => loadList())
</script>

<style lang="scss" scoped>
.search-form {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  margin-bottom: 16px;
}
.cell-content {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
</style>
<style lang="scss">
.tooltip-content {
    max-width: 400px;
    word-wrap: break-word;
    word-break: break-all;
}
</style>