# GenAI BTVN - Hướng dẫn sử dụng

## Mô tả

File `genAI_BTVN.php` là công cụ phân tích văn học với AI, hỗ trợ tự động load nội dung bài tập từ database.

## Cách sử dụng

### 1. Truy cập trực tiếp (không có bài tập)

```
http://localhost/LiteratureVer2/?route=class/homework/analysis_chat
```

-   Người dùng có thể nhập/dán văn bản thủ công
-   Hoặc upload file .txt

### 2. Truy cập với bài tập cụ thể (từ database)

```
http://localhost/LiteratureVer2/?route=class/homework/analysis_chat&task_id=8&topic_id=8&phase_id=3
```

**Tham số GET:**

-   `task_id`: ID của bài tập (bắt buộc để load nội dung)
-   `topic_id`: ID của chủ đề (tùy chọn, dùng cho breadcrumb)
-   `phase_id`: ID của giai đoạn (tùy chọn, dùng cho breadcrumb)

## Tính năng khi có task_id

### 1. Tự động load nội dung

-   Lấy `file_content` từ bảng `task` thông qua hàm `getInfoTask($taskId)`
-   Điền tự động vào textarea phân tích
-   Hiển thị thông tin bài tập ở panel bên trái

### 2. Hiển thị thông tin bài tập

Khi có `task_id`, hệ thống sẽ hiển thị:

-   **Tiêu đề bài tập** trong heading chính
-   **Breadcrumb** với link về chủ đề
-   **Thông báo blue box** với:
    -   Tên bài tập
    -   Mô tả bài tập (nếu có)
    -   Giai đoạn và tên pha học tập

### 3. Tự động phân tích (tùy chọn)

-   Sau khi load nội dung, hiển thị popup xác nhận
-   Người dùng chọn "OK" → tự động click nút "Phân tích"
-   Người dùng chọn "Cancel" → giữ nguyên, có thể chỉnh sửa trước

## Cấu trúc dữ liệu

### Hàm getInfoTask($taskId)

Trả về mảng với các trường:

```php
[
    'task_id' => 8,
    'task_title' => 'Phân tích bài thơ Sóng',
    'task_description' => 'Hãy phân tích chi tiết...',
    'file_content' => 'Nội dung văn bản đầy đủ...',
    'phase_name' => 'Tuần 1',
    'phase_number' => 1,
    'topic_id' => 8,
    'topic_name' => 'Thơ Xuân Diệu',
    'class_id' => 5,
    'm_phase_name' => 'Phân tích cá nhân',
    'phase_code' => 'PA',
    // ... các trường khác
]
```

## Luồng xử lý

1. **Request đến với GET params**

    ```
    ?route=class/homework/analysis_chat&task_id=8
    ```

2. **PHP xử lý**

    - Nhận `task_id` từ `$_GET['task_id']`
    - Gọi `getInfoTask($taskId)`
    - Lưu vào biến `$taskContent`, `$taskTitle`, `$taskInfo`

3. **HTML render**

    - Điền `$taskContent` vào textarea với `htmlspecialchars()`
    - Hiển thị thông tin task trong blue box
    - Cập nhật breadcrumb và page title

4. **JavaScript xử lý**
    - DOMContentLoaded event
    - Kiểm tra nếu có nội dung pre-loaded
    - Hiển thị confirm dialog
    - Tự động trigger `analyzeButton.click()` nếu user chọn Yes

## Testing

### Test case 1: Không có task_id

```
http://localhost/LiteratureVer2/?route=class/homework/analysis_chat
```

**Expected:** Trang trống, textarea placeholder, không có thông báo task

### Test case 2: Có task_id hợp lệ

```
http://localhost/LiteratureVer2/?route=class/homework/analysis_chat&task_id=8
```

**Expected:**

-   Textarea đã có nội dung từ database
-   Blue box hiển thị thông tin bài tập
-   Popup confirm auto-analyze xuất hiện
-   Breadcrumb có link về topic

### Test case 3: Task_id không tồn tại

```
http://localhost/LiteratureVer2/?route=class/homework/analysis_chat&task_id=99999
```

**Expected:**

-   `getInfoTask()` trả về null/false
-   Không có lỗi PHP
-   Trang hiển thị bình thường như test case 1

## Bảo mật

-   ✅ Tất cả input từ GET được validate với `intval()`
-   ✅ Output được escape với `htmlspecialchars()`
-   ✅ Không có SQL injection (sử dụng prepared statements)
-   ✅ Kiểm tra session auth trước khi truy cập

## Notes

-   File content được lưu trong trường `file_content` của bảng `task`
-   Nếu muốn lưu kết quả phân tích, cần implement API endpoint riêng
-   Hiện tại nút "Lưu thay đổi" chỉ log ra console, chưa lưu vào database
