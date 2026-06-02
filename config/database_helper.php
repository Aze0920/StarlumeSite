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
        `phone_country` varchar(8) NOT NULL DEFAULT '',
        `provider_uid` varchar(80) NOT NULL DEFAULT '',
        `provider_sid` varchar(80) NOT NULL DEFAULT '',
        `provider_host` varchar(120) NOT NULL DEFAULT '',
        `provider_token` varchar(1000) NOT NULL DEFAULT '',
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
        'phone_country' => "ALTER TABLE `jm_cards` ADD COLUMN `phone_country` varchar(8) NOT NULL DEFAULT '' AFTER `phone`",
        'provider_uid' => "ALTER TABLE `jm_cards` ADD COLUMN `provider_uid` varchar(80) NOT NULL DEFAULT '' AFTER `phone_country`",
        'provider_sid' => "ALTER TABLE `jm_cards` ADD COLUMN `provider_sid` varchar(80) NOT NULL DEFAULT '' AFTER `provider_uid`",
        'provider_host' => "ALTER TABLE `jm_cards` ADD COLUMN `provider_host` varchar(120) NOT NULL DEFAULT '' AFTER `provider_sid`",
        'provider_token' => "ALTER TABLE `jm_cards` ADD COLUMN `provider_token` varchar(1000) NOT NULL DEFAULT '' AFTER `provider_host`",
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
        $pdo->exec('ALTER TABLE `jm_cards` MODIFY COLUMN `provider_token` varchar(1000) NOT NULL DEFAULT \'\'');
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

function jmweb_is_china_mobile_local($digits)
{
    return (bool) preg_match('/^1[3-9]\d{9}$/', (string) $digits);
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
    if (jmweb_is_china_mobile_local($digits)) {
        return '86';
    }
    if (strlen($digits) === 13 && substr($digits, 0, 2) === '86' && jmweb_is_china_mobile_local(substr($digits, 2))) {
        return '86';
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

function jmweb_phone_without_country_code($phone, $countryCode = '')
{
    $digits = jmweb_phone_digits_only($phone);
    if ($digits === '') {
        return '';
    }
    $countryCode = preg_replace('/\D/', '', (string) $countryCode);
    if ($countryCode !== '' && strpos($digits, $countryCode) === 0) {
        return substr($digits, strlen($countryCode));
    }
    if (jmweb_is_china_mobile_local($digits)) {
        return $digits;
    }
    $countryCode = jmweb_detect_phone_country_code($phone);
    if ($countryCode !== '') {
        return substr($digits, strlen($countryCode));
    }
    return $digits;
}

function jmweb_phone_public_parts($phone, $countryCode = '')
{
    $phone = trim((string) $phone);
    $countryCode = preg_replace('/\D/', '', (string) $countryCode);
    if ($phone === '') {
        return array('phone' => '', 'phone_country' => $countryCode, 'phone_display' => '');
    }
    if ($countryCode === '') {
        $countryCode = jmweb_detect_phone_country_code($phone);
    }
    $local = jmweb_phone_without_country_code($phone, $countryCode);
    $display = $countryCode !== '' ? ('+' . $countryCode . ' ' . $local) : $local;
    return array(
        'phone' => $local,
        'phone_country' => $countryCode,
        'phone_display' => $display,
    );
}

function jmweb_country_dial_code_from_text($value)
{
    $value = trim((string) $value);
    if ($value === '') {
        return '';
    }
    $key = strtoupper(preg_replace('/[^A-Za-z]/', '', $value));
    if ($key === '') {
        return '';
    }
    $map = array(
        'US' => '1', 'USA' => '1', 'UNITEDSTATES' => '1', 'AMERICA' => '1',
        'CA' => '1', 'CANADA' => '1',
        'CN' => '86', 'CHINA' => '86',
        'TH' => '66', 'THAILAND' => '66',
        'RU' => '7', 'RUSSIA' => '7', 'RUSSIANFEDERATION' => '7',
        'GB' => '44', 'UK' => '44', 'UNITEDKINGDOM' => '44', 'ENGLAND' => '44',
        'HK' => '852', 'HONGKONG' => '852',
        'MO' => '853', 'MACAO' => '853', 'MACAU' => '853',
        'TW' => '886', 'TAIWAN' => '886',
        'SG' => '65', 'SINGAPORE' => '65',
        'MY' => '60', 'MALAYSIA' => '60',
        'VN' => '84', 'VIETNAM' => '84',
        'ID' => '62', 'INDONESIA' => '62',
        'PH' => '63', 'PHILIPPINES' => '63',
        'JP' => '81', 'JAPAN' => '81',
        'KR' => '82', 'KOREA' => '82', 'SOUTHKOREA' => '82',
        'IN' => '91', 'INDIA' => '91',
        'BR' => '55', 'BRAZIL' => '55',
        'MX' => '52', 'MEXICO' => '52',
        'AU' => '61', 'AUSTRALIA' => '61',
        'DE' => '49', 'GERMANY' => '49',
        'FR' => '33', 'FRANCE' => '33',
        'IT' => '39', 'ITALY' => '39',
        'ES' => '34', 'SPAIN' => '34',
        'NL' => '31', 'NETHERLANDS' => '31',
        'TR' => '90', 'TURKEY' => '90',
    );
    return isset($map[$key]) ? $map[$key] : '';
}

function jmweb_phone_country_from_payload($payload)
{
    if (!is_array($payload)) {
        return '';
    }
    $dialKeys = array('country_code', 'countryCode', 'phone_country', 'phoneCountry', 'area_code', 'areaCode', 'dial_code', 'dialCode', 'country_prefix', 'countryPrefix', 'prefix', 'cc');
    foreach ($dialKeys as $key) {
        if (isset($payload[$key]) && trim((string) $payload[$key]) !== '') {
            $countryCode = preg_replace('/\D/', '', (string) $payload[$key]);
            if ($countryCode !== '') {
                return $countryCode;
            }
        }
    }
    $countryKeys = array('country_iso', 'countryIso', 'country_code_iso', 'countryCodeIso', 'country', 'country_name', 'countryName', 'country_name_en', 'countryNameEn', 'name_en', 'nameEn', 'code');
    foreach ($countryKeys as $key) {
        if (isset($payload[$key]) && trim((string) $payload[$key]) !== '') {
            $countryCode = jmweb_country_dial_code_from_text($payload[$key]);
            if ($countryCode !== '') {
                return $countryCode;
            }
        }
    }
    return '';
}
