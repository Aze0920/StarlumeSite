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
        if (form.elements[key]) form.elements[key].value = settings[key];
    });
    if (settings.site_name) {
        document.querySelectorAll('[data-setting-display="site_name"]').forEach(function (item) {
            item.textContent = settings.site_name;
        });
        document.title = settings.site_name + ' 管理后台';
    }
}

var settingsForm = document.getElementById('settingsForm');
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
