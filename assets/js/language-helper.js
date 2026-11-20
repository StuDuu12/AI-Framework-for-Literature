/**
 * Language Helper Functions
 * Quick utilities for adding multi-language support to new pages
 */

/**
 * Quick reference guide for adding translations to HTML elements
 * 
 * USAGE EXAMPLES:
 * ===============
 * 
 * 1. Simple text translation:
 *    <h1 data-lang-key="page_title">Tiêu đề trang</h1>
 * 
 * 2. Button text:
 *    <button>
 *        <span data-lang-key="save">Lưu</span>
 *    </button>
 * 
 * 3. Input placeholder:
 *    <input placeholder="Nhập tên..." data-lang-key="enter_name">
 * 
 * 4. Title/tooltip:
 *    <button title="Xóa" data-lang-key="delete">
 *        <i class="bi bi-trash"></i>
 *    </button>
 * 
 * 5. Dynamic content (JavaScript):
 *    const text = translate('success_message');
 *    element.textContent = text;
 * 
 * 6. Alerts/Toasts:
 *    UI.error(translate('error_message'));
 *    showToast(translate('saved_successfully'), 'success');
 */

// Common translation patterns for quick copy-paste
const COMMON_PATTERNS = {
    // Form labels
    FORM_LABELS: {
        title: '<label data-lang-key="title">Tiêu đề</label>',
        description: '<label data-lang-key="description">Mô tả</label>',
        content: '<label data-lang-key="content">Nội dung</label>',
        name: '<label data-lang-key="name">Tên</label>',
        email: '<label data-lang-key="email">Email</label>',
        password: '<label data-lang-key="password">Mật khẩu</label>',
    },

    // Buttons
    BUTTONS: {
        save: '<button><span data-lang-key="save">Lưu</span></button>',
        cancel: '<button><span data-lang-key="cancel">Hủy</span></button>',
        delete: '<button><span data-lang-key="delete">Xóa</span></button>',
        edit: '<button><span data-lang-key="edit">Sửa</span></button>',
        add: '<button><span data-lang-key="add">Thêm</span></button>',
        search: '<button><span data-lang-key="search">Tìm kiếm</span></button>',
        submit: '<button><span data-lang-key="submit">Gửi</span></button>',
    },

    // Messages
    MESSAGES: {
        success: 'translate("success")',
        error: 'translate("error")',
        loading: 'translate("loading")',
        no_data: 'translate("no_data")',
    },

    // Status badges
    STATUS: {
        active: '<span class="badge" data-lang-key="active">Hoạt động</span>',
        inactive: '<span class="badge" data-lang-key="inactive">Không hoạt động</span>',
        pending: '<span class="badge" data-lang-key="pending">Đang chờ</span>',
        completed: '<span class="badge" data-lang-key="completed">Hoàn thành</span>',
    }
};

// ============================================
// PAGES TO UPDATE - Developer Checklist
// ============================================

const PAGES_CHECKLIST = {
    // ✅ COMPLETED
    completed: [
        'views/templates/header.php',
        'views/templates/footer.php',
        'views/homework/genAI_BTVN.php (partial)',
    ],

    // ⏳ IN PROGRESS
    inProgress: [
        'views/homework/genAI_BTVN.php - Need to add more keys',
    ],

    // 📋 TODO - High Priority
    highPriority: [
        'views/dashboard.php',
        'views/homeclass.php',
        'views/templates/sidebar.php',
        'views/templates/sidebar_admin.php',
        'views/templates/sidebar_teacher.php',
        'views/templates/sidebar_student.php',
    ],

    // 📋 TODO - Medium Priority
    mediumPriority: [
        'views/account/login.php',
        'views/admin/dashboard.php',
        'views/admin/create_user.php',
        'views/admin/edit_user.php',
        'views/homework/*',
        'views/tasks/*',
    ],

    // 📋 TODO - Low Priority
    lowPriority: [
        'views/criteria/*',
        'views/evaluate/*',
        'views/phase/*',
        'views/reflect/*',
        'views/participants_groups/*',
    ]
};

// ============================================
// HELPER FUNCTIONS FOR JAVASCRIPT
// ============================================

/**
 * Translate key to current language
 * @param {string} key - Translation key
 * @returns {string} Translated text
 */
function t(key) {
    return window.languageSwitcher ? window.languageSwitcher.translate(key) : key;
}

/**
 * Get current language code
 * @returns {string} 'vi' or 'en'
 */
function getCurrentLang() {
    return window.languageSwitcher ? window.languageSwitcher.getCurrentLanguage() : 'vi';
}

/**
 * Resolve bilingual criteria text stored as JSON {"vi":"...","en":"..."} or "VI | EN".
 * @param {string} raw
 * @param {string|null} lang
 * @returns {string}
 */
function criteriaLabel(raw, lang = null) {
    if (!raw) return '';
    const useLang = lang || getCurrentLang();
    const text = String(raw).trim();

    if (text.charAt(0) === '{') {
        try {
            const decoded = JSON.parse(text);
            if (decoded && typeof decoded === 'object') {
                if (decoded[useLang]) return decoded[useLang];
                if (decoded.vi) return decoded.vi;
                if (decoded.en) return decoded.en;
            }
        } catch (e) { /* fall through */ }
    }

    if (text.includes(' | ')) {
        const parts = text.split(' | ').map(s => s.trim());
        return useLang === 'en' && parts[1] ? parts[1] : parts[0];
    }

    return text;
}

/**
 * Switch to specific language
 * @param {string} lang - 'vi' or 'en'
 */
function setLang(lang) {
    if (window.languageSwitcher) {
        window.languageSwitcher.setLanguage(lang);
    }
}

/**
 * Create translated element dynamically
 * @param {string} tag - HTML tag name
 * @param {string} langKey - Translation key
 * @param {string} defaultText - Default text (fallback)
 * @returns {HTMLElement}
 */
function createTranslatedElement(tag, langKey, defaultText = '') {
    const element = document.createElement(tag);
    element.setAttribute('data-lang-key', langKey);
    element.textContent = t(langKey) || defaultText;
    return element;
}

/**
 * Add translation to existing element
 * @param {HTMLElement} element - Target element
 * @param {string} langKey - Translation key
 */
function addTranslation(element, langKey) {
    if (element) {
        element.setAttribute('data-lang-key', langKey);
        if (element.tagName === 'INPUT' || element.tagName === 'TEXTAREA') {
            element.placeholder = t(langKey);
        } else {
            element.textContent = t(langKey);
        }
    }
}

/**
 * Batch translate multiple elements
 * @param {Array} elements - Array of {selector, key} objects
 */
function batchTranslate(elements) {
    elements.forEach(({ selector, key }) => {
        const element = document.querySelector(selector);
        if (element) {
            addTranslation(element, key);
        }
    });
}

// Example usage:
/*
batchTranslate([
    { selector: '#save-btn', key: 'save' },
    { selector: '#cancel-btn', key: 'cancel' },
    { selector: '#title-input', key: 'enter_title' },
]);
*/

// ============================================
// PHP HELPER SNIPPETS
// ============================================

/**
 * PHP code snippets for adding translations
 * 
 * 1. Simple span with translation:
 *    <span data-lang-key="role_<?= $role ?>"><?= $roleText ?></span>
 * 
 * 2. Button with translation:
 *    <button>
 *        <span data-lang-key="save">Lưu</span>
 *    </button>
 * 
 * 3. Form label:
 *    <label for="title" data-lang-key="title">Tiêu đề</label>
 * 
 * 4. Input placeholder:
 *    <input placeholder="Nhập tên" data-lang-key="enter_name">
 * 
 * 5. Dynamic role label (like in header):
 *    <?php
 *    switch ($role) {
 *        case 'admin':
 *            echo '<span data-lang-key="role_admin">Quản trị viên</span>';
 *            break;
 *        case 'teacher':
 *            echo '<span data-lang-key="role_teacher">Giáo viên</span>';
 *            break;
 *    }
 *    ?>
 */

// ============================================
// ADDING NEW TRANSLATION KEYS
// ============================================

/**
 * Steps to add new translation keys:
 * 
 * 1. Open assets/js/translations.js
 * 
 * 2. Add to BOTH 'vi' and 'en' objects:
 *    vi: {
 *        'your_new_key': 'Văn bản tiếng Việt',
 *    },
 *    en: {
 *        'your_new_key': 'English text',
 *    }
 * 
 * 3. Use in HTML:
 *    <span data-lang-key="your_new_key">Default text</span>
 * 
 * 4. Or in JavaScript:
 *    const text = t('your_new_key');
 * 
 * 5. Test by switching languages
 */

// ============================================
// COMMON MISTAKES TO AVOID
// ============================================

/**
 * ❌ DON'T DO THIS:
 * 
 * 1. Hardcoded text without data-lang-key:
 *    <button>Lưu</button>  // Will NOT translate
 * 
 * 2. Missing key in translations.js:
 *    <span data-lang-key="nonexistent_key">Text</span>  // Will show key name
 * 
 * 3. Using innerHTML with translations:
 *    element.innerHTML = translate('key');  // May break if HTML in translation
 * 
 * 4. Not handling dynamic content:
 *    // Add data-lang-key to dynamically created elements too!
 * 
 * ✅ DO THIS INSTEAD:
 * 
 * 1. Always add data-lang-key:
 *    <button><span data-lang-key="save">Lưu</span></button>
 * 
 * 2. Check key exists in both languages
 * 
 * 3. Use textContent for safety:
 *    element.textContent = translate('key');
 * 
 * 4. Dynamic elements automatically detected by MutationObserver
 */

console.log('📖 Language Helper loaded. See comments for usage examples.');
