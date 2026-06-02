<template>
    <div>
        <el-card>
            <template #header>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span>系统配置</span>
                    <el-button type="primary" @click="save">保存配置</el-button>
                </div>
            </template>

            <el-form :model="form" label-width="130px">
                <el-form-item label="网站名称">
                    <el-input v-model="form.site_name" style="width: 300px" />
                </el-form-item>
                <el-form-item label="网站Logo">
                    <el-input v-model="form.site_logo" placeholder="Logo图片URL" style="width: 400px" />
                </el-form-item>
                <el-form-item label="统计代码">
                    <el-input v-model="form.statistics_code" type="textarea" :rows="4" placeholder="如百度统计、Google Analytics等统计代码" style="width: 500px" />
                </el-form-item>
                <el-form-item>
                    <template #label>
                        <el-tooltip content="用户观看广告后获得的VIP奖励时长" placement="top">看广告奖励<el-icon><QuestionFilled /></el-icon></el-tooltip>
                    </template>
                    <el-input-number v-model="form.ad_video_reward" :min="0" /> 分钟
                </el-form-item>
                <el-form-item>
                    <template #label>
                        <el-tooltip content="每个用户每天最多观看广告的次数" placement="top">每日广告上限<el-icon><QuestionFilled /></el-icon></el-tooltip>
                    </template>
                    <el-input-number v-model="form.ad_daily_limit" :min="0" /> 次
                </el-form-item>
                <el-form-item>
                    <template #label>
                        <el-tooltip content="新用户注册时自动赠送的VIP天数" placement="top">注册赠送VIP<el-icon><QuestionFilled /></el-icon></el-tooltip>
                    </template>
                    <el-input-number v-model="form.default_vip_days" :min="0" /> 天
                </el-form-item>
            </el-form>
        </el-card>
    </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import { get, post } from '@/utils/request'
import { ElMessage } from 'element-plus'
import { QuestionFilled } from '@element-plus/icons-vue'

const form = ref({
    site_name: '',
    site_logo: '',
    statistics_code: '',
    ad_video_reward: 30,
    ad_daily_limit: 10,
    default_vip_days: 0,
})

const configMap = {
    site_name: 'site_name',
    site_logo: 'site_logo',
    statistics_code: 'statistics_code',
    ad_video_reward: 'ad_video_reward',
    ad_daily_limit: 'ad_daily_limit',
    default_vip_days: 'default_vip_days',
}

const originalForm = ref('')
const saveTimer = ref(null)
const saveing = ref(false)

const loadConfig = async () => {
    const res = await get('/config/list')
    const configs = res.data

    configs.forEach(item => {
        const key = configMap[item.key]
        if (key) {
            form.value[key] = item.value
        }
    })
    originalForm.value = JSON.stringify(form.value)
}

const save = async (auto = false) => {
    if (saveing.value) return

    if (auto && JSON.stringify(form.value) === originalForm.value) {
        return
    }

    if (auto) {
        saveing.value = true
    }

    const configs = [
        { key: 'site_name', value: form.value.site_name, type: 'string', description: '网站名称' },
        { key: 'site_logo', value: form.value.site_logo, type: 'string', description: '网站Logo' },
        { key: 'statistics_code', value: form.value.statistics_code, type: 'text', description: '统计代码' },
        { key: 'ad_video_reward', value: form.value.ad_video_reward, type: 'int', description: '看广告奖励时长(分钟)' },
        { key: 'ad_daily_limit', value: form.value.ad_daily_limit, type: 'int', description: '每日广告观看次数上限' },
        { key: 'default_vip_days', value: form.value.default_vip_days, type: 'int', description: '新用户注册赠送VIP天数' },
    ]

    try {
        await post('/config/save', { configs })
        originalForm.value = JSON.stringify(form.value)
        if (auto) {
            ElMessage.success('自动保存成功')
        }
    } catch (e) {
        if (auto) {
            ElMessage.error('自动保存失败')
        }
    } finally {
        if (auto) {
            saveing.value = false
        }
    }
}

const debounceSave = () => {
    if (saveTimer.value) {
        clearTimeout(saveTimer.value)
    }
    saveTimer.value = setTimeout(() => {
        save(true)
    }, 1000)
}

watch(() => form.value, () => {
    debounceSave()
}, { deep: true })

onMounted(() => loadConfig())
</script>

<style lang="scss" scoped>
</style>
