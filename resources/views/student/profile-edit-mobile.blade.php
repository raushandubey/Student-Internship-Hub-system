@extends('layouts.app-mobile')

@section('content')
<div class="max-w-lg mx-auto px-4 py-6">

    {{-- Header --}}
    <div class="mb-5">
        <a href="{{ route('profile.show') }}"
           class="inline-flex items-center gap-2 text-sm text-gray-500 mb-3 hover:text-gray-700 transition-colors">
            <i class="fas fa-arrow-left text-xs"></i>
            Back to Profile
        </a>
        <h1 class="text-2xl font-bold text-gray-900">Edit Profile</h1>
        <p class="text-sm text-gray-500 mt-1">Complete all steps to maximize your job matches</p>
    </div>

    {{-- Progress Stepper --}}
    <div class="card mb-5">
        <div class="flex items-center justify-between mb-3">
            <span class="text-sm font-semibold text-gray-700">Profile Setup</span>
            <span class="text-sm font-semibold text-primary-600" id="progressText">Step 1 of 4</span>
        </div>

        {{-- Step circles + connector lines --}}
        <div class="flex items-center gap-0">
            <div class="step-item active" data-step="1" id="step-indicator-1">
                <div class="step-circle">1</div>
            </div>
            <div class="step-line flex-1" id="step-line-1"></div>
            <div class="step-item" data-step="2" id="step-indicator-2">
                <div class="step-circle">2</div>
            </div>
            <div class="step-line flex-1" id="step-line-2"></div>
            <div class="step-item" data-step="3" id="step-indicator-3">
                <div class="step-circle">3</div>
            </div>
            <div class="step-line flex-1" id="step-line-3"></div>
            <div class="step-item" data-step="4" id="step-indicator-4">
                <div class="step-circle">4</div>
            </div>
        </div>

        {{-- Step labels --}}
        <div class="flex justify-between mt-2 px-0">
            <span class="text-[10px] text-gray-500 text-center w-8">Info</span>
            <span class="text-[10px] text-gray-500 text-center w-8">Skills</span>
            <span class="text-[10px] text-gray-500 text-center w-8">Goals</span>
            <span class="text-[10px] text-gray-500 text-center w-8">Resume</span>
        </div>
    </div>

    {{-- Multi-Step Form --}}
    <form id="profile-edit-form"
          action="{{ route('profile.update') }}"
          method="POST"
          enctype="multipart/form-data"
          data-no-loading>
        @csrf
        @method('PUT')

        {{-- STEP 1: Basic Information --}}
        <div class="form-step active card card-lg" id="form-step-1">
            <div class="flex items-center gap-3 mb-5">
                <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-user text-blue-600"></i>
                </div>
                <div>
                    <h2 class="text-lg font-bold text-gray-900">Basic Information</h2>
                    <p class="text-xs text-gray-500">Your name, education and ID</p>
                </div>
            </div>

            <div class="space-y-4">

                {{-- Profile Photo --}}
                <div class="form-group">
                    <label class="form-label">Profile Photo <span style="font-weight:400;color:#6b7280;">(Optional)</span></label>
                    <div style="display:flex;align-items:center;gap:1rem;">
                        <div id="mPhotoWrap" style="width:72px;height:72px;border-radius:50%;overflow:hidden;border:2px solid #e5e7eb;flex-shrink:0;display:flex;align-items:center;justify-content:center;background:#f3f4f6;">
                            @if($profile && $profile->getPhotoUrl())
                                <img id="mPhotoPreview" src="{{ $profile->getPhotoUrl() }}" alt="Photo" style="width:100%;height:100%;object-fit:cover;">
                            @else
                                <span style="font-size:1.75rem;font-weight:700;color:#6366f1;">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                            @endif
                        </div>
                        <div style="flex:1;">
                            <label for="mobile_profile_photo" style="display:inline-flex;align-items:center;gap:0.5rem;padding:0.6rem 1rem;background:#eff6ff;border:1.5px dashed #6366f1;border-radius:10px;color:#6366f1;font-size:0.875rem;font-weight:600;cursor:pointer;width:100%;justify-content:center;">
                                <i class="fas fa-camera"></i> Change Photo
                            </label>
                            <input type="file" name="profile_photo" id="mobile_profile_photo" accept="image/jpeg,image/png,image/jpg,image/webp" style="display:none;">
                            <p style="color:#9ca3af;font-size:0.7rem;text-align:center;margin-top:0.3rem;">JPG, PNG, WEBP · Max 2MB</p>
                        </div>
                    </div>
                    @error('profile_photo')<span class="form-error">{{ $message }}</span>@enderror
                </div>

                {{-- Location --}}
                <div class="form-group">
                    <label class="form-label" for="location">Your City / Location</label>
                    <div style="position:relative;">
                        <i class="fas fa-map-marker-alt" style="position:absolute;left:0.875rem;top:50%;transform:translateY(-50%);color:#9ca3af;font-size:0.875rem;pointer-events:none;"></i>
                        <input type="text"
                               id="location"
                               name="location"
                               class="form-input"
                               style="padding-left:2.5rem;"
                               value="{{ old('location', $profile->location ?? '') }}"
                               placeholder="e.g., Mumbai, Delhi, Bangalore">
                    </div>
                    <span class="form-hint">Helps us find internships near you</span>
                    @error('location')<span class="form-error">{{ $message }}</span>@enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="name">Full Name <span class="text-red-500">*</span></label>
                    <input type="text"
                           id="name"
                           name="name"
                           class="form-input"
                           value="{{ old('name', $profile->name ?? auth()->user()->name) }}"
                           placeholder="Your full name"
                           required>
                    <span class="form-error hidden" id="name-error">Please enter your full name</span>
                </div>

                <div class="form-group">
                    <label class="form-label" for="academic_background">Academic Background <span class="text-red-500">*</span></label>
                    <textarea id="academic_background"
                              name="academic_background"
                              class="form-textarea"
                              rows="4"
                              placeholder="e.g., B.Tech in Computer Science, XYZ University, 2024"
                              required>{{ old('academic_background', $profile->academic_background ?? '') }}</textarea>
                    <span class="form-hint">Include degree, major, university, and graduation year</span>
                    <span class="form-error hidden" id="academic-error">Please enter your academic background</span>
                </div>

                <div class="form-group">
                    <label class="form-label" for="aadhaar_number">Aadhaar Number <span class="text-red-500">*</span></label>
                    <input type="text"
                           id="aadhaar_number"
                           name="aadhaar_number"
                           class="form-input"
                           value="{{ old('aadhaar_number', $profile->aadhaar_number ?? '') }}"
                           pattern="[0-9]{12}"
                           maxlength="12"
                           inputmode="numeric"
                           placeholder="12-digit Aadhaar number"
                           required>
                    <span class="form-hint">Used for identity verification only</span>
                    <span class="form-error hidden" id="aadhaar-error">Enter a valid 12-digit Aadhaar number</span>
                </div>
            </div>
        </div>

        {{-- STEP 2: Skills --}}
        <div class="form-step card card-lg" id="form-step-2">
            <div class="flex items-center gap-3 mb-5">
                <div class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-code text-green-600"></i>
                </div>
                <div>
                    <h2 class="text-lg font-bold text-gray-900">Your Skills</h2>
                    <p class="text-xs text-gray-500">Technical and soft skills</p>
                </div>
            </div>

            <div class="space-y-4">
                <div class="form-group">
                    <label class="form-label" for="skills">Skills <span class="text-red-500">*</span></label>
                    <textarea id="skills"
                              name="skills"
                              class="form-textarea"
                              rows="6"
                              placeholder="e.g., Python, JavaScript, React, Node.js, SQL, Git, Communication, Teamwork"
                              required>{{ old('skills', is_array($profile->skills ?? null) ? implode(', ', $profile->skills) : ($profile->skills ?? '')) }}</textarea>
                    <span class="form-hint">List skills separated by commas</span>
                    <span class="form-error hidden" id="skills-error">Please enter at least one skill</span>
                </div>

                <div class="p-4 bg-blue-50 border border-blue-100 rounded-xl">
                    <p class="text-sm font-semibold text-blue-700 mb-1">
                        <i class="fas fa-lightbulb mr-1"></i> Pro Tip
                    </p>
                    <p class="text-sm text-blue-600">
                        Include both technical skills (languages, tools) and soft skills (communication, leadership) for better matches.
                    </p>
                </div>
            </div>
        </div>

        {{-- STEP 3: Career Interests --}}
        <div class="form-step card card-lg" id="form-step-3">
            <div class="flex items-center gap-3 mb-5">
                <div class="w-10 h-10 bg-purple-100 rounded-xl flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-star text-purple-600"></i>
                </div>
                <div>
                    <h2 class="text-lg font-bold text-gray-900">Career Interests</h2>
                    <p class="text-xs text-gray-500">What roles excite you?</p>
                </div>
            </div>

            <div class="space-y-4">
                <div class="form-group">
                    <label class="form-label" for="career_interests">Career Interests <span class="text-red-500">*</span></label>
                    <textarea id="career_interests"
                              name="career_interests"
                              class="form-textarea"
                              rows="6"
                              placeholder="e.g., Software Development, Data Science, Machine Learning, Web Development"
                              required>{{ old('career_interests', is_array($profile->career_interests ?? null) ? implode(', ', $profile->career_interests) : ($profile->career_interests ?? '')) }}</textarea>
                    <span class="form-hint">What type of roles are you looking for?</span>
                    <span class="form-error hidden" id="interests-error">Please enter your career interests</span>
                </div>

                <div class="p-4 bg-green-50 border border-green-100 rounded-xl">
                    <p class="text-sm font-semibold text-green-700 mb-1">
                        <i class="fas fa-bullseye mr-1"></i> Better Matches
                    </p>
                    <p class="text-sm text-green-600">
                        The more specific you are about your interests, the better our AI can match you with relevant internships.
                    </p>
                </div>
            </div>
        </div>

        {{-- STEP 4: Resume Upload --}}
        <div class="form-step card card-lg" id="form-step-4">
            <div class="flex items-center gap-3 mb-5">
                <div class="w-10 h-10 bg-red-100 rounded-xl flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-file-pdf text-red-600"></i>
                </div>
                <div>
                    <h2 class="text-lg font-bold text-gray-900">Upload Resume</h2>
                    <p class="text-xs text-gray-500">PDF, DOC or DOCX (max 5MB)</p>
                </div>
            </div>

            <div class="space-y-4">
                {{-- Existing Resume --}}
                @if($profile && $profile->resume_path)
                    <div class="p-4 bg-gray-50 border border-gray-200 rounded-xl">
                        <p class="text-xs text-gray-500 mb-2 font-medium uppercase tracking-wide">Current Resume</p>
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-file-pdf text-red-600"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-gray-900 truncate">
                                    {{ basename($profile->resume_path) }}
                                </p>
                                <p class="text-xs text-gray-500">
                                    Updated {{ $profile->updated_at->diffForHumans() }}
                                </p>
                            </div>
                            <a href="{{ $profile->getResumeUrl() }}"
                               target="_blank"
                               class="btn btn-sm btn-secondary"
                               rel="noopener noreferrer">
                                <i class="fas fa-eye mr-1"></i> View
                            </a>
                        </div>
                    </div>
                @endif

                {{-- Upload Area --}}
                <div class="form-group">
                    <label class="form-label">
                        {{ $profile && $profile->resume_path ? 'Replace Resume' : 'Upload Resume' }}
                        @if(!($profile && $profile->resume_path))
                            <span class="text-red-500">*</span>
                        @endif
                    </label>

                    <input type="file"
                           name="resume"
                           id="resumeInput"
                           class="hidden"
                           accept=".pdf,.doc,.docx"
                           {{ !($profile && $profile->resume_path) ? 'required' : '' }}>

                    <button type="button"
                            id="resumeUploadBtn"
                            onclick="document.getElementById('resumeInput').click()"
                            class="w-full border-2 border-dashed border-gray-300 rounded-xl p-6 text-center hover:border-primary-500 hover:bg-primary-50 transition-all active:scale-98">
                        <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 mb-2 block"></i>
                        <p class="text-sm font-semibold text-gray-700">Tap to upload</p>
                        <p class="text-xs text-gray-500 mt-1">PDF, DOC or DOCX (Max 5MB)</p>
                    </button>

                    {{-- File preview --}}
                    <div id="filePreview" class="hidden mt-3 p-3 bg-green-50 border border-green-200 rounded-xl">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-check-circle text-green-600 flex-shrink-0"></i>
                            <span class="text-sm font-medium text-green-800 truncate" id="fileName"></span>
                            <button type="button"
                                    onclick="clearResume()"
                                    class="ml-auto text-gray-400 hover:text-red-500 transition-colors flex-shrink-0"
                                    aria-label="Remove file">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>

                    <span class="form-error hidden" id="resume-error">Please upload your resume</span>
                </div>

                <div class="p-4 bg-yellow-50 border border-yellow-100 rounded-xl">
                    <p class="text-sm font-semibold text-yellow-700 mb-1">
                        <i class="fas fa-exclamation-triangle mr-1"></i> Important
                    </p>
                    <p class="text-sm text-yellow-600">
                        Ensure your resume is up-to-date and highlights skills relevant to your target roles.
                    </p>
                </div>
            </div>
        </div>

        {{-- Navigation Buttons --}}
        <div class="flex gap-3 mt-5">
            <button type="button"
                    id="prevBtn"
                    class="btn btn-secondary flex-1"
                    disabled>
                <i class="fas fa-arrow-left mr-2 text-xs"></i>
                Back
            </button>
            <button type="button"
                    id="nextBtn"
                    class="btn btn-primary flex-1">
                Next
                <i class="fas fa-arrow-right ml-2 text-xs"></i>
            </button>
            <button type="submit"
                    id="submitBtn"
                    class="btn btn-primary flex-1 hidden">
                <i class="fas fa-check mr-2 text-xs"></i>
                Save Profile
            </button>
        </div>

    </form>

</div>

@push('scripts')
<script>
(function() {
    'use strict';

    // ---- State ----
    let currentStep = 1;
    const TOTAL_STEPS = 4;

    // ---- Element refs ----
    const prevBtn    = document.getElementById('prevBtn');
    const nextBtn    = document.getElementById('nextBtn');
    const submitBtn  = document.getElementById('submitBtn');
    const progressTx = document.getElementById('progressText');

    // Step validators
    const stepValidators = {
        1: function() {
            const name     = document.getElementById('name');
            const academic = document.getElementById('academic_background');
            const aadhaar  = document.getElementById('aadhaar_number');
            let valid = true;

            // Name
            if (!name.value.trim()) {
                showError('name-error');
                name.classList.add('border-red-400');
                valid = false;
            } else {
                hideError('name-error');
                name.classList.remove('border-red-400');
            }

            // Academic
            if (!academic.value.trim()) {
                showError('academic-error');
                academic.classList.add('border-red-400');
                valid = false;
            } else {
                hideError('academic-error');
                academic.classList.remove('border-red-400');
            }

            // Aadhaar
            if (!/^\d{12}$/.test(aadhaar.value.trim())) {
                showError('aadhaar-error');
                aadhaar.classList.add('border-red-400');
                valid = false;
            } else {
                hideError('aadhaar-error');
                aadhaar.classList.remove('border-red-400');
            }

            return valid;
        },
        2: function() {
            const skills = document.getElementById('skills');
            if (!skills.value.trim()) {
                showError('skills-error');
                skills.classList.add('border-red-400');
                return false;
            }
            hideError('skills-error');
            skills.classList.remove('border-red-400');
            return true;
        },
        3: function() {
            const interests = document.getElementById('career_interests');
            if (!interests.value.trim()) {
                showError('interests-error');
                interests.classList.add('border-red-400');
                return false;
            }
            hideError('interests-error');
            interests.classList.remove('border-red-400');
            return true;
        },
        4: function() {
            // Resume is optional if one already exists
            const resumeInput = document.getElementById('resumeInput');
            const isRequired  = resumeInput.hasAttribute('required');
            if (isRequired && !resumeInput.files.length) {
                showError('resume-error');
                return false;
            }
            hideError('resume-error');
            return true;
        }
    };

    function showError(id) {
        const el = document.getElementById(id);
        if (el) el.classList.remove('hidden');
    }

    function hideError(id) {
        const el = document.getElementById(id);
        if (el) el.classList.add('hidden');
    }

    function goToStep(step) {
        // Hide current step
        const currentEl = document.getElementById('form-step-' + currentStep);
        if (currentEl) {
            currentEl.classList.remove('active');
        }

        // Update indicators
        updateStepIndicator(currentStep, step > currentStep ? 'completed' : 'pending');

        currentStep = step;

        // Show new step
        const nextEl = document.getElementById('form-step-' + currentStep);
        if (nextEl) {
            nextEl.classList.add('active');
            // Scroll to top of form smoothly
            nextEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        // Update active indicator
        updateStepIndicator(currentStep, 'active');

        // Update connector lines
        for (let i = 1; i < TOTAL_STEPS; i++) {
            const line = document.getElementById('step-line-' + i);
            if (line) {
                if (i < currentStep) {
                    line.classList.add('completed');
                } else {
                    line.classList.remove('completed');
                }
            }
        }

        // Update progress text
        progressTx.textContent = 'Step ' + currentStep + ' of ' + TOTAL_STEPS;

        // Update buttons
        prevBtn.disabled = (currentStep === 1);
        if (currentStep === TOTAL_STEPS) {
            nextBtn.classList.add('hidden');
            submitBtn.classList.remove('hidden');
        } else {
            nextBtn.classList.remove('hidden');
            submitBtn.classList.add('hidden');
        }
    }

    function updateStepIndicator(step, state) {
        const indicator = document.getElementById('step-indicator-' + step);
        if (!indicator) return;

        indicator.classList.remove('active', 'completed');

        if (state === 'active') {
            indicator.classList.add('active');
            indicator.querySelector('.step-circle').innerHTML = step;
        } else if (state === 'completed') {
            indicator.classList.add('completed');
            indicator.querySelector('.step-circle').innerHTML = '<i class="fas fa-check text-xs"></i>';
        } else {
            indicator.querySelector('.step-circle').innerHTML = step;
        }
    }

    // ---- Button listeners ----
    nextBtn.addEventListener('click', function() {
        const validator = stepValidators[currentStep];
        if (validator && !validator()) {
            // Shake the button to indicate error
            this.classList.add('animate-pulse');
            setTimeout(() => this.classList.remove('animate-pulse'), 600);
            return;
        }
        if (currentStep < TOTAL_STEPS) {
            goToStep(currentStep + 1);
        }
    });

    prevBtn.addEventListener('click', function() {
        if (currentStep > 1) {
            goToStep(currentStep - 1);
        }
    });

    // ---- Resume upload listeners ----
    const resumeInput = document.getElementById('resumeInput');
    resumeInput?.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            document.getElementById('fileName').textContent = file.name;
            document.getElementById('filePreview').classList.remove('hidden');
            document.getElementById('resumeUploadBtn').classList.add('hidden');
        }
    });

    window.clearResume = function() {
        resumeInput.value = '';
        document.getElementById('filePreview').classList.add('hidden');
        document.getElementById('resumeUploadBtn').classList.remove('hidden');
    };

    // ---- Initialize on load ----
    document.addEventListener('DOMContentLoaded', function() {
        goToStep(1);

        // If there are validation errors from server, go to step 1
        const serverErrors = document.querySelectorAll('.text-red-500');
        if (serverErrors.length > 0) {
            goToStep(1);
        }
    });

    // ── Live mobile photo preview ─────────────────────────────────────────────
    const mPhotoInput = document.getElementById('mobile_profile_photo');
    if (mPhotoInput) {
        mPhotoInput.addEventListener('change', function() {
            const file = this.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = function(e) {
                const wrap = document.getElementById('mPhotoWrap');
                wrap.innerHTML = '<img src="' + e.target.result + '" alt="Preview" style="width:100%;height:100%;object-fit:cover;">';
            };
            reader.readAsDataURL(file);
        });
    }

})();
</script>
@endpush
@endsection
