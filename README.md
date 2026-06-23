# 影视系统

一个支持手机、平板、Web 三合一的影视系统，支持多资源站采集。

## 项目结构

```
moive-app/
├── backend/                 # 后端 API 服务 (ThinkPHP 8.x)
├── multi-end-users/         # 用户端 (Vue 3 + Vant)
├── system-end-admin/        # 管理端 (Vue 3 + Element Plus)
├── database/                 # 数据库 (SQLite)
├── .kilo/                    # Kilo AI 配置
└── .ai/                      # AI 工具配置
```

## 目录说明

| 目录 | 说明 | 技术栈 |
|------|------|--------|
| `backend/` | 后端 API 服务 | PHP 8.1+, ThinkPHP 8.x, SQLite |
| `multi-end-users/` | 用户端前端 | Vue 3, Vant 4, Pinia, Video.js |
| `system-end-admin/` | 管理端前端 | Vue 3, Element Plus, Axios |
| `database/` | 数据库文件 | SQLite 3.26+ |

## 端口说明

| 服务 | 端口 |
|------|------|
| 后端 API | 8080 |
| 用户端 | 3000 |
| 管理端 | 3001 |

---

## 一、后端服务 (backend)

### 技术栈

| 技术 | 版本 | 说明 |
|------|------|------|
| PHP | >=8.1 | 服务器语言 |
| ThinkPHP | ^8.0 | PHP 框架 |
| ThinkORM | ^3.0 | 数据库 ORM |
| ThinkMultiApp | ^1.0 | 多应用支持 |
| Firebase PHP-JWT | ^6.0 | JWT 认证 |

### 目录结构

```
backend/
├── app/
│   ├── controller/          # 控制器
│   │   ├── admin/           # 管理端控制器
│   │   │   ├── AdminController.php       # 管理员管理
│   │   │   ├── BannerController.php      # 轮播图管理
│   │   │   ├── CollectSourceController.php  # 资源采集站点
│   │   │   ├── ConfigController.php      # 系统配置
│   │   │   ├── DashboardController.php   # 数据看板
│   │   │   ├── LoginController.php      # 管理员登录
│   │   │   ├── UserController.php       # 用户管理
│   │   │   └── VideoController.php      # 视频管理
│   │   └── Api/             # 用户端控制器
│   ├── model/               # 数据模型 (Admin, User, Video, Category等)
│   ├── service/             # 服务层
│   │   ├── AppleCmsService.php      # 苹果CMS采集服务
│   │   └── CollectionTaskService.php # 采集任务服务
│   └── route/
│       └── admin.php        # 管理端路由
├── config/                  # 配置文件
├── public/
│   └── index.php           # 入口文件
└── composer.json           # PHP 依赖
```

### 功能说明

#### 管理端 API
| 接口 | 说明 |
|------|------|
| `/admin/login` | 管理员登录 |
| `/admin/logout` | 管理员登出 |
| `/admin/dashboard/stats` | 数据统计 |
| `/admin/user/*` | 用户管理(列表/详情/添加/修改密码/重置密码) |
| `/admin/video/*` | 视频管理(列表/保存/删除/状态切换) |
| `/admin/category/*` | 分类管理 |
| `/admin/card/*` | 卡密管理(列表/生成/删除/禁用) |
| `/admin/collectSource/*` | 资源采集站点(列表/添加/编辑/删除/测试连接/重置采集) |
| `/admin/video/collectBySourceId` | 采集视频(GET接口，支持断点续采) |
| `/admin/banner/*` | 轮播图管理 |
| `/admin/config/*` | 系统配置管理 |
| `/admin/logs` | 管理员操作日志 |

#### 用户端 API
| 接口 | 说明 |
|------|------|
| **认证** | |
| `/auth/register` | 用户注册 |
| `/auth/login` | 用户登录 |
| `/auth/refresh` | 刷新Token |
| **视频（公开）** | |
| `/video/home` | 首页数据（推荐、分类筛选） |
| `/video/list` | 视频列表（分页/筛选） |
| `/video/detail` | 视频详情 |
| `/video/rank` | 排行榜 |
| `/video/search` | 搜索视频 |
| `/video/categories` | 分类列表 |
| `/video/playUrl` | 获取播放地址 |
| `/video/play/:id` | 播放页数据 |
| **用户（需认证）** | |
| `/user/info` | 用户信息 |
| **收藏** | |
| `/favorite/list` | 收藏列表 |
| `/favorite/add` | 添加收藏 |
| `/favorite/remove` | 取消收藏 |
| **观看历史** | |
| `/history/list` | 历史记录列表 |
| `/history/add` | 添加历史记录 |
| `/history/sync` | 同步历史记录 |
| `/history/clear` | 清除历史记录 |
| **卡密** | |
| `/card/redeem` | 卡密兑换 |
| **广告** | |
| `/ad/watch` | 广告观看 |

### 启动方式

```bash
cd backend

# 安装依赖
composer install

# 复制配置
cp .env.example .env

# 启动开发服务器
php think run
```

### 数据库配置

当前使用 SQLite 数据库，数据库文件位于 `database/database.sqlite`。

```bash
# 初始化数据库
sqlite3 database/database.sqlite < database/init.sql
```

---

## 二、用户端 (multi-end-users)

### 技术栈

| 技术 | 版本 | 说明 |
|------|------|------|
| Vue | ^3.4 | 渐进式框架 |
| Vite | ^5.2 | 构建工具 |
| Vant | ^4.8 | 移动端 UI |
| Pinia | ^2.1 | 状态管理 |
| Vue Router | ^4.3 | 路由管理 |
| Axios | ^1.6 | HTTP 客户端 |
| Video.js | ^8.10 | 视频播放器 |
| NProgress | ^0.2 | 进度条 |
| Dayjs | ^1.11 | 时间处理 |

### 目录结构

```
multi-end-users/
├── src/
│   ├── router/
│   │   └── index.js        # 路由配置
│   ├── stores/              # 状态管理
│   │   ├── history.js      # 历史记录状态
│   │   └── user.js         # 用户状态
│   ├── styles/
│   │   └── index.scss      # 全局样式
│   ├── utils/
│   │   └── request.js      # Axios 封装
│   ├── views/              # 页面组件
│   │   ├── Home.vue        # 首页
│   │   ├── Category.vue    # 分类页
│   │   ├── Detail.vue       # 视频详情
│   │   ├── Play.vue        # 播放页
│   │   ├── Rank.vue        # 排行榜
│   │   ├── Favorites.vue   # 我的收藏
│   │   ├── History.vue     # 观看历史
│   │   ├── Card.vue        # 卡密充值
│   │   ├── Login.vue       # 登录页
│   │   ├── Register.vue    # 注册页
│   │   └── User.vue        # 个人中心
│   ├── App.vue             # 根组件
│   └── main.js             # 入口文件
├── index.html               # HTML 模板
├── package.json             # 依赖配置
└── vite.config.js           # Vite 配置
```

### 页面说明

| 页面 | 路由 | 说明 |
|------|------|------|
| 首页 | `/` | 视频推荐、分类筛选 |
| 分类 | `/category` | 按分类浏览 |
| 详情 | `/detail/:id` | 视频详情信息 |
| 播放 | `/play/:id` | 视频播放页面 |
| 排行榜 | `/rank` | 热门视频排行 |
| 我的收藏 | `/favorites` | 收藏列表 |
| 观看历史 | `/history` | 历史记录 |
| 卡密充值 | `/card` | 卡密兑换 |
| 登录 | `/login` | 用户登录 |
| 注册 | `/register` | 用户注册 |
| 个人中心 | `/user` | 用户信息 |

### 底部导航

- **首页**：推荐、电影、电视剧、动漫、短视频、纪录片
- **排行榜**：热门视频排行
- **我的**：收藏、历史、充值、个人信息

### 启动方式

```bash
cd multi-end-users

# 安装依赖
npm install

# 启动开发服务器
npm run dev
```

---

## 三、管理端 (system-end-admin)

### 技术栈

| 技术 | 版本 | 说明 |
|------|------|------|
| Vue | ^3.4 | 渐进式框架 |
| Vite | ^5.2 | 构建工具 |
| Element Plus | ^2.6 | PC 端 UI |
| Pinia | ^2.1 | 状态管理 |
| Vue Router | ^4.3 | 路由管理 |
| Axios | ^1.6 | HTTP 客户端 |
| ECharts | ^5.5 | 数据可视化 |
| Dayjs | ^1.11 | 时间处理 |

### 目录结构

```
system-end-admin/
├── src/
│   ├── router/
│   │   └── index.js        # 路由配置
│   ├── utils/
│   │   └── request.js       # Axios 封装
│   ├── views/               # 页面组件
│   │   ├── Layout.vue      # 布局组件
│   │   ├── Login.vue       # 登录页
│   │   ├── Dashboard.vue    # 数据看板
│   │   ├── User.vue        # 用户管理
│   │   ├── Video.vue        # 视频管理
│   │   ├── Card.vue        # 卡密管理
│   │   └── Collect.vue     # 采集管理
│   ├── App.vue              # 根组件
│   └── main.js              # 入口文件
├── index.html               # HTML 模板
├── package.json             # 依赖配置
└── vite.config.js           # Vite 配置
```

### 页面说明

| 页面 | 路由 | 说明 |
|------|------|------|
| 登录 | `/login` | 管理员登录 |
| 数据看板 | `/dashboard` | 统计数据图表 |
| 用户管理 | `/user` | 用户列表、VIP管理 |
| 视频管理 | `/video` | 视频列表、编辑 |
| 卡密管理 | `/card` | 卡密生成、统计 |
| 资源采集 | `/collect` | 苹果CMS资源站采集 |

### 启动方式

```bash
cd system-end-admin

# 安装依赖
npm install

# 启动开发服务器
npm run dev
```

---

## 四、数据库 (database)

### 支持数据库

- **SQLite** 3.26+ (当前使用)
- **MySQL** 5.7+ (兼容)

### 数据库文件

`database/database.sqlite`

### 表结构

| 表名 | 说明 |
|------|------|
| `users` | 用户表 |
| `categories` | 影视分类表 |
| `videos` | 影视内容表 (含source_vod_id去重字段) |
| `video_sources` | 视频资源播放地址表 |
| `collect_sources` | 资源采集站点配置表 (含page_count断点续采字段) |
| `source_sites` | 资源站点关联表 |
| `watch_history` | 观看历史记录表 |
| `favorites` | 收藏表 |
| `card_keys` | 卡密表 |
| `vip_transactions` | VIP变动记录表 |
| `login_logs` | 用户登录日志表 |
| `admin_login_logs` | 管理员登录日志表 |
| `admin_logs` | 管理员操作日志表 |
| `system_config` | 系统配置表 |
| `admins` | 管理员表 |
| `banners` | 轮播图表 |

### 初始化

```bash
sqlite3 database/database.sqlite < database/init.sql
```

---

## 五、VIP 会员体系

### 获取方式

1. **卡密兑换**：天卡、周卡、月卡、季卡、年卡、永久卡
2. **观看广告**：每日可观看广告获得 VIP 时长

### 权限

- 观看 VIP 专属视频
- 免广告观看

---

## 六、资源采集

### 支持资源站

- **苹果CMS**：通过 `ac=detail` API 对接资源站，自动采集影视数据

### 采集功能

- 自动获取视频信息（含简介、封面、播放地址等）
- 支持断点续采（记录采集页码和中断位置）
- 支持多资源站切换
- 采集去重：按 title + release_year 判断重复
- 剧集更新：比较剧集数量，只有新增时才更新

### 采集接口

| 接口 | 方法 | 说明 |
|------|------|------|
| `/admin/video/collectBySourceId` | GET | 触发采集/处理下一个视频 |
| `/admin/video/collectProgress` | GET | 获取采集进度 |
| `/admin/video/collectReset` | POST | 强制重置采集任务 |
| `/admin/collectSource/resetCollect` | POST | 重置资源站点采集状态 |

---

## 七、管理员操作日志

所有管理员操作都会记录到 `admin_logs` 表，包括：

- 视频管理：添加/编辑/删除/上架/隐藏
- 分类管理：添加/编辑/删除
- 资源站点：添加/编辑/删除/启用/禁用/重置采集
- 轮播图管理：添加/编辑/删除/启用/禁用
- 用户管理：添加/修改VIP/重置密码
- 卡密管理：生成/删除/禁用
- 系统配置：修改

---

## 配置说明

### 环境变量 (.env)

```ini
# 数据库配置 - 当前使用 SQLite
DATABASE_TYPE=sqlite
DATABASE_SQLITE_PATH=c:/Users/Administrator/Desktop/moive-app/database/database.sqlite
```

---

## 开发说明

### 目录说明

| 目录 | 说明 |
|------|------|
| `backend/` | 后端 API，使用 ThinkPHP 8.x |
| `multi-end-users/` | 用户端，使用 Vue 3 + Vant |
| `system-end-admin/` | 管理端，使用 Vue 3 + Element Plus |
| `database/` | 数据库文件和脚本 |

### 端口说明

| 服务 | 端口 |
|------|------|
| 后端 API | 8080 |
| 用户端 | 3000 |
| 管理端 | 3001 |
