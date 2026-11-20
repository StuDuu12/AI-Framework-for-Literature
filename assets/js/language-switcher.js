/**
 * Language Switcher System
 * Handles multi-language translation for the entire application
 * 
 * Dependencies:
 * - translations.js (must be loaded first)
 * - toast.js (for Toast notifications)
 * 
 * Toast Usage:
 * - Toast.success(message, duration)
 * - Toast.error(message, duration)
 * - Toast.warning(message, duration)
 * - Toast.info(message, duration)
 * - Toast.show(message, type, duration)
 */

class LanguageSwitcher {
    constructor() {
        this.currentLang = this.getStoredLanguage() || 'vi';
        this.translations = window.translations || {};
        this.init();
    }

    /**
     * Initialize the language system
     */
    init() {
        console.log(`🌐 Language Switcher initialized - Current: ${this.currentLang}`);
        this.applyLanguage(this.currentLang);
        this.updateLanguageIcon();
        this.setupEventListeners();
    }

    /**
     * Get stored language from localStorage
     */
    getStoredLanguage() {
        return localStorage.getItem('app_language') || 'vi';
    }

    /**
     * Save language preference to localStorage
     */
    setStoredLanguage(lang) {
        localStorage.setItem('app_language', lang);
    }

    /**
     * Get translation for a key
     */
    translate(key, lang = null) {
        const targetLang = lang || this.currentLang;

        if (!key || !this.translations[targetLang]) {
            return null;
        }

        if (!Object.prototype.hasOwnProperty.call(this.translations[targetLang], key)) {
            return null;
        }

        return this.translations[targetLang][key];
    }

    /**
     * Apply language to all elements with data-lang-key attribute
     */
    applyLanguage(lang) {
        this.currentLang = lang;
        this.setStoredLanguage(lang);

        const elements = document.querySelectorAll('[data-lang-key]');
        elements.forEach(element => {
            this.translateElement(element, lang);
        });

        document.documentElement.lang = lang;
        document.cookie = 'app_language=' + encodeURIComponent(lang) + ';path=/;max-age=31536000;SameSite=Lax';

        window.dispatchEvent(new CustomEvent('languageChanged', {
            detail: { language: lang } 
        }));

        console.log(`✅ Language changed to: ${lang}`);
    }

    /**
     * Toggle between Vietnamese and English
     */
    toggleLanguage() {
        const newLang = this.currentLang === 'vi' ? 'en' : 'vi';
        this.applyLanguage(newLang);
        this.updateLanguageIcon();
        
        // Show toast notification
        this.showLanguageChangeNotification(newLang);
    }

    /**
     * Update language icon in the button
     */
    updateLanguageIcon() {
        const langIcon = document.getElementById('language-icon');
        const langText = document.getElementById('language-text');
        
        if (langIcon) {
            langIcon.textContent = this.currentLang.toUpperCase();
        }
        
        if (langText) {
            langText.textContent = this.currentLang === 'vi' ? 'Tiếng Việt' : 'English';
        }
    }

    /**
     * Show notification when language changes
     */
    showLanguageChangeNotification(lang) {
        const message = lang === 'vi' 
            ? '✅ Đã chuyển sang Tiếng Việt' 
            : '✅ Language changed to English';
        
        const langLabel = lang === 'vi' ? 'VI' : 'EN';
        const fullMessage = `${message} (${langLabel})`;
        
        // Use the global Toast instance
        if (typeof Toast !== 'undefined' && Toast.success) {
            Toast.success(fullMessage, 3500);
        } else {
            // Fallback if Toast is not available
            console.log(`🌐 ${fullMessage}`);
            // Show native alert as last resort
            UI.notify(fullMessage);
        }
    }

    /**
     * Setup event listeners
     */
    setupEventListeners() {
        // Listen for language toggle button clicks
        const langToggleBtn = document.getElementById('language-toggle-btn');
        if (langToggleBtn) {
            langToggleBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.toggleLanguage();
            });
        }

        // Listen for page loads (in case of dynamic content)
        document.addEventListener('DOMContentLoaded', () => {
            this.applyLanguage(this.currentLang);
        });

        // Observe for dynamically added elements
        this.observeDynamicContent();
    }

    /**
     * Observe for dynamically added content with data-lang-key
     */
    observeDynamicContent() {
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                mutation.addedNodes.forEach((node) => {
                    if (node.nodeType === 1) { // Element node
                        // Check if the node itself has data-lang-key
                        if (node.hasAttribute && node.hasAttribute('data-lang-key')) {
                            this.translateElement(node);
                        }
                        
                        // Check children
                        const children = node.querySelectorAll ? node.querySelectorAll('[data-lang-key]') : [];
                        children.forEach(child => this.translateElement(child));
                    }
                });
            });
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }

    /**
     * Translate a single element
     */
    translateElement(element, lang = null) {
        const key = element.getAttribute('data-lang-key');
        const translation = this.translate(key, lang);
        const attrTarget = element.getAttribute('data-lang-attr');

        if (translation === null || translation === undefined) {
            return;
        }

        if (attrTarget === 'placeholder') {
            element.placeholder = translation;
            return;
        }
        if (attrTarget === 'title') {
            element.title = translation;
            return;
        }
        if (attrTarget === 'html') {
            element.innerHTML = translation;
            return;
        }

        if (element.tagName === 'INPUT' || element.tagName === 'TEXTAREA') {
            if (element.hasAttribute('placeholder') || attrTarget === 'placeholder') {
                element.placeholder = translation;
            } else {
                element.value = translation;
            }
        } else if (element.hasAttribute('title') && !element.querySelector('[data-lang-key]')) {
            element.title = translation;
        } else if (this.hasDirectTranslatableChild(element)) {
            // Parent has translatable children — skip overwriting text
            return;
        } else {
            element.textContent = translation;
        }
    }

    /**
     * Whether element has direct child with its own translation key
     */
    hasDirectTranslatableChild(element) {
        return Array.from(element.children).some(
            child => child.hasAttribute && child.hasAttribute('data-lang-key')
        );
    }

    /**
     * Get current language
     */
    getCurrentLanguage() {
        return this.currentLang;
    }

    /**
     * Set language programmatically
     */
    setLanguage(lang) {
        if (this.translations[lang]) {
            this.applyLanguage(lang);
            this.updateLanguageIcon();
        } else {
            console.error(`Language "${lang}" not supported`);
        }
    }
}

// Initialize on page load
let languageSwitcher;

// Wait for translations to load
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initLanguageSwitcher);
} else {
    initLanguageSwitcher();
}

function initLanguageSwitcher() {
    // Wait a bit for translations.js to load
    setTimeout(() => {
        languageSwitcher = new LanguageSwitcher();
        window.languageSwitcher = languageSwitcher; // Make globally accessible
    }, 100);
}

// Expose toggle function globally for inline onclick handlers
function toggleLanguage() {
    if (window.languageSwitcher) {
        window.languageSwitcher.toggleLanguage();
    }
}

// Expose translate function globally
function translate(key) {
    const value = window.languageSwitcher ? window.languageSwitcher.translate(key) : null;
    return value ?? key;
}

window.t = translate;
