# Hướng dẫn giao diện (UI) cho dev

Tài liệu ngắn để code mới giữ được sự đồng nhất. Nguồn chuẩn: `assets/css/tokens.css`, `assets/css/ui.css`, `assets/js/ui.js`.

## 1. Màu, chữ, khoảng cách: chỉ dùng token

- **Không viết mã màu cứng** (`#4154f1`, `#333`, `#fff`…) trong CSS hay `style=""`. Thay vào đó dùng token:

| Mục đích | Token |
|---|---|
| Màu chủ đạo | `--lit-primary` |
| Màu chủ đạo khi hover | `--lit-primary-hover` |
| Nền nhạt màu chủ đạo | `--lit-primary-subtle` |
| Chữ đặt trên nền màu chủ đạo | `--lit-primary-contrast` |
| Màu nhấn (vàng giấy cũ) | `--lit-accent` |
| Màu nhấn, nền nhạt | `--lit-accent-subtle` |
| Thành công / cảnh báo / lỗi / thông tin | `--lit-success`, `--lit-warning`, `--lit-danger`, `--lit-info`; mỗi màu có biến thể `-subtle` |
| Tiêu đề | `--lit-heading` |
| Chữ thường | `--lit-text` |
| Chữ phụ | `--lit-text-secondary` |
| Chữ mờ | `--lit-text-muted` |
| Nền trang | `--lit-bg` |
| Nền card | `--lit-surface` |
| Nền phụ | `--lit-surface-2` |
| Nền hover | `--lit-surface-3` |
| Viền | `--lit-border` |
| Viền đậm | `--lit-border-strong` |
| Khoảng cách | `--sp-1` … `--sp-10` (thang 4px) |
| Bo góc | `--lit-radius-sm`, `--lit-radius-md`, `--lit-radius-lg`, `--lit-radius-xl` |
| Đổ bóng | `--lit-shadow-sm`, `--lit-shadow-md`, `--lit-shadow-lg`, `--lit-shadow-xl` |

- **Chế độ tối** chỉ đổi giá trị token (khối `.dark-theme` trong `tokens.css`). Dùng token thì chế độ tối tự đúng, không cần viết thêm rule `body.dark-theme …`.
- **Chữ:**
  - `--font-body` (Inter) cho giao diện.
  - `--font-heading` (Playfair Display) cho tiêu đề.
  - Văn bản đọc dài (tác phẩm, bài phân tích) dùng class `.reading-text` (Literata).
- **Icon:** chỉ dùng **Bootstrap Icons** (`bi bi-…`). Icon chỉ để trang trí thì thêm `aria-hidden="true"`. Nút chỉ có icon phải có `aria-label`.

## 2. Thông báo và hộp xác nhận: không dùng alert/confirm/prompt

```js
UI.success('Đã lưu bài.');
UI.error(data.message || 'Không lưu được.');
UI.warning('Vui lòng nhập tên nhóm.');
UI.notify(serverMessage);          // tự đoán loại theo nội dung
UI.networkError(err);              // lỗi mạng: câu dễ hiểu, chi tiết ra console

if (!await UI.confirm(`Xoá chủ đề "${name}"?`, { danger: true, confirmText: 'Xoá' })) return;
const reason = await UI.prompt({ title: 'Từ chối', message: 'Lý do:', multiline: true });  // null nếu huỷ
```

- **Xác nhận khai báo trong HTML**, không cần viết JS: `<a href="…" data-confirm="Xoá mục này?" data-confirm-danger>Xoá</a>`. Cách này cũng dùng được trên `<form>`.
- **Toast hiện ngay trước `location.reload()` hoặc chuyển trang** vẫn được giữ và hiện tiếp ở trang sau.
- **Từ PHP** dùng `flash_redirect($url, 'warning', 'Nội dung…')` (định nghĩa trong `config/session.php`). Trang đích tự hiện toast; không `echo "<script>alert(...)"`.
- **Viết thông báo bằng tiếng Việt và nói người dùng cần làm gì**: "Không lưu được. Vui lòng thử lại." thay cho "Error: …".

## 3. Thành phần dùng chung (`ui.css`)

| Thành phần | Class |
|---|---|
| Thẻ nhiệm vụ | `.ui-task-grid > a.ui-task`, các phần `__meta`, `__title`, `__path`, `__foot`, `__cta`; nhấn mạnh hạn bằng `--overdue` / `--soon` |
| Badge trạng thái | `.ui-badge` cùng biến thể `--primary`, `--success`, `--warning`, `--danger`, `--accent` |
| Khung trống | `.ui-empty`: icon + `.ui-empty__title` + mô tả + **nút hành động tiếp theo** |
| Khác | `.ui-card`, `.ui-section` (với `__head`, `__title`), `.ui-progress > span`, `.ui-skeleton`, `.ui-timeline`, `.ui-hero` |

### Thành phần dạng partial (PHP)

- **`views/homework/_work_header.php`**: đầu trang cho các trang làm bài với AI.
  - Hiển thị:
    - breadcrumb đầy đủ, đề bài;
    - badge loại giai đoạn, trạng thái nộp, hạn nộp;
    - thanh các bước (`$wh['steps']`);
    - trạng thái tự lưu và trạng thái AI.
  - Trạng thái lưu và AI tự cập nhật bằng cách theo dõi `fetch` tới:
    - API lưu bài: `save_*.php`, `analysis_history.php`;
    - API gọi AI: `analyze_task.php`, `call_api.php`.
  - Đổi bước từ JS: `WorkHeader.setStep(n)`.
- **`views/evaluate/_rubric_form.php`**: form chấm điểm theo tiêu chí dạng thẻ. Học sinh chấm chéo và giáo viên chấm điểm cuối đều dùng form này.
  - Có rubric (bảng `rubric_level`): hiện các thẻ mức điểm kèm mô tả.
  - Không có rubric: hiện các nút chọn điểm.
  - Luôn gửi kèm `action` (`draft` hoặc `submit`).
- **`renderSubmissionMarkdown($text)`** (`config/helper_evaluate.php`): hiển thị bài nộp dạng markdown đơn giản. Hàm escape HTML trước khi định dạng, nên **luôn dùng hàm này thay cho `echo` nội dung bài**.
- **Thang đồng thuận** (`.consensus-scale`): vị trí điểm của từng người trên thang điểm. Dùng ở trang chấm của giáo viên và trang chấm theo nhóm.

## 4. Ngôn ngữ

- **Chữ mặc định trong HTML, JS và thông báo API đều viết tiếng Việt.** `data-lang-key` chỉ dùng để chuyển sang tiếng Anh khi người dùng chọn.
- Thông báo lỗi từ PHP trả về qua `'message' => '…'` cũng hiện cho người dùng. Hãy viết câu tiếng Việt nói rõ cần làm gì, ví dụ "Vui lòng nhập tên nhóm.", thay cho "Group name is required".
- **Mọi ô nhập phải có nhãn:** `<label for="id">`, hoặc `aria-label` khi không có nhãn hiển thị.

## 5. Điều hướng

- **Link vào trang lớp, chủ đề, giai đoạn, nhiệm vụ luôn mang đủ tham số** (`task_id`, `topic_id`, `phase_id`, `deliver_id`). Router (`config/nav_context.php`) tự đặt "lớp hiện tại" theo URL nên sidebar và breadcrumb luôn đúng, kể cả khi mở nhiều tab.
- **Tên giai đoạn tiếng Việt kèm icon** lấy qua `phaseTypeMeta($phase_code)` trong `config/helper_phase.php`.
- **Trang chủ học sinh** là `/today`. `/workspace` là thư viện tác phẩm.
