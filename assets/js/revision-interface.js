/**
 * Fig. 15 — Active revision interface following successful reflection submission.
 */
(function (global) {
    'use strict';

    const FIGURE = {
        figure: 'Fig. 15',
        title: 'Active revision interface following successful reflection submission',
        title_vi: 'Giao diện chỉnh sửa tích cực sau khi nộp phản tư thành công',
    };

    const SECTION_LABELS = {
        general: 'General Analysis',
        story: 'Story Analysis',
        drama: 'Drama Analysis',
        poetry: 'Poetry Analysis',
        conclusion: 'Lessons & Messages',
        intertextuality: 'Intertextuality',
    };

    function getLang() {
        return (document.documentElement.lang || 'en').toLowerCase().startsWith('vi') ? 'vi' : 'en';
    }

    function getPanel() {
        return document.getElementById('fig15-revision-interface');
    }

    function getBadge() {
        return document.getElementById('fig15-status-badge');
    }

    function setWorkflowStep(currentStep) {
        const panel = getPanel();
        if (!panel) return;
        panel.querySelectorAll('[data-workflow-step]').forEach((el) => {
            const step = parseInt(el.dataset.workflowStep, 10);
            el.classList.remove('research-figure__workflow-step--done', 'research-figure__workflow-step--current');
            if (step < currentStep) {
                el.classList.add('research-figure__workflow-step--done');
            } else if (step === currentStep) {
                el.classList.add('research-figure__workflow-step--current');
            }
        });
    }

    function renderSectionTracker(editedSectionIds, activeSectionId) {
        const tracker = document.getElementById('fig15-section-tracker');
        if (!tracker) return;

        if (!editedSectionIds.length && !activeSectionId) {
            tracker.classList.add('hidden');
            return;
        }

        const lang = getLang();
        let chips = '';
        const ids = new Set(editedSectionIds);
        if (activeSectionId) ids.add(activeSectionId);

        ids.forEach((id) => {
            const label = SECTION_LABELS[id] || id;
            const isActive = id === activeSectionId;
            chips += '<span class="research-figure__section-chip' + (isActive ? ' research-figure__section-chip--editing' : '') + '">' +
                (isActive ? '✎ ' : '✓ ') + label + '</span>';
        });

        tracker.innerHTML =
            '<div class="research-figure__tracker-title">' +
            (lang === 'vi' ? 'Các phần đang / đã chỉnh sửa' : 'Sections under active revision') +
            '</div><div class="research-figure__section-chips">' + chips + '</div>';
        tracker.classList.remove('hidden');
    }

    function showAfterReflection() {
        const panel = getPanel();
        if (!panel) return;

        const lang = getLang();
        const badge = getBadge();
        panel.classList.remove('hidden');
        setWorkflowStep(2);

        if (badge) {
            badge.textContent = lang === 'vi' ? 'PHẢN TƯ HOÀN TẤT' : 'REFLECTION COMPLETE';
            badge.className = 'research-figure__badge research-figure__badge--ready';
        }
    }

    function activateRevision(sectionId, behaviorTracker) {
        const panel = getPanel();
        if (!panel) return;

        const lang = getLang();
        const badge = getBadge();
        panel.classList.remove('hidden');
        setWorkflowStep(3);

        if (badge) {
            badge.textContent = lang === 'vi' ? 'ĐANG CHỈNH SỬA' : 'ACTIVE REVISION';
            badge.className = 'research-figure__badge research-figure__badge--active';
        }

        let editedIds = [];
        if (behaviorTracker && behaviorTracker.sections) {
            editedIds = Object.keys(behaviorTracker.sections).filter((sid) => {
                const s = behaviorTracker.sections[sid];
                return s && s.originalText !== s.currentText;
            });
        }

        renderSectionTracker(editedIds, sectionId || null);
    }

    function refreshFromTracker(behaviorTracker, activeSectionId) {
        if (!getPanel() || getPanel().classList.contains('hidden')) return;

        let editedIds = [];
        if (behaviorTracker && behaviorTracker.sections) {
            editedIds = Object.keys(behaviorTracker.sections).filter((sid) => {
                const s = behaviorTracker.sections[sid];
                return s && s.originalText !== s.currentText;
            });
        }

        if (editedIds.length > 0) {
            activateRevision(activeSectionId || null, behaviorTracker);
        } else {
            renderSectionTracker([], activeSectionId || null);
        }
    }

    global.RevisionInterface = {
        FIGURE,
        showAfterReflection,
        activateRevision,
        refreshFromTracker,
    };
})(window);
