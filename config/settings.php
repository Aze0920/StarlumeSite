<?php
function jmweb_default_settings()
{
    return array(
        'site_name' => defined('JMWEB_NAME') ? JMWEB_NAME : '豪猪接码',
        'home_title' => '兑换码验证',
        'home_subtitle' => '兑换后 5 分钟到期，没收到验证码或者手机无法使用，可以过 2 分钟后点击取消激活，可以重新兑换会更换新的号码。收到验证码后兑换券会被消费。一个号只能接一次码。',
        'notice_text' => '不退不换，手机号错误点击取消激活，多兑换几次。',
        'exchange_expire_minutes' => 5,
        'cancel_wait_minutes' => 2,
        'active_sms_provider' => 'haozhu',
        'haozhu_api_hosts' => "api.haozhuma.com\napi.haozhuyun.com",
        'haozhu_api_account' => '',
        'haozhu_api_password' => '',
        'haozhu_release_api' => '',
        'luban_apikey' => '',
    );
}

function jmweb_settings_path()
{
    return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'settings.json';
}

function jmweb_read_settings()
{
    $defaults = jmweb_default_settings();
    $file = jmweb_settings_path();
    if (!is_file($file)) {
        return $defaults;
    }

    $json = json_decode(file_get_contents($file), true);
    if (!is_array($json)) {
        return $defaults;
    }

    return array_merge($defaults, $json);
}

function jmweb_clean_multiline_hosts($value)
{
    $value = str_replace(array("\r\n", "\r", ',', '，', ';', '；', '|'), "\n", (string) $value);
    $lines = explode("\n", $value);
    $hosts = array();
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        $line = preg_replace('#^https?://#i', '', $line);
        $line = preg_replace('#/.*$#', '', $line);
        $line = trim($line);
        if ($line !== '' && !in_array($line, $hosts, true)) {
            $hosts[] = $line;
        }
    }
    if (empty($hosts)) {
        $hosts = array('api.haozhuma.com', 'api.haozhuyun.com');
    }
    return implode("\n", $hosts);
}

function jmweb_clean_settings($input)
{
    $defaults = jmweb_default_settings();
    $current = jmweb_read_settings();
    $settings = array();

    $settings['site_name'] = isset($input['site_name']) ? trim((string) $input['site_name']) : (isset($current['site_name']) ? (string) $current['site_name'] : $defaults['site_name']);
    $settings['home_title'] = isset($input['home_title']) ? trim((string) $input['home_title']) : (isset($current['home_title']) ? (string) $current['home_title'] : $defaults['home_title']);
    $settings['home_subtitle'] = isset($input['home_subtitle']) ? trim((string) $input['home_subtitle']) : (isset($current['home_subtitle']) ? (string) $current['home_subtitle'] : $defaults['home_subtitle']);
    $settings['notice_text'] = isset($input['notice_text']) ? trim((string) $input['notice_text']) : (isset($current['notice_text']) ? (string) $current['notice_text'] : $defaults['notice_text']);
    $settings['exchange_expire_minutes'] = isset($input['exchange_expire_minutes']) ? (int) $input['exchange_expire_minutes'] : (isset($current['exchange_expire_minutes']) ? (int) $current['exchange_expire_minutes'] : (int) $defaults['exchange_expire_minutes']);
    $settings['cancel_wait_minutes'] = isset($input['cancel_wait_minutes']) ? (int) $input['cancel_wait_minutes'] : (isset($current['cancel_wait_minutes']) ? (int) $current['cancel_wait_minutes'] : (int) $defaults['cancel_wait_minutes']);
    $settings['active_sms_provider'] = isset($input['active_sms_provider']) ? trim((string) $input['active_sms_provider']) : (isset($current['active_sms_provider']) ? (string) $current['active_sms_provider'] : $defaults['active_sms_provider']);
    if (!in_array($settings['active_sms_provider'], array('haozhu', 'luban'), true)) {
        $settings['active_sms_provider'] = 'haozhu';
    }
    $settings['haozhu_api_hosts'] = isset($input['haozhu_api_hosts']) ? jmweb_clean_multiline_hosts($input['haozhu_api_hosts']) : (isset($current['haozhu_api_hosts']) ? (string) $current['haozhu_api_hosts'] : $defaults['haozhu_api_hosts']);
    $settings['haozhu_api_account'] = isset($input['haozhu_api_account']) ? trim((string) $input['haozhu_api_account']) : (isset($current['haozhu_api_account']) ? (string) $current['haozhu_api_account'] : $defaults['haozhu_api_account']);
    if (isset($input['haozhu_api_password'])) {
        $settings['haozhu_api_password'] = trim((string) $input['haozhu_api_password']);
        if ($settings['haozhu_api_password'] === '' && !empty($current['haozhu_api_password'])) {
            $settings['haozhu_api_password'] = (string) $current['haozhu_api_password'];
        }
    } else {
        $settings['haozhu_api_password'] = isset($current['haozhu_api_password']) ? (string) $current['haozhu_api_password'] : $defaults['haozhu_api_password'];
    }
    $settings['haozhu_release_api'] = isset($input['haozhu_release_api']) ? trim((string) $input['haozhu_release_api']) : (isset($current['haozhu_release_api']) ? (string) $current['haozhu_release_api'] : '');
    if (isset($input['luban_apikey'])) {
        $settings['luban_apikey'] = trim((string) $input['luban_apikey']);
        if ($settings['luban_apikey'] === '' && !empty($current['luban_apikey'])) {
            $settings['luban_apikey'] = (string) $current['luban_apikey'];
        }
    } else {
        $settings['luban_apikey'] = isset($current['luban_apikey']) ? (string) $current['luban_apikey'] : $defaults['luban_apikey'];
    }

    if ($settings['site_name'] === '') {
        $settings['site_name'] = $defaults['site_name'];
    }
    if ($settings['home_title'] === '') {
        $settings['home_title'] = $defaults['home_title'];
    }
    if ($settings['home_subtitle'] === '') {
        $settings['home_subtitle'] = $defaults['home_subtitle'];
    }
    if ($settings['notice_text'] === '') {
        $settings['notice_text'] = $defaults['notice_text'];
    }
    if ($settings['exchange_expire_minutes'] < 1) {
        $settings['exchange_expire_minutes'] = 1;
    }
    if ($settings['exchange_expire_minutes'] > 60) {
        $settings['exchange_expire_minutes'] = 60;
    }
    if ($settings['cancel_wait_minutes'] < 0) {
        $settings['cancel_wait_minutes'] = 0;
    }
    if ($settings['cancel_wait_minutes'] > 30) {
        $settings['cancel_wait_minutes'] = 30;
    }

    return $settings;
}

function jmweb_public_settings($settings)
{
    $safe = $settings;
    if (!empty($safe['haozhu_api_password'])) {
        $safe['haozhu_api_password'] = '';
        $safe['haozhu_api_password_saved'] = true;
    } else {
        $safe['haozhu_api_password_saved'] = false;
    }
    if (!empty($safe['luban_apikey'])) {
        $safe['luban_apikey'] = '';
        $safe['luban_apikey_saved'] = true;
    } else {
        $safe['luban_apikey_saved'] = false;
    }
    return $safe;
}

function jmweb_save_settings($settings)
{
    $settings = jmweb_clean_settings($settings);
    $file = jmweb_settings_path();
    $dir = dirname($file);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $json = json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    return file_put_contents($file, $json . PHP_EOL) !== false;
}
