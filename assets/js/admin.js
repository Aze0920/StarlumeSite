async function postAdmin(action, payload) {
    payload = payload || {};
    var form = new FormData();
    form.append('action', action);
    Object.keys(payload).forEach(function (key) {
        form.append(key, payload[key]);
    });

    var response = await fetch('../api/admin.php', { method: 'POST', body: form, credentials: 'same-origin' });
    var text = await response.text();
    try {
        return JSON.parse(text);
    } catch (error) {
        var preview = text.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim().slice(0, 260);
        return {
            ok: false,
            message: preview ? '服务器返回的不是 JSON：' + preview : '服务器返回为空，请检查 PHP 错误日志。',
            output: text || '',
        };
    }
}

function setText(el, text) {
    if (el) el.textContent = text;
}

function setSettingsMessage(text, type) {
    var el = document.getElementById('settingsMsg');
    if (!el) {
        if (text) alert(text);
        return;
    }
    el.textContent = text || '';
    el.className = 'settings-msg' + (type ? ' ' + type : '');
}

function setCardMessage(el, text, type) {
    if (!el) return;
    el.textContent = text || '';
    el.className = 'settings-msg' + (type ? ' ' + type : '');
}

function showAdminPage(pageName, saveHash) {
    var target = document.getElementById('page-' + pageName);
    if (!target) pageName = 'dashboard';

    document.querySelectorAll('.side-link[data-page]').forEach(function (item) {
        item.classList.toggle('active', item.getAttribute('data-page') === pageName);
    });
    document.querySelectorAll('.admin-page').forEach(function (page) {
        page.classList.add('hidden');
    });

    target = document.getElementById('page-' + pageName);
    if (target) target.classList.remove('hidden');

    if (saveHash !== false) {
        window.location.hash = pageName;
        try { localStorage.setItem('jmweb_admin_page', pageName); } catch (e) {}
    }
}

function initAdminPageFromHash() {
    var pageName = window.location.hash ? window.location.hash.replace('#', '') : '';
    if (!pageName) {
        try { pageName = localStorage.getItem('jmweb_admin_page') || ''; } catch (e) { pageName = ''; }
    }
    showAdminPage(pageName || 'dashboard', false);
}

var loginForm = document.getElementById('loginForm');
if (loginForm) {
    loginForm.addEventListener('submit', async function (event) {
        event.preventDefault();
        var msg = document.getElementById('loginMsg');
        if (msg) msg.className = 'form-msg';
        setText(msg, '正在登录...');
        var data = new FormData(loginForm);
        var result = await postAdmin('login', {
            username: data.get('username') || '',
            password: data.get('password') || '',
        });
        setText(msg, result.message || '登录失败');
        if (result.ok) {
            window.location.reload();
        } else if (msg) {
            msg.className = 'form-msg error';
        }
    });
}

document.querySelectorAll('.side-link[data-page], [data-page].version-jump, .version-stat[data-page]').forEach(function (button) {
    button.addEventListener('click', function () {
        showAdminPage(button.getAttribute('data-page') || 'dashboard', true);
    });
});

window.addEventListener('hashchange', function () {
    initAdminPageFromHash();
});
initAdminPageFromHash();

var logoutBtn = document.getElementById('logoutBtn');
if (logoutBtn) {
    logoutBtn.addEventListener('click', async function () {
        await postAdmin('logout');
        try { localStorage.removeItem('jmweb_admin_page'); } catch (e) {}
        window.location.href = '../admin/';
    });
}

var checkUpdateBtn = document.getElementById('checkUpdateBtn');
var updateBtn = document.getElementById('updateBtn');
var updateOutput = document.getElementById('updateOutput');
var remoteVersion = document.getElementById('remoteVersion');

if (checkUpdateBtn) {
    checkUpdateBtn.addEventListener('click', async function () {
        checkUpdateBtn.disabled = true;
        checkUpdateBtn.textContent = '检查中...';
        if (updateBtn) updateBtn.classList.add('hidden');
        setText(updateOutput, '正在检查远程版本，请稍等...');
        try {
            var result = await postAdmin('check_update');
            setText(remoteVersion, result.remote_version ? 'v' + result.remote_version : '检查失败');

            var lines = [
                result.message || '检查完成',
                '当前版本：' + (result.current_version || '-'),
                '远程版本：' + (result.remote_version || '-'),
            ];
            if (result.release_date) lines.push('发布日期：' + result.release_date);
            if (result.description) lines.push('更新说明：' + result.description);
            setText(updateOutput, lines.join('\n'));

            if (result.ok && result.has_update && updateBtn) {
                updateBtn.classList.remove('hidden');
            }
        } catch (error) {
            setText(updateOutput, '检查失败：' + error.message);
        } finally {
            checkUpdateBtn.disabled = false;
            checkUpdateBtn.textContent = '检查更新';
        }
    });
}

if (updateBtn) {
    updateBtn.addEventListener('click', async function () {
        updateBtn.disabled = true;
        updateBtn.textContent = '更新中...';
        setText(updateOutput, '正在执行更新，请稍等...');
        try {
            var result = await postAdmin('update');
            var updateLines = [];
            updateLines.push(result.message || '更新请求完成');
            if (result.log_path) updateLines.push('日志文件：' + result.log_path);
            if (result.output) updateLines.push('\n' + result.output);
            else if (result.log) updateLines.push('\n' + result.log);
            else if (result.php_output) updateLines.push('\nPHP 输出：\n' + result.php_output);
            setText(updateOutput, updateLines.join('\n'));
        } catch (error) {
            setText(updateOutput, '请求失败：' + error.message);
        } finally {
            updateBtn.disabled = false;
            updateBtn.textContent = '立即更新';
        }
    });
}

function fillSettingsForm(settings) {
    var form = document.getElementById('settingsForm');
    if (!form || !settings) return;
    Object.keys(settings).forEach(function (key) {
        if (!form.elements[key]) return;
        if (key === 'haozhu_api_password') {
            form.elements[key].value = '';
            form.elements[key].placeholder = settings.haozhu_api_password_saved ? '已保存，留空不修改' : '请输入 API 密码';
            return;
        }
        form.elements[key].value = settings[key];
    });
    if (settings.site_name) {
        document.querySelectorAll('[data-setting-display="site_name"]').forEach(function (item) {
            item.textContent = settings.site_name;
        });
        document.title = settings.site_name + ' 管理后台';
    }
}

var settingsForm = document.getElementById('settingsForm');
var settingsMsg = document.getElementById('settingsMsg');
var resetSettingsBtn = document.getElementById('resetSettingsBtn');

if (settingsForm) {
    settingsForm.addEventListener('submit', async function (event) {
        event.preventDefault();
        var submitButton = settingsForm.querySelector('button[type="submit"]');
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.textContent = '保存中...';
        }
        setSettingsMessage('正在保存设置...');
        var formData = new FormData(settingsForm);
        var payload = {};
        formData.forEach(function (value, key) { payload[key] = value; });
        try {
            var result = await postAdmin('save_settings', payload);
            setSettingsMessage(result.message || '保存完成', result.ok ? 'success' : 'error');
            if (result.ok && result.settings) fillSettingsForm(result.settings);
        } catch (error) {
            setSettingsMessage('保存失败：' + error.message, 'error');
        } finally {
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.textContent = '保存设置';
            }
        }
    });
}

if (resetSettingsBtn) {
    resetSettingsBtn.addEventListener('click', async function () {
        resetSettingsBtn.disabled = true;
        resetSettingsBtn.textContent = '恢复中...';
        setSettingsMessage('正在恢复默认设置...');
        try {
            var result = await postAdmin('reset_settings');
            setSettingsMessage(result.message || '已恢复默认', result.ok ? 'success' : 'error');
            if (result.ok && result.settings) fillSettingsForm(result.settings);
        } catch (error) {
            setSettingsMessage('恢复失败：' + error.message, 'error');
        } finally {
            resetSettingsBtn.disabled = false;
            resetSettingsBtn.textContent = '恢复默认';
        }
    });
}

var cardState = {
    page: 1,
    pages: 1,
    limit: 10,
};
var cardCacheKey = 'jmweb_card_filters';

function saveCardFilters() {
    var limitSelect = document.getElementById('cardLimitSelect');
    var keywordInput = document.getElementById('cardKeyword');
    try {
        localStorage.setItem(cardCacheKey, JSON.stringify({
            statuses: getCardStatuses(),
            limit: limitSelect ? limitSelect.value : '10',
            keyword: keywordInput ? keywordInput.value : '',
        }));
    } catch (e) {}
}

function restoreCardFilters() {
    var raw = '';
    try { raw = localStorage.getItem(cardCacheKey) || ''; } catch (e) { raw = ''; }
    if (!raw) return;
    try {
        var data = JSON.parse(raw);
        var savedStatuses = Array.isArray(data.statuses) ? data.statuses : [];
        document.querySelectorAll('input[name="card_status"]').forEach(function (item) {
            item.checked = savedStatuses.indexOf(item.value) !== -1;
        });
        var limitSelect = document.getElementById('cardLimitSelect');
        if (limitSelect && data.limit) limitSelect.value = data.limit;
        var keywordInput = document.getElementById('cardKeyword');
        if (keywordInput && typeof data.keyword === 'string') keywordInput.value = data.keyword;
    } catch (e) {}
}

function getCardStatuses() {
    var statuses = [];
    document.querySelectorAll('input[name="card_status"]:checked').forEach(function (item) {
        statuses.push(item.value);
    });
    return statuses;
}

function selectedCardIds() {
    var ids = [];
    document.querySelectorAll('.card-check:checked').forEach(function (item) {
        ids.push(item.value);
    });
    return ids;
}

function selectedCardNos() {
    return Array.prototype.slice.call(document.querySelectorAll('.card-check:checked')).map(function (item) {
        return item.getAttribute('data-card-no') || '';
    }).filter(Boolean);
}

function escapeHtml(value) {
    return String(value == null ? '' : value).replace(/[&<>"']/g, function (char) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[char];
    });
}

function copyText(text) {
    if (navigator.clipboard && window.isSecureContext) {
        return navigator.clipboard.writeText(text);
    }
    var textarea = document.createElement('textarea');
    textarea.value = text;
    textarea.style.position = 'fixed';
    textarea.style.left = '-9999px';
    textarea.style.top = '-9999px';
    document.body.appendChild(textarea);
    textarea.focus();
    textarea.select();
    var ok = document.execCommand('copy');
    document.body.removeChild(textarea);
    return ok ? Promise.resolve() : Promise.reject(new Error('复制失败'));
}

function renderCardStats(stats) {
    var el = document.getElementById('cardStats');
    if (!el || !stats) return;
    el.innerHTML = '';
    [
        ['total', '全部'],
        ['available', '可用'],
        ['used', '已用'],
        ['disabled', '禁用'],
    ].forEach(function (item) {
        var box = document.createElement('div');
        box.innerHTML = '<strong>' + (stats[item[0]] || 0) + '</strong><span>' + item[1] + '</span>';
        el.appendChild(box);
    });
}

function renderCards(cards) {
    var list = document.getElementById('cardList');
    if (!list) return;
    list.innerHTML = '';
    if (!cards || !cards.length) {
        list.className = 'card-list empty';
        list.textContent = '暂无卡密。';
        return;
    }
    list.className = 'card-list';
    cards.forEach(function (card) {
        var item = document.createElement('label');
        item.className = 'card-item status-' + card.status;
        item.setAttribute('data-card-item', String(card.id));
        item.innerHTML = '<input class="card-check" type="checkbox" value="' + card.id + '" data-card-no="' + escapeHtml(card.card_no) + '">' +
            '<span class="card-no">' + escapeHtml(card.card_no) + '</span>' +
            '<span class="card-status">' + escapeHtml(card.status_label) + '</span>' +
            '<span class="card-meta"><b>项目ID：</b>' + escapeHtml(card.project_id || '-') + '</span>' +
            '<span class="card-meta"><b>手机号：</b>' + escapeHtml(card.phone || '-') + '</span>' +
            '<span class="card-meta"><b>验证码：</b>' + escapeHtml(card.sms_code || '-') + '</span>';
        list.appendChild(item);
    });
    bindCardDragSelect(list);
}

function bindCardDragSelect(list) {
    if (!list || list.getAttribute('data-drag-bound') === '1') return;
    list.setAttribute('data-drag-bound', '1');
    var dragging = false;
    var dragValue = true;
    function setItemChecked(target) {
        var item = target && target.closest ? target.closest('.card-item') : null;
        if (!item || !list.contains(item)) return;
        var checkbox = item.querySelector('.card-check');
        if (!checkbox) return;
        checkbox.checked = dragValue;
        item.classList.toggle('is-selected', checkbox.checked);
    }
    list.addEventListener('mousedown', function (event) {
        var item = event.target.closest ? event.target.closest('.card-item') : null;
        if (!item || !list.contains(item)) return;
        var checkbox = item.querySelector('.card-check');
        if (!checkbox) return;
        dragging = true;
        dragValue = !checkbox.checked;
        setItemChecked(item);
        event.preventDefault();
    });
    list.addEventListener('mouseover', function (event) {
        if (!dragging) return;
        setItemChecked(event.target);
    });
    document.addEventListener('mouseup', function () {
        dragging = false;
    });
    list.addEventListener('change', function (event) {
        if (!event.target.classList.contains('card-check')) return;
        var item = event.target.closest('.card-item');
        if (item) item.classList.toggle('is-selected', event.target.checked);
    });
}

async function loadCards(resetPage) {
    var list = document.getElementById('cardList');
    var limitSelect = document.getElementById('cardLimitSelect');
    var keywordInput = document.getElementById('cardKeyword');
    var summary = document.getElementById('cardListSummary');
    var pageInfo = document.getElementById('cardPageInfo');
    var selectAll = document.getElementById('cardSelectAll');
    if (!list || !limitSelect) return;
    if (resetPage) cardState.page = 1;
    cardState.limit = parseInt(limitSelect.value, 10) || 10;
    list.className = 'card-list empty';
    list.textContent = '正在加载卡密...';
    if (selectAll) selectAll.checked = false;
    try {
        var result = await postAdmin('list_cards', {
            limit: cardState.limit,
            page: cardState.page,
            keyword: keywordInput ? keywordInput.value : '',
            statuses: getCardStatuses().join(','),
        });
        if (!result.ok) {
            list.textContent = result.message || '加载失败';
            return;
        }
        cardState.page = result.page || 1;
        cardState.pages = result.pages || 1;
        renderCards(result.cards || []);
        renderCardStats(result.stats || {});
        if (summary) summary.textContent = '一列显示 ' + (result.limit || cardState.limit) + ' 个，共 ' + (result.total || 0) + ' 个';
        if (pageInfo) pageInfo.textContent = cardState.page + ' / ' + cardState.pages;
    } catch (error) {
        list.textContent = '加载失败：' + error.message;
    }
}

var cardCreateForm = document.getElementById('cardCreateForm');
if (cardCreateForm) {
    restoreCardFilters();
    loadCards(true);
    cardCreateForm.addEventListener('submit', async function (event) {
        event.preventDefault();
        var msg = document.getElementById('cardCreateMsg');
        var button = cardCreateForm.querySelector('button[type="submit"]');
        var data = new FormData(cardCreateForm);
        var projectId = (data.get('project_id') || '').toString().trim();
        var count = parseInt(data.get('count'), 10) || 0;
        if (!/^\d{1,20}$/.test(projectId)) {
            setCardMessage(msg, '请输入正确的项目ID，只能是数字。', 'error');
            return;
        }
        if (count < 1 || count > 10000) {
            setCardMessage(msg, '制作数量必须在 1 - 10000 之间。', 'error');
            return;
        }
        if (button) {
            button.disabled = true;
            button.textContent = '制作中...';
        }
        setCardMessage(msg, '正在制作卡密，请稍等...');
        try {
            var result = await postAdmin('create_cards', { count: count, project_id: projectId });
            setCardMessage(msg, result.message || '制作完成', result.ok ? 'success' : 'error');
            if (result.ok) loadCards(true);
        } catch (error) {
            setCardMessage(msg, '制作失败：' + error.message, 'error');
        } finally {
            if (button) {
                button.disabled = false;
                button.textContent = '开始制作';
            }
        }
    });
}

var checkProjectBtn = document.getElementById('checkProjectBtn');
if (checkProjectBtn) {
    checkProjectBtn.addEventListener('click', async function () {
        var msg = document.getElementById('cardCreateMsg');
        var input = document.getElementById('cardProjectId');
        var projectId = input ? input.value.trim() : '';
        if (!/^\d{1,20}$/.test(projectId)) {
            setCardMessage(msg, '请输入正确的项目ID，只能是数字。', 'error');
            return;
        }
        checkProjectBtn.disabled = true;
        checkProjectBtn.textContent = '检测中...';
        setCardMessage(msg, '正在登录豪猪码并测试取号，请稍等...');
        try {
            var result = await postAdmin('check_haozhu_project', { project_id: projectId });
            setCardMessage(msg, result.message || '检测完成', result.ok ? 'success' : 'error');
        } catch (error) {
            setCardMessage(msg, '检测失败：' + error.message, 'error');
        } finally {
            checkProjectBtn.disabled = false;
            checkProjectBtn.textContent = '检测项目ID';
        }
    });
}

var cardLimitSelect = document.getElementById('cardLimitSelect');
if (cardLimitSelect) cardLimitSelect.addEventListener('change', function () { saveCardFilters(); loadCards(true); });
var cardRefreshBtn = document.getElementById('cardRefreshBtn');
if (cardRefreshBtn) cardRefreshBtn.addEventListener('click', function () { saveCardFilters(); loadCards(true); });
var cardKeyword = document.getElementById('cardKeyword');
if (cardKeyword) cardKeyword.addEventListener('keydown', function (event) { if (event.key === 'Enter') { saveCardFilters(); loadCards(true); } });
document.querySelectorAll('input[name="card_status"]').forEach(function (item) {
    item.addEventListener('change', function () { saveCardFilters(); loadCards(true); });
});
var cardPrevPage = document.getElementById('cardPrevPage');
if (cardPrevPage) cardPrevPage.addEventListener('click', function () {
    if (cardState.page > 1) {
        cardState.page--;
        loadCards(false);
    }
});
var cardNextPage = document.getElementById('cardNextPage');
if (cardNextPage) cardNextPage.addEventListener('click', function () {
    if (cardState.page < cardState.pages) {
        cardState.page++;
        loadCards(false);
    }
});
var cardSelectAll = document.getElementById('cardSelectAll');
if (cardSelectAll) cardSelectAll.addEventListener('change', function () {
    document.querySelectorAll('.card-check').forEach(function (item) {
        item.checked = cardSelectAll.checked;
        var row = item.closest('.card-item');
        if (row) row.classList.toggle('is-selected', item.checked);
    });
});
var copyCardsBtn = document.getElementById('copyCardsBtn');
if (copyCardsBtn) copyCardsBtn.addEventListener('click', async function () {
    var nos = selectedCardNos();
    var msg = document.getElementById('cardBatchMsg');
    if (!nos.length) {
        setText(msg, '请先选择要复制的卡密。');
        return;
    }
    copyCardsBtn.disabled = true;
    try {
        await copyText(nos.join('\n'));
        setText(msg, '已复制 ' + nos.length + ' 条卡密。');
    } catch (error) {
        setText(msg, '复制失败，请手动复制。');
    } finally {
        copyCardsBtn.disabled = false;
    }
});
document.querySelectorAll('[data-card-batch]').forEach(function (button) {
    button.addEventListener('click', async function () {
        var ids = selectedCardIds();
        var msg = document.getElementById('cardBatchMsg');
        var action = button.getAttribute('data-card-batch');
        if (!ids.length) {
            setText(msg, '请先选择卡密。');
            return;
        }
        if (action === 'delete' && !confirm('确定删除选中的卡密吗？')) {
            return;
        }
        button.disabled = true;
        setText(msg, '正在操作...');
        try {
            var result = await postAdmin('batch_cards', { ids: ids.join(','), batch_action: action });
            setText(msg, result.message || '操作完成');
            loadCards(false);
        } catch (error) {
            setText(msg, '操作失败：' + error.message);
        } finally {
            button.disabled = false;
        }
    });
});
