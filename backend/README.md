# 后端目录说明

## 目录结构
```
backend/
├── app/              # 应用目录
│   ├── controller/   # 控制器
│   ├── model/        # 模型
│   ├── service/      # 服务层
│   ├── middleware/   # 中间件
│   ├── common.php    # 公共函数
│   └── route/        # 路由
├── config/           # 配置目录
│   ├── app.php      # 应用配置
│   ├── database.php  # 数据库配置
│   ├── route.php    # 路由配置
│   └── jwt.php      # JWT配置
├── route/            # 路由定义
├── public/           # Web入口
├── vendor/           # Composer依赖(安装后生成)
├── composer.json    # Composer配置
└── .env             # 环境变量(需创建)
```

## 安装步骤
1. 进入backend目录
2. 执行 `composer install`
3. 复制 `.env.example` 为 `.env`
4. 修改 `.env` 中的数据库配置
5. 访问 `http://your-domain/api/` 测试接口
