@extends('recruiter.layouts.app')

@section('content')
<style>
    .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
    .page-header h1 { color: #fff; font-size: 1.8rem; font-weight: 700; }

    /* Filters */
    .filters-bar {
        background: rgba(255,255,255,.07); border: 1px solid rgba(255,255,255,.1);
        border-radius: 14px; padding: 1.2rem 1.5rem; margin-bottom: 1.5rem;
        display: flex; flex-wrap: wrap; gap: 1rem; align-items: flex-end;
    }
    .filter-group { display: flex; flex-direction: column; gap: .4rem; min-width: 160px; }
    .filter-group label { color: rgba(255,255,255,.6); font-size: .8rem; font-weight: 500; }
    .filter-control {
        background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.15);
        border-radius: 8px; color: #fff; padding: .5rem .9rem; font-size: .85rem;
    }
    .filter-control option { background: #1a1a2e; }
    .btn-filter {
        background: rgba(233,69,96,.15); border: 1px solid rgba(233,69,96,.3);
        color: #e94560; padding: .5rem 1.2rem; border-radius: 8px;
        font-size: .85rem; font-weight: 600; cursor: pointer; transition: all .2s;
    }
    .btn-filter:hover { background: rgba(233,69,96,.3); }
    .btn-clear {
        background: rgba(255,255,255,.06); border: 1px solid rgba(255,255,255,.12);
        color: rgba(255,255,255,.6); padding: .5rem 1rem; border-radius: 8px;
        font-size: .85rem; text-decoration: none; transition: all .2s;
    }
    .btn-clear:hover { color: #fff; }

    /* Bulk actions */
    .bulk-bar {
        background: rgba(233,69,96,.1); border: 1px solid rgba(233,69,96,.2);
        border-radius: 10px; padding: .8rem 1.2rem; margin-bottom: 1rem;
        display: none; align-items: center; gap: 1rem;
    }
    .bulk-bar.visible { display: flex; }
    .bulk-bar span { color: rgba(255,255,255,.8); font-size: .9rem; }
    .bulk-select {
        background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.15);
        border-radius: 8px; color: #fff; padding: .4rem .8rem; font-size: .85rem;
    }
    .bulk-select option { background: #1a1a2e; }
    .btn-bulk {
        background: #e94560; border: none; color: #fff;
        padding: .4rem 1rem; border-radius: 8px; font-size: .85rem;
        font-weight: 600; cursor: pointer; transition: all .2s;
    }
    .btn-bulk:hover { background: #c62a47; }

    /* Group header */
    .group-header {
        background: rgba(255,255,255,.05); border: 1px solid rgba(255,255,255,.08);
        border-radius: 10px; padding: .75rem 1.2rem; margin-bottom: .5rem; margin-top: 1.5rem;
        display: flex; justify-content: space-between; align-items: center;
    }
    .group-header:first-of-type { margin-top: 0; }
    .group-title { color: #fff; font-weight: 600; font-size: 1rem; }
    .group-count { color: rgba(255,255,255,.5); font-size: .85rem; }

    /* Table */
    .card { background: rgba(255,255,255,.07); border: 1px solid rgba(255,255,255,.1); border-radius: 14px; overflow: hidden; margin-bottom: 1rem; }
    table { width: 100%; border-collapse: collapse; }
    thead th {
        background: rgba(255,255,255,.04); color: rgba(255,255,255,.5);
        font-size: .75rem; font-weight: 600; text-transform: uppercase; letter-spacing: .05em;
        padding: .8rem 1rem; text-align: left;
    }
    tbody tr { border-top: 1px solid rgba(255,255,255,.05); transition: background .15s; }
    tbody tr:hover { background: rgba(255,255,255,.03); }
    tbody td { padding: .85rem 1rem; color: rgba(255,255,255,.85); font-size: .88rem; }

    /* Status badge */
    .status-badge {
        padding: .25rem .7rem; border-radius: 20px; font-size: .75rem; font-weight: 600;
        display: inline-block;
    }
    .status-pending           { background: rgba(255,193,7,.2);   color: #f2c94c; }
    .status-under_review      { background: rgba(0,123,255,.2);   color: #4da3ff; }
    .status-shortlisted       { background: rgba(111,66,193,.2);  color: #b39ddb; }
    .status-interview_scheduled { background: rgba(23,162,184,.2); color: #56ccf2; }
    .status-approved          { background: rgba(40,167,69,.2);   color: #6fcf97; }
    .status-rejected          { background: rgba(220,53,69,.2);   color: #eb5757; }

    /* Status dropdown */
    .status-select {
        background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.12);
        border-radius: 8px; color: #fff; padding: .3rem .6rem; font-size: .8rem; cursor: pointer;
    }
    .status-select option { background: #1a1a2e; }

    /* Action buttons */
    .btn-action {
        background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.12);
        color: rgba(255,255,255,.8); padding: .3rem .7rem; border-radius: 7px;
        font-size: .78rem; cursor: pointer; transition: all .15s; text-decoration: none;
        display: inline-flex; align-items: center; gap: .3rem;
    }
    .btn-action:hover { background: rgba(255,255,255,.15); color: #fff; }

    .empty-state { text-align: center; padding: 3rem; color: rgba(255,255,255,.5); }
    .empty-state i { font-size: 2.5rem; margin-bottom: .75rem; display: block; }

    /* Profile modal */
    .modal-overlay {
        display: none; position: fixed; inset: 0;
        background: rgba(0,0,0,.7); backdrop-filter: blur(6px);
        z-index: 9000; align-items: center; justify-content: center;
    }
    .modal-overlay.open { display: flex; }
    .modal-box {
        background: #1a1a2e; border: 1px solid rgba(255,255,255,.15);
        border-radius: 20px; padding: 2rem; max-width: 600px; width: 90%;
        max-height: 85vh; overflow-y: auto; position: relative;
        animation: fadeUp .25s ease;
    }
    @keyframes fadeUp { from { opacity:0; transform: translateY(20px); } to { opacity:1; transform: translateY(0); } }
    .modal-close {
        position: absolute; top: 1rem; right: 1rem;
        background: rgba(255,255,255,.1); border: none; color: #fff;
        width: 32px; height: 32px; border-radius: 50%; cursor: pointer; font-size: 1.1rem;
        display: flex; align-items: center; justify-content: center; transition: all .2s;
    }
    .modal-close:hover { background: rgba(255,255,255,.2); }
    .modal-title { color: #fff; font-size: 1.3rem; font-weight: 700; margin-bottom: 1.5rem; }
    .profile-field { margin-bottom: 1rem; }
    .profile-field label { color: rgba(255,255,255,.5); font-size: .8rem; font-weight: 500; display: block; margin-bottom: .3rem; }
    .profile-field p { color: rgba(255,255,255,.9); font-size: .9rem; }
    .resume-frame { width: 100%; height: 300px; border: 1px solid rgba(255,255,255,.1); border-radius: 10px; margin-top: .5rem; }
    .btn-download {
        background: rgba(40,167,69,.15); border: 1px solid rgba(40,167,69,.3);
        color: #6fcf97; padding: .4rem 1rem; border-radius: 8px; font-size: .85rem;
        text-decoration: none; display: inline-flex; align-items: center; gap: .4rem; margin-top: .5rem;
    }
    .loading-spinner { text-align: center; padding: 2rem; color: rgba(255,255,255,.5); }
    
    @keyframes fadeIn { from { opacity:0; transform: translateY(-10px); } to { opacity:1; transform: translateY(0); } }
</style>

<div class="page-header">
    <h1><i class="fas fa-users me-2"></i>Applications</h1>
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
        <button type="submit" class="btn-filter"><i class="fas fa-filter me-1"></i>Filter</button>
        <a href="{{ route('recruiter.applications.index') }}" class="btn-clear">Clear</a>
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
                <span class="group-title"><i class="fas fa-briefcase me-2"></i>{{ $internshipTitle }}</span>
                <span class="group-count">{{ $apps->count() }} application(s)</span>
            </div>
            <div class="card">
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
                            <td style="display:flex;gap:.4rem;flex-wrap:wrap">
                                <button type="button" class="btn-action view-profile-btn"
                                        data-id="{{ $app->id }}">
                                    <i class="fas fa-user"></i> Profile
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
                                <a href="{{ route('recruiter.applications.history', $app) }}" class="btn-action">
                                    <i class="fas fa-history"></i>
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endforeach
    @endif
</form>

{{-- Profile Modal --}}
<div class="modal-overlay" id="profileModal">
    <div class="modal-box" id="profileModalBox">
        <button class="modal-close" id="modalClose">×</button>
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
        
        console.log('Updating status:', { id, status, currentStatus });
        
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
            console.log('Response status:', r.status);
            if (!r.ok) {
                return r.json().then(err => {
                    console.error('Error response:', err);
                    return Promise.reject(err);
                }).catch(parseErr => {
                    // If JSON parsing fails, return text
                    return r.text().then(text => {
                        console.error('Non-JSON error response:', text);
                        return Promise.reject({ message: 'Server error. Please check the console for details.' });
                    });
                });
            }
            return r.json();
        })
        .then(data => {
            console.log('Success response:', data);
            if (data.success) {
                const badge = this.closest('tr').querySelector('.status-badge');
                badge.className = `status-badge status-${data.status}`;
                badge.textContent = data.status_label;
                this.dataset.current = data.status;
                
                // Show success message
                const successMsg = document.createElement('div');
                successMsg.style.cssText = 'position:fixed;top:20px;right:20px;background:#6fcf97;color:#fff;padding:1rem 1.5rem;border-radius:10px;z-index:10000;animation:fadeIn 0.3s';
                successMsg.innerHTML = '<i class="fas fa-check-circle me-2"></i>Status updated successfully!';
                document.body.appendChild(successMsg);
                setTimeout(() => successMsg.remove(), 3000);
            }
        })
        .catch(err => {
            console.error('Catch block error:', err);
            // Revert dropdown to previous status
            this.value = currentStatus;
            
            // Show error message
            const errorMsg = err.message || 'Failed to update status. Please check the status transition rules.';
            alert(errorMsg);
        });
    });
});

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
                   <a href="${data.resume_url}" download class="btn-download"><i class="fas fa-download"></i> Download Resume</a>`
                : `<p style="color:rgba(255,255,255,.5);font-size:.9rem">No resume uploaded.</p>`;

            modalContent.innerHTML = `
                <div class="modal-title"><i class="fas fa-user-circle me-2"></i>${data.name}</div>
                <div class="profile-field"><label>Email</label><p>${data.email}</p></div>
                <div class="profile-field"><label>Skills</label><p>${skills}</p></div>
                <div class="profile-field"><label>Academic Background</label><p>${data.academic_background || '—'}</p></div>
                <div class="profile-field"><label>Career Interests</label><p>${data.career_interests || '—'}</p></div>
                <div class="profile-field"><label>Resume</label>${resumeHtml}</div>
            `;
        })
        .catch(() => {
            modalContent.innerHTML = '<p style="color:#eb5757;text-align:center">Failed to load profile.</p>';
        });
    });
});

document.getElementById('modalClose').addEventListener('click', () => modal.classList.remove('open'));
modal.addEventListener('click', e => { if (e.target === modal) modal.classList.remove('open'); });
</script>
@endpush
