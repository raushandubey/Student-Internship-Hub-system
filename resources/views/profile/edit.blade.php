@extends('layouts.app')

@section('content')
<div class="profile-edit-container">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-8">
                
                <!-- Header Section -->
                <div class="text-center mb-5">
                    <div class="profile-header-icon">
                        <i class="fas fa-user-edit"></i>
                    </div>
                    <h2 class="profile-title">Edit Your Profile</h2>
                    <p class="profile-subtitle">Update your information to keep your profile current</p>
                </div>

                <!-- Main Form Card -->
                <div class="profile-card">
                    <div class="card-inner">
                        <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data" class="profile-form">
                            @csrf
                            @method('PUT')

                            <!-- Form Fields Grid -->
                            <div class="form-grid">
                                
                                <!-- Name Field -->
                                <div class="form-group full-width">
                                    <div class="input-wrapper">
                                        <i class="input-icon fas fa-user"></i>
                                        <input type="text" 
                                               name="name" 
                                               id="name"
                                               class="form-input @error('name') error @enderror"
                                               placeholder="Enter your full name"
                                               value="{{ old('name', $profile->name ?? auth()->user()->name) }}"
                                               required>
                                        <label for="name" class="form-label">Full Name *</label>
                                        <div class="input-border"></div>
                                    </div>
                                    @error('name')
                                        <div class="error-text">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Academic Background -->
                                <div class="form-group">
                                    <div class="input-wrapper">
                                        <i class="input-icon fas fa-graduation-cap"></i>
                                        <input type="text" 
                                               name="academic_background" 
                                               id="academic_background"
                                               class="form-input"
                                               placeholder="e.g., B.Tech Computer Science"
                                               value="{{ old('academic_background', $profile->academic_background ?? '') }}">
                                        <label for="academic_background" class="form-label">Academic Background</label>
                                        <div class="input-border"></div>
                                    </div>
                                </div>

                                <!-- Aadhaar Number -->
                                <div class="form-group">
                                    <div class="input-wrapper">
                                        <i class="input-icon fas fa-id-card"></i>
                                        <input type="text" 
                                               name="aadhaar_number" 
                                               id="aadhaar_number"
                                               class="form-input"
                                               placeholder="Enter Aadhaar number"
                                               value="{{ old('aadhaar_number', $profile->aadhaar_number ?? '') }}"
                                               maxlength="12">
                                        <label for="aadhaar_number" class="form-label">Aadhaar Number</label>
                                        <div class="input-border"></div>
                                    </div>
                                </div>

                                <!-- Skills -->
                                <div class="form-group full-width">
                                    <div class="input-wrapper">
                                        <i class="input-icon fas fa-tools"></i>
                                        <input type="text" 
                                               name="skills" 
                                               id="skills"
                                               class="form-input"
                                               placeholder="JavaScript, Python, React, Laravel"
                                               value="{{ old('skills', $profile && $profile->skills ? (is_array($profile->skills) ? implode(',', $profile->skills) : $profile->skills) : '') }}">
                                        <label for="skills" class="form-label">Skills (comma separated)</label>
                                        <div class="input-border"></div>
                                    </div>
                                    <div class="form-hint">
                                        <i class="fas fa-info-circle"></i>
                                        Separate multiple skills with commas
                                    </div>
                                </div>

                                <!-- Career Interests -->
                                <div class="form-group full-width">
                                    <div class="textarea-wrapper">
                                        <i class="input-icon fas fa-bullseye"></i>
                                        <textarea name="career_interests" 
                                                  id="career_interests" 
                                                  class="form-textarea"
                                                  placeholder="Describe your career goals and interests..."
                                                  rows="4">{{ old('career_interests', $profile->career_interests ?? '') }}</textarea>
                                        <label for="career_interests" class="form-label">Career Interests</label>
                                        <div class="input-border"></div>
                                    </div>
                                </div>

                                <!-- Resume Upload -->
                                <div class="form-group full-width">
                                    <div class="upload-section">
                                        <label class="upload-label">
                                            <i class="fas fa-file-pdf"></i>
                                            Resume Upload <span class="badge">PDF only</span>
                                        </label>
                                        
                                        <div class="upload-area" id="uploadArea">
                                            <input type="file" 
                                                   name="resume" 
                                                   id="resume" 
                                                   accept=".pdf"
                                                   class="upload-input">
                                            
                                            <div class="upload-content">
                                                <div class="upload-icon">
                                                    <i class="fas fa-cloud-upload-alt"></i>
                                                </div>
                                                <div class="upload-text">
                                                    <h4>Drop your resume here</h4>
                                                    <p>or click to browse files</p>
                                                    <small>Maximum 5MB • PDF format only</small>
                                                </div>
                                            </div>
                                        </div>

                                        @if($profile && $profile->resume_path)
                                            <div class="current-file">
                                                <i class="fas fa-file-pdf"></i>
                                                <span>Current Resume:</span>
                                                <a href="{{ Storage::url($profile->resume_path) }}" target="_blank" class="view-link">
                                                    <i class="fas fa-external-link-alt"></i>
                                                    View Resume
                                                </a>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="form-actions">
                                <a href="{{ route('profile.show') }}" class="btn-secondary">
                                    <i class="fas fa-arrow-left"></i>
                                    Cancel
                                </a>
                                
                                <button type="submit" class="btn-primary">
                                    <i class="fas fa-save"></i>
                                    <span class="btn-text">Save Changes</span>
                                    <span class="btn-loader">
                                        <i class="fas fa-spinner fa-spin"></i>
                                        Saving...
                                    </span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Main Container */
.profile-edit-container {
    min-height: 100vh;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    position: relative;
    overflow: hidden;
}

.profile-edit-container::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="rgba(255,255,255,0.05)"/><circle cx="75" cy="75" r="1" fill="rgba(255,255,255,0.05)"/><circle cx="50" cy="10" r="0.5" fill="rgba(255,255,255,0.03)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
    animation: float 20s ease-in-out infinite;
    pointer-events: none;
}

@keyframes float {
    0%, 100% { transform: translateY(0px) rotate(0deg); }
    50% { transform: translateY(-20px) rotate(1deg); }
}

/* Header */
.profile-header-icon {
    width: 80px;
    height: 80px;
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1.5rem;
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.profile-header-icon i {
    font-size: 2rem;
    color: white;
}

.profile-title {
    color: white;
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.profile-subtitle {
    color: rgba(255, 255, 255, 0.8);
    font-size: 1.1rem;
    font-weight: 400;
}

/* Main Card */
.profile-card {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(20px);
    border-radius: 24px;
    border: 1px solid rgba(255, 255, 255, 0.2);
    box-shadow: 0 25px 45px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    position: relative;
}

.profile-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
}

.card-inner {
    padding: 3rem;
}

/* Form Grid */
.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2rem;
    margin-bottom: 3rem;
}

.form-group.full-width {
    grid-column: 1 / -1;
}

/* Input Styling */
.input-wrapper, .textarea-wrapper {
    position: relative;
    margin-bottom: 0.5rem;
}

.form-input, .form-textarea {
    width: 100%;
    padding: 1rem 1rem 1rem 3rem;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    color: white;
    font-size: 1rem;
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
}

.form-input:focus, .form-textarea:focus {
    outline: none;
    background: rgba(255, 255, 255, 0.1);
    border-color: rgba(255, 255, 255, 0.3);
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
}

.form-input::placeholder, .form-textarea::placeholder {
    color: rgba(255, 255, 255, 0.5);
}
.form-input:focus + .form-label,
.form-input:valid + .form-label,
.form-textarea:focus + .form-label,
.form-textarea:valid + .form-label {
    top: -0.5rem;
    left: 2.5rem;
    font-size: 0.8rem;
    color: rgba(255, 255, 255, 0.9);
    background: rgba(103, 126, 234, 0.8);
    border-radius: 4px;
    padding: 0.2rem 0.5rem;
}


.input-icon {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: rgba(255, 255, 255, 0.6);
    z-index: 2;
}

.form-label {
    position: absolute;
    left: 3rem;
    top: 1rem;
    color: rgba(255, 255, 255, 0.6);
    font-size: 0.9rem;
    transition: all 0.3s ease;
    pointer-events: none;
    background: transparent;
    padding: 0 0.5rem;
}

.form-input:focus + .form-label,
.form-input:not(:placeholder-shown) + .form-label,
.form-textarea:focus + .form-label,
.form-textarea:not(:placeholder-shown) + .form-label {
    top: -0.5rem;
    left: 2.5rem;
    font-size: 0.8rem;
    color: rgba(255, 255, 255, 0.9);
    background: rgba(103, 126, 234, 0.8);
    border-radius: 4px;
    padding: 0.2rem 0.5rem;
}

.input-border {
    position: absolute;
    bottom: 0;
    left: 0;
    width: 0;
    height: 2px;
    background: linear-gradient(90deg, #667eea, #764ba2);
    transition: width 0.3s ease;
}

.form-input:focus ~ .input-border,
.form-textarea:focus ~ .input-border {
    width: 100%;
}

.form-hint {
    color: rgba(255, 255, 255, 0.6);
    font-size: 0.85rem;
    margin-top: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.error-text {
    color: #ff6b6b;
    font-size: 0.85rem;
    margin-top: 0.5rem;
}

.form-input.error {
    border-color: #ff6b6b;
    background: rgba(255, 107, 107, 0.1);
}

/* Upload Section */
.upload-section {
    margin-top: 1rem;
}

.upload-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: white;
    font-weight: 600;
    margin-bottom: 1rem;
    font-size: 1rem;
}

.upload-label .badge {
    background: rgba(255, 193, 7, 0.8);
    color: #000;
    padding: 0.2rem 0.5rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 500;
}

.upload-area {
    position: relative;
    border: 2px dashed rgba(255, 255, 255, 0.3);
    border-radius: 16px;
    padding: 2rem;
    text-align: center;
    transition: all 0.3s ease;
    background: rgba(255, 255, 255, 0.02);
    cursor: pointer;
}

.upload-area:hover {
    border-color: rgba(255, 255, 255, 0.5);
    background: rgba(255, 255, 255, 0.05);
    transform: translateY(-2px);
}

.upload-input {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    opacity: 0;
    cursor: pointer;
}

.upload-icon {
    font-size: 3rem;
    color: rgba(255, 255, 255, 0.6);
    margin-bottom: 1rem;
}

.upload-text h4 {
    color: white;
    margin-bottom: 0.5rem;
    font-weight: 600;
}

.upload-text p {
    color: rgba(255, 255, 255, 0.7);
    margin-bottom: 0.5rem;
}

.upload-text small {
    color: rgba(255, 255, 255, 0.5);
    font-size: 0.85rem;
}

.current-file {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-top: 1rem;
    padding: 1rem;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 12px;
    color: rgba(255, 255, 255, 0.8);
}

.view-link {
    color: #4fc3f7;
    text-decoration: none;
    font-weight: 500;
    transition: color 0.3s ease;
}

.view-link:hover {
    color: #29b6f6;
}

/* Action Buttons */
.form-actions {
    display: flex;
    gap: 1rem;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
}

.btn-secondary, .btn-primary {
    padding: 1rem 2rem;
    border-radius: 50px;
    font-weight: 600;
    text-decoration: none;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 1rem;
    position: relative;
    overflow: hidden;
}

.btn-secondary {
    background: rgba(255, 255, 255, 0.1);
    color: white;
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.btn-secondary:hover {
    background: rgba(255, 255, 255, 0.2);
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
}

.btn-primary {
    background: linear-gradient(135deg, #28a745, #20c997);
    color: white;
    box-shadow: 0 10px 25px rgba(40, 167, 69, 0.3);
    min-width: 180px;
}

.btn-primary:hover {
    transform: translateY(-3px);
    box-shadow: 0 15px 35px rgba(40, 167, 69, 0.4);
}

.btn-loader {
    display: none;
}

.btn-primary.loading .btn-text {
    display: none;
}

.btn-primary.loading .btn-loader {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

/* Responsive Design */
@media (max-width: 768px) {
    .card-inner {
        padding: 2rem 1.5rem;
    }
    
    .form-grid {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
    
    .profile-title {
        font-size: 2rem;
    }
    
    .form-actions {
        flex-direction: column-reverse;
    }
    
    .btn-secondary, .btn-primary {
        width: 100%;
        justify-content: center;
    }
}

@media (max-width: 576px) {
    .profile-edit-container {
        padding: 1rem;
    }
    
    .card-inner {
        padding: 1.5rem 1rem;
    }
}


</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // File upload handling
    const resumeInput = document.getElementById('resume');
    const uploadArea = document.getElementById('uploadArea');
    const uploadContent = uploadArea.querySelector('.upload-content');
    
    // Drag and drop functionality
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        uploadArea.addEventListener(eventName, preventDefaults, false);
    });
    
    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }
    
    ['dragenter', 'dragover'].forEach(eventName => {
        uploadArea.addEventListener(eventName, highlight, false);
    });
    
    ['dragleave', 'drop'].forEach(eventName => {
        uploadArea.addEventListener(eventName, unhighlight, false);
    });
    
    function highlight(e) {
        uploadArea.style.borderColor = 'rgba(255, 255, 255, 0.7)';
        uploadArea.style.background = 'rgba(255, 255, 255, 0.1)';
    }
    
    function unhighlight(e) {
        uploadArea.style.borderColor = 'rgba(255, 255, 255, 0.3)';
        uploadArea.style.background = 'rgba(255, 255, 255, 0.02)';
    }
    
    uploadArea.addEventListener('drop', handleDrop, false);
    
    function handleDrop(e) {
        const dt = e.dataTransfer;
        const files = dt.files;
        resumeInput.files = files;
        handleFiles(files);
    }
    
    resumeInput.addEventListener('change', function(e) {
        handleFiles(e.target.files);
    });
    
    function handleFiles(files) {
        if (files.length > 0) {
            const file = files[0];
            const fileSize = (file.size / 1024 / 1024).toFixed(2);
            
            uploadContent.innerHTML = `
                <div class="upload-icon">
                    <i class="fas fa-file-pdf" style="color: #28a745;"></i>
                </div>
                <div class="upload-text">
                    <h4 style="color: #28a745;">${file.name}</h4>
                    <p>Ready to upload • ${fileSize} MB</p>
                    <small>Click submit to save changes</small>
                </div>
            `;
        }
    }
    
    // Form submission handling
    const form = document.querySelector('.profile-form');
    const submitBtn = document.querySelector('.btn-primary');
    
    form.addEventListener('submit', function(e) {
        submitBtn.classList.add('loading');
        submitBtn.disabled = true;
    });
    
    // Input focus animations
    const inputs = document.querySelectorAll('.form-input, .form-textarea');
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.classList.add('focused');
        });
        
        input.addEventListener('blur', function() {
            if (!this.value) {
                this.parentElement.classList.remove('focused');
            }
        });
    });
    
    // Skills input enhancement
    const skillsInput = document.getElementById('skills');
    skillsInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            const currentValue = this.value.trim();
            if (currentValue && !currentValue.endsWith(',')) {
                this.value = currentValue + ', ';
            }
        }
    });
});
</script>
@endsection
