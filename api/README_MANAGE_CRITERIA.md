# API Quản lý Tiêu chí Đánh giá

## File: `manage_criteria.php`

API endpoint tổng hợp để quản lý nhóm tiêu chí và tiêu chí đánh giá.

---

## 🔐 Authentication

- **Required**: User phải đăng nhập
- **Role**: Chỉ giáo viên (`teacher`) mới có quyền truy cập

---

## 📋 API Endpoints

### 1. QUẢN LÝ NHÓM TIÊU CHÍ (Criteria Groups)

#### 1.1. Lấy danh sách nhóm tiêu chí
**Method**: `GET`  
**Action**: `get_groups`

**Lấy tất cả nhóm của lớp:**
```
GET /api/manage_criteria.php?action=get_groups&class_id={class_id}
```

**Lấy chi tiết một nhóm:**
```
GET /api/manage_criteria.php?action=get_groups&nhom_tc_id={group_id}
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "nhom_tc_id": 1,
      "class_id": 5,
      "ten_nhom_tc": "Đánh giá nội dung",
      "description": "Các tiêu chí về nội dung bài làm",
      "criteria_count": 3
    }
  ]
}
```

---

#### 1.2. Tạo nhóm tiêu chí mới
**Method**: `POST`  
**Action**: `create_group`

**Parameters:**
- `action=create_group` (required)
- `class_id` (required) - ID của lớp học
- `ten_nhom_tc` (required) - Tên nhóm tiêu chí
- `description` (optional) - Mô tả nhóm

**Example:**
```javascript
const formData = new FormData();
formData.append('action', 'create_group');
formData.append('class_id', 5);
formData.append('ten_nhom_tc', 'Đánh giá ngôn ngữ');
formData.append('description', 'Các tiêu chí về ngôn ngữ văn học');

fetch('/api/manage_criteria.php', {
    method: 'POST',
    body: formData
});
```

**Response:**
```json
{
  "success": true,
  "nhom_tc_id": 2,
  "message": "Tạo nhóm tiêu chí thành công"
}
```

---

#### 1.3. Cập nhật nhóm tiêu chí
**Method**: `POST`  
**Action**: `update_group`

**Parameters:**
- `action=update_group` (required)
- `nhom_tc_id` (required) - ID của nhóm cần cập nhật
- `ten_nhom_tc` (required) - Tên nhóm mới
- `description` (optional) - Mô tả mới

**Response:**
```json
{
  "success": true,
  "message": "Cập nhật nhóm tiêu chí thành công"
}
```

---

#### 1.4. Xóa nhóm tiêu chí
**Method**: `POST`  
**Action**: `delete_group`

**Parameters (JSON body):**
```json
{
  "action": "delete_group",
  "nhom_tc_id": 2
}
```

**Example:**
```javascript
fetch('/api/manage_criteria.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        action: 'delete_group',
        nhom_tc_id: 2
    })
});
```

**Response:**
```json
{
  "success": true,
  "message": "Xóa nhóm tiêu chí thành công"
}
```

**Note:** Không thể xóa nhóm nếu còn tiêu chí bên trong.

---

### 2. QUẢN LÝ TIÊU CHÍ (Criteria)

#### 2.1. Lấy danh sách tiêu chí
**Method**: `GET`  
**Action**: `get_criteria`

**Lấy tất cả tiêu chí của nhóm:**
```
GET /api/manage_criteria.php?action=get_criteria&nhom_tc_id={group_id}
```

**Lấy chi tiết một tiêu chí:**
```
GET /api/manage_criteria.php?action=get_criteria&tc_id={criteria_id}
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "tc_id": 1,
      "nhom_tc_id": 1,
      "name_tc": "Độ chính xác nội dung",
      "description": "Đánh giá tính chính xác của nội dung",
      "min_score": 0,
      "max_score": 10
    }
  ]
}
```

---

#### 2.2. Tạo tiêu chí mới
**Method**: `POST`  
**Action**: `create_criteria`

**Parameters:**
- `action=create_criteria` (required)
- `nhom_tc_id` (required) - ID của nhóm tiêu chí
- `name_tc` (required) - Tên tiêu chí
- `description` (optional) - Mô tả tiêu chí
- `min_score` (required) - Điểm tối thiểu
- `max_score` (required) - Điểm tối đa

**Example:**
```javascript
const formData = new FormData();
formData.append('action', 'create_criteria');
formData.append('nhom_tc_id', 1);
formData.append('name_tc', 'Độ sáng tạo');
formData.append('description', 'Đánh giá tính sáng tạo trong bài làm');
formData.append('min_score', 0);
formData.append('max_score', 10);

fetch('/api/manage_criteria.php', {
    method: 'POST',
    body: formData
});
```

**Response:**
```json
{
  "success": true,
  "tc_id": 5,
  "message": "Tạo tiêu chí thành công"
}
```

---

#### 2.3. Cập nhật tiêu chí
**Method**: `POST`  
**Action**: `update_criteria`

**Parameters:**
- `action=update_criteria` (required)
- `tc_id` (required) - ID của tiêu chí cần cập nhật
- `name_tc` (required) - Tên tiêu chí mới
- `description` (optional) - Mô tả mới
- `min_score` (required) - Điểm tối thiểu mới
- `max_score` (required) - Điểm tối đa mới

**Response:**
```json
{
  "success": true,
  "message": "Cập nhật tiêu chí thành công"
}
```

---

#### 2.4. Xóa tiêu chí
**Method**: `POST`  
**Action**: `delete_criteria`

**Parameters (JSON body):**
```json
{
  "action": "delete_criteria",
  "tc_id": 5
}
```

**Example:**
```javascript
fetch('/api/manage_criteria.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        action: 'delete_criteria',
        tc_id: 5
    })
});
```

**Response:**
```json
{
  "success": true,
  "message": "Xóa tiêu chí thành công"
}
```

**Note:** Không thể xóa tiêu chí nếu đã được sử dụng trong đánh giá.

---

## 🔍 Validation Rules

### Nhóm Tiêu chí:
- `ten_nhom_tc`: Không được để trống
- `class_id`: Phải là số nguyên dương
- Giáo viên phải có quyền truy cập lớp học

### Tiêu chí:
- `name_tc`: Không được để trống
- `min_score`: Phải là số, không âm
- `max_score`: Phải là số, không âm
- `min_score` ≤ `max_score`
- `nhom_tc_id`: Nhóm tiêu chí phải tồn tại

---

## ❌ Error Responses

```json
{
  "success": false,
  "message": "Error message here"
}
```

**Common Errors:**
- `Unauthorized - Not logged in`: User chưa đăng nhập
- `Unauthorized - Only teachers can manage criteria`: User không phải giáo viên
- `Invalid class ID`: class_id không hợp lệ
- `Tên nhóm tiêu chí không được để trống`: Thiếu tên nhóm
- `Không thể xóa nhóm này vì còn X tiêu chí liên kết`: Nhóm còn tiêu chí
- `Không thể xóa tiêu chí này vì đã được sử dụng trong X đánh giá`: Tiêu chí đang được dùng
- `Điểm tối thiểu không được lớn hơn điểm tối đa`: Lỗi validate điểm số

---

## 📊 Database Schema

### Table: `nhom_tc`
```sql
CREATE TABLE `nhom_tc` (
  `nhom_tc_id` int NOT NULL AUTO_INCREMENT,
  `class_id` int NOT NULL,
  `ten_nhom_tc` varchar(100) NOT NULL,
  `description` text,
  PRIMARY KEY (`nhom_tc_id`),
  FOREIGN KEY (`class_id`) REFERENCES `class`(`class_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Table: `tieu_chi`
```sql
CREATE TABLE `tieu_chi` (
  `tc_id` int NOT NULL AUTO_INCREMENT,
  `name_tc` varchar(100) DEFAULT NULL,
  `nhom_tc_id` int DEFAULT NULL,
  `description` text,
  `min_score` int DEFAULT NULL,
  `max_score` int DEFAULT NULL,
  PRIMARY KEY (`tc_id`),
  FOREIGN KEY (`nhom_tc_id`) REFERENCES `nhom_tc`(`nhom_tc_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## 🎯 Use Cases

### 1. Tạo hệ thống tiêu chí cho lớp học
```javascript
// Bước 1: Tạo nhóm "Đánh giá nội dung"
// Bước 2: Thêm tiêu chí "Độ chính xác" (0-10 điểm)
// Bước 3: Thêm tiêu chí "Tính sáng tạo" (0-5 điểm)
// Bước 4: Tạo nhóm "Đánh giá ngôn ngữ"
// Bước 5: Thêm tiêu chí "Chính tả" (0-10 điểm)
```

### 2. Sử dụng trong đánh giá
```javascript
// Frontend lấy danh sách nhóm và tiêu chí
// Hiển thị form đánh giá cho từng tiêu chí
// Lưu điểm vào bảng detail_evaluate với tc_id
```

---

## 📝 Notes

- API sử dụng `utf8mb4` encoding để hỗ trợ tiếng Việt
- Tất cả response đều là JSON format
- Kiểm tra quyền truy cập ở mọi endpoint
- Cascade protection: phải xóa tiêu chí trước khi xóa nhóm
- Foreign key protection: không thể xóa tiêu chí đang được sử dụng trong đánh giá
