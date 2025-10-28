# Response ID Flow - genAI_BTVN.php

## 📋 Tóm tắt thay đổi

### 1. **Thêm biến `currentResponseId`**
```javascript
let currentResponseId = null; // Track if user already has a response saved
```

### 2. **Chặn phân tích lại**
- Nếu đã có `currentResponseId` → Không cho phép ấn "Phân tích" lại
- Alert: "Bạn đã có phân tích được lưu cho bài tập này. Không thể phân tích lại."

### 3. **Thay đổi nút "Làm lại" → "Chuyển sang bình luận"**
- Khi có `currentResponseId`:
  - Icon: Chat bubble (thay vì reload icon)
  - Text: "Chuyển sang bình luận"
  - Color: Blue (thay vì red)
  - Action: `handleCommentRedirect()` (thay vì `handleReset()`)

### 4. **Auto-save trước khi chuyển trang**
- `handleCommentRedirect()` sẽ:
  1. Gọi `handleSaveAnalysis()` để lưu nội dung
  2. Hiển thị loading "Đang lưu..."
  3. Sau khi lưu thành công → Redirect đến `/class/homework/comment?task_id=X&topic_id=Y&phase_id=Z&user_id=U`

---

## 🔄 Flow chi tiết

### **Scenario A: User lần đầu (chưa có response)**

```
1. User mở trang genAI_BTVN.php?task_id=8&topic_id=8&phase_id=3
2. loadSavedAnalysis() check API → has_data: false
3. currentResponseId = null
4. User nhập văn bản → Ấn "Phân tích"
5. handleAnalysis() → Không bị chặn (vì currentResponseId = null)
6. Phân tích xong → displayAnalysis()
7. switchToChatMode() → Hiện nút "Làm lại" (red, reset icon)
8. User edit content → Ấn "Lưu thay đổi"
9. handleSaveAnalysis() → Lưu vào DB → currentResponseId = 123 (từ API response)
10. switchToChatMode() → Nút đổi thành "Chuyển sang bình luận" (blue, chat icon)
```

### **Scenario B: User quay lại (đã có response)**

```
1. User mở trang genAI_BTVN.php?task_id=8&topic_id=8&phase_id=3
2. loadSavedAnalysis() check API → has_data: true, response_id: 123
3. currentResponseId = 123 (lưu vào biến)
4. displaySavedAnalysis() → Hiện content đã lưu
5. switchToChatMode() → Hiện nút "Chuyển sang bình luận" (blue, chat icon)
6. User cố ấn "Phân tích" → Alert: "Bạn đã có phân tích được lưu..."
7. User edit content → Ấn "Lưu thay đổi" → UPDATE content
8. User ấn "Chuyển sang bình luận" →
   - handleCommentRedirect() gọi handleSaveAnalysis()
   - Hiện loading "Đang lưu..."
   - Lưu xong → window.location.href = "/class/homework/comment?..."
```

---

## 🎨 UI Changes

### **Trước (Chưa có responseID):**
```
┌─────────────────────────────────────────┐
│  [ Phân tích ]  [ Làm lại ]  [ Hỏi AI ] │  ← Red "Làm lại"
└─────────────────────────────────────────┘
```

### **Sau (Đã có responseID):**
```
┌──────────────────────────────────────────────────────────┐
│  [ Phân tích ]  [ Chuyển sang bình luận ]  [ Hỏi AI ]    │  ← Blue "Chuyển sang bình luận"
└──────────────────────────────────────────────────────────┘
```

**Note:** Nút "Phân tích" bị disable/alert nếu cố ấn khi đã có responseID

---

## 🛠️ Functions Modified

### 1. `handleAnalysis()` - Thêm check
```javascript
async function handleAnalysis() {
    // ✅ NEW: Check if user already has a saved response
    if (currentResponseId) {
        alert('Bạn đã có phân tích được lưu cho bài tập này. Không thể phân tích lại.');
        return;
    }
    // ... existing code
}
```

### 2. `handleSaveAnalysis()` - Lưu responseID và return result
```javascript
async function handleSaveAnalysis() {
    // ... save logic
    
    // ✅ NEW: Store response_id
    if (result.response_id) {
        currentResponseId = result.response_id;
        
        // Update UI to show "Go to Comment" button
        if (isAnalyzed) {
            switchToChatMode();
        }
    }
    
    // ✅ NEW: Return result for other functions
    return result;
}
```

### 3. `loadSavedAnalysis()` - Lưu responseID khi load
```javascript
async function loadSavedAnalysis() {
    // ... load logic
    
    // ✅ NEW: Store response_id
    if (savedData.response_id) {
        currentResponseId = savedData.response_id;
    }
    
    // ... display logic
}
```

### 4. `switchToChatMode()` - Thay đổi UI dựa trên responseID
```javascript
function switchToChatMode() {
    // ... existing code
    
    // ✅ NEW: Check if user already has a response
    if (currentResponseId) {
        // Show "Go to Comment" button
        resetButton.innerHTML = `
            <svg>...</svg>
            <span>Chuyển sang bình luận</span>
        `;
        resetButton.classList.add('border-blue-600', 'text-blue-600');
    } else {
        // Show normal "Reset" button
        resetButton.classList.remove('hidden');
    }
}
```

### 5. `handleResetOrComment()` - NEW wrapper function
```javascript
function handleResetOrComment() {
    if (currentResponseId) {
        handleCommentRedirect();
    } else {
        handleReset();
    }
}
```

### 6. `handleCommentRedirect()` - NEW function
```javascript
async function handleCommentRedirect() {
    try {
        // 1. Show loading
        resetButton.disabled = true;
        resetButton.innerHTML = 'Đang lưu...';
        
        // 2. Save first
        const saveResult = await handleSaveAnalysis();
        if (!saveResult || !saveResult.success) {
            throw new Error('Không thể lưu dữ liệu');
        }
        
        // 3. Build URL
        const urlParams = new URLSearchParams(window.location.search);
        const commentUrl = `/class/homework/comment?task_id=${taskId}&topic_id=${topicId}&phase_id=${phaseId}&user_id=${userId}`;
        
        // 4. Redirect
        window.location.href = commentUrl;
        
    } catch (error) {
        alert('Có lỗi xảy ra khi lưu và chuyển trang.');
        // Restore button
    }
}
```

---

## ✅ Testing Checklist

### Test 1: First time user (no saved response)
- [ ] Open genAI_BTVN.php?task_id=8&...
- [ ] See empty textarea
- [ ] Click "Phân tích" → Success
- [ ] See "Làm lại" button (red)
- [ ] Click "Lưu thay đổi" → Success
- [ ] Button changes to "Chuyển sang bình luận" (blue)

### Test 2: Returning user (has saved response)
- [ ] Open genAI_BTVN.php?task_id=8&...
- [ ] Auto-load saved content
- [ ] See "Chuyển sang bình luận" button (blue)
- [ ] Click "Phân tích" → Alert: "Bạn đã có phân tích..."
- [ ] Edit content → Click "Lưu thay đổi" → Success
- [ ] Click "Chuyển sang bình luận" → Loading → Redirect to comment page

### Test 3: Comment redirect
- [ ] Click "Chuyển sang bình luận"
- [ ] See loading "Đang lưu..."
- [ ] Verify content saved in DB
- [ ] Verify redirect to `/class/homework/comment?task_id=8&topic_id=8&phase_id=3&user_id=X`

---

## 📝 Notes

1. **Vẫn được edit textarea:** Dù có responseID, user vẫn có thể edit content trong textarea và save
2. **Không xóa được phân tích:** Một khi đã có response, không thể "Làm lại" (reset) nữa
3. **Auto-save trước redirect:** Đảm bảo data được lưu trước khi chuyển sang trang comment

---

## 🔗 Related Files

- `views/homework/genAI_BTVN.php` - Main UI file (modified)
- `api/save_analysis.php` - Save API (already returns response_id)
- `api/load_analysis.php` - Load API (already returns response_id)
- `api/UPDATE_LOGIC_EXPLANATION.md` - Update logic documentation
