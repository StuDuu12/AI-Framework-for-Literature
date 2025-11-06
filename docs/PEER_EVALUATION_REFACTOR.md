# Tóm tắt cập nhật hệ thống đánh giá chéo

## 1. Helper Functions (config/helper_homework.php)

Đã thêm các helper functions mới để xử lý peer evaluation:

### Quản lý tiêu chí đánh giá:
- `getTaskCriteriaGroup($taskId)` - Lấy bộ tiêu chí đã cấu hình cho task
- `setTaskCriteriaGroup($taskId, $nhomTcId)` - Gán/cập nhật bộ tiêu chí cho task  
- `hasTaskEvaluations($taskId)` - Kiểm tra đã có đánh giá nào chưa

### Quản lý phân công chấm bài:
- `getResponsesForEvaluation($taskId)` - Lấy danh sách responses đã nộp (status=2) cần được chấm
- `getEvaluatorAssignments($taskId)` - Lấy danh sách phân công hiện tại
- `assignEvaluator($taskId, $responseId, $evaluatorType, $evaluatorValue, $addedBy)` - Phân công người chấm
  - Tự động tạo deliver record cho evaluator nếu chưa có
  - Tạo deliver_evaluate record liên kết với response cần chấm
  - Ngăn chặn tự chấm bài của chính mình
- `removeEvaluatorAssignment($evaluatorDeliverId, $responseId)` - Xóa phân công

## 2. Helper Functions (config/helper_phase.php)

Đã thêm:
- `isTeacherInClass($classId, $userId)` - Kiểm tra user có phải teacher của class không (sử dụng trực tiếp class_id)

## 3. API Endpoints

### api/evaluate/set_criteria.php
**Mục đích:** Lưu bộ tiêu chí cho task

**Cấu trúc:**
```php
- ob_start() / ob_end_clean() - Prevent output before JSON
- Require: db.php, helper_account.php, helper_class.php, helper_homework.php
- Check login với is_login()
- Check role === 'teacher'
- Verify permissions với isTeacherInClass()
- Check hasTaskEvaluations() - không cho đổi tiêu chí nếu đã có đánh giá
- Call setTaskCriteriaGroup()
```

**Input:** POST
- `task_id` (int)
- `nhom_tc_id` (int)

**Output:** JSON
```json
{
    "success": true/false,
    "message": "..."
}
```

### api/evaluate/assign_evaluators.php  
**Mục đích:** Phân công người chấm cho các responses

**Cấu trúc:**
```php
- ob_start() / ob_end_clean()
- Require: db.php, helper_account.php, helper_class.php, helper_homework.php
- Check login và role === 'teacher'
- Verify permissions với isTeacherInClass()
- Check criteria đã được cấu hình chưa
- Loop qua từng assignment và call assignEvaluator()
```

**Input:** POST
- `task_id` (int)
- `assignments` (JSON string) - Array of:
  ```json
  [
    {
      "response_id": 123,
      "evaluator_type": "user", // hoặc "group"
      "evaluator_value": 456 // user_id hoặc group_id
    }
  ]
  ```

**Output:** JSON
```json
{
    "success": true/false,
    "message": "...",
    "success_count": 10,
    "error_count": 2,
    "errors": ["Response 5: Lỗi...", ...]
}
```

## 4. Views (views/evaluate/setting.php)

### Thay đổi data loading:
**Trước:**
- Query `deliver` table với JOIN users/groups
- Hiển thị theo deliver records

**Sau:**
- Sử dụng `getResponsesForEvaluation($task_id)` - chỉ lấy responses đã nộp
- Sử dụng `getEvaluatorAssignments($task_id)` - lấy phân công theo response_id

### Thay đổi UI:

**Bảng phân công mới:**
| Cột | Mô tả |
|-----|-------|
| Bài nộp | Tên người/nhóm nộp + thời gian |
| Người nộp | Badge: Cá nhân/Nhóm/Lớp |
| Response ID | `<code>` tag với response_id |
| Người chấm | Container cho phép ADD nhiều evaluators |
| Hành động | Badge hiển thị số người đã phân công |

**Tính năng mới:**
1. **Add nhiều người chấm cho 1 response:**
   - Button "+ Thêm người chấm" cho mỗi response
   - Mỗi evaluator có nút "X" để xóa (ẩn nếu chỉ còn 1)

2. **Select evaluator:**
   - Optgroup: "Học sinh" và "Nhóm"
   - Tự động loại trừ người nộp bài khỏi danh sách
   - Format: `user_123` hoặc `group_456`

### JavaScript changes:

**Event delegation cho Add/Remove evaluator:**
```javascript
document.addEventListener('click', function(e) {
    // Handle add-evaluator button
    if (e.target.classList.contains('add-evaluator')) {
        // Clone select options
        // Add new row to container
        // Show all remove buttons
    }
    
    // Handle remove-evaluator button  
    if (e.target.classList.contains('remove-evaluator')) {
        // Remove row
        // Hide remove button if only 1 row left
    }
});
```

**Form submission:**
```javascript
// Parse FormData
// Build assignments array
for (const [key, value] of formData.entries()) {
    if (key.startsWith('assignments[') && value) {
        const responseId = key.match(/assignments\[(\d+)\]/)[1];
        const [evaluatorType, evaluatorValue] = value.split('_');
        
        assignments.push({
            response_id: parseInt(responseId),
            evaluator_type: evaluatorType,
            evaluator_value: parseInt(evaluatorValue)
        });
    }
}

// Send as FormData with JSON string
payload.append('assignments', JSON.stringify(assignments));
```

## 5. Luồng hoạt động mới

### Cấu hình bộ tiêu chí:
1. Teacher chọn bộ tiêu chí từ dropdown
2. Submit form → `/api/evaluate/set_criteria.php`
3. API validate và lưu vào `task_evaluate` table
4. Reload page

### Phân công chấm bài:
1. Hệ thống hiển thị danh sách **responses** đã nộp (không phải delivers)
2. Teacher chọn người chấm cho từng response:
   - Click "+ Thêm người chấm" để add thêm evaluator
   - Select từ dropdown (user hoặc group)
   - Click "X" để xóa evaluator
3. Submit form → JavaScript parse thành JSON
4. POST đến `/api/evaluate/assign_evaluators.php` với format:
   ```
   task_id=24
   assignments=[{"response_id":123,"evaluator_type":"user","evaluator_value":456},...]
   ```
5. API loop qua từng assignment:
   - Find/create deliver record cho evaluator
   - Create `deliver_evaluate` record
   - Check prevent self-evaluation
6. Return success/errors
7. Reload page

## 6. Database flow

### Task_evaluate table:
```
id_task | id_group_tc
--------|------------
24      | 3
```

### Deliver table (evaluator records):
```
id_deliver | task_id | type  | value | response | status
-----------|---------|-------|-------|----------|-------
101        | 24      | user  | 456   | NULL     | 0      (evaluator)
102        | 24      | group | 7     | NULL     | 0      (evaluator)
50         | 24      | user  | 390   | 123      | 2      (submitter)
```

### Deliver_evaluate table:
```
id_deliver | response_review
-----------|----------------
101        | 123           (user 456 chấm response 123)
102        | 123           (group 7 chấm response 123)
```

## 7. Ưu điểm của cấu trúc mới

✅ **Tuân thủ chuẩn project:**
- API files giống `manage_tasks.php`, `manage_criteria.php`
- Logic trong helper functions
- ob_start/ob_end_clean pattern
- Consistent error handling

✅ **Linh hoạt hơn:**
- Một response có thể được chấm bởi nhiều người
- Dễ dàng thêm/xóa evaluators qua UI
- Hỗ trợ cả user và group làm evaluator

✅ **Dữ liệu chính xác:**
- Hiển thị responses thực tế đã nộp, không phải delivers
- Không hiển thị responses chưa nộp (status != 2)
- Prevent self-evaluation ở cả UI lẫn API

✅ **Bảo mật tốt hơn:**
- Validate permissions ở mọi endpoint
- Check hasEvaluations trước khi cho đổi criteria
- Transaction trong assignEvaluator()

## 8. Testing checklist

- [ ] Chọn bộ tiêu chí cho task
- [ ] Hiển thị list responses đã nộp
- [ ] Add nhiều evaluators cho 1 response
- [ ] Remove evaluator
- [ ] Submit phân công
- [ ] Verify deliver + deliver_evaluate records được tạo
- [ ] Không cho self-evaluation
- [ ] Không cho đổi criteria sau khi có evaluation
- [ ] Permission checks hoạt động
- [ ] UI responsive với nhiều evaluators
