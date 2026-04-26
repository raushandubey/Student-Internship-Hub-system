@extends('recruiter.layouts.app')

@section('content')
@push('styles')
<style>
    /* ── Page Header ── */
    .page-header { margin-bottom: 1.75rem; }
    .page-header h1 { color: #fff; font-size: clamp(1.3rem, 4vw, 1.8rem); font-weight: 700; }

    /* ── Cards ── */
    .form-card {
        background: rgba(255,255,255,.07); border: 1px solid rgba(255,255,255,.1);
        border-radius: 16px; padding: clamp(1.25rem, 4vw, 2rem);
        max-width: 700px; width: 100%; margin-bottom: 1.25rem;
    }

    /* ── Logo Section ── */
    .logo-section-header {
        display: flex; align-items: center; gap: .5rem;
        color: rgba(255,255,255,.7); font-size: .875rem; font-weight: 600;
        margin-bottom: 1.1rem;
    }
    .logo-section-header i { color: #e94560; }
    .logo-preview {
        width: 80px; height: 80px; border-radius: 12px;
        background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.12);
        display: flex; align-items: center; justify-content: center;
        overflow: hidden; margin-bottom: .85rem;
    }
    .logo-preview img { width: 100%; height: 100%; object-fit: cover; }
    .logo-preview i { font-size: 2rem; color: rgba(255,255,255,.25); }
    .file-hint { color: rgba(255,255,255,.35); font-size: .75rem; margin-top: .3rem; }

    /* ── Form Elements ── */
    .form-group { margin-bottom: 1.35rem; }
    .form-label {
        display: block; color: rgba(255,255,255,.8);
        font-size: .875rem; font-weight: 500; margin-bottom: .45rem;
    }
    .form-control {
        width: 100%; background: rgba(255,255,255,.08);
        border: 1px solid rgba(255,255,255,.15); border-radius: 10px;
        color: #fff; padding: .75rem 1rem; font-size: .925rem;
        font-family: 'Inter', sans-serif;
        transition: border-color .2s, background .2s;
    }
    .form-control:focus { outline: none; border-color: #e94560; background: rgba(255,255,255,.1); }
    .form-control::placeholder { color: rgba(255,255,255,.3); }
    textarea.form-control { resize: vertical; min-height: 100px; }
    .error-msg { color: #eb5757; font-size: .78rem; margin-top: .3rem; display: flex; align-items: center; gap: .3rem; }

    /* ── Buttons ── */
    .form-actions {
        display: flex; flex-wrap: wrap; gap: .75rem; margin-top: 1.75rem;
    }
    .btn-primary {
        background: linear-gradient(135deg, #e94560, #c62a47);
        color: #fff; border: none; padding: .75rem 1.75rem;
        border-radius: 10px; font-weight: 600; font-size: .925rem;
        cursor: pointer; transition: all .2s; display: inline-flex; align-items: center; gap: .4rem;
    }
    .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(233,69,96,.4); }
    .btn-secondary {
        background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.15);
        color: rgba(255,255,255,.8); padding: .75rem 1.75rem;
        border-radius: 10px; font-weight: 600; text-decoration: none;
        transition: all .2s; display: inline-flex; align-items: center; gap: .4rem;
    }
    .btn-secondary:hover { background: rgba(255,255,255,.12); color: #fff; }

    .btn-upload {
        background: rgba(233,69,96,.12); border: 1px solid rgba(233,69,96,.25);
        color: #e94560; padding: .5rem 1.1rem; border-radius: 9px;
        font-size: .85rem; font-weight: 600; cursor: pointer; transition: all .2s;
        display: inline-flex; align-items: center; gap: .4rem;
    }
    .btn-upload:hover { background: rgba(233,69,96,.22); }

    @media (max-width: 480px) {
        .form-actions { flex-direction: column; }
        .btn-primary, .btn-secondary { width: 100%; justify-content: center; }
    }
</style>
@endpush

<div class="page-header">
    <h1><i class="fas fa-edit" style="color:#e94560;margin-right:.5rem"></i>Edit Profile</h1>
</div>

{{-- Logo upload (separate form) --}}
<div class="form-card">
    <div class="logo-section-header">
        <i class="fas fa-image"></i>Organization Logo
    </div>
    <div class="logo-preview">
        @if($profile?->logo_path)
            <img src="{{ $profile->logo_url }}" alt="Logo">
        @else
            <i class="fas fa-building"></i>
        @endif
    </div>
    <form method="POST" action="{{ route('recruiter.profile.logo') }}" enctype="multipart/form-data">
        @csrf
        <div class="form-group" style="margin-bottom:.6rem">
            <input type="file" name="logo" class="form-control" accept="image/*">
            <div class="file-hint">
                <i class="fas fa-info-circle" style="margin-right:.3rem"></i>Max 2MB. JPEG, PNG, GIF, SVG accepted.
            </div>
            @error('logo')<div class="error-msg"><i class="fas fa-times-circle"></i>{{ $message }}</div>@enderror
        </div>
        <button type="submit" class="btn-upload">
            <i class="fas fa-upload"></i>Upload Logo
        </button>
    </form>
</div>

{{-- Profile details form --}}
<div class="form-card">
    <form method="POST" action="{{ route('recruiter.profile.update') }}">
        @csrf @method('PUT')

        <div class="form-group">
            <label class="form-label" for="organization">
                Organization <span style="color:#e94560">*</span>
            </label>
            <input type="text" id="organization" name="organization" class="form-control"
                   value="{{ old('organization', $profile?->organization) }}"
                   placeholder="Your company or organization name"
                   required>
            @error('organization')<div class="error-msg"><i class="fas fa-times-circle"></i>{{ $message }}</div>@enderror
        </div>

        <div class="form-group">
            <label class="form-label" for="description">Description</label>
            <textarea id="description" name="description" class="form-control"
                      placeholder="Tell students about your company...">{{ old('description', $profile?->description) }}</textarea>
            @error('description')<div class="error-msg"><i class="fas fa-times-circle"></i>{{ $message }}</div>@enderror
        </div>

        <div class="form-group">
            <label class="form-label" for="website">Website</label>
            <input type="url" id="website" name="website" class="form-control"
                   value="{{ old('website', $profile?->website) }}"
                   placeholder="https://yourcompany.com">
            @error('website')<div class="error-msg"><i class="fas fa-times-circle"></i>{{ $message }}</div>@enderror
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-primary">
                <i class="fas fa-save"></i>Save Changes
            </button>
            <a href="{{ route('recruiter.profile.show') }}" class="btn-secondary">
                <i class="fas fa-times"></i>Cancel
            </a>
        </div>
    </form>
</div>
@endsection
