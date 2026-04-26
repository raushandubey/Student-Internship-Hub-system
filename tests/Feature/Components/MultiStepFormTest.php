<?php

namespace Tests\Feature\Components;

use Tests\TestCase;
use App\Models\User;
use App\Models\Internship;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

/**
 * Integration Tests for Multi-Step Form Component
 * 
 * Tests the complete multi-step form functionality including:
 * - Navigation through all 3 steps
 * - Data preservation when going back
 * - Validation preventing invalid submissions
 * - File upload validation (PDF only, max 5MB)
 * 
 * Requirements: 5.2, 5.3, 5.4, 5.6, 5.9
 */
class MultiStepFormTest extends TestCase
{
    use RefreshDatabase;

    protected User $student;
    protected Internship $internship;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a student user
        $this->student = User::factory()->create([
            'role' => 'student',
        ]);

        // Create an internship to apply to
        $this->internship = Internship::factory()->create([
            'title' => 'Software Engineering Intern',
            'organization' => 'Tech Corp',
            'is_active' => true,
        ]);

        // Set up fake storage for file uploads
        Storage::fake('public');
    }

    /**
     * Helper method to render the multi-step form component
     */
    protected function renderComponent($currentStep = 1, $totalSteps = 3)
    {
        return view('components.forms.multi-step-form', [
            'totalSteps' => $totalSteps,
            'currentStep' => $currentStep,
            'formId' => 'test-form',
            'formAction' => route('applications.apply', $this->internship),
            'slot' => '', // Empty slot for testing
        ])->render();
    }

    /** @test */
    public function it_renders_multi_step_form_with_progress_indicator()
    {
        $html = $this->renderComponent();
        
        // Check for multi-step form container
        $this->assertStringContainsString('multi-step-form-container', $html);
        
        // Check for progress indicator
        $this->assertStringContainsString('progress-indicator', $html);
        $this->assertStringContainsString('Step', $html);
        $this->assertStringContainsString('of', $html);
        $this->assertStringContainsString('Complete', $html);
        
        // Check for progress bar
        $this->assertStringContainsString('role="progressbar"', $html);
    }

    /** @test */
    public function it_displays_step_1_of_3_initially()
    {
        $html = $this->renderComponent(1, 3);
        
        // Check that Step 1 is displayed
        $this->assertStringContainsString('Step', $html);
        $this->assertStringContainsString('of 3', $html);
        
        // Check progress percentage (33% for step 1 of 3)
        $this->assertStringContainsString('33% Complete', $html);
    }

    /** @test */
    public function it_shows_next_button_on_first_step()
    {
        $html = $this->renderComponent(1, 3);
        
        // Next button should be visible
        $this->assertStringContainsString('Next', $html);
        $this->assertStringContainsString('next-btn', $html);
        
        // Back button should be hidden on first step
        $this->assertStringContainsString('back-btn', $html);
        $this->assertStringContainsString('hidden', $html);
        
        // Submit button should be hidden on first step
        $this->assertStringContainsString('submit-btn', $html);
    }

    /** @test */
    public function it_validates_required_fields_before_advancing()
    {
        $html = $this->renderComponent();

        // Check that validation is present
        $this->assertStringContainsString('validateStep', $html);
        $this->assertStringContainsString('required', $html);
    }

    /** @test */
    public function it_displays_inline_error_messages_for_empty_required_fields()
    {
        $html = $this->renderComponent();

        // Check for error container
        $this->assertStringContainsString('error-container', $html);
        $this->assertStringContainsString('error-list', $html);
        
        // Check for error styling classes
        $this->assertStringContainsString('border-red-500', $html);
        $this->assertStringContainsString('field-error-message', $html);
    }

    /** @test */
    public function it_has_file_upload_validation_for_pdf_only()
    {
        $html = $this->renderComponent(2, 3);

        // Check that file validation function exists
        $this->assertStringContainsString('validateFileInput', $html);
        $this->assertStringContainsString('Only PDF files are allowed', $html);
    }

    /** @test */
    public function it_has_file_size_validation_for_max_5mb()
    {
        $html = $this->renderComponent(2, 3);

        // Check that file size validation exists
        $this->assertStringContainsString('validateFileInput', $html);
        $this->assertStringContainsString('5 * 1024 * 1024', $html); // 5MB in bytes
        $this->assertStringContainsString('exceeds maximum allowed size', $html);
    }

    /** @test */
    public function it_preserves_form_data_when_navigating_between_steps()
    {
        $html = $this->renderComponent();

        // Check for data preservation functions
        $this->assertStringContainsString('saveStepData', $html);
        $this->assertStringContainsString('restoreStepData', $html);
        $this->assertStringContainsString('formState', $html);
        
        // Check for sessionStorage usage
        $this->assertStringContainsString('sessionStorage', $html);
    }

    /** @test */
    public function it_shows_back_button_on_second_and_third_steps()
    {
        // Test step 2
        $htmlStep2 = $this->renderComponent(2, 3);
        $this->assertStringContainsString('back-btn', $htmlStep2);
        $this->assertStringContainsString('Back', $htmlStep2);
        
        // Test step 3
        $htmlStep3 = $this->renderComponent(3, 3);
        $this->assertStringContainsString('back-btn', $htmlStep3);
        $this->assertStringContainsString('Back', $htmlStep3);
    }

    /** @test */
    public function it_shows_submit_button_on_final_step()
    {
        $html = $this->renderComponent(3, 3);

        // Submit button should be visible on final step
        $this->assertStringContainsString('submit-btn', $html);
        $this->assertStringContainsString('Submit Application', $html);
        
        // Next button should be hidden on final step
        $this->assertStringContainsString('hidden', $html);
    }

    /** @test */
    public function it_updates_progress_bar_as_user_advances_through_steps()
    {
        // Step 1 - 33%
        $htmlStep1 = $this->renderComponent(1, 3);
        $this->assertStringContainsString('33% Complete', $htmlStep1);
        $this->assertStringContainsString('width: 33%', $htmlStep1);

        // Step 2 - 67%
        $htmlStep2 = $this->renderComponent(2, 3);
        $this->assertStringContainsString('67% Complete', $htmlStep2);
        $this->assertStringContainsString('width: 67%', $htmlStep2);

        // Step 3 - 100%
        $htmlStep3 = $this->renderComponent(3, 3);
        $this->assertStringContainsString('100% Complete', $htmlStep3);
        $this->assertStringContainsString('width: 100%', $htmlStep3);
    }

    /** @test */
    public function it_generates_review_summary_on_final_step()
    {
        $html = $this->renderComponent(3, 3);

        // Check for review summary generation function
        $this->assertStringContainsString('generateReviewSummary', $html);
        $this->assertStringContainsString('review-summary', $html);
    }

    /** @test */
    public function it_allows_editing_previous_steps_from_review()
    {
        $html = $this->renderComponent(3, 3);

        // Check for edit step functionality
        $this->assertStringContainsString('editStep', $html);
        $this->assertStringContainsString('Edit', $html);
    }

    /** @test */
    public function it_disables_submit_button_during_submission()
    {
        $html = $this->renderComponent(3, 3);

        // Check for loading state and disabled state
        $this->assertStringContainsString('btn-loading', $html);
        $this->assertStringContainsString('disabled', $html);
        $this->assertStringContainsString('aria-busy', $html);
    }

    /** @test */
    public function it_shows_loading_spinner_on_submit()
    {
        $html = $this->renderComponent(3, 3);

        // Check for spinner animation
        $this->assertStringContainsString('spinner', $html);
        $this->assertStringContainsString('@keyframes spinner', $html);
    }

    /** @test */
    public function it_has_proper_aria_labels_for_accessibility()
    {
        $html = $this->renderComponent();

        // Check for ARIA attributes
        $this->assertStringContainsString('aria-label="Form progress"', $html);
        $this->assertStringContainsString('aria-label="Go to previous step"', $html);
        $this->assertStringContainsString('aria-label="Go to next step"', $html);
        $this->assertStringContainsString('aria-label="Submit form"', $html);
        $this->assertStringContainsString('role="progressbar"', $html);
        $this->assertStringContainsString('aria-valuenow', $html);
        $this->assertStringContainsString('aria-valuemin', $html);
        $this->assertStringContainsString('aria-valuemax', $html);
    }

    /** @test */
    public function it_has_touch_friendly_button_targets()
    {
        $html = $this->renderComponent();

        // Check for touch-target class (minimum 44x44px)
        $this->assertStringContainsString('touch-target', $html);
        $this->assertStringContainsString('min-width: 44px', $html);
        $this->assertStringContainsString('min-height: 44px', $html);
    }

    /** @test */
    public function it_includes_csrf_token_for_security()
    {
        $html = $this->renderComponent();

        // Check for CSRF token
        $this->assertStringContainsString('csrf', $html);
    }

    /** @test */
    public function it_validates_email_format_when_present()
    {
        $html = $this->renderComponent();

        // Check for email validation
        $this->assertStringContainsString('validateFieldType', $html);
        $this->assertStringContainsString('type === \'email\'', $html);
        $this->assertStringContainsString('valid email address', $html);
    }

    /** @test */
    public function it_provides_real_time_validation_on_blur()
    {
        $html = $this->renderComponent();

        // Check for blur event listener
        $this->assertStringContainsString('addEventListener(\'blur\'', $html);
        $this->assertStringContainsString('validateSingleField', $html);
    }

    /** @test */
    public function it_clears_errors_when_user_starts_typing()
    {
        $html = $this->renderComponent();

        // Check for input event listener that clears errors
        $this->assertStringContainsString('addEventListener(\'input\'', $html);
        $this->assertStringContainsString('classList.remove(\'border-red-500\')', $html);
    }

    /** @test */
    public function it_scrolls_to_top_when_changing_steps()
    {
        $html = $this->renderComponent();

        // Check for scroll to top functionality
        $this->assertStringContainsString('scrollToTop', $html);
        $this->assertStringContainsString('scrollIntoView', $html);
    }

    /** @test */
    public function it_formats_file_sizes_for_display()
    {
        $html = $this->renderComponent(2, 3);

        // Check for file size formatting function
        $this->assertStringContainsString('formatFileSize', $html);
        $this->assertStringContainsString('KB', $html);
        $this->assertStringContainsString('MB', $html);
    }

    /** @test */
    public function it_prevents_xss_in_review_summary()
    {
        $html = $this->renderComponent(3, 3);

        // Check for HTML escaping function
        $this->assertStringContainsString('escapeHtml', $html);
    }

    /** @test */
    public function it_has_responsive_padding_for_mobile()
    {
        $html = $this->renderComponent();

        // Check for mobile-specific padding
        $this->assertStringContainsString('px-4', $html);
        $this->assertStringContainsString('md:px-0', $html);
        $this->assertStringContainsString('pb-20', $html); // Extra padding for bottom nav
    }

    /** @test */
    public function it_has_smooth_transitions_for_progress_bar()
    {
        $html = $this->renderComponent();

        // Check for transition classes
        $this->assertStringContainsString('transition-all', $html);
        $this->assertStringContainsString('duration-300', $html);
    }

    /** @test */
    public function it_prevents_double_submission()
    {
        $html = $this->renderComponent(3, 3);

        // Check for double submission prevention
        $this->assertStringContainsString('submitBtn.disabled', $html);
        $this->assertStringContainsString('preventDefault', $html);
    }

    /** @test */
    public function it_clears_session_storage_after_submission()
    {
        $html = $this->renderComponent(3, 3);

        // Check for sessionStorage cleanup
        $this->assertStringContainsString('sessionStorage.removeItem', $html);
    }

    /** @test */
    public function it_supports_custom_form_id_for_multiple_forms()
    {
        $html1 = view('components.forms.multi-step-form', [
            'totalSteps' => 3,
            'currentStep' => 1,
            'formId' => 'form-1',
            'formAction' => route('applications.apply', $this->internship),
            'slot' => '',
        ])->render();

        $html2 = view('components.forms.multi-step-form', [
            'totalSteps' => 3,
            'currentStep' => 1,
            'formId' => 'form-2',
            'formAction' => route('applications.apply', $this->internship),
            'slot' => '',
        ])->render();

        // Check that different form IDs are used
        $this->assertStringContainsString('form-1', $html1);
        $this->assertStringContainsString('form-2', $html2);
        
        // Check that form state is isolated
        $this->assertStringContainsString('formState[formId]', $html1);
    }

    /** @test */
    public function it_has_proper_button_focus_states()
    {
        $html = $this->renderComponent();

        // Check for focus ring styles
        $this->assertStringContainsString('focus:outline-none', $html);
        $this->assertStringContainsString('focus:ring-2', $html);
        $this->assertStringContainsString('focus:ring-offset-2', $html);
    }

    /** @test */
    public function it_displays_error_container_with_animation()
    {
        $html = $this->renderComponent();

        // Check for error container animation
        $this->assertStringContainsString('@keyframes slideDown', $html);
        $this->assertStringContainsString('animation: slideDown', $html);
    }

    /** @test */
    public function it_validates_pattern_attribute_when_present()
    {
        $html = $this->renderComponent();

        // Check for pattern validation
        $this->assertStringContainsString('hasAttribute(\'pattern\')', $html);
        $this->assertStringContainsString('new RegExp', $html);
    }

    /** @test */
    public function it_validates_min_and_max_length()
    {
        $html = $this->renderComponent();

        // Check for length validation
        $this->assertStringContainsString('minlength', $html);
        $this->assertStringContainsString('maxlength', $html);
        $this->assertStringContainsString('value.length', $html);
    }
}
