{{--
    x-forms.form-step — Represents a single step in a multi-step form.
    Contains the fields for that step.

    Props:
      - step (int)        : The step number (1-indexed)
      - title (string)    : The title of this step
      - description (str) : Subtitle/description
      - formId (string)   : The ID of the parent multi-step-form
--}}
@props([
    'step'        => 1,
    'title'       => '',
    'description' => '',
    'formId'      => 'multi-step-form',
])

<div data-form-step="{{ $step }}" class="card card-lg" style="display: {{ $step == 1 ? 'block' : 'none' }};">
    
    @if($title || $description)
        <div class="mb-5 border-b border-gray-100 pb-4">
            @if($title)
                <h2 class="text-xl font-bold text-gray-900">{{ $title }}</h2>
            @endif
            @if($description)
                <p class="text-sm text-gray-500 mt-1">{{ $description }}</p>
            @endif
        </div>
    @endif

    {{-- Step Content Slot --}}
    <div class="space-y-4">
        {{ $slot }}
    </div>

</div>
