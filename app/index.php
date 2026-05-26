<?php
/**
 * 豪猪接码前台入口，由 public/index.php 加载。
 */
error_reporting(0);
ini_set('display_errors', 0);

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/config/settings.php';
$jmwebSettings = jmweb_read_settings();

$path = parse_url(isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/', PHP_URL_PATH);
if (!$path) {
    $path = '/';
}

if (preg_match('/\.(css|js|png|jpg|jpeg|gif|ico|woff|woff2|ttf|map|svg)$/i', $path)) {
    return false;
}

if ($path !== '/install.php') {
    jmweb_require_installed(false);
}

if ($path === '/' || $path === '/index.php') {
    ?>
<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($jmwebSettings['home_title'], ENT_QUOTES, 'UTF-8') ?></title>
    <meta name="description" content="输入兑换码获取手机号，并查看验证码接收状态。">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="app-shell redeem-shell">
    <main class="page page-public">
        <section class="redeem-hero">
            <p class="eyebrow">Voucher Exchange</p>
            <h1><?= htmlspecialchars($jmwebSettings['home_title'], ENT_QUOTES, 'UTF-8') ?></h1>
            <p class="lead"><?= htmlspecialchars($jmwebSettings['home_subtitle'], ENT_QUOTES, 'UTF-8') ?></p>
            <p class="service-notice"><strong>使用提示：</strong><?= htmlspecialchars($jmwebSettings['notice_text'], ENT_QUOTES, 'UTF-8') ?></p>
        </section>

        <section class="redeem-panel">
            <form id="redeem-form" class="stack-form">
                <label for="voucher-code">兑换码</label>
                <input id="voucher-code" name="code" maxlength="32" placeholder="HZ-项目ID-XXXX-XXXX-XXXX" autocomplete="off">
                <button id="redeem-submit" type="submit">开始验证</button>
            </form>
            <p id="redeem-message" class="message" aria-live="polite"></p>
        </section>

        <section id="activation-panel" class="redeem-panel hidden">
            <div class="status-grid">
                <div>
                    <span class="label">手机号</span>
                    <span class="phone-line">
                        <strong id="phone-number">-</strong>
                        <button id="copy-phone-button" class="icon-button" type="button" aria-label="复制手机号" title="复制手机号" disabled>
                            <svg aria-hidden="true" viewBox="0 0 24 24" focusable="false">
                                <rect x="9" y="9" width="11" height="11" rx="2"></rect>
                                <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                            </svg>
                        </button>
                    </span>
                </div>
                <div>
                    <span class="label">状态</span>
                    <strong id="activation-state">-</strong>
                </div>
                <div>
                    <span class="label">验证码</span>
                    <strong id="activation-code">等待中</strong>
                </div>
                <div>
                    <span class="label">到期时间</span>
                    <strong id="activation-expiry">-</strong>
                </div>
            </div>
            <div class="button-row">
                <button id="refresh-status" type="button" class="secondary">刷新状态</button>
                <div id="cancel-control" class="cancel-control">
                    <button id="cancel-activation" type="button" class="danger">取消激活</button>
                    <span id="cancel-countdown" class="countdown" aria-live="polite"></span>
                </div>
            </div>
            <p class="message">未收到验证码可取消激活；收到验证码后兑换券会直接消费，无法取消。</p>
            <p class="service-notice"><strong>使用提示：</strong><?= htmlspecialchars($jmwebSettings['notice_text'], ENT_QUOTES, 'UTF-8') ?></p>
        </section>
    </main>
    <script>
        window.JMWEB_PUBLIC_SETTINGS = {
            exchangeExpireMinutes: <?= (int) $jmwebSettings['exchange_expire_minutes'] ?>,
            cancelWaitMinutes: <?= (int) $jmwebSettings['cancel_wait_minutes'] ?>
        };
    </script>
    <script src="/assets/js/app.js"></script>
</body>
</html>
    <?php
    exit;
}

if ($path === '/admin' || $path === '/admin/') {
    include dirname(__DIR__) . '/admin/index.php';
    exit;
}

if ($path === '/admin.php') {
    header('Location: /admin/', true, 302);
    exit;
}

http_response_code(404);
echo '<!doctype html><html lang="zh-CN"><head><meta charset="utf-8"><title>404</title></head><body><h1>404 Not Found</h1></body></html>';
