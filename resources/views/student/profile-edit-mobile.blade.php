@extends('layouts.app-mobile')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-6">
    
    {{-- Header --}}
    <div class="mb-6">
        <a href="{{ route('profile.show') }}" class="inline-flex items-center text-sm text-gray-600 mb-3">
            <i class="fas fa-arrow-left mr-2"></i>
            Back to Profile
        </a>
        <h1 class="text-2xl font-bold text-gray-900 mb-2">Edit Profile</h1>
        <p class="text-sm text-gray-600">Complete all steps to maximize your job matches</p>
    </div>

    {{-- Form Wizard Container --}}
    <div class="form-wizard" data-total-steps="4" data-form-id="profile-edit">
        
        {{-- Progress Stepper --}}
        <div class="bg-white rounded-2xl p-4 mb-4 border border-gray-200">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-gray-700">Progress</span>
                <span class="text-sm font-medium text-primary-600" id="progressText">Step 1 of 4</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="step-item active" data-step="1">
                    <div class="step-circle">1</div>
                </div>
                <div class="step-line"></div>
                <div class="step-item" data-step="2">
                    <div class="step-circle">2</div>
                </div>
                <div class="step-line"></div>
                <div class="step-item" data-step="3">
                    <div class="step-circle">3</div>
                </div>
                <div class="step-line"></div>
                <div class="step-item" data-step="4">
                    <div class="step-circle">4</div>
                </div>
            </div>
        </div>

        {{-- Form --}}
        <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            {{-- Step 1: Basic Information --}}
            <div class="form-step active bg-white rounded-2xl p-6 border border-gray-200" data-step="1">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Basic Information</h2>
                
                <div class="space-y-4">
                    <div class="form-group">
                        <label class="form-label">Full Name *</label>
                        <input type="text" 
                               name="name" 
                               class="form-input" 
                               value="{{ old('name', $profile->name ?? auth()->user()->name) }}"
                               required>
                        <span class="form-error hidden">Please enter your full name</span>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Academic Background *</label>
                        <textarea name="academic_background" 
                                  class="form-textarea" 
                                  rows="4" 
                                  placeholder="e.g., B.Tech in Computer Science, XYZ University, 2024"
                                  required>{{ old('academic_background', $profile->academic_background ?? '') }}</textarea>
                        <span class="form-hint">Include your degree, major, university, and graduation year</span>
                        <span class="form-error hidden">Please enter your academic background</span>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Aadhaar Number *</label>
                        <input type="text" 
                               name="aadhaar_number" 
                               class="form-input" 
                               value="{{ old('aadhaar_number', $profile->aadhaar_number ?? '') }}"
                               pattern="[0-9]{12}"
                               maxlength="12"
                               placeholder="123456789012"
                               required>
                        <span class="form-hint">12-digit Aadhaar number (for verification purposes)</span>
                        <span class="form-error hidden">Please enter a valid 12-digit Aadhaar number</span>
                    </div>
                </div>
            </div>

            {{-- Step 2: Skills --}}
            <div class="form-step hidden bg-white rounded-2xl p-6 border border-gray-200" data-step="2">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Your Skills</h2>
                
                <div class="space-y-4">
                    <div class="form-group">
                        <label class="form-label">Skills *</label>
                        <textarea name="skills" 
                                  class="form-textarea" 
                                  rows="6" 
                                  placeholder="e.g., Python, JavaScript, React, Node.js, SQL, Git"
                                  required>{{ old('skills', $profile->skills ?? '') }}</textarea>
                        <span class="form-hint">List your technical and soft skills (comma-separated)</span>
                        <span class="form-error hidden">Please enter at least one skill</span>
                    </div>

                    <div class="p-4 bg-blue-50 rounded-xl">
                        <p class="text-sm text-blue-700 font-medium mb-2">
                            <i class="fas fa-lightbulb"></i> Tip
                        </p>
                        <p class="text-sm text-blue-600">
                            Include both technical skills (programming languages, tools) and soft skills (communication, teamwork, problem-solving)
                        </p>
                    </div>
                </div>
            </div>

            {{-- Step 3: Career Interests --}}
            <div class="form-step hidden bg-white rounded-2xl p-6 border border-gray-200" data-step="3">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Career Interests</h2>
                
                <div class="space-y-4">
                    <div class="form-group">
                        <label class="form-label">Career Interests *</label>
                        <textarea name="career_interests" 
                                  class="form-textarea" 
                                  rows="6" 
                                  placeholder="e.g., Software Development, Data Science, Machine Learning, Web Development"
                                  required>{{ old('career_interests', $profile->career_interests ?? '') }}</textarea>
                        <span class="form-hint">What type of roles are you interested in?</span>
                        <span class="form-error hidden">Please enter your career interests</span>
                    </div>

                    <div class="p-4 bg-green-50 rounded-xl">
                        <p class="text-sm text-green-700 font-medium mb-2">
                            <i class="fas fa-star"></i> Better Matches
                        </p>
                        <p class="text-sm text-green-600">
                            The more specific you are, the better we can match you with relevant opportunities
                        </p>
                    </div>
                </div>
            </div>

            {{-- Step 4: Resume Upload --}}
            <div class="form-step hidden bg-white rounded-2xl p-6 border border-gray-200" data-step="4">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Upload Resume</h2>
                
                <div class="space-y-4">
                    @if($profile && $profile->resume_path)
                        <div class="p-4 bg-gray-50 rounded-xl mb-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-file-pdf text-red-600"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-gray-900">Current Resume</p>
                                    <p class="text-xs text-gray-500">{{ basename($profile->resume_path) }}</p>
                                </div>
                                <a href="{{ $profile->getResumeUrl() }}" 
                                   target="_blank" 
                                   class="text-sm text-primary-600 hover:text-primary-700">
                                    View
                                </a>
                            </div>
                        </div>
                    @endif

                    <div class="form-group">
                        <label class="form-label">
                            {{ $profile && $profile->resume_path ? 'Replace Resume' : 'Upload Resume *' }}
                        </label>
                        <div class="relative">
                            <input type="file" 
                                   name="resume" 
                                   id="resumeInput"
                                   class="hidden" 
                                   accept=".pdf,.doc,.docx"
                                   {{ !($profile && $profile->resume_path) ? 'required' : '' }}>
                            <button type="button" 
                                    onclick="document.getElementById('resumeInput').click()"
                                    class="w-full border-2 border-dashed border-gray-300 rounded-xl p-6 text-center hover:border-primary-500 transition-colors">
                                <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 mb-2"></i>
                                <p class="text-sm font-medium text-gray-700">Click to upload</p>
                                <p class="text-xs text-gray-500 mt-1">PDF, DOC, or DOCX (Max 5MB)</p>
                            </button>
                        </div>
                        <div id="filePreview" class="hidden mt-3 p-3 bg-green-50 rounded-lg">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-check-circle text-green-600"></i>
                                <span class="text-sm text-green-700" id="fileName"></span>
                            </div>
                        </div>
                        <span class="form-error hidden">Please upload your resume</span>
                    </div>

                    <div class="p-4 bg-yellow-50 rounded-xl">
                        <p class="text-sm text-yellow-700 font-medium mb-2">
                            <i class="fas fa-exclamation-triangle"></i> Important
                        </p>
                        <p class="text-sm text-yellow-600">
                            Make sure your resume is up-to-date and highlights your relevant skills and experience
                        </p>
                    </div>
                </div>
            </div>

            {{-- Navigation Buttons --}}
            <div class="flex gap-3 mt-6">
                <button type="button" 
                        id="prevBtn" 
                        class="btn btn-secondary flex-1"
                        disabled>
                    <i class="fas fa-arrow-left mr-2"></i>
                    Back
                </button>
                <button type="button" 
                        id="nextBtn" 
                        class="btn btn-primary flex-1">
                    Next
                    <i class="fas fa-arrow-right ml-2"></i>
                </button>
                <button type="submit" 
                        id="submitBtn" 
                        class="btn btn-primary flex-1 hidden">
                    <i class="fas fa-check mr-2"></i>
                    Save Profile
                </button>
            </div>
        </form>

    </div>

</div>

@push('scripts')
<script>
// File upload preview
document.getElementById('resumeInput')?.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        document.getElementById('fileName').textContent = file.name;
        document.getElementById('filePreview').classList.remove('hidden');
    }
});

// Update progress text
document.addEventListener('DOMContentLoaded', function() {
    const wizard = document.querySelector('.form-wizard');
    if (wizard) {
        const observer = new MutationObserver(function() {
            const currentStep = wizard.querySelector('.form-step.active')?.dataset.step || 1;
            const totalSteps = wizard.dataset.totalSteps || 4;
            document.getElementById('progressText').textContent = `Step ${currentStep} of ${totalSteps}`;
        });
        
        observer.observe(wizard, { 
            attributes: true, 
            subtree: true, 
            attributeFilter: ['class'] 
        });
    }
});
</script>
@endpush
@endsection
