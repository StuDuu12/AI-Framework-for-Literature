# Hướng dẫn thiết lập hệ thống Admin

## Các bước thực hiện:

### 1. Cập nhật database

Chạy file SQL migration để tạo bảng `feedback`:

```bash
mysql -u root -p diemdanh_literature < database/migrations/add_feedback_table.sql
```

Hoặc import thủ công vào phpMyAdmin:

-   Mở file `database/migrations/add_feedback_table.sql`
-   Copy nội dung và chạy trong phpMyAdmin

### 2. Tài khoản Admin mặc định

Database đã có sẵn tài khoản admin:

-   **Username**: `admin`
-   **Email**: `admin@gmail.com`
-   **Password**: `1`
-   **Role**: `admin`

### 3. Truy cập hệ thống Admin

Sau khi đăng nhập với tài khoản admin, bạn sẽ được tự động chuyển đến trang quản trị:

```
http://literaturever2.test/views/admin/dashboard.php
```

## Chức năng của Admin

### 1. Quản lý người dùng

-   **Xem danh sách**: Tất cả người dùng, lọc theo vai trò (admin/teacher/student)
-   **Tìm kiếm**: Theo tên, username, email
-   **Tạo mới**: Tạo tài khoản giáo viên và học sinh
-   **Chỉnh sửa**: Sửa thông tin, đổi mật khẩu, thay đổi vai trò
-   **Xóa**: Xóa tài khoản người dùng (không thể tự xóa mình)

**Đường dẫn**:

-   Danh sách: `/views/admin/manage_users.php`
-   Tạo mới: `/views/admin/create_user.php`
-   Chỉnh sửa: `/views/admin/edit_user.php?id={user_id}`

### 2. Quản lý góp ý

-   **Xem danh sách**: Tất cả góp ý từ học sinh và giáo viên
-   **Lọc theo trạng thái**:
    -   `pending`: Chờ xử lý (màu vàng)
    -   `reviewed`: Đã xem (màu xanh dương)
    -   `resolved`: Đã giải quyết (màu xanh lá)
-   **Tìm kiếm**: Theo chủ đề, nội dung, người gửi
-   **Phản hồi**: Viết phản hồi cho góp ý
-   **Cập nhật trạng thái**: Đánh dấu đã xem/đã giải quyết

**Đường dẫn**: `/views/admin/manage_feedback.php`

### 3. Dashboard

-   Thống kê tổng quan:
    -   Số lượng giáo viên
    -   Số lượng học sinh
    -   Số góp ý chờ xử lý
    -   Số lớp học
-   Thao tác nhanh: Link đến các chức năng chính

**Đường dẫn**: `/views/admin/dashboard.php`

## Chức năng cho Học sinh và Giáo viên

### Gửi góp ý

Học sinh và giáo viên có thể gửi góp ý cho admin qua:

-   **Đường dẫn**: `/views/feedback/index.php`
-   **Menu**: Sidebar → Góp ý → Gửi góp ý

**Tính năng**:

-   Nhập chủ đề và nội dung góp ý
-   Xem lịch sử các góp ý đã gửi
-   Xem phản hồi từ admin
-   Theo dõi trạng thái xử lý

## Cấu trúc Database

### Bảng `users`

```sql
- user_id: INT (Primary Key)
- fullname: VARCHAR(255)
- username: VARCHAR(100)
- email: VARCHAR(255)
- password_hash: VARCHAR(255)
- role: ENUM('student', 'teacher', 'admin')
- created_at: TIMESTAMP
```

### Bảng `feedback`

```sql
- feedback_id: INT (Primary Key)
- user_id: INT (Foreign Key → users.user_id)
- subject: VARCHAR(255)
- message: TEXT
- status: ENUM('pending', 'reviewed', 'resolved')
- admin_response: TEXT
- created_at: TIMESTAMP
- updated_at: TIMESTAMP
```

## API Endpoints

### `/api/admin_actions.php`

#### Xóa người dùng

```json
POST /api/admin_actions.php
{
  "action": "delete_user",
  "user_id": 123
}
```

#### Phản hồi góp ý

```json
POST /api/admin_actions.php
{
  "action": "respond_feedback",
  "feedback_id": 456,
  "admin_response": "Cảm ơn bạn đã góp ý...",
  "status": "resolved"
}
```

## Bảo mật

1. **Kiểm tra quyền**: Tất cả trang admin đều kiểm tra `$_SESSION['role'] === 'admin'`
2. **Mật khẩu**: Sử dụng `password_hash()` với `PASSWORD_DEFAULT`
3. **SQL Injection**: Sử dụng Prepared Statements với PDO
4. **XSS**: Sử dụng `htmlspecialchars()` khi hiển thị dữ liệu
5. **CSRF**: Chỉ admin có quyền xóa/sửa

## Giao diện

-   **Sidebar Admin**: Purple gradient (màu tím sang)
-   **Dashboard Cards**:
    -   Teachers: Blue (Xanh dương)
    -   Students: Green (Xanh lá)
    -   Pending Feedback: Yellow (Vàng)
    -   Classes: Cyan (Xanh lơ)
-   **Responsive**: Hoạt động tốt trên mobile và desktop

## Lưu ý

1. Admin không thể xóa tài khoản của chính mình
2. Khi xóa user, tất cả feedback của user đó cũng bị xóa (CASCADE)
3. Góp ý được sắp xếp theo trạng thái: pending → reviewed → resolved
4. Sidebar hiển thị số góp ý chờ xử lý (badge đỏ)

## Troubleshooting

### Lỗi không thể truy cập trang admin

-   Kiểm tra đã đăng nhập với tài khoản có `role = 'admin'`
-   Clear session và đăng nhập lại

### Lỗi database khi tạo feedback

-   Kiểm tra bảng `feedback` đã được tạo chưa
-   Chạy lại file migration SQL

### Lỗi URL_ROOT undefined

-   Kiểm tra file `config/env.php` có define `URL_ROOT` chưa
-   Restart web server

## Nâng cấp tương lai

1. Phân quyền chi tiết hơn (Admin cấp 1, cấp 2...)
2. Log hoạt động của admin
3. Export danh sách người dùng ra Excel
4. Gửi email thông báo khi có phản hồi
5. Dashboard với biểu đồ thống kê
