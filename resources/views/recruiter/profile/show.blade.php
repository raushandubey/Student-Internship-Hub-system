@extends('recruiter.layouts.app')

@section('content')
@push('styles')
<style>
    /* ── Page Header ── */
    .profile-page-header {
        display: flex; justify-content: space-between; align-items: center;
        margin-bottom: 1.75rem; flex-wrap: wrap; gap: .75rem;
    }
    .profile-page-header h1 { color: #fff; font-size: clamp(1.3rem, 4vw, 1.8rem); font-weight: 700; }

    /* ── Profile Card ── */
    .profile-card {
        background: rgba(255,255,255,.07); border: 1px solid rgba(255,255,255,.1);
        border-radius: 20px; padding: clamp(1.5rem, 4vw, 2.5rem);
        max-width: 700px; width: 100%;
    }
    .profile-header {
        display: flex; align-items: center; gap: 1.25rem; margin-bottom: 2rem;
        flex-wrap: wrap;
    }
    .logo-wrap {
        width: 84px; height: 84px; border-radius: 16px;
        background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.12);
        display: flex; align-items: center; justify-content: center; overflow: hidden; flex-shrink: 0;
    }
    .logo-wrap img { width: 100%; height: 100%; object-fit: cover; }
    .logo-wrap i { font-size: 2.3rem; color: rgba(255,255,255,.25); }
    .profile-name { color: #fff; font-size: 1.4rem; font-weight: 700; }
    .profile-org  { color: rgba(255,255,255,.55); font-size: .9rem; margin-top: .2rem; }

    /* ── Fields ── */
    .field-row { margin-bottom: 1.25rem; padding-bottom: 1.25rem; border-bottom: 1px solid rgba(255,255,255,.06); }
    .field-row:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
    .field-row label {
        color: rgba(255,255,255,.45); font-size: .75rem; font-weight: 600;
        display: block; margin-bottom: .35rem; text-transform: uppercase; letter-spacing: .05em;
    }
    .field-row p { color: rgba(255,255,255,.9); font-size: .9rem; }
    .field-row a { color: #e94560; text-decoration: none; }
    .field-row a:hover { text-decoration: underline; }

    /* ── Button ── */
    .btn-edit {
        background: linear-gradient(135deg, #e94560, #c62a47);
        color: #fff; border: none; padding: .6rem 1.3rem;
        border-radius: 10px; font-weight: 600; font-size: .875rem;
        text-decoration: none; display: inline-flex; align-items: center; gap: .4rem;
        transition: all .2s; white-space: nowrap;
    }
    .btn-edit:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(233,69,96,.4); color: #fff; }

    @media (max-width: 480px) {
        .logo-wrap { width: 68px; height: 68px; }
        .profile-name { font-size: 1.2rem; }
    }
</style>
@endpush

<div class="profile-page-header">
    <h1><i class="fas fa-user-circle" style="color:#e94560;margin-right:.5rem"></i>My Profile</h1>
    <a href="{{ route('recruiter.profile.edit') }}" class="btn-edit">
        <i class="fas fa-edit"></i>Edit Profile
    </a>
</div>

<div class="profile-card">
    <div class="profile-header">
        <div class="logo-wrap">
            @if($profile?->logo_path)
                <img src="{{ $profile->logo_url }}" alt="Organization Logo">
            @else
                <i class="fas fa-building"></i>
            @endif
        </div>
        <div>
            <div class="profile-name">{{ auth()->user()->name }}</div>
            <div class="profile-org">{{ $profile?->organization ?? 'No organization set' }}</div>
        </div>
    </div>

    <div class="field-row">
        <label>Email</label>
        <p>{{ auth()->user()->email }}</p>
    </div>

    <div class="field-row">
        <label>Organization</label>
        <p>{{ $profile?->organization ?? '—' }}</p>
    </div>

    <div class="field-row">
        <label>Description</label>
        <p style="white-space:pre-wrap">{{ $profile?->description ?? '—' }}</p>
    </div>

    <div class="field-row">
        <label>Website</label>
        @if($profile?->website)
            <p><a href="{{ $profile->website }}" target="_blank" rel="noopener noreferrer">
                <i class="fas fa-external-link-alt" style="margin-right:.3rem;font-size:.75rem"></i>{{ $profile->website }}
            </a></p>
        @else
            <p>—</p>
        @endif
    </div>
</div>
@endsection
