<template>
    <div ref="containerRef">
        <el-card>
            <template #header>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span>管理员列表</span>
                    <el-button type="primary" @click="showAddDialog">添加管理员</el-button>
                </div>
            </template>

            <el-form :inline="true" :model="query" class="search-form">
                <el-input v-model="query.keyword" placeholder="搜索用户名" clearable style="width: 200px">
                    <template #prefix><el-icon><User /></el-icon></template>
                </el-input>
                <el-button type="primary" @click="loadList">搜索</el-button>
            </el-form>

            <!-- 桌面端表格 -->
            <el-table v-if="!isMobile" :data="list" stripe border>
                <el-table-column prop="id" label="ID" width="80" resizable />
                <el-table-column prop="username" label="用户名" min-width="120" resizable />
                <el-table-column prop="nickname" label="昵称" min-width="120" resizable />
                <el-table-column label="状态" width="140" resizable>
                    <template #default="{ row }">
                        <el-tag :type="row.status === 1 ? 'success' : 'danger'">{{ row.status_name }}</el-tag>
                        <el-button 
                            link 
                            :type="row.status === 1 ? 'danger' : 'success'" 
                            @click="toggleStatus(row)" 
                            style="margin-left: 8px"
                            :disabled="canToggleStatus(row) === false"
                        >
                            {{ row.status === 1 ? '禁用' : '启用' }}
                        </el-button>
                    </template>
                </el-table-column>
                <el-table-column prop="last_login_time" label="最后登录" width="170" resizable />
                <el-table-column prop="last_login_ip" label="登录IP" width="140" resizable />
                <el-table-column prop="created_at" label="创建时间" width="170" resizable />
                <el-table-column label="操作" width="160" resizable>
                    <template #default="{ row }">
                        <el-button link type="primary" @click="editAdmin(row)">编辑</el-button>
                        <el-button link type="danger" @click="deleteAdmin(row)">删除</el-button>
                    </template>
                </el-table-column>
            </el-table>

            <!-- 移动端卡片列表 -->
            <div v-else class="mobile-list">
                <el-card v-for="row in list" :key="row.id" class="mobile-card">
                    <div class="mobile-item"><span class="label">ID</span><span class="value">{{ row.id }}</span></div>
                    <div class="mobile-item"><span class="label">用户名</span><span class="value">{{ row.username }}</span></div>
                    <div class="mobile-item"><span class="label">昵称</span><span class="value">{{ row.nickname }}</span></div>
                    <div class="mobile-item">
                        <span class="label">状态</span>
                        <el-tag :type="row.status === 1 ? 'success' : 'danger'" size="small">{{ row.status_name }}</el-tag>
                    </div>
                    <div class="mobile-item"><span class="label">最后登录</span><span class="value">{{ row.last_login_time || '-' }}</span></div>
                    <div class="mobile-item"><span class="label">登录IP</span><span class="value">{{ row.last_login_ip || '-' }}</span></div>
                    <div class="mobile-item"><span class="label">创建时间</span><span class="value">{{ row.created_at }}</span></div>
                    <div class="mobile-item actions">
                        <el-button size="small" type="primary" @click="editAdmin(row)">编辑</el-button>
                        <el-button size="small" type="danger" @click="deleteAdmin(row)">删除</el-button>
                    </div>
                </el-card>
            </div>

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
import { ref, onMounted, onUnmounted } from 'vue'
import { get, post } from '@/utils/request'
import { ElMessage, ElMessageBox } from 'element-plus'

const query = ref({ page: 1, limit: 20, keyword: '' })
const list = ref([])
const total = ref(0)
const containerRef = ref(null)
const isMobile = ref(false)

const checkMobile = () => {
    if (containerRef.value) {
        isMobile.value = containerRef.value.offsetWidth < 768
    }
}

let resizeObserver = null

onMounted(() => {
    loadList()
    checkMobile()
    if (containerRef.value) {
        resizeObserver = new ResizeObserver(() => {
            checkMobile()
        })
        resizeObserver.observe(containerRef.value)
    }
})

onUnmounted(() => {
    if (resizeObserver) {
        resizeObserver.disconnect()
    }
})

const showDialog = ref(false)
const isEdit = ref(false)
const form = ref({
    id: 0,
    username: '',
    nickname: '',
    password: '',
    status: 1
})

const getCurrentAdminInfo = () => {
    const adminInfo = localStorage.getItem('adminInfo')
    if (adminInfo) {
        return JSON.parse(adminInfo)
    }
    return null
}

const canToggleStatus = (row) => {
    const currentAdmin = getCurrentAdminInfo()
    if (row.id == 1) {
        return false
    }
    if (currentAdmin && row.id == currentAdmin.id) {
        return false
    }
    return true
}

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

const toggleStatus = (row) => {
    if (!canToggleStatus(row)) {
        return
    }
    const action = row.status === 1 ? '禁用' : '启用'
    ElMessageBox.confirm(`确定要${action}该管理员吗？`, '提示', {
        confirmButtonText: '确定',
        cancelButtonText: '取消',
        type: 'warning'
    }).then(async () => {
        const newStatus = row.status === 1 ? 0 : 1
        await post('/admin/update', { id: row.id, status: newStatus })
        ElMessage.success(`${action}成功`)
        loadList()
    }).catch(() => {})
}
</script>

<style lang="scss" scoped>
.search-form {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  margin-bottom: 16px;
}
.mobile-list {
    .mobile-card {
        margin-bottom: 12px;
    }
    .mobile-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 0;
        border-bottom: 1px solid #f0f0f0;
        &:last-child {
            border-bottom: none;
        }
        .label {
            color: #999;
            font-size: 13px;
        }
        .value {
            color: #333;
            font-size: 13px;
        }
        &.actions {
            margin-top: 8px;
            padding-top: 8px;
            border-top: 1px solid #eee;
            border-bottom: none;
            justify-content: flex-end;
            gap: 8px;
        }
    }
}
</style>
