@extends('layouts.app')

@section('content')
<div class="dashboard-container">
    <div class="container mx-auto px-4 py-6 max-w-7xl">
        
        <!-- Animated Background Elements -->
        <div class="floating-elements">
            <div class="floating-circle circle-1"></div>
            <div class="floating-circle circle-2"></div>
            <div class="floating-circle circle-3"></div>
        </div>

        <!-- Header Section with Advanced Glass Effect -->
        <div class="welcome-card">
            <div class="welcome-content">
                <div class="user-info">
                    <div class="user-avatar">
                        <i class="fas fa-user-circle"></i>
                    </div>
                    <div class="user-details">
                        <h1 class="welcome-title">
                            Welcome back, <span class="user-name">{{ auth()->user()->name }}</span>! 👋
                        </h1>
                        <p class="welcome-subtitle">
                            <i class="fas fa-briefcase me-2"></i>
                            Track your career journey and discover new opportunities
                        </p>
                    </div>
                </div>
                
                <div class="completion-widget">
                    <div class="completion-circle">
                        <svg class="progress-ring" width="80" height="80">
                            <circle class="progress-ring-circle" 
                                    cx="40" cy="40" r="35" 
                                    stroke-dasharray="220" 
                                    stroke-dashoffset="{{ 220 - (220 * ($profileCompletion ?? 75) / 100) }}"
                                    data-percentage="{{ $profileCompletion ?? 75 }}">
                            </circle>
                        </svg>
                        <div class="completion-text">
                            <span class="percentage">{{ $profileCompletion ?? '75' }}%</span>
                            <small>Complete</small>
                        </div>
                    </div>
                    <div class="completion-info">
                        <h3>Profile Status</h3>
                        <p>{{ ($profileCompletion ?? 75) >= 80 ? 'Excellent' : (($profileCompletion ?? 75) >= 50 ? 'Good' : 'Needs Work') }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Advanced Stats Grid -->
        <div class="stats-grid">
            <a href="{{ route('my-applications') }}" class="stat-card applications" data-aos="fade-up" data-aos-delay="100" style="text-decoration: none; cursor: pointer;">
                <div class="stat-header">
                    <div class="stat-icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <div class="stat-trend positive">
                        <i class="fas fa-arrow-up"></i>
                        +12%
                    </div>
                </div>
                <div class="stat-content">
                    <div class="stat-number" data-target="{{ $appliedJobs ?? '0' }}">0</div>
                    <div class="stat-label">Applications Sent</div>
                </div>
                <div class="stat-footer">
                    <div class="stat-progress">
                        <div class="progress-bar" style="width: 75%"></div>
                    </div>
                    <span class="stat-subtext">Click to view details</span>
                </div>
            </a>

            <div class="stat-card interviews" data-aos="fade-up" data-aos-delay="200">
                <div class="stat-header">
                    <div class="stat-icon">
                        <i class="fas fa-video"></i>
                    </div>
                    <div class="stat-trend positive">
                        <i class="fas fa-arrow-up"></i>
                        +5%
                    </div>
                </div>
                <div class="stat-content">
                    <div class="stat-number" data-target="{{ $interviews ?? '0' }}">0</div>
                    <div class="stat-label">Interviews Scheduled</div>
                </div>
                <div class="stat-footer">
                    <div class="stat-progress">
                        <div class="progress-bar" style="width: 60%"></div>
                    </div>
                    <span class="stat-subtext">Success rate: 15%</span>
                </div>
            </div>

            <div class="stat-card recommendations" data-aos="fade-up" data-aos-delay="300">
                <div class="stat-header">
                    <div class="stat-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="stat-trend neutral">
                        <i class="fas fa-minus"></i>
                        0%
                    </div>
                </div>
                <div class="stat-content">
                    <div class="stat-number" data-target="{{ $recommendations ?? '0' }}">0</div>
                    <div class="stat-label">Job Matches</div>
                </div>
                <div class="stat-footer">
                    <div class="stat-progress">
                        <div class="progress-bar" style="width: 85%"></div>
                    </div>
                    <span class="stat-subtext">Updated today</span>
                </div>
            </div>

            <div class="stat-card profile-views" data-aos="fade-up" data-aos-delay="400">
                <div class="stat-header">
                    <div class="stat-icon">
                        <i class="fas fa-eye"></i>
                    </div>
                    <div class="stat-trend positive">
                        <i class="fas fa-arrow-up"></i>
                        +8%
                    </div>
                </div>
                <div class="stat-content">
                    <div class="stat-number" data-target="{{ $profileViews ?? '0' }}">0</div>
                    <div class="stat-label">Profile Views</div>
                </div>
                <div class="stat-footer">
                    <div class="stat-progress">
                        <div class="progress-bar" style="width: 45%"></div>
                    </div>
                    <span class="stat-subtext">Last 7 days</span>
                </div>
            </div>
        </div>

        <!-- Career Intelligence Section (Phase 8) -->
        <div class="career-intelligence-section">
            <div class="section-header-inline">
                <h2 class="section-title-inline">
                    <i class="fas fa-brain"></i>
                    Career Intelligence
                </h2>
                <span class="intelligence-badge">AI-Powered Insights</span>
            </div>

            <div class="intelligence-grid">
                <!-- Career Readiness Score -->
                <div class="intelligence-card readiness-card">
                    <div class="readiness-ring-container">
                        <svg class="readiness-ring" width="120" height="120">
                            <circle class="readiness-ring-bg" cx="60" cy="60" r="50"></circle>
                            <circle class="readiness-ring-progress" cx="60" cy="60" r="50"
                                    stroke-dasharray="314"
                                    stroke-dashoffset="{{ 314 - (314 * ($careerReadiness['score'] ?? 0) / 100) }}">
                            </circle>
                        </svg>
                        <div class="readiness-score-text">
                            <span class="score-value">{{ $careerReadiness['score'] ?? 0 }}</span>
                            <span class="score-label">Score</span>
                        </div>
                    </div>
                    <div class="readiness-info">
                        <h3 class="readiness-title">Career Readiness</h3>
                        <span class="readiness-level level-{{ strtolower($careerReadiness['level'] ?? 'developing') }}">
                            {{ $careerReadiness['level'] ?? 'Developing' }}
                        </span>
                        <div class="readiness-breakdown">
                            <div class="breakdown-item">
                                <span class="breakdown-label">Profile</span>
                                <div class="breakdown-bar">
                                    <div class="breakdown-fill" style="width: {{ ($careerReadiness['breakdown']['profile_completeness'] ?? 0) * 4 }}%"></div>
                                </div>
                            </div>
                            <div class="breakdown-item">
                                <span class="breakdown-label">Match Quality</span>
                                <div class="breakdown-bar">
                                    <div class="breakdown-fill" style="width: {{ ($careerReadiness['breakdown']['match_quality'] ?? 0) * 4 }}%"></div>
                                </div>
                            </div>
                            <div class="breakdown-item">
                                <span class="breakdown-label">Success Rate</span>
                                <div class="breakdown-bar">
                                    <div class="breakdown-fill" style="width: {{ ($careerReadiness['breakdown']['success_rate'] ?? 0) * 4 }}%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Skill Strengths -->
                <div class="intelligence-card strengths-card">
                    <div class="card-icon-header">
                        <div class="icon-circle green">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <h3>Your Strengths</h3>
                    </div>
                    @if(!empty($skillStrengths['strongest']))
                        <div class="strongest-skill">
                            <span class="strongest-label">Top Skill</span>
                            <span class="strongest-value">{{ ucfirst($skillStrengths['strongest']) }}</span>
                        </div>
                        <div class="skills-list">
                            @foreach(array_slice($skillStrengths['skills'] ?? [], 0, 4) as $skill => $count)
                                <div class="skill-item strength">
                                    <span class="skill-name">{{ ucfirst($skill) }}</span>
                                    <span class="skill-count">{{ $count }} matches</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="empty-skills">
                            <i class="fas fa-info-circle"></i>
                            <p>Apply to internships to discover your strengths</p>
                        </div>
                    @endif
                </div>

                <!-- Skill Gaps -->
                <div class="intelligence-card gaps-card">
                    <div class="card-icon-header">
                        <div class="icon-circle orange">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <h3>Skills to Learn</h3>
                    </div>
                    @if(!empty($skillGaps['weakest']))
                        <div class="weakest-skill">
                            <span class="weakest-label">Most Requested</span>
                            <span class="weakest-value">{{ ucfirst($skillGaps['weakest']) }}</span>
                        </div>
                        <div class="skills-list">
                            @foreach(array_slice($skillGaps['gaps'] ?? [], 0, 4) as $skill => $count)
                                <div class="skill-item gap">
                                    <span class="skill-name">{{ ucfirst($skill) }}</span>
                                    <span class="skill-count">{{ $count }} jobs need this</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="empty-skills">
                            <i class="fas fa-trophy"></i>
                            <p>Great! No major skill gaps detected</p>
                        </div>
                    @endif
                </div>

                <!-- Application Outcomes -->
                <div class="intelligence-card outcomes-card">
                    <div class="card-icon-header">
                        <div class="icon-circle blue">
                            <i class="fas fa-chart-pie"></i>
                        </div>
                        <h3>Application Stats</h3>
                    </div>
                    <div class="outcomes-stats">
                        <div class="outcome-item">
                            <span class="outcome-value">{{ $outcomes['avg_match_score'] ?? 0 }}%</span>
                            <span class="outcome-label">Avg Match</span>
                        </div>
                        <div class="outcome-item">
                            <span class="outcome-value">{{ $outcomes['success_rate'] ?? 0 }}%</span>
                            <span class="outcome-label">Success Rate</span>
                        </div>
                        <div class="outcome-item">
                            <span class="outcome-value">{{ $outcomes['high_match_applications'] ?? 0 }}</span>
                            <span class="outcome-label">High Match Apps</span>
                        </div>
                    </div>
                    @if(!empty($careerReadiness['improvements']))
                        <div class="improvements-list">
                            <h4><i class="fas fa-lightbulb"></i> Suggestions</h4>
                            @foreach(array_slice($careerReadiness['improvements'] ?? [], 0, 2) as $improvement)
                                <div class="improvement-item impact-{{ $improvement['impact'] }}">
                                    <i class="fas fa-arrow-right"></i>
                                    <span>{{ $improvement['message'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Action Cards Grid -->
        <div class="action-cards-grid">
            <!-- Profile Completion Card -->
            <div class="action-card profile-card" data-aos="fade-up" data-aos-delay="500">
                <div class="card-header">
                    <div class="card-icon profile-icon">
                        <i class="fas fa-user-edit"></i>
                    </div>
                    <div class="card-status">
                        @if(($profileCompletion ?? 75) < 100)
                            <span class="status-badge incomplete">
                                <i class="fas fa-exclamation-circle"></i>
                                Incomplete
                            </span>
                        @else
                            <span class="status-badge complete">
                                <i class="fas fa-check-circle"></i>
                                Complete
                            </span>
                        @endif
                    </div>
                </div>
                
                <div class="card-content">
                    <h3 class="card-title">Complete Your Profile</h3>
                    <p class="card-description">
                        Boost your visibility and get better job matches by completing your professional profile.
                    </p>
                    
                    <div class="progress-section">
                        <div class="progress-info">
                            <span class="progress-label">Progress</span>
                            <span class="progress-percentage">{{ $profileCompletion ?? '75' }}%</span>
                        </div>
                        <div class="modern-progress-bar">
                            <div class="progress-fill" style="width: {{ $profileCompletion ?? '75' }}%"></div>
                        </div>
                        <div class="progress-steps">
                            <div class="step {{ ($profileCompletion ?? 75) >= 20 ? 'completed' : '' }}"></div>
                            <div class="step {{ ($profileCompletion ?? 75) >= 40 ? 'completed' : '' }}"></div>
                            <div class="step {{ ($profileCompletion ?? 75) >= 60 ? 'completed' : '' }}"></div>
                            <div class="step {{ ($profileCompletion ?? 75) >= 80 ? 'completed' : '' }}"></div>
                            <div class="step {{ ($profileCompletion ?? 75) >= 100 ? 'completed' : '' }}"></div>
                        </div>
                    </div>
                </div>
                
                <div class="card-action">
                    <a href="{{ route('profile.edit') }}" class="action-button primary">
                        <i class="fas fa-edit"></i>
                        <span>Edit Profile</span>
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>

            <!-- Job Recommendations Card -->
            <div class="action-card recommendations-card" data-aos="fade-up" data-aos-delay="600">
                <div class="card-header">
                    <div class="card-icon recommendations-icon">
                        <i class="fas fa-briefcase"></i>
                    </div>
                    <div class="card-status">
                        @if(($recommendations ?? 0) > 0)
                            <span class="status-badge available">
                                <i class="fas fa-check"></i>
                                {{ $recommendations }} Available
                            </span>
                        @else
                            <span class="status-badge pending">
                                <i class="fas fa-clock"></i>
                                Pending
                            </span>
                        @endif
                    </div>
                </div>
                
                <div class="card-content">
                    <h3 class="card-title">Job Recommendations</h3>
                    <p class="card-description">
                        Discover personalized job opportunities that match your skills and preferences.
                    </p>
                    
                    @if(($recommendations ?? 0) > 0)
                        <div class="recommendation-stats">
                            <div class="stat-item">
                                <i class="fas fa-bullseye"></i>
                                <span>{{ $recommendations }} perfect matches</span>
                            </div>
                            <div class="stat-item">
                                <i class="fas fa-clock"></i>
                                <span>Updated 2 hours ago</span>
                            </div>
                            <div class="stat-item">
                                <i class="fas fa-chart-line"></i>
                                <span>95% compatibility</span>
                            </div>
                        </div>
                    @else
                        <div class="empty-state">
                            <i class="fas fa-search"></i>
                            <p>Complete your profile to unlock job recommendations</p>
                        </div>
                    @endif
                </div>
                
                <div class="card-action">
                    <a href="{{ route('recommendations.index') }}" class="action-button secondary">
                        <i class="fas fa-search"></i>
                        <span>View Recommendations</span>
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>

            <!-- Analytics Card -->
            <div class="action-card analytics-card" data-aos="fade-up" data-aos-delay="700">
                <div class="card-header">
                    <div class="card-icon analytics-icon">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <div class="card-status">
                        <span class="status-badge analytics">
                            <i class="fas fa-trending-up"></i>
                            Trending Up
                        </span>
                    </div>
                </div>
                
                <div class="card-content">
                    <h3 class="card-title">Career Analytics</h3>
                    <p class="card-description">
                        Track your job search performance and optimize your career strategy.
                    </p>
                    
                    <div class="analytics-grid">
                        <div class="analytics-item">
                            <div class="analytics-value">{{ $profileViews ?? '0' }}</div>
                            <div class="analytics-label">Profile Views</div>
                            <div class="analytics-trend positive">+12%</div>
                        </div>
                        <div class="analytics-item">
                            <div class="analytics-value">{{ $applicationRate ?? '0%' }}</div>
                            <div class="analytics-label">Application Rate</div>
                            <div class="analytics-trend neutral">0%</div>
                        </div>
                        <div class="analytics-item">
                            <div class="analytics-value">{{ $responseRate ?? '0%' }}</div>
                            <div class="analytics-label">Response Rate</div>
                            <div class="analytics-trend positive">+5%</div>
                        </div>
                        <div class="analytics-item">
                            <div class="analytics-value">4.8</div>
                            <div class="analytics-label">Profile Score</div>
                            <div class="analytics-trend positive">+0.3</div>
                        </div>
                    </div>
                </div>
                
                <div class="card-action">
                    <a href="{{ route('profile.show') }}" class="action-button tertiary">
                        <i class="fas fa-analytics"></i>
                        <span>View Analytics</span>
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- Recent Activity Section -->
        <div class="recent-activity-section" data-aos="fade-up" data-aos-delay="800">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="fas fa-history"></i>
                    Recent Activity
                </h2>
                <a href="#" class="view-all-link">
                    <span>View All</span>
                    <i class="fas fa-arrow-right"></i>
                </a>
            </div>
            
            <div class="activity-timeline">
                <div class="activity-item">
                    <div class="activity-icon application">
                        <i class="fas fa-paper-plane"></i>
                    </div>
                    <div class="activity-content">
                        <h4>Applied to Software Engineer at TechCorp</h4>
                        <p>Your application has been submitted successfully</p>
                        <span class="activity-time">2 hours ago</span>
                    </div>
                </div>
                
                <div class="activity-item">
                    <div class="activity-icon profile">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="activity-content">
                        <h4>Profile viewed by HR Manager</h4>
                        <p>Microsoft Recruiter viewed your profile</p>
                        <span class="activity-time">5 hours ago</span>
                    </div>
                </div>
                
                <div class="activity-item">
                    <div class="activity-icon recommendation">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="activity-content">
                        <h4>New job recommendations available</h4>
                        <p>3 new jobs match your preferences</p>
                        <span class="activity-time">1 day ago</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Main Dashboard Container */
.dashboard-container {
    min-height: 100vh;
    position: relative;
    overflow: hidden;
}

/* Floating Background Elements */
.floating-elements {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
    z-index: 0;
}

.floating-circle {
    position: absolute;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(10px);
    animation: float 20s infinite linear;
}

.circle-1 {
    width: 200px;
    height: 200px;
    top: 10%;
    left: 10%;
    animation-delay: -5s;
}

.circle-2 {
    width: 150px;
    height: 150px;
    top: 60%;
    right: 15%;
    animation-delay: -10s;
}

.circle-3 {
    width: 100px;
    height: 100px;
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

/* Welcome Card */
.welcome-card {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 24px;
    padding: 2rem;
    margin-bottom: 2rem;
    position: relative;
    z-index: 1;
    box-shadow: 0 25px 45px rgba(0, 0, 0, 0.1);
}

.welcome-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
}

.welcome-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 2rem;
}

.user-info {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    flex: 1;
}

.user-avatar {
    width: 70px;
    height: 70px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
    flex-shrink: 0;
}

.user-avatar i {
    font-size: 2.5rem;
    color: white;
}

.welcome-title {
    font-size: 2rem;
    font-weight: 700;
    color: white;
    margin-bottom: 0.5rem;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.user-name {
    background: linear-gradient(135deg, #667eea, #764ba2);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.welcome-subtitle {
    color: rgba(255, 255, 255, 0.8);
    font-size: 1.1rem;
    display: flex;
    align-items: center;
}

/* Completion Widget */
.completion-widget {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex-shrink: 0;
}

.completion-circle {
    position: relative;
    width: 80px;
    height: 80px;
}

.progress-ring {
    transform: rotate(-90deg);
}

.progress-ring-circle {
    fill: transparent;
    stroke: rgba(255, 255, 255, 0.2);
    stroke-width: 8;
    transition: stroke-dashoffset 2s ease;
}

.progress-ring-circle {
    stroke: url(#gradient);
}

.completion-text {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    text-align: center;
    color: white;
}

.percentage {
    font-size: 1.2rem;
    font-weight: 700;
    display: block;
}

.completion-text small {
    font-size: 0.8rem;
    opacity: 0.8;
}

.completion-info h3 {
    color: white;
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.completion-info p {
    color: rgba(255, 255, 255, 0.8);
    font-size: 0.9rem;
}

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
    margin-bottom: 3rem;
}

/* Career Intelligence Section (Phase 8) */
.career-intelligence-section {
    margin-bottom: 3rem;
}

.section-header-inline {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1.5rem;
}

.section-title-inline {
    color: white;
    font-size: 1.5rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.section-title-inline i {
    color: #667eea;
}

.intelligence-badge {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
}

.intelligence-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
}

.intelligence-card {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 20px;
    padding: 1.5rem;
    transition: transform 0.3s, box-shadow 0.3s;
}

.intelligence-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
}

/* Readiness Card */
.readiness-card {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    grid-column: span 2;
}

.readiness-ring-container {
    position: relative;
    flex-shrink: 0;
}

.readiness-ring-bg {
    fill: none;
    stroke: rgba(255, 255, 255, 0.1);
    stroke-width: 10;
}

.readiness-ring-progress {
    fill: none;
    stroke: url(#readinessGradient);
    stroke-width: 10;
    stroke-linecap: round;
    transform: rotate(-90deg);
    transform-origin: center;
    transition: stroke-dashoffset 1s ease;
}

.readiness-score-text {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    text-align: center;
}

.score-value {
    display: block;
    font-size: 2rem;
    font-weight: 800;
    color: white;
}

.score-label {
    font-size: 0.8rem;
    color: rgba(255, 255, 255, 0.7);
}

.readiness-info {
    flex: 1;
}

.readiness-title {
    color: white;
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.readiness-level {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: 600;
    margin-bottom: 1rem;
}

.level-excellent { background: rgba(16, 185, 129, 0.2); color: #10b981; }
.level-good { background: rgba(59, 130, 246, 0.2); color: #3b82f6; }
.level-developing { background: rgba(245, 158, 11, 0.2); color: #f59e0b; }
.level-getting\ started { background: rgba(239, 68, 68, 0.2); color: #ef4444; }

.readiness-breakdown {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.breakdown-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.breakdown-label {
    color: rgba(255, 255, 255, 0.7);
    font-size: 0.8rem;
    width: 90px;
}

.breakdown-bar {
    flex: 1;
    height: 6px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 3px;
    overflow: hidden;
}

.breakdown-fill {
    height: 100%;
    background: linear-gradient(90deg, #667eea, #764ba2);
    border-radius: 3px;
    transition: width 1s ease;
}

/* Card Icon Header */
.card-icon-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1rem;
}

.icon-circle {
    width: 45px;
    height: 45px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    color: white;
}

.icon-circle.green { background: linear-gradient(135deg, #10b981, #059669); }
.icon-circle.orange { background: linear-gradient(135deg, #f59e0b, #d97706); }
.icon-circle.blue { background: linear-gradient(135deg, #3b82f6, #2563eb); }

.card-icon-header h3 {
    color: white;
    font-size: 1.1rem;
    font-weight: 600;
}

/* Strongest/Weakest Skill */
.strongest-skill, .weakest-skill {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 12px;
    padding: 0.75rem 1rem;
    margin-bottom: 1rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.strongest-label, .weakest-label {
    color: rgba(255, 255, 255, 0.6);
    font-size: 0.8rem;
}

.strongest-value {
    color: #10b981;
    font-weight: 700;
    font-size: 1rem;
}

.weakest-value {
    color: #f59e0b;
    font-weight: 700;
    font-size: 1rem;
}

/* Skills List */
.skills-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.skill-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.skill-item:last-child {
    border-bottom: none;
}

.skill-name {
    color: white;
    font-size: 0.9rem;
}

.skill-count {
    color: rgba(255, 255, 255, 0.6);
    font-size: 0.75rem;
}

.skill-item.strength .skill-name::before {
    content: '✓ ';
    color: #10b981;
}

.skill-item.gap .skill-name::before {
    content: '+ ';
    color: #f59e0b;
}

.empty-skills {
    text-align: center;
    padding: 1.5rem;
    color: rgba(255, 255, 255, 0.6);
}

.empty-skills i {
    font-size: 2rem;
    margin-bottom: 0.5rem;
    display: block;
}

/* Outcomes Stats */
.outcomes-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 0.75rem;
    margin-bottom: 1rem;
}

.outcome-item {
    text-align: center;
    padding: 0.75rem;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 12px;
}

.outcome-value {
    display: block;
    font-size: 1.25rem;
    font-weight: 700;
    color: white;
}

.outcome-label {
    font-size: 0.7rem;
    color: rgba(255, 255, 255, 0.6);
}

/* Improvements List */
.improvements-list h4 {
    color: rgba(255, 255, 255, 0.8);
    font-size: 0.85rem;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.improvements-list h4 i {
    color: #f59e0b;
}

.improvement-item {
    display: flex;
    align-items: flex-start;
    gap: 0.5rem;
    padding: 0.5rem 0;
    font-size: 0.8rem;
    color: rgba(255, 255, 255, 0.8);
}

.improvement-item i {
    color: #667eea;
    margin-top: 2px;
}

.improvement-item.impact-high {
    color: #10b981;
}

.improvement-item.impact-medium {
    color: #f59e0b;
}

@media (max-width: 768px) {
    .readiness-card {
        grid-column: span 1;
        flex-direction: column;
        text-align: center;
    }
    
    .intelligence-grid {
        grid-template-columns: 1fr;
    }
}

.stat-card {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 20px;
    padding: 1.5rem;
    position: relative;
    overflow: hidden;
    transition: all 0.3s ease;
    z-index: 1;
    display: block;
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, transparent, rgba(255, 255, 255, 0.1));
    opacity: 0;
    transition: opacity 0.3s ease;
}

.stat-card:hover::before {
    opacity: 1;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
}

.stat-card.applications:hover {
    transform: translateY(-8px) scale(1.02);
    box-shadow: 0 25px 50px rgba(0, 123, 255, 0.3);
    border-color: rgba(0, 123, 255, 0.5);
}

.stat-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: white;
}

.applications .stat-icon {
    background: linear-gradient(135deg, #007bff, #0056b3);
}

.interviews .stat-icon {
    background: linear-gradient(135deg,rgb(145, 103, 18),rgb(5, 62, 18));
}

.recommendations .stat-icon {
    background: linear-gradient(135deg, #6f42c1, #563d7c);
}

.profile-views .stat-icon {
    background: linear-gradient(135deg, #fd7e14, #e8590c);
}

.stat-trend {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.8rem;
    font-weight: 600;
    padding: 0.25rem 0.5rem;
    border-radius: 20px;
}

.stat-trend.positive {
    background: rgba(12, 73, 26, 0.5);
    color: #282828;
}

.stat-trend.neutral {
    background: rgba(108, 117, 125, 0.2);
    color: #6c757d;
}

.stat-number {
    font-size: 2.5rem;
    font-weight: 800;
    color: white;
    margin-bottom: 0.5rem;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.stat-label {
    color: rgba(255, 255, 255, 0.8);
    font-size: 1rem;
    font-weight: 500;
    margin-bottom: 1rem;
}

.stat-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.stat-progress {
    flex: 1;
    height: 4px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 2px;
    margin-right: 1rem;
    overflow: hidden;
}

.progress-bar {
    height: 100%;
    background: linear-gradient(90deg, #667eea, #764ba2);
    border-radius: 2px;
    transition: width 2s ease;
}

.stat-subtext {
    color: rgba(255, 255, 255, 0.6);
    font-size: 0.8rem;
}

/* Action Cards Grid */
.action-cards-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 2rem;
    margin-bottom: 3rem;
}

.action-card {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 24px;
    padding: 2rem;
    position: relative;
    overflow: hidden;
    transition: all 0.3s ease;
    z-index: 1;
}

.action-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.2);
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.card-icon {
    width: 60px;
    height: 60px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    color: white;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
}

.profile-icon {
    background: linear-gradient(135deg, #007bff, #0056b3);
}

.recommendations-icon {
    background: linear-gradient(135deg,rgb(40, 167, 44),rgb(24, 129, 47));
}

.analytics-icon {
    background: linear-gradient(135deg, #6f42c1, #563d7c);
}

.status-badge {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
}

.status-badge.incomplete {
    background: rgba(220, 53, 69, 0.2);
    color: #dc3545;
}

.status-badge.complete {
    background: rgba(40, 167, 69, 0.2);
    color:rgb(9, 70, 23);
}

.status-badge.available {
    background: rgba(40, 167, 69, 0.2);
    color:rgb(11, 63, 23);
}

.status-badge.pending {
    background: rgba(255, 193, 7, 0.2);
    color: #ffc107;
}

.status-badge.analytics {
    background: rgba(111, 66, 193, 0.2);
    color: #6f42c1;
}

.card-title {
    color: white;
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 0.75rem;
}

.card-description {
    color: rgba(255, 255, 255, 0.8);
    font-size: 1rem;
    line-height: 1.6;
    margin-bottom: 1.5rem;
}

/* Progress Section */
.progress-section {
    margin-bottom: 1.5rem;
}

.progress-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.75rem;
}

.progress-label {
    color: rgba(255, 255, 255, 0.8);
    font-weight: 500;
}

.progress-percentage {
    color: white;
    font-weight: 700;
    font-size: 1.1rem;
}

.modern-progress-bar {
    height: 8px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 1rem;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #007bff, #0056b3);
    border-radius: 4px;
    transition: width 2s ease;
}

.progress-steps {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.step {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.3);
    transition: all 0.3s ease;
}

.step.completed {
    background: linear-gradient(135deg, #007bff, #0056b3);
    box-shadow: 0 0 10px rgba(0, 123, 255, 0.5);
}

/* Recommendation Stats */
.recommendation-stats {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    margin-bottom: 1rem;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    color: rgba(255, 255, 255, 0.9);
    font-size: 0.9rem;
}

.stat-item i {
    color: #28a745;
    width: 16px;
    text-align: center;
}

/* Analytics Grid */
.analytics-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 1rem;
}

.analytics-item {
    text-align: center;
    padding: 1rem;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 12px;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.analytics-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: white;
    margin-bottom: 0.25rem;
}

.analytics-label {
    font-size: 0.8rem;
    color: rgba(255, 255, 255, 0.7);
    margin-bottom: 0.25rem;
}

.analytics-trend {
    font-size: 0.75rem;
    font-weight: 600;
}

.analytics-trend.positive {
    color: #282828;
}

.analytics-trend.neutral {
    color: #282828;
}

/* Action Buttons */
.action-button {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
    width: 100%;
    padding: 1rem 1.5rem;
    border-radius: 12px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
    position: relative;
    overflow: hidden;
}

.action-button::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: rgba(255, 255, 255, 0.1);
    transition: left 0.3s ease;
}

.action-button:hover::before {
    left: 0;
}

.action-button.primary {
    background: linear-gradient(135deg, #007bff, #0056b3);
    color: white;
    box-shadow: 0 10px 25px rgba(0, 123, 255, 0.3);
}

.action-button.secondary {
    background: linear-gradient(135deg, #28a745, #1e7e34);
    color: white;
    box-shadow: 0 10px 25px rgba(40, 167, 69, 0.3);
}

.action-button.tertiary {
    background: linear-gradient(135deg, #6f42c1, #563d7c);
    color: white;
    box-shadow: 0 10px 25px rgba(111, 66, 193, 0.3);
}

.action-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
}

/* Recent Activity Section */
.recent-activity-section {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 24px;
    padding: 2rem;
    position: relative;
    z-index: 1;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
}

.section-title {
    color: white;
    font-size: 1.5rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.view-all-link {
    color: #667eea;
    text-decoration: none;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
}

.view-all-link:hover {
    color: #764ba2;
    transform: translateX(5px);
}

.activity-timeline {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.activity-item {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    padding: 1rem;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 12px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    transition: all 0.3s ease;
}

.activity-item:hover {
    background: rgba(255, 255, 255, 0.1);
    transform: translateX(10px);
}

.activity-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    flex-shrink: 0;
}

.activity-icon.application {
    background: linear-gradient(135deg, #007bff, #0056b3);
}

.activity-icon.profile {
    background: linear-gradient(135deg, #28a745, #1e7e34);
}

.activity-icon.recommendation {
    background: linear-gradient(135deg, #ffc107, #e0a800);
}

.activity-content h4 {
    color: white;
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.activity-content p {
    color: rgba(255, 255, 255, 0.8);
    font-size: 0.9rem;
    margin-bottom: 0.25rem;
}

.activity-time {
    color: rgba(255, 255, 255, 0.6);
    font-size: 0.8rem;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 2rem;
}

.empty-state i {
    font-size: 2rem;
    color: rgba(255, 255, 255, 0.4);
    margin-bottom: 1rem;
}

.empty-state p {
    color: rgba(255, 255, 255, 0.6);
    font-style: italic;
}

/* Responsive Design */
@media (max-width: 768px) {
    .welcome-content {
        flex-direction: column;
        text-align: center;
        gap: 1.5rem;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .action-cards-grid {
        grid-template-columns: 1fr;
    }
    
    .welcome-title {
        font-size: 1.5rem;
    }
    
    .analytics-grid {
        grid-template-columns: 1fr;
    }
    
    .section-header {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
    }
}

@media (max-width: 576px) {
    .dashboard-container {
        padding: 1rem;
    }
    
    .welcome-card,
    .action-card,
    .recent-activity-section {
        padding: 1.5rem;
    }
    
    .stat-card {
        padding: 1rem;
    }
}
</style>

<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize AOS
    AOS.init({
        duration: 800,
        easing: 'ease-out-cubic',
        once: true,
        offset: 50
    });
    
    // Animate numbers
    function animateNumber(element, target) {
        let current = 0;
        const increment = target / 50;
        const timer = setInterval(() => {
            current += increment;
            if (current >= target) {
                current = target;
                clearInterval(timer);
            }
            element.textContent = Math.floor(current);
        }, 30);
    }
    
    // Animate all stat numbers
    const statNumbers = document.querySelectorAll('.stat-number');
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const target = parseInt(entry.target.dataset.target) || 0;
                animateNumber(entry.target, target);
                observer.unobserve(entry.target);
            }
        });
    });
    
    statNumbers.forEach(number => {
        observer.observe(number);
    });
    
    // Animate progress bars
    setTimeout(() => {
        const progressBars = document.querySelectorAll('.progress-bar');
        progressBars.forEach(bar => {
            const width = bar.style.width;
            bar.style.width = '0%';
            setTimeout(() => {
                bar.style.width = width;
            }, 100);
        });
        
        const progressFills = document.querySelectorAll('.progress-fill');
        progressFills.forEach(fill => {
            const width = fill.style.width;
            fill.style.width = '0%';
            setTimeout(() => {
                fill.style.width = width;
            }, 500);
        });
    }, 1000);
    
    // Add gradient to SVG
    const svg = document.querySelector('.progress-ring');
    if (svg) {
        const defs = document.createElementNS('http://www.w3.org/2000/svg', 'defs');
        const gradient = document.createElementNS('http://www.w3.org/2000/svg', 'linearGradient');
        gradient.setAttribute('id', 'gradient');
        gradient.setAttribute('x1', '0%');
        gradient.setAttribute('y1', '0%');
        gradient.setAttribute('x2', '100%');
        gradient.setAttribute('y2', '0%');
        
        const stop1 = document.createElementNS('http://www.w3.org/2000/svg', 'stop');
        stop1.setAttribute('offset', '0%');
        stop1.setAttribute('stop-color', '#667eea');
        
        const stop2 = document.createElementNS('http://www.w3.org/2000/svg', 'stop');
        stop2.setAttribute('offset', '100%');
        stop2.setAttribute('stop-color', '#764ba2');
        
        gradient.appendChild(stop1);
        gradient.appendChild(stop2);
        defs.appendChild(gradient);
        svg.insertBefore(defs, svg.firstChild);
    }
    
    // Card hover effects
    const cards = document.querySelectorAll('.stat-card, .action-card');
    cards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-8px) scale(1.02)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
        });
    });
    
    // Activity item animations
    const activityItems = document.querySelectorAll('.activity-item');
    activityItems.forEach((item, index) => {
        item.style.animationDelay = `${index * 0.1}s`;
        item.classList.add('fade-in-left');
    });
});

// Add fade-in animation class
const style = document.createElement('style');
style.textContent = `
    .fade-in-left {
        animation: fadeInLeft 0.6s ease forwards;
        opacity: 0;
        transform: translateX(-30px);
    }
    
    @keyframes fadeInLeft {
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }
`;
document.head.appendChild(style);
</script>
@endsection
