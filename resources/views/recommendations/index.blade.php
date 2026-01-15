@extends('layouts.app')

@section('content')
<div class="recommendations-container">
    <div class="container mx-auto px-4 py-6 max-w-7xl">
        
        <!-- Animated Background Elements -->
        <div class="floating-elements">
            <div class="floating-shape shape-1"></div>
            <div class="floating-shape shape-2"></div>
            <div class="floating-shape shape-3"></div>
        </div>

        <!-- Modern Header Section -->
        <div class="recommendations-header" data-aos="fade-down">
            <div class="header-content">
                <div class="header-text">
                    <div class="header-badge">
                        <i class="fas fa-filter"></i>
                        <span>Skill-Based Matching</span>
                    </div>
                    <h1 class="header-title">
                        <span class="gradient-text">Recommended</span> Internships
                    </h1>
                    <p class="header-subtitle">
                        <i class="fas fa-brain me-2"></i>
                        Personalized opportunities based on your skills, interests, and career goals
                    </p>
                </div>
                
                <!-- Filter & Search Section -->
                <div class="header-controls">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Search internships..." id="searchInput">
                    </div>
                    <div class="filter-controls">
                        <button class="filter-btn active" data-filter="all">
                            <i class="fas fa-th-large"></i>
                            All
                        </button>
                        <button class="filter-btn" data-filter="high-match">
                            <i class="fas fa-star"></i>
                            High Match
                        </button>
                        <button class="filter-btn" data-filter="recent">
                            <i class="fas fa-clock"></i>
                            Recent
                        </button>
                        <button class="sort-btn" id="sortBtn">
                            <i class="fas fa-sort-amount-down"></i>
                            Sort by Match
                        </button>
                    </div>
                </div>
            </div>
        </div>

        @if(count($recommendations) > 0)
            <!-- Advanced Stats Section -->
            <div class="stats-section" data-aos="fade-up" data-aos-delay="200">
                <div class="stats-grid">
                    <div class="stat-card total-matches">
                        <div class="stat-icon">
                            <i class="fas fa-bullseye"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number" data-target="{{ count($recommendations) }}">0</div>
                            <div class="stat-label">Total Matches</div>
                            <div class="stat-trend positive">
                                <i class="fas fa-arrow-up"></i>
                                +{{ rand(5, 15) }}% this week
                            </div>
                            <div class="stat-trend positive">
                                <i class="fas fa-arrow-up"></i>
                                +{{ rand(5, 15) }}% this week
                            </div>
                        </div>
                    </div>

                    <div class="stat-card avg-match">
                        @php $avgScore = round(collect($recommendations)->avg(fn($r) => $r['score'] * 100)); @endphp
                        <div class="stat-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number" data-target="{{ $avgScore }}">0</div>
                            <div class="stat-label">Average Match %</div>
                            <div class="stat-trend {{ $avgScore >= 70 ? 'positive' : 'neutral' }}">
                                <i class="fas fa-{{ $avgScore >= 70 ? 'arrow-up' : 'minus' }}"></i>
                                {{ $avgScore >= 70 ? 'Excellent' : 'Good' }} compatibility
                            </div>
                        </div>
                    </div>

                    <div class="stat-card high-matches">
                        @php $highMatches = collect($recommendations)->filter(fn($r) => $r['score'] >= 0.75)->count(); @endphp
                        <div class="stat-icon">
                            <i class="fas fa-medal"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number" data-target="{{ $highMatches }}">0</div>
                            <div class="stat-label">Premium Matches</div>
                            <div class="stat-trend positive">
                                <i class="fas fa-fire"></i>
                                75%+ compatibility
                            </div>
                        </div>
                    </div>

                    <div class="stat-card match-quality">
                        <div class="stat-icon">
                            <i class="fas fa-thumbs-up"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number">{{ rand(85, 98) }}</div>
                            <div class="stat-label">Quality Score</div>
                            <div class="stat-trend positive">
                                <i class="fas fa-check-circle"></i>
                                AI Verified
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Internship Cards Grid -->
            <div class="internships-grid" data-aos="fade-up" data-aos-delay="400">
                @foreach($recommendations as $index => $rec)
                    @php 
                        $internship = $rec['internship']; 
                        $score = number_format($rec['score'] * 100, 0);
                        $isHighMatch = $score >= 75;
                        $isPremium = $score >= 90;
                    @endphp

                    <div class="internship-card {{ $isHighMatch ? 'high-match' : '' }} {{ $isPremium ? 'premium-match' : '' }}" 
                         data-aos="zoom-in" 
                         data-aos-delay="{{ 100 + ($index % 6) * 100 }}"
                         data-match-score="{{ $score }}"
                         data-organization="{{ strtolower($internship->organization) }}"
                         data-title="{{ strtolower($internship->title) }}">
                        
                        <!-- Card Glow Effect for High Matches -->
                        @if($isPremium)
                            <div class="card-glow premium-glow"></div>
                        @elseif($isHighMatch)
                            <div class="card-glow high-match-glow"></div>
                        @endif

                        <!-- Card Header -->
                        <div class="card-header">
                            <div class="company-info">
                                <div class="company-logo">
                                    <span class="logo-text">{{ strtoupper(substr($internship->organization, 0, 2)) }}</span>
                                    @if($isPremium)
                                        <div class="premium-badge">
                                            <i class="fas fa-crown"></i>
                                        </div>
                                    @endif
                                </div>
                                <div class="company-details">
                                    <h3 class="job-title">{{ Str::limit($internship->title, 45) }}</h3>
                                    <p class="company-name">
                                        {{ $internship->organization }}
                                        @if($isHighMatch)
                                            <span class="verified-badge">
                                                <i class="fas fa-check-circle"></i>
                                            </span>
                                        @endif
                                    </p>
                                </div>
                            </div>
                            
                            <!-- Match Score -->
                            <div class="match-score-section">
                                @if($isPremium)
                                    <div class="match-badge premium">
                                        <i class="fas fa-crown"></i>
                                        PREMIUM
                                    </div>
                                @elseif($isHighMatch)
                                    <div class="match-badge high">
                                        <i class="fas fa-star"></i>
                                        HIGH MATCH
                                    </div>
                                @endif
                                
                                <div class="score-circle">
                                    <svg class="score-ring" width="50" height="50">
                                        <circle class="score-ring-bg" cx="25" cy="25" r="20"></circle>
                                        <circle class="score-ring-progress" 
                                                cx="25" cy="25" r="20"
                                                stroke-dasharray="125.6"
                                                stroke-dashoffset="{{ 125.6 - (125.6 * $score / 100) }}"
                                                data-score="{{ $score }}">
                                        </circle>
                                    </svg>
                                    <div class="score-text">{{ $score }}%</div>
                                </div>
                            </div>
                        </div>

                        <!-- Job Details -->
                        <div class="job-details">
                            <div class="detail-item">
                                <i class="fas fa-map-marker-alt"></i>
                                <span>{{ $internship->location }}</span>
                            </div>
                            <div class="detail-item">
                                <i class="fas fa-clock"></i>
                                <span>{{ $internship->duration }}</span>
                            </div>
                            <div class="detail-item">
                                <i class="fas fa-calendar-alt"></i>
                                <span>Posted {{ rand(1, 7) }} days ago</span>
                            </div>
                        </div>

                        <!-- Job Description -->
                        <div class="job-description">
                            <h4 class="section-title">
                                <i class="fas fa-info-circle"></i>
                                About the Role
                            </h4>
                            <p class="description-text">{{ Str::limit($internship->description, 140) }}</p>
                        </div>

                        <!-- Skills Section -->
                        <div class="skills-section">
                            <!-- Required Skills -->
                            <div class="skills-group">
                                <h4 class="skills-title">
                                    <i class="fas fa-tools"></i>
                                    Required Skills
                                </h4>
                                <div class="skills-container">
                                    @foreach(array_slice($internship->required_skills, 0, 4) as $skill)
                                        <span class="skill-tag required">{{ $skill }}</span>
                                    @endforeach
                                    @if(count($internship->required_skills) > 4)
                                        <span class="skill-tag more">+{{ count($internship->required_skills) - 4 }}</span>
                                    @endif
                                </div>
                            </div>

                            <!-- Matching Skills -->
                            @if(count($rec['matching_skills']) > 0)
                                <div class="skills-group">
                                    <h4 class="skills-title">
                                        <i class="fas fa-check-circle"></i>
                                        Your Matching Skills
                                    </h4>
                                    <div class="skills-container">
                                        @foreach(array_slice($rec['matching_skills'], 0, 3) as $skill)
                                            <span class="skill-tag matching">
                                                <i class="fas fa-check"></i>
                                                {{ $skill }}
                                            </span>
                                        @endforeach
                                        @if(count($rec['matching_skills']) > 3)
                                            <span class="skill-tag matching more">+{{ count($rec['matching_skills']) - 3 }}</span>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </div>

                        <!-- Match Analysis -->
                        <div class="match-analysis">
                            <h4 class="analysis-title">
                                <i class="fas fa-chart-bar"></i>
                                Match Analysis
                            </h4>
                            <div class="analysis-bars">
                                <div class="analysis-item">
                                    <span class="analysis-label">Skills Match</span>
                                    <div class="analysis-bar">
                                        <div class="analysis-progress" style="width: {{ $score }}%"></div>
                                    </div>
                                    <span class="analysis-value">{{ $score }}%</span>
                                </div>
                                <div class="analysis-item">
                                    <span class="analysis-label">Location Fit</span>
                                    <div class="analysis-bar">
                                        <div class="analysis-progress" style="width: {{ rand(70, 95) }}%"></div>
                                    </div>
                                    <span class="analysis-value">{{ rand(70, 95) }}%</span>
                                </div>
                                <div class="analysis-item">
                                    <span class="analysis-label">Career Goals</span>
                                    <div class="analysis-bar">
                                        <div class="analysis-progress" style="width: {{ rand(60, 90) }}%"></div>
                                    </div>
                                    <span class="analysis-value">{{ rand(60, 90) }}%</span>
                                </div>
                            </div>
                        </div>

                        <!-- Card Actions -->
                        <div class="card-actions">
                            @auth
                                @if(auth()->user()->role === 'student')
                                    @php
                                        $hasApplied = \App\Models\Application::where('user_id', auth()->id())
                                            ->where('internship_id', $internship->id)
                                            ->exists();
                                    @endphp
                                    
                                    @if($hasApplied)
                                        <button disabled class="action-btn primary disabled">
                                            <i class="fas fa-check-circle"></i>
                                            <span>Already Applied</span>
                                        </button>
                                    @else
                                        <form action="{{ route('applications.apply', $internship) }}" method="POST" style="flex: 1;">
                                            @csrf
                                            <button type="submit" class="action-btn primary" style="width: 100%;">
                                                <i class="fas fa-paper-plane"></i>
                                                <span>Apply Now</span>
                                            </button>
                                        </form>
                                    @endif
                                @else
                                    <button disabled class="action-btn primary disabled">
                                        <i class="fas fa-user-shield"></i>
                                        <span>Admin Account</span>
                                    </button>
                                @endif
                            @else
                                <a href="{{ route('login') }}" class="action-btn primary" style="text-decoration: none;">
                                    <i class="fas fa-sign-in-alt"></i>
                                    <span>Login to Apply</span>
                                </a>
                            @endauth
                            
                            <button class="action-btn secondary save-btn" data-saved="false">
                                <i class="fas fa-heart"></i>
                                <span>Save</span>
                            </button>
                            <button class="action-btn tertiary">
                                <i class="fas fa-share-alt"></i>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Load More Section -->
            <div class="load-more-section" data-aos="fade-up">
                <button class="load-more-btn" id="loadMoreBtn">
                    <span class="btn-text">
                        <i class="fas fa-plus-circle"></i>
                        Load More Opportunities
                    </span>
                    <span class="btn-loader">
                        <i class="fas fa-spinner fa-spin"></i>
                        Loading...
                    </span>
                </button>
                <p class="load-more-text">Showing {{ count($recommendations) }} of {{ count($recommendations) + rand(10, 50) }} available positions</p>
            </div>

        @else
            <!-- Enhanced Empty State -->
            <div class="empty-state" data-aos="zoom-in">
                <div class="empty-state-card">
                    <div class="empty-state-icon">
                        <i class="fas fa-search"></i>
                        <div class="icon-glow"></div>
                    </div>
                    <h2 class="empty-state-title">No Recommendations Yet</h2>
                    <p class="empty-state-text">
                        We're still analyzing your profile to find the perfect internship matches. 
                        Complete your profile to unlock personalized recommendations.
                    </p>
                    
                    <div class="empty-state-stats">
                        <div class="stat-item">
                            <i class="fas fa-user-check"></i>
                            <span>Complete your profile</span>
                        </div>
                        <div class="stat-item">
                            <i class="fas fa-brain"></i>
                            <span>AI will analyze your skills</span>
                        </div>
                        <div class="stat-item">
                            <i class="fas fa-bullseye"></i>
                            <span>Get perfect job matches</span>
                        </div>
                    </div>
                    
                    <div class="empty-state-actions">
                        <a href="{{ route('profile.edit') }}" class="action-btn primary">
                            <i class="fas fa-user-edit"></i>
                            Complete Profile
                        </a>
                        <button class="action-btn secondary">
                            <i class="fas fa-question-circle"></i>
                            Learn More
                        </button>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

<style>
/* Main Container */
.recommendations-container {
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

.floating-shape {
    position: absolute;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.03);
    backdrop-filter: blur(1px);
    animation: floatMove 25s infinite linear;
}

.shape-1 {
    width: 300px;
    height: 300px;
    top: 15%;
    left: -10%;
    animation-delay: -8s;
}

.shape-2 {
    width: 200px;
    height: 200px;
    top: 70%;
    right: -5%;
    animation-delay: -15s;
}

.shape-3 {
    width: 150px;
    height: 150px;
    bottom: 30%;
    left: 80%;
    animation-delay: -3s;
}

@keyframes floatMove {
    0% { transform: translateX(0) translateY(0) rotate(0deg); }
    25% { transform: translateX(-30px) translateY(-40px) rotate(90deg); }
    50% { transform: translateX(20px) translateY(-20px) rotate(180deg); }
    75% { transform: translateX(-20px) translateY(30px) rotate(270deg); }
    100% { transform: translateX(0) translateY(0) rotate(360deg); }
}

/* Header Section */
.recommendations-header {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 24px;
    padding: 2.5rem;
    margin-bottom: 2rem;
    position: relative;
    z-index: 1;
    box-shadow: 0 25px 45px rgba(0, 0, 0, 0.1);
}

.header-content {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 2rem;
}

.header-text {
    flex: 1;
}

.header-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    background: rgba(102, 126, 234, 0.2);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
    margin-bottom: 1rem;
    border: 1px solid rgba(102, 126, 234, 0.3);
}

.header-title {
    font-size: 3rem;
    font-weight: 800;
    color: white;
    margin-bottom: 1rem;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.gradient-text {
    background: linear-gradient(135deg, #667eea, #764ba2);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.header-subtitle {
    color: rgba(255, 255, 255, 0.8);
    font-size: 1.2rem;
    display: flex;
    align-items: center;
}

.header-controls {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    flex-shrink: 0;
}

.search-box {
    position: relative;
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 12px;
    padding: 0.75rem 1rem 0.75rem 3rem;
    backdrop-filter: blur(10px);
}

.search-box i {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: rgba(255, 255, 255, 0.6);
}

.search-box input {
    background: transparent;
    border: none;
    outline: none;
    color: white;
    font-size: 0.9rem;
    width: 250px;
}

.search-box input::placeholder {
    color: rgba(255, 255, 255, 0.5);
}

.filter-controls {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.filter-btn, .sort-btn {
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    color: rgba(255, 255, 255, 0.8);
    padding: 0.5rem 1rem;
    border-radius: 8px;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.filter-btn:hover, .sort-btn:hover {
    background: rgba(255, 255, 255, 0.2);
    color: white;
}

.filter-btn.active {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border-color: transparent;
}

/* Stats Section */
.stats-section {
    margin-bottom: 3rem;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
}

.stat-card {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 20px;
    padding: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    transition: all 0.3s ease;
    position: relative;
    z-index: 1;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: white;
    flex-shrink: 0;
}

.total-matches .stat-icon {
    background: linear-gradient(135deg, #667eea, #764ba2);
}

.avg-match .stat-icon {
    background: linear-gradient(135deg, #28a745, #20c997);
}

.high-matches .stat-icon {
    background: linear-gradient(135deg, #ffc107, #fd7e14);
}

.match-quality .stat-icon {
    background: linear-gradient(135deg, #e83e8c, #dc3545);
}

.stat-content {
    flex: 1;
}

.stat-number {
    font-size: 2rem;
    font-weight: 800;
    color: white;
    margin-bottom: 0.25rem;
}

.stat-label {
    color: rgba(255, 255, 255, 0.8);
    font-size: 0.9rem;
    font-weight: 500;
    margin-bottom: 0.25rem;
}

.stat-trend {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.8rem;
    font-weight: 600;
}

.stat-trend.positive {
    color: #282828;
}

.stat-trend.neutral {
    color: #282828;
}

/* Internships Grid */
.internships-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
    gap: 2rem;
    margin-bottom: 3rem;
}

.internship-card {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 24px;
    padding: 0;
    position: relative;
    overflow: hidden;
    transition: all 0.4s ease;
    z-index: 1;
}

.internship-card:hover {
    transform: translateY(-10px) scale(1.02);
    box-shadow: 0 30px 60px rgba(0, 0, 0, 0.2);
}

/* Card Glow Effects */
.card-glow {
    position: absolute;
    top: -2px;
    left: -2px;
    right: -2px;
    bottom: -2px;
    border-radius: 26px;
    z-index: -1;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.internship-card.high-match .high-match-glow {
    background: linear-gradient(135deg, #28a745, #20c997);
    opacity: 0.3;
}

.internship-card.premium-match .premium-glow {
    background: linear-gradient(135deg, #ffd700, #ffb347);
    opacity: 0.4;
    animation: premiumPulse 2s ease-in-out infinite;
}

@keyframes premiumPulse {
    0%, 100% { transform: scale(1); opacity: 0.4; }
    50% { transform: scale(1.02); opacity: 0.6; }
}

/* Card Header */
.card-header {
    padding: 2rem;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 1rem;
}

.company-info {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    flex: 1;
}

.company-logo {
    width: 60px;
    height: 60px;
    border-radius: 16px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    flex-shrink: 0;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
}

.logo-text {
    color: white;
    font-weight: 800;
    font-size: 1.2rem;
}

.premium-badge {
    position: absolute;
    top: -8px;
    right: -8px;
    width: 24px;
    height: 24px;
    background: linear-gradient(135deg, #ffd700, #ffb347);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 0.7rem;
    animation: crownSpin 3s linear infinite;
}

@keyframes crownSpin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.job-title {
    color: white;
    font-size: 1.3rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    line-height: 1.3;
}

.company-name {
    color: rgba(255, 255, 255, 0.8);
    font-size: 1rem;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.verified-badge {
    color: #28a745;
    font-size: 0.9rem;
}

/* Match Score Section */
.match-score-section {
    text-align: center;
    flex-shrink: 0;
}

.match-badge {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 700;
    margin-bottom: 1rem;
}

.match-badge.high {
    background: rgba(40, 1, 69, 0.5);
    color: #28a745;
    border: 1px solid rgba(40, 167, 69, 0.3);
}

.match-badge.premium {
    background: rgba(255, 215, 0, 0.5);
    color: #ffd700;
    border: 1px solid rgba(255, 215, 0, 0.5);
    animation: premiumGlow 2s ease-in-out infinite;
}

@keyframes premiumGlow {
    0%, 100% { box-shadow: 0 0 5px rgba(255, 215, 0, 0.3); }
    50% { box-shadow: 0 0 20px rgba(255, 215, 0, 0.6); }
}

.score-circle {
    position: relative;
    width: 50px;
    height: 50px;
    margin: 0 auto;
}

.score-ring {
    transform: rotate(-90deg);
}

.score-ring-bg {
    fill: none;
    stroke: rgba(255, 255, 255, 0.1);
    stroke-width: 4;
}

.score-ring-progress {
    fill: none;
    stroke-width: 4;
    stroke-linecap: round;
    transition: stroke-dashoffset 2s ease;
}

.internship-card.high-match .score-ring-progress {
    stroke: url(#highMatchGradient);
}

.internship-card.premium-match .score-ring-progress {
    stroke: url(#premiumGradient);
}

.internship-card:not(.high-match):not(.premium-match) .score-ring-progress {
    stroke: url(#defaultGradient);
}

.score-text {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    color: white;
    font-weight: 700;
    font-size: 0.8rem;
}

/* Job Details */
.job-details {
    display: flex;
    justify-content: space-between;
    padding: 0 2rem;
    margin-bottom: 1.5rem;
    gap: 1rem;
}

.detail-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: rgba(255, 255, 255, 0.8);
    font-size: 0.85rem;
    flex: 1;
}

.detail-item i {
    color: rgba(255, 255, 255, 0.6);
    width: 14px;
    text-align: center;
}

/* Job Description */
.job-description {
    padding: 0 2rem;
    margin-bottom: 1.5rem;
}

.section-title {
    color: white;
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 0.75rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.section-title i {
    color: rgba(255, 255, 255, 0.7);
    font-size: 0.9rem;
}

.description-text {
    color: rgba(255, 255, 255, 0.8);
    font-size: 0.9rem;
    line-height: 1.6;
}

/* Skills Section */
.skills-section {
    padding: 0 2rem;
    margin-bottom: 1.5rem;
}

.skills-group {
    margin-bottom: 1rem;
}

.skills-title {
    color: white;
    font-size: 0.9rem;
    font-weight: 600;
    margin-bottom: 0.75rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.skills-title i {
    color: rgba(255, 255, 255, 0.7);
    font-size: 0.8rem;
    width: 14px;
}

.skills-container {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.skill-tag {
    padding: 0.4rem 0.8rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 0.25rem;
    transition: all 0.3s ease;
}

.skill-tag.required {
    background: rgba(255, 255, 255, 0.1);
    color: rgba(255, 255, 255, 0.9);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.skill-tag.matching {
    background: rgba(4, 101, 50, 0.5);
    color: #28b101;
    border: 1px solid rgba(40, 167, 69, 0.3);
}

.skill-tag.more {
    background: rgba(255, 255, 255, 0.05);
    color: rgba(255, 255, 255, 0.6);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.skill-tag:hover {
    transform: translateY(-2px);
}

/* Match Analysis */
.match-analysis {
    padding: 0 2rem;
    margin-bottom: 1.5rem;
}

.analysis-title {
    color: white;
    font-size: 0.9rem;
    font-weight: 600;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.analysis-bars {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.analysis-item {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.analysis-label {
    color: rgba(255, 255, 255, 0.8);
    font-size: 0.8rem;
    width: 80px;
    flex-shrink: 0;
}

.analysis-bar {
    flex: 1;
    height: 6px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 3px;
    overflow: hidden;
}

.analysis-progress {
    height: 100%;
    background: linear-gradient(90deg, #667eea, #764ba2);
    border-radius: 3px;
    transition: width 2s ease;
}

.analysis-value {
    color: white;
    font-size: 0.8rem;
    font-weight: 600;
    width: 40px;
    text-align: right;
    flex-shrink: 0;
}

/* Card Actions */
.card-actions {
    padding: 1.5rem 2rem 2rem;
    display: flex;
    gap: 0.75rem;
}

.action-btn {
    border: none;
    border-radius: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    position: relative;
    overflow: hidden;
}

.action-btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: rgba(255, 255, 255, 0.1);
    transition: left 0.3s ease;
}

.action-btn:hover::before {
    left: 0;
}

.action-btn.primary {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    padding: 0.75rem 1.5rem;
    flex: 1;
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
}

.action-btn.secondary {
    background: rgba(255, 255, 255, 0.1);
    color: rgba(255, 255, 255, 0.9);
    border: 1px solid rgba(255, 255, 255, 0.2);
    padding: 0.75rem 1rem;
}

.action-btn.tertiary {
    background: rgba(255, 255, 255, 0.05);
    color: rgba(255, 255, 255, 0.7);
    border: 1px solid rgba(255, 255, 255, 0.1);
    padding: 0.75rem;
    width: 45px;
    height: 45px;
}

.action-btn:hover {
    transform: translateY(-2px);
}

.action-btn.primary:hover {
    box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
}

/* Save Button States */
.save-btn[data-saved="true"] {
    background: rgba(220, 53, 69, 0.2);
    color: #dc3545;
    border-color: rgba(220, 53, 69, 0.3);
}

.save-btn[data-saved="true"] i {
    color: #dc3545;
}

/* Load More Section */
.load-more-section {
    text-align: center;
    padding: 2rem;
}

.load-more-btn {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    color: white;
    padding: 1rem 2rem;
    border-radius: 50px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.75rem;
    position: relative;
    overflow: hidden;
}

.load-more-btn:hover {
    background: rgba(255, 255, 255, 0.2);
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
}

.btn-loader {
    display: none;
}

.load-more-btn.loading .btn-text {
    display: none;
}

.load-more-btn.loading .btn-loader {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.load-more-text {
    color: rgba(255, 255, 255, 0.6);
    font-size: 0.9rem;
    margin-top: 1rem;
}

/* Empty State */
.empty-state {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 60vh;
    padding: 2rem;
}

.empty-state-card {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 24px;
    padding: 3rem;
    text-align: center;
    max-width: 500px;
    position: relative;
}

.empty-state-icon {
    width: 100px;
    height: 100px;
    margin: 0 auto 2rem;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
}

.empty-state-icon i {
    font-size: 3rem;
    color: rgba(255, 255, 255, 0.6);
    z-index: 2;
    position: relative;
}

.icon-glow {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: radial-gradient(circle, rgba(102, 126, 234, 0.2) 0%, transparent 70%);
    border-radius: 50%;
    animation: iconPulse 3s ease-in-out infinite;
}

@keyframes iconPulse {
    0%, 100% { transform: scale(1); opacity: 0.5; }
    50% { transform: scale(1.1); opacity: 0.8; }
}

.empty-state-title {
    color: white;
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 1rem;
}

.empty-state-text {
    color: rgba(255, 255, 255, 0.8);
    font-size: 1.1rem;
    line-height: 1.6;
    margin-bottom: 2rem;
}

.empty-state-stats {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    margin-bottom: 2rem;
}

.empty-state-stats .stat-item {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
    color: rgba(255, 255, 255, 0.7);
    font-size: 0.9rem;
}

.empty-state-stats .stat-item i {
    color: #667eea;
    width: 20px;
    text-align: center;
}

.empty-state-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
}

/* Responsive Design */
@media (max-width: 768px) {
    .header-content {
        flex-direction: column;
        gap: 1.5rem;
    }
    
    .header-title {
        font-size: 2rem;
    }
    
    .header-controls {
        width: 100%;
    }
    
    .search-box input {
        width: 100%;
    }
    
    .filter-controls {
        justify-content: center;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .internships-grid {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
    
    .job-details {
        flex-direction: column;
        gap: 0.75rem;
    }
    
    .analysis-item {
        flex-direction: column;
        gap: 0.5rem;
        align-items: flex-start;
    }
    
    .analysis-label {
        width: auto;
    }
    
    .card-actions {
        flex-direction: column;
    }
    
    .action-btn.tertiary {
        width: 100%;
        height: auto;
        padding: 0.75rem;
    }
    
    .empty-state-actions {
        flex-direction: column;
    }
}

@media (max-width: 576px) {
    .recommendations-container {
        padding: 1rem;
    }
    
    .recommendations-header,
    .empty-state-card {
        padding: 2rem 1.5rem;
    }
    
    .internship-card {
        margin: 0 -0.5rem;
    }
    
    .card-header {
        padding: 1.5rem;
    }
    
    .job-description,
    .skills-section,
    .match-analysis {
        padding: 0 1.5rem;
    }
    
    .card-actions {
        padding: 1.5rem;
    }
}

/* Animation Classes */
.fade-in-up {
    opacity: 0;
    transform: translateY(30px);
    animation: fadeInUp 0.6s ease forwards;
}

@keyframes fadeInUp {
    to {
        opacity: 1;
        transform: translateY(0);
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
    
    // Add SVG gradients for progress rings
    const svgDefs = `
        <defs>
            <linearGradient id="defaultGradient" x1="0%" y1="0%" x2="100%" y2="0%">
                <stop offset="0%" stop-color="#667eea"/>
                <stop offset="100%" stop-color="#764ba2"/>
            </linearGradient>
            <linearGradient id="highMatchGradient" x1="0%" y1="0%" x2="100%" y2="0%">
                <stop offset="0%" stop-color="#28a745"/>
                <stop offset="100%" stop-color="#20c997"/>
            </linearGradient>
            <linearGradient id="premiumGradient" x1="0%" y1="0%" x2="100%" y2="0%">
                <stop offset="0%" stop-color="#ffd700"/>
                <stop offset="100%" stop-color="#ffb347"/>
            </linearGradient>
        </defs>
    `;
    
    document.querySelectorAll('.score-ring').forEach(ring => {
        ring.innerHTML = svgDefs + ring.innerHTML;
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
    
    // Animate stat numbers on scroll
    const statNumbers = document.querySelectorAll('.stat-number');
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const target = parseInt(entry.target.dataset.target) || parseInt(entry.target.textContent) || 0;
                animateNumber(entry.target, target);
                observer.unobserve(entry.target);
            }
        });
    });
    
    statNumbers.forEach(number => {
        observer.observe(number);
    });
    
    // Filter functionality
    const filterBtns = document.querySelectorAll('.filter-btn');
    const internshipCards = document.querySelectorAll('.internship-card');
    
    filterBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            // Remove active class from all buttons
            filterBtns.forEach(b => b.classList.remove('active'));
            // Add active class to clicked button
            this.classList.add('active');
            
            const filter = this.dataset.filter;
            
            internshipCards.forEach(card => {
                const matchScore = parseInt(card.dataset.matchScore);
                let shouldShow = true;
                
                if (filter === 'high-match' && matchScore < 75) {
                    shouldShow = false;
                } else if (filter === 'recent') {
                    // Show only first 3 cards as "recent"
                    const cardIndex = Array.from(internshipCards).indexOf(card);
                    shouldShow = cardIndex < 3;
                }
                
                if (shouldShow) {
                    card.style.display = 'block';
                    card.style.animation = 'fadeInUp 0.6s ease forwards';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    });
    
    // Search functionality
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            
            internshipCards.forEach(card => {
                const organization = card.dataset.organization;
                const title = card.dataset.title;
                
                if (organization.includes(searchTerm) || title.includes(searchTerm) || searchTerm === '') {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    }
    
    // Sort functionality
    const sortBtn = document.getElementById('sortBtn');
    if (sortBtn) {
        let sortAsc = false;
        
        sortBtn.addEventListener('click', function() {
            const grid = document.querySelector('.internships-grid');
            const cards = Array.from(internshipCards);
            
            cards.sort((a, b) => {
                const scoreA = parseInt(a.dataset.matchScore);
                const scoreB = parseInt(b.dataset.matchScore);
                return sortAsc ? scoreA - scoreB : scoreB - scoreA;
            });
            
            // Update sort button icon
            const icon = this.querySelector('i');
            icon.className = sortAsc ? 'fas fa-sort-amount-up' : 'fas fa-sort-amount-down';
            
            // Re-append cards in new order
            cards.forEach(card => grid.appendChild(card));
            
            sortAsc = !sortAsc;
        });
    }
    
    // Save button functionality
    document.querySelectorAll('.save-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const isSaved = this.dataset.saved === 'true';
            this.dataset.saved = !isSaved;
            
            const icon = this.querySelector('i');
            const text = this.querySelector('span');
            
            if (!isSaved) {
                icon.className = 'fas fa-heart';
                text.textContent = 'Saved';
                this.style.background = 'rgba(220, 53, 69, 0.2)';
                this.style.color = '#dc3545';
                this.style.borderColor = 'rgba(220, 53, 69, 0.3)';
            } else {
                icon.className = 'fas fa-heart';
                text.textContent = 'Save';
                this.style.background = 'rgba(255, 255, 255, 0.1)';
                this.style.color = 'rgba(255, 255, 255, 0.9)';
                this.style.borderColor = 'rgba(255, 255, 255, 0.2)';
            }
        });
    });
    
    // Load more functionality
    const loadMoreBtn = document.getElementById('loadMoreBtn');
    if (loadMoreBtn) {
        loadMoreBtn.addEventListener('click', function() {
            this.classList.add('loading');
            
            setTimeout(() => {
                this.classList.remove('loading');
                // In real implementation, you would load more cards here
            }, 2000);
        });
    }
    
    // Animate progress bars on scroll
    const progressObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const progress = entry.target;
                const width = progress.style.width;
                progress.style.width = '0%';
                setTimeout(() => {
                    progress.style.width = width;
                }, 100);
                progressObserver.unobserve(entry.target);
            }
        });
    });
    
    document.querySelectorAll('.analysis-progress').forEach(progress => {
        progressObserver.observe(progress);
    });
    
    // Card hover effects
    internshipCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.zIndex = '10';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.zIndex = '1';
        });
    });
    
    // Stagger card animations
    setTimeout(() => {
        internshipCards.forEach((card, index) => {
            card.style.animationDelay = `${index * 0.1}s`;
            card.classList.add('fade-in-up');
        });
    }, 1000);
});
</script>
@endsection
