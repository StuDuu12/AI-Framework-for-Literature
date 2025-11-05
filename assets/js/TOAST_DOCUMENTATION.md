# Toast Notification System - Documentation

## Tổng quan

Hệ thống Toast Notification cung cấp thông báo nhất quán và đẹp mắt trên toàn bộ ứng dụng, sử dụng Bootstrap 5 và JavaScript.

## Cấu trúc file

```
assets/
├── css/
│   └── style.css              # CSS cho toast (đã thêm vào cuối file)
├── js/
│   └── toast.js               # Toast Manager class
config/
└── helper_redirect.php         # PHP helper functions cho redirect với toast
views/
└── templates/
    └── vendors.php             # Include toast.js (đã thêm)
```

## Sử dụng trong JavaScript

### 1. Hiển thị toast cơ bản

```javascript
// Toast thành công
Toast.success('Lưu thành công!');

// Toast lỗi
Toast.error('Đã có lỗi xảy ra!');

// Toast cảnh báo
Toast.warning('Vui lòng kiểm tra lại!');

// Toast thông tin
Toast.info('Đây là thông tin quan trọng');
```

### 2. Toast với thời gian tùy chỉnh

```javascript
// Hiển thị 5 giây
Toast.success('Đã lưu', 5000);

// Không tự động ẩn (duration = 0)
Toast.info('Thông báo quan trọng', 0);
```

### 3. Toast với callback

```javascript
// Thực hiện hành động sau khi toast ẩn
Toast.success('Đã xóa!', 2000, function () {
    console.log('Toast đã ẩn');
    // Thực hiện hành động khác
});
```

### 4. Toast và redirect

```javascript
// Hiển thị toast rồi chuyển trang
Toast.successAndRedirect('Lưu thành công!', 'success', '/class/task', 2000);

// Hoặc sử dụng phương thức rút gọn
Toast.errorAndRedirect('Lỗi xảy ra!', '/class', 2000);
```

### 5. Toast tùy chỉnh hoàn toàn

```javascript
Toast.show('Thông báo tùy chỉnh', 'warning', 3000, function () {
    window.location.reload();
});
```

## Sử dụng trong PHP

### 1. Include helper file

```php
require_once './config/helper_redirect.php';
```

### 2. Redirect với error toast

```php
// Redirect với mã lỗi (sẽ được mapping thành message trong JS)
redirectWithError('/class/task?topic_id=1&phase_id=2', 'unauthorized');

// Các error code có sẵn:
// - unauthorized: "Bạn không có quyền truy cập nhiệm vụ này!"
// - not_assigned: "Nhiệm vụ này chưa được giao cho bạn!"
// - invalid_deliver: "Thông tin giao bài không hợp lệ!"
// - access_denied: "Truy cập bị từ chối!"
// - not_found: "Không tìm thấy dữ liệu!"
// - invalid_data: "Dữ liệu không hợp lệ!"
```

### 3. Redirect với success toast

```php
// Redirect với mã success (sẽ được mapping thành message trong JS)
redirectWithSuccess('/class/task?topic_id=1', 'saved');

// Các success code có sẵn:
// - saved: "Lưu thành công!"
// - updated: "Cập nhật thành công!"
// - deleted: "Xóa thành công!"
// - created: "Tạo mới thành công!"
// - completed: "Hoàn thành!"
// - submitted: "Nộp bài thành công!"
```

### 4. Redirect với custom message

```php
// Redirect với message tùy chỉnh
redirectWithToast('/class', 'Đây là thông báo tùy chỉnh', 'info');

// Type: success, error, warning, info
redirectWithToast('/class/task', 'Cảnh báo quan trọng!', 'warning');
```

### 5. Build URL với parameters

```php
// Helper để build URL dễ dàng
$url = buildUrl('/class/task', [
    'topic_id' => $topicId,
    'phase_id' => $phaseId,
    'deliver_id' => $deliverId
]);
// Kết quả: /class/task?topic_id=1&phase_id=2&deliver_id=3

// Sử dụng với redirect
redirectWithError($url, 'unauthorized');
```

## Tự động kiểm tra URL parameters

Toast system tự động kiểm tra URL params khi trang load:

```
URL: /class/task?topic_id=1&error=unauthorized
→ Hiển thị toast error: "Bạn không có quyền truy cập nhiệm vụ này!"

URL: /class/task?success=saved
→ Hiển thị toast success: "Lưu thành công!"

URL: /class?message=Custom%20message&type=warning
→ Hiển thị toast warning: "Custom message"
```

Sau khi hiển thị, các parameters sẽ được tự động xóa khỏi URL.

## Ví dụ thực tế

### Ví dụ 1: Validate và redirect trong perform.php

```php
// Kiểm tra quyền truy cập
if ($deliverId > 0) {
    if (!validateUserDeliverAccess($deliverId, $userId)) {
        $redirectUrl = buildUrl('/class/task', [
            'topic_id' => $topicId,
            'phase_id' => $phaseId
        ]);
        redirectWithError($redirectUrl, 'unauthorized');
    }
}
```

### Ví dụ 2: AJAX với toast

```javascript
$.ajax({
    url: '/api/save_data.php',
    method: 'POST',
    data: formData,
    success: function (response) {
        if (response.success) {
            Toast.successAndRedirect('Lưu thành công!', 'success', '/class/task', 2000);
        } else {
            Toast.error(response.message || 'Đã có lỗi xảy ra!');
        }
    },
    error: function () {
        Toast.error('Không thể kết nối đến server!');
    },
});
```

### Ví dụ 3: Delete với confirm và toast

```javascript
$('.delete-btn').click(function () {
    if (confirm('Bạn có chắc muốn xóa?')) {
        $.post('/api/delete.php', { id: $(this).data('id') })
            .done(function (response) {
                if (response.success) {
                    Toast.successAndRedirect('Xóa thành công!', 'success', window.location.href, 1500);
                } else {
                    Toast.error('Không thể xóa!');
                }
            })
            .fail(function () {
                Toast.error('Lỗi kết nối!');
            });
    }
});
```

## Tùy chỉnh

### Thay đổi màu sắc

Chỉnh sửa trong `assets/css/style.css`:

```css
.toast.bg-success {
    background-color: #28a745 !important;
}
.toast.bg-danger {
    background-color: #dc3545 !important;
}
.toast.bg-warning {
    background-color: #ffc107 !important;
}
.toast.bg-info {
    background-color: #17a2b8 !important;
}
```

### Thêm error code mới

Chỉnh sửa trong `assets/js/toast.js`, hàm `checkUrlParams()`:

```javascript
const errorMessages = {
    'unauthorized': 'Bạn không có quyền truy cập nhiệm vụ này!',
    'your_new_code': 'Your new error message!',
    // ... thêm code mới
};
```

### Thay đổi vị trí toast

Chỉnh sửa trong `assets/js/toast.js`, hàm `init()`:

```javascript
// Top-right (mặc định)
this.container.className = 'toast-container position-fixed top-0 end-0 p-3';

// Top-left
this.container.className = 'toast-container position-fixed top-0 start-0 p-3';

// Bottom-right
this.container.className = 'toast-container position-fixed bottom-0 end-0 p-3';

// Top-center
this.container.className = 'toast-container position-fixed top-0 start-50 translate-middle-x p-3';
```

## Best Practices

1. **Sử dụng error codes thay vì messages cứng**: Dễ dàng thay đổi message sau này
2. **Luôn redirect sau khi hiển thị error**: Tránh user refresh và gặp lại lỗi
3. **Sử dụng duration phù hợp**:
    - Success: 2-3 giây
    - Error: 3-4 giây
    - Warning: 3-4 giây
    - Info: 2-3 giây
4. **Xóa alert() cũ**: Thay thế tất cả `alert()` bằng `Toast.error()` hoặc `Toast.info()`
5. **Validate trước khi redirect**: Luôn kiểm tra dữ liệu trước khi chuyển trang

## Troubleshooting

### Toast không hiển thị?

-   Kiểm tra `toast.js` đã được include trong `vendors.php`
-   Kiểm tra Bootstrap JS đã được load
-   Mở Console để xem lỗi JavaScript

### Toast bị che bởi các element khác?

-   Tăng `z-index` trong CSS: `#toast-container { z-index: 99999 !important; }`

### URL parameters không bị xóa?

-   Kiểm tra `Toast.checkUrlParams()` có được gọi khi page load
-   Kiểm tra browser có hỗ trợ `window.history.replaceState()`
