<template>
    <div>
        <el-card>
            <template #header>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span>管理员列表</span>
                    <el-button type="primary" @click="showAddDialog">添加管理员</el-button>
                </div>
            </template>

            <el-form :inline="true" :model="query">
                <el-form-item label="用户名">
                    <el-input v-model="query.keyword" placeholder="搜索用户名" clearable style="width: 200px" />
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="loadList">搜索</el-button>
                </el-form-item>
            </el-form>

            <el-table :data="list" stripe border>
                <el-table-column prop="id" label="ID" width="80" resizable />
                <el-table-column prop="username" label="用户名" min-width="120" resizable />
                <el-table-column prop="nickname" label="昵称" min-width="120" resizable />
                <el-table-column label="状态" width="100" resizable>
                    <template #default="{ row }">
                        <el-tag :type="row.status === 1 ? 'success' : 'danger'">{{ row.status_name }}</el-tag>
                    </template>
                </el-table-column>
                <el-table-column prop="last_login_time" label="最后登录" width="170" resizable />
                <el-table-column prop="last_login_ip" label="登录IP" width="140" resizable />
                <el-table-column prop="created_at" label="创建时间" width="170" resizable />
                <el-table-column label="操作" width="150" resizable>
                    <template #default="{ row }">
                        <el-button link type="primary" @click="editAdmin(row)">编辑</el-button>
                        <el-button link type="danger" @click="deleteAdmin(row)">删除</el-button>
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

        <!-- 添加/编辑对话框 -->
        <el-dialog v-model="showDialog" :title="isEdit ? '编辑管理员' : '添加管理员'" width="500px">
            <el-form :model="form" label-width="80px">
                <el-form-item label="用户名" v-if="!isEdit">
                    <el-input v-model="form.username" placeholder="请输入用户名" />
                </el-form-item>
                <el-form-item label="昵称">
                    <el-input v-model="form.nickname" placeholder="请输入昵称" />
                </el-form-item>
                <el-form-item label="密码" :required="!isEdit">
                    <el-input v-model="form.password" type="password" :placeholder="isEdit ? '不修改请留空' : '请输入密码'" show-password />
                    <span v-if="isEdit" style="color: #999; font-size: 12px; margin-left: 8px;">不修改请留空</span>
                </el-form-item>
                <el-form-item label="状态">
                    <el-radio-group v-model="form.status">
                        <el-radio :label="1">启用</el-radio>
                        <el-radio :label="0">禁用</el-radio>
                    </el-radio-group>
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
import { ElMessage, ElMessageBox } from 'element-plus'

const query = ref({ page: 1, limit: 20, keyword: '' })
const list = ref([])
const total = ref(0)

const showDialog = ref(false)
const isEdit = ref(false)
const form = ref({
    id: 0,
    username: '',
    nickname: '',
    password: '',
    status: 1
})

const loadList = async () => {
    const res = await get('/admin/list', query.value)
    list.value = res.data.list
    total.value = res.data.total
}

const showAddDialog = () => {
    isEdit.value = false
    form.value = {
        id: 0,
        username: '',
        nickname: '',
        password: '',
        status: 1
    }
    showDialog.value = true
}

const editAdmin = (row) => {
    isEdit.value = true
    form.value = {
        id: row.id,
        username: row.username,
        nickname: row.nickname,
        password: '',
        status: row.status
    }
    showDialog.value = true
}

const save = async () => {
    if (!isEdit.value && !form.value.username) {
        ElMessage.error('用户名不能为空')
        return
    }
    if (!isEdit.value && form.value.password.length < 6) {
        ElMessage.error('密码长度不能少于6位')
        return
    }
    if (isEdit.value && form.value.password && form.value.password.length < 6) {
        ElMessage.error('密码长度不能少于6位')
        return
    }

    const url = isEdit.value ? '/admin/update' : '/admin/add'
    await post(url, form.value)
    ElMessage.success('保存成功')
    showDialog.value = false
    loadList()
}

const deleteAdmin = (row) => {
    ElMessageBox.confirm('确定要删除该管理员吗？', '提示', {
        confirmButtonText: '确定',
        cancelButtonText: '取消',
        type: 'warning'
    }).then(async () => {
        await post('/admin/delete', { ids: [row.id] })
        ElMessage.success('删除成功')
        loadList()
    }).catch(() => {})
}

onMounted(() => loadList())
</script>

<style lang="scss" scoped>
</style>
