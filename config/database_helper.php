<?php
require_once dirname(__DIR__) . '/config/app.php';

function jmweb_db_config()
{
    $file = dirname(__DIR__) . '/config/database.php';
    if (!is_file($file)) {
        return array();
    }
    $config = require $file;
    return is_array($config) ? $config : array();
}

function jmweb_pdo()
{
    $config = jmweb_db_config();
    $charset = isset($config['charset']) ? $config['charset'] : 'utf8mb4';
    $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$charset}";
    return new PDO($dsn, $config['username'], $config['password'], array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ));
}

function jmweb_check_admin($username, $password)
{
    if (!jmweb_is_installed()) {
        return false;
    }

    try {
        $pdo = jmweb_pdo();
        $stmt = $pdo->prepare('SELECT username, password_hash FROM jm_admins WHERE username = ? LIMIT 1');
        $stmt->execute(array($username));
        $admin = $stmt->fetch();
        return $admin && password_verify($password, $admin['password_hash']);
    } catch (Exception $e) {
        jmweb_log('管理员登录检测失败：' . $e->getMessage());
        return false;
    }
}

function jmweb_ensure_cards_table()
{
    $pdo = jmweb_pdo();
    $pdo->exec("CREATE TABLE IF NOT EXISTS `jm_cards` (
        `id` int unsigned NOT NULL AUTO_INCREMENT,
        `card_no` varchar(64) NOT NULL,
        `status` varchar(20) NOT NULL DEFAULT 'available',
        `used_at` int unsigned NOT NULL DEFAULT 0,
        `disabled_at` int unsigned NOT NULL DEFAULT 0,
        `created_at` int unsigned NOT NULL DEFAULT 0,
        `updated_at` int unsigned NOT NULL DEFAULT 0,
        PRIMARY KEY (`id`),
        UNIQUE KEY `idx_card_no` (`card_no`),
        KEY `idx_status` (`status`),
        KEY `idx_created_at` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    return $pdo;
}

function jmweb_card_status_label($status)
{
    if ($status === 'used') {
        return '已用';
    }
    if ($status === 'disabled') {
        return '禁用';
    }
    return '可用';
}
