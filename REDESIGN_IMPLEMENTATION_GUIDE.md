# 🚀 Mobile-First Redesign Implementation Guide

## Quick Start

This guide provides ready-to-use code samples for implementing the mobile-first redesign specified in `MOBILE_FIRST_REDESIGN_SPEC.md`.

---

## 1. Bottom Navigation Component

**File**: `resources/views/components/bottom-nav.blade.php`

```blade
{{-- Bottom Navigation (Mobile-First) --}}
<nav class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 z-50 pb-safe md:hidden">
    <div class="flex justify-around items-center h-16 px-2">
        {{-- Home --}}
        <a href="{{ route('dashboard') }}" 
           class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <i class="fas fa-home text-xl"></i>
            <span class="text-xs mt-1">Home</span>
        </a>

        {{-- Applications --}}
        <a href="{{ route('my-applications') }}" 
           class="nav-item {{ request()->routeIs('my-applications') ? 'active' : '' }}">
            <i class="fas fa-clipboard-list text-xl"></i>
            <span class="text-xs mt-1">Applications</span>
        </a>

        {{-- Recommendations --}}
        <a href="{{ route('recommendations.index') }}" 
           class="nav-item {{ request()->routeIs('recommendations.*') ? 'active' : '' }}">
            <i class="fas fa-star text-xl"></i>
            <span class="text-xs mt-1">Jobs</span>
        </a>

        {{-- Profile --}}
        <a href="{{ route('profile.show') }}" 
           class="nav-item {{ request()->routeIs('profile.*') ? 'active' : '' }}">
            <i class="fas fa-user text-xl"></i>
            <span class="text-xs mt-1">Profile</span>
        </a>
    </div>
</nav>

<style>
.nav-item {
    @apply flex flex-col items-center justify-center flex-1 py-2 text-gray-500 transition-colors;
    @apply hover:text-primary-600 active:scale-95;
    min-width: 60px;
    min-height: 44px; /* Touch-friendly */
}

.nav-item.active {
    @apply text-primary-600 font-semibold;
}

.nav-item i {
    @apply transition-transform;
}

.nav-item.active i {
    @apply scale-110;
}

/* Safe area for iPhone notch */
.pb-safe {
    padding-bottom: env(safe-area-inset-bottom);
}
</style>
```

---

## 2. Mobile-First Layout

**File**: `resources/views/layouts/app-mobile.blade.php`

```blade
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'InternshipHub') }}</title>
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 antialiased">
    {{-- Main Content --}}
    <main class="min-h-screen pb-20">
        {{-- pb-20 = 80px clearance for bottom nav --}}
        @yield('content')
    </main>

    {{-- Bottom Navigation --}}
    @auth
        @if(auth()->user()->isStudent())
            @include('components.bottom-nav')
        @endif
    @endauth

    {{-- Toast Notifications --}}
    <div id="toast-container" class="fixed top-4 right-4 z-50 space-y-2"></div>

    @stack('scripts')
</body>
</html>
```

---

## 3. Mobile Dashboard

**File**: `resources/views/student/dashboard-mobile.blade.php`

```blade
@extends('layouts.app-mobile')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6 space-y-4">
    
    {{-- Welcome Header (Collapsed) --}}
    <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-200">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 bg-primary-100 rounded-full flex items-center justify-center flex-shrink-0">
                <span class="text-primary-600 font-bold text-lg">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </span>
            </div>
            <div class="flex-1 min-w-0">
                <h1 class="text-lg font-semibold text-gray-900 truncate">
                    Hi, {{ explode(' ', auth()->user()->name)[0] }}!
                </h1>
                <p class="text-sm text-gray-500">Welcome back</p>
            </div>
            <div class="flex-shrink-0">
                <div class="w-12 h-12 relative">
                    <svg class="w-full h-full transform -rotate-90">
                        <circle cx="24" cy="24" r="20" stroke="#e5e7eb" stroke-width="4" fill="none"/>
                        <circle cx="24" cy="24" r="20" 
                                stroke="#667eea" 
                                stroke-width="4" 
                                fill="none"
                                stroke-dasharray="125.6"
                                stroke-dashoffset="{{ 125.6 - (125.6 * ($profileCompletion ?? 75) / 100) }}"
                                stroke-linecap="round"/>
                    </svg>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <span class="text-xs font-bold text-gray-700">{{ $profileCompletion ?? 75 }}%</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Primary CTA --}}
    @if(($profileCompletion ?? 75) < 100)
        <a href="{{ route('profile.edit') }}" class="block">
            <div class="bg-gradient-to-r from-primary-600 to-primary-700 rounded-2xl p-6 shadow-lg">
                <div class="flex items-center justify-between text-white">
                    <div>
                        <h2 class="text-xl font-bold mb-1">Complete Your Profile</h2>
                        <p class="text-primary-100 text-sm">Get better job matches</p>
                    </div>
                    <i class="fas fa-arrow-right text-2xl"></i>
                </div>
            </div>
        </a>
    @else
        <a href="{{ route('recommendations.index') }}" class="block">
            <div class="bg-gradient-to-r from-green-600 to-green-700 rounded-2xl p-6 shadow-lg">
                <div class="flex items-center justify-between text-white">
                    <div>
                        <h2 class="text-xl font-bold mb-1">Find Your Next Opportunity</h2>
                        <p class="text-green-100 text-sm">{{ $recommendations ?? 0 }} jobs waiting</p>
                    </div>
                    <i class="fas fa-briefcase text-2xl"></i>
                </div>
            </div>
        </a>
    @endif

    {{-- Key Metrics --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        {{-- Applications --}}
        <a href="{{ route('my-applications') }}" class="block">
            <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-200 hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between mb-2">
                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-file-alt text-blue-600"></i>
                    </div>
                    <span class="text-2xl font-bold text-gray-900">{{ $appliedJobs ?? 0 }}</span>
                </div>
                <p class="text-sm text-gray-600">Applications</p>
            </div>
        </a>

        {{-- Interviews --}}
        <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-200">
            <div class="flex items-center justify-between mb-2">
                <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-video text-green-600"></i>
                </div>
                <span class="text-2xl font-bold text-gray-900">{{ $interviews ?? 0 }}</span>
            </div>
            <p class="text-sm text-gray-600">Interviews</p>
        </div>

        {{-- Profile Views --}}
        <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-200">
            <div class="flex items-center justify-between mb-2">
                <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-eye text-purple-600"></i>
                </div>
                <span class="text-2xl font-bold text-gray-900">{{ $profileViews ?? 0 }}</span>
            </div>
            <p class="text-sm text-gray-600">Profile Views</p>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <a href="{{ route('recommendations.index') }}" class="block">
            <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-200 hover:shadow-md transition-shadow">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-star text-yellow-600 text-xl"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="font-semibold text-gray-900">Job Matches</h3>
                        <p class="text-sm text-gray-500">{{ $recommendations ?? 0 }} available</p>
                    </div>
                    <i class="fas fa-chevron-right text-gray-400"></i>
                </div>
            </div>
        </a>

        <a href="{{ route('my-applications') }}" class="block">
            <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-200 hover:shadow-md transition-shadow">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-clipboard-list text-blue-600 text-xl"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="font-semibold text-gray-900">Track Applications</h3>
                        <p class="text-sm text-gray-500">{{ $appliedJobs ?? 0 }} in progress</p>
                    </div>
                    <i class="fas fa-chevron-right text-gray-400"></i>
                </div>
            </div>
        </a>
    </div>

    {{-- Recent Activity (Collapsed) --}}
    <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-200">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-gray-900">Recent Activity</h2>
            <button class="text-sm text-primary-600 font-medium">View All</button>
        </div>
        <div class="space-y-3">
            {{-- Activity items (show 3 max) --}}
            <div class="flex items-start gap-3">
                <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-paper-plane text-blue-600 text-sm"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm text-gray-900 font-medium">Applied to Software Engineer</p>
                    <p class="text-xs text-gray-500">2 hours ago</p>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
```

---

## 4. Multi-Step Form Component

**File**: `resources/views/components/form-wizard.blade.php`

```blade
<div class="form-wizard" data-current-step="1" data-total-steps="4">
    {{-- Progress Stepper --}}
    <div class="progress-stepper mb-8">
        <div class="flex items-center justify-between">
            @for($i = 1; $i <= 4; $i++)
                <div class="step-item flex-1 {{ $i === 1 ? 'active' : '' }}" data-step="{{ $i }}">
                    <div class="step-circle">{{ $i }}</div>
                    @if($i < 4)
                        <div class="step-line"></div>
                    @endif
                </div>
            @endfor
        </div>
        <div class="step-labels flex justify-between mt-2">
            <span class="text-xs text-gray-600">Basic</span>
            <span class="text-xs text-gray-600">Skills</span>
            <span class="text-xs text-gray-600">Interests</span>
            <span class="text-xs text-gray-600">Resume</span>
        </div>
    </div>

    {{-- Form Steps --}}
    <form id="wizardForm" class="space-y-6">
        @csrf
        
        {{-- Step 1: Basic Info --}}
        <div class="form-step active" data-step="1">
            <h2 class="text-xl font-semibold mb-4">Basic Information</h2>
            
            <div class="space-y-4">
                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="name" class="form-input" required />
                </div>

                <div class="form-group">
                    <label class="form-label">Academic Background</label>
                    <textarea name="academic_background" class="form-textarea" rows="3"></textarea>
                </div>
            </div>
        </div>

        {{-- Step 2: Skills --}}
        <div class="form-step hidden" data-step="2">
            <h2 class="text-xl font-semibold mb-4">Your Skills</h2>
            
            <div class="form-group">
                <label class="form-label">Skills (comma-separated)</label>
                <input type="text" name="skills" class="form-input" 
                       placeholder="e.g., Python, JavaScript, React" />
            </div>
        </div>

        {{-- Step 3: Career Interests --}}
        <div class="form-step hidden" data-step="3">
            <h2 class="text-xl font-semibold mb-4">Career Interests</h2>
            
            <div class="form-group">
                <label class="form-label">What are you looking for?</label>
                <textarea name="career_interests" class="form-textarea" rows="4"></textarea>
            </div>
        </div>

        {{-- Step 4: Resume --}}
        <div class="form-step hidden" data-step="4">
            <h2 class="text-xl font-semibold mb-4">Upload Resume</h2>
            
            <div class="form-group">
                <label class="form-label">Resume (PDF)</label>
                <input type="file" name="resume" accept=".pdf" class="form-input" />
            </div>
        </div>

        {{-- Navigation Buttons --}}
        <div class="flex gap-3">
            <button type="button" id="prevBtn" class="btn btn-secondary flex-1" disabled>
                <i class="fas fa-arrow-left mr-2"></i>
                Back
            </button>
            <button type="button" id="nextBtn" class="btn btn-primary flex-1">
                Next
                <i class="fas fa-arrow-right ml-2"></i>
            </button>
            <button type="submit" id="submitBtn" class="btn btn-primary flex-1 hidden">
                <i class="fas fa-check mr-2"></i>
                Submit
            </button>
        </div>
    </form>
</div>

<style>
.progress-stepper {
    @apply relative;
}

.step-item {
    @apply relative flex items-center;
}

.step-circle {
    @apply w-10 h-10 rounded-full border-2 border-gray-300 bg-white;
    @apply flex items-center justify-center font-semibold text-gray-500;
    @apply transition-all duration-300;
}

.step-item.active .step-circle,
.step-item.completed .step-circle {
    @apply border-primary-600 bg-primary-600 text-white;
}

.step-line {
    @apply flex-1 h-0.5 bg-gray-300 mx-2;
    @apply transition-all duration-300;
}

.step-item.completed .step-line {
    @apply bg-primary-600;
}

.form-step {
    @apply transition-all duration-300;
}

.form-step.hidden {
    @apply hidden;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const wizard = document.querySelector('.form-wizard');
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const submitBtn = document.getElementById('submitBtn');
    const form = document.getElementById('wizardForm');
    
    let currentStep = 1;
    const totalSteps = parseInt(wizard.dataset.totalSteps);

    function showStep(step) {
        // Hide all steps
        document.querySelectorAll('.form-step').forEach(s => s.classList.add('hidden'));
        
        // Show current step
        document.querySelector(`.form-step[data-step="${step}"]`).classList.remove('hidden');
        
        // Update stepper
        document.querySelectorAll('.step-item').forEach((item, index) => {
            if (index + 1 < step) {
                item.classList.add('completed');
                item.classList.remove('active');
            } else if (index + 1 === step) {
                item.classList.add('active');
                item.classList.remove('completed');
            } else {
                item.classList.remove('active', 'completed');
            }
        });

        // Update buttons
        prevBtn.disabled = step === 1;
        
        if (step === totalSteps) {
            nextBtn.classList.add('hidden');
            submitBtn.classList.remove('hidden');
        } else {
            nextBtn.classList.remove('hidden');
            submitBtn.classList.add('hidden');
        }

        // Auto-save to localStorage
        saveProgress();
    }

    function saveProgress() {
        const formData = new FormData(form);
        const data = Object.fromEntries(formData);
        localStorage.setItem('formWizardProgress', JSON.stringify({
            step: currentStep,
            data: data
        }));
    }

    function loadProgress() {
        const saved = localStorage.getItem('formWizardProgress');
        if (saved) {
            const { step, data } = JSON.parse(saved);
            currentStep = step;
            
            // Restore form values
            Object.keys(data).forEach(key => {
                const input = form.querySelector(`[name="${key}"]`);
                if (input) input.value = data[key];
            });
            
            showStep(currentStep);
        }
    }

    prevBtn.addEventListener('click', () => {
        if (currentStep > 1) {
            currentStep--;
            showStep(currentStep);
        }
    });

    nextBtn.addEventListener('click', () => {
        if (currentStep < totalSteps) {
            currentStep++;
            showStep(currentStep);
        }
    });

    form.addEventListener('submit', (e) => {
        e.preventDefault();
        // Submit form via AJAX or normal submission
        form.submit();
        localStorage.removeItem('formWizardProgress');
    });

    // Load saved progress on page load
    loadProgress();
});
</script>
```

---

## 5. Tailwind Configuration

**File**: `tailwind.config.js`

```javascript
export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
  ],
  theme: {
    extend: {
      colors: {
        primary: {
          50: '#f5f7ff',
          100: '#ebf0ff',
          200: '#d6e0ff',
          300: '#b3c7ff',
          400: '#8ca5ff',
          500: '#667eea',
          600: '#5a67d8',
          700: '#4c51bf',
          800: '#434190',
          900: '#3c366b',
        },
      },
      spacing: {
        'safe': 'env(safe-area-inset-bottom)',
      },
      fontSize: {
        'xs': ['0.75rem', { lineHeight: '1rem' }],
        'sm': ['0.875rem', { lineHeight: '1.25rem' }],
        'base': ['1rem', { lineHeight: '1.5rem' }],
        'lg': ['1.125rem', { lineHeight: '1.75rem' }],
        'xl': ['1.25rem', { lineHeight: '1.75rem' }],
        '2xl': ['1.5rem', { lineHeight: '2rem' }],
      },
    },
  },
  plugins: [],
}
```

---

## 6. Performance Optimization Script

**File**: `resources/js/lazy-load.js`

```javascript
// Lazy Load Images
document.addEventListener('DOMContentLoaded', function() {
    const lazyImages = document.querySelectorAll('img[loading="lazy"]');
    
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    if (img.dataset.src) {
                        img.src = img.dataset.src;
                        img.removeAttribute('data-src');
                    }
                    observer.unobserve(img);
                }
            });
        });

        lazyImages.forEach(img => imageObserver.observe(img));
    } else {
        // Fallback for older browsers
        lazyImages.forEach(img => {
            if (img.dataset.src) {
                img.src = img.dataset.src;
            }
        });
    }
});

// Toast Notifications
function showToast(message, type = 'success') {
    const container = document.getElementById('toast-container');
    const toast = document.createElement('div');
    
    const colors = {
        success: 'bg-green-500',
        error: 'bg-red-500',
        info: 'bg-blue-500',
        warning: 'bg-yellow-500'
    };
    
    toast.className = `${colors[type]} text-white px-4 py-3 rounded-lg shadow-lg flex items-center gap-2 animate-slide-in`;
    toast.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'info-circle'}"></i>
        <span>${message}</span>
    `;
    
    container.appendChild(toast);
    
    setTimeout(() => {
        toast.classList.add('animate-slide-out');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Export for use in other scripts
window.showToast = showToast;
```

---

## Implementation Checklist

### Week 1: Foundation
- [ ] Create `app-mobile.blade.php` layout
- [ ] Implement bottom navigation component
- [ ] Update Tailwind configuration
- [ ] Test on multiple devices

### Week 2: Dashboard
- [ ] Create `dashboard-mobile.blade.php`
- [ ] Simplify metrics display
- [ ] Add responsive breakpoints
- [ ] Test performance

### Week 3: Forms
- [ ] Implement form wizard component
- [ ] Create multi-step profile edit
- [ ] Add auto-save functionality
- [ ] Test validation

### Week 4: Lists & Cards
- [ ] Create card components
- [ ] Redesign recommendations page
- [ ] Implement filters
- [ ] Add pagination

### Week 5: Polish
- [ ] Fix resume display
- [ ] Optimize images
- [ ] Add lazy loading
- [ ] Performance audit

### Week 6: Testing
- [ ] Cross-browser testing
- [ ] Accessibility audit
- [ ] User testing
- [ ] Bug fixes

---

**Ready to implement!** Start with Phase 1 and iterate based on feedback.
