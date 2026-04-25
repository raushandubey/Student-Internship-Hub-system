{{--
    x-forms.multi-step-form — Wrapper component for multi-step forms.
    Handles form tag, CSRF, method spoofing, and file enctype.

    Props:
      - totalSteps (int) : Total number of wizard steps
      - currentStep (int) : Currently active step (1-indexed)
      - formId (string)   : Unique ID for this form
      - formAction (string): The POST/action URL
      - formMethod (string): HTTP method (GET, POST)
--}}
@props([
    'totalSteps'   => 3,
    'currentStep'  => 1,
    'formId'       => 'multi-step-form',
    'formAction'   => '',
    'formMethod'   => 'POST',
])

<div class="multi-step-form-wrapper max-w-2xl mx-auto" id="{{ $formId }}-wrapper">

    {{-- Step Progress Indicator --}}
    <div class="flex items-center mb-6 px-1" id="{{ $formId }}-progress" role="progressbar"
         aria-valuenow="{{ $currentStep }}" aria-valuemin="1" aria-valuemax="{{ $totalSteps }}">
        @for($step = 1; $step <= $totalSteps; $step++)
            {{-- Step circle --}}
            <div class="step-item {{ $step <= $currentStep ? ($step < $currentStep ? 'completed' : 'active') : '' }}"
                 id="{{ $formId }}-step-indicator-{{ $step }}">
                <div class="step-circle">
                    @if($step < $currentStep)
                        <i class="fas fa-check text-xs"></i>
                    @else
                        {{ $step }}
                    @endif
                </div>
                <span class="hidden sm:block text-[10px] mt-1 text-gray-500 text-center">Step {{ $step }}</span>
            </div>

            {{-- Connector line (not after last step) --}}
            @if($step < $totalSteps)
                <div class="step-line flex-1 {{ $step < $currentStep ? 'completed' : '' }}"
                     id="{{ $formId }}-step-line-{{ $step }}"></div>
            @endif
        @endfor
    </div>

    {{-- Step counter text --}}
    <p class="text-sm text-gray-500 text-center mb-5" id="{{ $formId }}-step-counter">
        Step <span id="{{ $formId }}-current-step">{{ $currentStep }}</span> of {{ $totalSteps }}
    </p>

    {{-- The actual form --}}
    <form id="{{ $formId }}"
          action="{{ $formAction }}"
          method="{{ strtoupper($formMethod) === 'GET' ? 'GET' : 'POST' }}"
          enctype="multipart/form-data">
        @csrf
        @if(!in_array(strtoupper($formMethod), ['GET', 'POST']))
            @method($formMethod)
        @endif

        {{-- Slot: all x-forms.form-step children go here --}}
        {{ $slot }}

        {{-- Navigation Buttons --}}
        <div class="flex gap-3 mt-6" id="{{ $formId }}-nav">
            <button type="button"
                    id="{{ $formId }}-prev-btn"
                    class="btn btn-secondary flex-1"
                    style="display: {{ $currentStep <= 1 ? 'none' : 'flex' }};">
                <i class="fas fa-arrow-left mr-2 text-xs"></i> Back
            </button>

            <button type="button"
                    id="{{ $formId }}-next-btn"
                    class="btn btn-primary flex-1"
                    style="display: {{ $currentStep >= $totalSteps ? 'none' : 'flex' }};">
                Next <i class="fas fa-arrow-right ml-2 text-xs"></i>
            </button>

            <button type="submit"
                    id="{{ $formId }}-submit-btn"
                    class="btn btn-primary flex-1"
                    style="display: {{ $currentStep >= $totalSteps ? 'flex' : 'none' }};">
                <i class="fas fa-paper-plane mr-2 text-xs"></i> Submit Application
            </button>
        </div>
    </form>
</div>

@once
<script>
/**
 * MultiStepForm — minimal vanilla JS wizard engine.
 * One instance per formId, initialized on DOMContentLoaded.
 */
(function() {
    document.addEventListener('DOMContentLoaded', function() {
        const wrappers = document.querySelectorAll('.multi-step-form-wrapper');

        wrappers.forEach(function(wrapper) {
            const formId   = wrapper.id.replace('-wrapper', '');
            const form     = document.getElementById(formId);
            if (!form) return;

            const steps        = form.querySelectorAll('[data-form-step]');
            const totalSteps   = steps.length;
            let   currentStep  = 1;

            const prevBtn    = document.getElementById(formId + '-prev-btn');
            const nextBtn    = document.getElementById(formId + '-next-btn');
            const submitBtn  = document.getElementById(formId + '-submit-btn');
            const counter    = document.getElementById(formId + '-current-step');

            function showStep(step) {
                steps.forEach(function(el, idx) {
                    el.style.display = (idx + 1 === step) ? 'block' : 'none';
                });

                // Update indicators
                for (let i = 1; i <= totalSteps; i++) {
                    const indicator = document.getElementById(formId + '-step-indicator-' + i);
                    const line      = document.getElementById(formId + '-step-line-' + i);
                    if (indicator) {
                        indicator.classList.remove('active', 'completed');
                        const circle = indicator.querySelector('.step-circle');
                        if (i < step) {
                            indicator.classList.add('completed');
                            if (circle) circle.innerHTML = '<i class="fas fa-check text-xs"></i>';
                        } else if (i === step) {
                            indicator.classList.add('active');
                            if (circle) circle.textContent = i;
                        } else {
                            if (circle) circle.textContent = i;
                        }
                    }
                    if (line) {
                        line.classList.toggle('completed', i < step);
                    }
                }

                // Update counter
                if (counter) counter.textContent = step;

                // Update buttons
                if (prevBtn) prevBtn.style.display  = step > 1          ? 'flex'  : 'none';
                if (nextBtn) nextBtn.style.display  = step < totalSteps ? 'flex'  : 'none';
                if (submitBtn) submitBtn.style.display = step >= totalSteps ? 'flex' : 'none';

                currentStep = step;

                // Scroll to top of wrapper
                wrapper.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }

            function validateCurrentStep() {
                const currentEl = form.querySelector('[data-form-step="' + currentStep + '"]');
                if (!currentEl) return true;

                const required = currentEl.querySelectorAll('[required]');
                let valid = true;
                required.forEach(function(el) {
                    if (!el.value.trim()) {
                        el.classList.add('border-red-400');
                        valid = false;
                    } else {
                        el.classList.remove('border-red-400');
                    }
                });
                return valid;
            }

            if (nextBtn) {
                nextBtn.addEventListener('click', function() {
                    if (!validateCurrentStep()) return;
                    if (currentStep < totalSteps) showStep(currentStep + 1);
                });
            }

            if (prevBtn) {
                prevBtn.addEventListener('click', function() {
                    if (currentStep > 1) showStep(currentStep - 1);
                });
            }

            showStep(1);
        });
    });
})();
</script>
@endonce
