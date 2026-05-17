/**
 * ResumeOptimizer — Client-side controller for the AI Resume Optimizer modal.
 *
 * API Endpoints used:
 *   GET  /resume-optimizer/score/{id}    → score + breakdown
 *   POST /resume-optimizer/rewrite/{id}  → AI rewrite
 *
 * Designed to be non-destructive to the existing chatbot.js and apply flow.
 */

window.ResumeOptimizer = (function () {
    'use strict';

    // ── Helpers ────────────────────────────────────────────────────────────

    function el(id) { return document.getElementById(id); }

    function show(id) {
        const e = el(id);
        if (e) e.classList.remove('hidden');
    }

    function hide(id) {
        const e = el(id);
        if (e) e.classList.add('hidden');
    }

    function setHtml(id, html) {
        const e = el(id);
        if (e) e.innerHTML = html;
    }

    function setText(id, text) {
        const e = el(id);
        if (e) e.textContent = text;
    }

    // Animate a progress bar + number from 0 → target
    function animateBar(barId, pctId, target) {
        let current = 0;
        const step = Math.ceil(target / 40);
        const interval = setInterval(() => {
            current = Math.min(current + step, target);
            const bar = el(barId);
            const pct = el(pctId);
            if (bar) bar.style.width = current + '%';
            if (pct) pct.textContent = current + '%';
            if (current >= target) clearInterval(interval);
        }, 25);
    }

    // Animate the SVG arc score ring
    function animateRing(circleId, scoreValId, target) {
        const circumference = 314; // 2 * π * 50
        let current = 0;
        const step = Math.ceil(target / 40);

        const interval = setInterval(() => {
            current = Math.min(current + step, target);
            const circle = el(circleId);
            const scoreEl = el(scoreValId);

            if (circle) {
                const offset = circumference - (current / 100) * circumference;
                circle.style.strokeDashoffset = offset;

                // Colour: red <60, yellow 60-70, green >70
                if (current < 60) circle.style.stroke = '#ef4444';
                else if (current < 70) circle.style.stroke = '#f59e0b';
                else circle.style.stroke = '#10b981';
            }

            if (scoreEl) scoreEl.textContent = current + '%';
            if (current >= target) clearInterval(interval);
        }, 25);
    }

    // Set gate badge colour and text
    function setGateBadge(id, tier, message) {
        const badge = el('rom-gate-badge-' + id);
        const msg = el('rom-gate-message-' + id);

        if (!badge) return;

        badge.className = 'rom-gate-badge inline-flex items-center gap-1.5 px-4 py-1.5 rounded-full text-sm font-semibold mb-1';

        if (tier === 'high') {
            badge.classList.add('rom-gate-badge-high');
            badge.innerHTML = '<i class="fas fa-check-circle"></i> High Match';
        } else if (tier === 'medium') {
            badge.classList.add('rom-gate-badge-medium');
            badge.innerHTML = '<i class="fas fa-exclamation-circle"></i> Moderate Match';
        } else {
            badge.classList.add('rom-gate-badge-low');
            badge.innerHTML = '<i class="fas fa-times-circle"></i> Low Match';
        }

        if (msg) msg.textContent = message;
    }

    // CSRF token from meta tag
    function getCsrf() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    // ── State Store ────────────────────────────────────────────────────────
    const _state = {};

    // ── Public API ─────────────────────────────────────────────────────────

    /**
     * Open the optimizer modal and trigger scoring immediately.
     * @param {number} internshipId
     */
    function open(internshipId) {
        const modal = el('resume-optimizer-modal-' + internshipId);
        if (!modal) return;

        // ── Teleport to body to escape card overflow:hidden / z-index stacking ──
        if (modal.parentElement !== document.body) {
            modal._originalParent = modal.parentElement;
            modal._originalNextSibling = modal.nextSibling;
            document.body.appendChild(modal);
        }

        modal.classList.add('rom-open');
        document.body.style.overflow = 'hidden';

        _state[internshipId] = { scored: false, rewritten: false };

        // Show loading, hide everything else
        show('rom-loading-' + internshipId);
        hide('rom-score-panel-' + internshipId);
        hide('rom-rewriting-' + internshipId);
        hide('rom-comparison-' + internshipId);
        hide('rom-error-' + internshipId);

        _fetchScore(internshipId);
    }

    /**
     * Close the modal.
     * @param {number} internshipId
     */
    function close(internshipId) {
        const modal = el('resume-optimizer-modal-' + internshipId);
        if (!modal) return;
        modal.classList.remove('rom-open');
        document.body.style.overflow = '';

        // Return modal to its original position in the DOM
        if (modal._originalParent) {
            modal._originalParent.insertBefore(modal, modal._originalNextSibling || null);
            delete modal._originalParent;
            delete modal._originalNextSibling;
        }
    }

    /**
     * Trigger AI rewrite.
     * @param {number} internshipId
     */
    function rewrite(internshipId) {
        hide('rom-score-panel-' + internshipId);
        show('rom-rewriting-' + internshipId);

        const baseUrl = window.resumeOptimizerRewriteBase || '/resume-optimizer/rewrite/';

        fetch(baseUrl + internshipId, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': getCsrf(),
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
        })
            .then(r => r.json())
            .then(data => {
                hide('rom-rewriting-' + internshipId);

                if (!data.success) {
                    _showError(internshipId, data.error || 'Rewrite failed. Please try again.');
                    return;
                }

                _renderComparison(internshipId, data.data);
            })
            .catch(err => {
                console.error('[ResumeOptimizer] Rewrite error:', err);
                hide('rom-rewriting-' + internshipId);
                _showError(internshipId, 'Network error. Please check your connection.');
            });
    }

    /**
     * Go back from comparison to the score panel.
     * @param {number} internshipId
     */
    function backToScore(internshipId) {
        hide('rom-comparison-' + internshipId);
        show('rom-score-panel-' + internshipId);
    }

    /**
     * Copy the rewritten resume text to clipboard.
     * @param {number} internshipId
     */
    function copyResume(internshipId) {
        const preview = el('rom-preview-text-' + internshipId);
        if (!preview) return;

        navigator.clipboard.writeText(preview.textContent).then(() => {
            // Brief visual feedback
            const btn = preview.previousElementSibling && preview.previousElementSibling.querySelector('button');
            if (btn) {
                const orig = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-check"></i> Copied!';
                setTimeout(() => btn.innerHTML = orig, 2000);
            }
        });
    }

    // ── Private ────────────────────────────────────────────────────────────

    function _fetchScore(internshipId) {
        const baseUrl = window.resumeOptimizerScoreBase || '/resume-optimizer/score/';

        fetch(baseUrl + internshipId, {
            headers: { 'Accept': 'application/json' }
        })
            .then(r => r.json())
            .then(data => {
                hide('rom-loading-' + internshipId);

                if (!data.success) {
                    const prefix = data.stage_failed ? '[' + data.stage_failed + '] ' : '';
                    _showError(internshipId, prefix + (data.error || 'Unable to analyse resume.'));
                    return;
                }

                _renderScore(internshipId, data.data);
            })
            .catch(err => {
                console.error('[ResumeOptimizer] Score fetch error:', err);
                hide('rom-loading-' + internshipId);
                _showError(internshipId, 'Network error. Please try again.');
            });
    }

    function _renderScore(internshipId, d) {
        show('rom-score-panel-' + internshipId);

        // Animate score ring
        animateRing(
            'rom-score-circle-' + internshipId,
            'rom-score-value-' + internshipId,
            d.score
        );

        // Gate badge
        setGateBadge(internshipId, d.tier, d.gate_message);

        // Progress bars
        animateBar('rom-skill-bar-' + internshipId, 'rom-skill-pct-' + internshipId, d.skill_match);
        animateBar('rom-kw-bar-' + internshipId, 'rom-kw-pct-' + internshipId, d.keyword_score);
        animateBar('rom-fmt-bar-' + internshipId, 'rom-fmt-pct-' + internshipId, d.format_score);

        // Missing skills
        const missingWrap = el('rom-missing-wrap-' + internshipId);
        const missingBox = el('rom-missing-skills-' + internshipId);
        if (d.missing_skills && d.missing_skills.length > 0 && missingWrap && missingBox) {
            missingBox.innerHTML = d.missing_skills.map(s =>
                `<span class="rom-skill-chip rom-missing-chip">${s}</span>`
            ).join('');
            missingWrap.classList.remove('hidden');
        }

        // Issues
        const issuesWrap = el('rom-issues-wrap-' + internshipId);
        const issuesList = el('rom-issues-list-' + internshipId);
        if (d.issues && d.issues.length > 0 && issuesWrap && issuesList) {
            issuesList.innerHTML = d.issues.map(i =>
                `<li class="rom-issue-item"><i class="fas fa-exclamation-triangle text-amber-500 flex-shrink-0 mt-0.5"></i><span>${i}</span></li>`
            ).join('');
            issuesWrap.classList.remove('hidden');
        }

        // Strengths
        const strengthsWrap = el('rom-strengths-wrap-' + internshipId);
        const strengthsList = el('rom-strengths-list-' + internshipId);
        if (d.strengths && d.strengths.length > 0 && strengthsWrap && strengthsList) {
            strengthsList.innerHTML = d.strengths.map(s =>
                `<li class="rom-strength-item"><i class="fas fa-check-circle text-green-500 flex-shrink-0 mt-0.5"></i><span>${s}</span></li>`
            ).join('');
            strengthsWrap.classList.remove('hidden');
        }

        // Adjust "Apply Anyway" button based on gate
        const applyBtn = el('rom-apply-btn-' + internshipId);
        if (applyBtn && d.tier === 'low') {
            applyBtn.innerHTML = '<i class="fas fa-exclamation-triangle mr-2 text-amber-500"></i>Apply Anyway (Low Match)';
        }
    }

    function _renderComparison(internshipId, d) {
        show('rom-comparison-' + internshipId);

        // Before / After scores
        const beforeEl = el('rom-before-score-' + internshipId);
        const afterEl = el('rom-after-score-' + internshipId);

        if (beforeEl) beforeEl.textContent = d.before_score + '%';
        if (afterEl) afterEl.textContent = d.after_score + '%';

        // Colour after score
        if (afterEl) {
            afterEl.className = 'text-3xl font-black ' +
                (d.after_score >= 70 ? 'text-green-600' : d.after_score >= 50 ? 'text-amber-500' : 'text-red-500');
        }

        // Improvements list
        const impList = el('rom-improvements-list-' + internshipId);
        if (impList && d.improvements) {
            impList.innerHTML = d.improvements.map(i =>
                `<li class="rom-improvement-item"><i class="fas fa-arrow-up text-green-500 flex-shrink-0 mt-0.5"></i><span>${i}</span></li>`
            ).join('');
        }

        // Preview text — clean excerpt (first 600 printable chars only)
        const preview = el('rom-preview-text-' + internshipId);
        if (preview && d.rewritten_text) {
            // Strip any remaining binary / PDF noise before display
            const clean = d.rewritten_text
                .replace(/%PDF-[\d.]+[\s\S]*?(?=PROFESSIONAL|SUMMARY|SKILLS|EXPERIENCE|\n[A-Z]{3}|$)/i, '')
                .replace(/[^\x09\x0A\x0D\x20-\x7E]/g, '')
                .replace(/[ \t]+/g, ' ')
                .trim();
            preview.textContent = clean.slice(0, 600) + (clean.length > 600 ? '\n\n... (full resume in PDF download)' : '');
        }

        // ── Update download button href with the real version_id ──
        const dlBtn = el('rom-download-btn-' + internshipId);
        if (dlBtn && d.version_id) {
            dlBtn.href = `/resume-optimizer/download/${internshipId}?version_id=${d.version_id}`;
        }

        // ── Populate the hidden version_id field in the optimised apply form ──
        const versionInput = el('rom-version-id-' + internshipId);
        if (versionInput && d.version_id) {
            versionInput.value = d.version_id;
        }
    }

    function _showError(internshipId, message) {
        show('rom-error-' + internshipId);
        const msgEl = el('rom-error-msg-' + internshipId);
        if (msgEl) msgEl.textContent = message;
    }

    // ── Keyboard close ─────────────────────────────────────────────────────
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            // Close any open optimizer modals
            document.querySelectorAll('.rom-overlay.rom-open').forEach(modal => {
                modal.classList.remove('rom-open');
                document.body.style.overflow = '';
            });
        }
    });

    return { open, close, rewrite, backToScore, copyResume };
})();
