{{--
    x-forms.review-step — A special final step in a multi-step form that provides 
    a summary of the application before submission.

    Props:
      - formId (string)   : The ID of the parent multi-step-form
      - title (string)    : Step title
      - description (str) : Step description
--}}
@props([
    'formId'      => 'multi-step-form',
    'title'       => 'Review Your Application',
    'description' => 'Please review all information before submitting.',
])

<div>
    <div class="mb-5 border-b border-gray-100 pb-4">
        <h2 class="text-xl font-bold text-gray-900">{{ $title }}</h2>
        <p class="text-sm text-gray-500 mt-1">{{ $description }}</p>
    </div>

    {{-- Review Summary Container --}}
    <div class="bg-gray-50 rounded-xl p-5 space-y-4 border border-gray-200" id="{{ $formId }}-review-summary">
        
        {{-- Cover Letter Summary --}}
        <div>
            <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Cover Letter</h4>
            <p class="text-sm text-gray-900 bg-white p-3 rounded border border-gray-100 line-clamp-3 italic" id="{{ $formId }}-review-cover"></p>
        </div>

        {{-- Why This Role Summary --}}
        <div>
            <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Why This Role</h4>
            <p class="text-sm text-gray-900 bg-white p-3 rounded border border-gray-100 line-clamp-2 italic" id="{{ $formId }}-review-why"></p>
        </div>

        {{-- Resume Summary --}}
        <div>
            <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Resume Attached</h4>
            <div class="flex items-center gap-2 bg-white p-2 rounded border border-gray-100">
                <i class="fas fa-file-pdf text-red-500"></i>
                <span class="text-sm font-medium text-gray-900 truncate" id="{{ $formId }}-review-resume">No file selected</span>
            </div>
        </div>

    </div>
</div>

@once
<script>
/**
 * Simple script to populate the review step right before it's shown.
 * Listens for the "Next" button clicks on the parent form.
 */
document.addEventListener('DOMContentLoaded', function() {
    const formId = '{{ $formId }}';
    const form = document.getElementById(formId);
    if (!form) return;

    const nextBtn = document.getElementById(formId + '-next-btn');
    if (!nextBtn) return;

    nextBtn.addEventListener('click', function() {
        // We only care about updating this when we are moving to the final step (which we assume is step 3 for this specific apply flow)
        // A more robust solution would be to update it on every step change, but this is tailored for the current apply.blade.php

        const coverLetter = document.getElementById('cover_letter');
        const whyRole = document.getElementById('why_this_role');
        const resumeInput = document.getElementById('resume');

        const reviewCover = document.getElementById(formId + '-review-cover');
        const reviewWhy = document.getElementById(formId + '-review-why');
        const reviewResume = document.getElementById(formId + '-review-resume');

        if (coverLetter && reviewCover) {
            reviewCover.textContent = coverLetter.value || 'Not provided';
        }

        if (whyRole && reviewWhy) {
            reviewWhy.textContent = whyRole.value || 'Not provided';
        }

        if (resumeInput && reviewResume) {
            if (resumeInput.files && resumeInput.files[0]) {
                reviewResume.textContent = resumeInput.files[0].name;
            } else {
                reviewResume.textContent = 'No file selected';
            }
        }
    });
});
</script>
@endonce
