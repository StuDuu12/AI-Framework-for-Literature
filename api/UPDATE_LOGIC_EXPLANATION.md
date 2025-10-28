# Update Logic cho Save Analysis

## ❌ Old Logic (DELETE + INSERT)

### Vấn đề:
```php
// Xóa TẤT CẢ content cũ
DELETE FROM content WHERE response_id = 123

// Insert lại TẤT CẢ
INSERT INTO content ... (15 rows)
```

**Nhược điểm:**
- ❌ Mất `content_id` gốc → Các bình luận (comments) liên kết đến content sẽ bị mất
- ❌ Mất lịch sử `created_at` của từng section
- ❌ Performance kém khi có nhiều sections
- ❌ Không thể track changes history

---

## ✅ New Logic (UPDATE + INSERT + DELETE)

### Cải tiến:
```php
// 1. UPDATE content hiện có (giữ nguyên content_id)
UPDATE content SET content = ?, updated_at = NOW() WHERE content_id = 456

// 2. INSERT content mới (nếu user thêm sections)
INSERT INTO content ... (1 row mới)

// 3. DELETE content thừa (nếu user xóa sections)
DELETE FROM content WHERE content_id IN (470, 471)
```

**Ưu điểm:**
- ✅ Giữ nguyên `content_id` → Comments vẫn còn
- ✅ Giữ nguyên `created_at` → Track được thời gian tạo gốc
- ✅ Performance tốt hơn (chỉ thay đổi những gì cần)
- ✅ Có thể track changes qua `updated_at`

---

## Chi tiết Logic

### Step 1: Lấy existing content
```php
if ($existingResponse) {
    $response_id = $existingResponse['id_response'];
    
    // Get existing content for this response
    $existingContent = getContentByResponse($response_id);
    // Result: [
    //   {content_id: 456, order: 0, content: "# Title"},
    //   {content_id: 457, order: 1, content: "## Section 1"},
    //   {content_id: 458, order: 2, content: "## Section 2"},
    //   ...
    // ]
}
```

### Step 2: Tạo map theo order
```php
$existingByOrder = [];
foreach ($existingContent as $content) {
    $existingByOrder[$content['order']] = $content;
}
// Result: {
//   0: {content_id: 456, ...},
//   1: {content_id: 457, ...},
//   2: {content_id: 458, ...},
//   ...
// }
```

### Step 3: Loop qua content mới
```php
foreach ($contentArray as $contentItem) {
    $order = $contentItem['order'];
    
    if (isset($existingByOrder[$order])) {
        // CASE A: Order này đã tồn tại → UPDATE
        $content_id = $existingByOrder[$order]['content_id'];
        UPDATE content 
        SET content = :content, updated_at = NOW()
        WHERE content_id = :content_id
        
        // Remove khỏi map (để biết cái nào chưa xử lý)
        unset($existingByOrder[$order]);
        
    } else {
        // CASE B: Order này chưa tồn tại → INSERT mới
        INSERT INTO content (response_id, content, `order`, ...)
        VALUES (:response_id, :content, :order, ...)
    }
}
```

### Step 4: Xóa content thừa
```php
// Những gì còn lại trong $existingByOrder là content đã bị user xóa
foreach ($existingByOrder as $remainingContent) {
    DELETE FROM content WHERE content_id = :content_id
}
```

---

## Ví dụ thực tế

### Scenario 1: Chỉ chỉnh sửa nội dung (không thay đổi số sections)

**Before:**
```
Order 0: content_id=456, content="# Old Title"
Order 1: content_id=457, content="## Section A"
Order 2: content_id=458, content="## Section B"
```

**User edits:**
- Title: "# Old Title" → "# New Title"
- Section A: "## Section A" → "## Section A Updated"

**After:**
```
Order 0: content_id=456, content="# New Title"        ← UPDATE
Order 1: content_id=457, content="## Section A Updated" ← UPDATE
Order 2: content_id=458, content="## Section B"        ← UPDATE (không đổi nhưng vẫn update)
```

**SQL:**
```sql
UPDATE content SET content='# New Title', updated_at=NOW() WHERE content_id=456
UPDATE content SET content='## Section A Updated', updated_at=NOW() WHERE content_id=457
UPDATE content SET content='## Section B', updated_at=NOW() WHERE content_id=458
```

**Result:** 3 UPDATEs, 0 INSERTs, 0 DELETEs

---

### Scenario 2: Thêm section mới

**Before:**
```
Order 0: content_id=456, content="# Title"
Order 1: content_id=457, content="## Section A"
Order 2: content_id=458, content="## Section B"
```

**User adds:**
- Order 3: "## Section C (New)"

**After:**
```
Order 0: content_id=456, content="# Title"           ← UPDATE
Order 1: content_id=457, content="## Section A"      ← UPDATE
Order 2: content_id=458, content="## Section B"      ← UPDATE
Order 3: content_id=500, content="## Section C (New)" ← INSERT NEW
```

**SQL:**
```sql
UPDATE content SET content='# Title', updated_at=NOW() WHERE content_id=456
UPDATE content SET content='## Section A', updated_at=NOW() WHERE content_id=457
UPDATE content SET content='## Section B', updated_at=NOW() WHERE content_id=458
INSERT INTO content (response_id, content, `order`, ...) VALUES (123, '## Section C (New)', 3, ...)
```

**Result:** 3 UPDATEs, 1 INSERT, 0 DELETEs

---

### Scenario 3: Xóa section

**Before:**
```
Order 0: content_id=456, content="# Title"
Order 1: content_id=457, content="## Section A"
Order 2: content_id=458, content="## Section B"
Order 3: content_id=459, content="## Section C"
```

**User deletes:**
- Section B (order 2)

**After:**
```
Order 0: content_id=456, content="# Title"     ← UPDATE
Order 1: content_id=457, content="## Section A" ← UPDATE
Order 2: content_id=459, content="## Section C" ← UPDATE (order thay đổi từ 3→2)
```

**SQL:**
```sql
UPDATE content SET content='# Title', updated_at=NOW() WHERE content_id=456
UPDATE content SET content='## Section A', updated_at=NOW() WHERE content_id=457
UPDATE content SET content='## Section C', updated_at=NOW() WHERE content_id=459
DELETE FROM content WHERE content_id=458  ← Old order 2 bị xóa
```

**Result:** 3 UPDATEs, 0 INSERTs, 1 DELETE

**Lưu ý:** Order của Section C thay đổi từ 3 → 2, nhưng `content_id=459` vẫn giữ nguyên!

---

### Scenario 4: Drag & Drop (Đổi thứ tự)

**Before:**
```
Order 0: content_id=456, content="# Title"
Order 1: content_id=457, content="## Section A"
Order 2: content_id=458, content="## Section B"
Order 3: content_id=459, content="## Section C"
```

**User drags:**
- Section C lên trên Section A

**After (Frontend sends):**
```javascript
sections = [
  "## Section C",  // order=1 (từ 3 lên 1)
  "## Section A",  // order=2 (từ 1 xuống 2)
  "## Section B"   // order=3 (từ 2 xuống 3)
]
```

**Backend receives:**
```
Order 0: content="# Title"
Order 1: content="## Section C"  // Khớp với old order 3
Order 2: content="## Section A"  // Khớp với old order 1
Order 3: content="## Section B"  // Khớp với old order 2
```

**SQL:**
```sql
UPDATE content SET content='# Title', updated_at=NOW() WHERE content_id=456
UPDATE content SET content='## Section C', updated_at=NOW() WHERE content_id=459  ← order 1 (old order 3)
UPDATE content SET content='## Section A', updated_at=NOW() WHERE content_id=457  ← order 2 (old order 1)
UPDATE content SET content='## Section B', updated_at=NOW() WHERE content_id=458  ← order 3 (old order 2)
```

**Result:** 4 UPDATEs, 0 INSERTs, 0 DELETEs

**⚠️ Vấn đề:** Content_id không khớp với order mới!
- Order 1 có content_id=459 (vốn là order 3)
- Order 2 có content_id=457 (vốn là order 1)

**🔍 Giải pháp:** Đây là acceptable! Vì:
- `order` chỉ là thứ tự hiển thị (có thể thay đổi)
- `content_id` là primary key (không đổi)
- Comments vẫn liên kết đúng qua `content_id`

---

## So sánh Performance

### Old Logic (DELETE + INSERT)
```sql
-- 1 DELETE query (xóa 15 rows)
DELETE FROM content WHERE response_id = 123

-- 15 INSERT queries
INSERT INTO content ... (row 1)
INSERT INTO content ... (row 2)
...
INSERT INTO content ... (row 15)

-- TOTAL: 16 queries
```

### New Logic (UPDATE + INSERT + DELETE)

**Case 1: Chỉ edit (không thêm/xóa)**
```sql
-- 15 UPDATE queries
UPDATE content SET ... WHERE content_id = 456
UPDATE content SET ... WHERE content_id = 457
...
UPDATE content SET ... WHERE content_id = 470

-- TOTAL: 15 queries (tối ưu hơn)
```

**Case 2: Edit + Add 2 sections**
```sql
-- 15 UPDATE queries
UPDATE ... (15 lần)

-- 2 INSERT queries
INSERT ... (2 lần)

-- TOTAL: 17 queries (gần tương đương)
```

**Case 3: Edit + Delete 3 sections**
```sql
-- 12 UPDATE queries (còn 12 sections)
UPDATE ... (12 lần)

-- 3 DELETE queries
DELETE ... (3 lần)

-- TOTAL: 15 queries (tốt hơn)
```

---

## Data Integrity

### Bảo toàn Comments

**Bảng comment:**
```sql
CREATE TABLE comment (
  comment_id INT PRIMARY KEY,
  content_id INT,  ← Liên kết đến content
  user_id INT,
  comment_text TEXT,
  ...
)
```

**Old Logic:**
```
User 1: Comments vào content_id=457
[Lưu lại]
→ DELETE content_id=457
→ INSERT content_id=500 (new ID)
→ Comment vẫn trỏ đến 457 (KHÔNG TỒN TẠI!) ❌
```

**New Logic:**
```
User 1: Comments vào content_id=457
[Lưu lại]
→ UPDATE content_id=457 (GIỮ NGUYÊN ID)
→ Comment vẫn trỏ đến 457 (VẪN TỒN TẠI!) ✅
```

---

## Code Flow Summary

```php
// 1. Check existing response
$existingResponse = getResponseUserByTask($task_id, $user_id);

if ($existingResponse) {
    // 2. Get existing content
    $existingContent = getContentByResponse($response_id);
    
    // 3. Create order map
    $existingByOrder = [];
    foreach ($existingContent as $content) {
        $existingByOrder[$content['order']] = $content;
    }
    
    // 4. Process new content
    foreach ($contentArray as $contentItem) {
        if (isset($existingByOrder[$order])) {
            // UPDATE existing
            UPDATE content WHERE content_id = X
            unset($existingByOrder[$order]);
        } else {
            // INSERT new
            INSERT INTO content ...
        }
    }
    
    // 5. Delete remaining (user removed)
    foreach ($existingByOrder as $remaining) {
        DELETE FROM content WHERE content_id = X
    }
}
```

---

## Testing

### Test Case 1: Pure Edit
```bash
Before: 15 sections
User edits: All 15 sections (chỉ nội dung)
After: 15 sections

Expected:
- 15 UPDATEs
- 0 INSERTs
- 0 DELETEs
- All content_ids preserved
```

### Test Case 2: Add Sections
```bash
Before: 15 sections
User adds: 3 sections
After: 18 sections

Expected:
- 15 UPDATEs (existing)
- 3 INSERTs (new)
- 0 DELETEs
```

### Test Case 3: Remove Sections
```bash
Before: 15 sections
User removes: 5 sections
After: 10 sections

Expected:
- 10 UPDATEs (remaining)
- 0 INSERTs
- 5 DELETEs (removed)
```

### Test Case 4: Mixed Operations
```bash
Before: 15 sections
User:
  - Edits: 10 sections
  - Adds: 2 sections
  - Removes: 3 sections
After: 14 sections

Expected:
- 12 UPDATEs (10 edited + 2 kept)
- 2 INSERTs (new)
- 3 DELETEs (removed)
```

---

## Conclusion

✅ **New Logic is better because:**
1. Preserves `content_id` → Comments stay valid
2. Preserves `created_at` → Track original creation time
3. Better performance for edit-only scenarios
4. More granular change tracking via `updated_at`
5. Safer for relational data integrity

✅ **Trade-offs:**
- Slightly more complex code
- More SQL queries in some cases
- But: Benefits far outweigh costs!
