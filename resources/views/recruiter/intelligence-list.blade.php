@extends('recruiter.layouts.app')
@section('content')
@push('styles')
<style>
.page-header{margin-bottom:1.5rem}
.page-header h1{color:#fff;font-size:clamp(1.3rem,4vw,1.8rem);font-weight:700}
.page-header p{color:rgba(255,255,255,.55);margin-top:.3rem;font-size:.875rem}
.intel-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:1rem}
.intel-card{background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.1);border-radius:16px;padding:1.25rem;transition:transform .2s,box-shadow .2s}
.intel-card:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(0,0,0,.25)}
.intel-card-title{color:#fff;font-size:.95rem;font-weight:700;margin-bottom:.3rem}
.intel-card-meta{color:rgba(255,255,255,.45);font-size:.78rem;margin-bottom:1rem}
.intel-card-stat{display:flex;justify-content:space-between;align-items:center;padding:.4rem 0;border-bottom:1px solid rgba(255,255,255,.06)}
.intel-card-stat:last-child{border-bottom:none}
.intel-card-stat span:first-child{color:rgba(255,255,255,.55);font-size:.8rem}
.intel-card-stat span:last-child{color:#fff;font-weight:600;font-size:.85rem}
.btn-intel{display:inline-flex;align-items:center;gap:.4rem;background:rgba(233,69,96,.12);border:1px solid rgba(233,69,96,.25);color:#e94560;padding:.45rem 1rem;border-radius:8px;font-size:.82rem;font-weight:600;text-decoration:none;transition:all .2s;margin-top:1rem}
.btn-intel:hover{background:rgba(233,69,96,.25);color:#fff}
.empty-state{text-align:center;padding:3rem;color:rgba(255,255,255,.4)}
.empty-state i{font-size:2.5rem;display:block;margin-bottom:.75rem;opacity:.3}
</style>
@endpush

<div class="page-header">
    <h1><i class="fas fa-brain" style="color:#e94560;margin-right:.5rem"></i>Intelligence Center</h1>
    <p>Select an internship to view AI-powered candidate rankings and trust indicators</p>
</div>

@if($internships->isEmpty())
<div class="empty-state">
    <i class="fas fa-briefcase"></i>
    <p>No internships posted yet.</p>
    <a href="{{ route('recruiter.internships.create') }}" style="color:#e94560;text-decoration:none;margin-top:.5rem;display:inline-block">Post your first internship →</a>
</div>
@else
<div class="intel-grid">
    @foreach($internships as $internship)
    <div class="intel-card">
        <div class="intel-card-title">{{ $internship->title }}</div>
        <div class="intel-card-meta">{{ $internship->organization }} &nbsp;·&nbsp; {{ $internship->location ?? 'Remote' }}</div>
        <div class="intel-card-stat">
            <span>Total Applicants</span>
            <span>{{ $internship->applications_count }}</span>
        </div>
        <div class="intel-card-stat">
            <span>Status</span>
            <span style="color:{{ $internship->is_active ? '#6fcf97' : '#eb5757' }}">
                {{ $internship->is_active ? 'Active' : 'Inactive' }}
            </span>
        </div>
        @if($internship->applications_count > 0)
        <a href="{{ route('recruiter.intelligence.show', $internship) }}" class="btn-intel">
            <i class="fas fa-brain"></i> View Intelligence
        </a>
        @else
        <div style="color:rgba(255,255,255,.3);font-size:.8rem;margin-top:1rem">No applicants yet</div>
        @endif
    </div>
    @endforeach
</div>
@endif
@endsection
