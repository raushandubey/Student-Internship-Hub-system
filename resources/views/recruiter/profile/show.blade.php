@extends('recruiter.layouts.app')

@section('content')
<style>
    .profile-card {
        background: rgba(255,255,255,.07); border: 1px solid rgba(255,255,255,.1);
        border-radius: 20px; padding: 2.5rem; max-width: 700px;
    }
    .profile-header { display: flex; align-items: center; gap: 1.5rem; margin-bottom: 2rem; }
    .logo-wrap {
        width: 90px; height: 90px; border-radius: 16px;
        background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.15);
        display: flex; align-items: center; justify-content: center; overflow: hidden; flex-shrink: 0;
    }
    .logo-wrap img { width: 100%; height: 100%; object-fit: cover; }
    .logo-wrap i { font-size: 2.5rem; color: rgba(255,255,255,.3); }
    .profile-name { color: #fff; font-size: 1.5rem; font-weight: 700; }
    .profile-org  { color: rgba(255,255,255,.6); font-size: 1rem; margin-top: .2rem; }
    .field-row { margin-bottom: 1.2rem; }
    .field-row label { color: rgba(255,255,255,.5); font-size: .8rem; font-weight: 500; display: block; margin-bottom: .3rem; }
    .field-row p { color: rgba(255,255,255,.9); font-size: .95rem; }
    .field-row a { color: #e94560; text-decoration: none; }
    .field-row a:hover { text-decoration: underline; }
    .btn-edit {
        background: linear-gradient(135deg, #e94560, #c62a47);
        color: #fff; border: none; padding: .65rem 1.5rem;
        border-radius: 10px; font-weight: 600; font-size: .9rem;
        text-decoration: none; display: inline-flex; align-items: center; gap: .4rem;
        transition: all .2s;
    }
    .btn-edit:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(233,69,96,.4); color: #fff; }
</style>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:2rem">
    <h1 style="color:#fff;font-size:1.8rem;font-weight:700"><i class="fas fa-user-circle me-2"></i>My Profile</h1>
    <a href="{{ route('recruiter.profile.edit') }}" class="btn-edit"><i class="fas fa-edit"></i> Edit Profile</a>
</div>

<div class="profile-card">
    <div class="profile-header">
        <div class="logo-wrap">
            @if($profile?->logo_path)
                <img src="{{ $profile->logo_url }}" alt="Logo">
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
        <p>{{ $profile?->description ?? '—' }}</p>
    </div>

    <div class="field-row">
        <label>Website</label>
        @if($profile?->website)
            <p><a href="{{ $profile->website }}" target="_blank">{{ $profile->website }}</a></p>
        @else
            <p>—</p>
        @endif
    </div>
</div>
@endsection
