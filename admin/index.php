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
            <a class="brand" href="../"><span class="brand-mark">S</span><span data-setting-display="site_name"><?= htmlspecialchars($jmwebSettings['site_name'], ENT_QUOTES, 'UTF-8') ?></span></a>
            <div class="side-menu-main">
                <button class="side-link active" data-page="dashboard">控制台</button>
                <button class="side-link" data-page="cards">豪猪管理</button>
                <button class="side-link" data-page="settings">基本设置</button>
            </div>
            <div class="side-menu-bottom">
                <button class="side-link" data-page="update">系统更新</button>
                <button id="logoutBtn" class="side-link danger">退出登录</button>
            </div>
        </aside>
        <main class="admin-main">
            <div class="admin-topbar">
                <div><span class="eyebrow">Admin Console</span><h1>管理后台</h1></div>
                <button class="status-pill version-jump" type="button" data-page="update" title="点击进入系统更新">v<?= JMWEB_VERSION ?></button>
            </div>

            <section class="admin-page" id="page-dashboard">
                <div class="admin-grid">
                    <article class="stat"><strong data-setting-display="site_name"><?= htmlspecialchars($jmwebSettings['site_name'], ENT_QUOTES, 'UTF-8') ?></strong><span>站点名称</span></article>
                    <article class="stat version-stat" data-page="update"><strong><?= JMWEB_VERSION ?></strong><span>当前版本，点击进入系统更新</span></article>
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

            <section class="admin-page hidden" id="page-cards">
                <div class="settings-hero-card card-hero-card">
                    <div>
                        <span class="eyebrow">Haozhu Manager</span>
                        <h2>豪猪管理</h2>
                        <p>集中管理豪猪项目检测、兑换码生成和号码接口配置，先在“基本设置”中填写 API 信息后再使用。</p>
                    </div>
                    <div class="settings-badge">项目管理</div>
                </div>
                <div class="cards-workspace">
                    <section class="settings-card card-create-panel">
                        <div class="settings-card-head">
                            <strong>生成兑换码</strong>
                            <span>最多 10000 张</span>
                        </div>
                        <form id="cardCreateForm" class="card-create-form">
                            <label class="setting-field">项目ID
                                <input name="project_id" id="cardProjectId" inputmode="numeric" placeholder="请输入豪猪码项目ID">
                            </label>
                            <button class="btn ghost full" type="button" id="checkProjectBtn">检测项目ID</button>
                            <label class="setting-field">制作数量
                                <input name="count" type="number" min="1" max="10000" value="10" placeholder="请输入制作数量">
                            </label>
                            <button class="btn primary full" type="submit">开始生成</button>
                            <div id="cardCreateMsg" class="settings-msg">请输入项目ID并先检测可用性。</div>
                        </form>
                        <div class="card-stats" id="cardStats">
                            <div><strong>0</strong><span>全部</span></div>
                            <div><strong>0</strong><span>可用</span></div>
                            <div><strong>0</strong><span>已用</span></div>
                            <div><strong>0</strong><span>禁用</span></div>
                        </div>
                    </section>
                    <section class="settings-card card-list-panel">
                        <div class="card-list-toolbar">
                            <div>
                                <strong>卡密详情</strong>
                                <span id="cardListSummary">一列显示 10 个</span>
                            </div>
                            <div class="card-toolbar-controls">
                                <select id="cardLimitSelect">
                                    <option value="10">10</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                    <option value="500">500</option>
                                    <option value="1000">1000</option>
                                    <option value="5000">5000</option>
                                    <option value="10000">10000</option>
                                </select>
                                <input id="cardKeyword" placeholder="搜索卡密">
                            </div>
                        </div>
                        <div class="card-filter-row">
                            <label><input type="checkbox" name="card_status" value="available" checked> 可用</label>
                            <label><input type="checkbox" name="card_status" value="used" checked> 已用</label>
                            <label><input type="checkbox" name="card_status" value="disabled" checked> 禁用</label>
                            <button class="btn ghost" type="button" id="cardRefreshBtn">刷新</button>
                        </div>
                        <div class="card-batch-row">
                            <label><input type="checkbox" id="cardSelectAll"> 全选当前页</label>
                            <button class="btn ghost" type="button" id="copyCardsBtn">复制卡密</button>
                            <button class="btn ghost" type="button" data-card-batch="enable">启用</button>
                            <button class="btn ghost danger-soft" type="button" data-card-batch="disable">禁用卡密</button>
                            <button class="btn ghost danger-soft" type="button" data-card-batch="delete">删除</button>
                            <span id="cardBatchMsg" class="muted">可多选后批量操作</span>
                        </div>
                        <div id="cardList" class="card-list empty">正在加载卡密...</div>
                        <div class="card-pager">
                            <button class="btn ghost" type="button" id="cardPrevPage">上一页</button>
                            <span id="cardPageInfo">1 / 1</span>
                            <button class="btn ghost" type="button" id="cardNextPage">下一页</button>
                        </div>
                    </section>
                </div>
            </section>

            <section class="admin-page hidden" id="page-settings">
                <div class="settings-hero-card">
                    <div>
                        <span class="eyebrow">Site Settings</span>
                        <h2>基本设置</h2>
                        <p>管理前台展示文案和站点名称。保存后立即写入服务器本地配置。</p>
                    </div>
                    <div class="settings-badge">本地配置</div>
                </div>
                <form id="settingsForm" class="settings-form modern-settings-form">
                    <div class="settings-card">
                        <div class="settings-card-head">
                            <strong>站点信息</strong>
                            <span>显示在后台侧边栏和控制台</span>
                        </div>
                        <div class="settings-grid two">
                            <label class="setting-field">站点名称
                                <input name="site_name" value="<?= htmlspecialchars($jmwebSettings['site_name'], ENT_QUOTES, 'UTF-8') ?>" maxlength="40">
                            </label>
                            <label class="setting-field">首页标题
                                <input name="home_title" value="<?= htmlspecialchars($jmwebSettings['home_title'], ENT_QUOTES, 'UTF-8') ?>" maxlength="60">
                            </label>
                        </div>
                    </div>
                    <div class="settings-card">
                        <div class="settings-card-head">
                            <strong>前台文案</strong>
                            <span>这些内容会显示在兑换码验证首页</span>
                        </div>
                        <div class="settings-grid">
                            <label class="setting-field">首页说明文案
                                <textarea name="home_subtitle" rows="4" maxlength="500"><?= htmlspecialchars($jmwebSettings['home_subtitle'], ENT_QUOTES, 'UTF-8') ?></textarea>
                            </label>
                            <label class="setting-field">红色使用提示
                                <textarea name="notice_text" rows="3" maxlength="300"><?= htmlspecialchars($jmwebSettings['notice_text'], ENT_QUOTES, 'UTF-8') ?></textarea>
                            </label>
                        </div>
                    </div>
                    <div class="settings-card">
                        <div class="settings-card-head">
                            <strong>豪猪接口配置</strong>
                            <span>用于检测项目ID、取号和接收验证码</span>
                        </div>
                        <div class="settings-grid two">
                            <label class="setting-field">API 账号
                                <input name="haozhu_api_account" value="<?= htmlspecialchars($jmwebSettings['haozhu_api_account'], ENT_QUOTES, 'UTF-8') ?>" maxlength="160" placeholder="请输入 API 账号">
                            </label>
                            <label class="setting-field">API 密码
                                <input name="haozhu_api_password" type="password" value="" maxlength="160" placeholder="<?= !empty($jmwebSettings['haozhu_api_password']) ? '已保存，留空不修改' : '请输入 API 密码' ?>" autocomplete="new-password">
                            </label>
                        </div>
                        <div class="settings-grid">
                            <label class="setting-field">API 地址，一行一个
                                <textarea name="haozhu_api_hosts" rows="3" maxlength="500" placeholder="api.haozhuma.com&#10;api.haozhuyun.com"><?= htmlspecialchars($jmwebSettings['haozhu_api_hosts'], ENT_QUOTES, 'UTF-8') ?></textarea>
                            </label>
                            <label class="setting-field">释放接口 api 参数，暂未填写官方接口时留空
                                <input name="haozhu_release_api" value="<?= htmlspecialchars($jmwebSettings['haozhu_release_api'], ENT_QUOTES, 'UTF-8') ?>" maxlength="60" placeholder="例如 releasePhone，确认官方文档后填写">
                            </label>
                        </div>
                    </div>
                    <div class="settings-actions">
                        <div id="settingsMsg" class="settings-msg">修改后点击保存设置。</div>
                        <div class="hero-actions">
                            <button class="btn primary" type="submit">保存设置</button>
                            <button class="btn ghost" type="button" id="resetSettingsBtn">恢复默认</button>
                        </div>
                    </div>
                </form>
            </section>
        </main>
    </div>
<?php endif; ?>
<script src="../assets/js/admin.js"></script>
</body>
</html>
