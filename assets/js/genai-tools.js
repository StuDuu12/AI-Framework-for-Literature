/**
 * GenAI tools: autosave draft, server history sync, export
 */
window.GenAITools = (function () {
    const meta = document.querySelector('meta[name="url-root"]');
    const base = meta?.content || '';

    function setAutosaveStatus(state, text) {
        const el = document.getElementById('autosave-status');
        if (!el) return;
        el.className = 'autosave-status ' + (state || '');
        el.textContent = text || '';
    }

    let autosaveTimer = null;

    function scheduleAutosave(taskId, deliverId, getData) {
        clearTimeout(autosaveTimer);
        setAutosaveStatus('saving', 'Đang lưu nháp...');
        autosaveTimer = setTimeout(async () => {
            try {
                const draft_data = typeof getData === 'function' ? getData() : getData;
                const res = await fetch(base + '/api/save_draft.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ task_id: taskId, deliver_id: deliverId, draft_data })
                });
                const json = await res.json();
                if (json.success) {
                    setAutosaveStatus('saved', 'Đã lưu nháp ' + new Date().toLocaleTimeString('vi-VN'));
                } else {
                    setAutosaveStatus('error', json.message || 'Lưu nháp thất bại');
                }
            } catch (e) {
                setAutosaveStatus('error', 'Lưu nháp thất bại');
            }
        }, 2000);
    }

    async function loadDraft(taskId, deliverId) {
        try {
            const res = await fetch(`${base}/api/load_draft.php?task_id=${taskId}&deliver_id=${deliverId}`);
            return await res.json();
        } catch (e) {
            return { has_draft: false };
        }
    }

    async function saveHistory(taskId, deliverId, analysisData, label) {
        try {
            const res = await fetch(base + '/api/analysis_history.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'save',
                    task_id: taskId,
                    deliver_id: deliverId,
                    analysis_data: analysisData,
                    label: label
                })
            });
            const json = await res.json();
            return json.success ? (json.id || null) : null;
        } catch (e) {
            console.warn('History sync failed', e);
            return null;
        }
    }

    async function loadServerHistory(taskId, deliverId) {
        try {
            const res = await fetch(`${base}/api/analysis_history.php?task_id=${taskId}&deliver_id=${deliverId}`);
            const json = await res.json();
            return {
                history: json.history || [],
                deliverStatus: json.deliver_status ?? 0,
                confirmedHistoryId: json.confirmed_history_id ?? null
            };
        } catch (e) {
            return { history: [], deliverStatus: 0, confirmedHistoryId: null };
        }
    }

    async function confirmVersion(taskId, deliverId, historyId, analysisData) {
        const body = {
            action: 'confirm',
            task_id: taskId,
            deliver_id: deliverId
        };
        if (historyId) {
            body.history_id = historyId;
        } else if (analysisData) {
            body.analysis_data = analysisData;
        }
        const res = await fetch(base + '/api/analysis_history.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body)
        });
        return await res.json();
    }

    function exportAnalysisHtml(title, analysisData) {
        const esc = (s) => {
            const d = document.createElement('div');
            d.textContent = s == null ? '' : String(s);
            return d.innerHTML;
        };
        let body = `<h1>${esc(title || analysisData?.title || 'Phân tích văn học')}</h1>`;
        if (analysisData?.characteristics) {
            body += '<h2>Đặc điểm văn bản</h2><ul>';
            for (const [k, v] of Object.entries(analysisData.characteristics)) {
                body += `<li><strong>${esc(k)}:</strong> ${esc(v)}</li>`;
            }
            body += '</ul>';
        }
        if (analysisData?.keywords?.length) {
            body += `<h2>Từ khóa</h2><p>${analysisData.keywords.map(esc).join(', ')}</p>`;
        }
        const sections = [
            ['generalAnalysis', 'Phân tích chung'],
            ['storyAnalysis', 'Phân tích truyện'],
            ['dramaAnalysis', 'Phân tích kịch'],
            ['poetryAnalysis', 'Phân tích thơ'],
            ['concludingThoughts', 'Kết luận']
        ];
        sections.forEach(([key, heading]) => {
            if (analysisData?.[key]) {
                body += `<h2>${heading}</h2><pre style="white-space:pre-wrap;font-family:inherit">${esc(JSON.stringify(analysisData[key], null, 2))}</pre>`;
            }
        });
        const html = `<!DOCTYPE html><html><head><meta charset="utf-8"><title>${esc(title)}</title>
<style>body{font-family:Georgia,serif;max-width:800px;margin:2rem auto;line-height:1.6;padding:1rem}h1{color:#012970}h2{color:#4154f1;margin-top:1.5rem}</style></head>
<body>${body}<hr><p><em>Xuất từ Literature System — ${new Date().toLocaleString('vi-VN')}</em></p></body></html>`;
        const blob = new Blob(['\ufeff', html], { type: 'application/msword;charset=utf-8' });
        const a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = (title || 'phan-tich').replace(/[^\w\s-]/g, '').trim().slice(0, 60) + '.doc';
        a.click();
        URL.revokeObjectURL(a.href);
    }

    function printAnalysis() {
        window.print();
    }

    async function clearAllHistory(taskId, deliverId) {
        try {
            const res = await fetch(base + '/api/analysis_history.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'clear_all',
                    task_id: taskId,
                    deliver_id: deliverId
                })
            });
            return await res.json();
        } catch (e) {
            console.warn('Clear history failed', e);
            return { success: false };
        }
    }

    return {
        scheduleAutosave,
        loadDraft,
        saveHistory,
        loadServerHistory,
        confirmVersion,
        clearAllHistory,
        exportAnalysisHtml,
        printAnalysis,
        setAutosaveStatus
    };
})();
