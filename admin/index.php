<?php require_once dirname(__DIR__) . '/config/app.php'; ?>
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
            <a class="brand" href="../"><span class="brand-mark">S</span><span><?= JMWEB_NAME ?></span></a>
            <button class="side-link active" data-page="dashboard">控制台</button>
            <button class="side-link" data-page="update">系统更新</button>
            <button class="side-link" data-page="settings">基础信息</button>
            <button id="logoutBtn" class="side-link danger">退出登录</button>
        </aside>
        <main class="admin-main">
            <div class="admin-topbar">
                <div><span class="eyebrow">Admin Console</span><h1>管理后台</h1></div>
                <span class="status-pill">v<?= JMWEB_VERSION ?></span>
            </div>

            <section class="admin-page" id="page-dashboard">
                <div class="admin-grid">
                    <article class="stat"><strong><?= JMWEB_NAME ?></strong><span>站点名称</span></article>
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
                    <h2>系统一键更新</h2>
                    <p>更新源：<code><?= JMWEB_UPDATE_REPO ?></code></p>
                    <p>工作目录：<code><?= JMWEB_UPDATE_WORKDIR ?></code></p>
                    <button id="updateBtn" class="btn primary">开始一键更新</button>
                    <pre id="updateOutput" class="console-box">等待操作...</pre>
                </div>
            </section>

            <section class="admin-page hidden" id="page-settings">
                <div class="panel">
                    <h2>基础信息</h2>
                    <div class="info-list">
                        <p><b>网站名称：</b><?= JMWEB_NAME ?></p>
                        <p><b>仓库名建议：</b><?= JMWEB_REPO_NAME ?></p>
                        <p><b>本地目录：</b><?= JMWEB_SITE_DIR ?></p>
                        <p><b>后台账号：</b>安装时设置的管理员账号</p>
                    </div>
                </div>
            </section>
        </main>
    </div>
<?php endif; ?>
<script src="../assets/js/admin.js"></script>
</body>
</html>
