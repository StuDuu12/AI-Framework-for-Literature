/**
 * Fig. 10 — Algorithmic validation of the structural outline (client display).
 */
(function (global) {
    'use strict';

    const FIGURE = {
        figure: 'Fig. 10',
        title: 'Algorithmic validation of the structural outline',
        title_vi: 'Kiểm tra thuật toán khung phân tích cấu trúc',
    };

    function isNonEmptyString(value) {
        return typeof value === 'string' && value.trim() !== '';
    }

    function getByPath(obj, path) {
        return path.split('.').reduce((acc, key) => {
            if (acc == null || typeof acc !== 'object') return undefined;
            return acc[key];
        }, obj);
    }

    function checkField(data, path, label, required = true) {
        const current = getByPath(data, path);
        const found = current !== undefined;

        let status = 'pass';
        let detail = 'Present';

        if (!found) {
            status = required ? 'fail' : 'skip';
            detail = required ? 'Missing' : 'Optional — not provided';
        } else if (typeof current === 'string') {
            if (!isNonEmptyString(current)) {
                status = 'fail';
                detail = 'Empty string';
            }
        } else if (Array.isArray(current)) {
            if (current.length === 0) {
                status = 'fail';
                detail = 'Empty array';
            } else {
                detail = current.length + ' item(s)';
            }
        }

        return {
            id: path.replace(/\./g, '_'),
            path,
            label,
            required,
            status,
            detail,
        };
    }

    function validateStructuralOutlineClient(data, context = {}) {
        if (data && data._validation && typeof data._validation === 'object') {
            return data._validation;
        }

        const sections = [
            checkField(data, 'title', 'Title'),
            checkField(data, 'characteristics.Thể loại', 'Genre (Thể loại)'),
            checkField(data, 'characteristics.Phong cách', 'Style (Phong cách)'),
            checkField(data, 'characteristics.Giọng điệu', 'Tone (Giọng điệu)'),
            checkField(data, 'keywords', 'Keywords'),
            checkField(data, 'generalAnalysis.thematicAnalysis', 'Thematic analysis blocks'),
            checkField(data, 'generalAnalysis.linguisticInsights.motifs', 'Motifs'),
            checkField(data, 'generalAnalysis.linguisticInsights.symbolsAndMetaphors', 'Symbols & metaphors'),
            checkField(data, 'generalAnalysis.linguisticInsights.temporalElements', 'Temporal elements'),
            checkField(data, 'generalAnalysis.linguisticInsights.natureAndScenery', 'Nature & scenery'),
            checkField(data, 'concludingThoughts.lesson', 'Lesson'),
            checkField(data, 'concludingThoughts.message', 'Message'),
            checkField(data, 'intertextuality.initialAnalysis', 'Intertextuality — initial analysis'),
            checkField(data, 'intertextuality.comparativeText.title', 'Comparative text — title'),
            checkField(data, 'intertextuality.comparativeText.author', 'Comparative text — author'),
            checkField(data, 'intertextuality.comparison.similarities', 'Comparison — similarities'),
            checkField(data, 'intertextuality.comparison.differences', 'Comparison — differences'),
            checkField(data, 'intertextuality.comparison.effects', 'Comparison — effects'),
        ];

        const genreSubfields = {
            poetryAnalysis: ['rhyming', 'emotionalFlow', 'structure', 'imagery', 'rhythm', 'spaceTimeOrganization', 'rhetoricalDevices'],
            storyAnalysis: ['character', 'space', 'time', 'dialogue', 'narrator', 'situation', 'prominentDetails'],
            dramaAnalysis: ['characters', 'conflict', 'action', 'structure', 'dialogue'],
        };

        const activeGenres = Object.keys(genreSubfields).filter((key) => {
            const block = data[key];
            return block && typeof block === 'object' && Object.keys(block).length > 0;
        });

        const genreRuleOk = activeGenres.length === 1;
        const genreBlock = genreRuleOk ? activeGenres[0] : null;

        if (genreBlock) {
            genreSubfields[genreBlock].forEach((sub) => {
                sections.push(checkField(data, genreBlock + '.' + sub, sub + ' (' + genreBlock + ')'));
            });
        } else {
            sections.push({
                id: 'genre_block',
                path: 'genre',
                label: 'Genre block (exactly one required)',
                required: true,
                status: 'fail',
                detail: activeGenres.length === 0
                    ? 'No poetry/story/drama block found'
                    : 'Multiple genre blocks: ' + activeGenres.join(', '),
            });
        }

        const requiredSections = sections.filter((s) => s.required);
        const passedCount = requiredSections.filter((s) => s.status === 'pass').length;
        const totalRequired = requiredSections.length;
        const score = totalRequired > 0 ? Math.round((passedCount / totalRequired) * 100) : 0;
        const passed = genreRuleOk && passedCount === totalRequired;
        const inputTruncated = !!(context.input_truncated || (data._meta && data._meta.input_truncated));

        const steps = [
            { id: 'ingest', label: 'Text ingestion', label_vi: 'Thu thập văn bản', status: 'pass', detail: inputTruncated ? 'Input truncated for token limit' : 'Full input accepted' },
            { id: 'schema_request', label: 'Schema-constrained AI request', label_vi: 'Gọi AI với ràng buộc JSON schema', status: 'pass', detail: 'response_format: json_object' },
            { id: 'json_parse', label: 'JSON extraction & parse', label_vi: 'Trích xuất và parse JSON', status: 'pass', detail: 'parseJsonFromAiText' },
            { id: 'schema_check', label: 'Structural outline schema check', label_vi: 'Kiểm tra schema khung phân tích', status: passed ? 'pass' : 'fail', detail: passedCount + '/' + totalRequired + ' required fields valid' },
            { id: 'genre_rule', label: 'Single genre block rule', label_vi: 'Quy tắc một khối thể loại', status: genreRuleOk ? 'pass' : 'fail', detail: genreBlock ? 'Active: ' + genreBlock : (activeGenres.length === 0 ? 'No genre block' : 'Multiple genre blocks') },
            { id: 'map_blocks', label: 'Map to editable content blocks', label_vi: 'Ánh xạ sang khối nội dung chỉnh sửa', status: passed ? 'pass' : 'warn', detail: passed ? 'Ready for student self-critique' : 'Partial mapping — review required' },
        ];

        return {
            figure: FIGURE.figure,
            title: FIGURE.title,
            title_vi: FIGURE.title_vi,
            passed,
            score,
            summary: passed ? 'Structural outline passed algorithmic validation.' : 'Structural outline failed one or more validation rules.',
            summary_vi: passed ? 'Khung phân tích đã vượt qua kiểm tra thuật toán.' : 'Khung phân tích chưa đạt một hoặc nhiều quy tắc kiểm tra.',
            steps,
            sections,
            genre_block: genreBlock,
            genre_rule_ok: genreRuleOk,
        };
    }

    function statusIcon(status) {
        if (status === 'pass') return '✓';
        if (status === 'fail') return '✗';
        if (status === 'warn' || status === 'pending') return '…';
        return '–';
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text == null ? '' : String(text);
        return div.innerHTML;
    }

    function renderOutlineValidationPanel(validation, options = {}) {
        const loading = !!options.loading;
        const activeStep = options.activeStep != null ? options.activeStep : -1;
        const lang = options.lang === 'vi' ? 'vi' : 'en';

        const badgeClass = loading
            ? 'outline-validation__badge--pending'
            : (validation.passed ? 'outline-validation__badge--pass' : 'outline-validation__badge--fail');
        const badgeText = loading
            ? (lang === 'vi' ? 'ĐANG KIỂM TRA…' : 'VALIDATING…')
            : (validation.passed ? (lang === 'vi' ? 'HỢP LỆ' : 'VALIDATED') : (lang === 'vi' ? 'CHƯA ĐẠT' : 'FAILED'));

        const ariaLabel = lang === 'vi' ? validation.title_vi : validation.title;

        let stepsHtml = '';
        validation.steps.forEach((step, index) => {
            const stepStatus = loading && index === activeStep ? 'pending' : (loading && index > activeStep ? 'pending' : step.status);
            const stepClass = 'outline-validation__step outline-validation__step--' + stepStatus + (loading && index === activeStep ? ' outline-validation__step--active' : '');
            const label = lang === 'vi' ? (step.label_vi || step.label) : step.label;
            stepsHtml += '<div class="' + stepClass + '">' +
                '<span class="outline-validation__step-num">' + (index + 1) + '</span>' +
                '<span class="outline-validation__step-label">' + escapeHtml(label) + '</span>' +
                '<span class="outline-validation__step-detail">' + escapeHtml(step.detail || '') + '</span>' +
                '</div>';
        });

        let rowsHtml = '';
        (validation.sections || []).forEach((section) => {
            rowsHtml += '<tr>' +
                '<td>' + escapeHtml(section.label) + '</td>' +
                '<td><span class="outline-validation__status outline-validation__status--' + section.status + '">' + statusIcon(section.status) + ' ' + section.status + '</span></td>' +
                '<td>' + escapeHtml(section.detail || '') + '</td>' +
                '</tr>';
        });

        const scoreText = loading
            ? ''
            : '<span class="outline-validation__score">' + validation.score + '% schema compliance</span>';

        return '<section class="outline-validation' + (loading ? ' outline-validation--loading' : '') + '" id="outline-validation-panel" aria-label="' + escapeHtml(ariaLabel) + '">' +
            '<header class="outline-validation__header">' +
            '<div class="flex items-center justify-end gap-2 w-full">' + scoreText +
            '<span class="outline-validation__badge ' + badgeClass + '">' + badgeText + '</span>' +
            '</div></header>' +
            '<div class="outline-validation__body">' +
            '<div class="outline-validation__pipeline">' + stepsHtml + '</div>' +
            (loading ? '' : ('<details class="outline-validation__sections">' +
                '<summary>' + (lang === 'vi' ? 'Chi tiết kiểm tra từng trường schema' : 'Schema field validation details') + ' (' + (validation.sections || []).length + ')</summary>' +
                '<table class="outline-validation__table"><thead><tr><th>Field</th><th>Status</th><th>Detail</th></tr></thead><tbody>' + rowsHtml + '</tbody></table>' +
                '</details>')) +
            '</div></section>';
    }

    function getLoadingValidation() {
        return {
            figure: FIGURE.figure,
            title: FIGURE.title,
            title_vi: FIGURE.title_vi,
            passed: false,
            score: 0,
            steps: [
                { id: 'ingest', label: 'Text ingestion', label_vi: 'Thu thập văn bản', status: 'pending', detail: 'Waiting…' },
                { id: 'schema_request', label: 'Schema-constrained AI request', label_vi: 'Gọi AI với ràng buộc JSON schema', status: 'pending', detail: 'Waiting…' },
                { id: 'json_parse', label: 'JSON extraction & parse', label_vi: 'Trích xuất và parse JSON', status: 'pending', detail: 'Waiting…' },
                { id: 'schema_check', label: 'Structural outline schema check', label_vi: 'Kiểm tra schema khung phân tích', status: 'pending', detail: 'Waiting…' },
                { id: 'genre_rule', label: 'Single genre block rule', label_vi: 'Quy tắc một khối thể loại', status: 'pending', detail: 'Waiting…' },
                { id: 'map_blocks', label: 'Map to editable content blocks', label_vi: 'Ánh xạ sang khối nội dung chỉnh sửa', status: 'pending', detail: 'Waiting…' },
            ],
            sections: [],
        };
    }

    global.OutlineValidation = {
        validateStructuralOutlineClient,
        renderOutlineValidationPanel,
        getLoadingValidation,
    };
})(window);
