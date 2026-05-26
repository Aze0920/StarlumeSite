<?php
session_start();

require_once __DIR__ . '/install.php';

define('JMWEB_NAME', '星澜云站');
define('JMWEB_REPO_NAME', 'StarlumeSite');
define('JMWEB_VERSION', '1.0.0');
define('JMWEB_ADMIN_USER', 'admin');
define('JMWEB_ADMIN_PASSWORD', '123456');
define('JMWEB_UPDATE_REPO', 'https://github.com/Aze0920/StarlumeSite.git');
define('JMWEB_UPDATE_INFO_URL', 'https://raw.githubusercontent.com/Aze0920/StarlumeSite/main/version.json');
define('JMWEB_SITE_DIR', dirname(__DIR__));
define('JMWEB_UPDATE_WORKDIR', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'update-source');

function jmweb_is_admin(): bool
{
    return !empty($_SESSION['jmweb_admin']);
}

function jmweb_json(array $data): void
{
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function jmweb_require_admin(): void
{
    if (!jmweb_is_admin()) {
        jmweb_json(['ok' => false, 'message' => '请先登录后台']);
    }
}

function jmweb_data_path(string $file): string
{
    return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . $file;
}

function jmweb_log(string $message): void
{
    $dir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'logs';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    file_put_contents($dir . DIRECTORY_SEPARATOR . 'system.log', '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL, FILE_APPEND);
}
