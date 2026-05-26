<?php
error_reporting(0);
ini_set('display_errors', 0);
ob_start();

require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/config/database_helper.php';

function jmweb_api_log_path()
{
    return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'update.log';
}

function jmweb_read_update_log()
{
    $file = jmweb_api_log_path();
    if (!is_file($file)) {
        return '';
    }
    $content = file_get_contents($file);
    if (strlen($content) > 20000) {
        return substr($content, -20000);
    }
    return $content;
}

function jmweb_write_update_log($message)
{
    $file = jmweb_api_log_path();
    $dir = dirname($file);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    file_put_contents($file, '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL, FILE_APPEND);
}

function jmweb_find_php_cli()
{
    $candidates = array();
    if (defined('PHP_BINDIR') && PHP_BINDIR) {
        $candidates[] = PHP_BINDIR . DIRECTORY_SEPARATOR . 'php';
        $candidates[] = dirname(PHP_BINDIR) . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'php';
    }
    $candidates[] = '/www/server/php/80/bin/php';
    $candidates[] = '/www/server/php/81/bin/php';
    $candidates[] = '/www/server/php/82/bin/php';
    $candidates[] = '/www/server/php/74/bin/php';
    $candidates[] = '/usr/bin/php';
    $candidates[] = '/usr/local/bin/php';
    $candidates[] = 'php';

    foreach ($candidates as $candidate) {
        $output = array();
        $code = 0;
        @exec(escapeshellarg($candidate) . ' -v 2>&1', $output, $code);
        $text = implode("\n", $output);
        if ($code === 0 && stripos($text, 'PHP') !== false && stripos($text, 'fpm-fcgi') === false) {
            return $candidate;
        }
    }

    return '';
}

function jmweb_api_json($data)
{
    $buffer = ob_get_clean();
    if ($buffer) {
        $data['php_output'] = $buffer;
        if (empty($data['output'])) {
            $data['output'] = $buffer;
        }
    }
    jmweb_json($data);
}

register_shutdown_function(function () {
    $error = error_get_last();
    if ($error && in_array($error['type'], array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR))) {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array(
            'ok' => false,
            'message' => 'PHP 致命错误：' . $error['message'],
            'output' => '文件：' . $error['file'] . "\n行号：" . $error['line'] . "\n错误：" . $error['message'],
            'log_path' => 'data/update.log',
            'log' => jmweb_read_update_log(),
        ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
});

set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

try {
    function jmweb_fetch_url($url)
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_USERAGENT, 'JmWeb-Updater');
            $body = curl_exec($ch);
            curl_close($ch);
            return $body;
        }

        $context = stream_context_create(array(
            'http' => array(
                'timeout' => 12,
                'header' => "User-Agent: JmWeb-Updater\r\n",
            ),
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
            ),
        ));
        return @file_get_contents($url, false, $context);
    }

    function jmweb_fetch_github_version_info()
    {
        $apiUrl = 'https://api.github.com/repos/Aze0920/StarlumeSite/contents/version.json?ref=main&t=' . time();
        $raw = jmweb_fetch_url($apiUrl);
        if ($raw !== false && trim($raw) !== '') {
            $api = json_decode($raw, true);
            if (is_array($api) && !empty($api['content'])) {
                $content = base64_decode(str_replace(array("\r", "\n", ' '), '', $api['content']));
                $json = json_decode($content, true);
                if (is_array($json) && !empty($json['version'])) {
                    $json['_source'] = 'GitHub API';
                    return $json;
                }
            }
        }

        $raw = jmweb_fetch_url(JMWEB_UPDATE_INFO_URL . '?t=' . time());
        if ($raw === false || trim($raw) === '') {
            return false;
        }
        $json = json_decode($raw, true);
        if (is_array($json)) {
            $json['_source'] = 'GitHub raw';
        }
        return $json;
    }

    function jmweb_read_local_version_file()
    {
        $file = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'version.json';
        if (!is_file($file)) {
            return '';
        }
        $json = json_decode(file_get_contents($file), true);
        if (!is_array($json) || empty($json['version'])) {
            return '';
        }
        return (string) $json['version'];
    }

    function jmweb_read_local_app_version_file()
    {
        $file = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'app.php';
        if (!is_file($file)) {
            return '';
        }
        $content = file_get_contents($file);
        if (preg_match("/JMWEB_VERSION'\s*,\s*'([^']+)'/", $content, $match)) {
            return (string) $match[1];
        }
        return '';
    }

    $action = isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : '');

    if ($action !== 'login') {
        jmweb_require_installed(true);
    }

    if ($action === 'login') {
        jmweb_require_installed(true);
        $username = isset($_POST['username']) ? trim($_POST['username']) : '';
        $password = isset($_POST['password']) ? trim($_POST['password']) : '';
        if (jmweb_check_admin($username, $password)) {
            $_SESSION['jmweb_admin'] = true;
            $_SESSION['jmweb_admin_user'] = $username;
            jmweb_log('管理员登录成功：' . $username);
            jmweb_api_json(array('ok' => true, 'message' => '登录成功'));
        }
        jmweb_api_json(array('ok' => false, 'message' => '账号或密码错误'));
    }

    if ($action === 'logout') {
        unset($_SESSION['jmweb_admin']);
        jmweb_api_json(array('ok' => true, 'message' => '已退出登录'));
    }

    if ($action === 'check_update') {
        jmweb_require_admin();

        $remote = jmweb_fetch_github_version_info();
        if ($remote === false) {
            jmweb_api_json(array(
                'ok' => false,
                'message' => '检查失败：无法读取远程版本信息，请确认服务器能访问 GitHub。',
                'current_version' => JMWEB_VERSION,
                'output' => '远程版本地址：' . JMWEB_UPDATE_INFO_URL,
            ));
        }

        if (!is_array($remote) || empty($remote['version'])) {
            jmweb_api_json(array(
                'ok' => false,
                'message' => '检查失败：远程版本信息格式不正确。',
                'current_version' => JMWEB_VERSION,
                'output' => json_encode($remote, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            ));
        }

        $remoteVersion = (string) $remote['version'];
        $hasUpdate = version_compare($remoteVersion, JMWEB_VERSION, '>');
        jmweb_api_json(array(
            'ok' => true,
            'has_update' => $hasUpdate,
            'message' => $hasUpdate ? '发现新版本，可以更新。' : '当前已经是最新版本。',
            'current_version' => JMWEB_VERSION,
            'remote_version' => $remoteVersion,
            'release_date' => isset($remote['release_date']) ? $remote['release_date'] : '',
            'description' => isset($remote['description']) ? $remote['description'] : '',
            'repo' => isset($remote['repo']) ? $remote['repo'] : JMWEB_UPDATE_REPO,
            'source' => isset($remote['_source']) ? $remote['_source'] : '',
        ));
    }

    if ($action === 'update') {
        jmweb_require_admin();

        $script = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'update.php';
        $logPath = jmweb_api_log_path();
        file_put_contents($logPath, '');
        jmweb_write_update_log('Start update request.');
        jmweb_write_update_log('Current version: ' . JMWEB_VERSION);
        jmweb_write_update_log('Repo: ' . JMWEB_UPDATE_REPO);
        jmweb_write_update_log('Script: ' . $script);

        if (!is_file($script)) {
            jmweb_write_update_log('Update script not found.');
            jmweb_api_json(array(
                'ok' => false,
                'message' => '更新脚本不存在',
                'output' => jmweb_read_update_log(),
                'log_path' => 'data/update.log',
                'log' => jmweb_read_update_log(),
            ));
        }

        if (!function_exists('exec')) {
            jmweb_write_update_log('PHP exec function is disabled.');
            jmweb_api_json(array(
                'ok' => false,
                'message' => '更新失败：服务器禁用了 exec 函数，无法在后台执行 Git 更新。',
                'output' => jmweb_read_update_log(),
                'log_path' => 'data/update.log',
                'log' => jmweb_read_update_log(),
            ));
        }

        $php = jmweb_find_php_cli();
        if ($php === '') {
            jmweb_write_update_log('No available PHP CLI found. PHP_BINARY=' . (defined('PHP_BINARY') ? PHP_BINARY : 'unknown') . ', PHP_BINDIR=' . (defined('PHP_BINDIR') ? PHP_BINDIR : 'unknown'));
            jmweb_api_json(array(
                'ok' => false,
                'message' => '更新失败：未找到可用的 PHP CLI，当前服务器可能只暴露了 php-fpm。',
                'output' => jmweb_read_update_log(),
                'log_path' => 'data/update.log',
                'log' => jmweb_read_update_log(),
            ));
        }
        jmweb_write_update_log('PHP CLI: ' . $php);
        $cmd = escapeshellarg($php) . ' ' . escapeshellarg($script) . ' 2>&1';
        jmweb_write_update_log('Command: ' . $cmd);

        $output = array();
        $code = 0;
        exec($cmd, $output, $code);
        foreach ($output as $line) {
            jmweb_write_update_log($line);
        }
        jmweb_write_update_log('Exit code: ' . $code);

        $log = jmweb_read_update_log();
        $actualVersionJson = jmweb_read_local_version_file();
        $actualVersionApp = jmweb_read_local_app_version_file();
        jmweb_write_update_log('Actual local version.json after update: ' . ($actualVersionJson === '' ? 'unknown' : $actualVersionJson));
        jmweb_write_update_log('Actual local config/app.php after update: ' . ($actualVersionApp === '' ? 'unknown' : $actualVersionApp));
        $log = jmweb_read_update_log();
        jmweb_log('执行一键更新，退出码：' . $code);
        jmweb_api_json(array(
            'ok' => $code === 0,
            'message' => $code === 0 ? '更新完成，当前文件版本：' . ($actualVersionApp !== '' ? $actualVersionApp : $actualVersionJson) : '更新失败，请查看日志',
            'current_version' => $actualVersionApp !== '' ? $actualVersionApp : $actualVersionJson,
            'version_json' => $actualVersionJson,
            'version_app' => $actualVersionApp,
            'output' => $log,
            'log_path' => 'data/update.log',
            'log' => $log,
        ));
    }

    jmweb_api_json(array('ok' => false, 'message' => '未知操作'));
} catch (Exception $e) {
    jmweb_log('接口错误：' . $e->getMessage());
    jmweb_write_update_log('API error: ' . $e->getMessage());
    jmweb_api_json(array(
        'ok' => false,
        'message' => '服务器错误：' . $e->getMessage(),
        'output' => jmweb_read_update_log(),
        'log_path' => 'data/update.log',
        'log' => jmweb_read_update_log(),
    ));
}
