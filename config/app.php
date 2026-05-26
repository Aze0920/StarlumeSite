<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/install.php';

define('JMWEB_NAME', '豪猪接码');
define('JMWEB_REPO_NAME', 'StarlumeSite');
define('JMWEB_VERSION', '1.0.31');
define('JMWEB_UPDATE_REPO', 'https://github.com/Aze0920/StarlumeSite.git');
define('JMWEB_UPDATE_INFO_URL', 'https://raw.githubusercontent.com/Aze0920/StarlumeSite/main/version.json');
define('JMWEB_SITE_DIR', dirname(__DIR__));
define('JMWEB_UPDATE_WORKDIR', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'update-source');

function jmweb_is_admin()
{
    return !empty($_SESSION['jmweb_admin']);
}

function jmweb_json($data)
{
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function jmweb_require_admin()
{
    if (!jmweb_is_admin()) {
        jmweb_json(array('ok' => false, 'message' => '请先登录后台'));
    }
}

function jmweb_data_path($file)
{
    return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . $file;
}

function jmweb_log($message)
{
    $dir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'logs';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    file_put_contents($dir . DIRECTORY_SEPARATOR . 'system.log', '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL, FILE_APPEND);
}
