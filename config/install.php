<?php
/**
 * 安装状态检测
 */
function jmweb_config_path()
{
    return __DIR__ . '/database.php';
}

function jmweb_lock_path()
{
    return dirname(__DIR__) . '/data/install.lock';
}

function jmweb_is_installed()
{
    $configFile = jmweb_config_path();
    $lockFile = jmweb_lock_path();

    if (!is_file($configFile)) {
        return false;
    }

    $config = require $configFile;
    $installed = is_array($config)
        && !empty($config['host'])
        && !empty($config['database'])
        && isset($config['username']);

    if ($installed && !is_file($lockFile)) {
        $lockDir = dirname($lockFile);
        if (!is_dir($lockDir)) {
            mkdir($lockDir, 0755, true);
        }
        @file_put_contents($lockFile, date('Y-m-d H:i:s'));
    }

    return $installed;
}

function jmweb_require_installed($json = false)
{
    if (jmweb_is_installed()) {
        return;
    }

    if ($json) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(503);
        echo json_encode(array(
            'ok' => false,
            'installed' => false,
            'message' => '系统尚未安装，请先访问 install.php 完成安装。',
            'install_url' => '/install.php',
        ), JSON_UNESCAPED_UNICODE);
        exit;
    }

    header('Location: /install.php');
    exit;
}
