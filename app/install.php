<?php
require_once dirname(__DIR__) . '/config/app.php';

error_reporting(0);
ini_set('display_errors', 0);

$installed = jmweb_is_installed();
$message = '';
$messageType = 'danger';
$success = false;

function installer_value($key, $default = '')
{
    return htmlspecialchars(isset($_POST[$key]) ? $_POST[$key] : $default, ENT_QUOTES, 'UTF-8');
}

function installer_write_config($config)
{
    $content = "<?php\n/**\n * 数据库配置\n * 本文件由安装程序生成。\n */\nreturn " . var_export($config, true) . ";\n";
    return file_put_contents(dirname(__DIR__) . '/config/database.php', $content, LOCK_EX) !== false;
}

function installer_create_lock()
{
    $dataPath = dirname(__DIR__) . '/data';
    if (!is_dir($dataPath)) {
        mkdir($dataPath, 0755, true);
    }
    return file_put_contents($dataPath . '/install.lock', 'installed_at=' . date('c') . PHP_EOL, LOCK_EX) !== false;
}

function installer_init_database($config, $adminUser, $adminPassword, $adminEmail)
{
    if (!extension_loaded('pdo_mysql')) {
        throw new RuntimeException('当前 PHP 未启用 pdo_mysql 扩展，请在宝塔 PHP 设置里开启。');
    }

    $charset = $config['charset'];
    $dsnWithoutDb = "mysql:host={$config['host']};port={$config['port']};charset={$charset}";
    $pdo = new PDO($dsnWithoutDb, $config['username'], $config['password'], array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ));

    $dbName = str_replace('`', '``', $config['database']);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `{$dbName}`");
    $pdo->exec("CREATE TABLE IF NOT EXISTS `jm_settings` (
        `name` varchar(100) NOT NULL,
        `value` longtext NULL,
        `updated_at` int unsigned NOT NULL DEFAULT 0,
        PRIMARY KEY (`name`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $pdo->exec("CREATE TABLE IF NOT EXISTS `jm_admins` (
        `id` int unsigned NOT NULL AUTO_INCREMENT,
        `username` varchar(80) NOT NULL,
        `password_hash` varchar(255) NOT NULL,
        `email` varchar(120) DEFAULT NULL,
        `created_at` int unsigned NOT NULL DEFAULT 0,
        PRIMARY KEY (`id`),
        UNIQUE KEY `idx_username` (`username`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $pdo->exec("CREATE TABLE IF NOT EXISTS `jm_cards` (
        `id` int unsigned NOT NULL AUTO_INCREMENT,
        `card_no` varchar(96) NOT NULL,
        `project_id` varchar(40) NOT NULL DEFAULT '',
        `status` varchar(20) NOT NULL DEFAULT 'available',
        `phone` varchar(32) NOT NULL DEFAULT '',
        `provider_uid` varchar(80) NOT NULL DEFAULT '',
        `provider_sid` varchar(80) NOT NULL DEFAULT '',
        `provider_host` varchar(120) NOT NULL DEFAULT '',
        `sms_code` varchar(40) NOT NULL DEFAULT '',
        `sms_text` text NULL,
        `expires_at` int unsigned NOT NULL DEFAULT 0,
        `used_at` int unsigned NOT NULL DEFAULT 0,
        `disabled_at` int unsigned NOT NULL DEFAULT 0,
        `created_at` int unsigned NOT NULL DEFAULT 0,
        `updated_at` int unsigned NOT NULL DEFAULT 0,
        PRIMARY KEY (`id`),
        UNIQUE KEY `idx_card_no` (`card_no`),
        KEY `idx_project_id` (`project_id`),
        KEY `idx_status` (`status`),
        KEY `idx_created_at` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM jm_admins WHERE username = ?');
    $stmt->execute([$adminUser]);
    if ((int) $stmt->fetch()['total'] === 0) {
        $insert = $pdo->prepare('INSERT INTO jm_admins (username, password_hash, email, created_at) VALUES (?, ?, ?, ?)');
        $insert->execute([$adminUser, password_hash($adminPassword, PASSWORD_DEFAULT), $adminEmail, time()]);
    }

    $setting = $pdo->prepare('REPLACE INTO jm_settings (name, value, updated_at) VALUES (?, ?, ?)');
    $setting->execute(['site_name', JMWEB_NAME, time()]);
    $setting->execute(['version', JMWEB_VERSION, time()]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$installed) {
    $host = trim(isset($_POST['host']) ? $_POST['host'] : '127.0.0.1');
    $port = (int) (isset($_POST['port']) ? $_POST['port'] : 3306);
    $database = trim(isset($_POST['database']) ? $_POST['database'] : 'starlume');
    $username = trim(isset($_POST['username']) ? $_POST['username'] : 'root');
    $password = (string) (isset($_POST['password']) ? $_POST['password'] : '');
    $adminUser = preg_replace('/[^a-zA-Z0-9_]/', '', isset($_POST['admin_user']) ? $_POST['admin_user'] : 'admin');
    $adminPassword = (string) (isset($_POST['admin_password']) ? $_POST['admin_password'] : '');
    $adminEmail = filter_var(trim(isset($_POST['admin_email']) ? $_POST['admin_email'] : 'admin@starlume.local'), FILTER_SANITIZE_EMAIL);

    if ($host === '' || $database === '' || $username === '' || $port <= 0) {
        $message = '请填写完整的数据库连接信息。';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $database)) {
        $message = '数据库名只能包含字母、数字和下划线。';
    } elseif (strlen($adminUser) < 3 || strlen($adminUser) > 20) {
        $message = '管理员用户名需为 3-20 位字母、数字或下划线。';
    } elseif (strlen($adminPassword) < 6) {
        $message = '管理员密码至少 6 位。';
    } elseif (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
        $message = '请填写正确的管理员邮箱。';
    } else {
        $config = array(
            'host' => $host,
            'port' => $port,
            'database' => $database,
            'username' => $username,
            'password' => $password,
            'charset' => 'utf8mb4',
        );

        try {
            installer_init_database($config, $adminUser, $adminPassword, $adminEmail);
            if (!installer_write_config($config)) {
                throw new RuntimeException('无法写入 config/database.php，请检查目录权限。');
            }
            if (!installer_create_lock()) {
                throw new RuntimeException('无法写入 data/install.lock，请检查目录权限。');
            }
            $_SESSION['jmweb_admin_user'] = $adminUser;
            $_SESSION['jmweb_admin'] = true;
            $success = true;
            $messageType = 'success';
            $message = '安装完成！现在可以进入网站后台。';
            $installed = true;
        } catch (Exception $e) {
            $message = '安装失败：' . $e->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= JMWEB_NAME ?> 安装向导</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="install-body">
    <main class="install-page">
        <section class="install-card">
            <div class="install-hero">
                <span class="brand-mark large">S</span>
                <h1><?= JMWEB_NAME ?> 安装向导</h1>
                <p>填写数据库信息后，系统会自动创建基础数据表、写入数据库配置，并生成安装锁。宝塔部署时请把运行目录设置为 <b>/public</b>。</p>
                <div class="install-tips">
                    <span>检测安装状态</span>
                    <span>写入数据库配置</span>
                    <span>创建管理员账号</span>
                    <span>支持后台自动更新</span>
                </div>
            </div>
            <div class="install-form-wrap">
                <?php if ($installed && !$message): ?>
                    <div class="done-box">
                        <h2>系统已经安装</h2>
                        <p>如果需要重新安装，请删除 <code>data/install.lock</code> 并清空或修改 <code>config/database.php</code>。</p>
                        <div class="hero-actions"><a class="btn primary" href="/admin/">进入后台</a><a class="btn ghost" href="/">返回首页</a></div>
                    </div>
                <?php elseif ($success): ?>
                    <div class="done-box">
                        <h2>安装成功</h2>
                        <p><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
                        <div class="hero-actions"><a class="btn primary" href="/admin/">进入后台</a><a class="btn ghost" href="/">访问首页</a></div>
                    </div>
                <?php else: ?>
                    <h2>填写安装信息</h2>
                    <p class="muted">请先在宝塔里创建 MySQL 数据库，也可以使用有建库权限的账号让程序自动创建。</p>
                    <?php if ($message): ?><div class="alert <?= $messageType ?>"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
                    <form method="post" class="install-form">
                        <h3><span>1</span>数据库信息</h3>
                        <div class="form-grid">
                            <label>数据库主机<input name="host" value="<?= installer_value('host', '127.0.0.1') ?>" required></label>
                            <label>端口<input name="port" type="number" value="<?= installer_value('port', '3306') ?>" required></label>
                            <label>数据库名<input name="database" value="<?= installer_value('database', 'starlume') ?>" required></label>
                            <label>数据库账号<input name="username" value="<?= installer_value('username', 'root') ?>" required></label>
                            <label class="wide">数据库密码<input name="password" type="password" value="<?= installer_value('password') ?>"></label>
                        </div>
                        <h3><span>2</span>管理员账号</h3>
                        <div class="form-grid">
                            <label>管理员用户名<input name="admin_user" value="<?= installer_value('admin_user', 'admin') ?>" required></label>
                            <label>管理员密码<input name="admin_password" type="password" placeholder="至少 6 位" required></label>
                            <label class="wide">管理员邮箱<input name="admin_email" type="email" value="<?= installer_value('admin_email', 'admin@starlume.local') ?>" required></label>
                        </div>
                        <button class="btn primary full" type="submit">开始安装</button>
                    </form>
                <?php endif; ?>
            </div>
        </section>
    </main>
</body>
</html>
