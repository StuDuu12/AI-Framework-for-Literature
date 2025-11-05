# Cập nhật API và Helper - Deliver với Response ID

## Thay đổi

### 1. `api/save_response.php`

-   ✅ Thêm `require_once helper_task.php`
-   ✅ Validate `deliver_id` từ request
-   ✅ Kiểm tra quyền truy cập: `validateUserDeliverAccess($deliver_id, $user_id)`
-   ✅ Sau khi insert response, cập nhật deliver:
    ```php
    updateDeliverStatus($deliver_id, $response_id, 1);
    ```

### 2. `api/analyze_task.php`

-   ✅ Thêm `require_once helper_task.php`
-   ✅ Validate `deliver_id` từ request nếu có
-   ✅ Kiểm tra quyền truy cập: `validateUserDeliverAccess($deliver_id, $user_id)`

### 3. `config/helper_task.php`

-   ✅ Cập nhật comment cho function `updateDeliverStatus()`:
    -   Parameter: `$responseId` (INT) - ID tham chiếu đến `response.id_response`
    -   Không phải JSON text, là ID

## Cấu trúc Database (giữ nguyên)

```
deliver table:
- id_deliver (INT)
- task_id (INT)
- type (ENUM: 'class','group','user')
- value (INT)
- response (INT) → references response.id_response
- status (INT) → 0: assigned, 1: in_progress, 2: completed

response table:
- id_response (INT)
- user_id (INT)
- owner_user_id (INT)
- task_id (INT)
- json_ai (JSON) → AI response lưu ở đây
```

## Luồng hoạt động

1. User truy cập genAI.php với `deliver_id`
2. Validate quyền truy cập: `validateUserDeliverAccess($deliver_id, $user_id)`
3. User tạo AI analysis → gửi request với `deliver_id`
4. Backend validate lại `deliver_id`
5. Backend gọi `insertResponse()` → nhận `$response_id`
6. Backend gọi `updateDeliverStatus($deliver_id, $response_id, 1)`
7. Kết quả:
    - `response.json_ai` = AI response (JSON)
    - `deliver.response` = `$response_id` (INT)
    - `deliver.status` = 1 (in progress)

## Không cần migration

Database structure đã đúng, không cần thay đổi gì.
