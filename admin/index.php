<?php
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/config/settings.php';
$jmwebSettings = jmweb_read_settings();
?>
<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= JMWEB_NAME ?> 管理后台</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="admin-body">
<?php if (!jmweb_is_admin()): ?>
    <main class="login-page">
        <section class="login-card">
            <span class="brand-mark large">S</span>
            <h1>管理员登录</h1>
            <p>登录后可查看后台和执行一键更新。</p>
            <form id="loginForm" class="form-stack">
                <label>用户名<input name="username" value="admin" autocomplete="username"></label>
                <label>密码<input name="password" type="password" placeholder="请输入安装时设置的密码" autocomplete="current-password"></label>
                <button class="btn primary full" type="submit">登录后台</button>
                <div id="loginMsg" class="form-msg"></div>
            </form>
            <a class="back-link" href="../">返回首页</a>
        </section>
    </main>
<?php else: ?>
    <div class="admin-shell">
        <aside class="sidebar">
            <a class="brand" href="../"><span class="brand-mark">S</span><span><?= htmlspecialchars($jmwebSettings['site_name'], ENT_QUOTES, 'UTF-8') ?></span></a>
            <button class="side-link active" data-page="dashboard">控制台</button>
            <button class="side-link" data-page="update">系统更新</button>
            <button class="side-link" data-page="settings">基本设置</button>
            <button id="logoutBtn" class="side-link danger">退出登录</button>
        </aside>
        <main class="admin-main">
            <div class="admin-topbar">
                <div><span class="eyebrow">Admin Console</span><h1>管理后台</h1></div>
                <span class="status-pill">v<?= JMWEB_VERSION ?></span>
            </div>

            <section class="admin-page" id="page-dashboard">
                <div class="admin-grid">
                    <article class="stat"><strong><?= htmlspecialchars($jmwebSettings['site_name'], ENT_QUOTES, 'UTF-8') ?></strong><span>站点名称</span></article>
                    <article class="stat"><strong><?= JMWEB_VERSION ?></strong><span>当前版本</span></article>
                    <article class="stat"><strong>正常</strong><span>运行状态</span></article>
                </div>
                <div class="panel">
                    <h2>下一步可以开发的功能</h2>
                    <ul class="nice-list">
                        <li>网站配置保存</li>
                        <li>文章 / 产品管理</li>
                        <li>用户系统</li>
                        <li>数据库安装程序</li>
                    </ul>
                </div>
            </section>

            <section class="admin-page hidden" id="page-update">
                <div class="panel update-panel">
                    <h2>系统更新</h2>
                    <p>更新源：<code><?= JMWEB_UPDATE_REPO ?></code></p>
                    <p>版本信息：<code><?= JMWEB_UPDATE_INFO_URL ?></code></p>
                    <p>工作目录：<code><?= JMWEB_UPDATE_WORKDIR ?></code></p>
                    <div class="update-status-card">
                        <div>
                            <span class="muted">当前版本</span>
                            <strong>v<?= JMWEB_VERSION ?></strong>
                        </div>
                        <div>
                            <span class="muted">远程版本</span>
                            <strong id="remoteVersion">未检查</strong>
                        </div>
                    </div>
                    <div class="hero-actions">
                        <button id="checkUpdateBtn" class="btn primary">检查更新</button>
                        <button id="updateBtn" class="btn ghost hidden">立即更新</button>
                    </div>
                    <pre id="updateOutput" class="console-box">请先点击“检查更新”。</pre>
                </div>
            </section>

            <section class="admin-page hidden" id="page-settings">
                <div class="panel">
                    <h2>基本设置</h2>
                    <p class="muted">这里保存的是服务器本地配置，在线更新不会覆盖这些设置。</p>
                    <form id="settingsForm" class="settings-form">
                        <div class="form-grid">
                            <label>站点名称
                                <input name="site_name" value="<?= htmlspecialchars($jmwebSettings['site_name'], ENT_QUOTES, 'UTF-8') ?>" maxlength="40">
                            </label>
                            <label>首页标题
                                <input name="home_title" value="<?= htmlspecialchars($jmwebSettings['home_title'], ENT_QUOTES, 'UTF-8') ?>" maxlength="60">
                            </label>
                            <label>兑换有效期（分钟）
                                <input name="exchange_expire_minutes" type="number" min="1" max="60" value="<?= (int) $jmwebSettings['exchange_expire_minutes'] ?>">
                            </label>
                            <label>取消等待时间（分钟）
                                <input name="cancel_wait_minutes" type="number" min="0" max="30" value="<?= (int) $jmwebSettings['cancel_wait_minutes'] ?>">
                            </label>
                            <label class="wide">首页说明文案
                                <textarea name="home_subtitle" rows="4" maxlength="500"><?= htmlspecialchars($jmwebSettings['home_subtitle'], ENT_QUOTES, 'UTF-8') ?></textarea>
                            </label>
                            <label class="wide">红色使用提示
                                <textarea name="notice_text" rows="3" maxlength="300"><?= htmlspecialchars($jmwebSettings['notice_text'], ENT_QUOTES, 'UTF-8') ?></textarea>
                            </label>
                        </div>
                        <div class="hero-actions">
                            <button class="btn primary" type="submit">保存设置</button>
                            <button class="btn ghost" type="button" id="resetSettingsBtn">恢复默认</button>
                        </div>
                        <div id="settingsMsg" class="form-msg"></div>
                    </form>
                </div>
            </section>
        </main>
    </div>
<?php endif; ?>
<script src="../assets/js/admin.js"></script>
</body>
</html>
