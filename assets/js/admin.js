async function postAdmin(action, payload = {}) {
    const form = new FormData();
    form.append('action', action);
    Object.entries(payload).forEach(([key, value]) => form.append(key, value));
    const response = await fetch('../api/admin.php', { method: 'POST', body: form });
    return response.json();
}

const loginForm = document.getElementById('loginForm');
if (loginForm) {
    loginForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const msg = document.getElementById('loginMsg');
        msg.className = 'form-msg';
        msg.textContent = '正在登录...';
        const data = new FormData(loginForm);
        const result = await postAdmin('login', {
            username: data.get('username') || '',
            password: data.get('password') || '',
        });
        msg.textContent = result.message || '登录失败';
        if (result.ok) {
            window.location.reload();
        } else {
            msg.className = 'form-msg error';
        }
    });
}

document.querySelectorAll('.side-link[data-page]').forEach((button) => {
    button.addEventListener('click', () => {
        document.querySelectorAll('.side-link[data-page]').forEach((item) => item.classList.remove('active'));
        button.classList.add('active');
        document.querySelectorAll('.admin-page').forEach((page) => page.classList.add('hidden'));
        document.getElementById(`page-${button.dataset.page}`)?.classList.remove('hidden');
    });
});

const logoutBtn = document.getElementById('logoutBtn');
if (logoutBtn) {
    logoutBtn.addEventListener('click', async () => {
        await postAdmin('logout');
        window.location.reload();
    });
}

const checkUpdateBtn = document.getElementById('checkUpdateBtn');
const updateBtn = document.getElementById('updateBtn');
const updateOutput = document.getElementById('updateOutput');
const remoteVersion = document.getElementById('remoteVersion');

if (checkUpdateBtn) {
    checkUpdateBtn.addEventListener('click', async () => {
        checkUpdateBtn.disabled = true;
        updateBtn?.classList.add('hidden');
        if (updateOutput) updateOutput.textContent = '正在检查远程版本，请稍等...';
        try {
            const result = await postAdmin('check_update');
            if (remoteVersion) {
                remoteVersion.textContent = result.remote_version ? `v${result.remote_version}` : '检查失败';
            }
            const lines = [
                result.message || '检查完成',
                `当前版本：${result.current_version || '-'}`,
                `远程版本：${result.remote_version || '-'}`,
            ];
            if (result.release_date) lines.push(`发布日期：${result.release_date}`);
            if (result.description) lines.push(`更新说明：${result.description}`);
            if (updateOutput) updateOutput.textContent = lines.join('\n');
            if (result.ok && result.has_update && updateBtn) {
                updateBtn.classList.remove('hidden');
            }
        } catch (error) {
            if (updateOutput) updateOutput.textContent = `检查失败：${error.message}`;
        } finally {
            checkUpdateBtn.disabled = false;
        }
    });
}

if (updateBtn) {
    updateBtn.addEventListener('click', async () => {
        updateBtn.disabled = true;
        if (updateOutput) updateOutput.textContent = '正在执行更新，请稍等...';
        try {
            const result = await postAdmin('update');
            if (updateOutput) updateOutput.textContent = `${result.message || ''}\n\n${result.output || ''}`.trim();
        } catch (error) {
            if (updateOutput) updateOutput.textContent = `请求失败：${error.message}`;
        } finally {
            updateBtn.disabled = false;
        }
    });
}
