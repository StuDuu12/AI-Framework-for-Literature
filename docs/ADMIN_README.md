# Admin System - Quick Start

## 🚀 Cài đặt nhanh

### 1. Import database

```sql
-- Chạy trong phpMyAdmin hoặc MySQL client
source database/migrations/add_feedback_table.sql;
```

### 2. Đăng nhập Admin

-   URL: `http://literaturever2.test/index.php?route=login`
-   Username: `admin`
-   Password: `1`

### 3. Truy cập Dashboard

Sau khi đăng nhập, tự động chuyển đến: `http://literaturever2.test/views/admin/dashboard.php`

## 📋 Chức năng chính

### Admin Dashboard

-   ✅ Thống kê tổng quan hệ thống
-   ✅ Quản lý người dùng (Giáo viên, Học sinh)
-   ✅ Tạo tài khoản mới với role
-   ✅ Sửa/Xóa tài khoản
-   ✅ Quản lý góp ý từ học sinh

### Học sinh/Giáo viên

-   ✅ Gửi góp ý cho admin
-   ✅ Xem lịch sử góp ý
-   ✅ Nhận phản hồi từ admin

## 📁 Cấu trúc file

```
views/admin/
├── dashboard.php          # Trang chủ admin
├── manage_users.php       # Quản lý người dùng
├── create_user.php        # Tạo tài khoản mới
├── edit_user.php          # Sửa tài khoản
└── manage_feedback.php    # Quản lý góp ý

views/feedback/
└── index.php              # Trang gửi góp ý (học sinh/GV)

views/templates/
└── sidebar_admin.php      # Sidebar cho admin

api/
└── admin_actions.php      # API xử lý admin

database/migrations/
└── add_feedback_table.sql # Migration tạo bảng feedback
```

## 🔐 Bảo mật

-   Kiểm tra role admin trước khi truy cập
-   Password được hash với `password_hash()`
-   Prepared statements chống SQL injection
-   XSS protection với `htmlspecialchars()`

## 📚 Tài liệu chi tiết

Xem file: `docs/ADMIN_SETUP.md`
