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

    function formatTimeBySeconds(timestamp) {
        var date = new Date(timestamp * 1000);
        var pad = function (n) { return n < 10 ? '0' + n : String(n); };
        return date.getFullYear() + '/' + pad(date.getMonth() + 1) + '/' + pad(date.getDate()) + ' ' + pad(date.getHours()) + ':' + pad(date.getMinutes()) + ':' + pad(date.getSeconds());
    }

    function phoneDigitsOnly(value) {
        var digits = String(value == null ? '' : value).replace(/[^\d+]/g, '');
        if (digits.indexOf('+') === 0) digits = digits.slice(1);
        else if (digits.indexOf('00') === 0) digits = digits.slice(2);
        return digits.replace(/\D/g, '');
    }

    var phoneCountryCodes = [
        '880', '886', '853', '852', '855', '856', '850', '976', '977', '975', '974', '973', '972', '971', '968', '967', '966', '965', '964', '963', '962', '961', '960',
        '423', '421', '420', '389', '387', '386', '385', '383', '382', '381', '380', '378', '377', '376', '375', '374', '373', '372', '371', '370', '359', '358', '357', '356', '355', '354', '353', '352', '351', '350',
        '998', '996', '995', '994', '993', '992',
        '687', '686', '685', '684', '683', '682', '681', '680', '679', '678', '677', '676', '675', '674', '673', '672', '670', '692', '691', '690', '689', '688',
        '599', '598', '597', '596', '595', '594', '593', '592', '591', '590', '509', '508', '507', '506', '505', '504', '503', '502', '501', '500',
        '91', '90', '86', '84', '82', '81', '66', '65', '64', '63', '62', '61', '60', '58', '57', '56', '55', '54', '53', '52', '51', '49', '48', '47', '46', '45', '44', '43', '41', '40', '39', '36', '34', '33', '32', '31', '30', '27', '20',
        '7', '1'
    ].sort(function (a, b) { return b.length - a.length; });

    function phoneWithoutCountryCode(value) {
        var digits = phoneDigitsOnly(value);
        if (!digits) return '';
        for (var i = 0; i < phoneCountryCodes.length; i++) {
            var code = phoneCountryCodes[i];
            if (digits.indexOf(code) === 0) {
                var local = digits.slice(code.length);
                if (local.length >= 7 && local.length <= 15) return local;
            }
        }
        return digits;
    }

    function detectPhoneCountryCode(value) {
        var digits = phoneDigitsOnly(value);
        if (!digits) return '';
        for (var i = 0; i < phoneCountryCodes.length; i++) {
            var code = phoneCountryCodes[i];
            if (digits.indexOf(code) === 0) {
                var local = digits.slice(code.length);
                if (local.length >= 7 && local.length <= 15) return code;
            }
        }
        return '';
    }

    function formatPhoneDisplay(value) {
        var local = phoneWithoutCountryCode(value);
        if (!local) return '';
        var country = detectPhoneCountryCode(value);
        return country ? ('+' + country + ' ' + local) : local;
    }

    function renderActivation(data) {
        currentActivation = data;
        if (activationPanel) activationPanel.classList.remove('hidden');
        var copyPhone = data.phone || phoneWithoutCountryCode(data.phone_display || '');
        var displayPhone = data.phone_display || formatPhoneDisplay(copyPhone);
        if (phoneNumber) phoneNumber.textContent = displayPhone || '-';
        currentActivation.phone = copyPhone;
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
        if (copyPhoneButton) copyPhoneButton.disabled = !copyPhone;
    }

    function stopPolling() {
        if (pollTimer) clearInterval(pollTimer);
        pollTimer = null;
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

    if (redeemForm) {
        redeemForm.addEventListener('submit', function (event) {
            event.preventDefault();
            var code = voucherInput ? voucherInput.value.trim() : '';
            if (!code) {
                setMessage('请输入兑换码。', 'error');
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
            var value = currentActivation && currentActivation.phone ? currentActivation.phone : phoneWithoutCountryCode(phoneNumber ? phoneNumber.textContent.trim() : '');
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
