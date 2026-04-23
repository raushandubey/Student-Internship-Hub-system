@extends('recruiter.layouts.app')

@section('content')
<style>
    .page-header { margin-bottom: 2rem; }
    .page-header h1 { color: #fff; font-size: 1.8rem; font-weight: 700; }
    .form-card {
        background: rgba(255,255,255,.07);
        border: 1px solid rgba(255,255,255,.1);
        border-radius: 16px; padding: 2rem; max-width: 800px;
    }
    .form-group { margin-bottom: 1.4rem; }
    label { display: block; color: rgba(255,255,255,.8); font-size: .9rem; font-weight: 500; margin-bottom: .5rem; }
    .form-control {
        width: 100%; background: rgba(255,255,255,.08);
        border: 1px solid rgba(255,255,255,.15); border-radius: 10px;
        color: #fff; padding: .75rem 1rem; font-size: .95rem;
        transition: border-color .2s;
    }
    .form-control:focus { outline: none; border-color: #e94560; background: rgba(255,255,255,.1); }
    .form-control::placeholder { color: rgba(255,255,255,.35); }
    textarea.form-control { resize: vertical; min-height: 120px; }
    .error-msg { color: #eb5757; font-size: .8rem; margin-top: .3rem; }
    .skills-input-wrap { position: relative; }
    .skills-tags { display: flex; flex-wrap: wrap; gap: .4rem; margin-top: .5rem; }
    .skill-tag {
        background: rgba(233,69,96,.15); border: 1px solid rgba(233,69,96,.3);
        color: #e94560; padding: .25rem .7rem; border-radius: 20px; font-size: .8rem;
        display: flex; align-items: center; gap: .3rem;
    }
    .skill-tag button { background: none; border: none; color: #e94560; cursor: pointer; font-size: .9rem; line-height: 1; }
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
    .btn-primary {
        background: linear-gradient(135deg, #e94560, #c62a47);
        color: #fff; border: none; padding: .75rem 2rem;
        border-radius: 10px; font-weight: 600; font-size: .95rem;
        cursor: pointer; transition: all .2s;
    }
    .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(233,69,96,.4); }
    .btn-secondary {
        background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.15);
        color: rgba(255,255,255,.8); padding: .75rem 2rem;
        border-radius: 10px; font-weight: 600; font-size: .95rem;
        text-decoration: none; cursor: pointer; transition: all .2s;
    }
    .btn-secondary:hover { background: rgba(255,255,255,.12); color: #fff; }
    .form-actions { display: flex; gap: 1rem; margin-top: 1.5rem; }
    .toggle-wrap { display: flex; align-items: center; gap: .75rem; }
    .toggle { width: 44px; height: 24px; background: rgba(255,255,255,.15); border-radius: 12px; position: relative; cursor: pointer; transition: background .2s; }
    .toggle.on { background: #e94560; }
    .toggle::after { content: ''; position: absolute; top: 3px; left: 3px; width: 18px; height: 18px; background: #fff; border-radius: 50%; transition: left .2s; }
    .toggle.on::after { left: 23px; }
    @media (max-width: 600px) { .form-row { grid-template-columns: 1fr; } }
</style>

<div class="page-header">
    <h1>
        <i class="fas fa-{{ $internship ? 'edit' : 'plus-circle' }} me-2"></i>
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
                <label for="title">Job Title *</label>
                <input type="text" id="title" name="title" class="form-control"
                       value="{{ old('title', $internship?->title) }}" placeholder="e.g. Software Engineering Intern" required>
                @error('title')<div class="error-msg">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label for="organization">Organization *</label>
                <input type="text" id="organization" name="organization" class="form-control"
                       value="{{ old('organization', $internship?->organization) }}" placeholder="e.g. Acme Corp" required>
                @error('organization')<div class="error-msg">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="location">Location *</label>
                <input type="text" id="location" name="location" class="form-control"
                       value="{{ old('location', $internship?->location) }}" placeholder="e.g. Remote / New York" required>
                @error('location')<div class="error-msg">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label for="duration">Duration *</label>
                <input type="text" id="duration" name="duration" class="form-control"
                       value="{{ old('duration', $internship?->duration) }}" placeholder="e.g. 3 months" required>
                @error('duration')<div class="error-msg">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="form-group">
            <label>Required Skills *</label>
            <div class="skills-input-wrap">
                <input type="text" id="skillInput" class="form-control" placeholder="Type a skill and press Enter">
            </div>
            <div class="skills-tags" id="skillsTags"></div>
            <div id="skillsHidden"></div>
            @error('required_skills')<div class="error-msg">{{ $message }}</div>@enderror
        </div>

        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" class="form-control"
                      placeholder="Describe the role, responsibilities, and requirements...">{{ old('description', $internship?->description) }}</textarea>
            @error('description')<div class="error-msg">{{ $message }}</div>@enderror
        </div>

        @if($internship)
        <div class="form-group">
            <label>Status</label>
            <div class="toggle-wrap">
                <div class="toggle {{ $internship->is_active ? 'on' : '' }}" id="statusToggle"></div>
                <span style="color:rgba(255,255,255,.8);font-size:.9rem" id="statusLabel">
                    {{ $internship->is_active ? 'Active' : 'Inactive' }}
                </span>
                <input type="hidden" name="is_active" id="isActiveInput" value="{{ $internship->is_active ? '1' : '0' }}">
            </div>
        </div>
        @endif

        <div class="form-actions">
            <button type="submit" class="btn-primary">
                <i class="fas fa-save me-2"></i>{{ $internship ? 'Update' : 'Create' }} Internship
            </button>
            <a href="{{ route('recruiter.internships.index') }}" class="btn-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
// Skills tag input
const skillInput = document.getElementById('skillInput');
const skillsTags = document.getElementById('skillsTags');
const skillsHidden = document.getElementById('skillsHidden');
let skills = @json(old('required_skills', $internship?->required_skills ?? []));

function renderSkills() {
    skillsTags.innerHTML = '';
    skillsHidden.innerHTML = '';
    skills.forEach((skill, i) => {
        const tag = document.createElement('span');
        tag.className = 'skill-tag';
        tag.innerHTML = `${skill} <button type="button" onclick="removeSkill(${i})">×</button>`;
        skillsTags.appendChild(tag);
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'required_skills[]';
        input.value = skill;
        skillsHidden.appendChild(input);
    });
}

function removeSkill(i) {
    skills.splice(i, 1);
    renderSkills();
}

skillInput.addEventListener('keydown', function(e) {
    if (e.key === 'Enter' || e.key === ',') {
        e.preventDefault();
        const val = this.value.trim();
        if (val && !skills.includes(val)) {
            skills.push(val);
            renderSkills();
        }
        this.value = '';
    }
});

renderSkills();

// Status toggle
const toggle = document.getElementById('statusToggle');
const isActiveInput = document.getElementById('isActiveInput');
const statusLabel = document.getElementById('statusLabel');
if (toggle) {
    toggle.addEventListener('click', function() {
        const isOn = this.classList.toggle('on');
        isActiveInput.value = isOn ? '1' : '0';
        statusLabel.textContent = isOn ? 'Active' : 'Inactive';
    });
}
</script>
@endpush
