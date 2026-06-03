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
                <button class="side-link" data-page="luban-cards">鲁班接码</button>
                <button class="side-link" data-page="yinuopp-cards">一诺PP</button>
                <button class="side-link" data-page="yinuocx-cards">一诺CX</button>
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
                        <p>集中管理豪猪项目检测、接口配置、兑换码生成和号码使用。</p>
                    </div>
                    <div class="hero-actions">
                        <button class="btn ghost" type="button" id="toggleHaozhuSettingsBtn">配置</button>
                        <div class="settings-badge">项目管理</div>
                    </div>
                </div>
                <form id="haozhuSettingsForm" class="settings-form modern-settings-form hidden">
                    <div class="settings-card platform-settings-card">
                        <div class="settings-card-head">
                            <strong>豪猪配置</strong>
                            <span>豪猪账号、密钥和接口地址</span>
                        </div>
                        <div class="platform-config-panel">
                            <div class="platform-config-head">
                                <div>
                                    <strong>豪猪</strong>
                                    <p>这里填写豪猪账号、密钥和接口地址。</p>
                                </div>
                                <span class="settings-badge">当前启用</span>
                            </div>
                            <div class="settings-grid two">
                                <label class="setting-field">豪猪 API 账号
                                    <input name="haozhu_api_account" value="<?= htmlspecialchars($jmwebSettings['haozhu_api_account'], ENT_QUOTES, 'UTF-8') ?>" maxlength="160" placeholder="请输入豪猪 API 账号">
                                </label>
                                <label class="setting-field">豪猪 API 密钥/密码
                                    <input name="haozhu_api_password" type="password" value="" maxlength="160" placeholder="<?= !empty($jmwebSettings['haozhu_api_password']) ? '已保存，留空不修改' : '请输入豪猪 API 密钥或密码' ?>" autocomplete="new-password">
                                </label>
                            </div>
                            <div class="settings-grid">
                                <label class="setting-field">豪猪 API 地址，一行一个
                                    <textarea name="haozhu_api_hosts" rows="3" maxlength="500" placeholder="api.haozhuma.com&#10;api.haozhuyun.com"><?= htmlspecialchars($jmwebSettings['haozhu_api_hosts'], ENT_QUOTES, 'UTF-8') ?></textarea>
                                </label>
                            </div>
                        </div>
                        <div class="settings-actions inline-actions">
                            <div id="haozhuSettingsMsg" class="settings-msg"></div>
                            <div class="hero-actions">
                                <button class="btn primary" type="submit">保存豪猪配置</button>
                            </div>
                        </div>
                    </div>
                </form>
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

            <section class="admin-page hidden" id="page-luban-cards">
                <div class="settings-hero-card card-hero-card">
                    <div>
                        <span class="eyebrow">Luban Sms</span>
                        <h2>鲁班接码</h2>
                        <p>独立管理鲁班接码 APIKEY、项目检测、兑换码生成和短信接收。</p>
                    </div>
                    <div class="hero-actions">
                        <button class="btn ghost" type="button" id="toggleLubanSettingsBtn">配置</button>
                        <div class="settings-badge">独立制卡</div>
                    </div>
                </div>
                <form id="lubanSettingsForm" class="settings-form modern-settings-form hidden">
                    <div class="settings-card platform-settings-card">
                        <div class="settings-card-head">
                            <strong>鲁班接码配置</strong>
                            <span>只需要填写鲁班 APIKEY</span>
                        </div>
                        <div class="platform-config-panel">
                            <div class="platform-config-head">
                                <div>
                                    <strong>鲁班接码</strong>
                                    <p>接口地址固定为 lubansms.com，request_id 会作为编号/项目请求标识保存。</p>
                                </div>
                                <span class="settings-badge">Luban Sms</span>
                            </div>
                            <div class="settings-grid">
                                <label class="setting-field">鲁班 APIKEY
                                    <input name="luban_apikey" type="password" value="" maxlength="160" placeholder="<?= !empty($jmwebSettings['luban_apikey']) ? '已保存，留空不修改' : '请输入鲁班 APIKEY' ?>" autocomplete="new-password">
                                </label>
                            </div>
                        </div>
                        <div class="settings-actions inline-actions">
                            <div id="lubanSettingsMsg" class="settings-msg"></div>
                            <div class="hero-actions">
                                <button class="btn primary" type="submit">保存鲁班配置</button>
                            </div>
                        </div>
                    </div>
                </form>
                <div class="cards-workspace">
                    <section class="settings-card card-create-panel">
                        <div class="settings-card-head">
                            <strong>生成鲁班兑换码</strong>
                            <span>最多 10000 张</span>
                        </div>
                        <form id="lubanCardCreateForm" class="card-create-form">
                            <label class="setting-field">项目ID / service_id
                                <input name="project_id" id="lubanCardProjectId" inputmode="numeric" placeholder="请输入鲁班 service_id">
                            </label>
                            <button class="btn ghost full" type="button" id="checkLubanProjectBtn">检测项目ID</button>
                            <label class="setting-field">制作数量
                                <input name="count" type="number" min="1" max="10000" value="10" placeholder="请输入制作数量">
                            </label>
                            <button class="btn primary full" type="submit">开始生成</button>
                            <div id="lubanCardCreateMsg" class="settings-msg">请输入项目ID并先检测可用性。</div>
                        </form>
                        <div class="card-stats" id="lubanCardStats">
                            <div><strong>0</strong><span>全部</span></div>
                            <div><strong>0</strong><span>可用</span></div>
                            <div><strong>0</strong><span>已用</span></div>
                            <div><strong>0</strong><span>禁用</span></div>
                        </div>
                    </section>
                    <section class="settings-card card-list-panel">
                        <div class="card-list-toolbar">
                            <div>
                                <strong>鲁班卡密详情</strong>
                                <span id="lubanCardListSummary">一列显示 10 个</span>
                            </div>
                            <div class="card-toolbar-controls">
                                <select id="lubanCardLimitSelect">
                                    <option value="10">10</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                    <option value="500">500</option>
                                    <option value="1000">1000</option>
                                    <option value="5000">5000</option>
                                    <option value="10000">10000</option>
                                </select>
                                <input id="lubanCardKeyword" placeholder="搜索卡密">
                            </div>
                        </div>
                        <div class="card-filter-row">
                            <label><input type="checkbox" name="luban_card_status" value="available" checked> 可用</label>
                            <label><input type="checkbox" name="luban_card_status" value="used" checked> 已用</label>
                            <label><input type="checkbox" name="luban_card_status" value="disabled" checked> 禁用</label>
                            <button class="btn ghost" type="button" id="lubanCardRefreshBtn">刷新</button>
                        </div>
                        <div class="card-batch-row">
                            <label><input type="checkbox" id="lubanCardSelectAll"> 全选当前页</label>
                            <button class="btn ghost" type="button" id="lubanCopyCardsBtn">复制卡密</button>
                            <button class="btn ghost" type="button" data-luban-card-batch="enable">启用</button>
                            <button class="btn ghost danger-soft" type="button" data-luban-card-batch="disable">禁用卡密</button>
                            <button class="btn ghost danger-soft" type="button" data-luban-card-batch="delete">删除</button>
                            <span id="lubanCardBatchMsg" class="muted">可多选后批量操作</span>
                        </div>
                        <div id="lubanCardList" class="card-list empty">正在加载卡密...</div>
                        <div class="card-pager">
                            <button class="btn ghost" type="button" id="lubanCardPrevPage">上一页</button>
                            <span id="lubanCardPageInfo">1 / 1</span>
                            <button class="btn ghost" type="button" id="lubanCardNextPage">下一页</button>
                        </div>
                    </section>
                </div>
            </section>

            <section class="admin-page hidden" id="page-yinuopp-cards">
                <div class="settings-hero-card card-hero-card">
                    <div>
                        <span class="eyebrow">Yinuo PP</span>
                        <h2>一诺PP</h2>
                        <p>通过手动库存管理号码和接码 API，一行一个库存，生成独立兑换码。</p>
                    </div>
                    <div class="hero-actions">
                        <button class="btn ghost" type="button" id="toggleYinuoppSettingsBtn">配置</button>
                        <div class="settings-badge">库存制卡</div>
                    </div>
                </div>
                <form id="yinuoppSettingsForm" class="settings-form modern-settings-form hidden">
                    <div class="settings-card platform-settings-card">
                        <div class="settings-card-head">
                            <strong>一诺PP库存配置</strong>
                            <span>格式：区号手机号|接码API，一行一个</span>
                        </div>
                        <div class="platform-config-panel">
                            <div class="platform-config-head">
                                <div>
                                    <strong>一诺PP</strong>
                                    <p>例如：+16309199343|http://a.62-us.com/api/get_sms?key=df629911e2def8c3d93c0178006a432f</p>
                                </div>
                                <span class="settings-badge">手动库存</span>
                            </div>
                            <div class="settings-grid">
                                <label class="setting-field">一诺PP库存，一行一个
                                    <textarea name="yinuopp_inventory" rows="8" maxlength="1000000" placeholder="+16309199343|http://a.62-us.com/api/get_sms?key=xxxx"><?= htmlspecialchars($jmwebSettings['yinuopp_inventory'], ENT_QUOTES, 'UTF-8') ?></textarea>
                                </label>
                            </div>
                        </div>
                        <div class="settings-actions inline-actions">
                            <div id="yinuoppSettingsMsg" class="settings-msg"></div>
                            <div class="hero-actions">
                                <button class="btn primary" type="submit">保存一诺PP库存</button>
                            </div>
                        </div>
                    </div>
                </form>
                <div class="cards-workspace">
                    <section class="settings-card card-create-panel">
                        <div class="settings-card-head">
                            <strong>生成一诺PP兑换码</strong>
                            <span>按库存数量生成</span>
                        </div>
                        <form id="yinuoppCardCreateForm" class="card-create-form">
                            <label class="setting-field">制作数量
                                <input name="count" type="number" min="1" max="10000" value="10" placeholder="不能超过当前库存">
                            </label>
                            <button class="btn primary full" type="submit">开始生成</button>
                            <div id="yinuoppCardCreateMsg" class="settings-msg"></div>
                        </form>
                        <div class="card-stats yinuopp-stats-grid" id="yinuoppCardStats">
                            <div><strong>0</strong><span>手机号总数</span></div>
                            <div><strong>0</strong><span>正常可用</span></div>
                            <div><strong>0</strong><span>已用次数</span></div>
                            <div><strong>0</strong><span>接码成功</span></div>
                            <div><strong>0</strong><span>问题号</span></div>
                            <div><strong>0</strong><span>禁用号</span></div>
                            <div><strong>0</strong><span>可用卡密</span></div>
                            <div><strong>0</strong><span>已用卡密</span></div>
                        </div>
                    </section>
                    <div class="yinuopp-detail-stack">
                    <section class="settings-card card-list-panel yinuopp-mode-panel">
                        <div class="card-list-toolbar">
                            <div>
                                <strong>一诺PP详情</strong>
                                <span id="yinuoppModeSummary">请选择查看卡密详情或手机号详情</span>
                            </div>
                            <div class="yinuopp-mode-switch">
                                <button class="btn primary" type="button" data-yinuopp-mode="cards">卡密详情</button>
                                <button class="btn ghost" type="button" data-yinuopp-mode="numbers">手机号详情</button>
                            </div>
                        </div>
                    </section>
                    <section class="settings-card card-list-panel" id="yinuoppCardsPanel">
                        <div class="card-list-toolbar">
                            <div>
                                <strong>一诺PP卡密详情</strong>
                                <span id="yinuoppCardListSummary">一列显示 10 个</span>
                            </div>
                            <div class="card-toolbar-controls">
                                <select id="yinuoppCardLimitSelect">
                                    <option value="10">10</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                    <option value="500">500</option>
                                    <option value="1000">1000</option>
                                    <option value="5000">5000</option>
                                    <option value="10000">10000</option>
                                </select>
                                <input id="yinuoppCardKeyword" placeholder="搜索卡密">
                            </div>
                        </div>
                        <div class="card-filter-row">
                            <label><input type="checkbox" name="yinuopp_card_status" value="available" checked> 可用</label>
                            <label><input type="checkbox" name="yinuopp_card_status" value="used" checked> 已用</label>
                            <label><input type="checkbox" name="yinuopp_card_status" value="disabled" checked> 禁用</label>
                            <button class="btn ghost" type="button" id="yinuoppCardRefreshBtn">刷新</button>
                        </div>
                        <div class="card-batch-row">
                            <label><input type="checkbox" id="yinuoppCardSelectAll"> 全选当前页</label>
                            <button class="btn ghost" type="button" id="yinuoppCopyCardsBtn">复制卡密</button>
                            <button class="btn ghost" type="button" data-yinuopp-card-batch="enable">启用</button>
                            <button class="btn ghost danger-soft" type="button" data-yinuopp-card-batch="disable">禁用卡密</button>
                            <button class="btn ghost danger-soft" type="button" data-yinuopp-card-batch="delete">删除</button>
                            <span id="yinuoppCardBatchMsg" class="muted">可多选后批量操作</span>
                        </div>
                        <div id="yinuoppCardList" class="card-list empty">正在加载卡密...</div>
                        <div class="card-pager">
                            <button class="btn ghost" type="button" id="yinuoppCardPrevPage">上一页</button>
                            <span id="yinuoppCardPageInfo">1 / 1</span>
                            <button class="btn ghost" type="button" id="yinuoppCardNextPage">下一页</button>
                        </div>
                    </section>
                    <section class="settings-card card-list-panel hidden" id="yinuoppNumbersPanel">
                        <div class="card-list-toolbar">
                            <div>
                                <strong>一诺PP手机号详情</strong>
                                <span id="yinuoppNumberListSummary">一列显示 10 个</span>
                            </div>
                            <div class="card-toolbar-controls">
                                <select id="yinuoppNumberLimitSelect">
                                    <option value="10">10</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                    <option value="500">500</option>
                                    <option value="1000">1000</option>
                                    <option value="5000">5000</option>
                                    <option value="10000">10000</option>
                                </select>
                                <input id="yinuoppNumberKeyword" placeholder="搜索手机号/API">
                            </div>
                        </div>
                        <div class="card-filter-row">
                            <label><input type="checkbox" name="yinuopp_number_status" value="available" checked> 可用</label>
                            <label><input type="checkbox" name="yinuopp_number_status" value="bad" checked> 问题</label>
                            <label><input type="checkbox" name="yinuopp_number_status" value="disabled" checked> 禁用</label>
                            <button class="btn ghost" type="button" id="yinuoppNumberRefreshBtn">刷新</button>
                        </div>
                        <div class="card-batch-row">
                            <label><input type="checkbox" id="yinuoppNumberSelectAll"> 全选当前页</label>
                            <button class="btn ghost" type="button" data-yinuopp-number-batch="enable">启用</button>
                            <button class="btn ghost danger-soft" type="button" data-yinuopp-number-batch="disable">禁用手机号</button>
                            <button class="btn ghost danger-soft" type="button" data-yinuopp-number-batch="delete">删除手机号</button>
                            <span id="yinuoppNumberBatchMsg" class="muted">取消激活/更换手机号会自动记录为问题号</span>
                        </div>
                        <div id="yinuoppNumberList" class="card-list empty">正在加载手机号...</div>
                        <div class="card-pager">
                            <button class="btn ghost" type="button" id="yinuoppNumberPrevPage">上一页</button>
                            <span id="yinuoppNumberPageInfo">1 / 1</span>
                            <button class="btn ghost" type="button" id="yinuoppNumberNextPage">下一页</button>
                        </div>
                    </section>
                    </div>
                </div>
            </section>

            <section class="admin-page hidden" id="page-yinuocx-cards">
                <div class="settings-hero-card card-hero-card">
                    <div>
                        <span class="eyebrow">Yinuo CX</span>
                        <h2>一诺CX</h2>
                        <p>同一诺PP逻辑，通过手动库存管理号码和接码 API，一行一个库存，生成独立兑换码。</p>
                    </div>
                    <div class="hero-actions">
                        <button class="btn ghost" type="button" id="toggleYinuocxSettingsBtn">配置</button>
                        <div class="settings-badge">库存制卡</div>
                    </div>
                </div>
                <form id="yinuocxSettingsForm" class="settings-form modern-settings-form hidden">
                    <div class="settings-card platform-settings-card">
                        <div class="settings-card-head">
                            <strong>一诺CX库存配置</strong>
                            <span>格式：区号手机号|接码API，一行一个</span>
                        </div>
                        <div class="platform-config-panel">
                            <div class="platform-config-head">
                                <div>
                                    <strong>一诺CX</strong>
                                    <p>例如：+16309199343|http://a.62-us.com/api/get_sms?key=xxxx</p>
                                </div>
                                <span class="settings-badge">手动库存</span>
                            </div>
                            <div class="settings-grid">
                                <label class="setting-field">一诺CX库存，一行一个
                                    <textarea name="yinuocx_inventory" rows="8" maxlength="1000000" placeholder="+16309199343|http://a.62-us.com/api/get_sms?key=xxxx"><?= htmlspecialchars($jmwebSettings['yinuocx_inventory'], ENT_QUOTES, 'UTF-8') ?></textarea>
                                </label>
                            </div>
                        </div>
                        <div class="settings-actions inline-actions">
                            <div id="yinuocxSettingsMsg" class="settings-msg"></div>
                            <div class="hero-actions">
                                <button class="btn primary" type="submit">保存一诺CX库存</button>
                            </div>
                        </div>
                    </div>
                </form>
                <div class="cards-workspace">
                    <section class="settings-card card-create-panel">
                        <div class="settings-card-head">
                            <strong>生成一诺CX兑换码</strong>
                            <span>按库存数量生成</span>
                        </div>
                        <form id="yinuocxCardCreateForm" class="card-create-form">
                            <label class="setting-field">制作数量
                                <input name="count" type="number" min="1" max="10000" value="10" placeholder="不能超过当前库存">
                            </label>
                            <button class="btn primary full" type="submit">开始生成</button>
                            <div id="yinuocxCardCreateMsg" class="settings-msg"></div>
                        </form>
                        <div class="card-stats yinuopp-stats-grid" id="yinuocxCardStats">
                            <div><strong>0</strong><span>手机号总数</span></div>
                            <div><strong>0</strong><span>正常可用</span></div>
                            <div><strong>0</strong><span>已用次数</span></div>
                            <div><strong>0</strong><span>接码成功</span></div>
                            <div><strong>0</strong><span>问题号</span></div>
                            <div><strong>0</strong><span>禁用号</span></div>
                            <div><strong>0</strong><span>可用卡密</span></div>
                            <div><strong>0</strong><span>已用卡密</span></div>
                        </div>
                    </section>
                    <div class="yinuopp-detail-stack">
                    <section class="settings-card card-list-panel yinuopp-mode-panel">
                        <div class="card-list-toolbar">
                            <div>
                                <strong>一诺CX详情</strong>
                                <span id="yinuocxModeSummary">请选择查看卡密详情或手机号详情</span>
                            </div>
                            <div class="yinuopp-mode-switch">
                                <button class="btn primary" type="button" data-yinuocx-mode="cards">卡密详情</button>
                                <button class="btn ghost" type="button" data-yinuocx-mode="numbers">手机号详情</button>
                            </div>
                        </div>
                    </section>
                    <section class="settings-card card-list-panel" id="yinuocxCardsPanel">
                        <div class="card-list-toolbar">
                            <div>
                                <strong>一诺CX卡密详情</strong>
                                <span id="yinuocxCardListSummary">一列显示 10 个</span>
                            </div>
                            <div class="card-toolbar-controls">
                                <select id="yinuocxCardLimitSelect">
                                    <option value="10">10</option><option value="50">50</option><option value="100">100</option><option value="500">500</option><option value="1000">1000</option><option value="5000">5000</option><option value="10000">10000</option>
                                </select>
                                <input id="yinuocxCardKeyword" placeholder="搜索卡密">
                            </div>
                        </div>
                        <div class="card-filter-row">
                            <label><input type="checkbox" name="yinuocx_card_status" value="available" checked> 可用</label>
                            <label><input type="checkbox" name="yinuocx_card_status" value="used" checked> 已用</label>
                            <label><input type="checkbox" name="yinuocx_card_status" value="disabled" checked> 禁用</label>
                            <button class="btn ghost" type="button" id="yinuocxCardRefreshBtn">刷新</button>
                        </div>
                        <div class="card-batch-row">
                            <label><input type="checkbox" id="yinuocxCardSelectAll"> 全选当前页</label>
                            <button class="btn ghost" type="button" id="yinuocxCopyCardsBtn">复制卡密</button>
                            <button class="btn ghost" type="button" data-yinuocx-card-batch="enable">启用</button>
                            <button class="btn ghost danger-soft" type="button" data-yinuocx-card-batch="disable">禁用卡密</button>
                            <button class="btn ghost danger-soft" type="button" data-yinuocx-card-batch="delete">删除</button>
                            <span id="yinuocxCardBatchMsg" class="muted">可多选后批量操作</span>
                        </div>
                        <div id="yinuocxCardList" class="card-list empty">正在加载卡密...</div>
                        <div class="card-pager">
                            <button class="btn ghost" type="button" id="yinuocxCardPrevPage">上一页</button>
                            <span id="yinuocxCardPageInfo">1 / 1</span>
                            <button class="btn ghost" type="button" id="yinuocxCardNextPage">下一页</button>
                        </div>
                    </section>
                    <section class="settings-card card-list-panel hidden" id="yinuocxNumbersPanel">
                        <div class="card-list-toolbar">
                            <div>
                                <strong>一诺CX手机号详情</strong>
                                <span id="yinuocxNumberListSummary">一列显示 10 个</span>
                            </div>
                            <div class="card-toolbar-controls">
                                <select id="yinuocxNumberLimitSelect">
                                    <option value="10">10</option><option value="50">50</option><option value="100">100</option><option value="500">500</option><option value="1000">1000</option><option value="5000">5000</option><option value="10000">10000</option>
                                </select>
                                <input id="yinuocxNumberKeyword" placeholder="搜索手机号/API">
                            </div>
                        </div>
                        <div class="card-filter-row">
                            <label><input type="checkbox" name="yinuocx_number_status" value="available" checked> 可用</label>
                            <label><input type="checkbox" name="yinuocx_number_status" value="bad" checked> 问题</label>
                            <label><input type="checkbox" name="yinuocx_number_status" value="disabled" checked> 禁用</label>
                            <button class="btn ghost" type="button" id="yinuocxNumberRefreshBtn">刷新</button>
                        </div>
                        <div class="card-batch-row">
                            <label><input type="checkbox" id="yinuocxNumberSelectAll"> 全选当前页</label>
                            <button class="btn ghost" type="button" data-yinuocx-number-batch="enable">启用</button>
                            <button class="btn ghost danger-soft" type="button" data-yinuocx-number-batch="disable">禁用手机号</button>
                            <button class="btn ghost danger-soft" type="button" data-yinuocx-number-batch="delete">删除手机号</button>
                            <span id="yinuocxNumberBatchMsg" class="muted">取消激活/更换手机号会自动记录为问题号</span>
                        </div>
                        <div id="yinuocxNumberList" class="card-list empty">正在加载手机号...</div>
                        <div class="card-pager">
                            <button class="btn ghost" type="button" id="yinuocxNumberPrevPage">上一页</button>
                            <span id="yinuocxNumberPageInfo">1 / 1</span>
                            <button class="btn ghost" type="button" id="yinuocxNumberNextPage">下一页</button>
                        </div>
                    </section>
                    </div>
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
<script src="../assets/js/admin.js?v=<?= urlencode(JMWEB_VERSION) ?>"></script>
<script>
(function () {
    function postCx(action, payload) {
        var form = new FormData();
        form.append('action', action);
        payload = payload || {};
        Object.keys(payload).forEach(function (key) { form.append(key, payload[key]); });
        return fetch('../api/admin.php', { method: 'POST', body: form, credentials: 'same-origin' }).then(function (response) {
            return response.text();
        }).then(function (text) {
            try { return JSON.parse(text); } catch (e) { return { ok: false, message: '服务器返回异常：' + text.replace(/<[^>]+>/g, ' ').trim().slice(0, 200) }; }
        });
    }
    function text(id, value, type) {
        var el = document.getElementById(id);
        if (!el) return;
        el.textContent = value || '';
        if (type) el.className = 'settings-msg ' + type;
    }
    function esc(value) {
        return String(value == null ? '' : value).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[c]; });
    }
    function cxStatuses(name) {
        var values = [];
        document.querySelectorAll('input[name="' + name + '"]:checked').forEach(function (item) { values.push(item.value); });
        return values.join(',');
    }
    function setCxMode(mode) {
        var cards = document.getElementById('yinuocxCardsPanel');
        var numbers = document.getElementById('yinuocxNumbersPanel');
        var summary = document.getElementById('yinuocxModeSummary');
        if (!cards || !numbers) return;
        var showNumbers = mode === 'numbers';
        cards.classList.toggle('hidden', showNumbers);
        numbers.classList.toggle('hidden', !showNumbers);
        document.querySelectorAll('[data-yinuocx-mode]').forEach(function (button) {
            var active = button.getAttribute('data-yinuocx-mode') === mode;
            button.classList.toggle('primary', active);
            button.classList.toggle('ghost', !active);
        });
        if (summary) summary.textContent = showNumbers ? '当前查看手机号详情，可管理删除手机号。' : '当前查看卡密详情，可复制、启用、禁用或删除卡密。';
        if (showNumbers) loadCxNumbers(true); else loadCxCards(true);
    }
    function renderCxStats(numberStats, cardStats) {
        var box = document.getElementById('yinuocxCardStats');
        if (!box) return;
        numberStats = numberStats || window.__cxNumberStats || {};
        cardStats = cardStats || window.__cxCardStats || {};
        window.__cxNumberStats = numberStats;
        window.__cxCardStats = cardStats;
        var items = [[numberStats.total || 0, '手机号总数'], [numberStats.available || 0, '正常可用'], [numberStats.used || 0, '分配次数'], [numberStats.success || 0, '接码成功'], [numberStats.bad || 0, '问题号'], [numberStats.disabled || 0, '禁用号'], [cardStats.available || 0, '可用卡密'], [cardStats.used || 0, '已用卡密']];
        box.innerHTML = items.map(function (item) { return '<div><strong>' + item[0] + '</strong><span>' + item[1] + '</span></div>'; }).join('');
    }
    function loadCxCards(reset) {
        var list = document.getElementById('yinuocxCardList');
        if (!list) return;
        var limit = document.getElementById('yinuocxCardLimitSelect');
        var keyword = document.getElementById('yinuocxCardKeyword');
        window.__cxCardPage = reset ? 1 : (window.__cxCardPage || 1);
        list.className = 'card-list empty';
        list.textContent = '正在加载卡密...';
        postCx('list_cards', { provider: 'yinuocx', limit: limit ? limit.value : 10, page: window.__cxCardPage, keyword: keyword ? keyword.value : '', statuses: cxStatuses('yinuocx_card_status') }).then(function (result) {
            if (!result.ok) { list.textContent = result.message || '加载失败'; return; }
            window.__cxCardPage = result.page || 1;
            window.__cxCardPages = result.pages || 1;
            var summary = document.getElementById('yinuocxCardListSummary');
            var pageInfo = document.getElementById('yinuocxCardPageInfo');
            if (summary) summary.textContent = '一列显示 ' + (result.limit || (limit ? limit.value : 10)) + ' 个，共 ' + (result.total || 0) + ' 个';
            if (pageInfo) pageInfo.textContent = window.__cxCardPage + ' / ' + window.__cxCardPages;
            renderCxStats(null, result.stats || {});
            var cards = result.cards || [];
            if (!cards.length) { list.textContent = '暂无卡密。'; return; }
            list.className = 'card-list';
            list.innerHTML = cards.map(function (card) {
                return '<label class="card-item status-' + esc(card.status) + '"><input class="yinuocx-card-check" type="checkbox" value="' + esc(card.id) + '" data-card-no="' + esc(card.card_no) + '"><span class="card-no">' + esc(card.card_no) + '</span><span class="card-status">' + esc(card.status_label) + '</span><span class="card-meta"><b>项目ID：</b>' + esc(card.project_id || '-') + '</span><span class="card-meta"><b>手机号：</b>' + esc(card.phone || '-') + '</span><span class="card-meta"><b>验证码：</b>' + esc(card.sms_code || '-') + '</span></label>';
            }).join('');
        });
    }
    function loadCxNumbers(reset) {
        var list = document.getElementById('yinuocxNumberList');
        if (!list) return;
        var limit = document.getElementById('yinuocxNumberLimitSelect');
        var keyword = document.getElementById('yinuocxNumberKeyword');
        window.__cxNumberPage = reset ? 1 : (window.__cxNumberPage || 1);
        list.className = 'card-list empty';
        list.textContent = '正在加载手机号...';
        postCx('list_yinuocx_numbers', { limit: limit ? limit.value : 10, page: window.__cxNumberPage, keyword: keyword ? keyword.value : '', statuses: cxStatuses('yinuocx_number_status') }).then(function (result) {
            if (!result.ok) { list.textContent = result.message || '加载失败'; return; }
            window.__cxNumberPage = result.page || 1;
            window.__cxNumberPages = result.pages || 1;
            var summary = document.getElementById('yinuocxNumberListSummary');
            var pageInfo = document.getElementById('yinuocxNumberPageInfo');
            if (summary) summary.textContent = '一列显示 ' + (result.limit || (limit ? limit.value : 10)) + ' 个，共 ' + (result.total || 0) + ' 个';
            if (pageInfo) pageInfo.textContent = window.__cxNumberPage + ' / ' + window.__cxNumberPages;
            renderCxStats(result.stats || {}, null);
            var nums = result.numbers || [];
            if (!nums.length) { list.textContent = '暂无手机号。'; return; }
            list.className = 'card-list yinuopp-number-table';
            list.innerHTML = '<div class="yinuopp-number-row yinuopp-number-head"><span>选择框</span><span>区号</span><span>手机号</span><span>最近接码</span><span>可用</span><span>接码次数</span><span>分配次数</span><span>问题次数</span></div>' + nums.map(function (n) {
                return '<label class="yinuopp-number-row status-' + esc(n.status || 'available') + '"><span><input class="yinuocx-number-check" type="checkbox" value="' + esc(n.id) + '"></span><span class="number-area">' + esc(n.phone_area || '-') + '</span><span class="number-local">' + esc(n.phone_local || n.phone || '-') + '</span><span>' + esc(n.last_success_at_text || '-') + '</span><span class="card-status">' + esc(n.status_label || '-') + '</span><span>' + esc(n.success_count || 0) + '</span><span>' + esc(n.use_count || 0) + '</span><span>' + esc(n.bad_count || 0) + '</span></label>';
            }).join('');
        });
    }
    document.addEventListener('click', function (event) {
        var pageBtn = event.target.closest && event.target.closest('.side-link[data-page], [data-page].version-jump, .version-stat[data-page]');
        if (pageBtn) {
            var pageName = pageBtn.getAttribute('data-page') || 'dashboard';
            document.querySelectorAll('.admin-page').forEach(function (page) { page.classList.add('hidden'); });
            var target = document.getElementById('page-' + pageName);
            if (target) target.classList.remove('hidden');
            document.querySelectorAll('.side-link[data-page]').forEach(function (item) { item.classList.toggle('active', item.getAttribute('data-page') === pageName); });
            try { localStorage.setItem('jmweb_admin_page', pageName); } catch (e) {}
        }
        var configBtn = event.target.closest && event.target.closest('#toggleYinuocxSettingsBtn');
        if (configBtn) {
            event.preventDefault();
            var form = document.getElementById('yinuocxSettingsForm');
            if (form) { var hidden = form.classList.toggle('hidden'); configBtn.textContent = hidden ? '配置' : '收起配置'; }
        }
        var modeBtn = event.target.closest && event.target.closest('[data-yinuocx-mode]');
        if (modeBtn) { event.preventDefault(); setCxMode(modeBtn.getAttribute('data-yinuocx-mode') || 'cards'); }
        if (event.target.closest && event.target.closest('#yinuocxCardRefreshBtn')) { event.preventDefault(); loadCxCards(true); }
        if (event.target.closest && event.target.closest('#yinuocxNumberRefreshBtn')) { event.preventDefault(); loadCxNumbers(true); }
        if (event.target.closest && event.target.closest('#yinuocxCardPrevPage')) { event.preventDefault(); window.__cxCardPage = Math.max(1, (window.__cxCardPage || 1) - 1); loadCxCards(false); }
        if (event.target.closest && event.target.closest('#yinuocxCardNextPage')) { event.preventDefault(); window.__cxCardPage = Math.min(window.__cxCardPages || 1, (window.__cxCardPage || 1) + 1); loadCxCards(false); }
        if (event.target.closest && event.target.closest('#yinuocxNumberPrevPage')) { event.preventDefault(); window.__cxNumberPage = Math.max(1, (window.__cxNumberPage || 1) - 1); loadCxNumbers(false); }
        if (event.target.closest && event.target.closest('#yinuocxNumberNextPage')) { event.preventDefault(); window.__cxNumberPage = Math.min(window.__cxNumberPages || 1, (window.__cxNumberPage || 1) + 1); loadCxNumbers(false); }
        if (event.target.closest && event.target.closest('#yinuocxCardSelectAll')) {
            document.querySelectorAll('.yinuocx-card-check').forEach(function (item) { item.checked = event.target.checked; });
        }
        if (event.target.closest && event.target.closest('#yinuocxNumberSelectAll')) {
            document.querySelectorAll('.yinuocx-number-check').forEach(function (item) { item.checked = event.target.checked; });
        }
        if (event.target.closest && event.target.closest('#yinuocxCopyCardsBtn')) {
            event.preventDefault();
            var selectedCards = Array.prototype.slice.call(document.querySelectorAll('.yinuocx-card-check:checked')).map(function (item) { return item.getAttribute('data-card-no') || ''; }).filter(Boolean);
            if (!selectedCards.length) { text('yinuocxCardBatchMsg', '请先选择卡密。'); return; }
            var copied = selectedCards.join('\n');
            if (navigator.clipboard && navigator.clipboard.writeText) navigator.clipboard.writeText(copied);
            else {
                var tmp = document.createElement('textarea');
                tmp.value = copied;
                document.body.appendChild(tmp);
                tmp.select();
                document.execCommand('copy');
                document.body.removeChild(tmp);
            }
            var msg = document.getElementById('yinuocxCardBatchMsg');
            if (msg) msg.textContent = '已复制 ' + selectedCards.length + ' 个卡密。';
        }
        var cardBatchBtn = event.target.closest && event.target.closest('[data-yinuocx-card-batch]');
        if (cardBatchBtn) {
            event.preventDefault();
            var cardIds = Array.prototype.slice.call(document.querySelectorAll('.yinuocx-card-check:checked')).map(function (item) { return item.value; });
            var cardAction = cardBatchBtn.getAttribute('data-yinuocx-card-batch') || '';
            if (!cardIds.length) { var cmsg = document.getElementById('yinuocxCardBatchMsg'); if (cmsg) cmsg.textContent = '请先选择卡密。'; return; }
            if (cardAction === 'delete' && !confirm('确定删除选中的卡密吗？')) return;
            if (cardAction === 'disable' && !confirm('确定禁用选中的卡密吗？禁用后前台无法继续使用。')) return;
            postCx('batch_cards', { ids: cardIds.join(','), batch_action: cardAction }).then(function (result) {
                var cmsg = document.getElementById('yinuocxCardBatchMsg');
                if (cmsg) cmsg.textContent = result.message || '操作完成';
                loadCxCards(false);
            });
        }
        var numberBatchBtn = event.target.closest && event.target.closest('[data-yinuocx-number-batch]');
        if (numberBatchBtn) {
            event.preventDefault();
            var numberIds = Array.prototype.slice.call(document.querySelectorAll('.yinuocx-number-check:checked')).map(function (item) { return item.value; });
            var numberAction = numberBatchBtn.getAttribute('data-yinuocx-number-batch') || '';
            if (!numberIds.length) { var nmsg = document.getElementById('yinuocxNumberBatchMsg'); if (nmsg) nmsg.textContent = '请先选择手机号。'; return; }
            if (numberAction === 'delete' && !confirm('确定删除选中的手机号吗？')) return;
            if (numberAction === 'disable' && !confirm('确定禁用选中的手机号吗？')) return;
            postCx('batch_yinuocx_numbers', { ids: numberIds.join(','), batch_action: numberAction }).then(function (result) {
                var nmsg = document.getElementById('yinuocxNumberBatchMsg');
                if (nmsg) nmsg.textContent = result.message || '操作完成';
                loadCxNumbers(false);
            });
        }
    }, true);
    document.addEventListener('submit', function (event) {
        if (event.target && event.target.id === 'yinuocxSettingsForm') {
            event.preventDefault();
            event.stopImmediatePropagation();
            text('yinuocxSettingsMsg', '正在保存一诺CX库存...');
            var payload = {};
            new FormData(event.target).forEach(function (value, key) { payload[key] = value; });
            postCx('save_settings', payload).then(function (result) {
                text('yinuocxSettingsMsg', result.message || (result.ok ? '一诺CX库存已导入。' : '保存失败'), result.ok ? 'success' : 'error');
                if (result.ok && event.target.elements.yinuocx_inventory) event.target.elements.yinuocx_inventory.value = '';
                if (result.ok) { loadCxNumbers(true); loadCxCards(true); }
            });
        }
        if (event.target && event.target.id === 'yinuocxCardCreateForm') {
            event.preventDefault();
            event.stopImmediatePropagation();
            var count = event.target.elements.count ? event.target.elements.count.value : 0;
            text('yinuocxCardCreateMsg', '正在生成一诺CX兑换码...');
            postCx('create_yinuocx_cards', { count: count }).then(function (result) {
                text('yinuocxCardCreateMsg', result.message || '制作完成', result.ok ? 'success' : 'error');
                if (result.ok) loadCxCards(true);
            });
        }
    }, true);
    ['yinuocxCardLimitSelect', 'yinuocxCardKeyword'].forEach(function (id) { var el = document.getElementById(id); if (el) el.addEventListener(id.indexOf('Keyword') > -1 ? 'keydown' : 'change', function (e) { if (e.type === 'change' || e.key === 'Enter') loadCxCards(true); }); });
    ['yinuocxNumberLimitSelect', 'yinuocxNumberKeyword'].forEach(function (id) { var el = document.getElementById(id); if (el) el.addEventListener(id.indexOf('Keyword') > -1 ? 'keydown' : 'change', function (e) { if (e.type === 'change' || e.key === 'Enter') loadCxNumbers(true); }); });
    document.querySelectorAll('input[name="yinuocx_card_status"]').forEach(function (el) { el.addEventListener('change', function () { loadCxCards(true); }); });
    document.querySelectorAll('input[name="yinuocx_number_status"]').forEach(function (el) { el.addEventListener('change', function () { loadCxNumbers(true); }); });
    loadCxCards(true);
    loadCxNumbers(true);
})();
</script>
</body>
</html>
