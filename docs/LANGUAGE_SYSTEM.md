# Multi-Language System Documentation

## 📖 Overview

This project now supports **Vietnamese (vi)** and **English (en)** languages with a dynamic language switcher.

## 🚀 Quick Start

### For Users
1. Look for the **language toggle button** in the top-right corner of the navigation bar (next to theme toggle)
2. Click the button to switch between **VI** (Vietnamese) and **EN** (English)
3. The page will instantly translate all supported content
4. Your language preference is saved automatically

### For Developers

#### Adding Translation to New Elements

Add the `data-lang-key` attribute to any HTML element:

```html
<!-- Text content -->
<h1 data-lang-key="analysis_title">Phân tích Văn học</h1>

<!-- Input placeholder -->
<input type="text" placeholder="Tìm kiếm" data-lang-key="search_placeholder">

<!-- Button text -->
<button>
    <span data-lang-key="save">Lưu</span>
</button>

<!-- Title attribute -->
<button title="Đổi giao diện" data-lang-key="theme_toggle">
    <i class="bi bi-moon-fill"></i>
</button>
```

#### Adding New Translations

1. Open `assets/js/translations.js`
2. Add your key-value pairs to both `vi` and `en` objects:

```javascript
const translations = {
    vi: {
        'your_key': 'Nội dung tiếng Việt',
        'another_key': 'Nội dung khác'
    },
    en: {
        'your_key': 'English content',
        'another_key': 'Another content'
    }
};
```

3. Use the key in your HTML:

```html
<span data-lang-key="your_key">Default text</span>
```

## 📂 File Structure

```
assets/js/
├── translations.js          # Translation dictionary (vi/en)
├── language-switcher.js     # Core language switching logic
├── main.js                  # Main application JS
└── toast.js                 # Toast notifications

views/templates/
├── header.php               # Header with language toggle button
├── footer.php               # Footer with translated copyright
├── assets.php               # CSS assets
└── vendors.php              # JS assets (includes language scripts)
```

## 🔧 Technical Details

### Language Switcher Class

The `LanguageSwitcher` class handles all translation logic:

```javascript
// Initialize
const languageSwitcher = new LanguageSwitcher();

// Get current language
languageSwitcher.getCurrentLanguage(); // 'vi' or 'en'

// Set language programmatically
languageSwitcher.setLanguage('en');

// Get translation for a specific key
languageSwitcher.translate('save'); // Returns 'Lưu' (vi) or 'Save' (en)
```

### Features

✅ **Auto-detection of dynamic content** - New elements with `data-lang-key` are automatically translated
✅ **LocalStorage persistence** - Language preference is saved and restored
✅ **Toast notifications** - Users get feedback when switching languages
✅ **Multiple element types** - Supports text, placeholders, titles, and button content
✅ **Event system** - Custom `languageChanged` event for other scripts to react

### Browser Support

- ✅ Chrome/Edge 90+
- ✅ Firefox 88+
- ✅ Safari 14+
- ✅ Opera 76+

## 🎨 Styling the Language Button

The language button is styled inline in `header.php`. To customize:

```css
#language-icon {
    font-weight: 600;
    font-size: 0.875rem;
    color: var(--bs-nav-link-color);
}

#language-toggle-btn:hover {
    background-color: rgba(0, 0, 0, 0.05);
}
```

## 🧪 Testing

### Test Checklist

- [ ] Header logo translates
- [ ] Search placeholder translates
- [ ] User role labels translate (Admin/Teacher/Student)
- [ ] Logout button translates
- [ ] Footer copyright translates
- [ ] Form labels translate
- [ ] Button texts translate
- [ ] Toast messages translate
- [ ] Language preference persists after page reload

### Manual Testing

1. Open browser DevTools (F12)
2. Check Console for language switcher initialization:
   ```
   🌐 Language Switcher initialized - Current: vi
   ```
3. Click language toggle button
4. Verify console shows:
   ```
   ✅ Language changed to: en
   ```
5. Reload page and verify language persists

## 📋 Translation Keys Reference

### Common Keys

| Key | Vietnamese | English |
|-----|-----------|---------|
| `system_title` | Literature System | Literature System |
| `search_placeholder` | Tìm kiếm | Search |
| `logout` | Đăng xuất | Logout |
| `save` | Lưu | Save |
| `cancel` | Hủy | Cancel |
| `delete` | Xóa | Delete |
| `edit` | Sửa | Edit |
| `add` | Thêm | Add |

For a complete list, see `assets/js/translations.js`.

## 🐛 Troubleshooting

### Language not switching?

1. Check browser console for errors
2. Verify `translations.js` is loaded before `language-switcher.js`
3. Clear browser cache (Ctrl + F5)
4. Check that elements have correct `data-lang-key` attributes

### New elements not translating?

1. Ensure `data-lang-key` attribute is present
2. Verify the key exists in `translations.js`
3. Check that the element is added to DOM after page load (dynamic content is auto-detected)

### Language preference not saving?

1. Check browser LocalStorage is enabled
2. Open DevTools → Application → LocalStorage
3. Look for `app_language` key with value `vi` or `en`

## 🔄 Future Enhancements

- [ ] Add more languages (French, Spanish, Chinese)
- [ ] Admin panel for managing translations
- [ ] Export/Import translation JSON files
- [ ] RTL (Right-to-Left) language support
- [ ] Language-specific date/time formatting
- [ ] Automatic language detection based on browser settings

## 👥 Contributing

To add a new language:

1. Add language code to `translations` object in `translations.js`
2. Translate all existing keys
3. Update language switcher to support the new language
4. Test thoroughly
5. Submit pull request

## 📝 License

This multi-language system is part of the Literature System project.
Copyright © 2026 Literature - MonTeam. All Rights Reserved.
