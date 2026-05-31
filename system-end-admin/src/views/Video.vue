<template>
    <div>
        <el-card>
            <el-form :inline="true" :model="query">
                <el-form-item label="类型">
                    <el-select v-model="query.type" placeholder="全部" clearable style="width: 120px">
                        <el-option label="电影" :value="1" />
                        <el-option label="电视剧" :value="2" />
                        <el-option label="动漫" :value="3" />
                        <el-option label="短视频" :value="4" />
                        <el-option label="纪录片" :value="5" />
                    </el-select>
                </el-form-item>
                <el-form-item label="关键词">
                    <el-input v-model="query.keyword" placeholder="标题" clearable style="width: 200px" />
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="loadList">搜索</el-button>
                </el-form-item>
            </el-form>

            <el-table :data="list" stripe border>
                <el-table-column prop="id" label="ID" width="80" resizable />
                <el-table-column prop="title" label="标题" resizable />
                <el-table-column prop="type" label="类型" width="100" resizable>
                    <template #default="{ row }">
                        {{ ['', '电影', '电视剧', '动漫', '短视频', '纪录片'][row.type] }}
                    </template>
                </el-table-column>
                <el-table-column prop="is_vip" label="VIP" width="80" resizable>
                    <template #default="{ row }">
                        <el-tag :type="row.is_vip ? 'success' : 'info'">{{ row.is_vip ? '是' : '否' }}</el-tag>
                    </template>
                </el-table-column>
                <el-table-column prop="play_count" label="播放量" width="100" resizable />
                <el-table-column prop="is_show" label="状态" width="80" resizable>
                    <template #default="{ row }">
                        <el-tag :type="row.is_show ? 'success' : 'danger'">{{ row.is_show ? '显示' : '隐藏' }}</el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="操作" width="200" resizable>
                    <template #default="{ row }">
                        <el-button link type="primary" @click="edit(row)">编辑</el-button>
                        <el-button link type="danger" @click="del(row.id)">删除</el-button>
                    </template>
                </el-table-column>
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

        <el-dialog v-model="showDialog" :title="isEdit ? '编辑' : '添加'" width="600px">
            <el-form :model="form" label-width="100px">
                <el-form-item label="标题">
                    <el-input v-model="form.title" />
                </el-form-item>
                <el-form-item label="类型">
                    <el-select v-model="form.type">
                        <el-option label="电影" :value="1" />
                        <el-option label="电视剧" :value="2" />
                        <el-option label="动漫" :value="3" />
                        <el-option label="短视频" :value="4" />
                        <el-option label="纪录片" :value="5" />
                    </el-select>
                </el-form-item>
                <el-form-item label="VIP">
                    <el-switch v-model="form.is_vip" />
                </el-form-item>
                <el-form-item label="显示">
                    <el-switch v-model="form.is_show" />
                </el-form-item>
                <el-form-item label="简介">
                    <el-input v-model="form.desc" type="textarea" :rows="3" />
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="showDialog = false">取消</el-button>
                <el-button type="primary" @click="save">保存</el-button>
            </template>
        </el-dialog>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { get, post } from '@/utils/request'
import { ElMessageBox } from 'element-plus'

const query = ref({ page: 1, limit: 20, type: '', keyword: '' })
const list = ref([])
const total = ref(0)
const showDialog = ref(false)
const isEdit = ref(false)
const form = ref({})

const loadList = async () => {
    const res = await get('/video/list', query.value)
    list.value = res.data.list
    total.value = res.data.total
}

const edit = (row) => {
    isEdit.value = true
    form.value = { ...row }
    showDialog.value = true
}

const save = async () => {
    await post('/video/save', form.value)
    showDialog.value = false
    loadList()
}

const del = (id) => {
    ElMessageBox.confirm('确定删除吗？', '提示').then(async () => {
        await post('/video/delete', { id })
        loadList()
    }).catch(() => {})
}

onMounted(() => loadList())
</script>

<style lang="scss" scoped>
</style>