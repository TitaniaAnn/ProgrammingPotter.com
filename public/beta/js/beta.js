/* Beta Testing Portal JS */

(function () {
    'use strict';

    // ── Section navigation ────────────────────────────────────────
    const navItems = document.querySelectorAll('.beta-nav-item');
    const sections = document.querySelectorAll('.beta-section');

    function showSection(sectionId) {
        sections.forEach(s => s.classList.remove('beta-section--active'));
        navItems.forEach(n => n.classList.remove('active'));

        const target = document.getElementById(sectionId);
        if (target) target.classList.add('beta-section--active');

        const activeNav = document.querySelector(`.beta-nav-item[data-section="${sectionId}"]`);
        if (activeNav) activeNav.classList.add('active');

        if (sectionId === 'issues' && !submissionsLoaded) {
            loadSubmissions();
        }
    }

    navItems.forEach(item => {
        item.addEventListener('click', e => {
            e.preventDefault();
            showSection(item.dataset.section);
            history.replaceState(null, '', '#' + item.dataset.section);
            closeSidebar();
        });
    });

    const initialHash = location.hash.replace('#', '');
    if (initialHash && document.getElementById(initialHash)) {
        showSection(initialHash);
    }

    // ── Mobile sidebar ────────────────────────────────────────────
    const sidebar   = document.querySelector('.beta-sidebar');
    const overlay   = document.getElementById('sidebarOverlay');
    const toggleBtn = document.getElementById('sidebarToggle');

    function closeSidebar() {
        sidebar && sidebar.classList.remove('open');
        overlay && overlay.classList.remove('open');
    }

    toggleBtn && toggleBtn.addEventListener('click', () => {
        sidebar.classList.toggle('open');
        overlay.classList.toggle('open');
    });
    overlay && overlay.addEventListener('click', closeSidebar);

    // ── Sub-tabs (Submissions / GitHub) ──────────────────────────
    const subtabs      = document.querySelectorAll('.beta-subtab');
    const submissionsTabEl = document.getElementById('submissionsTab');
    const githubTabEl      = document.getElementById('githubTab');

    subtabs.forEach(btn => {
        btn.addEventListener('click', () => {
            subtabs.forEach(b => b.classList.remove('beta-subtab--active'));
            btn.classList.add('beta-subtab--active');

            if (btn.dataset.subtab === 'submissions') {
                submissionsTabEl && (submissionsTabEl.style.display = '');
                githubTabEl      && (githubTabEl.style.display      = 'none');
                if (!submissionsLoaded) loadSubmissions();
            } else {
                submissionsTabEl && (submissionsTabEl.style.display = 'none');
                githubTabEl      && (githubTabEl.style.display      = '');
                if (!issuesLoaded) loadIssues('all');
            }
        });
    });

    // ── Community submissions ─────────────────────────────────────
    let submissionsLoaded = false;
    let allSubmissions    = [];

    const submissionsList  = document.getElementById('submissionsList');
    const subFilterBtns    = document.querySelectorAll('.beta-sub-filter-btn');
    const subSortSelect    = document.getElementById('subSort');

    let activeType   = 'all';
    let activeStatus = 'all';
    let activeSort   = 'votes';

    async function loadSubmissions() {
        if (!submissionsList) return;
        submissionsList.innerHTML = '<div class="beta-loading">Loading submissions…</div>';

        const params = new URLSearchParams({ type: activeType, status: activeStatus, sort: activeSort });
        try {
            const res = await fetch('/beta/api/feedback.php?' + params);
            if (!res.ok) throw new Error();
            allSubmissions    = await res.json();
            submissionsLoaded = true;
            renderSubmissions(allSubmissions);
        } catch {
            submissionsList.innerHTML = '<div class="beta-no-results">Could not load submissions.</div>';
        }
    }

    function renderSubmissions(items) {
        if (!items.length) {
            submissionsList.innerHTML = '<div class="beta-no-results">No submissions yet — be the first!</div>';
            return;
        }

        submissionsList.innerHTML = items.map(item => {
            const score    = item.upvotes - item.downvotes;
            const upActive = item.my_vote ===  1 ? 'beta-vote-btn--active-up'   : '';
            const dnActive = item.my_vote === -1 ? 'beta-vote-btn--active-down' : '';
            const typeCls  = item.type === 'bug' ? 'beta-type-bug' : 'beta-type-feature';
            const statusCls = {
                open:        'beta-status-open',
                in_progress: 'beta-status-progress',
                closed:      'beta-status-closed',
            }[item.status] ?? 'beta-status-open';
            const date = new Date(item.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
            const ghLink = item.github_issue_url
                ? `<a href="${esc(item.github_issue_url)}" target="_blank" rel="noopener" class="beta-gh-link">GH #${item.github_issue_number} ↗</a>`
                : '';

            return `
            <div class="beta-sub-card" data-id="${item.id}">
                <div class="beta-vote-col">
                    <button class="beta-vote-btn beta-vote-btn--up ${upActive}"
                            data-id="${item.id}" data-vote="1" title="Upvote">▲</button>
                    <span class="beta-vote-score" data-score="${item.id}">${score}</span>
                    <button class="beta-vote-btn beta-vote-btn--down ${dnActive}"
                            data-id="${item.id}" data-vote="-1" title="Downvote">▼</button>
                </div>
                <div class="beta-sub-card__body">
                    <div class="beta-sub-card__header">
                        <span class="beta-label ${typeCls}">${esc(item.type)}</span>
                        <span class="beta-label ${statusCls}">${esc(item.status.replace('_',' '))}</span>
                        <span class="beta-sub-card__title">${esc(item.title)}</span>
                    </div>
                    ${item.body ? `<div class="beta-sub-card__desc">${esc(item.body)}</div>` : ''}
                    <div class="beta-sub-card__meta">
                        <span>by ${esc(item.user_name)}</span>
                        <span>${date}</span>
                        <span class="beta-vote-tally">▲ ${item.upvotes} · ▼ ${item.downvotes}</span>
                        ${ghLink}
                    </div>
                </div>
            </div>`;
        }).join('');

        // Attach vote handlers
        submissionsList.querySelectorAll('.beta-vote-btn').forEach(btn => {
            btn.addEventListener('click', handleVote);
        });
    }

    async function handleVote(e) {
        const btn        = e.currentTarget;
        const feedbackId = parseInt(btn.dataset.id);
        const vote       = parseInt(btn.dataset.vote);

        btn.disabled = true;

        try {
            const res = await fetch('/beta/api/vote.php', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify({ feedback_id: feedbackId, vote }),
            });
            if (!res.ok) throw new Error();
            const data = await res.json();

            // Update the card in allSubmissions
            const idx = allSubmissions.findIndex(s => s.id === feedbackId);
            if (idx !== -1) {
                allSubmissions[idx].upvotes   = data.upvotes;
                allSubmissions[idx].downvotes = data.downvotes;
                allSubmissions[idx].my_vote   = data.my_vote;
            }

            // Update DOM in place
            const card  = btn.closest('.beta-sub-card');
            const score = data.upvotes - data.downvotes;
            card.querySelector(`[data-score="${feedbackId}"]`).textContent = score;
            card.querySelector('.beta-vote-tally').textContent = `▲ ${data.upvotes} · ▼ ${data.downvotes}`;
            card.querySelector('.beta-vote-btn--up').classList.toggle('beta-vote-btn--active-up',   data.my_vote ===  1);
            card.querySelector('.beta-vote-btn--down').classList.toggle('beta-vote-btn--active-down', data.my_vote === -1);
        } catch {
            // silent fail
        } finally {
            btn.disabled = false;
        }
    }

    subFilterBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            subFilterBtns.forEach(b => b.classList.remove('beta-sub-filter-btn--active'));
            btn.classList.add('beta-sub-filter-btn--active');
            activeType   = btn.dataset.type;
            activeStatus = btn.dataset.status;
            submissionsLoaded = false;
            loadSubmissions();
        });
    });

    subSortSelect && subSortSelect.addEventListener('change', () => {
        activeSort = subSortSelect.value;
        submissionsLoaded = false;
        loadSubmissions();
    });

    // ── GitHub issues ─────────────────────────────────────────────
    let issuesLoaded = false;
    let allIssues    = [];

    const issuesList  = document.getElementById('issuesList');
    const refreshBtn  = document.getElementById('refreshIssues');
    const filterBtns  = document.querySelectorAll('.beta-filter-btn');

    async function loadIssues(state) {
        if (!issuesList) return;
        issuesList.innerHTML = '<div class="beta-loading">Loading issues…</div>';

        try {
            const res = await fetch('/beta/api/issues.php?state=' + encodeURIComponent(state));
            if (!res.ok) throw new Error();
            allIssues    = await res.json();
            issuesLoaded = true;
            renderIssues(allIssues);
        } catch {
            issuesList.innerHTML = '<div class="beta-no-results">Could not load issues.</div>';
        }
    }

    function renderIssues(issues) {
        if (!issues.length) {
            issuesList.innerHTML = '<div class="beta-no-results">No issues found.</div>';
            return;
        }

        issuesList.innerHTML = issues.map(issue => {
            const labels    = (issue.labels || []).map(l =>
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
                    ${issue.comments ? `<span>💬 ${issue.comments}</span>` : ''}
                </div>
                ${issue.body ? `<div class="beta-issue-card__body">${esc(issue.body)}</div>` : ''}
            </div>`;
        }).join('');
    }

    function filterIssues(filter) {
        let filtered = allIssues;
        if (filter === 'open')        filtered = allIssues.filter(i => i.state === 'open');
        else if (filter === 'closed') filtered = allIssues.filter(i => i.state === 'closed');
        else if (filter === 'bug')    filtered = allIssues.filter(i => (i.labels||[]).some(l => l.name === 'bug'));
        else if (filter === 'enhancement') filtered = allIssues.filter(i => (i.labels||[]).some(l => l.name === 'enhancement' || l.name === 'feature'));
        renderIssues(filtered);
    }

    filterBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            filterBtns.forEach(b => b.classList.remove('beta-filter-btn--active'));
            btn.classList.add('beta-filter-btn--active');
            if (!issuesLoaded) loadIssues('all');
            else filterIssues(btn.dataset.filter);
        });
    });

    refreshBtn && refreshBtn.addEventListener('click', () => {
        issuesLoaded = false;
        allIssues    = [];
        loadIssues('all');
    });

    // Auto-load submissions when issues section is already active on page load
    if (document.querySelector('#issues.beta-section--active')) {
        loadSubmissions();
    }

    // ── Helpers ───────────────────────────────────────────────────
    function esc(str) {
        if (str == null) return '';
        return String(str)
            .replace(/&/g,  '&amp;')
            .replace(/</g,  '&lt;')
            .replace(/>/g,  '&gt;')
            .replace(/"/g,  '&quot;')
            .replace(/'/g,  '&#39;');
    }

    function hexToRgb(hex) {
        if (!hex) return '255,255,255';
        hex = hex.replace('#', '');
        const n = parseInt(hex, 16);
        return `${(n >> 16) & 255},${(n >> 8) & 255},${n & 255}`;
    }

})();
