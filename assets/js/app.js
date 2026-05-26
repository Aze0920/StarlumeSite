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
    var copyPhoneButton = document.getElementById('copy-phone-button');
    var refreshStatus = document.getElementById('refresh-status');
    var cancelActivation = document.getElementById('cancel-activation');
    var cancelCountdown = document.getElementById('cancel-countdown');

    var currentActivation = null;
    var countdownTimer = null;

    function setMessage(text, type) {
        if (!redeemMessage) return;
        redeemMessage.textContent = text || '';
        redeemMessage.className = 'message' + (type ? ' ' + type : '');
    }

    function formatVoucher(value) {
        return value.replace(/[^a-zA-Z0-9]/g, '').toUpperCase().slice(0, 16).replace(/(.{4})/g, '$1-').replace(/-$/, '');
    }

    function formatTime(date) {
        var pad = function (n) { return n < 10 ? '0' + n : String(n); };
        return date.getFullYear() + '/' + pad(date.getMonth() + 1) + '/' + pad(date.getDate()) + ' ' + pad(date.getHours()) + ':' + pad(date.getMinutes()) + ':' + pad(date.getSeconds());
    }

    function renderActivation(data) {
        currentActivation = data;
        if (activationPanel) activationPanel.classList.remove('hidden');
        if (phoneNumber) phoneNumber.textContent = data.phone || '-';
        if (activationState) activationState.textContent = data.state || '-';
        if (activationCode) activationCode.textContent = data.code || '等待中';
        if (activationExpiry) activationExpiry.textContent = data.expiryText || '-';
        if (copyPhoneButton) copyPhoneButton.disabled = !data.phone;
        startCancelCountdown(data.cancelAvailableAt || 0);
    }

    function startCancelCountdown(availableAt) {
        if (countdownTimer) clearInterval(countdownTimer);
        var tick = function () {
            var remain = Math.max(0, Math.ceil((availableAt - Date.now()) / 1000));
            if (cancelActivation) cancelActivation.disabled = remain > 0;
            if (cancelCountdown) cancelCountdown.textContent = remain > 0 ? remain + ' 秒后可取消' : '';
            if (remain <= 0 && countdownTimer) {
                clearInterval(countdownTimer);
                countdownTimer = null;
            }
        };
        tick();
        countdownTimer = setInterval(tick, 1000);
    }

    if (voucherInput) {
        voucherInput.addEventListener('input', function () {
            voucherInput.value = formatVoucher(voucherInput.value);
        });
    }

    if (redeemForm) {
        redeemForm.addEventListener('submit', function (event) {
            event.preventDefault();
            var code = voucherInput ? voucherInput.value.trim() : '';
            if (!code) {
                setMessage('请输入兑换码。', 'error');
                return;
            }
            if (code.replace(/-/g, '').length < 8) {
                setMessage('兑换码格式不正确。', 'error');
                return;
            }

            if (redeemSubmit) {
                redeemSubmit.disabled = true;
                redeemSubmit.textContent = '验证中...';
            }
            setMessage('兑换功能接口待接入，当前先展示手机号状态面板。', 'success');

            var expiry = new Date(Date.now() + 8 * 60 * 1000);
            renderActivation({
                phone: '-',
                state: '待接入',
                code: '等待中',
                expiryText: formatTime(expiry),
                cancelAvailableAt: Date.now() + 2 * 60 * 1000,
            });

            setTimeout(function () {
                if (redeemSubmit) {
                    redeemSubmit.disabled = false;
                    redeemSubmit.textContent = '开始验证';
                }
            }, 400);
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
            setMessage('状态刷新接口待接入。', 'info');
            if (currentActivation) renderActivation(currentActivation);
        });
    }

    if (cancelActivation) {
        cancelActivation.addEventListener('click', function () {
            setMessage('取消激活接口待接入。', 'info');
        });
    }
})();
