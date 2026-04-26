@extends('recruiter.layouts.app')

@section('content')
@push('styles')
<style>
    /* ── Page Header ── */
    .page-header { margin-bottom: 1.75rem; }
    .page-header h1 { color: #fff; font-size: clamp(1.3rem, 4vw, 1.8rem); font-weight: 700; }

    /* ── Form Card ── */
    .form-card {
        background: rgba(255,255,255,.07);
        border: 1px solid rgba(255,255,255,.1);
        border-radius: 16px; padding: clamp(1.25rem, 4vw, 2rem);
        max-width: 800px; width: 100%;
    }

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
    textarea.form-control { resize: vertical; min-height: 120px; }
    .error-msg { color: #eb5757; font-size: .78rem; margin-top: .3rem; display: flex; align-items: center; gap: .3rem; }

    /* ── 2-column row ── */
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
    }

    /* ── Skills ── */
    .skills-input-wrap { position: relative; }
    .skills-hint { color: rgba(255,255,255,.4); font-size: .75rem; margin-top: .3rem; }
    .skills-tags { display: flex; flex-wrap: wrap; gap: .4rem; margin-top: .6rem; }
    .skill-tag {
        background: rgba(233,69,96,.12); border: 1px solid rgba(233,69,96,.25);
        color: #e94560; padding: .25rem .65rem; border-radius: 20px; font-size: .78rem;
        display: flex; align-items: center; gap: .3rem;
    }
    .skill-tag button {
        background: none; border: none; color: #e94560; cursor: pointer;
        font-size: .9rem; line-height: 1; padding: 0; display: flex; align-items: center;
        transition: color .15s;
    }
    .skill-tag button:hover { color: #c62a47; }

    /* ── Toggle ── */
    .toggle-wrap { display: flex; align-items: center; gap: .75rem; }
    .toggle {
        width: 44px; height: 24px; background: rgba(255,255,255,.15);
        border-radius: 12px; position: relative; cursor: pointer; transition: background .2s;
        flex-shrink: 0;
    }
    .toggle.on { background: #e94560; }
    .toggle::after {
        content: ''; position: absolute; top: 3px; left: 3px;
        width: 18px; height: 18px; background: #fff; border-radius: 50%;
        transition: left .2s;
    }
    .toggle.on::after { left: 23px; }
    .toggle-label { color: rgba(255,255,255,.8); font-size: .875rem; }

    /* ── Actions ── */
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
        border-radius: 10px; font-weight: 600; font-size: .925rem;
        text-decoration: none; cursor: pointer; transition: all .2s;
        display: inline-flex; align-items: center;
    }
    .btn-secondary:hover { background: rgba(255,255,255,.12); color: #fff; }

    /* ── Responsive ── */
    @media (max-width: 600px) {
        .form-row { grid-template-columns: 1fr; }
        .form-actions { flex-direction: column; }
        .btn-primary, .btn-secondary { width: 100%; justify-content: center; }
    }
</style>
@endpush

<div class="page-header">
    <h1>
        <i class="fas fa-{{ $internship ? 'edit' : 'plus-circle' }}" style="color:#e94560;margin-right:.5rem"></i>
        {{ $internship ? 'Edit Internship' : 'Create Internship' }}
    </h1>
</div>

<div class="form-card">
    <form method="POST"
          action="{{ $internship ? route('recruiter.internships.update', $internship) : route('recruiter.internships.store') }}"
          id="internshipForm">
        @csrf
        @if($internship) @method('PUT') @endif

        <div class="form-row">
            <div class="form-group">
                <label class="form-label" for="title">Job Title <span style="color:#e94560">*</span></label>
                <input type="text" id="title" name="title" class="form-control"
                       value="{{ old('title', $internship?->title) }}"
                       placeholder="e.g. Software Engineering Intern" required>
                @error('title')<div class="error-msg"><i class="fas fa-times-circle"></i>{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label class="form-label" for="organization">Organization <span style="color:#e94560">*</span></label>
                <input type="text" id="organization" name="organization" class="form-control"
                       value="{{ old('organization', $internship?->organization) }}"
                       placeholder="e.g. Acme Corp" required>
                @error('organization')<div class="error-msg"><i class="fas fa-times-circle"></i>{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label" for="location">Location <span style="color:#e94560">*</span></label>
                <input type="text" id="location" name="location" class="form-control"
                       value="{{ old('location', $internship?->location) }}"
                       placeholder="e.g. Remote / New York" required>
                @error('location')<div class="error-msg"><i class="fas fa-times-circle"></i>{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label class="form-label" for="duration">Duration <span style="color:#e94560">*</span></label>
                <input type="text" id="duration" name="duration" class="form-control"
                       value="{{ old('duration', $internship?->duration) }}"
                       placeholder="e.g. 3 months" required>
                @error('duration')<div class="error-msg"><i class="fas fa-times-circle"></i>{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Required Skills <span style="color:#e94560">*</span></label>
            <div class="skills-input-wrap">
                <input type="text" id="skillInput" class="form-control" placeholder="Type a skill and press Enter or comma">
            </div>
            <div class="skills-hint"><i class="fas fa-info-circle" style="margin-right:.3rem"></i>Press Enter or comma to add</div>
            <div class="skills-tags" id="skillsTags"></div>
            <div id="skillsHidden"></div>
            @error('required_skills')<div class="error-msg"><i class="fas fa-times-circle"></i>{{ $message }}</div>@enderror
        </div>

        <div class="form-group">
            <label class="form-label" for="description">Description</label>
            <textarea id="description" name="description" class="form-control"
                      placeholder="Describe the role, responsibilities, and requirements...">{{ old('description', $internship?->description) }}</textarea>
            @error('description')<div class="error-msg"><i class="fas fa-times-circle"></i>{{ $message }}</div>@enderror
        </div>

        @if($internship)
        <div class="form-group">
            <label class="form-label">Status</label>
            <div class="toggle-wrap">
                <div class="toggle {{ $internship->is_active ? 'on' : '' }}" id="statusToggle" role="switch"
                     aria-checked="{{ $internship->is_active ? 'true' : 'false' }}"
                     aria-label="Toggle active status"></div>
                <span class="toggle-label" id="statusLabel">
                    {{ $internship->is_active ? 'Active' : 'Inactive' }}
                </span>
                <input type="hidden" name="is_active" id="isActiveInput" value="{{ $internship->is_active ? '1' : '0' }}">
            </div>
        </div>
        @endif

        <div class="form-actions">
            <button type="submit" class="btn-primary">
                <i class="fas fa-save"></i>{{ $internship ? 'Update' : 'Create' }} Internship
            </button>
            <a href="{{ route('recruiter.internships.index') }}" class="btn-secondary">
                <i class="fas fa-times" style="margin-right:.4rem"></i>Cancel
            </a>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
// ── Skills tag input ─────────────────────────────────────────────
const skillInput  = document.getElementById('skillInput');
const skillsTags  = document.getElementById('skillsTags');
const skillsHidden = document.getElementById('skillsHidden');
let skills = @json(old('required_skills', $internship?->required_skills ?? []));

function renderSkills() {
    skillsTags.innerHTML = '';
    skillsHidden.innerHTML = '';
    skills.forEach((skill, i) => {
        const tag = document.createElement('span');
        tag.className = 'skill-tag';
        tag.innerHTML = `${skill} <button type="button" onclick="removeSkill(${i})" aria-label="Remove ${skill}">×</button>`;
        skillsTags.appendChild(tag);
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'required_skills[]';
        input.value = skill;
        skillsHidden.appendChild(input);
    });
}

function removeSkill(i) { skills.splice(i, 1); renderSkills(); }

skillInput.addEventListener('keydown', function(e) {
    if (e.key === 'Enter' || e.key === ',') {
        e.preventDefault();
        const val = this.value.trim().replace(/,$/, '');
        if (val && !skills.includes(val)) { skills.push(val); renderSkills(); }
        this.value = '';
    }
});

renderSkills();

// ── Status toggle ────────────────────────────────────────────────
const toggle = document.getElementById('statusToggle');
const isActiveInput = document.getElementById('isActiveInput');
const statusLabel = document.getElementById('statusLabel');
if (toggle) {
    toggle.addEventListener('click', function() {
        const isOn = this.classList.toggle('on');
        this.setAttribute('aria-checked', isOn ? 'true' : 'false');
        isActiveInput.value = isOn ? '1' : '0';
        statusLabel.textContent = isOn ? 'Active' : 'Inactive';
    });
}
</script>
@endpush
