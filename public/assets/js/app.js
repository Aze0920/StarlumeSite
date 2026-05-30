(function () {
    var redeemForm = document.getElementById('redeem-form');
    var voucherInput = document.getElementById('voucher-code');
    var redeemSubmit = document.getElementById('redeem-submit');
    var redeemMessage = document.getElementById('redeem-message');
    var activationPanel = document.getElementById('activation-panel');
    var phoneNumber = document.getElementById('phone-number');
    var activationState = document.getElementById('activation-state');
    var activationCode = document.getElementById('activation-code');
    var activationExpiry = document.getElementById('activation-expiry');
    var activationTimeLabel = document.getElementById('activation-time-label');
    var copyPhoneButton = document.getElementById('copy-phone-button');
    var refreshStatus = document.getElementById('refresh-status');
    var cancelActivation = document.getElementById('cancel-activation');
    var cancelCountdown = document.getElementById('cancel-countdown');

    var currentActivation = null;
    var countdownTimer = null;
    var pollTimer = null;

    function postPublic(action, payload) {
        payload = payload || {};
        var form = new FormData();
        form.append('action', action);
        Object.keys(payload).forEach(function (key) { form.append(key, payload[key]); });
        return fetch('/api/admin.php', { method: 'POST', body: form, credentials: 'same-origin' }).then(function (response) {
            return response.text();
        }).then(function (text) {
            try {
                return JSON.parse(text);
            } catch (error) {
                var preview = text.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim().slice(0, 260);
                return { ok: false, message: preview ? '服务器返回异常：' + preview : '服务器返回为空。' };
            }
        });
    }

    function setMessage(text, type) {
        if (!redeemMessage) return;
        redeemMessage.textContent = text || '';
        redeemMessage.className = 'message' + (type ? ' ' + type : '');
    }

    function formatVoucher(value) {
        var clean = value.replace(/[^a-zA-Z0-9]/g, '').toUpperCase();
        var prefix = 'HZ';
        if (clean.indexOf('LB') === 0) {
            prefix = 'LB';
            clean = clean.slice(2);
        } else if (clean.indexOf('HZ') === 0) {
            prefix = 'HZ';
            clean = clean.slice(2);
        }
        clean = clean.slice(0, 32);
        var project = clean.length > 12 ? clean.slice(0, clean.length - 12) : '';
        var tail = clean.slice(Math.max(0, clean.length - 12));
        var groups = [];
        if (project) groups.push(project);
        tail.replace(/.{1,4}/g, function (part) { groups.push(part); return part; });
        return prefix + (groups.length ? '-' + groups.join('-') : '');
    }

    function formatTimeBySeconds(timestamp) {
        var date = new Date(timestamp * 1000);
        var pad = function (n) { return n < 10 ? '0' + n : String(n); };
        return date.getFullYear() + '/' + pad(date.getMonth() + 1) + '/' + pad(date.getDate()) + ' ' + pad(date.getHours()) + ':' + pad(date.getMinutes()) + ':' + pad(date.getSeconds());
    }

    function stopPolling() {
        if (pollTimer) clearInterval(pollTimer);
        pollTimer = null;
    }

    function renderActivation(data) {
        currentActivation = data;
        if (activationPanel) activationPanel.classList.remove('hidden');
        if (phoneNumber) phoneNumber.textContent = data.phone || '-';
        if (activationState) activationState.textContent = data.state || '-';
        if (activationCode) activationCode.textContent = data.code || '等待中';
        if (data.is_used || data.received_at) {
            if (activationTimeLabel) activationTimeLabel.textContent = '接码时间';
            if (activationExpiry) activationExpiry.textContent = data.received_at ? formatTimeBySeconds(data.received_at) : '-';
            if (cancelActivation) {
                cancelActivation.disabled = true;
                cancelActivation.textContent = '已激活';
            }
            if (cancelCountdown) cancelCountdown.textContent = '';
            stopPolling();
            if (countdownTimer) clearInterval(countdownTimer);
            countdownTimer = null;
        } else {
            if (activationTimeLabel) activationTimeLabel.textContent = '到期时间';
            if (activationExpiry) activationExpiry.textContent = data.expires_at ? formatTimeBySeconds(data.expires_at) : '-';
            if (cancelActivation) cancelActivation.textContent = '取消激活';
            startCountdown(data.expires_at || 0);
        }
        if (copyPhoneButton) copyPhoneButton.disabled = !data.phone;
    }

    function startCountdown(expiresAt) {
        if (countdownTimer) clearInterval(countdownTimer);
        var tick = function () {
            var remain = Math.max(0, (expiresAt || 0) - Math.floor(Date.now() / 1000));
            if (cancelActivation) cancelActivation.disabled = remain > 0;
            if (cancelCountdown) cancelCountdown.textContent = remain > 0 ? remain + ' 秒后可更换号码' : '可以更换号码';
            if (remain <= 0) {
                stopPolling();
                if (activationState && currentActivation && !currentActivation.code) activationState.textContent = '已超时';
                if (countdownTimer) clearInterval(countdownTimer);
                countdownTimer = null;
            }
        };
        tick();
        countdownTimer = setInterval(tick, 1000);
    }

    function pollCode(manual) {
        if (!currentActivation || !currentActivation.card_id) return;
        return postPublic('poll_code', { card_id: currentActivation.card_id }).then(function (result) {
            if (result.activation) renderActivation(result.activation);
            if (result.received) {
                stopPolling();
                setMessage(result.message || '已收到验证码。', 'success');
                return;
            }
            if (result.expired) {
                stopPolling();
                setMessage(result.message || '240 秒已到，可以更换号码。', 'info');
                return;
            }
            if (manual) setMessage(result.message || '暂未收到验证码。', 'info');
        }).catch(function (error) {
            if (manual) setMessage('刷新失败：' + error.message, 'error');
        });
    }

    function startPolling() {
        stopPolling();
        pollCode(false);
        pollTimer = setInterval(function () { pollCode(false); }, 5000);
    }

    if (voucherInput) {
        voucherInput.addEventListener('input', function () {
            voucherInput.value = formatVoucher(voucherInput.value);
        });
    }

    if (redeemForm) {
        redeemForm.addEventListener('submit', function (event) {
            event.preventDefault();
            var code = voucherInput ? formatVoucher(voucherInput.value.trim()) : '';
            if (voucherInput) voucherInput.value = code;
            if (!code) {
                setMessage('请输入兑换码。', 'error');
                return;
            }
            if (code.replace(/-/g, '').length < 15) {
                setMessage('兑换码格式不正确。', 'error');
                return;
            }
            if (redeemSubmit) {
                redeemSubmit.disabled = true;
                redeemSubmit.textContent = '获取中...';
            }
            setMessage('正在获取手机号，请稍等...', 'info');
            stopPolling();
            postPublic('redeem_card', { code: code }).then(function (result) {
                if (!result.ok) {
                    setMessage(result.message || '获取失败。', 'error');
                    return;
                }
                renderActivation(result.activation || {});
                setMessage(result.message || '已获取手机号，240 秒内持续获取验证码。', result.received || result.used ? 'success' : 'success');
                if (!result.received && !result.used) startPolling();
            }).catch(function (error) {
                setMessage('请求失败：' + error.message, 'error');
            }).finally(function () {
                if (redeemSubmit) {
                    redeemSubmit.disabled = false;
                    redeemSubmit.textContent = '开始验证';
                }
            });
        });
    }

    if (copyPhoneButton) {
        copyPhoneButton.addEventListener('click', function () {
            var value = phoneNumber ? phoneNumber.textContent.trim() : '';
            if (!value || value === '-') return;
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(value).then(function () {
                    setMessage('手机号已复制。', 'success');
                });
            }
        });
    }

    if (refreshStatus) {
        refreshStatus.addEventListener('click', function () {
            pollCode(true);
        });
    }

    if (cancelActivation) {
        cancelActivation.addEventListener('click', function () {
            if (!currentActivation || !currentActivation.card_id) return;
            cancelActivation.disabled = true;
            setMessage('正在取消当前号码...', 'info');
            postPublic('cancel_activation', { card_id: currentActivation.card_id }).then(function (result) {
                if (result.activation) renderActivation(result.activation);
                setMessage(result.message || '操作完成。', result.ok ? 'success' : 'error');
                if (result.ok) {
                    stopPolling();
                    if (phoneNumber) phoneNumber.textContent = '-';
                    if (activationState) activationState.textContent = '已取消';
                    if (activationCode) activationCode.textContent = '等待中';
                    if (activationExpiry) activationExpiry.textContent = '-';
                    if (activationTimeLabel) activationTimeLabel.textContent = '到期时间';
                    if (copyPhoneButton) copyPhoneButton.disabled = true;
                }
            }).catch(function (error) {
                setMessage('取消失败：' + error.message, 'error');
            }).finally(function () {
                cancelActivation.disabled = false;
            });
        });
    }
})();
