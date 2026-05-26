<?php
/**
 * 星澜云站前台入口，由 public/index.php 加载。
 */
error_reporting(0);
ini_set('display_errors', 0);

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

require_once dirname(__DIR__) . '/config/app.php';

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

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
    <title><?= JMWEB_NAME ?> - 轻量网站系统</title>
    <meta name="description" content="<?= JMWEB_NAME ?> 是一套可直接上传宝塔运行的前后台网站系统。">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <header class="site-header">
        <nav class="nav container">
            <a class="brand" href="/"><span class="brand-mark">S</span><span><?= JMWEB_NAME ?></span></a>
            <div class="nav-links"><a href="#features">功能</a><a href="#deploy">部署</a><a href="/admin/">后台</a></div>
        </nav>
    </header>
    <main>
        <section class="hero container">
            <div class="hero-copy">
                <span class="eyebrow">Simple · Modern · Baota Ready</span>
                <h1>漂亮、轻量、可直接上传宝塔的网站起步版。</h1>
                <p>已包含安装向导、数据库配置、前台展示页、后台登录页、后台总览、一键更新入口，以及 GitHub 一键上传脚本。</p>
                <div class="hero-actions"><a class="btn primary" href="/admin/">进入后台</a><a class="btn ghost" href="#deploy">查看部署说明</a></div>
            </div>
            <div class="hero-card">
                <div class="window-bar"><span></span><span></span><span></span></div>
                <div class="metric-grid">
                    <div><strong>1.0.0</strong><small>当前版本</small></div>
                    <div><strong>PHP</strong><small>原生运行</small></div>
                    <div><strong>Public</strong><small>运行目录</small></div>
                    <div><strong>GitHub</strong><small>一键同步</small></div>
                </div>
            </div>
        </section>
        <section id="features" class="section container">
            <div class="section-title"><span>核心模块</span><h2>先做最简单，但结构留好。</h2></div>
            <div class="cards">
                <article class="card"><b>安装向导</b><p>首次访问自动跳转安装页，填写数据库后自动写入配置和安装锁。</p></article>
                <article class="card"><b>管理后台</b><p>安装时设置管理员账号密码，后台包含总览、站点信息、系统更新。</p></article>
                <article class="card"><b>一键更新</b><p>后台点击即可尝试从 GitHub 拉取最新代码并同步到当前网站目录。</p></article>
            </div>
        </section>
        <section id="deploy" class="section container deploy-panel">
            <div><span class="eyebrow">Deployment</span><h2>宝塔运行目录</h2><p>上传整个项目到服务器后，请把宝塔网站运行目录设置为 <code>/public</code>。首次访问会进入 <code>/install.php</code> 安装。</p></div>
            <a class="btn primary" href="/admin/">立即管理</a>
        </section>
    </main>
    <footer class="footer container">© <?= date('Y') ?> <?= JMWEB_NAME ?> · Repository: <?= JMWEB_REPO_NAME ?></footer>
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
