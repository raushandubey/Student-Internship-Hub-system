@extends('layouts.app')

@section('content')
<div class="login-container">
    <!-- Background Pattern -->
    <div class="bg-pattern"></div>
    
    <div class="login-wrapper">
        <!-- Login Card -->
        <div class="login-card">
            <!-- Header -->
            <div class="login-header">
                <div class="logo">
                    <div class="logo-icon">
                        <i class="fas fa-briefcase"></i>
                    </div>
                    <h1>InternshipHub</h1>
                </div>
                <div class="welcome-text">
                    <h2>Welcome Back</h2>
                    <p>Sign in to continue your journey</p>
                </div>
            </div>

            <!-- Login Form -->
            <form method="POST" action="{{ route('login') }}" class="login-form">
                @csrf
                
                <!-- Email Field -->
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <div class="input-group">
                        <i class="fas fa-envelope"></i>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            required 
                            value="{{ old('email') }}"
                            placeholder="Enter your email"
                            class="@error('email') error @enderror">
                    </div>
                    @error('email')
                        <span class="error-msg">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Password Field -->
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-group">
                        <i class="fas fa-lock"></i>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            required 
                            placeholder="Enter your password"
                            class="@error('password') error @enderror">
                        <button type="button" class="password-toggle" id="togglePassword">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    @error('password')
                        <span class="error-msg">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Options -->
                <div class="form-options">
                    <label class="checkbox-container">
                        <input type="checkbox" name="remember">
                        <span class="checkmark"></span>
                        Remember me
                    </label>
                    <a href=" " class="forgot-link">Forgot Password?</a>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="login-btn">
                    <span>Sign In</span>
                    <i class="fas fa-arrow-right"></i>
                </button>

                <!-- Divider -->
                <div class="divider">
                    <span>or</span>
                </div>

                <!-- Social Login -->
                <div class="social-buttons">
                    <button type="button" class="social-btn google">
                        <i class="fab fa-google"></i>
                        Google
                    </button>
                    <button type="button" class="social-btn github">
                        <i class="fab fa-github"></i>
                        GitHub
                    </button>
                </div>

                <!-- Register Link -->
                <p class="register-text">
                    Don't have an account? 
                    <a href="{{ route('register') }}">Create one</a>
                </p>
            </form>
        </div>

        <!-- Side Content -->
        <div class="side-content">
            <div class="feature">
                <div class="feature-icon">
                    <i class="fas fa-filter"></i>
                </div>
                <h3>Skill-Based Matching</h3>
                <p>Get personalized internship recommendations</p>
            </div>
            <div class="feature">
                <div class="feature-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h3>Career Analytics</h3>
                <p>Track your progress and optimize your search</p>
            </div>
            <div class="feature">
                <div class="feature-icon">
                    <i class="fas fa-users"></i>
                </div>
                <h3>Industry Network</h3>
                <p>Connect with top companies and recruiters</p>
            </div>
        </div>
    </div>
</div>

<style>
/* Reset & Base */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

/* Main Container */
.login-container {
    min-height: 100vh;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem;
    position: relative;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
}

/* Background Pattern */
.bg-pattern {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-image: 
        radial-gradient(circle at 25% 25%, rgba(255,255,255,0.1) 0%, transparent 50%),
        radial-gradient(circle at 75% 75%, rgba(255,255,255,0.05) 0%, transparent 50%);
    pointer-events: none;
}

/* Main Wrapper */
.login-wrapper {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 4rem;
    max-width: 1100px;
    width: 100%;
    align-items: center;
    position: relative;
    z-index: 1;
}

/* Login Card */
.login-card {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    border-radius: 20px;
    padding: 3rem;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    max-width: 450px;
    width: 100%;
    justify-self: end;
}

/* Header */
.login-header {
    text-align: center;
    margin-bottom: 2.5rem;
}

.logo {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1rem;
    margin-bottom: 2rem;
}

.logo-icon {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
}

.logo-icon i {
    color: white;
    font-size: 1.5rem;
}

.logo h1 {
    color: #333;
    font-size: 1.8rem;
    font-weight: 700;
}

.welcome-text h2 {
    color: #333;
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.welcome-text p {
    color: #666;
    font-size: 1rem;
}

/* Form */
.login-form {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.form-group label {
    color: #333;
    font-weight: 600;
    font-size: 0.9rem;
}

.input-group {
    position: relative;
    display: flex;
    align-items: center;
}

.input-group i {
    position: absolute;
    left: 1rem;
    color: #666;
    z-index: 2;
}

.input-group input {
    width: 100%;
    padding: 1rem 1rem 1rem 2.5rem;
    border: 2px solid #e1e5e9;
    border-radius: 10px;
    font-size: 1rem;
    background: #f8f9fa;
    transition: all 0.3s ease;
    color: #333;
}

.input-group input:focus {
    outline: none;
    border-color: #667eea;
    background: white;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.input-group input.error {
    border-color: #dc3545;
    background: #fff5f5;
}

.password-toggle {
    position: absolute;
    right: 1rem;
    background: none;
    border: none;
    color: #666;
    cursor: pointer;
    padding: 0.5rem;
    z-index: 2;
}

.password-toggle:hover {
    color: #333;
}

.error-msg {
    color: #dc3545;
    font-size: 0.85rem;
    margin-top: 0.25rem;
}

/* Form Options */
.form-options {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin: 0.5rem 0;
}

.checkbox-container {
    display: flex;
    align-items: center;
    font-size: 0.9rem;
    color: #666;
    cursor: pointer;
    user-select: none;
}

.checkbox-container input {
    display: none;
}

.checkmark {
    width: 18px;
    height: 18px;
    border: 2px solid #ddd;
    border-radius: 4px;
    margin-right: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
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

.forgot-link {
    color: #667eea;
    text-decoration: none;
    font-size: 0.9rem;
    font-weight: 500;
}

.forgot-link:hover {
    text-decoration: underline;
}

/* Login Button */
.login-btn {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border: none;
    padding: 1rem 2rem;
    border-radius: 10px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
}

.login-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.5);
}

.login-btn:active {
    transform: translateY(0);
}

/* Divider */
.divider {
    position: relative;
    text-align: center;
    margin: 1.5rem 0;
}

.divider::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 0;
    right: 0;
    height: 1px;
    background: #e1e5e9;
}

.divider span {
    background: white;
    color: #666;
    padding: 0 1rem;
    font-size: 0.9rem;
}

/* Social Buttons */
.social-buttons {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.social-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.75rem;
    border: 2px solid #e1e5e9;
    border-radius: 8px;
    background: white;
    color: #333;
    font-size: 0.9rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
}

.social-btn:hover {
    border-color: #667eea;
    color: #667eea;
    transform: translateY(-1px);
}

.social-btn.google:hover {
    border-color: #db4437;
    color: #db4437;
}

.social-btn.github:hover {
    border-color: #333;
    color: #333;
}

/* Register Link */
.register-text {
    text-align: center;
    color: #666;
    font-size: 0.9rem;
    margin-top: 1rem;
}

.register-text a {
    color: #667eea;
    text-decoration: none;
    font-weight: 600;
}

.register-text a:hover {
    text-decoration: underline;
}

/* Side Content */
.side-content {
    display: flex;
    flex-direction: column;
    gap: 2rem;
    justify-self: start;
}

.feature {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 16px;
    padding: 2rem;
    transition: all 0.3s ease;
}

.feature:hover {
    transform: translateX(10px);
    background: rgba(255, 255, 255, 0.15);
}

.feature-icon {
    width: 50px;
    height: 50px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1rem;
}

.feature-icon i {
    color: white;
    font-size: 1.5rem;
}

.feature h3 {
    color: white;
    font-size: 1.2rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.feature p {
    color: rgba(255, 255, 255, 0.8);
    font-size: 0.9rem;
    line-height: 1.5;
}

/* Responsive Design */
@media (max-width: 1024px) {
    .login-wrapper {
        grid-template-columns: 1fr;
        gap: 3rem;
        justify-items: center;
    }
    
    .login-card {
        justify-self: center;
        max-width: 500px;
    }
    
    .side-content {
        flex-direction: row;
        justify-self: center;
        max-width: 800px;
    }
}

@media (max-width: 768px) {
    .login-container {
        padding: 1rem;
    }
    
    .login-card {
        padding: 2rem;
    }
    
    .side-content {
        flex-direction: column;
    }
    
    .social-buttons {
        grid-template-columns: 1fr;
    }
    
    .form-options {
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start;
    }
}

@media (max-width: 480px) {
    .login-card {
        padding: 1.5rem;
    }
    
    .logo {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .welcome-text h2 {
        font-size: 1.5rem;
    }
}

/* Loading State */
.login-btn.loading {
    pointer-events: none;
    opacity: 0.8;
}

.login-btn.loading i {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Password Toggle
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');
    
    if (togglePassword && passwordInput) {
        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            
            const icon = this.querySelector('i');
            icon.classList.toggle('fa-eye');
            icon.classList.toggle('fa-eye-slash');
        });
    }
    
    // Form Submission
    const loginForm = document.querySelector('.login-form');
    const loginBtn = document.querySelector('.login-btn');
    
    if (loginForm && loginBtn) {
        loginForm.addEventListener('submit', function() {
            loginBtn.classList.add('loading');
            loginBtn.innerHTML = '<i class="fas fa-spinner"></i><span>Signing In...</span>';
        });
    }
    
    // Input Focus Effects
    const inputs = document.querySelectorAll('input');
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.style.transform = 'translateY(-2px)';
        });
        
        input.addEventListener('blur', function() {
            this.parentElement.style.transform = 'translateY(0)';
        });
    });
});
</script>
@endsection
