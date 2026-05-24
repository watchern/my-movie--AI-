# 影视系统

一个支持手机、平板、Web 三合一的影视系统，支持多资源站采集。

## 项目结构

```
moive-app/
├── backend/                 # 后端 API 服务
├── multi-end-users/         # 用户端（移动端/PC端）
├── system-end-admin/        # 管理端
└── database/                 # 数据库脚本
```

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
│   ├── common/              # 公共类
│   │   └── JwtHelper.php    # JWT 工具类
│   ├── controller/          # 控制器
│   │   ├── admin/           # 管理端控制器
│   │   │   ├── DashboardController.php  # 数据看板
│   │   │   ├── LoginController.php     # 管理员登录
│   │   │   ├── UserController.php      # 用户管理
│   │   │   └── VideoController.php     # 视频管理
│   │   ├── AuthController.php          # 用户认证
│   │   ├── CardController.php          # 卡密兑换
│   │   ├── AdController.php            # 广告观看
│   │   ├── FavoriteController.php      # 收藏管理
│   │   ├── HistoryController.php       # 观看历史
│   │   └── VideoController.php         # 视频接口
│   ├── middleware/          # 中间件
│   │   └── ApiAuth.php      # API 认证中间件
│   ├── model/               # 数据模型
│   │   ├── Admin.php        # 管理员模型
│   │   ├── CardKey.php      # 卡密模型
│   │   ├── Category.php     # 分类模型
│   │   ├── Favorite.php      # 收藏模型
│   │   ├── LoginLog.php     # 登录日志模型
│   │   ├── SourceSite.php   # 资源站模型
│   │   ├── SystemConfig.php # 系统配置模型
│   │   ├── User.php         # 用户模型
│   │   ├── Video.php        # 视频模型
│   │   ├── VideoSource.php  # 视频资源模型
│   │   ├── VipTransaction.php  # VIP变动记录模型
│   │   └── WatchHistory.php # 观看历史模型
│   └── service/             # 服务层
│       └── AppleCmsService.php  # 苹果CMS采集服务
├── config/                  # 配置文件
│   ├── app.php             # 应用配置
│   ├── console.php         # 控制台配置
│   ├── database.php        # 数据库配置
│   └── jwt.php             # JWT 配置
├── public/
│   └── index.php           # 入口文件
├── route/                  # 路由配置
│   ├── admin.php           # 管理端路由
│   └── app.php             # 用户端路由
├── .env.example            # 环境变量示例
├── composer.json           # PHP 依赖
└── README.md               # 后端说明
```

### 功能说明

#### 用户端 API
| 接口 | 说明 |
|------|------|
| `/auth/register` | 用户注册 |
| `/auth/login` | 用户登录 |
| `/video/list` | 视频列表 |
| `/video/detail` | 视频详情 |
| `/video/sources` | 播放资源 |
| `/favorite/*` | 收藏管理 |
| `/history/*` | 历史记录 |
| `/card/redeem` | 卡密兑换 |
| `/ad/watch` | 广告观看 |

#### 管理端 API
| 接口 | 说明 |
|------|------|
| `/admin/login` | 管理员登录 |
| `/admin/dashboard` | 数据统计 |
| `/admin/user/*` | 用户管理 |
| `/admin/video/*` | 视频管理 |
| `/admin/card/*` | 卡密管理 |

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

- **MySQL** 5.7+
- **SQLite** 3.26+

### 表结构

| 表名 | 说明 |
|------|------|
| `users` | 用户表 |
| `categories` | 影视分类表 |
| `videos` | 影视内容表 |
| `video_sources` | 视频资源播放地址表 |
| `watch_history` | 观看历史记录表 |
| `favorites` | 收藏表 |
| `card_keys` | 卡密表 |
| `vip_transactions` | VIP变动记录表 |
| `login_logs` | 用户登录日志表 |
| `source_sites` | 资源站点配置表 |
| `system_config` | 系统配置表 |
| `admins` | 管理员表 |

### 初始化

```bash
# MySQL
mysql -u root -p < database/init.sql

# SQLite
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

- **苹果CMS**：通过 API 对接资源站，自动采集影视数据

### 采集功能

- 自动获取视频信息
- 自动获取播放资源
- 支持多资源站切换

---

## 配置说明

### 环境变量 (.env)

```ini
# 应用配置
[APP]
app_debug = true
app_trace = false

# 数据库配置
[DATABASE]
database.type = mysql
database.hostname = 127.0.0.1
database.database = moive_app
database.username = root
database.password =
database.hostport = 3306
database.charset = utf8mb4

# SQLite 配置（仅 database.type = sqlite 时有效）
# database.sqlite_path = ../database/database.sqlite
```

---

## 开发说明

### 目录说明

| 目录 | 说明 |
|------|------|
| `backend/` | 后端 API，使用 ThinkPHP 8.x |
| `multi-end-users/` | 用户端，使用 Vue 3 + Vant |
| `system-end-admin/` | 管理端，使用 Vue 3 + Element Plus |
| `database/` | 数据库脚本 |

### 端口说明

| 服务 | 端口 |
|------|------|
| 后端 API | 8000 |
| 用户端 | 3000 |
| 管理端 | 3001 |
