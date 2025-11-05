/**
 * Toast Notification System
 * Provides consistent toast notifications across the application
 */

class ToastManager {
    constructor() {
        this.container = null;
        this.init();
    }

    /**
     * Initialize toast container
     */
    init() {
        // Check if container already exists
        this.container = document.getElementById('toast-container');

        if (!this.container) {
            // Create toast container
            this.container = document.createElement('div');
            this.container.id = 'toast-container';
            this.container.className = 'toast-container position-fixed top-0 end-0 p-3';
            this.container.style.zIndex = '9999';
            document.body.appendChild(this.container);
        }
    }

    /**
     * Show a toast notification
     * @param {string} message - The message to display
     * @param {string} type - Type of toast: success, error, warning, info
     * @param {number} duration - Duration in milliseconds (0 = no auto-hide)
     * @param {function} onHidden - Callback function when toast is hidden
     */
    show(message, type = 'info', duration = 3000, onHidden = null) {
        const toastId = 'toast-' + Date.now();

        // Toast colors and icons
        const types = {
            success: {
                bg: 'bg-success',
                icon: 'bi-check-circle-fill',
                textClass: 'text-white',
            },
            error: {
                bg: 'bg-danger',
                icon: 'bi-x-circle-fill',
                textClass: 'text-white',
            },
            warning: {
                bg: 'bg-warning',
                icon: 'bi-exclamation-triangle-fill',
                textClass: 'text-dark',
            },
            info: {
                bg: 'bg-info',
                icon: 'bi-info-circle-fill',
                textClass: 'text-white',
            },
        };

        const config = types[type] || types.info;

        // Create toast HTML
        const toastHTML = `
            <div id="${toastId}" class="toast align-items-center ${config.bg} ${config.textClass} border-0" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="bi ${config.icon} me-2"></i>
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        `;

        // Add toast to container
        this.container.insertAdjacentHTML('beforeend', toastHTML);

        // Get toast element
        const toastElement = document.getElementById(toastId);
        const bsToast = new bootstrap.Toast(toastElement, {
            autohide: duration > 0,
            delay: duration,
        });

        // Add event listener for when toast is hidden
        if (onHidden) {
            toastElement.addEventListener('hidden.bs.toast', () => {
                onHidden();
            });
        }

        // Remove toast element from DOM after it's hidden
        toastElement.addEventListener('hidden.bs.toast', () => {
            toastElement.remove();
        });

        // Show toast
        bsToast.show();

        return bsToast;
    }

    /**
     * Show success toast
     * @param {string} message
     * @param {number} duration
     * @param {function} onHidden
     */
    success(message, duration = 3000, onHidden = null) {
        return this.show(message, 'success', duration, onHidden);
    }

    /**
     * Show error toast
     * @param {string} message
     * @param {number} duration
     * @param {function} onHidden
     */
    error(message, duration = 4000, onHidden = null) {
        return this.show(message, 'error', duration, onHidden);
    }

    /**
     * Show warning toast
     * @param {string} message
     * @param {number} duration
     * @param {function} onHidden
     */
    warning(message, duration = 3500, onHidden = null) {
        return this.show(message, 'warning', duration, onHidden);
    }

    /**
     * Show info toast
     * @param {string} message
     * @param {number} duration
     * @param {function} onHidden
     */
    info(message, duration = 3000, onHidden = null) {
        return this.show(message, 'info', duration, onHidden);
    }

    /**
     * Show toast and redirect after it's hidden
     * @param {string} message
     * @param {string} type
     * @param {string} redirectUrl
     * @param {number} duration
     */
    showAndRedirect(message, type, redirectUrl, duration = 2000) {
        return this.show(message, type, duration, () => {
            window.location.href = redirectUrl;
        });
    }

    /**
     * Show error toast and redirect
     * @param {string} message
     * @param {string} redirectUrl
     * @param {number} duration
     */
    errorAndRedirect(message, redirectUrl, duration = 2000) {
        return this.showAndRedirect(message, 'error', redirectUrl, duration);
    }

    /**
     * Show success toast and redirect
     * @param {string} message
     * @param {string} redirectUrl
     * @param {number} duration
     */
    successAndRedirect(message, redirectUrl, duration = 2000) {
        return this.showAndRedirect(message, 'success', redirectUrl, duration);
    }

    /**
     * Check URL parameters for error/success messages
     * Automatically shows toast based on URL params
     */
    checkUrlParams() {
        const urlParams = new URLSearchParams(window.location.search);

        // Check for error parameter
        const error = urlParams.get('error');
        if (error) {
            const errorMessages = {
                'unauthorized': 'Bạn không có quyền truy cập nhiệm vụ này!',
                'not_assigned': 'Nhiệm vụ này chưa được giao cho bạn!',
                'invalid_deliver': 'Thông tin giao bài không hợp lệ!',
                'access_denied': 'Truy cập bị từ chối!',
                'not_found': 'Không tìm thấy dữ liệu!',
                'invalid_data': 'Dữ liệu không hợp lệ!',
            };

            const message = errorMessages[error] || 'Đã có lỗi xảy ra!';
            this.error(message);

            // Remove error param from URL
            urlParams.delete('error');
            const newUrl = window.location.pathname + (urlParams.toString() ? '?' + urlParams.toString() : '');
            window.history.replaceState({}, '', newUrl);
        }

        // Check for success parameter
        const success = urlParams.get('success');
        if (success) {
            const successMessages = {
                'saved': 'Lưu thành công!',
                'updated': 'Cập nhật thành công!',
                'deleted': 'Xóa thành công!',
                'created': 'Tạo mới thành công!',
                'completed': 'Hoàn thành!',
                'submitted': 'Nộp bài thành công!',
            };

            const message = successMessages[success] || 'Thành công!';
            this.success(message);

            // Remove success param from URL
            urlParams.delete('success');
            const newUrl = window.location.pathname + (urlParams.toString() ? '?' + urlParams.toString() : '');
            window.history.replaceState({}, '', newUrl);
        }

        // Check for custom message parameter
        const customMessage = urlParams.get('message');
        const messageType = urlParams.get('type') || 'info';
        if (customMessage) {
            this.show(decodeURIComponent(customMessage), messageType);

            // Remove message params from URL
            urlParams.delete('message');
            urlParams.delete('type');
            const newUrl = window.location.pathname + (urlParams.toString() ? '?' + urlParams.toString() : '');
            window.history.replaceState({}, '', newUrl);
        }
    }
}

// Create global instance
const Toast = new ToastManager();

// Auto-check URL params when page loads
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        Toast.checkUrlParams();
    });
} else {
    Toast.checkUrlParams();
}

// Export for use in modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = Toast;
}
