<template>
    <div>
        <el-card>
            <template #header>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span>轮播图管理</span>
                    <div>
                        <el-button type="success" @click="handleAdd">添加轮播图</el-button>
                        <el-button type="info" @click="loadList" style="margin-left: 8px;">刷新</el-button>
                    </div>
                </div>
            </template>

            <el-alert
                title="最多显示5个轮播图，超过部分将自动禁用"
                type="warning"
                :closable="false"
                style="margin-bottom: 15px;"
            />

            <el-table :data="list" stripe border>
                <el-table-column prop="id" label="ID" width="80" resizable />
                <el-table-column label="类型" width="100" resizable>
                    <template #default="{ row }">
                        <el-tag :type="row.type === 1 ? 'success' : 'warning'">
                            {{ row.type === 1 ? '视频' : '广告' }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="内容" min-width="200" resizable>
                    <template #default="{ row }">
                        <div v-if="row.type === 1">
                            <span>{{ row.video_title || '视频未找到' }}</span>
                        </div>
                        <div v-else>
                            <span>{{ row.title || '无标题' }}</span>
                        </div>
                    </template>
                </el-table-column>
                <el-table-column label="预览" width="120" resizable>
                    <template #default="{ row }">
                        <img 
                            v-if="row.cover_url || row.image_url" 
                            :src="row.cover_url || row.image_url" 
                            style="width: 80px; height: 45px; object-fit: cover; border-radius: 4px;"
                        />
                        <span v-else style="color: #999;">暂无图片</span>
                    </template>
                </el-table-column>
                <el-table-column label="排序" width="100" resizable>
                    <template #default="{ row }">
                        <el-input 
                            v-model="row.sort_order" 
                            placeholder="排序"
                            @change="handleSortChange(row)"
                        />
                    </template>
                </el-table-column>
                <el-table-column prop="status" label="状态" width="100" resizable>
                    <template #default="{ row }">
                        <el-tag 
                            :type="row.status ? 'success' : 'danger'" 
                            style="cursor: pointer;"
                            @click="toggleStatus(row)"
                        >{{ row.status ? '启用' : '禁用' }}</el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="操作" width="200" resizable>
                    <template #default="{ row }">
                        <el-button link type="primary" @click="moveUp(row)">上移</el-button>
                        <el-button link type="primary" @click="moveDown(row)">下移</el-button>
                        <el-button link type="warning" @click="edit(row)">编辑</el-button>
                        <el-button link type="danger" @click="del(row.id)">删除</el-button>
                    </template>
                </el-table-column>
            </el-table>

            <el-pagination
                v-model:current-page="query.page"
                v-model:page-size="query.limit"
                :total="total"
                :page-sizes="[10, 20, 50]"
                layout="total, sizes, prev, pager, next"
                @current-change="loadList"
                @size-change="loadList"
                style="margin-top: 20px; justify-content: flex-end;"
            />
        </el-card>

        <!-- 添加/编辑对话框 -->
        <el-dialog v-model="showAddDialog" :title="isEdit ? '编辑轮播图' : '添加轮播图'" width="600px">
            <el-form :model="form" label-width="100px">
                <el-form-item label="类型" required>
                    <el-radio-group v-model="form.type" @change="handleTypeChange">
                        <el-radio :label="1">视频</el-radio>
                        <el-radio :label="2">广告</el-radio>
                    </el-radio-group>
                </el-form-item>

                <!-- 视频类型 -->
                <template v-if="form.type === 1">
                    <el-form-item label="选择视频" required>
                        <el-select v-model="form.video_id" placeholder="请选择视频" style="width: 100%">
                            <el-option 
                                v-for="video in videoOptions" 
                                :key="video.id" 
                                :label="video.title" 
                                :value="video.id" 
                            />
                        </el-select>
                    </el-form-item>
                </template>

                <!-- 广告类型 -->
                <template v-if="form.type === 2">
                    <el-form-item label="广告标题" required>
                        <el-input v-model="form.title" placeholder="请输入广告标题" />
                    </el-form-item>
                    <el-form-item label="广告图片" required>
                        <el-input v-model="form.image_url" placeholder="请输入图片URL" />
                    </el-form-item>
                    <el-form-item label="跳转链接">
                        <el-input v-model="form.link_url" placeholder="点击后跳转的地址" />
                        <span style="color: #999; font-size: 12px;">留空则不跳转</span>
                    </el-form-item>
                </template>

                <el-form-item label="排序">
                    <el-input-number v-model="form.sort_order" :min="0" :max="999" />
                </el-form-item>

                <el-form-item label="状态">
                    <el-switch v-model="form.status" />
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="showAddDialog = false">取消</el-button>
                <el-button type="primary" @click="save">保存</el-button>
            </template>
        </el-dialog>
    </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import { get, post } from '@/utils/request'
import { ElMessageBox, ElMessage } from 'element-plus'

const query = ref({ page: 1, limit: 20 })
const list = ref([])
const total = ref(0)
const showAddDialog = ref(false)
const isEdit = ref(false)
const videoOptions = ref([])

const form = ref({
    id: 0,
    type: 1,
    video_id: 0,
    title: '',
    image_url: '',
    link_url: '',
    sort_order: 100,
    status: 1
})

const loadList = async () => {
    const res = await get('/banner/list', query.value)
    list.value = res.data.list
    total.value = res.data.total
}

const loadVideoOptions = async () => {
    const res = await get('/banner/videoOptions')
    videoOptions.value = res.data
}

const initForm = () => {
    return {
        id: 0,
        type: 1,
        video_id: 0,
        title: '',
        image_url: '',
        link_url: '',
        sort_order: 100,
        status: 1
    }
}

const handleTypeChange = () => {
    if (form.value.type === 1) {
        form.value.video_id = 0
    } else {
        form.value.title = ''
        form.value.image_url = ''
        form.value.link_url = ''
    }
}

const handleAdd = () => {
    isEdit.value = false
    form.value = initForm()
    showAddDialog.value = true
}

const edit = (row) => {
    isEdit.value = true
    form.value = { ...row }
    showAddDialog.value = true
}

const save = async () => {
    if (form.value.type === 1 && !form.value.video_id) {
        ElMessage.error('请选择视频')
        return
    }
    if (form.value.type === 2 && (!form.value.title || !form.value.image_url)) {
        ElMessage.error('广告标题和图片不能为空')
        return
    }
    
    await post('/banner/save', form.value)
    ElMessage.success('保存成功')
    showAddDialog.value = false
    form.value = initForm()
    loadList()
}

const del = (id) => {
    ElMessageBox.confirm('确定删除吗？', '提示').then(async () => {
        await post('/banner/delete', { id })
        ElMessage.success('删除成功')
        loadList()
    }).catch(() => {})
}

const toggleStatus = (row) => {
    const newValue = row.status ? 0 : 1
    const newText = newValue ? '启用' : '禁用'
    ElMessageBox.confirm(`确定将状态设置为"${newText}"吗？`, '提示').then(async () => {
        await post('/banner/updateStatus', { id: row.id, status: newValue })
        row.status = newValue
        ElMessage.success('更新成功')
        loadList()
    }).catch(() => {})
}

const handleSortChange = async (row) => {
    const sortOrder = parseInt(row.sort_order) || 0
    await post('/banner/save', { id: row.id, sort_order: sortOrder })
    ElMessage.success('排序已更新')
    loadList()
}

const moveUp = async (row) => {
    await get(`/banner/up/${row.id}`)
    ElMessage.success('已上移')
    loadList()
}

const moveDown = async (row) => {
    await get(`/banner/down/${row.id}`)
    ElMessage.success('已下移')
    loadList()
}

onMounted(() => {
    loadList()
    loadVideoOptions()
})
</script>