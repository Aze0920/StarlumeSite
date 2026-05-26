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

const updateBtn = document.getElementById('updateBtn');
if (updateBtn) {
    updateBtn.addEventListener('click', async () => {
        const output = document.getElementById('updateOutput');
        updateBtn.disabled = true;
        output.textContent = '正在执行一键更新，请稍等...';
        try {
            const result = await postAdmin('update');
            output.textContent = `${result.message || ''}\n\n${result.output || ''}`.trim();
        } catch (error) {
            output.textContent = `请求失败：${error.message}`;
        } finally {
            updateBtn.disabled = false;
        }
    });
}
