<?php
error_reporting(0);
ini_set('display_errors', 0);
ob_start();

require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/config/database_helper.php';
require_once dirname(__DIR__) . '/config/settings.php';

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

    function jmweb_random_card_part($length)
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $text = '';
        $max = strlen($chars) - 1;
        for ($i = 0; $i < $length; $i++) {
            if (function_exists('random_int')) {
                $text .= $chars[random_int(0, $max)];
            } else {
                $text .= $chars[mt_rand(0, $max)];
            }
        }
        return $text;
    }

    function jmweb_generate_card_no($projectId)
    {
        return 'HZ-' . $projectId . '-' . jmweb_random_card_part(4) . '-' . jmweb_random_card_part(4) . '-' . jmweb_random_card_part(4);
    }

    function jmweb_clean_project_id($projectId)
    {
        $projectId = trim((string) $projectId);
        return preg_match('/^[0-9]{1,20}$/', $projectId) ? $projectId : '';
    }

    function jmweb_haozhu_hosts($settings)
    {
        $raw = isset($settings['haozhu_api_hosts']) ? (string) $settings['haozhu_api_hosts'] : '';
        $raw = str_replace(array("\r\n", "\r", ',', '，', ';', '；', '|'), "\n", $raw);
        $hosts = array();
        foreach (explode("\n", $raw) as $host) {
            $host = trim($host);
            if ($host === '') {
                continue;
            }
            $host = preg_replace('#^https?://#i', '', $host);
            $host = preg_replace('#/.*$#', '', $host);
            if ($host !== '' && !in_array($host, $hosts, true)) {
                $hosts[] = $host;
            }
        }
        return empty($hosts) ? array('api.haozhuma.com', 'api.haozhuyun.com') : $hosts;
    }

    function jmweb_haozhu_url($host, $params)
    {
        return 'https://' . $host . '/sms/?' . http_build_query($params);
    }

    function jmweb_haozhu_json_request($host, $params)
    {
        $url = jmweb_haozhu_url($host, $params);
        $raw = jmweb_fetch_url($url);
        $json = $raw !== false ? json_decode($raw, true) : null;
        return array($url, $raw, is_array($json) ? $json : array());
    }

    function jmweb_haozhu_login($settings)
    {
        $account = isset($settings['haozhu_api_account']) ? trim((string) $settings['haozhu_api_account']) : '';
        $password = isset($settings['haozhu_api_password']) ? trim((string) $settings['haozhu_api_password']) : '';
        if ($account === '' || $password === '') {
            return array('ok' => false, 'message' => '请先在基本设置填写豪猪码 API 账号和密码。');
        }

        $lastMessage = '';
        foreach (jmweb_haozhu_hosts($settings) as $host) {
            list($url, $raw, $json) = jmweb_haozhu_json_request($host, array(
                'api' => 'login',
                'user' => $account,
                'pass' => $password,
            ));
            if (isset($json['code']) && (string) $json['code'] === '0' && !empty($json['token'])) {
                return array('ok' => true, 'token' => (string) $json['token'], 'host' => $host, 'message' => '登录成功');
            }
            $lastMessage = isset($json['msg']) ? (string) $json['msg'] : ($raw ? (string) $raw : '接口无响应');
        }
        return array('ok' => false, 'message' => '豪猪码登录失败：' . $lastMessage);
    }

    function jmweb_haozhu_release_phone($settings, $host, $token, $projectId, $phone)
    {
        $releaseApi = isset($settings['haozhu_release_api']) ? trim((string) $settings['haozhu_release_api']) : '';
        if ($releaseApi === '' || $phone === '') {
            return array('ok' => true, 'skipped' => true, 'message' => '未配置释放接口，已跳过释放。');
        }
        list($url, $raw, $json) = jmweb_haozhu_json_request($host, array(
            'api' => $releaseApi,
            'token' => $token,
            'sid' => $projectId,
            'phone' => $phone,
        ));
        $ok = isset($json['code']) && (string) $json['code'] === '0';
        return array('ok' => $ok, 'skipped' => false, 'message' => $ok ? '释放请求已执行。' : ('释放接口返回：' . (isset($json['msg']) ? $json['msg'] : $raw)));
    }

    function jmweb_haozhu_check_project($projectId)
    {
        $settings = jmweb_read_settings();
        $login = jmweb_haozhu_login($settings);
        if (empty($login['ok'])) {
            return $login;
        }
        list($url, $raw, $json) = jmweb_haozhu_json_request($login['host'], array(
            'api' => 'getPhone',
            'token' => $login['token'],
            'sid' => $projectId,
        ));
        if (!isset($json['code']) || (string) $json['code'] !== '0' || empty($json['phone'])) {
            return array('ok' => false, 'message' => '项目ID检测失败：' . (isset($json['msg']) ? $json['msg'] : ($raw ? $raw : '取号接口无响应')), 'host' => $login['host']);
        }
        $release = jmweb_haozhu_release_phone($settings, $login['host'], $login['token'], $projectId, (string) $json['phone']);
        return array(
            'ok' => true,
            'message' => '项目ID可用，测试取到手机号 ' . $json['phone'] . '。' . (empty($release['skipped']) ? $release['message'] : ' 未配置释放接口，未执行释放。'),
            'host' => $login['host'],
            'phone' => (string) $json['phone'],
            'release' => $release,
        );
    }

    function jmweb_card_allowed_limit($limit)
    {
        $allowed = array(10, 50, 100, 500, 1000, 5000, 10000);
        $limit = (int) $limit;
        return in_array($limit, $allowed, true) ? $limit : 10;
    }

    function jmweb_card_clean_statuses($statuses)
    {
        if (!is_array($statuses)) {
            $statuses = $statuses === '' ? array() : explode(',', (string) $statuses);
        }
        $allowed = array('available', 'used', 'disabled');
        $clean = array();
        foreach ($statuses as $status) {
            $status = trim((string) $status);
            if (in_array($status, $allowed, true) && !in_array($status, $clean, true)) {
                $clean[] = $status;
            }
        }
        return $clean;
    }

    function jmweb_card_ids_from_request()
    {
        $raw = isset($_POST['ids']) ? $_POST['ids'] : '';
        if (is_array($raw)) {
            $items = $raw;
        } else {
            $items = explode(',', (string) $raw);
        }
        $ids = array();
        foreach ($items as $item) {
            $id = (int) $item;
            if ($id > 0 && !in_array($id, $ids, true)) {
                $ids[] = $id;
            }
        }
        return $ids;
    }

    function jmweb_card_rows($rows)
    {
        $result = array();
        foreach ($rows as $row) {
            $row['status_label'] = jmweb_card_status_label($row['status']);
            $row['created_at_text'] = !empty($row['created_at']) ? date('Y-m-d H:i', (int) $row['created_at']) : '-';
            $row['used_at_text'] = !empty($row['used_at']) ? date('Y-m-d H:i', (int) $row['used_at']) : '-';
            $row['disabled_at_text'] = !empty($row['disabled_at']) ? date('Y-m-d H:i', (int) $row['disabled_at']) : '-';
            $result[] = $row;
        }
        return $result;
    }

    function jmweb_clean_card_no($code)
    {
        $code = strtoupper(trim((string) $code));
        $code = preg_replace('/[^A-Z0-9\-]/', '', $code);
        return $code;
    }

    function jmweb_public_activation_payload($card, $state, $code, $sms)
    {
        $now = time();
        $expireAt = !empty($card['expires_at']) ? (int) $card['expires_at'] : ($now + 240);
        return array(
            'card_id' => (int) $card['id'],
            'card_no' => $card['card_no'],
            'phone' => isset($card['phone']) ? $card['phone'] : '',
            'state' => $state,
            'code' => $code,
            'sms' => $sms,
            'expires_at' => $expireAt,
            'remain_seconds' => max(0, $expireAt - $now),
        );
    }

    function jmweb_haozhu_get_message($settings, $host, $token, $projectId, $phone)
    {
        list($url, $raw, $json) = jmweb_haozhu_json_request($host, array(
            'api' => 'getMessage',
            'token' => $token,
            'sid' => $projectId,
            'phone' => $phone,
        ));
        if (isset($json['code']) && (string) $json['code'] === '0' && (!empty($json['yzm']) || !empty($json['sms']))) {
            return array('ok' => true, 'code' => isset($json['yzm']) ? (string) $json['yzm'] : '', 'sms' => isset($json['sms']) ? (string) $json['sms'] : '', 'message' => isset($json['msg']) ? (string) $json['msg'] : '成功', 'raw' => $raw);
        }
        return array('ok' => false, 'code' => '', 'sms' => '', 'message' => isset($json['msg']) ? (string) $json['msg'] : ($raw ? (string) $raw : '暂未收到验证码'), 'raw' => $raw);
    }

    $action = isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : '');

    if ($action !== 'login' && !in_array($action, array('redeem_card', 'poll_code', 'cancel_activation'), true)) {
        jmweb_require_installed(true);
    }

    if ($action === 'redeem_card') {
        jmweb_require_installed(true);
        $code = jmweb_clean_card_no(isset($_POST['code']) ? $_POST['code'] : '');
        if ($code === '') {
            jmweb_api_json(array('ok' => false, 'message' => '请输入兑换码。'));
        }
        $pdo = jmweb_ensure_cards_table();
        $stmt = $pdo->prepare('SELECT * FROM jm_cards WHERE card_no = ? LIMIT 1');
        $stmt->execute(array($code));
        $card = $stmt->fetch();
        if (!$card) {
            jmweb_api_json(array('ok' => false, 'message' => '兑换码不存在。'));
        }
        if ($card['status'] === 'used') {
            jmweb_api_json(array('ok' => false, 'message' => '兑换码已使用。'));
        }
        if ($card['status'] === 'disabled') {
            jmweb_api_json(array('ok' => false, 'message' => '兑换码已禁用。'));
        }
        $now = time();
        if (!empty($card['phone']) && !empty($card['expires_at']) && (int) $card['expires_at'] > $now) {
            jmweb_api_json(array('ok' => true, 'message' => '手机号已获取，正在等待验证码。', 'activation' => jmweb_public_activation_payload($card, '等待验证码', '', '')));
        }

        $settings = jmweb_read_settings();
        $login = jmweb_haozhu_login($settings);
        if (empty($login['ok'])) {
            jmweb_api_json(array('ok' => false, 'message' => $login['message']));
        }
        list($url, $raw, $json) = jmweb_haozhu_json_request($login['host'], array(
            'api' => 'getPhone',
            'token' => $login['token'],
            'sid' => $card['project_id'],
        ));
        if (!isset($json['code']) || (string) $json['code'] !== '0' || empty($json['phone'])) {
            jmweb_api_json(array('ok' => false, 'message' => '获取手机号失败：' . (isset($json['msg']) ? $json['msg'] : ($raw ? $raw : '接口无响应'))));
        }
        $expiresAt = $now + 240;
        $update = $pdo->prepare('UPDATE jm_cards SET phone = ?, provider_uid = ?, provider_sid = ?, provider_host = ?, provider_token = ?, expires_at = ?, updated_at = ? WHERE id = ? AND status = ?');
        $update->execute(array((string) $json['phone'], isset($json['uid']) ? (string) $json['uid'] : '', isset($json['sid']) ? (string) $json['sid'] : $card['project_id'], $login['host'], $login['token'], $expiresAt, $now, $card['id'], 'available'));
        $card['phone'] = (string) $json['phone'];
        $card['provider_uid'] = isset($json['uid']) ? (string) $json['uid'] : '';
        $card['provider_sid'] = isset($json['sid']) ? (string) $json['sid'] : $card['project_id'];
        $card['provider_host'] = $login['host'];
        $card['provider_token'] = $login['token'];
        $card['expires_at'] = $expiresAt;
        jmweb_api_json(array('ok' => true, 'message' => '已获取手机号，请在 240 秒内等待验证码。', 'activation' => jmweb_public_activation_payload($card, '等待验证码', '', '')));
    }

    if ($action === 'poll_code') {
        jmweb_require_installed(true);
        $cardId = isset($_POST['card_id']) ? (int) $_POST['card_id'] : 0;
        if ($cardId <= 0) {
            jmweb_api_json(array('ok' => false, 'message' => '激活信息无效。'));
        }
        $pdo = jmweb_ensure_cards_table();
        $stmt = $pdo->prepare('SELECT * FROM jm_cards WHERE id = ? LIMIT 1');
        $stmt->execute(array($cardId));
        $card = $stmt->fetch();
        if (!$card || empty($card['phone'])) {
            jmweb_api_json(array('ok' => false, 'message' => '没有可查询的手机号。'));
        }
        if ((int) $card['expires_at'] <= time()) {
            jmweb_api_json(array('ok' => false, 'expired' => true, 'message' => '240 秒已到，可以更换号码。', 'activation' => jmweb_public_activation_payload($card, '已超时', '', '')));
        }
        $settings = jmweb_read_settings();
        $login = jmweb_haozhu_login($settings);
        if (empty($login['ok'])) {
            jmweb_api_json(array('ok' => false, 'message' => $login['message']));
        }
        $host = !empty($card['provider_host']) ? $card['provider_host'] : $login['host'];
        $token = !empty($card['provider_token']) ? $card['provider_token'] : $login['token'];
        $sms = jmweb_haozhu_get_message($settings, $host, $token, $card['project_id'], $card['phone']);
        if (!empty($sms['ok'])) {
            $now = time();
            $update = $pdo->prepare('UPDATE jm_cards SET status = ?, sms_code = ?, sms_text = ?, used_at = ?, updated_at = ? WHERE id = ? AND status = ?');
            $update->execute(array('used', $sms['code'], $sms['sms'], $now, $now, $card['id'], 'available'));
            $card['status'] = 'used';
            $card['sms_code'] = $sms['code'];
            $card['sms_text'] = $sms['sms'];
            jmweb_api_json(array('ok' => true, 'received' => true, 'message' => '已收到验证码，兑换券已消费。', 'activation' => jmweb_public_activation_payload($card, '已收到', $sms['code'], $sms['sms'])));
        }
        jmweb_write_update_log('Haozhu getMessage pending: project=' . $card['project_id'] . ', phone=' . $card['phone'] . ', host=' . $host . ', msg=' . $sms['message'] . ', raw=' . (isset($sms['raw']) ? $sms['raw'] : ''));
        jmweb_api_json(array('ok' => true, 'received' => false, 'message' => '暂未收到验证码。', 'activation' => jmweb_public_activation_payload($card, '等待验证码', '', '')));
    }

    if ($action === 'cancel_activation') {
        jmweb_require_installed(true);
        $cardId = isset($_POST['card_id']) ? (int) $_POST['card_id'] : 0;
        if ($cardId <= 0) {
            jmweb_api_json(array('ok' => false, 'message' => '激活信息无效。'));
        }
        $pdo = jmweb_ensure_cards_table();
        $stmt = $pdo->prepare('SELECT * FROM jm_cards WHERE id = ? LIMIT 1');
        $stmt->execute(array($cardId));
        $card = $stmt->fetch();
        if (!$card) {
            jmweb_api_json(array('ok' => false, 'message' => '卡密不存在。'));
        }
        if ($card['status'] === 'used') {
            jmweb_api_json(array('ok' => false, 'message' => '已收到验证码，兑换券已消费，不能取消。'));
        }
        if (!empty($card['expires_at']) && (int) $card['expires_at'] > time()) {
            jmweb_api_json(array('ok' => false, 'message' => '240 秒内持续获取验证码，暂不能更换号码。', 'activation' => jmweb_public_activation_payload($card, '等待验证码', '', '')));
        }
        $now = time();
        $update = $pdo->prepare('UPDATE jm_cards SET phone = ?, provider_uid = ?, provider_sid = ?, provider_host = ?, provider_token = ?, expires_at = 0, updated_at = ? WHERE id = ? AND status = ?');
        $update->execute(array('', '', '', '', '', $now, $card['id'], 'available'));
        jmweb_api_json(array('ok' => true, 'message' => '已取消当前号码，可以重新兑换更换手机号。'));
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

    if ($action === 'get_settings') {
        jmweb_require_admin();
        jmweb_api_json(array(
            'ok' => true,
            'settings' => jmweb_public_settings(jmweb_read_settings()),
            'defaults' => jmweb_public_settings(jmweb_default_settings()),
        ));
    }

    if ($action === 'save_settings') {
        jmweb_require_admin();
        $settings = jmweb_clean_settings($_POST);
        if (!jmweb_save_settings($settings)) {
            jmweb_api_json(array('ok' => false, 'message' => '保存失败，请检查 data 目录写入权限。'));
        }
        jmweb_log('管理员保存基本设置');
        jmweb_api_json(array('ok' => true, 'message' => '基本设置已保存。', 'settings' => jmweb_public_settings($settings)));
    }

    if ($action === 'reset_settings') {
        jmweb_require_admin();
        $settings = jmweb_default_settings();
        if (!jmweb_save_settings($settings)) {
            jmweb_api_json(array('ok' => false, 'message' => '恢复失败，请检查 data 目录写入权限。'));
        }
        jmweb_log('管理员恢复默认基本设置');
        jmweb_api_json(array('ok' => true, 'message' => '已恢复默认设置。', 'settings' => jmweb_public_settings($settings)));
    }

    if ($action === 'check_haozhu_project') {
        jmweb_require_admin();
        $projectId = jmweb_clean_project_id(isset($_POST['project_id']) ? $_POST['project_id'] : '');
        if ($projectId === '') {
            jmweb_api_json(array('ok' => false, 'message' => '请输入正确的项目ID，只能是数字。'));
        }
        $check = jmweb_haozhu_check_project($projectId);
        jmweb_log('检测豪猪码项目ID：' . $projectId . '，结果：' . (!empty($check['ok']) ? 'ok' : 'fail'));
        jmweb_api_json(array_merge(array('project_id' => $projectId), $check));
    }

    if ($action === 'create_cards') {
        jmweb_require_admin();
        $projectId = jmweb_clean_project_id(isset($_POST['project_id']) ? $_POST['project_id'] : '');
        if ($projectId === '') {
            jmweb_api_json(array('ok' => false, 'message' => '请输入正确的项目ID，只能是数字。'));
        }
        $count = isset($_POST['count']) ? (int) $_POST['count'] : 0;
        if ($count < 1) {
            jmweb_api_json(array('ok' => false, 'message' => '生成数量不能小于 1。'));
        }
        if ($count > 10000) {
            jmweb_api_json(array('ok' => false, 'message' => '一次最多只能制作 10000 张卡密。'));
        }

        $projectCheck = jmweb_haozhu_check_project($projectId);
        if (empty($projectCheck['ok'])) {
            jmweb_api_json(array('ok' => false, 'message' => $projectCheck['message']));
        }

        $pdo = jmweb_ensure_cards_table();
        $insert = $pdo->prepare('INSERT IGNORE INTO jm_cards (card_no, project_id, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?)');
        $created = 0;
        $cards = array();
        $now = time();
        $tries = 0;
        while ($created < $count && $tries < ($count * 8 + 80)) {
            $tries++;
            $cardNo = jmweb_generate_card_no($projectId);
            $insert->execute(array($cardNo, $projectId, 'available', $now, $now));
            if ($insert->rowCount() > 0) {
                $created++;
                if (count($cards) < 100) {
                    $cards[] = $cardNo;
                }
            }
        }

        jmweb_log('管理员制作卡密项目ID：' . $projectId . '，数量：' . $created);
        jmweb_api_json(array(
            'ok' => $created === $count,
            'message' => $created === $count ? '已成功制作 ' . $created . ' 张卡密，已逐条写入 jm_cards 独立卡密表。' : '只成功制作 ' . $created . ' 张卡密，请重试。',
            'created' => $created,
            'sample' => $cards,
        ));
    }

    if ($action === 'list_cards') {
        jmweb_require_admin();
        $pdo = jmweb_ensure_cards_table();
        $limit = jmweb_card_allowed_limit(isset($_POST['limit']) ? $_POST['limit'] : 10);
        $page = isset($_POST['page']) ? (int) $_POST['page'] : 1;
        if ($page < 1) {
            $page = 1;
        }
        $keyword = isset($_POST['keyword']) ? trim((string) $_POST['keyword']) : '';
        $statuses = jmweb_card_clean_statuses(isset($_POST['statuses']) ? $_POST['statuses'] : '');

        $where = array();
        $params = array();
        if ($keyword !== '') {
            $where[] = '(card_no LIKE ? OR project_id LIKE ?)';
            $params[] = '%' . $keyword . '%';
            $params[] = '%' . $keyword . '%';
        }
        if (!empty($statuses)) {
            $marks = implode(',', array_fill(0, count($statuses), '?'));
            $where[] = 'status IN (' . $marks . ')';
            foreach ($statuses as $status) {
                $params[] = $status;
            }
        }
        $whereSql = empty($where) ? '' : (' WHERE ' . implode(' AND ', $where));

        $countStmt = $pdo->prepare('SELECT COUNT(*) AS total FROM jm_cards' . $whereSql);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();
        $pages = max(1, (int) ceil($total / $limit));
        if ($page > $pages) {
            $page = $pages;
        }
        $offset = ($page - 1) * $limit;

        $listSql = 'SELECT id, card_no, project_id, status, phone, sms_code, sms_text, provider_uid, provider_sid, used_at, disabled_at, created_at, updated_at FROM jm_cards' . $whereSql . ' ORDER BY id DESC LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;
        $listStmt = $pdo->prepare($listSql);
        $listStmt->execute($params);

        $stats = array('total' => 0, 'available' => 0, 'used' => 0, 'disabled' => 0);
        $statRows = $pdo->query('SELECT status, COUNT(*) AS total FROM jm_cards GROUP BY status')->fetchAll();
        foreach ($statRows as $row) {
            $key = isset($row['status']) ? $row['status'] : '';
            $value = (int) $row['total'];
            $stats['total'] += $value;
            if (isset($stats[$key])) {
                $stats[$key] = $value;
            }
        }

        jmweb_api_json(array(
            'ok' => true,
            'cards' => jmweb_card_rows($listStmt->fetchAll()),
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
            'limit' => $limit,
            'stats' => $stats,
        ));
    }

    if ($action === 'batch_cards') {
        jmweb_require_admin();
        $pdo = jmweb_ensure_cards_table();
        $ids = jmweb_card_ids_from_request();
        $batchAction = isset($_POST['batch_action']) ? trim((string) $_POST['batch_action']) : '';
        if (empty($ids)) {
            jmweb_api_json(array('ok' => false, 'message' => '请先选择卡密。'));
        }
        if (count($ids) > 10000) {
            jmweb_api_json(array('ok' => false, 'message' => '一次最多操作 10000 条。'));
        }
        $marks = implode(',', array_fill(0, count($ids), '?'));
        $now = time();
        if ($batchAction === 'disable') {
            $sql = 'UPDATE jm_cards SET status = ?, disabled_at = ?, updated_at = ? WHERE id IN (' . $marks . ') AND status != ?';
            $params = array_merge(array('disabled', $now, $now), $ids, array('used'));
        } elseif ($batchAction === 'enable') {
            $sql = 'UPDATE jm_cards SET status = ?, disabled_at = 0, updated_at = ? WHERE id IN (' . $marks . ') AND status != ?';
            $params = array_merge(array('available', $now), $ids, array('used'));
        } elseif ($batchAction === 'delete') {
            $sql = 'DELETE FROM jm_cards WHERE id IN (' . $marks . ')';
            $params = $ids;
        } else {
            jmweb_api_json(array('ok' => false, 'message' => '未知批量操作。'));
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        jmweb_log('管理员批量操作卡密：' . $batchAction . '，数量：' . count($ids));
        jmweb_api_json(array('ok' => true, 'message' => '操作完成，影响 ' . $stmt->rowCount() . ' 条。'));
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
