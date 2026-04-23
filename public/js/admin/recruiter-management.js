/**
 * Admin Recruiter Management JavaScript
 * Handles AJAX filtering, bulk actions, badge updates, and timeline loading.
 * Requirements: 3.3, 3.4, 10.3, 10.5, 13.5, 14.4, 15.5
 */

(function () {
    'use strict';

    // -------------------------------------------------------------------------
    // AJAX Filtering & Search (Requirements: 3.3, 3.4, 14.4)
    // -------------------------------------------------------------------------

    const filterForm = document.getElementById('recruiter-filter-form');
    const recruiterTableBody = document.getElementById('recruiter-table-body');
    const paginationContainer = document.getElementById('recruiter-pagination');

    function buildFilterParams() {
        if (!filterForm) return new URLSearchParams();
        return new URLSearchParams(new FormData(filterForm));
    }

    function fetchRecruiters(params) {
        const url = '/admin/recruiters?' + params.toString();

        if (recruiterTableBody) {
            recruiterTableBody.innerHTML = '<tr><td colspan="8" class="text-center py-4 text-gray-500">Loading...</td></tr>';
        }

        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
        })
            .then(function (response) {
                if (!response.ok) throw new Error('Network response was not ok');
                return response.json();
            })
            .then(function (data) {
                if (recruiterTableBody && data.html) {
                    recruiterTableBody.innerHTML = data.html;
                }
                if (paginationContainer && data.pagination) {
                    paginationContainer.innerHTML = data.pagination;
                }
                // Re-attach row-level event listeners after DOM update
                attachRowActions();
                updateSelectAllState();
            })
            .catch(function (err) {
                console.error('Error fetching recruiters:', err);
                if (recruiterTableBody) {
                    recruiterTableBody.innerHTML =
                        '<tr><td colspan="8" class="text-center py-4 text-red-500">Failed to load data. Please refresh.</td></tr>';
                }
            });
    }

    if (filterForm) {
        // Debounce helper
        let debounceTimer;
        function debounce(fn, delay) {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(fn, delay);
        }

        // Search input – debounced
        const searchInput = filterForm.querySelector('[name="search"]');
        if (searchInput) {
            searchInput.addEventListener('input', function () {
                debounce(function () {
                    fetchRecruiters(buildFilterParams());
                }, 400);
            });
        }

        // Dropdowns / selects – immediate
        filterForm.querySelectorAll('select').forEach(function (select) {
            select.addEventListener('change', function () {
                fetchRecruiters(buildFilterParams());
            });
        });

        // Sort links
        document.querySelectorAll('[data-sort]').forEach(function (link) {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                const params = buildFilterParams();
                params.set('sort', this.dataset.sort);
                const currentDir = params.get('direction') === 'asc' ? 'desc' : 'asc';
                params.set('direction', currentDir);
                fetchRecruiters(params);
            });
        });

        // Clear filters button
        const clearBtn = document.getElementById('clear-filters-btn');
        if (clearBtn) {
            clearBtn.addEventListener('click', function () {
                filterForm.reset();
                fetchRecruiters(new URLSearchParams());
            });
        }
    }

    // -------------------------------------------------------------------------
    // Bulk Selection (Requirements: 10.1, 10.3)
    // -------------------------------------------------------------------------

    const selectAllCheckbox = document.getElementById('select-all-recruiters');
    const bulkActionBar = document.getElementById('bulk-action-bar');
    const selectedCountEl = document.getElementById('selected-count');

    function getSelectedIds() {
        return Array.from(document.querySelectorAll('.recruiter-checkbox:checked')).map(function (cb) {
            return cb.value;
        });
    }

    function updateSelectAllState() {
        if (!selectAllCheckbox) return;
        const all = document.querySelectorAll('.recruiter-checkbox');
        const checked = document.querySelectorAll('.recruiter-checkbox:checked');
        selectAllCheckbox.checked = all.length > 0 && checked.length === all.length;
        selectAllCheckbox.indeterminate = checked.length > 0 && checked.length < all.length;
        updateBulkBar(checked.length);
    }

    function updateBulkBar(count) {
        if (bulkActionBar) {
            bulkActionBar.classList.toggle('hidden', count === 0);
        }
        if (selectedCountEl) {
            selectedCountEl.textContent = count;
        }
    }

    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function () {
            document.querySelectorAll('.recruiter-checkbox').forEach(function (cb) {
                cb.checked = selectAllCheckbox.checked;
            });
            updateBulkBar(selectAllCheckbox.checked ? document.querySelectorAll('.recruiter-checkbox').length : 0);
        });
    }

    function attachRowActions() {
        document.querySelectorAll('.recruiter-checkbox').forEach(function (cb) {
            cb.removeEventListener('change', updateSelectAllState);
            cb.addEventListener('change', updateSelectAllState);
        });
    }

    attachRowActions();

    // -------------------------------------------------------------------------
    // Bulk Action Confirmation Dialog (Requirements: 10.5)
    // -------------------------------------------------------------------------

    const bulkActionForm = document.getElementById('bulk-action-form');
    const bulkActionSelect = document.getElementById('bulk-action-select');
    const bulkConfirmModal = document.getElementById('bulk-confirm-modal');
    const bulkConfirmMessage = document.getElementById('bulk-confirm-message');
    const bulkConfirmBtn = document.getElementById('bulk-confirm-btn');
    const bulkCancelBtn = document.getElementById('bulk-cancel-btn');
    const bulkReasonContainer = document.getElementById('bulk-reason-container');
    const bulkReasonInput = document.getElementById('bulk-reason-input');
    const bulkIdsInput = document.getElementById('bulk-ids-input');

    function openBulkConfirmModal(action, ids) {
        if (!bulkConfirmModal) return;

        const actionLabels = { approve: 'Approve', reject: 'Reject', suspend: 'Suspend' };
        const label = actionLabels[action] || action;

        if (bulkConfirmMessage) {
            bulkConfirmMessage.textContent =
                'Are you sure you want to ' + label + ' ' + ids.length + ' selected recruiter(s)?';
        }

        // Show reason field for reject/suspend
        if (bulkReasonContainer) {
            const needsReason = action === 'reject' || action === 'suspend';
            bulkReasonContainer.classList.toggle('hidden', !needsReason);
            if (bulkReasonInput) {
                bulkReasonInput.required = needsReason;
                bulkReasonInput.value = '';
            }
        }

        if (bulkIdsInput) {
            bulkIdsInput.value = ids.join(',');
        }

        bulkConfirmModal.classList.remove('hidden');
        bulkConfirmModal.setAttribute('aria-hidden', 'false');
    }

    function closeBulkConfirmModal() {
        if (!bulkConfirmModal) return;
        bulkConfirmModal.classList.add('hidden');
        bulkConfirmModal.setAttribute('aria-hidden', 'true');
    }

    const applyBulkBtn = document.getElementById('apply-bulk-action-btn');
    if (applyBulkBtn) {
        applyBulkBtn.addEventListener('click', function () {
            const action = bulkActionSelect ? bulkActionSelect.value : '';
            const ids = getSelectedIds();

            if (!action) {
                alert('Please select a bulk action.');
                return;
            }
            if (ids.length === 0) {
                alert('Please select at least one recruiter.');
                return;
            }

            openBulkConfirmModal(action, ids);
        });
    }

    if (bulkCancelBtn) {
        bulkCancelBtn.addEventListener('click', closeBulkConfirmModal);
    }

    // Close modal on backdrop click
    if (bulkConfirmModal) {
        bulkConfirmModal.addEventListener('click', function (e) {
            if (e.target === bulkConfirmModal) closeBulkConfirmModal();
        });
    }

    if (bulkConfirmBtn && bulkActionForm) {
        bulkConfirmBtn.addEventListener('click', function () {
            const action = bulkActionSelect ? bulkActionSelect.value : '';
            const needsReason = action === 'reject' || action === 'suspend';

            if (needsReason && bulkReasonInput && !bulkReasonInput.value.trim()) {
                bulkReasonInput.classList.add('border-red-500');
                bulkReasonInput.focus();
                return;
            }

            closeBulkConfirmModal();
            bulkActionForm.submit();
        });
    }

    // -------------------------------------------------------------------------
    // Real-time Pending Approval Badge Update (Requirements: 13.5)
    // -------------------------------------------------------------------------

    const pendingBadge = document.getElementById('pending-approval-badge');

    function updatePendingBadge() {
        fetch('/admin/recruiters/pending-count', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
        })
            .then(function (res) {
                if (!res.ok) return;
                return res.json();
            })
            .then(function (data) {
                if (!pendingBadge || data === undefined) return;
                const count = data.count || 0;
                pendingBadge.textContent = count;
                pendingBadge.classList.toggle('hidden', count === 0);
            })
            .catch(function () {
                // Silently fail – badge update is non-critical
            });
    }

    // Poll every 60 seconds for badge updates
    if (pendingBadge) {
        updatePendingBadge();
        setInterval(updatePendingBadge, 60000);
    }

    // -------------------------------------------------------------------------
    // Activity Timeline Lazy Loading with Pagination (Requirements: 15.5)
    // -------------------------------------------------------------------------

    const timelineContainer = document.getElementById('activity-timeline');
    const loadMoreTimelineBtn = document.getElementById('load-more-timeline');
    let timelinePage = 1;
    let timelineLoading = false;

    function loadTimelinePage(recruiterId, page) {
        if (timelineLoading) return;
        timelineLoading = true;

        if (loadMoreTimelineBtn) {
            loadMoreTimelineBtn.disabled = true;
            loadMoreTimelineBtn.textContent = 'Loading...';
        }

        fetch('/admin/recruiters/' + recruiterId + '/activity-timeline?page=' + page, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
        })
            .then(function (res) {
                if (!res.ok) throw new Error('Failed to load timeline');
                return res.json();
            })
            .then(function (data) {
                if (timelineContainer && data.html) {
                    timelineContainer.insertAdjacentHTML('beforeend', data.html);
                }

                if (loadMoreTimelineBtn) {
                    if (data.has_more) {
                        loadMoreTimelineBtn.disabled = false;
                        loadMoreTimelineBtn.textContent = 'Load More';
                        timelinePage++;
                    } else {
                        loadMoreTimelineBtn.style.display = 'none';
                    }
                }
            })
            .catch(function (err) {
                console.error('Timeline load error:', err);
                if (loadMoreTimelineBtn) {
                    loadMoreTimelineBtn.disabled = false;
                    loadMoreTimelineBtn.textContent = 'Retry';
                }
            })
            .finally(function () {
                timelineLoading = false;
            });
    }

    if (loadMoreTimelineBtn) {
        const recruiterId = loadMoreTimelineBtn.dataset.recruiterId;
        loadMoreTimelineBtn.addEventListener('click', function () {
            loadTimelinePage(recruiterId, timelinePage + 1);
        });
    }

    // Auto-load first page of timeline if container exists and is empty
    if (timelineContainer && timelineContainer.dataset.recruiterId) {
        const rid = timelineContainer.dataset.recruiter_id || timelineContainer.dataset.recruiterId;
        if (timelineContainer.children.length === 0) {
            loadTimelinePage(rid, 1);
        }
    }

    // -------------------------------------------------------------------------
    // Form Validation for Rejection and Suspension Reason Inputs (Requirements: 18.1, 18.2)
    // -------------------------------------------------------------------------

    function attachReasonValidation(formSelector, reasonSelector) {
        const form = document.querySelector(formSelector);
        if (!form) return;

        form.addEventListener('submit', function (e) {
            const reasonInput = form.querySelector(reasonSelector);
            if (!reasonInput) return;

            if (!reasonInput.value.trim()) {
                e.preventDefault();
                reasonInput.classList.add('border-red-500', 'ring-1', 'ring-red-500');

                let errMsg = reasonInput.nextElementSibling;
                if (!errMsg || !errMsg.classList.contains('reason-error')) {
                    errMsg = document.createElement('p');
                    errMsg.className = 'reason-error text-red-500 text-sm mt-1';
                    errMsg.textContent = 'A reason is required.';
                    reasonInput.insertAdjacentElement('afterend', errMsg);
                }
                reasonInput.focus();
            }
        });

        const reasonInput = form.querySelector(reasonSelector);
        if (reasonInput) {
            reasonInput.addEventListener('input', function () {
                reasonInput.classList.remove('border-red-500', 'ring-1', 'ring-red-500');
                const errMsg = reasonInput.nextElementSibling;
                if (errMsg && errMsg.classList.contains('reason-error')) {
                    errMsg.remove();
                }
            });
        }
    }

    attachReasonValidation('#reject-form', '[name="reason"]');
    attachReasonValidation('#suspend-form', '[name="reason"]');

    // -------------------------------------------------------------------------
    // Inline Action Modals (Approve / Reject / Suspend / Activate per row)
    // -------------------------------------------------------------------------

    document.addEventListener('click', function (e) {
        // Open modal triggers
        const trigger = e.target.closest('[data-modal-target]');
        if (trigger) {
            e.preventDefault();
            const modalId = trigger.dataset.modalTarget;
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.remove('hidden');
                modal.setAttribute('aria-hidden', 'false');

                // Populate hidden recruiter ID if present
                const recruiterIdInput = modal.querySelector('[name="recruiter_id"]');
                if (recruiterIdInput && trigger.dataset.recruiterId) {
                    recruiterIdInput.value = trigger.dataset.recruiterId;
                }
            }
        }

        // Close modal triggers
        const closeBtn = e.target.closest('[data-modal-close]');
        if (closeBtn) {
            const modal = closeBtn.closest('.modal-overlay');
            if (modal) {
                modal.classList.add('hidden');
                modal.setAttribute('aria-hidden', 'true');
            }
        }
    });

    // Close modals on Escape key
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal-overlay:not(.hidden)').forEach(function (modal) {
                modal.classList.add('hidden');
                modal.setAttribute('aria-hidden', 'true');
            });
            closeBulkConfirmModal();
        }
    });

})();
