/**
 * Toast — lớp tương thích ngược, hiển thị qua UI (assets/js/ui.js).
 * Code mới nên gọi thẳng UI.success / UI.error / UI.warning / UI.info.
 */
(function (window) {
    'use strict';

    function ui() {
        return window.UI;
    }

    function show(message, type, duration, onHidden) {
        if (!ui()) {
            console.warn('[Toast]', message);
            return null;
        }
        return ui().toast(message, type || 'info', { duration: duration, onHidden: onHidden });
    }

    function cleanParams(params) {
        var q = params.toString();
        window.history.replaceState({}, '', window.location.pathname + (q ? '?' + q : '') + window.location.hash);
    }

    var Toast = {
        show: show,
        success: function (m, d, cb) { return show(m, 'success', d, cb); },
        error: function (m, d, cb) { return show(m, 'error', d, cb); },
        warning: function (m, d, cb) { return show(m, 'warning', d, cb); },
        info: function (m, d, cb) { return show(m, 'info', d, cb); },
        showAndRedirect: function (m, type, url, d) {
            show(m, type, d || 2000);
            // ui.js giữ toast qua lần chuyển trang nên có thể chuyển ngay
            setTimeout(function () { window.location.href = url; }, 600);
        },
        errorAndRedirect: function (m, url, d) { return Toast.showAndRedirect(m, 'error', url, d); },
        successAndRedirect: function (m, url, d) { return Toast.showAndRedirect(m, 'success', url, d); },

        /** Hiện thông báo theo tham số URL ?error=... / ?success=... / ?message=...&type=... */
        checkUrlParams: function () {
            var params = new URLSearchParams(window.location.search);
            var changed = false;
            var errorMessages = {
                unauthorized: 'Bạn không có quyền truy cập nhiệm vụ này.',
                not_assigned: 'Nhiệm vụ này chưa được giao cho bạn.',
                invalid_deliver: 'Thông tin giao bài không hợp lệ.',
                access_denied: 'Truy cập bị từ chối.',
                not_found: 'Không tìm thấy dữ liệu.',
                invalid_data: 'Dữ liệu không hợp lệ.'
            };
            var successMessages = {
                saved: 'Đã lưu.',
                updated: 'Đã cập nhật.',
                deleted: 'Đã xoá.',
                created: 'Đã tạo mới.',
                completed: 'Đã hoàn thành.',
                submitted: 'Đã nộp bài.'
            };
            var err = params.get('error');
            if (err) {
                Toast.error(errorMessages[err] || 'Đã có lỗi xảy ra.');
                params.delete('error');
                changed = true;
            }
            var ok = params.get('success');
            if (ok) {
                Toast.success(successMessages[ok] || 'Thành công.');
                params.delete('success');
                changed = true;
            }
            var msg = params.get('message');
            if (msg) {
                var type = params.get('type');
                show(msg, ['success', 'error', 'warning', 'info'].indexOf(type) >= 0 ? type : 'info');
                params.delete('message');
                params.delete('type');
                changed = true;
            }
            if (changed) cleanParams(params);
        }
    };

    window.Toast = Toast;
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', Toast.checkUrlParams);
    } else {
        Toast.checkUrlParams();
    }
})(window);
