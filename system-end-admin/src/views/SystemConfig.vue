<template>
    <div>
        <el-card>
            <template #header>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span>系统配置</span>
                    <el-button type="primary" @click="save">保存配置</el-button>
                </div>
            </template>

            <el-form :model="form" label-width="140px">
                <el-form-item label="网站名称">
                    <el-input v-model="form.site_name" />
                </el-form-item>
                <el-form-item label="网站Logo">
                    <el-input v-model="form.site_logo" placeholder="Logo图片URL" />
                </el-form-item>
                <el-form-item label="看广告奖励时长(分钟)">
                    <el-input-number v-model="form.ad_video_reward" :min="0" />
                </el-form-item>
                <el-form-item label="每日广告观看次数上限">
                    <el-input-number v-model="form.ad_daily_limit" :min="0" />
                </el-form-item>
                <el-form-item label="新用户注册赠送VIP天数">
                    <el-input-number v-model="form.default_vip_days" :min="0" />
                </el-form-item>
            </el-form>
        </el-card>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { get, post } from '@/utils/request'
import { ElMessage } from 'element-plus'

const form = ref({
    site_name: '',
    site_logo: '',
    ad_video_reward: 30,
    ad_daily_limit: 10,
    default_vip_days: 0,
})

const configMap = {
    site_name: 'site_name',
    site_logo: 'site_logo',
    ad_video_reward: 'ad_video_reward',
    ad_daily_limit: 'ad_daily_limit',
    default_vip_days: 'default_vip_days',
}

const loadConfig = async () => {
    const res = await get('/config/list')
    const configs = res.data
    
    configs.forEach(item => {
        const key = configMap[item.key]
        if (key) {
            form.value[key] = item.value
        }
    })
}

const save = async () => {
    const configs = [
        { key: 'site_name', value: form.value.site_name, type: 'string', description: '网站名称' },
        { key: 'site_logo', value: form.value.site_logo, type: 'string', description: '网站Logo' },
        { key: 'ad_video_reward', value: form.value.ad_video_reward, type: 'int', description: '看广告奖励时长(分钟)' },
        { key: 'ad_daily_limit', value: form.value.ad_daily_limit, type: 'int', description: '每日广告观看次数上限' },
        { key: 'default_vip_days', value: form.value.default_vip_days, type: 'int', description: '新用户注册赠送VIP天数' },
    ]
    
    await post('/config/save', { configs })
    ElMessage.success('保存成功')
}

onMounted(() => loadConfig())
</script>

<style lang="scss" scoped>
</style>
