/**
 * Admin Recruiter Analytics JavaScript
 * Handles date range picker, CSV report download, and table sorting.
 * Requirements: 8.5, 17.1, 17.5
 */

(function () {
    'use strict';

    // -------------------------------------------------------------------------
    // Date Range Picker for Analytics Filtering (Requirements: 8.5)
    // -------------------------------------------------------------------------

    const dateRangeForm = document.getElementById('analytics-filter-form');
    const startDateInput = document.getElementById('analytics-start-date');
    const endDateInput = document.getElementById('analytics-end-date');

    function applyDateRangeFilter() {
        if (!dateRangeForm) return;

        const start = startDateInput ? startDateInput.value : '';
        const end = endDateInput ? endDateInput.value : '';

        // Basic validation: end must be >= start
        if (start && end && end < start) {
            showDateError('End date must be on or after the start date.');
            return;
        }

        clearDateError();
        dateRangeForm.submit();
    }

    function showDateError(message) {
        let errEl = document.getElementById('date-range-error');
        if (!errEl) {
            errEl = document.createElement('p');
            errEl.id = 'date-range-error';
            errEl.className = 'text-red-500 text-sm mt-1';
            if (dateRangeForm) {
                dateRangeForm.appendChild(errEl);
            }
        }
        errEl.textContent = message;
    }

    function clearDateError() {
        const errEl = document.getElementById('date-range-error');
        if (errEl) errEl.remove();
    }

    if (startDateInput) {
        startDateInput.addEventListener('change', function () {
            // Ensure end date minimum is updated
            if (endDateInput && startDateInput.value) {
                endDateInput.min = startDateInput.value;
            }
        });
    }

    if (endDateInput) {
        endDateInput.addEventListener('change', function () {
            if (startDateInput && endDateInput.value) {
                startDateInput.max = endDateInput.value;
            }
        });
    }

    const applyDateBtn = document.getElementById('apply-date-range-btn');
    if (applyDateBtn) {
        applyDateBtn.addEventListener('click', function (e) {
            e.preventDefault();
            applyDateRangeFilter();
        });
    }

    const clearDateBtn = document.getElementById('clear-date-range-btn');
    if (clearDateBtn) {
        clearDateBtn.addEventListener('click', function () {
            if (startDateInput) {
                startDateInput.value = '';
                startDateInput.removeAttribute('max');
            }
            if (endDateInput) {
                endDateInput.value = '';
                endDateInput.removeAttribute('min');
            }
            clearDateError();
            if (dateRangeForm) dateRangeForm.submit();
        });
    }

    // -------------------------------------------------------------------------
    // CSV Report Download with Loading Indicator (Requirements: 17.1, 17.5)
    // -------------------------------------------------------------------------

    const generateReportBtn = document.getElementById('generate-report-btn');
    const reportLoadingIndicator = document.getElementById('report-loading');

    function showReportLoading() {
        if (generateReportBtn) {
            generateReportBtn.disabled = true;
            generateReportBtn.dataset.originalText = generateReportBtn.textContent;
            generateReportBtn.textContent = 'Generating...';
        }
        if (reportLoadingIndicator) {
            reportLoadingIndicator.classList.remove('hidden');
        }
    }

    function hideReportLoading() {
        if (generateReportBtn) {
            generateReportBtn.disabled = false;
            generateReportBtn.textContent = generateReportBtn.dataset.originalText || 'Generate Report';
        }
        if (reportLoadingIndicator) {
            reportLoadingIndicator.classList.add('hidden');
        }
    }

    if (generateReportBtn) {
        generateReportBtn.addEventListener('click', function (e) {
            e.preventDefault();
            showReportLoading();

            // Build URL with current filter params
            const params = new URLSearchParams();
            if (startDateInput && startDateInput.value) params.set('start_date', startDateInput.value);
            if (endDateInput && endDateInput.value) params.set('end_date', endDateInput.value);

            const reportUrl = '/admin/recruiter-analytics/report?' + params.toString();

            // Use a hidden iframe or anchor to trigger download without navigating away
            const anchor = document.createElement('a');
            anchor.href = reportUrl;
            anchor.download = 'recruiter-analytics-report.csv';
            document.body.appendChild(anchor);
            anchor.click();
            document.body.removeChild(anchor);

            // Hide loading after a short delay (download is async from browser perspective)
            setTimeout(hideReportLoading, 2000);
        });
    }

    // -------------------------------------------------------------------------
    // Table Sorting by Clicking Column Headers (Requirements: 8.5)
    // -------------------------------------------------------------------------

    const analyticsTable = document.getElementById('analytics-table');

    function getCurrentSortParams() {
        const url = new URL(window.location.href);
        return {
            sort: url.searchParams.get('sort') || '',
            direction: url.searchParams.get('direction') || 'desc',
        };
    }

    function updateSortIndicators(activeSort, activeDirection) {
        document.querySelectorAll('[data-sort-col]').forEach(function (th) {
            const col = th.dataset.sortCol;
            const indicator = th.querySelector('.sort-indicator');
            if (!indicator) return;

            if (col === activeSort) {
                indicator.textContent = activeDirection === 'asc' ? ' ▲' : ' ▼';
                indicator.classList.add('text-blue-600');
            } else {
                indicator.textContent = ' ⇅';
                indicator.classList.remove('text-blue-600');
            }
        });
    }

    if (analyticsTable) {
        const { sort: currentSort, direction: currentDirection } = getCurrentSortParams();
        updateSortIndicators(currentSort, currentDirection);

        analyticsTable.querySelectorAll('[data-sort-col]').forEach(function (th) {
            th.style.cursor = 'pointer';
            th.setAttribute('role', 'button');
            th.setAttribute('tabindex', '0');

            function triggerSort() {
                const col = th.dataset.sortCol;
                const url = new URL(window.location.href);
                const existingSort = url.searchParams.get('sort');
                const existingDir = url.searchParams.get('direction') || 'desc';

                let newDir = 'desc';
                if (existingSort === col) {
                    newDir = existingDir === 'asc' ? 'desc' : 'asc';
                }

                url.searchParams.set('sort', col);
                url.searchParams.set('direction', newDir);
                window.location.href = url.toString();
            }

            th.addEventListener('click', triggerSort);
            th.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    triggerSort();
                }
            });
        });
    }

    // -------------------------------------------------------------------------
    // Highlight slow-response recruiters (> 7 days) (Requirements: 8.6)
    // -------------------------------------------------------------------------

    document.querySelectorAll('[data-avg-response]').forEach(function (cell) {
        const days = parseFloat(cell.dataset.avgResponse);
        if (!isNaN(days) && days > 7) {
            cell.classList.add('text-red-600', 'font-semibold');
            if (!cell.querySelector('.slow-badge')) {
                const badge = document.createElement('span');
                badge.className = 'slow-badge ml-1 inline-block bg-red-100 text-red-700 text-xs px-1 rounded';
                badge.textContent = 'Slow';
                cell.appendChild(badge);
            }
        }
    });

})();
