import { createRouter, createWebHashHistory } from 'vue-router'

const routes = [
  {
    path: '/login',
    component: () => import('@/views/Login.vue')
  },
  {
    path: '/',
    component: () => import('@/views/Layout.vue'),
    redirect: '/dashboard',
    children: [
      {
        path: 'dashboard',
        component: () => import('@/views/Dashboard.vue')
      },
      {
        path: 'video',
        component: () => import('@/views/Video.vue')
      },
      {
        path: 'user',
        component: () => import('@/views/User.vue')
      },
      {
        path: 'card',
        component: () => import('@/views/Card.vue')
      },
      {
        path: 'collect',
        component: () => import('@/views/Collect.vue')
      },
      {
        path: 'loginLog',
        component: () => import('@/views/LoginLog.vue')
      },
      {
        path: 'vipLog',
        component: () => import('@/views/VipLog.vue')
      },
      {
        path: 'systemConfig',
        component: () => import('@/views/SystemConfig.vue')
      },
      {
        path: 'watchHistory',
        component: () => import('@/views/WatchHistory.vue')
      },
      {
        path: 'favorite',
        component: () => import('@/views/Favorite.vue')
      },
      {
        path: 'adminManager',
        component: () => import('@/views/AdminManager.vue')
      }
    ]
  }
]

const router = createRouter({
  history: createWebHashHistory(),
  routes
})

router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('adminToken')
  if (to.path !== '/login' && !token) {
    next('/login')
  } else {
    next()
  }
})

export default router
