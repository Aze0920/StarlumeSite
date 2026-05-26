<?php
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/config/database_helper.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action !== 'login') {
    jmweb_require_installed(true);
}

if ($action === 'login') {
    jmweb_require_installed(true);
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    if (jmweb_check_admin($username, $password)) {
        $_SESSION['jmweb_admin'] = true;
        $_SESSION['jmweb_admin_user'] = $username;
        jmweb_log('管理员登录成功：' . $username);
        jmweb_json(['ok' => true, 'message' => '登录成功']);
    }
    jmweb_json(['ok' => false, 'message' => '账号或密码错误']);
}

if ($action === 'logout') {
    unset($_SESSION['jmweb_admin']);
    jmweb_json(['ok' => true, 'message' => '已退出登录']);
}

if ($action === 'update') {
    jmweb_require_admin();

    $script = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'update.php';
    if (!is_file($script)) {
        jmweb_json(['ok' => false, 'message' => '更新脚本不存在']);
    }

    $cmd = PHP_BINARY . ' ' . escapeshellarg($script) . ' 2>&1';
    $output = [];
    $code = 0;
    exec($cmd, $output, $code);

    jmweb_log('执行一键更新，退出码：' . $code);
    jmweb_json([
        'ok' => $code === 0,
        'message' => $code === 0 ? '更新完成' : '更新失败，请查看输出',
        'output' => implode("\n", $output),
    ]);
}

jmweb_json(['ok' => false, 'message' => '未知操作']);
