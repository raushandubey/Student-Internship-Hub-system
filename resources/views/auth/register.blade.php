@extends('layouts.app')

@section('content')
<div class="signup-fullwidth-page">
    <!-- Background Elements -->
    <div class="bg-decorations">
        <div class="decoration decoration-1"></div>
        <div class="decoration decoration-2"></div>
        <div class="decoration decoration-3"></div>
    </div>
    
    <!-- Main Content -->
    <div class="signup-content">
        <!-- Header Section -->
        <div class="signup-header">
            <div class="brand-logo">
                <i class="fas fa-briefcase"></i>
            </div>
            <h1 class="main-title">InternshipHub</h1>
            <p class="tagline">Your Gateway to Success</p>
        </div>
        
        <!-- Signup Form -->
        <div class="signup-form-container">
            <div class="form-header">
                <h2>Create Your Account</h2>
                <p>Join thousands of students finding their dream internships</p>
            </div>
            
            <form method="POST" action="{{ route('register') }}" class="signup-form">
                @csrf
                
                <!-- Name Field -->
                <div class="form-group">
                    <label for="name" class="form-label">Full Name</label>
                    <div class="input-wrapper">
                        <i class="fas fa-user input-icon"></i>
                        <input 
                            type="text" 
                            id="name" 
                            name="name" 
                            class="form-input @error('name') input-error @enderror"
                            placeholder="Enter your full name"
                            value="{{ old('name') }}"
                            required>
                    </div>
                    @error('name')
                        <span class="error-text">{{ $message }}</span>
                    @enderror
                </div>
                
                <!-- Email Field -->
                <div class="form-group">
                    <label for="email" class="form-label">Email Address</label>
                    <div class="input-wrapper">
                        <i class="fas fa-envelope input-icon"></i>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            class="form-input @error('email') input-error @enderror"
                            placeholder="Enter your email address"
                            value="{{ old('email') }}"
                            required>
                    </div>
                    @error('email')
                        <span class="error-text">{{ $message }}</span>
                    @enderror
                </div>
                
                <!-- Role Field -->
                <div class="form-group">
                    <label for="role" class="form-label">Account Type</label>
                    <div class="input-wrapper">
                        <i class="fas fa-user-tag input-icon"></i>
                        <select 
                            id="role" 
                            name="role" 
                            class="form-input form-select @error('role') input-error @enderror"
                            required>
                            <option value="" disabled selected>Choose your role</option>
                            <option value="student" {{ old('role') == 'student' ? 'selected' : '' }}>Student</option>
                            <!-- <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>Admin</option> -->
                        </select>
                        <i class="fas fa-chevron-down select-arrow"></i>
                    </div>
                    @error('role')
                        <span class="error-text">{{ $message }}</span>
                    @enderror
                </div>
                
                <!-- Password Field -->
                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="form-input @error('password') input-error @enderror"
                            placeholder="Create a strong password"
                            required>
                        <button type="button" class="password-toggle" onclick="togglePassword('password')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    @error('password')
                        <span class="error-text">{{ $message }}</span>
                    @enderror
                    
                    <!-- Password Strength Indicator -->
                    <div class="password-strength" id="passwordStrength">
                        <div class="strength-bar">
                            <div class="strength-fill" id="strengthFill"></div>
                        </div>
                        <span class="strength-text" id="strengthText">Password strength</span>
                    </div>
                </div>
                
                <!-- Confirm Password Field -->
                <div class="form-group">
                    <label for="password_confirmation" class="form-label">Confirm Password</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input 
                            type="password" 
                            id="password_confirmation" 
                            name="password_confirmation" 
                            class="form-input @error('password_confirmation') input-error @enderror"
                            placeholder="Confirm your password"
                            required>
                        <button type="button" class="password-toggle" onclick="togglePassword('password_confirmation')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    @error('password_confirmation')
                        <span class="error-text">{{ $message }}</span>
                    @enderror
                    <div class="password-match" id="passwordMatch"></div>
                </div>
                
                <!-- Terms & Conditions -->
                <div class="terms-section">
                    <label class="checkbox-container">
                        <input type="checkbox" name="terms" required>
                        <span class="checkmark"></span>
                        <span class="checkbox-text">
                            I agree to the <a href="#" class="terms-link">Terms of Service</a> 
                            and <a href="#" class="terms-link">Privacy Policy</a>
                        </span>
                    </label>
                </div>
                
                <!-- Submit Button -->
                <button type="submit" class="submit-button">
                    <span class="button-text">Create Account</span>
                    <i class="fas fa-arrow-right button-icon"></i>
                </button>
                
                <!-- Login Link -->
                <div class="login-redirect">
                    <p>Already have an account? 
                        <a href="{{ route('login') }}" class="login-link">Sign In</a>
                    </p>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Main Container */
.signup-fullwidth-page {
    width: 100vw;
    min-height: 100vh;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    position: relative;
    overflow: hidden;
}

/* Background Decorations */
.bg-decorations {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
    z-index: 1;
}

.decoration {
    position: absolute;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.1);
    animation: float 20s infinite linear;
}

.decoration-1 {
    width: 300px;
    height: 300px;
    top: 10%;
    left: 5%;
    animation-delay: -5s;
}

.decoration-2 {
    width: 200px;
    height: 200px;
    top: 60%;
    right: 10%;
    animation-delay: -10s;
}

.decoration-3 {
    width: 150px;
    height: 150px;
    bottom: 20%;
    left: 70%;
    animation-delay: -15s;
}

@keyframes float {
    0% { transform: translateY(0px) rotate(0deg); }
    33% { transform: translateY(-20px) rotate(120deg); }
    66% { transform: translateY(20px) rotate(240deg); }
    100% { transform: translateY(0px) rotate(360deg); }
}

/* Main Content */
.signup-content {
    position: relative;
    z-index: 2;
    width: 100%;
    max-width: 1200px;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 4rem;
    align-items: center;
}

/* Header Section */
.signup-header {
    text-align: center;
    color: white;
    padding: 2rem;
}

.brand-logo {
    width: 100px;
    height: 100px;
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(20px);
    border: 3px solid rgba(255, 255, 255, 0.3);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 2rem;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
}

.brand-logo i {
    font-size: 3rem;
    color: white;
}

.main-title {
    font-size: 4rem;
    font-weight: 800;
    margin-bottom: 1rem;
    text-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    background: linear-gradient(45deg, #ffffff, #f0f0f0);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.tagline {
    font-size: 1.5rem;
    opacity: 0.9;
    font-weight: 500;
    text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
}

/* Form Container */
.signup-form-container {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(25px);
    border-radius: 24px;
    padding: 3rem;
    box-shadow: 0 30px 60px rgba(0, 0, 0, 0.2);
    border: 1px solid rgba(255, 255, 255, 0.3);
    max-height: 90vh;
    overflow-y: auto;
}

.form-header {
    text-align: center;
    margin-bottom: 2rem;
}

.form-header h2 {
    font-size: 2.5rem;
    font-weight: 700;
    color: #374151;
    margin-bottom: 0.5rem;
}

.form-header p {
    font-size: 1.1rem;
    color: #6B7280;
}

/* Form Styling */
.signup-form {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.form-label {
    font-size: 0.9rem;
    font-weight: 600;
    color: #374151;
    margin-bottom: 0.25rem;
}

.input-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}

.input-icon {
    position: absolute;
    left: 1rem;
    color: #9CA3AF;
    font-size: 1rem;
    z-index: 2;
}

.form-input {
    width: 100%;
    padding: 1rem 1rem 1rem 3rem;
    border: 2px solid #E5E7EB;
    border-radius: 12px;
    font-size: 1rem;
    background: #F9FAFB;
    color: #374151;
    transition: all 0.3s ease;
}

.form-input:focus {
    outline: none;
    border-color: #667eea;
    background: white;
    box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
    transform: translateY(-2px);
}

.form-input::placeholder {
    color: #9CA3AF;
}

.input-error {
    border-color: #EF4444;
    background: #FEF2F2;
}

/* Select Dropdown */
.form-select {
    padding-right: 3rem;
    appearance: none;
}

.select-arrow {
    position: absolute;
    right: 1rem;
    color: #9CA3AF;
    font-size: 0.9rem;
    pointer-events: none;
}

/* Password Toggle */
.password-toggle {
    position: absolute;
    right: 1rem;
    background: none;
    border: none;
    color: #9CA3AF;
    cursor: pointer;
    padding: 0.5rem;
    transition: all 0.3s ease;
}

.password-toggle:hover {
    color: #374151;
}

/* Error Text */
.error-text {
    color: #EF4444;
    font-size: 0.85rem;
    font-weight: 500;
    margin-top: 0.25rem;
}

/* Password Strength */
.password-strength {
    margin-top: 0.5rem;
}

.strength-bar {
    width: 100%;
    height: 4px;
    background: #E5E7EB;
    border-radius: 2px;
    overflow: hidden;
    margin-bottom: 0.3rem;
}

.strength-fill {
    height: 100%;
    width: 0%;
    transition: all 0.3s ease;
    border-radius: 2px;
}

.strength-text {
    font-size: 0.75rem;
    color: #6B7280;
    font-weight: 500;
}

/* Password Match */
.password-match {
    font-size: 0.85rem;
    margin-top: 0.3rem;
    font-weight: 500;
}

.password-match.match {
    color: #059669;
}

.password-match.no-match {
    color: #EF4444;
}

/* Terms Section */
.terms-section {
    margin: 1rem 0;
}

.checkbox-container {
    display: flex;
    align-items: flex-start;
    cursor: pointer;
    font-size: 0.9rem;
    color: #6B7280;
    user-select: none;
    line-height: 1.4;
}

.checkbox-container input {
    display: none;
}

.checkmark {
    width: 18px;
    height: 18px;
    border: 2px solid #D1D5DB;
    border-radius: 4px;
    margin-right: 0.6rem;
    margin-top: 0.1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    flex-shrink: 0;
}

.checkbox-container input:checked + .checkmark {
    background: #667eea;
    border-color: #667eea;
}

.checkbox-container input:checked + .checkmark::after {
    content: '✓';
    color: white;
    font-size: 12px;
    font-weight: bold;
}

.terms-link {
    color: #667eea;
    text-decoration: none;
    font-weight: 500;
}

.terms-link:hover {
    text-decoration: underline;
}

/* Submit Button */
.submit-button {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border: none;
    padding: 1.25rem 2rem;
    border-radius: 12px;
    font-size: 1.1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
    margin-top: 1rem;
}

.submit-button:hover {
    transform: translateY(-3px);
    box-shadow: 0 15px 35px rgba(102, 126, 234, 0.5);
}

.button-icon {
    transition: transform 0.3s ease;
}

.submit-button:hover .button-icon {
    transform: translateX(5px);
}

/* Login Redirect */
.login-redirect {
    text-align: center;
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid #E5E7EB;
}

.login-redirect p {
    color: #6B7280;
    font-size: 1rem;
}

.login-link {
    color: #667eea;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
}

.login-link:hover {
    color: #764ba2;
    text-decoration: underline;
}

/* Responsive Design */
@media (max-width: 1024px) {
    .signup-content {
        grid-template-columns: 1fr;
        gap: 2rem;
        max-width: 600px;
    }
    
    .signup-header {
        order: 2;
    }
    
    .signup-form-container {
        order: 1;
    }
    
    .main-title {
        font-size: 3rem;
    }
}

@media (max-width: 768px) {
    .signup-fullwidth-page {
        padding: 1rem;
    }
    
    .signup-form-container {
        padding: 2rem;
    }
    
    .main-title {
        font-size: 2.5rem;
    }
    
    .brand-logo {
        width: 80px;
        height: 80px;
    }
    
    .brand-logo i {
        font-size: 2.5rem;
    }
}

@media (max-width: 480px) {
    .signup-form-container {
        padding: 1.5rem;
        max-height: none;
    }
    
    .form-header h2 {
        font-size: 2rem;
    }
    
    .main-title {
        font-size: 2rem;
    }
    
    .tagline {
        font-size: 1.2rem;
    }
}

/* Loading State */
.submit-button.loading {
    pointer-events: none;
    opacity: 0.8;
}

.submit-button.loading .button-icon {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Password Toggle Function
    window.togglePassword = function(inputId) {
        const input = document.getElementById(inputId);
        const button = input.nextElementSibling;
        const icon = button.querySelector('i');
        
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    };
    
    // Password Strength Checker
    const passwordInput = document.getElementById('password');
    const strengthFill = document.getElementById('strengthFill');
    const strengthText = document.getElementById('strengthText');
    
    if (passwordInput && strengthFill && strengthText) {
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            let strengthLabel = '';
            
            // Check password criteria
            if (password.length >= 8) strength += 20;
            if (password.match(/[a-z]/)) strength += 20;
            if (password.match(/[A-Z]/)) strength += 20;
            if (password.match(/[0-9]/)) strength += 20;
            if (password.match(/[^a-zA-Z0-9]/)) strength += 20;
            
            // Update strength bar and text
            strengthFill.style.width = strength + '%';
            
            if (strength <= 20) {
                strengthFill.style.background = '#EF4444';
                strengthLabel = 'Very Weak';
            } else if (strength <= 40) {
                strengthFill.style.background = '#F59E0B';
                strengthLabel = 'Weak';
            } else if (strength <= 60) {
                strengthFill.style.background = '#EAB308';
                strengthLabel = 'Fair';
            } else if (strength <= 80) {
                strengthFill.style.background = '#22C55E';
                strengthLabel = 'Good';
            } else {
                strengthFill.style.background = '#059669';
                strengthLabel = 'Very Strong';
            }
            
            strengthText.textContent = password ? strengthLabel : 'Password strength';
        });
    }
    
    // Password Match Checker
    const confirmPasswordInput = document.getElementById('password_confirmation');
    const passwordMatch = document.getElementById('passwordMatch');
    
    function checkPasswordMatch() {
        if (confirmPasswordInput && passwordInput && passwordMatch) {
            const password = passwordInput.value;
            const confirmPassword = confirmPasswordInput.value;
            
            if (confirmPassword) {
                if (password === confirmPassword) {
                    passwordMatch.textContent = '✓ Passwords match';
                    passwordMatch.className = 'password-match match';
                    confirmPasswordInput.classList.remove('input-error');
                } else {
                    passwordMatch.textContent = '✗ Passwords do not match';
                    passwordMatch.className = 'password-match no-match';
                    confirmPasswordInput.classList.add('input-error');
                }
            } else {
                passwordMatch.textContent = '';
                passwordMatch.className = 'password-match';
                confirmPasswordInput.classList.remove('input-error');
            }
        }
    }
    
    if (passwordInput && confirmPasswordInput) {
        passwordInput.addEventListener('input', checkPasswordMatch);
        confirmPasswordInput.addEventListener('input', checkPasswordMatch);
    }
    
    // Form Submission Loading State
    const signupForm = document.querySelector('.signup-form');
    const submitButton = document.querySelector('.submit-button');
    
    if (signupForm && submitButton) {
        signupForm.addEventListener('submit', function() {
            submitButton.classList.add('loading');
            submitButton.innerHTML = '<i class="fas fa-spinner button-icon"></i><span class="button-text">Creating Account...</span>';
            submitButton.disabled = true;
        });
    }
    
    // Input Focus Effects
    const formInputs = document.querySelectorAll('.form-input');
    formInputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.style.transform = 'translateY(-2px)';
        });
        
        input.addEventListener('blur', function() {
            this.parentElement.style.transform = 'translateY(0)';
        });
    });
    
    // Real-time Email Validation
    const emailInput = document.getElementById('email');
    if (emailInput) {
        emailInput.addEventListener('input', function() {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (this.value && !emailRegex.test(this.value)) {
                this.classList.add('input-error');
            } else {
                this.classList.remove('input-error');
            }
        });
    }
});
</script>
@endsection
