{{--
    Apply Flow (Mobile) — 3-Step Application Form
    Extends the mobile layout so students get the bottom nav and design tokens.
    All form logic, routes, and x-forms components are identical to apply.blade.php.
--}}

@extends('layouts.app-mobile')

@section('title', 'Apply — ' . $internship->title)

@section('content')
<div class="px-4 py-5 max-w-lg mx-auto" style="padding-bottom: 100px;">

    {{-- Back --}}
    <a href="{{ url()->previous() }}"
       class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-800 mb-4 transition-colors active:scale-95">
        <i class="fas fa-arrow-left text-xs"></i>
        <span>Back</span>
    </a>

    {{-- Job Info Card --}}
    <div class="card mb-4">
        <div class="flex items-start gap-3">
            <div class="w-12 h-12 bg-primary-100 rounded-xl flex items-center justify-center flex-shrink-0">
                <i class="fas fa-building text-primary-600 text-lg"></i>
            </div>
            <div class="flex-1 min-w-0">
                <h1 class="text-base font-bold text-gray-900 leading-tight truncate">
                    {{ $internship->title }}
                </h1>
                <p class="text-sm text-gray-500">{{ $internship->organization }}</p>
                <div class="flex flex-wrap gap-2 mt-1.5 text-xs text-gray-500">
                    <span class="flex items-center gap-1">
                        <i class="fas fa-map-marker-alt"></i>{{ $internship->location }}
                    </span>
                    <span class="flex items-center gap-1">
                        <i class="fas fa-clock"></i>{{ $internship->duration }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- Info Banner --}}
    <div class="bg-blue-50 border border-blue-200 rounded-xl p-3.5 mb-5 flex gap-2.5">
        <i class="fas fa-info-circle text-blue-600 mt-0.5 flex-shrink-0"></i>
        <p class="text-sm text-blue-800">
            Complete all <strong>3 steps</strong> before submitting. You can go back to edit any step.
        </p>
    </div>

    {{-- Flash errors --}}
    @if($errors->any())
        <div class="bg-red-50 border border-red-200 rounded-xl p-3 mb-4">
            <p class="text-sm font-semibold text-red-700 mb-1">
                <i class="fas fa-exclamation-circle mr-1"></i>Please fix the following:
            </p>
            <ul class="text-sm text-red-600 space-y-0.5 list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Multi-Step Form --}}
    <x-forms.multi-step-form
        :total-steps="3"
        :current-step="1"
        form-id="apply-form"
        form-action="{{ route('applications.apply', $internship) }}"
        form-method="POST">

        {{-- ============ STEP 1: Basic Info ============ --}}
        <x-forms.form-step
            step="1"
            title="Basic Information"
            description="Tell us why you're interested in this role"
            form-id="apply-form">

            {{-- Cover Letter --}}
            <div>
                <label for="cover_letter" class="form-label">
                    Cover Letter <span class="text-red-500">*</span>
                </label>
                <p class="text-xs text-gray-500 mb-1.5">Introduce yourself and explain your interest (100–1000 chars)</p>
                <textarea
                    id="cover_letter"
                    name="cover_letter"
                    rows="5"
                    required
                    minlength="100"
                    maxlength="1000"
                    placeholder="Dear Hiring Manager, I am excited to apply..."
                    class="form-input resize-none">{{ old('cover_letter') }}</textarea>
                <div class="flex justify-between mt-1">
                    <span class="text-xs text-red-500 hidden" id="cover-error">Minimum 100 characters</span>
                    <span class="text-xs text-gray-400 ml-auto" id="cover-count">0 / 1000</span>
                </div>
            </div>

            {{-- Why This Role --}}
            <div>
                <label for="why_this_role" class="form-label">
                    Why This Role? <span class="text-red-500">*</span>
                </label>
                <p class="text-xs text-gray-500 mb-1.5">What specific skills or experiences make you a great fit? (50–500 chars)</p>
                <textarea
                    id="why_this_role"
                    name="why_this_role"
                    rows="4"
                    required
                    minlength="50"
                    maxlength="500"
                    placeholder="My experience in ... makes me particularly suited..."
                    class="form-input resize-none">{{ old('why_this_role') }}</textarea>
                <div class="flex justify-between mt-1">
                    <span class="text-xs text-red-500 hidden" id="why-error">Minimum 50 characters</span>
                    <span class="text-xs text-gray-400 ml-auto" id="why-count">0 / 500</span>
                </div>
            </div>

        </x-forms.form-step>

        {{-- ============ STEP 2: Resume Upload ============ --}}
        <x-forms.form-step
            step="2"
            title="Resume Upload"
            description="Upload your latest resume in PDF format"
            form-id="apply-form">

            {{-- File Upload --}}
            <div>
                <label class="form-label">Resume (PDF, max 2MB)</label>

                {{-- Upload Zone --}}
                <div id="resume-drop-zone"
                     class="border-2 border-dashed border-gray-300 rounded-xl p-6 text-center transition-all duration-200 cursor-pointer hover:border-primary-400 hover:bg-primary-50"
                     onclick="document.getElementById('resume-input').click();">
                    <div id="upload-placeholder">
                        <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 mb-2 block"></i>
                        <p class="text-sm font-medium text-gray-600">Tap to upload your resume</p>
                        <p class="text-xs text-gray-400 mt-1">PDF files only, max 2MB</p>
                    </div>
                    <div id="file-preview" class="hidden">
                        <i class="fas fa-file-pdf text-red-500 text-3xl mb-2 block"></i>
                        <p class="text-sm font-bold text-gray-900" id="file-name">filename.pdf</p>
                        <p class="text-xs text-gray-400" id="file-size">0 KB</p>
                        <button type="button"
                                onclick="event.stopPropagation(); clearResume();"
                                class="mt-2 text-xs text-red-500 hover:text-red-700 font-medium">
                            <i class="fas fa-trash-alt mr-1"></i> Remove
                        </button>
                    </div>
                </div>

                <input type="file"
                       id="resume-input"
                       name="resume"
                       accept=".pdf"
                       class="hidden"
                       onchange="handleResume(this)">
            </div>

            {{-- Profile Resume Fallback --}}
            @auth
                @php $profile = auth()->user()->profile; @endphp
                @if($profile && $profile->resume_path)
                    <div class="bg-gray-50 rounded-xl p-3 border border-gray-200 mt-2">
                        <p class="text-xs text-gray-500 mb-2 font-medium">— Or use your profile resume —</p>
                        <div class="flex items-center gap-2">
                            <i class="fas fa-file-pdf text-red-400"></i>
                            <span class="text-sm text-gray-700 truncate flex-1">Profile Resume</span>
                            <label class="flex items-center gap-1.5 cursor-pointer">
                                <input type="checkbox" name="use_profile_resume" value="1"
                                       class="rounded text-primary-600">
                                <span class="text-xs text-gray-600">Use this</span>
                            </label>
                        </div>
                    </div>
                @endif
            @endauth

        </x-forms.form-step>

        {{-- ============ STEP 3: Review & Submit ============ --}}
        <x-forms.form-step
            step="3"
            title="Review & Submit"
            description="Confirm your details before submitting"
            form-id="apply-form">

            <x-forms.review-step form-id="apply-form" />

            {{-- Terms --}}
            <div class="flex items-start gap-3 bg-gray-50 rounded-xl p-3 border border-gray-200 mt-3">
                <input type="checkbox"
                       id="terms"
                       name="terms"
                       required
                       class="mt-0.5 rounded text-primary-600 flex-shrink-0">
                <label for="terms" class="text-sm text-gray-600">
                    I confirm that all information provided is accurate and I agree to the
                    <a href="#" class="text-primary-600 font-medium underline">Terms &amp; Conditions</a>.
                </label>
            </div>

        </x-forms.form-step>

    </x-forms.multi-step-form>

</div>

@push('scripts')
<script>
(function() {
    'use strict';

    // --- Character counters ---
    function initCounter(textareaId, countId, min, errorId) {
        const el = document.getElementById(textareaId);
        const countEl = document.getElementById(countId);
        const errorEl = document.getElementById(errorId);
        if (!el || !countEl) return;

        const max = parseInt(el.getAttribute('maxlength'), 10);

        function update() {
            const len = el.value.length;
            countEl.textContent = len + ' / ' + max;
            if (errorEl) errorEl.classList.toggle('hidden', len >= min);
        }

        el.addEventListener('input', update);
        update();
    }

    initCounter('cover_letter', 'cover-count', 100, 'cover-error');
    initCounter('why_this_role', 'why-count', 50, 'why-error');

    // --- Resume file handler ---
    window.handleResume = function(input) {
        const file = input.files[0];
        if (!file) return;

        if (file.size > 2 * 1024 * 1024) {
            alert('File exceeds 2MB limit. Please select a smaller file.');
            input.value = '';
            return;
        }

        document.getElementById('upload-placeholder').classList.add('hidden');
        document.getElementById('file-preview').classList.remove('hidden');
        document.getElementById('file-name').textContent = file.name;
        document.getElementById('file-size').textContent = formatBytes(file.size);
    };

    window.clearResume = function() {
        const input = document.getElementById('resume-input');
        if (input) input.value = '';
        document.getElementById('upload-placeholder').classList.remove('hidden');
        document.getElementById('file-preview').classList.add('hidden');
    };

    function formatBytes(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
    }

    // --- Drag and drop ---
    const dropZone = document.getElementById('resume-drop-zone');
    if (dropZone) {
        dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('border-primary-400', 'bg-primary-50'); });
        dropZone.addEventListener('dragleave', () => dropZone.classList.remove('border-primary-400', 'bg-primary-50'));
        dropZone.addEventListener('drop', e => {
            e.preventDefault();
            dropZone.classList.remove('border-primary-400', 'bg-primary-50');
            const dt = e.dataTransfer;
            if (dt.files.length > 0) {
                const input = document.getElementById('resume-input');
                input.files = dt.files;
                window.handleResume(input);
            }
        });
    }
})();
</script>
@endpush
@endsection
