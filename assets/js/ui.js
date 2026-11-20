/**
 * ui.js — Hệ thông báo & hộp thoại dùng chung (thay alert/confirm/prompt của trình duyệt)
 *
 *   UI.success('Đã lưu')            UI.error('Không lưu được')
 *   UI.warning('Vui lòng nhập tên')  UI.info('...')
 *   UI.notify(msg)                   // tự đoán loại theo nội dung (dùng cho thông báo từ server)
 *   UI.networkError(err, [msg])      // lỗi mạng/JS: hiện câu dễ hiểu, chi tiết ghi console
 *
 *   if (!await UI.confirm('Xoá chủ đề "A"?', { danger: true, confirmText: 'Xoá' })) return;
 *   const reason = await UI.prompt('Lý do từ chối', { placeholder: '...' });  // null nếu huỷ
 *
 *   Khai báo trong HTML (không cần JS):
 *   <a href="/x/delete" data-confirm="Xoá mục này?" data-confirm-danger>Xoá</a>
 *   <form data-confirm="Nộp bài? Sau khi nộp không sửa được.">...</form>
 *
 * Toast vẫn hiện sau khi trang reload/chuyển trang ngay sau đó (lưu tạm vào sessionStorage).
 * Nội dung luôn được chèn dạng text (không phải HTML) để tránh XSS.
 */
(function (window, document) {
    'use strict';

    var PENDING_KEY = 'ui_pending_toasts';
    var ICONS = {
        success: 'bi-check-circle-fill',
        error: 'bi-x-circle-fill',
        warning: 'bi-exclamation-triangle-fill',
        info: 'bi-info-circle-fill'
    };
    var DEFAULT_DURATION = { success: 3000, info: 3500, warning: 4500, error: 6000 };
    var live = [];          // toast đang hiển thị (để giữ qua lần chuyển trang)
    var container = null;

    function lang() {
        try { return (localStorage.getItem('app_language') || 'vi'); } catch (e) { return 'vi'; }
    }
    function t(vi, en) { return lang() === 'en' ? en : vi; }

    function onReady(fn) {
        if (document.body) fn();
        else document.addEventListener('DOMContentLoaded', fn);
    }

    function getContainer() {
        if (container && document.body.contains(container)) return container;
        container = document.createElement('div');
        container.className = 'ui-toast-stack';
        container.setAttribute('aria-live', 'polite');
        container.setAttribute('aria-atomic', 'false');
        document.body.appendChild(container);
        return container;
    }

    function toText(message) {
        if (message == null) return '';
        if (message instanceof Error) return message.message;
        if (typeof message === 'object') {
            return message.message || message.error || JSON.stringify(message);
        }
        return String(message);
    }

    /** Hiện một toast. opts: {duration (ms, 0 = không tự ẩn), onHidden} */
    function toast(message, type, opts) {
        type = ICONS[type] ? type : 'info';
        opts = opts || {};
        var text = toText(message).trim();
        if (!text) return null;
        var duration = opts.duration != null ? opts.duration : DEFAULT_DURATION[type];

        var record = { text: text, type: type, until: duration ? Date.now() + duration : 0 };
        live.push(record);

        var el = null;
        onReady(function () {
            el = document.createElement('div');
            el.className = 'ui-toast ui-toast--' + type;
            el.setAttribute('role', type === 'error' ? 'alert' : 'status');

            var icon = document.createElement('i');
            icon.className = 'bi ' + ICONS[type] + ' ui-toast__icon';
            icon.setAttribute('aria-hidden', 'true');

            var body = document.createElement('div');
            body.className = 'ui-toast__body';
            body.textContent = text;           // text, không phải HTML

            var close = document.createElement('button');
            close.type = 'button';
            close.className = 'ui-toast__close';
            close.setAttribute('aria-label', t('Đóng', 'Close'));
            close.innerHTML = '<i class="bi bi-x-lg" aria-hidden="true"></i>';

            el.appendChild(icon);
            el.appendChild(body);
            el.appendChild(close);
            getContainer().appendChild(el);
            requestAnimationFrame(function () { el.classList.add('is-visible'); });

            var timer = null;
            function hide() {
                if (!el) return;
                clearTimeout(timer);
                var idx = live.indexOf(record);
                if (idx >= 0) live.splice(idx, 1);
                el.classList.remove('is-visible');
                var node = el;
                el = null;
                setTimeout(function () { node.remove(); }, 250);
                if (typeof opts.onHidden === 'function') opts.onHidden();
            }
            function arm() { if (duration) timer = setTimeout(hide, duration); }
            close.addEventListener('click', hide);
            // Dừng đếm giờ khi rê chuột/focus để người dùng kịp đọc
            el.addEventListener('mouseenter', function () { clearTimeout(timer); });
            el.addEventListener('mouseleave', arm);
            arm();
        });
        return record;
    }

    /** Đoán loại thông báo từ nội dung (cho message trả về từ server) */
    function guessType(text) {
        var s = String(text || '').toLowerCase();
        if (/(lỗi|error|thất bại|failed|không thể|không được|cannot|could not|❌|từ chối|denied|invalid|không hợp lệ)/.test(s)) return 'error';
        if (/(thành công|success|✅|^đã |đã (lưu|xoá|xóa|cập nhật|gửi|tạo|nộp|thêm|sao chép|copy))/.test(s)) return 'success';
        if (/(vui lòng|please|chưa|hãy|⚠|cảnh báo|warning)/.test(s)) return 'warning';
        return 'info';
    }

    function notify(message, type) {
        var text = toText(message);
        return toast(text, type || guessType(text));
    }

    function networkError(err, friendly) {
        if (err) console.error(err);
        return toast(friendly || t('Không kết nối được máy chủ. Vui lòng kiểm tra mạng và thử lại.',
            'Could not reach the server. Please check your connection and try again.'), 'error');
    }

    // ---------- Giữ toast qua lần chuyển trang ----------
    window.addEventListener('pagehide', function () {
        var now = Date.now();
        var keep = live.filter(function (r) { return !r.until || r.until - now > 800; })
            .map(function (r) { return { text: r.text, type: r.type }; });
        try {
            if (keep.length) sessionStorage.setItem(PENDING_KEY, JSON.stringify(keep));
        } catch (e) { /* storage bị chặn: bỏ qua */ }
    });
    onReady(function () {
        var pending = null;
        try {
            pending = JSON.parse(sessionStorage.getItem(PENDING_KEY) || 'null');
            sessionStorage.removeItem(PENDING_KEY);
        } catch (e) { pending = null; }
        (pending || []).forEach(function (p) { toast(p.text, p.type); });
    });

    // ---------- Hộp thoại (confirm / prompt) ----------
    function dialog(options) {
        return new Promise(function (resolve) {
            onReady(function () {
                var previousFocus = document.activeElement;
                var overlay = document.createElement('div');
                overlay.className = 'ui-dialog-overlay';

                var box = document.createElement('div');
                box.className = 'ui-dialog' + (options.danger ? ' ui-dialog--danger' : '');
                box.setAttribute('role', 'alertdialog');
                box.setAttribute('aria-modal', 'true');

                var id = 'ui-dialog-' + Date.now();
                var head = document.createElement('div');
                head.className = 'ui-dialog__head';
                var icon = document.createElement('div');
                icon.className = 'ui-dialog__icon';
                icon.innerHTML = '<i class="bi ' + (options.danger ? 'bi-exclamation-triangle' : (options.input ? 'bi-pencil-square' : 'bi-question-circle')) + '" aria-hidden="true"></i>';
                var title = document.createElement('h2');
                title.className = 'ui-dialog__title';
                title.id = id + '-title';
                title.textContent = options.title || (options.input ? t('Nhập thông tin', 'Input') : t('Xác nhận', 'Confirm'));
                head.appendChild(icon);
                head.appendChild(title);
                box.setAttribute('aria-labelledby', title.id);

                var msg = document.createElement('div');
                msg.className = 'ui-dialog__message';
                msg.id = id + '-msg';
                msg.textContent = options.message || '';
                box.setAttribute('aria-describedby', msg.id);

                var input = null;
                if (options.input) {
                    input = document.createElement(options.multiline ? 'textarea' : 'input');
                    input.className = 'form-control ui-dialog__input';
                    if (!options.multiline) input.type = 'text';
                    else input.rows = 3;
                    input.placeholder = options.placeholder || '';
                    input.value = options.defaultValue || '';
                }

                var actions = document.createElement('div');
                actions.className = 'ui-dialog__actions';
                var cancelBtn = document.createElement('button');
                cancelBtn.type = 'button';
                cancelBtn.className = 'btn btn-light';
                cancelBtn.textContent = options.cancelText || t('Huỷ', 'Cancel');
                var okBtn = document.createElement('button');
                okBtn.type = 'button';
                okBtn.className = 'btn ' + (options.danger ? 'btn-danger' : 'btn-primary');
                okBtn.textContent = options.confirmText || t('Đồng ý', 'OK');
                actions.appendChild(cancelBtn);
                actions.appendChild(okBtn);

                box.appendChild(head);
                if (options.message) box.appendChild(msg);
                if (input) box.appendChild(input);
                box.appendChild(actions);
                overlay.appendChild(box);
                document.body.appendChild(overlay);
                document.body.classList.add('ui-dialog-open');
                requestAnimationFrame(function () { overlay.classList.add('is-visible'); });

                function close(result) {
                    document.removeEventListener('keydown', onKey, true);
                    overlay.classList.remove('is-visible');
                    setTimeout(function () {
                        overlay.remove();
                        if (!document.querySelector('.ui-dialog-overlay')) document.body.classList.remove('ui-dialog-open');
                    }, 180);
                    if (previousFocus && previousFocus.focus) { try { previousFocus.focus(); } catch (e) { } }
                    resolve(result);
                }
                function cancel() { close(options.input ? null : false); }
                function accept() { close(options.input ? input.value : true); }

                function onKey(e) {
                    if (e.key === 'Escape') { e.preventDefault(); cancel(); }
                    else if (e.key === 'Enter' && !(input && options.multiline) && document.activeElement !== cancelBtn) {
                        e.preventDefault(); accept();
                    } else if (e.key === 'Tab') {
                        // Giữ focus trong hộp thoại
                        var f = [input, cancelBtn, okBtn].filter(Boolean);
                        var i = f.indexOf(document.activeElement);
                        e.preventDefault();
                        f[(i + (e.shiftKey ? -1 : 1) + f.length) % f.length].focus();
                    }
                }
                document.addEventListener('keydown', onKey, true);
                cancelBtn.addEventListener('click', cancel);
                okBtn.addEventListener('click', accept);
                overlay.addEventListener('mousedown', function (e) { if (e.target === overlay) cancel(); });

                // Hành động nguy hiểm: focus mặc định vào nút Huỷ để Enter vô tình không xoá
                (input || (options.danger ? cancelBtn : okBtn)).focus();
            });
        });
    }

    function normalize(message, opts) {
        if (message && typeof message === 'object' && !(message instanceof Error)) return message;
        var o = Object.assign({}, opts || {});
        o.message = toText(message);
        return o;
    }

    /** Promise<boolean> */
    function confirmDialog(message, opts) {
        var o = normalize(message, opts);
        if (o.danger == null && /(xoá|xóa|delete|remove|gỡ|huỷ giao|hủy giao|unassign|xoá sạch|không thể hoàn tác)/i.test(o.message + ' ' + (o.title || ''))) {
            o.danger = true;
        }
        return dialog(o);
    }

    /** Promise<string|null> */
    function promptDialog(message, opts) {
        var o = normalize(message, opts);
        o.input = true;
        return dialog(o);
    }

    // ---------- data-confirm khai báo trong HTML ----------
    function handleDeclarativeConfirm(e) {
        var target = e.type === 'submit' ? e.target : e.target.closest('[data-confirm]:not(form)');
        if (!target || !target.hasAttribute('data-confirm')) return;
        if (target.__uiConfirmed) { target.__uiConfirmed = false; return; }
        e.preventDefault();
        e.stopImmediatePropagation();
        confirmDialog(target.getAttribute('data-confirm'), {
            danger: target.hasAttribute('data-confirm-danger') ? true : undefined,
            confirmText: target.getAttribute('data-confirm-ok') || undefined,
            title: target.getAttribute('data-confirm-title') || undefined
        }).then(function (ok) {
            if (!ok) return;
            target.__uiConfirmed = true;
            if (target.tagName === 'FORM') {
                if (target.requestSubmit) target.requestSubmit(); else target.submit();
            } else {
                target.click();
            }
        });
    }
    document.addEventListener('click', handleDeclarativeConfirm, true);
    document.addEventListener('submit', handleDeclarativeConfirm, true);

    // ---------- Flash từ server (PHP flash_set) ----------
    onReady(function () {
        var meta = document.querySelector('meta[name="app-flash"]');
        if (!meta) return;
        try {
            JSON.parse(meta.getAttribute('content') || '[]').forEach(function (f) { toast(f.message, f.type); });
        } catch (e) { /* bỏ qua */ }
    });

    window.UI = {
        toast: toast,
        notify: notify,
        success: function (m, o) { return toast(m, 'success', o); },
        error: function (m, o) { return toast(m, 'error', o); },
        warning: function (m, o) { return toast(m, 'warning', o); },
        info: function (m, o) { return toast(m, 'info', o); },
        networkError: networkError,
        confirm: confirmDialog,
        prompt: promptDialog
    };
})(window, document);
