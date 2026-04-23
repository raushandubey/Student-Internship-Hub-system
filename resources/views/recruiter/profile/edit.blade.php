@extends('recruiter.layouts.app')

@section('content')
<style>
    .page-header { margin-bottom: 2rem; }
    .page-header h1 { color: #fff; font-size: 1.8rem; font-weight: 700; }
    .form-card {
        background: rgba(255,255,255,.07); border: 1px solid rgba(255,255,255,.1);
        border-radius: 16px; padding: 2rem; max-width: 700px;
    }
    .form-group { margin-bottom: 1.4rem; }
    label { display: block; color: rgba(255,255,255,.8); font-size: .9rem; font-weight: 500; margin-bottom: .5rem; }
    .form-control {
        width: 100%; background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.15);
        border-radius: 10px; color: #fff; padding: .75rem 1rem; font-size: .95rem; transition: border-color .2s;
    }
    .form-control:focus { outline: none; border-color: #e94560; }
    .form-control::placeholder { color: rgba(255,255,255,.3); }
    textarea.form-control { resize: vertical; min-height: 100px; }
    .error-msg { color: #eb5757; font-size: .8rem; margin-top: .3rem; }
    .btn-primary {
        background: linear-gradient(135deg, #e94560, #c62a47);
        color: #fff; border: none; padding: .75rem 2rem;
        border-radius: 10px; font-weight: 600; cursor: pointer; transition: all .2s;
    }
    .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(233,69,96,.4); }
    .btn-secondary {
        background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.15);
        color: rgba(255,255,255,.8); padding: .75rem 2rem;
        border-radius: 10px; font-weight: 600; text-decoration: none; transition: all .2s;
    }
    .btn-secondary:hover { background: rgba(255,255,255,.12); color: #fff; }
    .form-actions { display: flex; gap: 1rem; margin-top: 1.5rem; }
    .logo-section {
        background: rgba(255,255,255,.04); border: 1px solid rgba(255,255,255,.08);
        border-radius: 12px; padding: 1.2rem; margin-bottom: 1.5rem;
    }
    .logo-section h3 { color: rgba(255,255,255,.7); font-size: .9rem; font-weight: 600; margin-bottom: 1rem; }
    .logo-preview {
        width: 80px; height: 80px; border-radius: 12px;
        background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.15);
        display: flex; align-items: center; justify-content: center; overflow: hidden; margin-bottom: .75rem;
    }
    .logo-preview img { width: 100%; height: 100%; object-fit: cover; }
    .logo-preview i { font-size: 2rem; color: rgba(255,255,255,.3); }
    .file-hint { color: rgba(255,255,255,.4); font-size: .78rem; margin-top: .3rem; }
</style>

<div class="page-header">
    <h1><i class="fas fa-edit me-2"></i>Edit Profile</h1>
</div>

{{-- Logo upload (separate form) --}}
<div class="form-card" style="margin-bottom:1.5rem">
    <div class="logo-section">
        <h3><i class="fas fa-image me-2"></i>Organization Logo</h3>
        <div class="logo-preview">
            @if($profile?->logo_path)
                <img src="{{ $profile->logo_url }}" alt="Logo">
            @else
                <i class="fas fa-building"></i>
            @endif
        </div>
        <form method="POST" action="{{ route('recruiter.profile.logo') }}" enctype="multipart/form-data">
            @csrf
            <div class="form-group" style="margin-bottom:.5rem">
                <input type="file" name="logo" class="form-control" accept="image/*">
                <div class="file-hint">Max 2MB. JPEG, PNG, GIF, SVG accepted.</div>
                @error('logo')<div class="error-msg">{{ $message }}</div>@enderror
            </div>
            <button type="submit" class="btn-primary" style="padding:.5rem 1.2rem;font-size:.85rem">
                <i class="fas fa-upload me-1"></i>Upload Logo
            </button>
        </form>
    </div>
</div>

{{-- Profile details form --}}
<div class="form-card">
    <form method="POST" action="{{ route('recruiter.profile.update') }}">
        @csrf @method('PUT')

        <div class="form-group">
            <label for="organization">Organization *</label>
            <input type="text" id="organization" name="organization" class="form-control"
                   value="{{ old('organization', $profile?->organization) }}" required>
            @error('organization')<div class="error-msg">{{ $message }}</div>@enderror
        </div>

        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" class="form-control"
                      placeholder="Tell students about your company...">{{ old('description', $profile?->description) }}</textarea>
            @error('description')<div class="error-msg">{{ $message }}</div>@enderror
        </div>

        <div class="form-group">
            <label for="website">Website</label>
            <input type="url" id="website" name="website" class="form-control"
                   value="{{ old('website', $profile?->website) }}" placeholder="https://yourcompany.com">
            @error('website')<div class="error-msg">{{ $message }}</div>@enderror
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-primary"><i class="fas fa-save me-2"></i>Save Changes</button>
            <a href="{{ route('recruiter.profile.show') }}" class="btn-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection
