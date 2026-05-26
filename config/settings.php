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

function jmweb_clean_settings($input)
{
    $defaults = jmweb_default_settings();
    $settings = array();

    $settings['site_name'] = isset($input['site_name']) ? trim((string) $input['site_name']) : $defaults['site_name'];
    $settings['home_title'] = isset($input['home_title']) ? trim((string) $input['home_title']) : $defaults['home_title'];
    $settings['home_subtitle'] = isset($input['home_subtitle']) ? trim((string) $input['home_subtitle']) : $defaults['home_subtitle'];
    $settings['notice_text'] = isset($input['notice_text']) ? trim((string) $input['notice_text']) : $defaults['notice_text'];
    $settings['exchange_expire_minutes'] = isset($input['exchange_expire_minutes']) ? (int) $input['exchange_expire_minutes'] : (int) $defaults['exchange_expire_minutes'];
    $settings['cancel_wait_minutes'] = isset($input['cancel_wait_minutes']) ? (int) $input['cancel_wait_minutes'] : (int) $defaults['cancel_wait_minutes'];

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
