@extends('layouts.app')

@section('content')
<div class="tracker-container">
    <div class="container mx-auto px-4 py-6 max-w-7xl">
        
        <!-- Page Header -->
        <div class="page-header-card">
            <div class="header-content">
                <div class="header-text">
                    <h1 class="page-title">
                        <i class="fas fa-clipboard-list"></i>
                        My Application Tracker
                    </h1>
                    <p class="page-subtitle">Track your internship applications through the hiring pipeline</p>
                </div>
                <div class="header-badge">
                    <div class="total-badge">
                        <i class="fas fa-file-alt"></i>
                        <span>Total: <strong>{{ $applications->count() }}</strong></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary Stats -->
        <div class="stats-row">
            @php
                $stages = [
                    ['key' => 'pending', 'label' => 'Pending', 'icon' => 'fa-clock', 'color' => 'yellow'],
                    ['key' => 'under_review', 'label' => 'Under Review', 'icon' => 'fa-search', 'color' => 'blue'],
                    ['key' => 'shortlisted', 'label' => 'Shortlisted', 'icon' => 'fa-star', 'color' => 'purple'],
                    ['key' => 'interview_scheduled', 'label' => 'Interview', 'icon' => 'fa-video', 'color' => 'indigo'],
                    ['key' => 'approved', 'label' => 'Approved', 'icon' => 'fa-check-circle', 'color' => 'green'],
                    ['key' => 'rejected', 'label' => 'Rejected', 'icon' => 'fa-times-circle', 'color' => 'red'],
                ];
            @endphp
            @foreach($stages as $stage)
                <div class="stat-mini-card {{ $stage['color'] }}">
                    <div class="stat-mini-icon">
                        <i class="fas {{ $stage['icon'] }}"></i>
                    </div>
                    <div class="stat-mini-content">
                        <div class="stat-mini-value">{{ $applications->filter(fn($a) => $a->status->value === $stage['key'])->count() }}</div>
                        <div class="stat-mini-label">{{ $stage['label'] }}</div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Applications with Progress Tracker -->
        @if($applications->count() > 0)
            <div class="applications-list">
                @foreach($applications as $application)
                    @php
                        $currentStatus = $application->status->value;
                        $pipelineStages = ['pending', 'under_review', 'shortlisted', 'interview_scheduled', 'approved'];
                        $currentIndex = array_search($currentStatus, $pipelineStages);
                        $isRejected = $currentStatus === 'rejected';
                    @endphp
                    
                    <div class="application-card {{ $isRejected ? 'rejected' : '' }}">
                        <!-- Application Header -->
                        <div class="app-header">
                            <div class="app-info">
                                <h3 class="app-title">{{ $application->internship->title }}</h3>
                                <p class="app-org">
                                    <i class="fas fa-building"></i>
                                    {{ $application->internship->organization }}
                                </p>
                                <p class="app-date">
                                    <i class="fas fa-calendar"></i>
                                    Applied {{ $application->created_at->format('M d, Y') }}
                                </p>
                            </div>
                            <div class="app-status-badge status-{{ $application->status->colorClass() }}">
                                {{ $application->status->label() }}
                            </div>
                        </div>

                        <!-- Progress Pipeline -->
                        @if(!$isRejected)
                            <div class="progress-pipeline">
                                @foreach($pipelineStages as $index => $stage)
                                    @php
                                        $stageLabels = [
                                            'pending' => 'Applied',
                                            'under_review' => 'Under Review',
                                            'shortlisted' => 'Shortlisted',
                                            'interview_scheduled' => 'Interview',
                                            'approved' => 'Approved'
                                        ];
                                        $isCompleted = $currentIndex !== false && $index <= $currentIndex;
                                        $isCurrent = $stage === $currentStatus;
                                    @endphp
                                    <div class="pipeline-stage {{ $isCompleted ? 'completed' : '' }} {{ $isCurrent ? 'current' : '' }}">
                                        <div class="stage-dot">
                                            @if($isCompleted && !$isCurrent)
                                                <i class="fas fa-check"></i>
                                            @else
                                                {{ $index + 1 }}
                                            @endif
                                        </div>
                                        <span class="stage-label">{{ $stageLabels[$stage] }}</span>
                                    </div>
                                    @if($index < count($pipelineStages) - 1)
                                        <div class="pipeline-connector {{ $isCompleted && $index < $currentIndex ? 'completed' : '' }}"></div>
                                    @endif
                                @endforeach
                            </div>
                        @else
                            <div class="rejected-notice">
                                <i class="fas fa-info-circle"></i>
                                <span>This application was not selected. Keep applying to other opportunities!</span>
                            </div>
                        @endif

                        <!-- Status Timeline (Last 3 changes) -->
                        @if($application->statusLogs->count() > 0)
                            <div class="status-timeline">
                                <h4 class="timeline-title">
                                    <i class="fas fa-history"></i>
                                    Recent Activity
                                </h4>
                                <div class="timeline-items">
                                    @foreach($application->statusLogs->take(3) as $log)
                                        <div class="timeline-item">
                                            <div class="timeline-dot"></div>
                                            <div class="timeline-content">
                                                <span class="timeline-status">
                                                    @if($log->from_status)
                                                        {{ ucfirst(str_replace('_', ' ', $log->from_status)) }} → 
                                                    @endif
                                                    {{ ucfirst(str_replace('_', ' ', $log->to_status)) }}
                                                </span>
                                                <span class="timeline-date">{{ $log->created_at->diffForHumans() }}</span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <!-- Prediction Section (Phase 8) -->
                        @if(isset($application->timeline['prediction']) && $application->timeline['prediction'])
                            <div class="prediction-section">
                                <div class="prediction-icon">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="prediction-content">
                                    <span class="prediction-label">Estimated Next Action</span>
                                    <span class="prediction-message">{{ $application->timeline['prediction']['message'] }}</span>
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <div class="empty-state-card">
                <div class="empty-icon">
                    <i class="fas fa-inbox"></i>
                </div>
                <h3 class="empty-title">No Applications Yet</h3>
                <p class="empty-description">Start exploring internship opportunities that match your skills!</p>
                <a href="{{ route('recommendations.index') }}" class="empty-cta-button">
                    <i class="fas fa-search"></i>
                    <span>Browse Internships</span>
                </a>
            </div>
        @endif
    </div>
</div>

<style>
.tracker-container {
    min-height: 100vh;
    padding-bottom: 2rem;
}

.page-header-card {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 20px;
    padding: 1.5rem 2rem;
    margin-bottom: 1.5rem;
}

.header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
}

.page-title {
    font-size: 1.75rem;
    font-weight: 700;
    color: white;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 0.25rem;
}

.page-subtitle {
    color: rgba(255, 255, 255, 0.7);
    font-size: 0.95rem;
}

.total-badge {
    background: linear-gradient(135deg, #667eea, #764ba2);
    padding: 0.75rem 1.25rem;
    border-radius: 12px;
    color: white;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.total-badge strong {
    font-size: 1.25rem;
}

/* Stats Row */
.stats-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.stat-mini-card {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.15);
    border-radius: 16px;
    padding: 1rem;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.stat-mini-icon {
    width: 45px;
    height: 45px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    color: white;
}

.stat-mini-card.yellow .stat-mini-icon { background: linear-gradient(135deg, #f59e0b, #d97706); }
.stat-mini-card.blue .stat-mini-icon { background: linear-gradient(135deg, #3b82f6, #2563eb); }
.stat-mini-card.purple .stat-mini-icon { background: linear-gradient(135deg, #8b5cf6, #7c3aed); }
.stat-mini-card.indigo .stat-mini-icon { background: linear-gradient(135deg, #6366f1, #4f46e5); }
.stat-mini-card.green .stat-mini-icon { background: linear-gradient(135deg, #10b981, #059669); }
.stat-mini-card.red .stat-mini-icon { background: linear-gradient(135deg, #ef4444, #dc2626); }

.stat-mini-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: white;
    line-height: 1;
}

.stat-mini-label {
    color: rgba(255, 255, 255, 0.7);
    font-size: 0.75rem;
}

/* Application Cards */
.applications-list {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.application-card {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 20px;
    padding: 1.5rem;
    transition: transform 0.2s, box-shadow 0.2s;
}

.application-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
}

.application-card.rejected {
    border-color: rgba(239, 68, 68, 0.3);
    opacity: 0.85;
}

.app-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1.5rem;
    gap: 1rem;
}

.app-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: white;
    margin-bottom: 0.5rem;
}

.app-org, .app-date {
    color: rgba(255, 255, 255, 0.7);
    font-size: 0.875rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.25rem;
}

.app-status-badge {
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    white-space: nowrap;
}

.status-yellow { background: linear-gradient(135deg, #f59e0b, #d97706); color: #000; }
.status-blue { background: linear-gradient(135deg, #3b82f6, #2563eb); color: white; }
.status-purple { background: linear-gradient(135deg, #8b5cf6, #7c3aed); color: white; }
.status-indigo { background: linear-gradient(135deg, #6366f1, #4f46e5); color: white; }
.status-green { background: linear-gradient(135deg, #10b981, #059669); color: white; }
.status-red { background: linear-gradient(135deg, #ef4444, #dc2626); color: white; }

/* Progress Pipeline */
.progress-pipeline {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 0;
    margin-bottom: 1rem;
    overflow-x: auto;
}

.pipeline-stage {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    min-width: 80px;
}

.stage-dot {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    border: 2px solid rgba(255, 255, 255, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
    font-weight: 600;
    color: rgba(255, 255, 255, 0.6);
    transition: all 0.3s;
}

.pipeline-stage.completed .stage-dot {
    background: linear-gradient(135deg, #10b981, #059669);
    border-color: #10b981;
    color: white;
}

.pipeline-stage.current .stage-dot {
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    border-color: #3b82f6;
    color: white;
    box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.3);
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.3); }
    50% { box-shadow: 0 0 0 8px rgba(59, 130, 246, 0.1); }
}

.stage-label {
    font-size: 0.7rem;
    color: rgba(255, 255, 255, 0.6);
    text-align: center;
    white-space: nowrap;
}

.pipeline-stage.completed .stage-label,
.pipeline-stage.current .stage-label {
    color: white;
    font-weight: 500;
}

.pipeline-connector {
    flex: 1;
    height: 3px;
    background: rgba(255, 255, 255, 0.2);
    margin: 0 0.5rem;
    margin-bottom: 1.5rem;
    border-radius: 2px;
}

.pipeline-connector.completed {
    background: linear-gradient(90deg, #10b981, #059669);
}

/* Rejected Notice */
.rejected-notice {
    background: rgba(239, 68, 68, 0.1);
    border: 1px solid rgba(239, 68, 68, 0.3);
    border-radius: 12px;
    padding: 1rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    color: #fca5a5;
    font-size: 0.9rem;
    margin-bottom: 1rem;
}

/* Status Timeline */
.status-timeline {
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    padding-top: 1rem;
}

/* Prediction Section (Phase 8) */
.prediction-section {
    display: flex;
    align-items: center;
    gap: 1rem;
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
    border: 1px solid rgba(102, 126, 234, 0.3);
    border-radius: 12px;
    padding: 1rem;
    margin-top: 1rem;
}

.prediction-icon {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1rem;
    flex-shrink: 0;
}

.prediction-content {
    flex: 1;
}

.prediction-label {
    display: block;
    font-size: 0.75rem;
    color: rgba(255, 255, 255, 0.6);
    margin-bottom: 0.25rem;
}

.prediction-message {
    color: white;
    font-size: 0.9rem;
    font-weight: 500;
}

.timeline-title {
    font-size: 0.85rem;
    color: rgba(255, 255, 255, 0.7);
    margin-bottom: 0.75rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.timeline-items {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.timeline-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.timeline-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.4);
}

.timeline-content {
    display: flex;
    justify-content: space-between;
    flex: 1;
    font-size: 0.8rem;
}

.timeline-status {
    color: rgba(255, 255, 255, 0.8);
}

.timeline-date {
    color: rgba(255, 255, 255, 0.5);
}

/* Empty State */
.empty-state-card {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 24px;
    padding: 4rem 2rem;
    text-align: center;
}

.empty-icon {
    width: 100px;
    height: 100px;
    margin: 0 auto 1.5rem;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.empty-icon i {
    font-size: 3rem;
    color: rgba(255, 255, 255, 0.5);
}

.empty-title {
    color: white;
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.empty-description {
    color: rgba(255, 255, 255, 0.7);
    margin-bottom: 1.5rem;
}

.empty-cta-button {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    text-decoration: none;
    border-radius: 12px;
    font-weight: 500;
    transition: transform 0.2s, box-shadow 0.2s;
}

.empty-cta-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
}

/* Responsive */
@media (max-width: 768px) {
    .header-content { flex-direction: column; align-items: flex-start; }
    .stats-row { grid-template-columns: repeat(3, 1fr); }
    .progress-pipeline { justify-content: flex-start; gap: 0; }
    .pipeline-stage { min-width: 60px; }
    .stage-label { font-size: 0.6rem; }
}

@media (max-width: 480px) {
    .stats-row { grid-template-columns: repeat(2, 1fr); }
}
</style>
@endsection
