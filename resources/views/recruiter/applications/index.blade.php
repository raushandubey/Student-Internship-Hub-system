@extends('recruiter.layouts.app')

@section('content')
@push('styles')
<style>
    /* ── Page Header ── */
    .page-header {
        display: flex; justify-content: space-between; align-items: center;
        margin-bottom: 1.5rem; flex-wrap: wrap; gap: .75rem;
    }
    .page-header h1 { color: #fff; font-size: clamp(1.3rem, 4vw, 1.8rem); font-weight: 700; }

    /* ── Filters ── */
    .filters-bar {
        background: rgba(255,255,255,.07); border: 1px solid rgba(255,255,255,.1);
        border-radius: 14px; padding: 1.1rem 1.25rem; margin-bottom: 1.25rem;
        display: flex; flex-wrap: wrap; gap: .85rem; align-items: flex-end;
    }
    .filter-group { display: flex; flex-direction: column; gap: .35rem; flex: 1; min-width: 140px; }
    .filter-group label { color: rgba(255,255,255,.55); font-size: .78rem; font-weight: 500; }
    .filter-control {
        background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.15);
        border-radius: 8px; color: #fff; padding: .5rem .85rem; font-size: .83rem;
        width: 100%;
    }
    .filter-control option { background: #1a1a2e; }
    .filter-actions { display: flex; gap: .5rem; flex-shrink: 0; align-items: flex-end; }
    .btn-filter {
        background: rgba(233,69,96,.15); border: 1px solid rgba(233,69,96,.3);
        color: #e94560; padding: .5rem 1.1rem; border-radius: 8px;
        font-size: .83rem; font-weight: 600; cursor: pointer; transition: all .2s;
        white-space: nowrap;
    }
    .btn-filter:hover { background: rgba(233,69,96,.3); }
    .btn-clear {
        background: rgba(255,255,255,.06); border: 1px solid rgba(255,255,255,.12);
        color: rgba(255,255,255,.6); padding: .5rem .9rem; border-radius: 8px;
        font-size: .83rem; text-decoration: none; transition: all .2s; white-space: nowrap;
        display: inline-flex; align-items: center;
    }
    .btn-clear:hover { color: #fff; background: rgba(255,255,255,.1); }

    /* ── Bulk actions ── */
    .bulk-bar {
        background: rgba(233,69,96,.08); border: 1px solid rgba(233,69,96,.2);
        border-radius: 10px; padding: .75rem 1.1rem; margin-bottom: 1rem;
        display: none; align-items: center; gap: .85rem; flex-wrap: wrap;
    }
    .bulk-bar.visible { display: flex; }
    .bulk-bar span { color: rgba(255,255,255,.8); font-size: .875rem; }
    .bulk-select {
        background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.15);
        border-radius: 8px; color: #fff; padding: .38rem .75rem; font-size: .83rem;
    }
    .bulk-select option { background: #1a1a2e; }
    .btn-bulk {
        background: #e94560; border: none; color: #fff;
        padding: .4rem .95rem; border-radius: 8px; font-size: .83rem;
        font-weight: 600; cursor: pointer; transition: background .2s;
    }
    .btn-bulk:hover { background: #c62a47; }

    /* ── Group header ── */
    .group-header {
        background: rgba(255,255,255,.05); border: 1px solid rgba(255,255,255,.08);
        border-radius: 10px; padding: .7rem 1.1rem; margin-bottom: .6rem; margin-top: 1.5rem;
        display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: .4rem;
    }
    .group-header:first-of-type { margin-top: 0; }
    .group-title { color: #fff; font-weight: 600; font-size: .9rem; display: flex; align-items: center; gap: .5rem; }
    .group-count { color: rgba(255,255,255,.45); font-size: .8rem; }

    /* ── Status badge ── */
    .status-badge {
        padding: .22rem .65rem; border-radius: 20px; font-size: .72rem; font-weight: 600;
        display: inline-block; white-space: nowrap;
    }
    .status-pending             { background: rgba(255,193,7,.18);   color: #f2c94c; }
    .status-under_review        { background: rgba(0,123,255,.18);   color: #4da3ff; }
    .status-shortlisted         { background: rgba(111,66,193,.18);  color: #b39ddb; }
    .status-interview_scheduled { background: rgba(23,162,184,.18);  color: #56ccf2; }
    .status-approved            { background: rgba(40,167,69,.18);   color: #6fcf97; }
    .status-rejected            { background: rgba(220,53,69,.18);   color: #eb5757; }

    /* ── Status dropdown ── */
    .status-select {
        background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.12);
        border-radius: 8px; color: #fff; padding: .28rem .55rem; font-size: .78rem; cursor: pointer;
    }
    .status-select option { background: #1a1a2e; }

    /* ── Action buttons ── */
    .btn-action {
        background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.12);
        color: rgba(255,255,255,.8); padding: .28rem .65rem; border-radius: 7px;
        font-size: .76rem; cursor: pointer; transition: all .15s; text-decoration: none;
        display: inline-flex; align-items: center; gap: .28rem; white-space: nowrap;
    }
    .btn-action:hover { background: rgba(255,255,255,.15); color: #fff; }

    /* ── DESKTOP: Table view ── */
    .desktop-table-wrap {
        background: rgba(255,255,255,.07); border: 1px solid rgba(255,255,255,.1);
        border-radius: 14px; overflow: hidden; margin-bottom: 1rem;
    }
    .desktop-table-wrap table { width: 100%; border-collapse: collapse; }
    .desktop-table-wrap thead th {
        background: rgba(255,255,255,.04); color: rgba(255,255,255,.45);
        font-size: .72rem; font-weight: 600; text-transform: uppercase; letter-spacing: .05em;
        padding: .8rem 1rem; text-align: left;
    }
    .desktop-table-wrap tbody tr { border-top: 1px solid rgba(255,255,255,.05); transition: background .15s; }
    .desktop-table-wrap tbody tr:hover { background: rgba(255,255,255,.03); }
    .desktop-table-wrap tbody td { padding: .8rem 1rem; color: rgba(255,255,255,.85); font-size: .86rem; }
    .desktop-table-wrap tbody td.actions-cell { display: flex; gap: .35rem; flex-wrap: wrap; align-items: center; }

    /* ── MOBILE: Card view ── */
    .mobile-app-cards { display: none; }
    .app-card {
        background: rgba(255,255,255,.07); border: 1px solid rgba(255,255,255,.1);
        border-radius: 14px; padding: 1rem; margin-bottom: .75rem;
        transition: transform .15s;
    }
    .app-card:active { transform: scale(.995); }
    .app-card-top {
        display: flex; align-items: flex-start; justify-content: space-between;
        gap: .75rem; margin-bottom: .75rem;
    }
    .app-card-name {
        font-size: .95rem; font-weight: 700; color: #fff;
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    }
    .app-card-email { font-size: .78rem; color: rgba(255,255,255,.5); margin-top: .15rem; }
    .app-card-check { flex-shrink: 0; margin-top: .1rem; }
    .app-card-meta { display: flex; flex-wrap: wrap; gap: .4rem; margin-bottom: .75rem; align-items: center; }
    .app-card-date { font-size: .75rem; color: rgba(255,255,255,.4); }
    .app-card-actions {
        display: flex; flex-wrap: wrap; gap: .4rem; align-items: center;
        padding-top: .75rem; border-top: 1px solid rgba(255,255,255,.06);
    }

    /* ── Empty state ── */
    .empty-state { text-align: center; padding: 3rem 1.5rem; color: rgba(255,255,255,.45); }
    .empty-state i { font-size: 2.5rem; margin-bottom: .75rem; display: block; opacity: .4; }

    /* ── Profile modal ── */
    .modal-overlay {
        display: none; position: fixed; inset: 0;
        background: rgba(0,0,0,.7); backdrop-filter: blur(6px);
        z-index: 9000; align-items: center; justify-content: center; padding: 1rem;
    }
    .modal-overlay.open { display: flex; }
    .modal-box {
        background: #1a1a2e; border: 1px solid rgba(255,255,255,.15);
        border-radius: 20px; padding: 1.75rem; width: 100%; max-width: 580px;
        max-height: 90vh; overflow-y: auto; position: relative;
        animation: fadeUp .25s ease;
    }
    @keyframes fadeUp { from { opacity:0; transform:translateY(20px); } to { opacity:1; transform:translateY(0); } }
    .modal-close {
        position: absolute; top: 1rem; right: 1rem;
        background: rgba(255,255,255,.1); border: none; color: #fff;
        width: 32px; height: 32px; border-radius: 50%; cursor: pointer; font-size: 1rem;
        display: flex; align-items: center; justify-content: center; transition: all .2s;
    }
    .modal-close:hover { background: rgba(255,255,255,.2); }
    .modal-title { color: #fff; font-size: 1.2rem; font-weight: 700; margin-bottom: 1.4rem; margin-right: 2rem; }
    .profile-field { margin-bottom: 1rem; }
    .profile-field label { color: rgba(255,255,255,.45); font-size: .75rem; font-weight: 500; display: block; margin-bottom: .3rem; text-transform: uppercase; letter-spacing: .04em; }
    .profile-field p { color: rgba(255,255,255,.9); font-size: .88rem; }
    .resume-frame { width: 100%; height: 280px; border: 1px solid rgba(255,255,255,.1); border-radius: 10px; margin-top: .5rem; }
    .btn-download {
        background: rgba(40,167,69,.12); border: 1px solid rgba(40,167,69,.25);
        color: #6fcf97; padding: .4rem .9rem; border-radius: 8px; font-size: .82rem;
        text-decoration: none; display: inline-flex; align-items: center; gap: .4rem; margin-top: .5rem;
        transition: all .2s;
    }
    .btn-download:hover { background: rgba(40,167,69,.2); }
    .loading-spinner { text-align: center; padding: 2rem; color: rgba(255,255,255,.45); }

    @keyframes fadeIn { from { opacity:0; transform:translateY(-8px); } to { opacity:1; transform:translateY(0); } }

    /* ── Responsive switch ── */
    @media (max-width: 768px) {
        .desktop-table-wrap { display: none; }
        .mobile-app-cards { display: block; }
        .filter-group { min-width: calc(50% - .5rem); }
        .filters-bar { padding: 1rem; }
    }
    @media (max-width: 480px) {
        .filter-group { min-width: 100%; }
        .filter-actions { width: 100%; }
        .btn-filter, .btn-clear { flex: 1; text-align: center; justify-content: center; }
    }
</style>
@endpush

<div class="page-header">
    <h1><i class="fas fa-users" style="color:#e94560;margin-right:.5rem"></i>Applications</h1>
</div>

{{-- Filters --}}
<form method="GET" action="{{ route('recruiter.applications.index') }}">
    <div class="filters-bar">
        <div class="filter-group">
            <label>Status</label>
            <select name="status" class="filter-control">
                <option value="">All Statuses</option>
                @foreach(\App\Enums\ApplicationStatus::cases() as $s)
                    <option value="{{ $s->value }}" {{ ($filters['status'] ?? '') === $s->value ? 'selected' : '' }}>
                        {{ $s->label() }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="filter-group">
            <label>Internship</label>
            <select name="internship_id" class="filter-control">
                <option value="">All Internships</option>
                @foreach($internships as $i)
                    <option value="{{ $i->id }}" {{ ($filters['internship_id'] ?? '') == $i->id ? 'selected' : '' }}>
                        {{ $i->title }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="filter-group">
            <label>From Date</label>
            <input type="date" name="date_from" class="filter-control" value="{{ $filters['date_from'] ?? '' }}">
        </div>
        <div class="filter-group">
            <label>To Date</label>
            <input type="date" name="date_to" class="filter-control" value="{{ $filters['date_to'] ?? '' }}">
        </div>
        <div class="filter-actions">
            <button type="submit" class="btn-filter"><i class="fas fa-filter" style="margin-right:.3rem"></i>Filter</button>
            <a href="{{ route('recruiter.applications.index') }}" class="btn-clear">Clear</a>
        </div>
    </div>
</form>

{{-- Bulk actions --}}
<form method="POST" action="{{ route('recruiter.applications.bulk-update') }}" id="bulkForm">
    @csrf
    <div class="bulk-bar" id="bulkBar">
        <span id="selectedCount">0 selected</span>
        <select name="status" class="bulk-select">
            @foreach(\App\Enums\ApplicationStatus::cases() as $s)
                <option value="{{ $s->value }}">→ {{ $s->label() }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn-bulk" onclick="return confirm('Update all selected applications?')">
            Apply Bulk Update
        </button>
    </div>
    <div id="bulkHiddenInputs"></div>

    @if($grouped->isEmpty())
        <div class="empty-state">
            <i class="fas fa-inbox"></i>
            <p>No applications found.</p>
        </div>
    @else
        @foreach($grouped as $internshipTitle => $apps)
            <div class="group-header">
                <span class="group-title">
                    <i class="fas fa-briefcase" style="color:#e94560"></i>{{ $internshipTitle }}
                </span>
                <span class="group-count">{{ $apps->count() }} application(s)</span>
            </div>

            {{-- ── Desktop Table ── --}}
            <div class="desktop-table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th><input type="checkbox" class="select-all-group" data-group="{{ $loop->index }}"></th>
                            <th>Student</th>
                            <th>Email</th>
                            <th>Applied</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($apps as $app)
                        <tr>
                            <td>
                                <input type="checkbox" class="app-checkbox" value="{{ $app->id }}"
                                       data-group="{{ $loop->parent->index }}">
                            </td>
                            <td><strong>{{ $app->user->name }}</strong></td>
                            <td>{{ $app->user->email }}</td>
                            <td>{{ $app->created_at->format('M d, Y') }}</td>
                            <td>
                                <span class="status-badge status-{{ $app->status->value }}">
                                    {{ $app->status->label() }}
                                </span>
                            </td>
                            <td class="actions-cell">
                                <button type="button" class="btn-action view-profile-btn" data-id="{{ $app->id }}">
                                    <i class="fas fa-user"></i>Profile
                                </button>
                                <select class="status-select status-update-select"
                                        data-id="{{ $app->id }}"
                                        data-current="{{ $app->status->value }}">
                                    @foreach(\App\Enums\ApplicationStatus::cases() as $s)
                                        <option value="{{ $s->value }}" {{ $app->status->value === $s->value ? 'selected' : '' }}>
                                            {{ $s->label() }}
                                        </option>
                                    @endforeach
                                </select>
                                <a href="{{ route('recruiter.applications.history', $app) }}" class="btn-action" title="History">
                                    <i class="fas fa-history"></i>
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- ── Mobile Cards ── --}}
            <div class="mobile-app-cards">
                @foreach($apps as $app)
                <div class="app-card">
                    <div class="app-card-top">
                        <div style="flex:1;min-width:0">
                            <div class="app-card-name">{{ $app->user->name }}</div>
                            <div class="app-card-email">{{ $app->user->email }}</div>
                        </div>
                        <input type="checkbox" class="app-checkbox app-card-check"
                               value="{{ $app->id }}" data-group="{{ $loop->parent->index }}"
                               style="width:18px;height:18px;cursor:pointer;accent-color:#e94560">
                    </div>
                    <div class="app-card-meta">
                        <span class="status-badge status-{{ $app->status->value }}">
                            {{ $app->status->label() }}
                        </span>
                        <span class="app-card-date">
                            <i class="fas fa-calendar-alt" style="margin-right:.25rem"></i>{{ $app->created_at->format('M d, Y') }}
                        </span>
                    </div>
                    <div class="app-card-actions">
                        <button type="button" class="btn-action view-profile-btn" data-id="{{ $app->id }}">
                            <i class="fas fa-user"></i>Profile
                        </button>
                        <select class="status-select status-update-select"
                                data-id="{{ $app->id }}"
                                data-current="{{ $app->status->value }}"
                                style="flex:1;max-width:160px">
                            @foreach(\App\Enums\ApplicationStatus::cases() as $s)
                                <option value="{{ $s->value }}" {{ $app->status->value === $s->value ? 'selected' : '' }}>
                                    {{ $s->label() }}
                                </option>
                            @endforeach
                        </select>
                        <a href="{{ route('recruiter.applications.history', $app) }}" class="btn-action" title="History">
                            <i class="fas fa-history"></i>
                        </a>
                    </div>
                </div>
                @endforeach
            </div>

        @endforeach
    @endif
</form>

{{-- Profile Modal --}}
<div class="modal-overlay" id="profileModal">
    <div class="modal-box" id="profileModalBox">
        <button class="modal-close" id="modalClose" aria-label="Close">×</button>
        <div id="profileModalContent">
            <div class="loading-spinner"><i class="fas fa-spinner fa-spin fa-2x"></i></div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

// ── Bulk selection ──────────────────────────────────────────────
const bulkBar = document.getElementById('bulkBar');
const selectedCount = document.getElementById('selectedCount');
const bulkHiddenInputs = document.getElementById('bulkHiddenInputs');

function updateBulkBar() {
    const checked = document.querySelectorAll('.app-checkbox:checked');
    bulkHiddenInputs.innerHTML = '';
    checked.forEach(cb => {
        const inp = document.createElement('input');
        inp.type = 'hidden'; inp.name = 'application_ids[]'; inp.value = cb.value;
        bulkHiddenInputs.appendChild(inp);
    });
    if (checked.length > 0) {
        bulkBar.classList.add('visible');
        selectedCount.textContent = `${checked.length} selected`;
    } else {
        bulkBar.classList.remove('visible');
    }
}

document.querySelectorAll('.app-checkbox').forEach(cb => cb.addEventListener('change', updateBulkBar));

document.querySelectorAll('.select-all-group').forEach(sa => {
    sa.addEventListener('change', function() {
        const group = this.dataset.group;
        document.querySelectorAll(`.app-checkbox[data-group="${group}"]`)
            .forEach(cb => { cb.checked = this.checked; });
        updateBulkBar();
    });
});

// ── AJAX status update ──────────────────────────────────────────
document.querySelectorAll('.status-update-select').forEach(sel => {
    sel.addEventListener('change', function() {
        const id = this.dataset.id;
        const status = this.value;
        const currentStatus = this.dataset.current;

        fetch(`/recruiter/applications/${id}/status`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ status })
        })
        .then(r => {
            if (!r.ok) {
                return r.json().then(err => Promise.reject(err)).catch(() =>
                    r.text().then(text => Promise.reject({ message: 'Server error.' }))
                );
            }
            return r.json();
        })
        .then(data => {
            if (data.success) {
                // Update ALL badges for this app id (desktop + mobile cards)
                document.querySelectorAll(`.status-update-select[data-id="${id}"]`).forEach(s => {
                    s.dataset.current = data.status;
                    s.value = data.status;
                });
                // Update badges - find closest tr (desktop) or app-card (mobile)
                const desktopRow = this.closest('tr');
                if (desktopRow) {
                    const badge = desktopRow.querySelector('.status-badge');
                    if (badge) { badge.className = `status-badge status-${data.status}`; badge.textContent = data.status_label; }
                }
                const mobileCard = this.closest('.app-card');
                if (mobileCard) {
                    const badge = mobileCard.querySelector('.status-badge');
                    if (badge) { badge.className = `status-badge status-${data.status}`; badge.textContent = data.status_label; }
                }

                showToast('Status updated!', 'success');
            }
        })
        .catch(err => {
            this.value = currentStatus;
            showToast(err.message || 'Failed to update status.', 'error');
        });
    });
});

// ── Toast notification ──────────────────────────────────────────
function showToast(message, type) {
    const bg = type === 'success' ? '#6fcf97' : '#eb5757';
    const icon = type === 'success' ? 'check-circle' : 'exclamation-circle';
    const toast = document.createElement('div');
    toast.style.cssText = `position:fixed;top:20px;right:20px;background:${bg};color:#fff;padding:.9rem 1.4rem;border-radius:12px;z-index:10000;animation:fadeIn 0.3s ease;font-size:.875rem;font-weight:600;display:flex;align-items:center;gap:.5rem;box-shadow:0 4px 20px rgba(0,0,0,.3);max-width:calc(100vw - 2rem)`;
    toast.innerHTML = `<i class="fas fa-${icon}"></i>${message}`;
    document.body.appendChild(toast);
    setTimeout(() => { toast.style.opacity = '0'; toast.style.transition = 'opacity .3s'; setTimeout(() => toast.remove(), 300); }, 3000);
}

// ── Profile modal ───────────────────────────────────────────────
const modal = document.getElementById('profileModal');
const modalContent = document.getElementById('profileModalContent');

document.querySelectorAll('.view-profile-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const id = this.dataset.id;
        modal.classList.add('open');
        modalContent.innerHTML = '<div class="loading-spinner"><i class="fas fa-spinner fa-spin fa-2x"></i></div>';

        fetch(`/recruiter/applications/${id}/profile`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            const skills = Array.isArray(data.skills) ? data.skills.join(', ') : (data.skills || '—');
            const resumeHtml = data.resume_url
                ? `<iframe src="${data.resume_url}" class="resume-frame"></iframe>
                   <a href="${data.resume_url}" download class="btn-download"><i class="fas fa-download"></i>Download Resume</a>`
                : `<p style="color:rgba(255,255,255,.45);font-size:.875rem">No resume uploaded.</p>`;

            modalContent.innerHTML = `
                <div class="modal-title"><i class="fas fa-user-circle" style="color:#e94560;margin-right:.5rem"></i>${data.name}</div>
                <div class="profile-field"><label>Email</label><p>${data.email}</p></div>
                <div class="profile-field"><label>Skills</label><p>${skills}</p></div>
                <div class="profile-field"><label>Academic Background</label><p>${data.academic_background || '—'}</p></div>
                <div class="profile-field"><label>Career Interests</label><p>${data.career_interests || '—'}</p></div>
                <div class="profile-field"><label>Resume</label>${resumeHtml}</div>
            `;
        })
        .catch(() => {
            modalContent.innerHTML = '<p style="color:#eb5757;text-align:center;padding:1.5rem">Failed to load profile.</p>';
        });
    });
});

document.getElementById('modalClose').addEventListener('click', () => modal.classList.remove('open'));
modal.addEventListener('click', e => { if (e.target === modal) modal.classList.remove('open'); });
document.addEventListener('keydown', e => { if (e.key === 'Escape') modal.classList.remove('open'); });
</script>
@endpush
