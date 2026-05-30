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

function jmweb_table_has_column($pdo, $table, $column)
{
    $table = str_replace('`', '``', $table);
    $column = str_replace("'", "''", $column);
    $stmt = $pdo->query("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");
    return (bool) $stmt->fetch();
}

function jmweb_ensure_cards_table()
{
    $pdo = jmweb_pdo();
    $pdo->exec("CREATE TABLE IF NOT EXISTS `jm_cards` (
        `id` int unsigned NOT NULL AUTO_INCREMENT,
        `card_no` varchar(96) NOT NULL,
        `project_id` varchar(40) NOT NULL DEFAULT '',
        `status` varchar(20) NOT NULL DEFAULT 'available',
        `phone` varchar(32) NOT NULL DEFAULT '',
        `provider_uid` varchar(80) NOT NULL DEFAULT '',
        `provider_sid` varchar(80) NOT NULL DEFAULT '',
        `provider_host` varchar(120) NOT NULL DEFAULT '',
        `provider_token` varchar(255) NOT NULL DEFAULT '',
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

    $columns = array(
        'project_id' => "ALTER TABLE `jm_cards` ADD COLUMN `project_id` varchar(40) NOT NULL DEFAULT '' AFTER `card_no`",
        'phone' => "ALTER TABLE `jm_cards` ADD COLUMN `phone` varchar(32) NOT NULL DEFAULT '' AFTER `status`",
        'provider_uid' => "ALTER TABLE `jm_cards` ADD COLUMN `provider_uid` varchar(80) NOT NULL DEFAULT '' AFTER `phone`",
        'provider_sid' => "ALTER TABLE `jm_cards` ADD COLUMN `provider_sid` varchar(80) NOT NULL DEFAULT '' AFTER `provider_uid`",
        'provider_host' => "ALTER TABLE `jm_cards` ADD COLUMN `provider_host` varchar(120) NOT NULL DEFAULT '' AFTER `provider_sid`",
        'provider_token' => "ALTER TABLE `jm_cards` ADD COLUMN `provider_token` varchar(255) NOT NULL DEFAULT '' AFTER `provider_host`",
        'sms_code' => "ALTER TABLE `jm_cards` ADD COLUMN `sms_code` varchar(40) NOT NULL DEFAULT '' AFTER `provider_token`",
        'sms_text' => "ALTER TABLE `jm_cards` ADD COLUMN `sms_text` text NULL AFTER `sms_code`",
        'expires_at' => "ALTER TABLE `jm_cards` ADD COLUMN `expires_at` int unsigned NOT NULL DEFAULT 0 AFTER `sms_text`",
    );
    foreach ($columns as $column => $sql) {
        if (!jmweb_table_has_column($pdo, 'jm_cards', $column)) {
            $pdo->exec($sql);
        }
    }

    try {
        $pdo->exec('ALTER TABLE `jm_cards` MODIFY COLUMN `card_no` varchar(96) NOT NULL');
    } catch (Exception $e) {}
    try {
        $pdo->exec('ALTER TABLE `jm_cards` ADD KEY `idx_project_id` (`project_id`)');
    } catch (Exception $e) {}

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

function jmweb_phone_digits_only($phone)
{
    $phone = trim((string) $phone);
    if ($phone === '') {
        return '';
    }
    $phone = preg_replace('/[^\d+]/', '', $phone);
    if (strpos($phone, '+') === 0) {
        $phone = substr($phone, 1);
    } elseif (strpos($phone, '00') === 0) {
        $phone = substr($phone, 2);
    }
    return preg_replace('/\D/', '', $phone);
}

function jmweb_phone_country_codes()
{
    static $codes = null;
    if ($codes !== null) {
        return $codes;
    }
    $codes = array(
        '880', '886', '853', '852', '855', '856', '850', '976', '977', '975', '974', '973', '972', '971', '968', '967', '966', '965', '964', '963', '962', '961', '960',
        '423', '421', '420', '389', '387', '386', '385', '383', '382', '381', '380', '378', '377', '376', '375', '374', '373', '372', '371', '370', '359', '358', '357', '356', '355', '354', '353', '352', '351', '350',
        '998', '996', '995', '994', '993', '992',
        '687', '686', '685', '684', '683', '682', '681', '680', '679', '678', '677', '676', '675', '674', '673', '672', '670', '692', '691', '690', '689', '688',
        '599', '598', '597', '596', '595', '594', '593', '592', '591', '590', '509', '508', '507', '506', '505', '504', '503', '502', '501', '500',
        '91', '90', '86', '84', '82', '81', '66', '65', '64', '63', '62', '61', '60', '58', '57', '56', '55', '54', '53', '52', '51', '49', '48', '47', '46', '45', '44', '43', '41', '40', '39', '36', '34', '33', '32', '31', '30', '27', '20',
        '7', '1',
    );
    usort($codes, function ($a, $b) {
        return strlen($b) - strlen($a);
    });
    return $codes;
}

function jmweb_detect_phone_country_code($phone)
{
    $digits = jmweb_phone_digits_only($phone);
    if ($digits === '') {
        return '';
    }
    foreach (jmweb_phone_country_codes() as $code) {
        if (strpos($digits, $code) === 0) {
            $local = substr($digits, strlen($code));
            if (strlen($local) >= 7 && strlen($local) <= 15) {
                return $code;
            }
        }
    }
    return '';
}

function jmweb_phone_without_country_code($phone)
{
    $digits = jmweb_phone_digits_only($phone);
    if ($digits === '') {
        return '';
    }
    $countryCode = jmweb_detect_phone_country_code($phone);
    if ($countryCode !== '') {
        return substr($digits, strlen($countryCode));
    }
    return $digits;
}
