<template>
  <van-popup v-model:show="show" round closeable position="bottom" :style="{ height: 'auto' }">
    <div class="quick-login-popup">
      <div class="title">登录 / 注册</div>
      
      <van-tabs v-model:active="activeTab" shrink>
        <van-tab title="登录" name="login"></van-tab>
        <van-tab title="注册" name="register"></van-tab>
      </van-tabs>

      <!-- 登录表单 -->
      <van-form v-if="activeTab === 'login'" @submit="onLogin">
        <van-cell-group inset>
          <van-field
            v-model="loginForm.email"
            name="email"
            label="邮箱"
            placeholder="请输入邮箱"
            :rules="[{ required: true, message: '请输入邮箱' }]"
          />
          <van-field
            v-model="loginForm.password"
            type="password"
            name="password"
            label="密码"
            placeholder="请输入密码"
            :rules="[{ required: true, message: '请输入密码' }]"
          />
        </van-cell-group>
        <div class="btn-wrapper">
          <van-button round block type="primary" native-type="submit" :loading="loading">
            登录
          </van-button>
        </div>
      </van-form>

      <!-- 注册表单 -->
      <van-form v-else @submit="onRegister">
        <van-cell-group inset>
          <van-field
            v-model="registerForm.email"
            name="email"
            label="邮箱"
            placeholder="请输入邮箱"
            :rules="[{ required: true, message: '请输入邮箱' }]"
          />
          <van-field
            v-model="registerForm.password"
            type="password"
            label="密码"
            placeholder="请输入密码"
            :rules="[{ required: true, message: '请输入密码' }]"
          />
          <van-field
            v-model="registerForm.repassword"
            type="password"
            label="确认密码"
            placeholder="请再次输入密码"
            :rules="[{ required: true, message: '请确认密码' }]"
          />
        </van-cell-group>
        <div class="btn-wrapper">
          <van-button round block type="primary" native-type="submit" :loading="loading">
            注册
          </van-button>
        </div>
      </van-form>
    </div>
  </van-popup>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { post } from '@/utils/request'
import { useUserStore } from '@/stores/user'
import { showToast } from 'vant'

const emit = defineEmits(['success'])

const userStore = useUserStore()
const show = ref(false)
const activeTab = ref('login')
const loading = ref(false)

const loginForm = reactive({
  email: '',
  password: ''
})

const registerForm = reactive({
  email: '',
  password: '',
  repassword: ''
})

const open = () => {
  show.value = true
  // 重置表单
  activeTab.value = 'login'
  loginForm.email = ''
  loginForm.password = ''
  registerForm.email = ''
  registerForm.password = ''
  registerForm.repassword = ''
}

const onLogin = async () => {
  loading.value = true
  try {
    await userStore.login(loginForm.email, loginForm.password)
    show.value = false
    emit('success')
    showToast('登录成功')
  } catch (e) {
    showToast(e.message || '登录失败')
  } finally {
    loading.value = false
  }
}

const onRegister = async () => {
  if (registerForm.password !== registerForm.repassword) {
    showToast('两次密码不一致')
    return
  }
  loading.value = true
  try {
    await userStore.register(registerForm.email, registerForm.password)
    show.value = false
    emit('success')
    showToast('注册成功')
  } catch (e) {
    showToast(e.message || '注册失败')
  } finally {
    loading.value = false
  }
}

defineExpose({ open })
</script>

<style scoped lang="scss">
.quick-login-popup {
  padding: 20px 16px 40px;

  .title {
    text-align: center;
    font-size: 18px;
    font-weight: 500;
    margin-bottom: 16px;
  }

  .btn-wrapper {
    margin-top: 24px;
    padding: 0 16px;
  }
}
</style>
