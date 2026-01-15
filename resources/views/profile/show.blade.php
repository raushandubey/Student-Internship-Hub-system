@extends('layouts.app')

@section('content')
<div class="profile-view-container">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-8">
                
                @if(session('success'))
                    <div class="success-alert">
                        <div class="alert-content">
                            <i class="fas fa-check-circle"></i>
                            <span>{{ session('success') }}</span>
                        </div>
                    </div>
                @endif

                <!-- Header Section -->
                <div class="profile-header">
                    <div class="profile-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="profile-header-content">
                        <h1 class="profile-name">{{ $profile->name ?? auth()->user()->name }}</h1>
                        <p class="profile-subtitle">Professional Profile</p>
                    </div>
                    <div class="profile-actions">
                        <a href="{{ route('profile.edit') }}" class="btn-edit">
                            <i class="fas fa-edit"></i>
                            <span>Edit Profile</span>
                        </a>
                    </div>
                </div>

                <!-- Main Profile Card -->
                <div class="profile-main-card">
                    <div class="profile-card-inner">
                        
                        <!-- Quick Info Section -->
                        <div class="quick-info-section">
                            <h3 class="section-title">
                                <i class="fas fa-info-circle"></i>
                                Quick Information
                            </h3>
                            
                            <div class="info-grid">
                                <div class="info-item">
                                    <div class="info-icon">
                                        <i class="fas fa-graduation-cap"></i>
                                    </div>
                                    <div class="info-content">
                                        <label>Academic Background</label>
                                        <value>{{ $profile->academic_background ?? 'Not provided' }}</value>
                                    </div>
                                </div>

                                <div class="info-item">
                                    <div class="info-icon">
                                        <i class="fas fa-id-card"></i>
                                    </div>
                                    <div class="info-content">
                                        <label>Aadhaar Number</label>
                                        <value>{{ $profile->aadhaar_number ?? 'Not provided' }}</value>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Skills Section -->
                        <div class="skills-section">
                            <h3 class="section-title">
                                <i class="fas fa-tools"></i>
                                Skills & Expertise
                            </h3>
                            
                            <div class="skills-container">
                                @if(!empty($profile->skills))
                                    @php
                                        $skills = is_array($profile->skills) ? $profile->skills : explode(',', $profile->skills);
                                    @endphp
                                    @foreach($skills as $skill)
                                        <div class="skill-tag">
                                            <i class="fas fa-code"></i>
                                            {{ trim($skill) }}
                                        </div>
                                    @endforeach
                                @else
                                    <div class="empty-state">
                                        <i class="fas fa-plus-circle"></i>
                                        <p>No skills added yet</p>
                                        <a href="{{ route('profile.edit') }}" class="add-link">Add Skills</a>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Career Interests Section -->
                        <div class="interests-section">
                            <h3 class="section-title">
                                <i class="fas fa-bullseye"></i>
                                Career Interests
                            </h3>
                            
                            <div class="interests-content">
                                @if($profile->career_interests)
                                    <div class="interests-text">
                                        <p>{{ $profile->career_interests }}</p>
                                    </div>
                                @else
                                    <div class="empty-state">
                                        <i class="fas fa-plus-circle"></i>
                                        <p>No career interests added yet</p>
                                        <a href="{{ route('profile.edit') }}" class="add-link">Add Interests</a>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Resume Section -->
                        <div class="resume-section">
                            <h3 class="section-title">
                                <i class="fas fa-file-pdf"></i>
                                Resume & Documents
                            </h3>
                            
                            <div class="resume-content">
                                @if($profile && $profile->resume_path)
                                    <div class="resume-item">
                                        <div class="resume-info">
                                            <div class="resume-icon">
                                                <i class="fas fa-file-pdf"></i>
                                            </div>
                                            <div class="resume-details">
                                                <h4>Current Resume</h4>
                                                <p>PDF Document • Uploaded</p>
                                            </div>
                                        </div>
                                        <div class="resume-actions">
                                            <a href="{{ Storage::url($profile->resume_path) }}" 
                                               target="_blank" 
                                               class="btn-view-resume">
                                                <i class="fas fa-external-link-alt"></i>
                                                View Resume
                                            </a>
                                        </div>
                                    </div>
                                @else
                                    <div class="empty-state">
                                        <i class="fas fa-upload"></i>
                                        <p>No resume uploaded yet</p>
                                        <a href="{{ route('profile.edit') }}" class="add-link">Upload Resume</a>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Profile Completion -->
                        <div class="completion-section">
                            <h3 class="section-title">
                                <i class="fas fa-chart-pie"></i>
                                Profile Completion
                            </h3>
                            
                            @php
                                $fields = [
                                    'name' => $profile->name ?? auth()->user()->name,
                                    'academic_background' => $profile->academic_background ?? null,
                                    'skills' => $profile->skills ?? null,
                                    'career_interests' => $profile->career_interests ?? null,
                                    'aadhaar_number' => $profile->aadhaar_number ?? null,
                                    'resume_path' => $profile->resume_path ?? null
                                ];
                                $completed = count(array_filter($fields));
                                $total = count($fields);
                                $percentage = round(($completed / $total) * 100);
                            @endphp
                            
                            <div class="completion-bar">
                                <div class="completion-info">
                                    <span class="completion-text">{{ $completed }}/{{ $total }} fields completed</span>
                                    <span class="completion-percentage">{{ $percentage }}%</span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: {{ $percentage }}%"></div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Main Container */
.profile-view-container {
    min-height: 100vh;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    position: relative;
    overflow: hidden;
}

.profile-view-container::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="dots" width="20" height="20" patternUnits="userSpaceOnUse"><circle cx="10" cy="10" r="1" fill="rgba(255,255,255,0.1)"/></pattern></defs><rect width="100" height="100" fill="url(%23dots)"/></svg>');
    animation: drift 30s linear infinite;
    pointer-events: none;
}

@keyframes drift {
    0% { transform: translate(0, 0) rotate(0deg); }
    100% { transform: translate(-50px, -50px) rotate(360deg); }
}

/* Success Alert */
.success-alert {
    margin-bottom: 2rem;
    background: rgba(40, 167, 69, 0.9);
    backdrop-filter: blur(10px);
    border-radius: 12px;
    border: 1px solid rgba(255, 255, 255, 0.2);
    animation: slideIn 0.5s ease-out;
}

.alert-content {
    padding: 1rem 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    color: white;
    font-weight: 500;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Profile Header */
.profile-header {
    display: flex;
    align-items: center;
    gap: 2rem;
    margin-bottom: 2rem;
    padding: 2rem;
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(20px);
    border-radius: 20px;
    border: 1px solid rgba(255, 255, 255, 0.2);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
}

.profile-avatar {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, #28a745, #20c997);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 10px 25px rgba(40, 167, 69, 0.3);
    flex-shrink: 0;
}

.profile-avatar i {
    font-size: 2.5rem;
    color: white;
}

.profile-header-content {
    flex: 1;
}

.profile-name {
    color: white;
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.profile-subtitle {
    color: rgba(255, 255, 255, 0.8);
    font-size: 1.1rem;
    margin-bottom: 0;
}

.profile-actions {
    flex-shrink: 0;
}

.btn-edit {
    background: linear-gradient(135deg, #007bff, #0056b3);
    color: white;
    border: none;
    padding: 1rem 2rem;
    border-radius: 50px;
    text-decoration: none;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
    box-shadow: 0 5px 15px rgba(0, 123, 255, 0.3);
}

.btn-edit:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(0, 123, 255, 0.4);
    color: white;
}

/* Main Profile Card */
.profile-main-card {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(20px);
    border-radius: 24px;
    border: 1px solid rgba(255, 255, 255, 0.2);
    box-shadow: 0 25px 45px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.profile-card-inner {
    padding: 2.5rem;
}

/* Section Styling */
.section-title {
    color: white;
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.section-title i {
    color: rgba(255, 255, 255, 0.8);
    font-size: 1.2rem;
}

/* Quick Info Section */
.quick-info-section {
    margin-bottom: 3rem;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
}

.info-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.5rem;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 16px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    transition: all 0.3s ease;
}

.info-item:hover {
    background: rgba(255, 255, 255, 0.1);
    transform: translateY(-2px);
}

.info-icon {
    width: 50px;
    height: 50px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.info-icon i {
    font-size: 1.5rem;
    color: rgba(255, 255, 255, 0.8);
}

.info-content label {
    display: block;
    color: rgba(255, 255, 255, 0.7);
    font-size: 0.9rem;
    margin-bottom: 0.25rem;
    font-weight: 500;
}

.info-content value {
    color: white;
    font-size: 1.1rem;
    font-weight: 600;
    display: block;
}

/* Skills Section */
.skills-section {
    margin-bottom: 3rem;
}

.skills-container {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
}

.skill-tag {
    background: linear-gradient(135deg, #28a745, #20c997);
    color: white;
    padding: 0.75rem 1.5rem;
    border-radius: 25px;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    box-shadow: 0 5px 15px rgba(40, 167, 69, 0.2);
    transition: all 0.3s ease;
}

.skill-tag:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(40, 167, 69, 0.3);
}

.skill-tag i {
    font-size: 0.9rem;
}

/* Career Interests Section */
.interests-section {
    margin-bottom: 3rem;
}

.interests-text {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 16px;
    padding: 2rem;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.interests-text p {
    color: rgba(255, 255, 255, 0.9);
    font-size: 1.1rem;
    line-height: 1.6;
    margin-bottom: 0;
}

/* Resume Section */
.resume-section {
    margin-bottom: 3rem;
}

.resume-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.5rem;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 16px;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.resume-info {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.resume-icon {
    width: 60px;
    height: 60px;
    background: rgba(220, 53, 69, 0.2);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.resume-icon i {
    font-size: 1.8rem;
    color: #dc3545;
}

.resume-details h4 {
    color: white;
    margin-bottom: 0.25rem;
    font-size: 1.2rem;
    font-weight: 600;
}

.resume-details p {
    color: rgba(255, 255, 255, 0.7);
    margin-bottom: 0;
    font-size: 0.9rem;
}

.btn-view-resume {
    background: linear-gradient(135deg, #dc3545, #c82333);
    color: white;
    padding: 0.75rem 1.5rem;
    border-radius: 25px;
    text-decoration: none;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
    box-shadow: 0 5px 15px rgba(220, 53, 69, 0.3);
}

.btn-view-resume:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(220, 53, 69, 0.4);
    color: white;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 3rem 2rem;
    background: rgba(255, 255, 255, 0.03);
    border-radius: 16px;
    border: 2px dashed rgba(255, 255, 255, 0.2);
}

.empty-state i {
    font-size: 3rem;
    color: rgba(255, 255, 255, 0.4);
    margin-bottom: 1rem;
}

.empty-state p {
    color: rgba(255, 255, 255, 0.6);
    margin-bottom: 1rem;
    font-size: 1.1rem;
}

.add-link {
    color: #4fc3f7;
    text-decoration: none;
    font-weight: 600;
    transition: color 0.3s ease;
}

.add-link:hover {
    color: #29b6f6;
}

/* Completion Section */
.completion-section {
    margin-bottom: 0;
}

.completion-bar {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 16px;
    padding: 1.5rem;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.completion-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.completion-text {
    color: rgba(255, 255, 255, 0.8);
    font-size: 1rem;
}

.completion-percentage {
    color: white;
    font-size: 1.5rem;
    font-weight: 700;
}

.progress-bar {
    height: 8px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 4px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #28a745, #20c997);
    border-radius: 4px;
    transition: width 1s ease-out;
}

/* Responsive Design */
@media (max-width: 768px) {
    .profile-card-inner {
        padding: 2rem 1.5rem;
    }
    
    .profile-header {
        flex-direction: column;
        text-align: center;
        gap: 1.5rem;
    }
    
    .profile-name {
        font-size: 2rem;
    }
    
    .info-grid {
        grid-template-columns: 1fr;
    }
    
    .resume-item {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
    }
}

@media (max-width: 576px) {
    .profile-view-container {
        padding: 1rem;
    }
    
    .profile-card-inner {
        padding: 1.5rem 1rem;
    }
    
    .profile-header {
        padding: 1.5rem;
    }
    
    .skills-container {
        justify-content: center;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Animate progress bar on load
    const progressFill = document.querySelector('.progress-fill');
    if (progressFill) {
        const targetWidth = progressFill.style.width;
        progressFill.style.width = '0%';
        setTimeout(() => {
            progressFill.style.width = targetWidth;
        }, 500);
    }
    
    // Add hover effects to interactive elements
    const interactiveElements = document.querySelectorAll('.info-item, .skill-tag, .btn-view-resume, .btn-edit');
    
    interactiveElements.forEach(element => {
        element.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-3px)';
        });
        
        element.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
    
    // Success alert auto-hide
    const successAlert = document.querySelector('.success-alert');
    if (successAlert) {
        setTimeout(() => {
            successAlert.style.opacity = '0';
            successAlert.style.transform = 'translateY(-20px)';
            setTimeout(() => {
                successAlert.remove();
            }, 300);
        }, 5000);
    }
});
</script>
@endsection
