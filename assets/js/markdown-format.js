/**
 * Lightweight Markdown → HTML for AI chat / Q&A output (no external deps).
 */
(function (global) {
    'use strict';

    function escapeHtml(text) {
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function normalizeMarkdownInput(text) {
        return String(text)
            .replace(/\r\n/g, '\n')
            .replace(/\uFF0A/g, '*')
            .replace(/\u2217/g, '*')
            .replace(/\u00D7/g, '*');
    }

    function formatInlineMarkdown(text) {
        let t = escapeHtml(text);

        t = t.replace(/`([^`\n]+?)`/g, '<code>$1</code>');
        t = t.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
        t = t.replace(/__(.+?)__/g, '<strong>$1</strong>');
        t = t.replace(/(?<!\*)\*([^*\n]+?)\*(?!\*)/g, '<em>$1</em>');
        t = t.replace(/(?<!_)_([^_\n]+?)_(?!_)/g, '<em>$1</em>');

        return t;
    }

    function formatAiMarkdown(text) {
        if (!text) return '';

        const lines = normalizeMarkdownInput(text).split('\n');
        const html = [];
        let inList = false;

        const closeList = function () {
            if (inList) {
                html.push('</ul>');
                inList = false;
            }
        };

        for (let i = 0; i < lines.length; i++) {
            const raw = lines[i];
            const trimmed = raw.trim();

            if (trimmed === '') {
                closeList();
                continue;
            }

            if (/^###\s+/.test(trimmed)) {
                closeList();
                html.push('<h3>' + formatInlineMarkdown(trimmed.replace(/^###\s+/, '')) + '</h3>');
                continue;
            }
            if (/^##\s+/.test(trimmed)) {
                closeList();
                html.push('<h2>' + formatInlineMarkdown(trimmed.replace(/^##\s+/, '')) + '</h2>');
                continue;
            }
            if (/^#\s+/.test(trimmed)) {
                closeList();
                html.push('<h1>' + formatInlineMarkdown(trimmed.replace(/^#\s+/, '')) + '</h1>');
                continue;
            }
            if (/^>\s?/.test(trimmed)) {
                closeList();
                html.push('<blockquote>' + formatInlineMarkdown(trimmed.replace(/^>\s?/, '')) + '</blockquote>');
                continue;
            }
            if (/^[-*]\s+/.test(trimmed)) {
                if (!inList) {
                    html.push('<ul>');
                    inList = true;
                }
                html.push('<li>' + formatInlineMarkdown(trimmed.replace(/^[-*]\s+/, '')) + '</li>');
                continue;
            }
            if (/^\d+\.\s+/.test(trimmed)) {
                closeList();
                html.push('<p>' + formatInlineMarkdown(trimmed) + '</p>');
                continue;
            }

            closeList();
            html.push('<p>' + formatInlineMarkdown(trimmed) + '</p>');
        }

        closeList();
        return '<div class="markdown-content">' + html.join('') + '</div>';
    }

    global.formatAiMarkdown = formatAiMarkdown;
    global.escapeHtml = global.escapeHtml || escapeHtml;

})(typeof window !== 'undefined' ? window : globalThis);
