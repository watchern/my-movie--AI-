<template>
    <div>
        <el-row :gutter="20">
            <el-col :span="6">
                <el-card>
                    <div class="stat">
                        <div class="num">{{ stats.user?.vip || 0 }}</div>
                        <div class="label">VIP用户</div>
                    </div>
                </el-card>
            </el-col>
            <el-col :span="6">
                <el-card>
                    <div class="stat">
                        <div class="num">{{ stats.user?.total || 0 }}</div>
                        <div class="label">总用户</div>
                    </div>
                </el-card>
            </el-col>
            <el-col :span="6">
                <el-card>
                    <div class="stat">
                        <div class="num">{{ stats.video?.total || 0 }}</div>
                        <div class="label">视频数</div>
                    </div>
                </el-card>
            </el-col>
            <el-col :span="6">
                <el-card>
                    <div class="stat">
                        <div class="num">{{ stats.card?.unused || 0 }}</div>
                        <div class="label">剩余兑换码</div>
                    </div>
                </el-card>
            </el-col>
        </el-row>

        <el-card style="margin-top: 20px">
            <template #header>播放TOP10</template>
            <el-table :data="stats.top_videos" stripe border>
                <el-table-column prop="title" label="标题" resizable />
                <el-table-column prop="play_count" label="播放量" sortable resizable />
                <el-table-column prop="type_name" label="类型" resizable />
            </el-table>
        </el-card>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { get } from '@/utils/request'

const stats = ref({})

const loadStats = async () => {
    const res = await get('/dashboard/stats')
    stats.value = res.data
}

onMounted(() => loadStats())
</script>

<style lang="scss" scoped>
.stat {
    text-align: center;

    .num {
        font-size: 36px;
        font-weight: 600;
        color: #409eff;
    }

    .label {
        margin-top: 8px;
        color: #999;
        font-size: 14px;
    }
}
</style>
