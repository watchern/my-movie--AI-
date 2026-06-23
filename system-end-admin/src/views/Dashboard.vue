<template>
    <div>
        <el-row :gutter="20">
            <el-col :span="6">
                <el-card>
                    <div class="stat">
                        <div class="num">{{ stats.user?.vip || 0 }}<span class="sub"> / {{ stats.user?.total || 0 }}</span></div>
                        <div class="label">VIP用户数</div>
                    </div>
                </el-card>
            </el-col>
            <el-col :span="6">
                <el-card>
                    <div class="stat">
                        <div class="num">{{ stats.card?.unused || 0 }}<span class="sub"> / {{ stats.card?.total || 0 }}</span></div>
                        <div class="label">有效兑换码</div>
                    </div>
                </el-card>
            </el-col>
            <el-col :span="6">
                <el-card>
                    <div class="stat">
                        <div class="num">{{ stats.video?.visible || 0 }}<span class="sub"> / {{ stats.video?.total || 0 }}</span></div>
                        <div class="label">显示视频数</div>
                    </div>
                </el-card>
            </el-col>
            <el-col :span="6">
                <el-card>
                    <div class="stat">
                        <div class="num">{{ stats.today?.watch_history || 0 }}</div>
                        <div class="label">今日播放</div>
                    </div>
                </el-card>
            </el-col>
        </el-row>

        <el-card style="margin-top: 20px">
            <template #header>
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <span>播放TOP10</span>
                    <el-select v-model="type" placeholder="全部类型" clearable size="small" style="width: 120px" @change="loadStats">
                        <el-option label="电影" :value="1" />
                        <el-option label="电视剧" :value="2" />
                        <el-option label="动漫" :value="3" />
                        <el-option label="短视频" :value="4" />
                        <el-option label="纪录片" :value="5" />
                    </el-select>
                </div>
            </template>
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
const type = ref('')

const loadStats = async () => {
    const res = await get('/dashboard/stats', type.value ? { type: type.value } : {})
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

    .sub {
        font-size: 16px;
        color: #999;
        font-weight: 400;
    }

    .label {
        margin-top: 8px;
        color: #999;
        font-size: 14px;
    }
}
</style>
