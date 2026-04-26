@extends('recruiter.layouts.app')

@section('content')
@push('styles')
<style>
    /* ── Page Header ── */
    .page-header {
        display: flex; align-items: center; gap: .85rem; margin-bottom: 1.75rem;
        flex-wrap: wrap;
    }
    .page-header h1 { color: #fff; font-size: clamp(1.2rem, 4vw, 1.6rem); font-weight: 700; }
    .back-btn {
        background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.12);
        color: rgba(255,255,255,.8); padding: .45rem .9rem; border-radius: 8px;
        text-decoration: none; font-size: .83rem; transition: all .2s;
        display: inline-flex; align-items: center; gap: .4rem; flex-shrink: 0;
    }
    .back-btn:hover { background: rgba(255,255,255,.15); color: #fff; }

    /* ── Info Card ── */
    .info-card {
        background: rgba(255,255,255,.07); border: 1px solid rgba(255,255,255,.1);
        border-radius: 14px; padding: 1.1rem 1.35rem; margin-bottom: 2rem;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
        gap: 1rem 1.5rem;
    }
    .info-item label {
        color: rgba(255,255,255,.4); font-size: .72rem; font-weight: 600;
        text-transform: uppercase; letter-spacing: .05em;
        display: block; margin-bottom: .25rem;
    }
    .info-item p { color: #fff; font-size: .9rem; font-weight: 500; }

    /* ── Status badges ── */
    .status-badge {
        padding: .22rem .6rem; border-radius: 20px; font-size: .72rem; font-weight: 600;
        display: inline-block;
    }
    .status-pending             { background: rgba(255,193,7,.18);   color: #f2c94c; }
    .status-under_review        { background: rgba(0,123,255,.18);   color: #4da3ff; }
    .status-shortlisted         { background: rgba(111,66,193,.18);  color: #b39ddb; }
    .status-interview_scheduled { background: rgba(23,162,184,.18);  color: #56ccf2; }
    .status-approved            { background: rgba(40,167,69,.18);   color: #6fcf97; }
    .status-rejected            { background: rgba(220,53,69,.18);   color: #eb5757; }

    /* ── Timeline ── */
    .timeline { position: relative; padding-left: 2rem; }
    .timeline::before {
        content: ''; position: absolute; left: .6rem; top: 0; bottom: 0;
        width: 2px; background: rgba(255,255,255,.08);
    }
    .timeline-item { position: relative; margin-bottom: 1.5rem; }
    .timeline-dot {
        position: absolute; left: -1.65rem; top: .35rem;
        width: 14px; height: 14px; border-radius: 50%;
        background: #e94560; border: 2px solid #16213e;
    }
    .timeline-card {
        background: rgba(255,255,255,.07); border: 1px solid rgba(255,255,255,.08);
        border-radius: 12px; padding: 1rem 1.15rem;
        transition: border-color .2s;
    }
    .timeline-card:hover { border-color: rgba(255,255,255,.15); }
    .timeline-header {
        display: flex; justify-content: space-between; align-items: center;
        margin-bottom: .5rem; gap: .5rem; flex-wrap: wrap;
    }
    .status-change { display: flex; align-items: center; gap: .45rem; font-size: .875rem; flex-wrap: wrap; }
    .status-from { color: rgba(255,255,255,.45); }
    .status-arrow { color: rgba(255,255,255,.25); }
    .timeline-meta { color: rgba(255,255,255,.35); font-size: .75rem; flex-shrink: 0; }
    .changed-by { color: rgba(255,255,255,.55); font-size: .8rem; margin-top: .35rem; }

    /* ── Empty ── */
    .empty-state { text-align: center; padding: 3rem 1.5rem; color: rgba(255,255,255,.4); }
    .empty-state i { font-size: 2.2rem; margin-bottom: .75rem; display: block; opacity: .35; }
</style>
@endpush

<div class="page-header">
    <a href="{{ route('recruiter.applications.index') }}" class="back-btn">
        <i class="fas fa-arrow-left"></i>Back
    </a>
    <h1><i class="fas fa-history" style="color:#e94560;margin-right:.5rem"></i>Status History</h1>
</div>

<div class="info-card">
    <div class="info-item">
        <label>Student</label>
        <p>{{ $application->user->name }}</p>
    </div>
    <div class="info-item">
        <label>Email</label>
        <p style="word-break:break-all">{{ $application->user->email }}</p>
    </div>
    <div class="info-item">
        <label>Internship</label>
        <p>{{ $application->internship->title }}</p>
    </div>
    <div class="info-item">
        <label>Current Status</label>
        <p>
            <span class="status-badge status-{{ $application->status->value }}">
                {{ $application->status->label() }}
            </span>
        </p>
    </div>
    <div class="info-item">
        <label>Applied On</label>
        <p>{{ $application->created_at->format('M d, Y') }}</p>
    </div>
</div>

@if($logs->isEmpty())
    <div class="empty-state">
        <i class="fas fa-history"></i>
        <p>No status changes recorded yet.</p>
    </div>
@else
    <div class="timeline">
        @foreach($logs as $log)
        <div class="timeline-item">
            <div class="timeline-dot"></div>
            <div class="timeline-card">
                <div class="timeline-header">
                    <div class="status-change">
                        @if($log->from_status)
                            <span class="status-badge status-{{ $log->from_status }}">
                                {{ \App\Enums\ApplicationStatus::from($log->from_status)->label() }}
                            </span>
                            <span class="status-arrow"><i class="fas fa-arrow-right"></i></span>
                        @else
                            <span class="status-from" style="font-size:.8rem">Initial</span>
                            <span class="status-arrow"><i class="fas fa-arrow-right"></i></span>
                        @endif
                        <span class="status-badge status-{{ $log->to_status }}">
                            {{ \App\Enums\ApplicationStatus::from($log->to_status)->label() }}
                        </span>
                    </div>
                    <span class="timeline-meta">{{ $log->created_at->format('M d, Y H:i') }}</span>
                </div>
                <div class="changed-by">
                    <i class="fas fa-user" style="margin-right:.3rem"></i>
                    Changed by: {{ $log->changedBy?->name ?? 'System' }}
                    @if($log->actor_type)
                        <span style="color:rgba(255,255,255,.25)">({{ $log->actor_type }})</span>
                    @endif
                </div>
                @if($log->notes)
                    <p style="color:rgba(255,255,255,.55);font-size:.83rem;margin-top:.4rem">{{ $log->notes }}</p>
                @endif
            </div>
        </div>
        @endforeach
    </div>
@endif
@endsection
