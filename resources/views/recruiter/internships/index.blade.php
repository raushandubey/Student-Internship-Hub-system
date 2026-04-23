@extends('recruiter.layouts.app')

@section('content')
<style>
    .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
    .page-header h1 { color: #fff; font-size: 1.8rem; font-weight: 700; }
    .btn-primary {
        background: linear-gradient(135deg, #e94560, #c62a47);
        color: #fff; border: none; padding: .65rem 1.4rem;
        border-radius: 10px; font-weight: 600; font-size: .9rem;
        text-decoration: none; cursor: pointer; transition: all .2s;
        display: inline-flex; align-items: center; gap: .4rem;
    }
    .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(233,69,96,.4); color: #fff; }
    .btn-sm { padding: .4rem .9rem; font-size: .8rem; }
    .btn-outline {
        background: transparent; border: 1px solid rgba(255,255,255,.2);
        color: rgba(255,255,255,.8); padding: .4rem .9rem;
        border-radius: 8px; font-size: .8rem; font-weight: 500;
        text-decoration: none; cursor: pointer; transition: all .2s;
        display: inline-flex; align-items: center; gap: .3rem;
    }
    .btn-outline:hover { background: rgba(255,255,255,.1); color: #fff; }
    .btn-danger { border-color: rgba(220,53,69,.4); color: #eb5757; }
    .btn-danger:hover { background: rgba(220,53,69,.15); }

    .card {
        background: rgba(255,255,255,.07);
        border: 1px solid rgba(255,255,255,.1);
        border-radius: 16px; overflow: hidden;
    }
    table { width: 100%; border-collapse: collapse; }
    thead th {
        background: rgba(255,255,255,.05);
        color: rgba(255,255,255,.6); font-size: .8rem;
        font-weight: 600; text-transform: uppercase; letter-spacing: .05em;
        padding: 1rem 1.2rem; text-align: left;
    }
    tbody tr { border-top: 1px solid rgba(255,255,255,.06); transition: background .15s; }
    tbody tr:hover { background: rgba(255,255,255,.04); }
    tbody td { padding: 1rem 1.2rem; color: rgba(255,255,255,.85); font-size: .9rem; }
    .badge {
        padding: .3rem .75rem; border-radius: 20px; font-size: .75rem; font-weight: 600;
    }
    .badge-active   { background: rgba(40,167,69,.2);  color: #6fcf97; }
    .badge-inactive { background: rgba(108,117,125,.2); color: #adb5bd; }
    .empty-state { text-align: center; padding: 4rem 2rem; color: rgba(255,255,255,.5); }
    .empty-state i { font-size: 3rem; margin-bottom: 1rem; display: block; }
</style>

<div class="page-header">
    <h1><i class="fas fa-briefcase me-2"></i>My Internships</h1>
    <a href="{{ route('recruiter.internships.create') }}" class="btn-primary">
        <i class="fas fa-plus"></i> Create New
    </a>
</div>

<div class="card">
    @if($internships->isEmpty())
        <div class="empty-state">
            <i class="fas fa-briefcase"></i>
            <p>No internships yet. Create your first one!</p>
            <a href="{{ route('recruiter.internships.create') }}" class="btn-primary" style="margin-top:1rem">
                <i class="fas fa-plus"></i> Create Internship
            </a>
        </div>
    @else
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
                    <td style="display:flex;gap:.5rem;flex-wrap:wrap">
                        <a href="{{ route('recruiter.internships.edit', $internship) }}" class="btn-outline">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <form method="POST" action="{{ route('recruiter.internships.destroy', $internship) }}"
                              onsubmit="return confirm('Delete this internship? All related applications will also be removed.')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn-outline btn-danger">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
