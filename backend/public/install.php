<?php
/**
 * 系统安装向导
 */

// 错误报告
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 定义路径
define('ROOT_PATH', dirname(__DIR__) . '/');
define('DATABASE_PATH', ROOT_PATH . 'database/');
define('ENV_PATH', ROOT_PATH . '.env');

// 错误状态
$error = '';

// 处理安装请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. 检查环境
    if ($action === 'check_env') {
        $checks = [];
        $checks['php_version'] = version_compare(PHP_VERSION, '8.1', '>=');
        $checks['pdo'] = extension_loaded('pdo');
        $checks['pdo_mysql'] = extension_loaded('pdo_mysql');
        $checks['pdo_sqlite'] = extension_loaded('pdo_sqlite');
        $checks['json'] = extension_loaded('json');
        $checks['mbstring'] = extension_loaded('mbstring');
        $checks['composer'] = file_exists(ROOT_PATH . 'vendor/autoload.php');

        // 检测 CLI PHP 版本（composer 使用 CLI PHP）
        $cli_php_version = 'unknown';
        $cli_php_ok = false;
        exec('php -v', $cli_output, $cli_return);
        if ($cli_return === 0 && !empty($cli_output)) {
            foreach ($cli_output as $line) {
                if (preg_match('/PHP (\d+\.\d+\.\d+)/', $line, $matches)) {
                    $cli_php_version = $matches[1];
                    $cli_php_ok = version_compare($cli_php_version, '8.1', '>=');
                    break;
                }
            }
        }
        $checks['cli_php_version'] = $cli_php_version;
        $checks['cli_php_ok'] = $cli_php_ok;

        $all_pass = !in_array(false, array_filter($checks, function($v, $k) {
            return $k !== 'composer' && $k !== 'cli_php_version'; // composer 和版本号不参与全部通过判断
        }, ARRAY_FILTER_USE_BOTH), true);

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'checks' => $checks, 'all_pass' => $all_pass]);
        exit;
    }

    // 2. 执行 composer install
    if ($action === 'composer_install') {
        $result = ['success' => false, 'message' => '', 'output' => ''];

        // 检查 composer 是否存在
        $composer_path = ROOT_PATH . 'composer.phar';
        $composer_cmd = 'composer';

        // 尝试使用 composer.phar
        if (file_exists($composer_path)) {
            $composer_cmd = 'php ' . $composer_path;
        } elseif (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Windows 环境
            $composer_cmd = 'composer.bat';
        }

        // 检查是否已有 vendor
        if (file_exists(ROOT_PATH . 'vendor/autoload.php')) {
            $result['success'] = true;
            $result['message'] = '依赖已安装';
            header('Content-Type: application/json');
            echo json_encode($result);
            exit;
        }

        // 执行 composer install
        $output = [];
        $return_var = 0;
        chdir(ROOT_PATH);
        exec($composer_cmd . ' install --no-interaction 2>&1', $output, $return_var);

        $output_str = implode("\n", $output);

        if ($return_var === 0 && file_exists(ROOT_PATH . 'vendor/autoload.php')) {
            $result['success'] = true;
            $result['message'] = '依赖安装成功';
            $result['output'] = $output_str;
        } else {
            $result['message'] = '依赖安装失败';
            $result['output'] = $output_str;
        }

        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }

    // 3. 测试数据库连接
    if ($action === 'test_db') {
        $db_type = $_POST['db_type'] ?? 'mysql';
        $result = ['success' => false, 'message' => ''];

        try {
            if ($db_type === 'sqlite') {
                $sqlite_path = $_POST['sqlite_path'] ?? DATABASE_PATH . 'database.sqlite';
                $dir = dirname($sqlite_path);
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                $pdo = new PDO('sqlite:' . $sqlite_path);
            } else {
                $host = $_POST['db_host'] ?? '127.0.0.1';
                $port = $_POST['db_port'] ?? '3306';
                $database = $_POST['db_name'] ?? 'moive_app';
                $username = $_POST['db_user'] ?? 'root';
                $password = $_POST['db_pass'] ?? '';

                $dsn = "mysql:host={$host};port={$port};charset=utf8mb4";
                $pdo = new PDO($dsn, $username, $password);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                // 创建数据库
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
                $pdo->exec("USE `{$database}`");
            }

            $result['success'] = true;
            $result['message'] = '数据库连接成功';
        } catch (PDOException $e) {
            $result['message'] = '数据库连接失败: ' . $e->getMessage();
        }

        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }

    // 4. 执行安装
    if ($action === 'install') {
        $result = ['success' => false, 'message' => ''];

        try {
            $db_type = $_POST['db_type'] ?? 'mysql';
            $admin_username = trim($_POST['admin_username'] ?? 'admin');
            $admin_password = trim($_POST['admin_password'] ?? '');
            $site_name = trim($_POST['site_name'] ?? '影视系统');

            if (empty($admin_password)) {
                throw new Exception('管理员密码不能为空');
            }

            // 连接数据库
            $pdo = null;
            if ($db_type === 'sqlite') {
                $sqlite_path = $_POST['sqlite_path'] ?? DATABASE_PATH . 'database.sqlite';
                $pdo = new PDO('sqlite:' . $sqlite_path);
            } else {
                $host = $_POST['db_host'] ?? '127.0.0.1';
                $port = $_POST['db_port'] ?? '3306';
                $database = $_POST['db_name'] ?? 'moive_app';
                $username = $_POST['db_user'] ?? 'root';
                $password = $_POST['db_pass'] ?? '';

                $dsn = "mysql:host={$host};port={$port};charset=utf8mb4";
                $pdo = new PDO($dsn, $username, $password, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                ]);
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
                $pdo->exec("USE `{$database}`");
            }

            // 读取并处理 SQL 文件
            $sql_file = DATABASE_PATH . 'init.sql';
            if (!file_exists($sql_file)) {
                throw new Exception('数据库初始化文件不存在');
            }

            $sql_content = file_get_contents($sql_file);

            // SQLite 兼容处理
            if ($db_type === 'sqlite') {
                // 替换 MySQL 特有语法
                $sql_content = str_replace('`', '', $sql_content);
                $sql_content = str_replace('COMMENT ', '-- COMMENT ', $sql_content);
                $sql_content = preg_replace('/ENGINE=\w+/', '', $sql_content);
                $sql_content = preg_replace('/DEFAULT CHARSET=\w+/', '', $sql_content);
                $sql_content = preg_replace('/COLLATE utf8mb4_general_ci/', '', $sql_content);
                // 处理自增ID，SQLite 使用 AUTOINCREMENT
                $sql_content = preg_replace('/INTEGER PRIMARY KEY AUTOINCREMENT/', 'INTEGER PRIMARY KEY AUTOINCREMENT', $sql_content);
                // 移除 MySQL 的 IF NOT EXISTS 后面紧跟的表创建语句
                $sql_content = preg_replace('/IF NOT EXISTS\s+/', '', $sql_content);
            }

            // 分割 SQL 语句
            $statements = array_filter(array_map('trim', explode(';', $sql_content)), function($stmt) {
                return !empty($stmt) && !str_starts_with($stmt, '--');
            });

            // 执行 SQL
            foreach ($statements as $statement) {
                if (stripos($statement, 'INSERT') === 0) {
                    // INSERT 语句特殊处理
                    $pdo->exec($statement);
                } else if (stripos($statement, 'CREATE') === 0 || stripos($statement, 'CREATE INDEX') === 0) {
                    $pdo->exec($statement);
                }
            }

            // 更新管理员密码
            $hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);

            if ($db_type === 'sqlite') {
                $pdo->exec("UPDATE admins SET password = '{$hashed_password}', username = '{$admin_username}'");
                $pdo->exec("UPDATE system_config SET value = '{$site_name}' WHERE `key` = 'site_name'");
            } else {
                $pdo->exec("UPDATE `admins` SET password = '" . $hashed_password . "', username = '" . $admin_username . "'");
                $pdo->exec("UPDATE `system_config` SET value = '" . $site_name . "' WHERE `key` = 'site_name'");
            }

            // 生成 .env 文件
            $env_content = "[APP]\n";
            $env_content .= "app_debug = false\n";
            $env_content .= "app_trace = false\n\n";
            $env_content .= "[DATABASE]\n";
            $env_content .= "database.type = {$db_type}\n";

            if ($db_type === 'sqlite') {
                $env_content .= "database.sqlite_path = ../database/database.sqlite\n";
            } else {
                $env_content .= "database.hostname = {$host}\n";
                $env_content .= "database.database = {$database}\n";
                $env_content .= "database.username = {$username}\n";
                $env_content .= "database.password = {$password}\n";
                $env_content .= "database.hostport = {$port}\n";
                $env_content .= "database.charset = utf8mb4\n";
            }

            $env_content .= "\n[JWT]\n";
            $env_content .= "jwt.secret = " . bin2hex(random_bytes(32)) . "\n";
            $env_content .= "jwt.expire = 86400\n";

            file_put_contents(ENV_PATH, $env_content);

            $result['success'] = true;
            $result['message'] = '安装成功！';
        } catch (Exception $e) {
            $result['message'] = '安装失败: ' . $e->getMessage();
        }

        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>系统安装 - 影视系统</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }
        .container {
            max-width: 700px;
            margin: 0 auto;
        }
        .card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            padding: 30px;
            text-align: center;
        }
        .header h1 { font-size: 28px; margin-bottom: 10px; }
        .header p { opacity: 0.9; font-size: 14px; }
        .content { padding: 30px; }
        .step {
            display: none;
        }
        .step.active { display: block; }
        .step h2 {
            font-size: 18px;
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
        }
        .check-list {
            margin-bottom: 20px;
        }
        .check-item {
            display: flex;
            justify-content: space-between;
            padding: 12px 15px;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 10px;
        }
        .check-item.pass { background: #d4edda; color: #155724; }
        .check-item.fail { background: #f8d7da; color: #721c24; }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: #667eea;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        .btn {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        .btn-secondary {
            background: #6c757d;
            color: #fff;
            margin-right: 10px;
        }
        .btn-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        .db-type-tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 2px solid #eee;
        }
        .db-type-tab {
            padding: 12px 24px;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            color: #666;
            font-weight: 500;
        }
        .db-type-tab.active {
            border-bottom-color: #667eea;
            color: #667eea;
        }
        .db-config { display: none; }
        .db-config.active { display: block; }
        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .message.error { background: #f8d7da; color: #721c24; }
        .message.success { background: #d4edda; color: #155724; }
        .message.info { background: #d1ecf1; color: #0c5460; }
        .success-page {
            text-align: center;
            padding: 40px 20px;
        }
        .success-icon {
            width: 80px;
            height: 80px;
            background: #28a745;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }
        .success-icon svg { width: 40px; height: 40px; fill: #fff; }
        .info-box {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            text-align: left;
        }
        .info-box h4 { margin-bottom: 10px; color: #333; }
        .info-box p { color: #666; margin-bottom: 8px; font-size: 14px; }
        .info-box code {
            background: #e9ecef;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: Consolas, monospace;
        }
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="header">
                <h1>影视系统安装向导</h1>
                <p>只需几分钟即可完成安装</p>
            </div>
            <div class="content">
                <!-- 步骤1: 环境检测 -->
                <div class="step active" id="step1">
                    <h2>第一步：环境检测</h2>
                    <div class="check-list" id="checkList">
                        <div class="check-item">正在检测环境...</div>
                    </div>
                    <button class="btn btn-primary" onclick="checkEnv()">重新检测</button>
                    <div class="btn-group">
                        <button class="btn btn-primary" id="nextStep1" onclick="goToStep(2)" disabled>下一步</button>
                    </div>
                </div>

                <!-- 步骤2: 安装依赖 -->
                <div class="step" id="step2">
                    <h2>第二步：安装依赖</h2>
                    <div class="form-group">
                        <p style="color: #666; margin-bottom: 15px;">正在安装 PHP 依赖包，请稍候...</p>
                    </div>
                    <div id="composerMessage"></div>
                    <div class="info-box">
                        <h4>即将安装的依赖</h4>
                        <p>topthink/framework ^8.0</p>
                        <p>topthink/think-orm ^3.0</p>
                        <p>topthink/think-multi-app ^1.0</p>
                        <p>firebase/php-jwt ^6.0</p>
                    </div>
                    <div class="btn-group">
                        <button class="btn btn-secondary" onclick="goToStep(1)">上一步</button>
                        <button class="btn btn-primary" id="btnComposer" onclick="runComposerInstall()">安装依赖</button>
                        <button class="btn btn-primary" id="nextStep2" onclick="goToStep(3)" disabled>下一步</button>
                    </div>
                </div>

                <!-- 步骤3: 数据库配置 -->
                <div class="step" id="step3">
                    <h2>第三步：数据库配置</h2>
                    <div class="db-type-tabs">
                        <div class="db-type-tab active" onclick="switchDbType('mysql')">MySQL</div>
                        <div class="db-type-tab" onclick="switchDbType('sqlite')">SQLite</div>
                    </div>

                    <!-- MySQL 配置 -->
                    <div class="db-config active" id="mysqlConfig">
                        <div class="form-row">
                            <div class="form-group">
                                <label>数据库地址</label>
                                <input type="text" id="dbHost" value="127.0.0.1">
                            </div>
                            <div class="form-group">
                                <label>端口</label>
                                <input type="text" id="dbPort" value="3306">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>数据库名称</label>
                            <input type="text" id="dbName" value="moive_app">
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>用户名</label>
                                <input type="text" id="dbUser" value="root">
                            </div>
                            <div class="form-group">
                                <label>密码</label>
                                <input type="password" id="dbPass" placeholder="请输入密码">
                            </div>
                        </div>
                    </div>

                    <!-- SQLite 配置 -->
                    <div class="db-config" id="sqliteConfig">
                        <div class="form-group">
                            <label>数据库路径</label>
                            <input type="text" id="sqlitePath" value="../database/database.sqlite">
                        </div>
                    </div>

                    <div id="dbMessage"></div>

                    <div class="btn-group">
                        <button class="btn btn-secondary" onclick="goToStep(2)">上一步</button>
                        <button class="btn btn-primary" onclick="testDb()">测试连接</button>
                        <button class="btn btn-primary" id="nextStep3" onclick="goToStep(4)" disabled>下一步</button>
                    </div>
                </div>

                <!-- 步骤4: 管理员设置 -->
                <div class="step" id="step4">
                    <h2>第四步：管理员设置</h2>
                    <div class="form-group">
                        <label>网站名称</label>
                        <input type="text" id="siteName" value="影视系统">
                    </div>
                    <div class="form-group">
                        <label>管理员用户名</label>
                        <input type="text" id="adminUsername" value="admin">
                    </div>
                    <div class="form-group">
                        <label>管理员密码</label>
                        <input type="password" id="adminPassword" placeholder="请输入管理员密码">
                    </div>
                    <div id="installMessage"></div>
                    <div class="btn-group">
                        <button class="btn btn-secondary" onclick="goToStep(3)">上一步</button>
                        <button class="btn btn-primary" onclick="doInstall()">立即安装</button>
                    </div>
                </div>

                <!-- 步骤5: 安装完成 -->
                <div class="step" id="step5">
                    <div class="success-page">
                        <div class="success-icon">
                            <svg viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                        </div>
                        <h2 style="color: #28a745; margin-bottom: 15px;">安装成功！</h2>
                        <p style="color: #666; margin-bottom: 20px;">影视系统已成功安装</p>

                        <div class="info-box">
                            <h4>后续步骤</h4>
                            <p><strong>用户端地址：</strong> <code>http://你的域名/multi-end-users/</code></p>
                            <p><strong>管理端地址：</strong> <code>http://你的域名/system-end-admin/</code></p>
                            <p><strong>管理员账号：</strong> <code id="finalUsername">admin</code></p>
                            <p><strong>管理员密码：</strong> <code>******</code></p>
                        </div>

                        <div class="info-box">
                            <h4>启动服务</h4>
                            <p>后端：<code>cd backend && php think run</code></p>
                            <p>用户端：<code>cd multi-end-users && npm run dev</code></p>
                            <p>管理端：<code>cd system-end-admin && npm run dev</code></p>
                        </div>

                        <a href="../" class="btn btn-primary" style="display: inline-block; text-decoration: none; margin-top: 20px;">返回首页</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentDbType = 'mysql';
        let dbConnected = false;
        let composerInstalled = false;

        // 步骤切换
        function goToStep(step) {
            document.querySelectorAll('.step').forEach(s => s.classList.remove('active'));
            document.getElementById('step' + step).classList.add('active');
        }

        // 环境检测
        async function checkEnv() {
            const checkList = document.getElementById('checkList');
            checkList.innerHTML = '<div class="check-item">正在检测环境...</div>';

            try {
                const res = await fetch('install.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=check_env'
                });
                const data = await res.json();

                if (data.success) {
                    const checks = data.checks;
                    const items = [
                        { name: 'PHP 版本 >= 8.1 (网页版)', key: 'php_version', current: '<?php echo PHP_VERSION; ?>' },
                        { name: 'PHP 版本 >= 8.1 (CLI版)', key: 'cli_php_ok', current: checks.cli_php_version || '未知' },
                        { name: 'PDO 扩展', key: 'pdo', current: checks.pdo ? '已启用' : '未启用' },
                        { name: 'PDO MySQL 扩展', key: 'pdo_mysql', current: checks.pdo_mysql ? '已启用' : '未启用' },
                        { name: 'PDO SQLite 扩展', key: 'pdo_sqlite', current: checks.pdo_sqlite ? '已启用' : '未启用' },
                        { name: 'JSON 扩展', key: 'json', current: checks.json ? '已启用' : '未启用' },
                        { name: 'MBString 扩展', key: 'mbstring', current: checks.mbstring ? '已启用' : '未启用' },
                    ];

                    checkList.innerHTML = items.map(item => `
                        <div class="check-item ${checks[item.key] ? 'pass' : 'fail'}">
                            <span>${item.name}</span>
                            <span>${item.current}</span>
                        </div>
                    `).join('');

                    document.getElementById('nextStep1').disabled = !data.all_pass;
                }
            } catch (e) {
                checkList.innerHTML = '<div class="message error">检测失败: ' + e.message + '</div>';
            }
        }

        // 执行 composer install
        async function runComposerInstall() {
            const msgDiv = document.getElementById('composerMessage');
            const btn = document.getElementById('btnComposer');
            btn.disabled = true;
            btn.innerHTML = '<span class="loading"></span> 安装中...';

            msgDiv.innerHTML = '<div class="message info">正在执行 composer install，请稍候...</div>';

            try {
                const res = await fetch('install.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=composer_install'
                });
                const data = await res.json();

                if (data.success) {
                    msgDiv.innerHTML = '<div class="message success">' + data.message + '</div>';
                    composerInstalled = true;
                    document.getElementById('nextStep2').disabled = false;
                    btn.style.display = 'none';
                } else {
                    msgDiv.innerHTML = '<div class="message error">' + data.message + '</div>';
                    if (data.output) {
                        msgDiv.innerHTML += '<pre style="margin-top:10px;padding:10px;background:#f8f9fa;border-radius:4px;font-size:12px;max-height:200px;overflow:auto;">' + data.output + '</pre>';
                    }
                    btn.disabled = false;
                    btn.innerHTML = '重新安装';
                }
            } catch (e) {
                msgDiv.innerHTML = '<div class="message error">安装失败: ' + e.message + '</div>';
                btn.disabled = false;
                btn.innerHTML = '重新安装';
            }
        }

        // 切换数据库类型
        function switchDbType(type) {
            currentDbType = type;
            document.querySelectorAll('.db-type-tab').forEach(t => t.classList.remove('active'));
            event.target.classList.add('active');
            document.querySelectorAll('.db-config').forEach(c => c.classList.remove('active'));
            document.getElementById(type + 'Config').classList.add('active');
            dbConnected = false;
            document.getElementById('nextStep3').disabled = true;
        }

        // 测试数据库连接
        async function testDb() {
            const msgDiv = document.getElementById('dbMessage');
            msgDiv.innerHTML = '<div class="message info">正在测试连接...</div>';

            const params = new URLSearchParams();
            params.append('action', 'test_db');
            params.append('db_type', currentDbType);

            if (currentDbType === 'mysql') {
                params.append('db_host', document.getElementById('dbHost').value);
                params.append('db_port', document.getElementById('dbPort').value);
                params.append('db_name', document.getElementById('dbName').value);
                params.append('db_user', document.getElementById('dbUser').value);
                params.append('db_pass', document.getElementById('dbPass').value);
            } else {
                params.append('sqlite_path', document.getElementById('sqlitePath').value);
            }

            try {
                const res = await fetch('install.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: params.toString()
                });
                const data = await res.json();

                if (data.success) {
                    msgDiv.innerHTML = '<div class="message success">' + data.message + '</div>';
                    dbConnected = true;
                    document.getElementById('nextStep3').disabled = false;
                } else {
                    msgDiv.innerHTML = '<div class="message error">' + data.message + '</div>';
                    dbConnected = false;
                    document.getElementById('nextStep3').disabled = true;
                }
            } catch (e) {
                msgDiv.innerHTML = '<div class="message error">测试失败: ' + e.message + '</div>';
            }
        }

        // 执行安装
        async function doInstall() {
            const adminPassword = document.getElementById('adminPassword').value;
            if (!adminPassword) {
                document.getElementById('installMessage').innerHTML = '<div class="message error">请输入管理员密码</div>';
                return;
            }

            const msgDiv = document.getElementById('installMessage');
            msgDiv.innerHTML = '<div class="message info"><span class="loading"></span> 正在安装，请稍候...</div>';

            const params = new URLSearchParams();
            params.append('action', 'install');
            params.append('db_type', currentDbType);
            params.append('site_name', document.getElementById('siteName').value);
            params.append('admin_username', document.getElementById('adminUsername').value);
            params.append('admin_password', adminPassword);

            if (currentDbType === 'mysql') {
                params.append('db_host', document.getElementById('dbHost').value);
                params.append('db_port', document.getElementById('dbPort').value);
                params.append('db_name', document.getElementById('dbName').value);
                params.append('db_user', document.getElementById('dbUser').value);
                params.append('db_pass', document.getElementById('dbPass').value);
            } else {
                params.append('sqlite_path', document.getElementById('sqlitePath').value);
            }

            try {
                const res = await fetch('install.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: params.toString()
                });
                const data = await res.json();

                if (data.success) {
                    document.getElementById('finalUsername').textContent = document.getElementById('adminUsername').value;
                    goToStep(5);
                } else {
                    msgDiv.innerHTML = '<div class="message error">' + data.message + '</div>';
                }
            } catch (e) {
                msgDiv.innerHTML = '<div class="message error">安装失败: ' + e.message + '</div>';
            }
        }

        // 页面加载时自动检测
        window.onload = checkEnv;
    </script>
</body>
</html>
