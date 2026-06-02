<template>
  <div class="login">
    <div class="card">
      <h2>影视系统管理后台</h2>
      <el-form :model="form" label-width="70px">
        <el-form-item label="用户名">
          <el-input v-model="form.username" placeholder="请输入用户名" />
        </el-form-item>
        <el-form-item label="密码">
          <el-input v-model="form.password" type="password" show-password placeholder="请输入密码" />
        </el-form-item>
        <el-form-item>
          <el-button type="primary" style="width: 100%" :loading="loading" @click="onLogin">登录</el-button>
        </el-form-item>
      </el-form>
      <p class="tip">默认账号：admin / admin123</p>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { post } from '@/utils/request'

const router = useRouter()
const form = ref({ username: '', password: '' })
const loading = ref(false)

const onLogin = async () => {
  if (!form.value.username || !form.value.password) return
  loading.value = true
  try {
    const res = await post('/login', form.value)
    localStorage.setItem('adminToken', res.data.token)
    localStorage.setItem('adminInfo', JSON.stringify({
      id: res.data.id,
      username: res.data.username,
      nickname: res.data.nickname
    }))
    router.replace('/')
  } catch (e) {
  } finally {
    loading.value = false
  }
}
</script>

<style lang="scss" scoped>
.login {
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);

  .card {
    width: 400px;
    padding: 40px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);

    h2 {
      text-align: center;
      margin-bottom: 32px;
      color: #333;
    }

    .tip {
      margin-top: 16px;
      text-align: center;
      color: #999;
      font-size: 13px;
    }
  }
}
</style>
