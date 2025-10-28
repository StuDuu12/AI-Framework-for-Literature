# API Save & Load Analysis - Hướng dẫn sử dụng

## Tổng quan

Hệ thống lưu và tải kết quả phân tích văn học với các tính năng:
- ✅ Kiểm tra response đã tồn tại cho user + task
- ✅ Tạo mới hoặc cập nhật response
- ✅ Lưu nội dung sections có thứ tự
- ✅ Lưu lịch sử chat AI vào json_ai
- ✅ Tự động load lại khi mở trang
- ✅ Cho phép chỉnh sửa và lưu lại

---

## API Endpoints

### 1. Save Analysis - `POST /api/save_analysis.php`

**Chức năng:**
- Kiểm tra user đã có response cho task chưa
- Nếu **chưa có** → tạo response mới
- Nếu **có rồi** → update response + xóa content cũ + thêm content mới
- Lưu lịch sử AI (analysis data + conversation) vào `json_ai`

**Request Body:**
```json
{
  "task_id": 8,
  "title": "Tản Mạn Tây Hồ",
  "sections": [
    "## Nỗi nhớ và sự hoài niệm về tình yêu đã qua\nBài thơ là dòng tâm sự...",
    "## Sự hữu hạn của thời gian và vẻ đẹp vĩnh cửu của ký ức\nThời gian được nhắc đến...",
    "..."
  ],
  "ai_history": {
    "analysisData": { ... },
    "conversationHistory": [ ... ],
    "savedAt": "2025-10-28T10:30:00Z"
  }
}
```

**Response - Success (New):**
```json
{
  "success": true,
  "message": "Đã lưu kết quả thành công",
  "data": {
    "response_id": 123,
    "content_ids": [456, 457, 458, ...],
    "is_new": true,
    "total_sections": 15
  }
}
```

**Response - Success (Update):**
```json
{
  "success": true,
  "message": "Đã cập nhật kết quả thành công",
  "data": {
    "response_id": 123,
    "content_ids": [500, 501, 502, ...],
    "is_new": false,
    "total_sections": 16
  }
}
```

**Response - Error:**
```json
{
  "error": "Invalid task_id",
  "message": "Task ID không hợp lệ"
}
```

---

### 2. Load Analysis - `GET /api/load_analysis.php`

**Chức năng:**
- Lấy response hiện tại của user cho task
- Lấy tất cả content sections đã lưu
- Parse AI history từ json_ai
- Trả về dữ liệu đã format

**Query Parameters:**
```
GET /api/load_analysis.php?task_id=8
```

**Response - Has Data:**
```json
{
  "success": true,
  "has_data": true,
  "data": {
    "response_id": 123,
    "title": "Tản Mạn Tây Hồ",
    "sections": [
      {
        "content": "## Nỗi nhớ...",
        "order": 1,
        "content_id": 456
      },
      {
        "content": "## Sự hữu hạn...",
        "order": 2,
        "content_id": 457
      }
    ],
    "ai_history": {
      "analysisData": { ... },
      "conversationHistory": [ ... ],
      "savedAt": "2025-10-28T10:30:00Z"
    },
    "created_at": "2025-10-28 10:30:00",
    "updated_at": "2025-10-28 11:45:00"
  }
}
```

**Response - No Data:**
```json
{
  "success": true,
  "has_data": false,
  "message": "Chưa có dữ liệu đã lưu"
}
```

---

## Luồng hoạt động

### A. Luồng Save (User bấm "Lưu thay đổi")

```
1. User chỉnh sửa content trong textareas
2. User bấm nút "Lưu thay đổi"
3. JavaScript thu thập:
   - Title từ .editable-title
   - Sections từ .sortable-section (theo thứ tự hiện tại)
   - AI history: currentAnalysisData + conversationHistory
4. Gửi POST /api/save_analysis.php
5. Backend:
   ├─ Kiểm tra task_id hợp lệ
   ├─ Kiểm tra quyền truy cập
   ├─ Tìm response hiện tại: getResponseUserByTask(task_id, user_id)
   ├─ NẾU có response:
   │  ├─ UPDATE response.json_ai
   │  ├─ DELETE FROM content WHERE response_id = ?
   │  └─ INSERT content mới
   └─ NẾU chưa có:
      ├─ INSERT response mới
      └─ INSERT content
6. Trả về response_id + content_ids
7. Frontend hiển thị thông báo "Đã lưu!"
```

### B. Luồng Load (User mở trang)

```
1. Page load → DOMContentLoaded event
2. JavaScript gọi loadSavedAnalysis()
3. Kiểm tra URL có task_id không
4. NẾU có task_id:
   ├─ Gửi GET /api/load_analysis.php?task_id=X
   ├─ Backend:
   │  ├─ Tìm response: getResponseUserByTask(task_id, user_id)
   │  ├─ Lấy content: getContentByResponse(response_id)
   │  ├─ Parse json_ai
   │  └─ Format và trả về
   ├─ Frontend:
   │  ├─ Restore currentAnalysisData
   │  ├─ Restore conversationHistory
   │  ├─ Render UI với displaySavedAnalysis()
   │  ├─ Enable edit mode
   │  └─ Show notification "Đã tải kết quả đã lưu"
   └─ User có thể chỉnh sửa và lưu lại
5. NẾU không có task_id:
   └─ Không làm gì (trang trống)
```

---

## Database Schema

### Bảng `response`
```sql
id_response (PK)
task_id (FK)
user_id (FK) -- người thực hiện save
owner_user_id (FK) -- chủ sở hữu bài làm (thường = user_id)
parent_response
json_ai (TEXT) -- Lưu lịch sử AI: analysisData + conversationHistory
created_at
updated_at
```

### Bảng `content`
```sql
content_id (PK)
response_id (FK)
content (TEXT) -- Nội dung section
order (INT) -- Thứ tự hiển thị
type_create ('user' | 'ai')
added_by (FK)
created_at
updated_at
```

---

## Ví dụ thực tế

### Scenario 1: Lần đầu phân tích và lưu

**Bước 1:** User truy cập
```
?route=class/homework/analysis_chat&task_id=8
```

**Bước 2:** Nội dung văn bản tự động load vào textarea

**Bước 3:** User bấm "Phân tích" → AI trả về kết quả

**Bước 4:** User chỉnh sửa một số sections

**Bước 5:** User bấm "Lưu thay đổi"
- API kiểm tra: `getResponseUserByTask(8, 388)` → **NULL**
- API tạo mới: `INSERT INTO response` → response_id = 123
- API lưu content: 15 sections → content_ids = [456-470]
- Frontend: "Đã lưu kết quả thành công"

---

### Scenario 2: Mở lại và chỉnh sửa

**Bước 1:** User truy cập lại URL cũ
```
?route=class/homework/analysis_chat&task_id=8
```

**Bước 2:** DOMContentLoaded → `loadSavedAnalysis()`
- API: `GET /api/load_analysis.php?task_id=8`
- Backend: `getResponseUserByTask(8, 388)` → response_id = 123
- Backend: `getContentByResponse(123)` → 15 sections
- Frontend: Render 15 sections với nút "Lưu thay đổi"

**Bước 3:** User thấy kết quả cũ, chỉnh sửa thêm

**Bước 4:** User drag & drop sắp xếp lại

**Bước 5:** User bấm "Lưu thay đổi"
- API: `getResponseUserByTask(8, 388)` → response_id = 123 (có rồi)
- API: `UPDATE response SET json_ai = ... WHERE id = 123`
- API: `DELETE FROM content WHERE response_id = 123`
- API: `INSERT INTO content` → content_ids = [500-514]
- Frontend: "Đã cập nhật kết quả thành công"

---

## JavaScript Functions

### 1. handleSaveAnalysis()
```javascript
async function handleSaveAnalysis() {
  // Thu thập task_id, title, sections
  // Chuẩn bị ai_history
  // POST /api/save_analysis.php
  // Hiển thị loading → success → restore button
}
```

### 2. loadSavedAnalysis()
```javascript
async function loadSavedAnalysis() {
  // Lấy task_id từ URL
  // GET /api/load_analysis.php
  // Restore currentAnalysisData + conversationHistory
  // Gọi displaySavedAnalysis()
  // Show notification
}
```

### 3. displaySavedAnalysis(savedData)
```javascript
function displaySavedAnalysis(savedData) {
  // Tạo HTML cho title + sections
  // Wrap mỗi section với createSortableSection()
  // Add event listeners (save, add section, drag & drop)
  // Initialize auto-resize
}
```

---

## Security

✅ **Authentication:**
- Kiểm tra `$_SESSION['user']` có tồn tại
- Trả 401 Unauthorized nếu chưa login

✅ **Authorization:**
- Student: Kiểm tra `checkTaskAssignedToStudent(task_id, user_id)`
- Teacher: Cho phép truy cập mọi task trong class của mình

✅ **Input Validation:**
- `intval()` cho tất cả IDs
- `json_encode()` với `JSON_UNESCAPED_UNICODE`
- `htmlspecialchars()` trong JavaScript escapeHtml()

✅ **SQL Injection Prevention:**
- Sử dụng PDO prepared statements
- `bindValue()` với type hints

---

## Testing

### Test Case 1: Lưu lần đầu
```bash
# Login as student (user_id = 388)
# Truy cập task_id = 8
# Phân tích văn bản
# Bấm "Lưu thay đổi"

Expected:
- Response mới được tạo
- 15+ content rows được insert
- json_ai chứa analysisData + conversationHistory
- Message: "Đã lưu kết quả thành công"
```

### Test Case 2: Load lại
```bash
# Refresh trang hoặc truy cập lại URL
Expected:
- DOMContentLoaded → loadSavedAnalysis()
- API trả về has_data = true
- UI render 15 sections
- Notification: "Đã tải kết quả phân tích đã lưu"
```

### Test Case 3: Chỉnh sửa và lưu lại
```bash
# Thêm 1 section mới
# Xóa 1 section cũ
# Drag & drop sắp xếp
# Bấm "Lưu thay đổi"

Expected:
- Content cũ bị xóa (DELETE)
- Content mới được insert (14 sections)
- Response được update
- Message: "Đã cập nhật kết quả thành công"
```

### Test Case 4: Không có quyền
```bash
# Login as student A
# Truy cập task không được assign cho A

Expected:
- 403 Forbidden
- Message: "Bạn không có quyền truy cập nhiệm vụ này"
```

---

## Troubleshooting

**Problem:** "Không tìm thấy task ID"
- **Solution:** Kiểm tra URL có `?task_id=X`

**Problem:** "Không có nội dung để lưu"
- **Solution:** Phân tích văn bản trước khi lưu

**Problem:** Load không thấy dữ liệu cũ
- **Solution:** 
  - Check console log
  - Verify response tồn tại trong DB
  - Check user_id match

**Problem:** Save bị lỗi 500
- **Solution:**
  - Check PHP error log
  - Verify PDO connection
  - Check json_ai format

---

## Future Enhancements

🔮 **Version History:**
- Lưu nhiều versions của cùng 1 response
- Cho phép rollback về version cũ

🔮 **Collaborative Editing:**
- Nhiều user cùng edit (teacher + student)
- Real-time sync

🔮 **Auto-save:**
- Tự động lưu mỗi 30 giây
- Draft mode

🔮 **Export:**
- Export to PDF/Word
- Email kết quả

🔮 **Comments:**
- Teacher comment vào từng section
- Student reply

---

## File Structure

```
LiteratureVer2/
├── api/
│   ├── save_analysis.php     # Save endpoint
│   ├── load_analysis.php     # Load endpoint
│   └── call_api.php          # AI analysis (existing)
├── config/
│   └── helper_homework.php   # Helper functions
├── views/
│   └── homework/
│       ├── genAI_BTVN.php    # Main UI
│       └── README_genAI_BTVN.md
└── database/
    └── diemdanh_literature.sql
```

---

## Kết luận

Hệ thống đã hoàn thiện:
- ✅ Save analysis với check existing response
- ✅ Load analysis tự động khi mở trang
- ✅ Edit và re-save với update logic
- ✅ Lưu AI history vào json_ai
- ✅ Security + validation đầy đủ

User experience:
1. Truy cập link bài tập → Nội dung tự động load
2. Phân tích → Chỉnh sửa → Lưu
3. Mở lại → Dữ liệu cũ tự động hiển thị
4. Tiếp tục chỉnh sửa → Lưu lại (update)
