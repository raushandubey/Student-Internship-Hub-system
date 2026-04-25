{{--
    Apply Flow View - Multi-Step Application Form
    
    Mobile-First Redesign - Task 10.1
    Requirements: 5.1, 5.2, 5.4, 5.5
    
    This view implements a 3-step application flow:
    - Step 1: Basic Info (cover letter, why this role)
    - Step 2: Resume Upload (file input with drag-and-drop UI)
    - Step 3: Confirmation (review summary)
    
    Uses the multi-step-form component for progress indication and navigation.
--}}

@extends('layouts.app')

@section('title', 'Apply for ' . $internship->title)

@section('content')
<div class="apply-flow-container min-h-screen bg-gray-50 py-6 md:py-8">
    <div class="max-w-2xl mx-auto px-4">
        {{-- Header Section --}}
        <div class="mb-6">
            {{-- Back Button --}}
            <a href="{{ route('internships.show', $internship) }}" 
               class="inline-flex items-center text-gray-600 hover:text-gray-900 mb-4 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>
                <span class="text-sm font-medium">Back to Job Details</span>
            </a>
            
            {{-- Job Info Card --}}
            <div class="bg-white rounded-lg shadow-md p-4 mb-6">
                <div class="flex items-start gap-4">
                    {{-- Company Logo Placeholder --}}
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-building text-blue-600 text-xl"></i>
                    </div>
                    
                    {{-- Job Info --}}
                    <div class="flex-1 min-w-0">
                        <h1 class="text-lg md:text-xl font-bold text-gray-900 mb-1 truncate">
                            {{ $internship->title }}
                        </h1>
                        <p class="text-sm text-gray-600 mb-2">{{ $internship->organization }}</p>
                        <div class="flex flex-wrap gap-2 text-xs text-gray-500">
                            <span class="flex items-center">
                                <i class="fas fa-map-marker-alt mr-1"></i>
                                {{ $internship->location }}
                            </span>
                            <span class="flex items-center">
                                <i class="fas fa-clock mr-1"></i>
                                {{ $internship->duration }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            
            {{-- Application Instructions --}}
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                <div class="flex items-start">
                    <i class="fas fa-info-circle text-blue-600 mt-0.5 mr-3"></i>
                    <div class="flex-1">
                        <p class="text-sm text-blue-900 font-medium mb-1">
                            Complete Your Application
                        </p>
                        <p class="text-sm text-blue-800">
                            Fill out all required information across 3 simple steps. You can go back to edit any section before submitting.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Multi-Step Form --}}
        <x-forms.multi-step-form 
            :total-steps="3" 
            :current-step="1" 
            form-id="application-form"
            form-action="{{ route('applications.apply', $internship) }}"
            form-method="POST">
            
            {{-- Step 1: Basic Information --}}
            <x-forms.form-step 
                step="1" 
                title="Basic Information" 
                description="Tell us why you're interested in this role"
                form-id="application-form">
                
                {{-- Cover Letter --}}
                <div class="mb-6">
                    <label for="cover_letter" class="block text-sm font-semibold text-gray-700 mb-2">
                        Cover Letter <span class="text-red-500">*</span>
                    </label>
                    <p class="text-xs text-gray-600 mb-2">
                        Introduce yourself and explain your interest in this position (100-1000 characters)
                    </p>
                    <textarea 
                        id="cover_letter" 
                        name="cover_letter" 
                        rows="6"
                        required
                        minlength="100"
                        maxlength="1000"
                        placeholder="Dear Hiring Manager,&#10;&#10;I am writing to express my strong interest in the {{ $internship->title }} position at {{ $internship->organization }}..."
                        class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all resize-none"
                        data-pattern-message="Cover letter must be between 100 and 1000 characters"></textarea>
                    <div class="flex justify-between items-center mt-1">
                        <span class="text-xs text-gray-500">Minimum 100 characters</span>
                        <span id="cover_letter_count" class="text-xs text-gray-500">0 / 1000</span>
                    </div>
                </div>
                
                {{-- Why This Role --}}
                <div class="mb-6">
                    <label for="why_this_role" class="block text-sm font-semibold text-gray-700 mb-2">
                        Why This Role? <span class="text-red-500">*</span>
                    </label>
                    <p class="text-xs text-gray-600 mb-2">
                        What specifically interests you about this opportunity? (50-500 characters)
                    </p>
                    <textarea 
                        id="why_this_role" 
                        name="why_this_role" 
                        rows="4"
                        required
                        minlength="50"
                        maxlength="500"
                        placeholder="I am particularly interested in this role because..."
                        class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all resize-none"
                        data-pattern-message="Response must be between 50 and 500 characters"></textarea>
                    <div class="flex justify-between items-center mt-1">
                        <span class="text-xs text-gray-500">Minimum 50 characters</span>
                        <span id="why_this_role_count" class="text-xs text-gray-500">0 / 500</span>
                    </div>
                </div>
                
                {{-- Additional Notes (Optional) --}}
                <div class="mb-6">
                    <label for="additional_notes" class="block text-sm font-semibold text-gray-700 mb-2">
                        Additional Notes <span class="text-gray-400 text-xs">(Optional)</span>
                    </label>
                    <p class="text-xs text-gray-600 mb-2">
                        Any other information you'd like to share (max 300 characters)
                    </p>
                    <textarea 
                        id="additional_notes" 
                        name="additional_notes" 
                        rows="3"
                        maxlength="300"
                        placeholder="Optional: Add any relevant information..."
                        class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all resize-none"></textarea>
                    <div class="flex justify-end mt-1">
                        <span id="additional_notes_count" class="text-xs text-gray-500">0 / 300</span>
                    </div>
                </div>
            </x-forms.form-step>
            
            {{-- Step 2: Resume Upload --}}
            <x-forms.form-step 
                step="2" 
                title="Resume & Portfolio" 
                description="Upload your resume and share your work"
                form-id="application-form">
                
                {{-- Resume Upload --}}
                <div class="mb-6">
                    <label for="resume" class="block text-sm font-semibold text-gray-700 mb-2">
                        Resume <span class="text-red-500">*</span>
                    </label>
                    <p class="text-xs text-gray-600 mb-3">
                        Upload your resume in PDF format (max 5MB)
                    </p>
                    
                    {{-- File Input with Drag & Drop UI --}}
                    <div class="relative">
                        <input 
                            type="file" 
                            id="resume" 
                            name="resume" 
                            accept=".pdf,application/pdf"
                            required
                            data-max-size="5242880"
                            class="hidden"
                            onchange="handleFileSelect(this, 'resume-display')">
                        
                        {{-- Custom File Upload UI --}}
                        <label 
                            for="resume" 
                            id="resume-upload-area"
                            class="flex flex-col items-center justify-center w-full h-40 border-2 border-dashed border-gray-300 rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100 transition-all"
                            ondragover="handleDragOver(event, this)"
                            ondragleave="handleDragLeave(event, this)"
                            ondrop="handleDrop(event, 'resume', 'resume-display')">
                            <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-3"></i>
                                <p class="mb-2 text-sm text-gray-600">
                                    <span class="font-semibold">Click to upload</span> or drag and drop
                                </p>
                                <p class="text-xs text-gray-500">PDF only (max 5MB)</p>
                            </div>
                        </label>
                        
                        {{-- File Display --}}
                        <div id="resume-display" class="file-display hidden mt-3 p-4 bg-green-50 border border-green-200 rounded-lg">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center flex-1 min-w-0">
                                    <i class="fas fa-file-pdf text-red-500 text-2xl mr-3"></i>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900 truncate" id="resume-filename"></p>
                                        <p class="text-xs text-gray-600" id="resume-filesize"></p>
                                    </div>
                                </div>
                                <button 
                                    type="button" 
                                    onclick="clearFile('resume', 'resume-display')"
                                    class="ml-3 text-red-600 hover:text-red-700 transition-colors">
                                    <i class="fas fa-times-circle text-xl"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Resume Tips --}}
                    <div class="mt-3 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                        <p class="text-xs text-yellow-800">
                            <i class="fas fa-lightbulb mr-1"></i>
                            <strong>Tip:</strong> Make sure your resume is up-to-date and highlights relevant skills for this position.
                        </p>
                    </div>
                </div>
                
                {{-- Portfolio URL (Optional) --}}
                <div class="mb-6">
                    <label for="portfolio_url" class="block text-sm font-semibold text-gray-700 mb-2">
                        Portfolio URL <span class="text-gray-400 text-xs">(Optional)</span>
                    </label>
                    <p class="text-xs text-gray-600 mb-2">
                        Share a link to your portfolio, GitHub, LinkedIn, or personal website
                    </p>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-link text-gray-400"></i>
                        </div>
                        <input 
                            type="url" 
                            id="portfolio_url" 
                            name="portfolio_url" 
                            placeholder="https://example.com/portfolio"
                            class="w-full pl-10 pr-4 py-3 border-2 border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                    </div>
                </div>
                
                {{-- LinkedIn Profile (Optional) --}}
                <div class="mb-6">
                    <label for="linkedin_url" class="block text-sm font-semibold text-gray-700 mb-2">
                        LinkedIn Profile <span class="text-gray-400 text-xs">(Optional)</span>
                    </label>
                    <p class="text-xs text-gray-600 mb-2">
                        Share your LinkedIn profile URL
                    </p>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fab fa-linkedin text-blue-600"></i>
                        </div>
                        <input 
                            type="url" 
                            id="linkedin_url" 
                            name="linkedin_url" 
                            placeholder="https://linkedin.com/in/yourprofile"
                            class="w-full pl-10 pr-4 py-3 border-2 border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                    </div>
                </div>
            </x-forms.form-step>
            
            {{-- Step 3: Review & Confirm --}}
            <x-forms.form-step 
                step="3" 
                title="Review & Confirm" 
                description="Review your application before submitting"
                form-id="application-form">
                
                {{-- Review Step Component --}}
                <x-forms.review-step 
                    form-id="application-form"
                    title="Review Your Application"
                    description="Please review all information before submitting. You can go back to edit any section." />
                
                {{-- Terms & Conditions --}}
                <div class="mt-6 p-4 bg-gray-50 border border-gray-200 rounded-lg">
                    <div class="flex items-start">
                        <input 
                            type="checkbox" 
                            id="terms_accepted" 
                            name="terms_accepted" 
                            required
                            class="mt-1 w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        <label for="terms_accepted" class="ml-3 text-sm text-gray-700">
                            I confirm that all information provided is accurate and complete. I understand that providing false information may result in disqualification. <span class="text-red-500">*</span>
                        </label>
                    </div>
                </div>
            </x-forms.form-step>
        </x-forms.multi-step-form>
    </div>
</div>

{{-- Character Counter Script --}}
<script>
// Character counter for textareas
document.addEventListener('DOMContentLoaded', function() {
    const textareas = [
        { id: 'cover_letter', counterId: 'cover_letter_count', max: 1000 },
        { id: 'why_this_role', counterId: 'why_this_role_count', max: 500 },
        { id: 'additional_notes', counterId: 'additional_notes_count', max: 300 }
    ];
    
    textareas.forEach(({ id, counterId, max }) => {
        const textarea = document.getElementById(id);
        const counter = document.getElementById(counterId);
        
        if (textarea && counter) {
            textarea.addEventListener('input', function() {
                const length = this.value.length;
                counter.textContent = `${length} / ${max}`;
                
                // Change color based on length
                if (length >= max) {
                    counter.classList.add('text-red-600', 'font-semibold');
                    counter.classList.remove('text-gray-500');
                } else if (length >= max * 0.9) {
                    counter.classList.add('text-yellow-600', 'font-semibold');
                    counter.classList.remove('text-gray-500', 'text-red-600');
                } else {
                    counter.classList.remove('text-red-600', 'text-yellow-600', 'font-semibold');
                    counter.classList.add('text-gray-500');
                }
            });
        }
    });
});

// File upload handlers
function handleFileSelect(input, displayId) {
    const file = input.files[0];
    if (!file) return;
    
    const display = document.getElementById(displayId);
    const uploadArea = document.getElementById(input.id + '-upload-area');
    
    if (display && uploadArea) {
        // Update display
        const filename = display.querySelector(`#${input.id}-filename`);
        const filesize = display.querySelector(`#${input.id}-filesize`);
        
        if (filename) filename.textContent = file.name;
        if (filesize) filesize.textContent = formatFileSize(file.size);
        
        // Show display, hide upload area
        display.classList.remove('hidden');
        uploadArea.classList.add('hidden');
    }
}

function clearFile(inputId, displayId) {
    const input = document.getElementById(inputId);
    const display = document.getElementById(displayId);
    const uploadArea = document.getElementById(inputId + '-upload-area');
    
    if (input) input.value = '';
    if (display) display.classList.add('hidden');
    if (uploadArea) uploadArea.classList.remove('hidden');
}

function handleDragOver(event, element) {
    event.preventDefault();
    element.classList.add('border-blue-500', 'bg-blue-50');
}

function handleDragLeave(event, element) {
    event.preventDefault();
    element.classList.remove('border-blue-500', 'bg-blue-50');
}

function handleDrop(event, inputId, displayId) {
    event.preventDefault();
    const element = event.currentTarget;
    element.classList.remove('border-blue-500', 'bg-blue-50');
    
    const input = document.getElementById(inputId);
    const files = event.dataTransfer.files;
    
    if (files.length > 0 && input) {
        input.files = files;
        handleFileSelect(input, displayId);
    }
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
}
</script>

{{-- Custom Styles --}}
<style>
/* Apply Flow Container */
.apply-flow-container {
    min-height: calc(100vh - 60px); /* Account for bottom nav on mobile */
}

/* File Upload Area Hover State */
#resume-upload-area:hover {
    border-color: #3b82f6;
}

/* Drag Over State */
#resume-upload-area.border-blue-500 {
    border-color: #3b82f6;
    background-color: #eff6ff;
}

/* Smooth transitions */
.file-display {
    animation: slideDown 0.3s ease-out;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Mobile adjustments */
@media (max-width: 767px) {
    .apply-flow-container {
        padding-bottom: 100px; /* Extra space for bottom nav + form buttons */
    }
}

/* Responsive typography */
@media (min-width: 768px) {
    .apply-flow-container {
        padding-top: 2rem;
        padding-bottom: 2rem;
    }
}
</style>
@endsection
