/* Beta Testing Portal JS */

(function () {
    'use strict';

    // ── Section navigation ────────────────────────────────────────
    const navItems   = document.querySelectorAll('.beta-nav-item');
    const sections   = document.querySelectorAll('.beta-section');

    function showSection(sectionId) {
        sections.forEach(s => s.classList.remove('beta-section--active'));
        navItems.forEach(n => n.classList.remove('active'));

        const target = document.getElementById(sectionId);
        if (target) target.classList.add('beta-section--active');

        const activeNav = document.querySelector(`.beta-nav-item[data-section="${sectionId}"]`);
        if (activeNav) activeNav.classList.add('active');

        // Load issues when that section is opened
        if (sectionId === 'issues' && !issuesLoaded) {
            loadIssues('all');
        }
    }

    navItems.forEach(item => {
        item.addEventListener('click', e => {
            e.preventDefault();
            const section = item.dataset.section;
            showSection(section);
            history.replaceState(null, '', '#' + section);
            closeSidebar();
        });
    });

    // Restore section from URL hash on load
    const initialHash = location.hash.replace('#', '');
    if (initialHash && document.getElementById(initialHash)) {
        showSection(initialHash);
    }

    // ── Mobile sidebar ────────────────────────────────────────────
    const sidebar        = document.querySelector('.beta-sidebar');
    const overlay        = document.getElementById('sidebarOverlay');
    const toggleBtn      = document.getElementById('sidebarToggle');

    function closeSidebar() {
        sidebar && sidebar.classList.remove('open');
        overlay && overlay.classList.remove('open');
    }

    toggleBtn && toggleBtn.addEventListener('click', () => {
        sidebar.classList.toggle('open');
        overlay.classList.toggle('open');
    });
    overlay && overlay.addEventListener('click', closeSidebar);

    // ── Issues loading ────────────────────────────────────────────
    let issuesLoaded  = false;
    let allIssues     = [];
    let activeFilter  = 'all';

    const issuesList     = document.getElementById('issuesList');
    const refreshBtn     = document.getElementById('refreshIssues');
    const filterBtns     = document.querySelectorAll('.beta-filter-btn');

    async function loadIssues(state) {
        if (!issuesList) return;
        issuesList.innerHTML = '<div class="beta-loading">Loading issues…</div>';

        try {
            const res  = await fetch('/beta/api/issues.php?state=' + encodeURIComponent(state));
            if (!res.ok) throw new Error('Failed to load');
            allIssues   = await res.json();
            issuesLoaded = true;
            renderIssues(allIssues);
        } catch {
            issuesList.innerHTML = '<div class="beta-no-results">Could not load issues. Please try again.</div>';
        }
    }

    function renderIssues(issues) {
        if (!issues.length) {
            issuesList.innerHTML = '<div class="beta-no-results">No issues found.</div>';
            return;
        }

        issuesList.innerHTML = issues.map(issue => {
            const labels   = (issue.labels || []).map(l =>
                `<span class="beta-label" style="background:rgba(${hexToRgb(l.color)},.2);color:#${l.color}">${esc(l.name)}</span>`
            ).join('');

            const statusCls = issue.state === 'open' ? 'beta-status-open' : 'beta-status-closed';
            const date      = new Date(issue.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });

            return `
            <div class="beta-issue-card" data-state="${esc(issue.state)}" data-labels="${esc((issue.labels||[]).map(l=>l.name).join(','))}">
                <div class="beta-issue-card__header">
                    <span class="beta-issue-card__num">#${issue.number}</span>
                    <a href="${esc(issue.html_url)}" target="_blank" rel="noopener" class="beta-issue-card__title">${esc(issue.title)}</a>
                    <span class="beta-label ${statusCls}">${esc(issue.state)}</span>
                </div>
                <div class="beta-issue-card__meta">
                    ${labels}
                    <span>${date}</span>
                    ${issue.comments ? `<span class="beta-issue-card__comments">💬 ${issue.comments}</span>` : ''}
                </div>
                ${issue.body ? `<div class="beta-issue-card__body">${esc(issue.body)}</div>` : ''}
            </div>`;
        }).join('');
    }

    function filterIssues(filter) {
        if (!allIssues.length) return;

        let filtered = allIssues;

        if (filter === 'open') {
            filtered = allIssues.filter(i => i.state === 'open');
        } else if (filter === 'closed') {
            filtered = allIssues.filter(i => i.state === 'closed');
        } else if (filter === 'bug') {
            filtered = allIssues.filter(i => (i.labels||[]).some(l => l.name === 'bug'));
        } else if (filter === 'enhancement') {
            filtered = allIssues.filter(i => (i.labels||[]).some(l => l.name === 'enhancement' || l.name === 'feature'));
        }

        renderIssues(filtered);
    }

    filterBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            filterBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            activeFilter = btn.dataset.filter;

            if (!issuesLoaded) {
                loadIssues('all');
            } else {
                filterIssues(activeFilter);
            }
        });
    });

    refreshBtn && refreshBtn.addEventListener('click', () => {
        issuesLoaded = false;
        allIssues = [];
        loadIssues('all');
    });

    // Auto-load issues if that section is already active on page load
    if (document.querySelector('#issues.beta-section--active')) {
        loadIssues('all');
    }

    // ── Helpers ───────────────────────────────────────────────────
    function esc(str) {
        if (str == null) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function hexToRgb(hex) {
        if (!hex) return '255,255,255';
        hex = hex.replace('#', '');
        const n = parseInt(hex, 16);
        return `${(n >> 16) & 255},${(n >> 8) & 255},${n & 255}`;
    }

})();
