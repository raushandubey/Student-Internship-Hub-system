@extends('recruiter.layouts.app')

@section('content')
@push('styles')
<style>
    /* ── Page Header ── */
    .page-header {
        display: flex; justify-content: space-between; align-items: center;
        margin-bottom: 1.75rem; flex-wrap: wrap; gap: .75rem;
    }
    .page-header h1 { color: #fff; font-size: clamp(1.3rem, 4vw, 1.8rem); font-weight: 700; }

    /* ── Buttons ── */
    .btn-primary {
        background: linear-gradient(135deg, #e94560, #c62a47);
        color: #fff; border: none; padding: .6rem 1.2rem;
        border-radius: 10px; font-weight: 600; font-size: .875rem;
        text-decoration: none; cursor: pointer; transition: all .2s;
        display: inline-flex; align-items: center; gap: .4rem;
        white-space: nowrap;
    }
    .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(233,69,96,.4); color: #fff; }
    .btn-outline {
        background: transparent; border: 1px solid rgba(255,255,255,.2);
        color: rgba(255,255,255,.8); padding: .38rem .85rem;
        border-radius: 8px; font-size: .78rem; font-weight: 500;
        text-decoration: none; cursor: pointer; transition: all .2s;
        display: inline-flex; align-items: center; gap: .3rem; white-space: nowrap;
    }
    .btn-outline:hover { background: rgba(255,255,255,.1); color: #fff; }
    .btn-danger { border-color: rgba(220,53,69,.35); color: #eb5757; }
    .btn-danger:hover { background: rgba(220,53,69,.12); border-color: rgba(220,53,69,.5); }

    /* ── Badge ── */
    .badge { padding: .25rem .65rem; border-radius: 20px; font-size: .72rem; font-weight: 600; }
    .badge-active   { background: rgba(40,167,69,.15);   color: #6fcf97; }
    .badge-inactive { background: rgba(108,117,125,.15); color: #adb5bd; }

    /* ── Empty state ── */
    .empty-state { text-align: center; padding: 3.5rem 1.5rem; color: rgba(255,255,255,.45); }
    .empty-state i { font-size: 2.8rem; margin-bottom: 1rem; display: block; opacity: .35; }

    /* ── DESKTOP Table ── */
    .desktop-table-wrap {
        background: rgba(255,255,255,.07); border: 1px solid rgba(255,255,255,.1);
        border-radius: 16px; overflow: hidden;
    }
    .desktop-table-wrap table { width: 100%; border-collapse: collapse; }
    .desktop-table-wrap thead th {
        background: rgba(255,255,255,.04); color: rgba(255,255,255,.45);
        font-size: .72rem; font-weight: 600; text-transform: uppercase; letter-spacing: .05em;
        padding: .9rem 1.1rem; text-align: left;
    }
    .desktop-table-wrap tbody tr { border-top: 1px solid rgba(255,255,255,.05); transition: background .15s; }
    .desktop-table-wrap tbody tr:hover { background: rgba(255,255,255,.03); }
    .desktop-table-wrap tbody td { padding: .9rem 1.1rem; color: rgba(255,255,255,.85); font-size: .875rem; }
    .desktop-table-wrap tbody td.actions-cell { display: flex; gap: .4rem; flex-wrap: wrap; align-items: center; }

    /* ── MOBILE Card list ── */
    .mobile-internship-cards { display: none; }
    .internship-card {
        background: rgba(255,255,255,.07); border: 1px solid rgba(255,255,255,.1);
        border-radius: 14px; padding: 1.1rem; margin-bottom: .75rem;
        transition: transform .15s;
    }
    .internship-card:active { transform: scale(.998); }
    .internship-card-header {
        display: flex; justify-content: space-between; align-items: flex-start;
        gap: .5rem; margin-bottom: .6rem;
    }
    .internship-card-title { color: #fff; font-weight: 700; font-size: .95rem; }
    .internship-card-org   { color: rgba(255,255,255,.5); font-size: .8rem; margin-top: .15rem; }
    .internship-card-meta {
        display: flex; flex-wrap: wrap; gap: .4rem; margin-bottom: .75rem;
        align-items: center; font-size: .78rem; color: rgba(255,255,255,.5);
    }
    .internship-card-meta span { display: flex; align-items: center; gap: .3rem; }
    .internship-card-actions {
        display: flex; gap: .4rem; flex-wrap: wrap;
        padding-top: .75rem; border-top: 1px solid rgba(255,255,255,.06);
    }

    @media (max-width: 768px) {
        .desktop-table-wrap { display: none; }
        .mobile-internship-cards { display: block; }
    }
</style>
@endpush

<div class="page-header">
    <h1><i class="fas fa-briefcase" style="color:#e94560;margin-right:.5rem"></i>My Internships</h1>
    <a href="{{ route('recruiter.internships.create') }}" class="btn-primary">
        <i class="fas fa-plus"></i>Create New
    </a>
</div>

@if($internships->isEmpty())
    <div class="desktop-table-wrap">
        <div class="empty-state">
            <i class="fas fa-briefcase"></i>
            <p style="margin-bottom:1rem">No internships yet. Create your first one!</p>
            <a href="{{ route('recruiter.internships.create') }}" class="btn-primary">
                <i class="fas fa-plus"></i>Create Internship
            </a>
        </div>
    </div>
@else
    {{-- Desktop Table --}}
    <div class="desktop-table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Organization</th>
                    <th>Location</th>
                    <th>Applications</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($internships as $internship)
                <tr>
                    <td><strong>{{ $internship->title }}</strong></td>
                    <td>{{ $internship->organization }}</td>
                    <td>{{ $internship->location }}</td>
                    <td>{{ $internship->applications_count ?? 0 }}</td>
                    <td>
                        <span class="badge {{ $internship->is_active ? 'badge-active' : 'badge-inactive' }}">
                            {{ $internship->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td class="actions-cell">
                        <a href="{{ route('recruiter.internships.edit', $internship) }}" class="btn-outline">
                            <i class="fas fa-edit"></i>Edit
                        </a>
                        <form method="POST" action="{{ route('recruiter.internships.destroy', $internship) }}"
                              onsubmit="return confirm('Delete this internship? All related applications will also be removed.')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn-outline btn-danger">
                                <i class="fas fa-trash"></i>Delete
                            </button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Mobile Cards --}}
    <div class="mobile-internship-cards">
        @foreach($internships as $internship)
        <div class="internship-card">
            <div class="internship-card-header">
                <div style="flex:1;min-width:0">
                    <div class="internship-card-title">{{ $internship->title }}</div>
                    <div class="internship-card-org">{{ $internship->organization }}</div>
                </div>
                <span class="badge {{ $internship->is_active ? 'badge-active' : 'badge-inactive' }}" style="flex-shrink:0">
                    {{ $internship->is_active ? 'Active' : 'Inactive' }}
                </span>
            </div>
            <div class="internship-card-meta">
                <span><i class="fas fa-map-marker-alt"></i>{{ $internship->location }}</span>
                <span><i class="fas fa-users"></i>{{ $internship->applications_count ?? 0 }} applicants</span>
            </div>
            <div class="internship-card-actions">
                <a href="{{ route('recruiter.internships.edit', $internship) }}" class="btn-outline" style="flex:1;justify-content:center">
                    <i class="fas fa-edit"></i>Edit
                </a>
                <form method="POST" action="{{ route('recruiter.internships.destroy', $internship) }}"
                      onsubmit="return confirm('Delete this internship?')" style="flex:1">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn-outline btn-danger" style="width:100%;justify-content:center">
                        <i class="fas fa-trash"></i>Delete
                    </button>
                </form>
            </div>
        </div>
        @endforeach
    </div>
@endif
@endsection
