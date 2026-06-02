<template>
    <div>
        <el-card>
            <template #header>
                <el-button type="primary" @click="showAddDialog = true">新增用户</el-button>
            </template>

            <el-form :inline="true" :model="query">
                <el-form-item label="邮箱">
                    <el-input v-model="query.keyword" placeholder="搜索邮箱" clearable />
                </el-form-item>
                <el-form-item label="VIP状态">
                    <el-select v-model="query.vip_status" placeholder="全部" clearable style="width: 100px">
                        <el-option label="普通用户" :value="0" />
                        <el-option label="VIP用户" :value="1" />
                    </el-select>
                </el-form-item>
                <el-form-item label="VIP剩余天数">
                    <el-select v-model="query.vip_days_operator" placeholder="选择" clearable style="width: 80px">
                        <el-option label="大于" :value="'>'" />
                        <el-option label="等于" :value="'='" />
                        <el-option label="小于" :value="'<'" />
                    </el-select>
                    <el-input 
                        v-model.number="query.vip_days_value" 
                        placeholder="天数" 
                        clearable 
                        type="number" 
                        min="0"
                        style="width: 100px; margin-left: 5px;"
                    />
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="loadList">搜索</el-button>
                </el-form-item>
            </el-form>

            <el-table :data="list" stripe border>
                <el-table-column prop="id" label="ID" width="80" resizable />
                <el-table-column prop="email" label="邮箱" resizable />
                <el-table-column prop="phone" label="手机号" width="150" resizable />
                <el-table-column label="VIP状态" width="200" resizable>
                    <template #default="{ row }">
                        <el-tooltip :disabled="!row.vip_status">
                            <template #content>
                                <div v-if="row.vip_status">
                                    <div>到期时间: {{ row.vip_expire_time || '永久' }}</div>
                                    <div>剩余: {{ row.vip_remain_time }}</div>
                                </div>
                            </template>
                            <el-tag :type="row.vip_status ? 'success' : 'info'">
                                {{ row.vip_status ? (row.vip_remain_days > 0 ? 'VIP(剩'+row.vip_remain_time+')' : 'VIP') : '普通' }}
                            </el-tag>
                        </el-tooltip>
                    </template>
                </el-table-column>
                <el-table-column prop="total_watch_time" label="观看时长" width="120" resizable>
                    <template #default="{ row }">{{ row.total_watch_time }}分钟</template>
                </el-table-column>
                <el-table-column label="注册时间" width="180" resizable>
                    <template #default="{ row }">
                        <el-tooltip :disabled="row.created_at == row.updated_at">
                            <template #content>
                                <div>注册时间: {{ row.created_at }}</div>
                                <div>更新时间: {{ row.updated_at }}</div>
                            </template>
                            <span>{{ row.created_at }}</span>
                        </el-tooltip>
                    </template>
                </el-table-column>
                <el-table-column label="操作" width="200" resizable>
                    <template #default="{ row }">
                        <el-button link type="primary" @click="editVip(row)">VIP设置</el-button>
                    </template>
                </el-table-column>
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

        <el-dialog v-model="showAddDialog" title="新增用户" width="400px">
            <el-alert
                title="默认密码: 123456"
                type="info"
                :closable="false"
                style="margin-bottom: 20px;"
            />
            <el-form :model="addForm" label-width="80px">
                <el-form-item label="邮箱">
                    <el-input v-model="addForm.email" placeholder="请输入邮箱" />
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="showAddDialog = false">取消</el-button>
                <el-button type="primary" @click="addUser">确定</el-button>
            </template>
        </el-dialog>

        <el-dialog v-model="showDialog" title="VIP设置" width="500px">
            <el-form :model="form" label-width="100px">
                <el-form-item label="VIP状态">
                    <el-switch v-model="form.vip_status" />
                </el-form-item>
                <el-form-item label="VIP时长">
                    <el-select v-model="form.days" placeholder="选择时长">
                        <el-option label="1天" :value="1" />
                        <el-option label="7天" :value="7" />
                        <el-option label="30天" :value="30" />
                        <el-option label="90天" :value="90" />
                        <el-option label="365天" :value="365" />
                    </el-select>
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="showDialog = false">取消</el-button>
                <el-button type="primary" @click="saveVip">保存</el-button>
            </template>
        </el-dialog>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { get, post } from '@/utils/request'
import { ElMessage } from 'element-plus'

const query = ref({ page: 1, limit: 20, keyword: '', vip_status: '' })
const list = ref([])
const total = ref(0)
const showDialog = ref(false)
const showAddDialog = ref(false)
const form = ref({})
const addForm = ref({ email: '' })

const loadList = async () => {
    const res = await get('/user/list', query.value)
    list.value = res.data.list
    total.value = res.data.total
}

const addUser = async () => {
    if (!addForm.value.email) {
        ElMessage.warning('请输入邮箱')
        return
    }
    const res = await post('/user/addUser', { email: addForm.value.email })
    ElMessage.success(`添加成功！默认密码: ${res.data.default_password}`)
    showAddDialog.value = false
    addForm.value.email = ''
    loadList()
}

const editVip = (row) => {
    form.value = { user_id: row.id, vip_status: row.vip_status, days: 30 }
    showDialog.value = true
}

const saveVip = async () => {
    await post('/user/updateVip', form.value)
    showDialog.value = false
    loadList()
}

onMounted(() => loadList())
</script>

<style lang="scss" scoped>
</style>
