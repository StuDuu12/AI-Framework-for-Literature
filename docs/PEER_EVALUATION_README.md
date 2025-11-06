# Hệ thống Đánh giá Chéo (Peer Evaluation)

## Tổng quan

Hệ thống đánh giá chéo cho phép giáo viên:

1. Cấu hình bộ tiêu chí đánh giá cho từng bài tập
2. Phân công học sinh/nhóm chấm bài cho nhau
3. Quản lý quá trình đánh giá

**Lưu ý quan trọng:** Trang cấu hình đánh giá chéo chỉ khả dụng cho các phase có loại **"Đánh giá đồng đẳng"** (`phase_code = 'PHASE_DANHGIANGUOIHOC'`, `m_phase_id = 4`).

## Quyền truy cập

-   **Giáo viên**: Có quyền cấu hình bộ tiêu chí và phân công chấm bài
-   **Học sinh**: Chỉ xem được bài mình cần chấm (chức năng sẽ được phát triển)
-   **Phase requirement**: Phase phải có `main_phase.phase_code = 'PHASE_DANHGIANGUOIHOC'`

## Cấu trúc Database

### 1. Bảng `nhom_tc` (Nhóm tiêu chí)

-   `nhom_tc_id`: ID nhóm tiêu chí
-   `class_id`: ID lớp học
-   `ten_nhom_tc`: Tên bộ tiêu chí (VD: "Bộ tiêu chí phân tích văn học")
-   `description`: Mô tả

### 2. Bảng `tieu_chi` (Tiêu chí)

-   `tc_id`: ID tiêu chí
-   `name_tc`: Tên tiêu chí (VD: "Nội dung phân tích")
-   `nhom_tc_id`: Thuộc nhóm tiêu chí nào
-   `description`: Mô tả chi tiết
-   `min_score`: Điểm tối thiểu
-   `max_score`: Điểm tối đa

### 3. Bảng `task_evaluate`

-   `id_task`: ID bài tập
-   `id_group_tc`: ID nhóm tiêu chí được sử dụng

**Lưu ý**: Mỗi task chỉ sử dụng 1 bộ tiêu chí. Nếu đã có người chấm thì không được thay đổi.

### 4. Bảng `deliver_evaluate`

-   `id_deliver`: ID của deliver (bài tập được giao cho người chấm)
-   `response_review`: ID của response cần được chấm

## Quy trình sử dụng

### Điều kiện tiên quyết

-   Phase phải có loại **"Đánh giá đồng đẳng"** (`PHASE_DANHGIANGUOIHOC`)
-   Giáo viên đã tạo bộ tiêu chí cho lớp
-   Học sinh đã nộp bài (có response)

### Bước 1: Tạo Phase đánh giá đồng đẳng

1. Tạo phase mới cho topic
2. Chọn loại phase: **"Đánh giá đồng đẳng"** (m_phase_id = 4)
3. Thiết lập thời gian bắt đầu và kết thúc

### Bước 2: Tạo bộ tiêu chí (One-time setup)

Giáo viên tạo bộ tiêu chí cho lớp học tại `/class/criteria?class_id=X`

### Bước 3: Cấu hình bài tập đánh giá chéo

1. Truy cập: `/evaluate/setting?task_id=X&topic_id=Y&phase_id=Z`
2. Hệ thống sẽ kiểm tra phase có phải loại "Đánh giá đồng đẳng" không
3. Chọn bộ tiêu chí đánh giá từ dropdown
4. Nhấn "Lưu cấu hình"

### Bước 4: Phân công chấm bài

1. Hệ thống hiển thị danh sách tất cả bài nộp (delivers)
2. Giáo viên chọn người chấm cho từng bài:
    - Có thể chọn học sinh cá nhân
    - Có thể chọn nhóm
    - Hệ thống tự động loại trừ việc tự chấm bài của mình
3. Sử dụng các nút hỗ trợ:
    - **Phân công ngẫu nhiên**: Random người chấm
    - **Phân công vòng tròn**: Phân đều theo vòng tròn
    - **Xóa tất cả phân công**: Reset về trạng thái ban đầu
4. Nhấn "Lưu phân công chấm bài"

## Luồng dữ liệu

### Khi phân công chấm bài:

```
1. Học sinh A nộp bài → tạo Response ID = 10
   deliver(id_deliver=1, task_id=20, type='user', value=366, response=10)

2. Giáo viên phân công Học sinh B chấm bài của A

3. Hệ thống tự động:
   a. Tạo deliver cho B (nếu chưa có):
      deliver(id_deliver=2, task_id=20, type='user', value=390, response=NULL)

   b. Tạo deliver_evaluate:
      deliver_evaluate(id_deliver=2, response_review=10)

4. Học sinh B giờ có nhiệm vụ chấm response_id=10
```

## API Endpoints

### 1. POST `/api/evaluate/set_criteria.php`

Cấu hình bộ tiêu chí cho task

**Parameters:**

-   `task_id`: ID bài tập
-   `nhom_tc_id`: ID nhóm tiêu chí

**Response:**

```json
{
    "success": true,
    "message": "Đã cấu hình bộ tiêu chí thành công"
}
```

### 2. POST `/api/evaluate/assign_evaluators.php`

Phân công người chấm bài

**Parameters:**

-   `task_id`: ID bài tập
-   `evaluator[deliver_id]`: Format "type_id" (VD: "user_390", "group_6")

**Response:**

```json
{
    "success": true,
    "message": "Đã phân công thành công 5 bài chấm",
    "assignment_count": 5
}
```

## Validation Rules

1. **Bộ tiêu chí**:

    - Phải chọn bộ tiêu chí trước khi phân công
    - Không được thay đổi nếu đã có người chấm

2. **Phân công chấm**:

    - Không được tự chấm bài của mình
    - Chỉ phân công cho bài đã nộp (có response)
    - Mỗi deliver chỉ được 1 người chấm

3. **Quyền hạn**:
    - Chỉ giáo viên mới được cấu hình
    - Học sinh chỉ xem được bài mình cần chấm

## Tính năng tự động

### Phân công ngẫu nhiên

-   Random chọn người chấm từ danh sách khả dụng
-   Tự động loại trừ người nộp bài

### Phân công vòng tròn

-   Phân đều số bài cần chấm cho mọi người
-   Đảm bảo công bằng trong việc phân công

## Ví dụ thực tế

### Scenario: Lớp 10A1 - Bài phân tích thơ

**Dữ liệu:**

-   Task ID: 20 - "Phân tích bài thơ Khúc mùa thu"
-   Học sinh: A (366), B (390), C (388)
-   Nhóm: Nhóm 1 (6), Nhóm 2 (7)

**Bước 1**: Giáo viên chọn bộ tiêu chí "Phân tích văn học"

```sql
INSERT INTO task_evaluate VALUES (20, 1);
```

**Bước 2**: Học sinh nộp bài

-   Học sinh A nộp → response_id = 10
-   Nhóm 1 nộp → response_id = 11
-   Học sinh B nộp → response_id = 12

**Bước 3**: Giáo viên phân công

-   Học sinh B chấm bài Học sinh A
-   Học sinh C chấm bài Nhóm 1
-   Nhóm 2 chấm bài Học sinh B

**Kết quả trong DB:**

```sql
-- Deliver của người chấm
INSERT INTO deliver VALUES
(4, 20, 'user', 390, NULL, 0, 389, NOW(), NOW()),  -- B nhận nhiệm vụ chấm
(5, 20, 'user', 388, NULL, 0, 389, NOW(), NOW()),  -- C nhận nhiệm vụ chấm
(6, 20, 'group', 7, NULL, 0, 389, NOW(), NOW());   -- Nhóm 2 nhận nhiệm vụ chấm

-- Phân công cụ thể
INSERT INTO deliver_evaluate VALUES
(4, 10),  -- B chấm response 10 (bài của A)
(5, 11),  -- C chấm response 11 (bài của Nhóm 1)
(6, 12);  -- Nhóm 2 chấm response 12 (bài của B)
```

## Lưu ý khi sử dụng

1. **Trước khi bắt đầu**:

    - **Đảm bảo phase có loại "Đánh giá đồng đẳng"** (bắt buộc)
    - Đảm bảo đã tạo bộ tiêu chí cho lớp
    - Đảm bảo học sinh đã nộp bài trước khi phân công

2. **Trong quá trình**:

    - Kiểm tra kỹ phân công trước khi lưu
    - Có thể điều chỉnh phân công bất cứ lúc nào (nếu chưa chấm)

3. **Sau khi hoàn thành**:
    - Không thể thay đổi bộ tiêu chí nếu đã có người chấm
    - Có thể xem báo cáo kết quả đánh giá

## Troubleshooting

### Lỗi "Không thể truy cập - Trang này chỉ khả dụng cho các phase có loại Đánh giá đồng đẳng"

→ Phase hiện tại không phải loại "Đánh giá đồng đẳng" (`PHASE_DANHGIANGUOIHOC`)
→ Giải pháp: Tạo hoặc chọn phase có `m_phase_id = 4` (Đánh giá đồng đẳng)

### Lỗi "Vui lòng cấu hình bộ tiêu chí trước"

→ Chưa chọn bộ tiêu chí trong Bước 3

### Lỗi "Đã có bài chấm được thực hiện"

→ Không thể thay đổi bộ tiêu chí vì đã có người bắt đầu chấm

### Không thấy tùy chọn phân công

→ Kiểm tra xem bài tập đã có bài nộp chưa (deliver.response != NULL)

## TODO / Tính năng tương lai

-   [ ] Trang cho học sinh thực hiện đánh giá
-   [ ] Báo cáo tổng hợp kết quả đánh giá
-   [ ] Cho phép đánh giá ẩn danh
-   [ ] Thêm deadline cho việc chấm bài
-   [ ] Gửi thông báo khi được phân công chấm
-   [ ] Xuất báo cáo Excel
