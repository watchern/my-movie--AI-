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
                    <el-button type="success" @click="add">添加</el-button>
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
                        <el-tag 
                            :type="row.is_vip ? 'success' : 'info'" 
                            style="cursor: pointer;"
                            @click="toggleVip(row)"
                        >{{ row.is_vip ? '是' : '否' }}</el-tag>
                    </template>
                </el-table-column>
                <el-table-column prop="play_count" label="播放量" width="100" resizable />
                <el-table-column prop="is_show" label="状态" width="80" resizable>
                    <template #default="{ row }">
                        <el-tag 
                            :type="row.is_show ? 'success' : 'danger'" 
                            style="cursor: pointer;"
                            @click="toggleShow(row)"
                        >{{ row.is_show ? '显示' : '隐藏' }}</el-tag>
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

        <el-dialog v-model="showDialog" :title="isEdit ? '编辑' : '添加'" width="1000px">
            <el-form :model="form" label-width="100px">
                <el-row :gutter="20">
                    <el-col :span="12">
                        <el-form-item label="标题">
                            <el-input v-model="form.title" />
                        </el-form-item>
                    </el-col>
                    <el-col :span="12">
                        <el-form-item label="分类">
                            <el-select v-model="form.category_id" placeholder="请选择分类" clearable style="width: 100%">
                                <el-option 
                                    v-for="cat in categories" 
                                    :key="cat.id" 
                                    :label="cat.name" 
                                    :value="cat.id" 
                                />
                            </el-select>
                        </el-form-item>
                    </el-col>
                </el-row>
                <el-row :gutter="20">
                    <el-col :span="8">
                        <el-form-item label="类型">
                            <el-select v-model="form.type" style="width: 100%">
                                <el-option label="电影" :value="1" />
                                <el-option label="电视剧" :value="2" />
                                <el-option label="动漫" :value="3" />
                                <el-option label="短视频" :value="4" />
                                <el-option label="纪录片" :value="5" />
                            </el-select>
                        </el-form-item>
                    </el-col>
                    <el-col :span="16">
                        <el-form-item label="标签">
                            <el-input v-model="form.tags" placeholder="多个标签用逗号分隔" />
                        </el-form-item>
                    </el-col>
                </el-row>
                <el-row :gutter="20">
                    <el-col :span="12">
                        <el-form-item label="封面图">
                            <el-input v-model="form.cover" placeholder="封面图URL" />
                        </el-form-item>
                    </el-col>
                    <el-col :span="12">
                        <el-form-item label="Banner图">
                            <el-input v-model="form.banner" placeholder="Banner图URL" />
                        </el-form-item>
                    </el-col>
                </el-row>
                <el-row :gutter="20">
                    <el-col :span="12">
                        <el-form-item label="导演">
                            <el-input v-model="form.director" />
                        </el-form-item>
                    </el-col>
                    <el-col :span="6">
                        <el-form-item label="VIP专享">
                            <el-switch v-model="form.is_vip" />
                        </el-form-item>
                    </el-col>
                    <el-col :span="6">
                        <el-form-item label="显示">
                            <el-switch v-model="form.is_show" />
                        </el-form-item>
                    </el-col>
                </el-row>
                <el-row :gutter="20">
                    <el-col :span="24">
                        <el-form-item label="演员">
                            <el-input v-model="form.actors" placeholder="多个演员用逗号分隔" />
                        </el-form-item>
                    </el-col>
                </el-row>
                <el-row :gutter="20">
                    <el-col :span="8">
                        <el-form-item label="时长(分)">
                            <el-input-number v-model="form.duration" :min="0" style="width: 100%" />
                        </el-form-item>
                    </el-col>
                    <el-col :span="8">
                        <el-form-item label="发行年份">
                            <el-input v-model="form.release_year" placeholder="如2024" />
                        </el-form-item>
                    </el-col>
                    <el-col :span="8">
                        <el-form-item label="地区">
                            <el-input v-model="form.region" placeholder="如中国大陆" />
                        </el-form-item>
                    </el-col>
                </el-row>
                <el-row :gutter="20">
                    <el-col :span="8">
                        <el-form-item label="语言">
                            <el-input v-model="form.language" placeholder="如普通话" />
                        </el-form-item>
                    </el-col>
                    <el-col :span="8">
                        <el-form-item label="评分">
                            <el-input-number v-model="form.rating" :min="0" :max="10" :step="0.1" style="width: 100%" />
                        </el-form-item>
                    </el-col>
                    <el-col :span="8">
                        <el-form-item label="播放量">
                            <el-input-number v-model="form.play_count" :min="0" style="width: 100%" />
                        </el-form-item>
                    </el-col>
                </el-row>
                <el-form-item label="简介">
                    <el-input v-model="form.description" type="textarea" :rows="4" />
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
const categories = ref([])

const loadList = async () => {
    const res = await get('/video/list', query.value)
    list.value = res.data.list
    total.value = res.data.total
}

const loadCategories = async () => {
    const res = await get('/video/categories')
    categories.value = res.data
}

const initForm = () => {
    return {
        id: 0,
        title: '',
        category_id: 0,
        type: 1,
        tags: '',
        cover: '',
        banner: '',
        director: '',
        actors: '',
        duration: 0,
        release_year: '',
        region: '',
        language: '',
        rating: 0,
        play_count: 0,
        is_vip: 0,
        is_show: 1,
        description: ''
    }
}

const edit = (row) => {
    isEdit.value = true
    // 转换JSON数组为逗号分隔的字符串
    const tagsStr = Array.isArray(row.tags) ? row.tags.join(',') : (row.tags || '')
    const actorsStr = Array.isArray(row.actors) ? row.actors.join(',') : (row.actors || '')
    
    form.value = { 
        ...row,
        tags: tagsStr,
        actors: actorsStr
    }
    showDialog.value = true
}

const save = async () => {
    // 转换逗号分隔的字符串为数组
    const data = { ...form.value }
    if (data.tags) {
        data.tags = data.tags.split(',').map(s => s.trim()).filter(s => s)
    }
    if (data.actors) {
        data.actors = data.actors.split(',').map(s => s.trim()).filter(s => s)
    }
    
    await post('/video/save', data)
    showDialog.value = false
    loadList()
}

const del = (id) => {
    ElMessageBox.confirm('确定删除吗？', '提示').then(async () => {
        await post('/video/delete', { id })
        loadList()
    }).catch(() => {})
}

const toggleVip = (row) => {
    const newValue = row.is_vip ? 0 : 1
    const newText = newValue ? '是' : '否'
    ElMessageBox.confirm(`确定将VIP状态设置为"${newText}"吗？`, '提示').then(async () => {
        await post('/video/updateStatus', { id: row.id, field: 'is_vip', value: newValue })
        row.is_vip = newValue
    }).catch(() => {})
}

const toggleShow = (row) => {
    const newValue = row.is_show ? 0 : 1
    const newText = newValue ? '显示' : '隐藏'
    ElMessageBox.confirm(`确定将显示状态设置为"${newText}"吗？`, '提示').then(async () => {
        await post('/video/updateStatus', { id: row.id, field: 'is_show', value: newValue })
        row.is_show = newValue
    }).catch(() => {})
}

const add = () => {
    isEdit.value = false
    form.value = initForm()
    showDialog.value = true
}

onMounted(() => {
    loadList()
    loadCategories()
})
</script>

<style lang="scss" scoped>
</style>