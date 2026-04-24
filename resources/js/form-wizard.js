/**
 * Multi-Step Form Wizard
 * Mobile-first form navigation with auto-save
 */

class FormWizard {
    constructor(container) {
        this.container = container;
        this.currentStep = 1;
        this.totalSteps = parseInt(container.dataset.totalSteps) || 4;
        this.form = container.querySelector('form');
        this.storageKey = 'formWizard_' + (container.dataset.formId || 'default');
        
        this.init();
    }

    init() {
        this.cacheElements();
        this.attachEventListeners();
        this.loadProgress();
        this.showStep(this.currentStep);
    }

    cacheElements() {
        this.prevBtn = this.container.querySelector('#prevBtn');
        this.nextBtn = this.container.querySelector('#nextBtn');
        this.submitBtn = this.container.querySelector('#submitBtn');
        this.steps = this.container.querySelectorAll('.form-step');
        this.stepIndicators = this.container.querySelectorAll('.step-item');
    }

    attachEventListeners() {
        // Navigation buttons
        this.prevBtn?.addEventListener('click', () => this.previousStep());
        this.nextBtn?.addEventListener('click', () => this.nextStep());
        
        // Form submission
        this.form?.addEventListener('submit', (e) => this.handleSubmit(e));
        
        // Auto-save on input change
        this.form?.addEventListener('input', () => this.autoSave());
        
        // Prevent accidental navigation away
        window.addEventListener('beforeunload', (e) => {
            if (this.hasUnsavedChanges()) {
                e.preventDefault();
                e.returnValue = '';
            }
        });
    }

    showStep(step) {
        // Validate step number
        if (step < 1 || step > this.totalSteps) return;
        
        this.currentStep = step;
        
        // Hide all steps
        this.steps.forEach(s => s.classList.add('hidden'));
        
        // Show current step
        const currentStepEl = this.container.querySelector(`.form-step[data-step="${step}"]`);
        if (currentStepEl) {
            currentStepEl.classList.remove('hidden');
            
            // Focus first input
            const firstInput = currentStepEl.querySelector('input, textarea, select');
            if (firstInput && window.innerWidth > 768) {
                setTimeout(() => firstInput.focus(), 100);
            }
        }
        
        // Update step indicators
        this.updateStepIndicators();
        
        // Update buttons
        this.updateButtons();
        
        // Scroll to top
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    updateStepIndicators() {
        this.stepIndicators.forEach((indicator, index) => {
            const stepNum = index + 1;
            
            indicator.classList.remove('active', 'completed');
            
            if (stepNum < this.currentStep) {
                indicator.classList.add('completed');
            } else if (stepNum === this.currentStep) {
                indicator.classList.add('active');
            }
        });
    }

    updateButtons() {
        // Previous button
        if (this.prevBtn) {
            this.prevBtn.disabled = this.currentStep === 1;
        }
        
        // Next/Submit buttons
        if (this.currentStep === this.totalSteps) {
            this.nextBtn?.classList.add('hidden');
            this.submitBtn?.classList.remove('hidden');
        } else {
            this.nextBtn?.classList.remove('hidden');
            this.submitBtn?.classList.add('hidden');
        }
    }

    previousStep() {
        if (this.currentStep > 1) {
            this.showStep(this.currentStep - 1);
        }
    }

    nextStep() {
        // Validate current step
        if (this.validateCurrentStep()) {
            if (this.currentStep < this.totalSteps) {
                this.showStep(this.currentStep + 1);
            }
        }
    }

    validateCurrentStep() {
        const currentStepEl = this.container.querySelector(`.form-step[data-step="${this.currentStep}"]`);
        if (!currentStepEl) return true;
        
        const inputs = currentStepEl.querySelectorAll('input[required], textarea[required], select[required]');
        let isValid = true;
        
        inputs.forEach(input => {
            // Clear previous errors
            this.clearError(input);
            
            // Check validity
            if (!input.value.trim()) {
                this.showError(input, 'This field is required');
                isValid = false;
            } else if (input.type === 'email' && !this.isValidEmail(input.value)) {
                this.showError(input, 'Please enter a valid email');
                isValid = false;
            }
        });
        
        return isValid;
    }

    showError(input, message) {
        input.classList.add('border-red-500');
        
        let errorEl = input.parentElement.querySelector('.form-error');
        if (errorEl) {
            errorEl.textContent = message;
            errorEl.classList.remove('hidden');
        }
    }

    clearError(input) {
        input.classList.remove('border-red-500');
        
        let errorEl = input.parentElement.querySelector('.form-error');
        if (errorEl) {
            errorEl.classList.add('hidden');
        }
    }

    isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    autoSave() {
        clearTimeout(this.saveTimeout);
        this.saveTimeout = setTimeout(() => {
            this.saveProgress();
        }, 1000); // Save after 1 second of inactivity
    }

    saveProgress() {
        if (!this.form) return;
        
        const formData = new FormData(this.form);
        const data = {};
        
        formData.forEach((value, key) => {
            data[key] = value;
        });
        
        const progress = {
            step: this.currentStep,
            data: data,
            timestamp: Date.now()
        };
        
        try {
            localStorage.setItem(this.storageKey, JSON.stringify(progress));
            this.showSaveIndicator();
        } catch (e) {
            console.error('Failed to save progress:', e);
        }
    }

    loadProgress() {
        try {
            const saved = localStorage.getItem(this.storageKey);
            if (!saved) return;
            
            const progress = JSON.parse(saved);
            
            // Check if saved data is not too old (24 hours)
            const age = Date.now() - progress.timestamp;
            if (age > 24 * 60 * 60 * 1000) {
                localStorage.removeItem(this.storageKey);
                return;
            }
            
            // Restore form values
            Object.keys(progress.data).forEach(key => {
                const input = this.form.querySelector(`[name="${key}"]`);
                if (input) {
                    input.value = progress.data[key];
                }
            });
            
            // Restore step
            this.currentStep = progress.step || 1;
            
            // Show notification
            this.showToast('Previous progress restored', 'info');
        } catch (e) {
            console.error('Failed to load progress:', e);
        }
    }

    clearProgress() {
        try {
            localStorage.removeItem(this.storageKey);
        } catch (e) {
            console.error('Failed to clear progress:', e);
        }
    }

    hasUnsavedChanges() {
        // Check if form has been modified
        const saved = localStorage.getItem(this.storageKey);
        return saved !== null;
    }

    handleSubmit(e) {
        e.preventDefault();
        
        // Validate all steps
        let allValid = true;
        for (let i = 1; i <= this.totalSteps; i++) {
            this.currentStep = i;
            if (!this.validateCurrentStep()) {
                allValid = false;
                this.showStep(i); // Show first invalid step
                break;
            }
        }
        
        if (allValid) {
            // Clear saved progress
            this.clearProgress();
            
            // Submit form
            this.form.submit();
        }
    }

    showSaveIndicator() {
        // Show a subtle save indicator
        const indicator = document.createElement('div');
        indicator.className = 'fixed bottom-24 right-4 bg-green-500 text-white px-3 py-1 rounded-full text-xs shadow-lg';
        indicator.textContent = 'Saved';
        document.body.appendChild(indicator);
        
        setTimeout(() => {
            indicator.style.opacity = '0';
            indicator.style.transition = 'opacity 0.3s';
            setTimeout(() => indicator.remove(), 300);
        }, 2000);
    }

    showToast(message, type = 'info') {
        if (window.showToast) {
            window.showToast(message, type);
        }
    }
}

// Initialize all form wizards on page load
document.addEventListener('DOMContentLoaded', function() {
    const wizards = document.querySelectorAll('.form-wizard');
    wizards.forEach(wizard => new FormWizard(wizard));
});

// Export for use in other scripts
window.FormWizard = FormWizard;
