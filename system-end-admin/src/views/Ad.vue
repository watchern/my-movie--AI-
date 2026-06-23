<template>
  <div>
    <el-card>
      <template #header>
        <div class="header">
          <span>广告管理</span>
          <el-button type="primary" @click="handleAdd">添加广告</el-button>
        </div>
      </template>

      <el-tabs v-model="activeTab" @tab-change="loadList">
        <el-tab-pane label="暂停广告" name="1" />
        <el-tab-pane label="结束广告" name="2" />
      </el-tabs>

      <el-table :data="list" border stripe>
        <el-table-column prop="id" label="ID" width="60" />
        <el-table-column prop="name" label="广告名称" min-width="150" />
        <el-table-column label="图片" width="120">
          <template #default="{ row }">
            <img v-if="row.image_base64" :src="row.image_base64" style="width: 100px; height: 50px; object-fit: cover; border-radius: 4px;" />
            <span v-else style="color: #999;">无图片</span>
          </template>
        </el-table-column>
        <el-table-column prop="link_url" label="跳转链接" min-width="200" show-overflow-tooltip />
        <el-table-column prop="sort_order" label="排序" width="80" />
        <el-table-column label="状态" width="80">
          <template #default="{ row }">
            <el-switch :model-value="Boolean(row.status)" @change="toggleStatus(row, $event)" />
          </template>
        </el-table-column>
        <el-table-column label="操作" width="120" fixed="right">
          <template #default="{ row }">
            <el-button link type="primary" size="small" @click="handleEdit(row)">编辑</el-button>
            <el-button link type="danger" size="small" @click="handleDelete(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>
    </el-card>

    <!-- 添加/编辑对话框 -->
    <el-dialog v-model="showDialog" :title="isEdit ? '编辑广告' : '添加广告'" width="500px">
      <el-form :model="form" label-width="80px">
        <el-form-item label="名称" required>
          <el-input v-model="form.name" placeholder="广告名称" />
        </el-form-item>
        <el-form-item label="类型" required>
          <el-select v-model="form.type" style="width: 100%">
            <el-option :value="1" label="暂停广告" />
            <el-option :value="2" label="结束广告" />
          </el-select>
        </el-form-item>
        <el-form-item label="图片" required>
          <el-radio-group v-model="imageInputType" @change="handleImageTypeChange" size="small">
            <el-radio-button value="upload">上传图片</el-radio-button>
            <el-radio-button value="url">远程URL</el-radio-button>
          </el-radio-group>
          <div v-if="imageInputType === 'upload'" style="margin-top: 12px;">
            <el-upload
              v-if="!form.image_base64"
              :auto-upload="false"
              :limit="1"
              accept="image/*"
              :on-change="handleImageChange"
              list-type="picture-card"
              :file-list="fileList"
            >
              <el-icon><Plus /></el-icon>
            </el-upload>
            <div v-else style="display: flex; align-items: center; gap: 12px;">
              <img :src="form.image_base64" style="width: 100px; height: 50px; object-fit: cover; border-radius: 4px; border: 1px solid #ddd;" />
              <el-button size="small" type="danger" @click="handleRemove">删除并重新上传</el-button>
            </div>
            <div style="color: #999; font-size: 12px; margin-top: 6px;">建议尺寸: 600x300</div>
          </div>
          <div v-else style="margin-top: 12px;">
            <el-input v-model="form.image_url" placeholder="输入图片URL，如 https://example.com/ad.jpg" size="small" />
            <el-button size="small" style="margin-top: 8px;" @click="previewUrlImage">预览并转换</el-button>
            <div v-if="form.image_base64" style="margin-top: 8px; display: flex; align-items: center; gap: 12px;">
              <img :src="form.image_base64" style="width: 100px; height: 50px; object-fit: cover; border-radius: 4px; border: 1px solid #ddd;" />
              <el-button size="small" type="danger" @click="form.image_base64 = ''">删除并重新输入</el-button>
            </div>
          </div>
        </el-form-item>
        <el-form-item label="跳转链接">
          <el-input v-model="form.link_url" placeholder="点击广告跳转URL（可选）" />
        </el-form-item>
        <el-form-item label="排序">
          <el-input-number v-model="form.sort_order" :min="0" :max="999" />
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
import { Plus } from '@element-plus/icons-vue'

const list = ref([])
const showDialog = ref(false)
const isEdit = ref(false)
const activeTab = ref('1')
const fileList = ref([])

const imageInputType = ref('upload')

const form = ref({
  id: 0,
  name: '',
  type: 1,
  image_base64: '',
  image_url: '',
  link_url: '',
  sort_order: 100,
})

function loadList() {
  get('/ad/list', { type: activeTab.value }).then(res => {
    list.value = res.data || []
  })
}

function handleAdd() {
  isEdit.value = false
  imageInputType.value = 'upload'
  form.value = { id: 0, name: '', type: parseInt(activeTab.value), image_base64: '', image_url: '', link_url: '', sort_order: 100 }
  fileList.value = []
  showDialog.value = true
}

function handleEdit(row) {
  isEdit.value = true
  form.value = { ...row }
  fileList.value = row.image_base64 ? [{ name: 'image', url: row.image_base64 }] : []
  imageInputType.value = row.image_base64 ? 'upload' : 'url'
  showDialog.value = true
}

function handleImageTypeChange() {
  form.value.image_base64 = ''
  form.value.image_url = ''
  fileList.value = []
}

function handleImageChange(file) {
  if (!file.raw) return
  const reader = new FileReader()
  reader.onload = (e) => {
    form.value.image_base64 = e.target.result
  }
  reader.readAsDataURL(file.raw)
}

function previewUrlImage() {
  if (!form.value.image_url) return ElMessage.warning('请输入图片URL')
  const img = new Image()
  img.onload = () => {
    const canvas = document.createElement('canvas')
    canvas.width = img.width
    canvas.height = img.height
    const ctx = canvas.getContext('2d')
    ctx.drawImage(img, 0, 0)
    form.value.image_base64 = canvas.toDataURL('image/jpeg', 0.85)
    ElMessage.success('图片已转换')
  }
  img.onerror = () => {
    ElMessage.error('图片加载失败，请检查URL是否正确')
  }
  img.src = form.value.image_url
}

function save() {
  if (!form.value.name) return ElMessage.warning('请输入广告名称')

  if (imageInputType.value === 'upload' && !form.value.image_base64) {
    return ElMessage.warning('请上传广告图片')
  }
  if (imageInputType.value === 'url' && !form.value.image_url) {
    return ElMessage.warning('请输入图片URL')
  }

  const saveData = { ...form.value }
  if (imageInputType.value === 'url') {
    saveData.image_base64 = saveData.image_url
  }

  post('/ad/save', saveData).then(() => {
    ElMessage.success('保存成功')
    showDialog.value = false
    loadList()
  })
}

function toggleStatus(row, value) {
  post('/ad/updateStatus', { id: row.id, value: value ? 1 : 0 }).then(() => {
    ElMessage.success(value ? '已启用' : '已禁用')
    loadList()
  })
}

function handleDelete(row) {
  ElMessageBox.confirm(`确定删除广告"${row.name}"吗？`, '提示').then(() => {
    post('/ad/delete', { ids: [row.id] }).then(() => {
      ElMessage.success('删除成功')
      loadList()
    })
  }).catch(() => {})
}

onMounted(() => loadList())
</script>

<style scoped>
.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}
</style>
