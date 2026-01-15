@extends('layouts.app')

@section('content')
<div class="tracker-container">
    <div class="container mx-auto px-4 py-6 max-w-7xl">
        
        <!-- Floating Background Elements -->
        <div class="floating-elements">
            <div class="floating-circle circle-1"></div>
            <div class="floating-circle circle-2"></div>
            <div class="floating-circle circle-3"></div>
        </div>

        <!-- Page Header -->
        <div class="page-header-card" data-aos="fade-down">
            <div class="header-content">
                <div class="header-text">
                    <h1 class="page-title">
                        <i class="fas fa-clipboard-list"></i>
                        My Application Tracker
                    </h1>
                    <p class="page-subtitle">
                        Track all your internship applications in one place
                    </p>
                </div>
                <div class="header-badge">
                    <div class="total-badge">
                        <i class="fas fa-file-alt"></i>
                        <span>Total Applications: <strong>{{ $applications->count() }}</strong></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary Stats Row -->
        <div class="stats-row" data-aos="fade-up" data-aos-delay="100">
            <div class="stat-mini-card total">
                <div class="stat-mini-icon">
                    <i class="fas fa-paper-plane"></i>
                </div>
                <div class="stat-mini-content">
                    <div class="stat-mini-value">{{ $applications->count() }}</div>
                    <div class="stat-mini-label">Total Applications</div>
                </div>
            </div>

            <div class="stat-mini-card approved">
                <div class="stat-mini-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-mini-content">
                    <div class="stat-mini-value">{{ $applications->where('status', 'approved')->count() }}</div>
                    <div class="stat-mini-label">Approved</div>
                </div>
            </div>

            <div class="stat-mini-card pending">
                <div class="stat-mini-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-mini-content">
                    <div class="stat-mini-value">{{ $applications->where('status', 'pending')->count() }}</div>
                    <div class="stat-mini-label">Pending</div>
                </div>
            </div>
        </div>

        <!-- Applications Table -->
        @if($applications->count() > 0)
            <div class="table-card" data-aos="fade-up" data-aos-delay="200">
                <div class="table-header">
                    <h2 class="table-title">
                        <i class="fas fa-list"></i>
                        Your Applications
                    </h2>
                    <div class="table-count">
                        {{ $applications->count() }} {{ $applications->count() === 1 ? 'application' : 'applications' }}
                    </div>
                </div>

                <div class="table-wrapper">
                    <table class="applications-table">
                        <thead>
                            <tr>
                                <th class="th-title">
                                    <i class="fas fa-briefcase me-2"></i>
                                    Internship Title
                                </th>
                                <th class="th-org">
                                    <i class="fas fa-building me-2"></i>
                                    Organization
                                </th>
                                <th class="th-date">
                                    <i class="fas fa-calendar me-2"></i>
                                    Applied Date
                                </th>
                                <th class="th-status">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Status
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($applications as $application)
                                <tr class="table-row">
                                    <td class="td-title">
                                        <div class="title-cell">
                                            <i class="fas fa-file-alt cell-icon"></i>
                                            <span class="title-text">{{ $application->internship->title }}</span>
                                        </div>
                                    </td>
                                    <td class="td-org">
                                        <span class="org-text">{{ $application->internship->organization }}</span>
                                    </td>
                                    <td class="td-date">
                                        <div class="date-cell">
                                            <i class="fas fa-calendar-day date-icon"></i>
                                            <span class="date-text">{{ $application->created_at->format('M d, Y') }}</span>
                                        </div>
                                    </td>
                                    <td class="td-status">
                                        @if($application->status === 'pending')
                                            <span class="status-badge status-pending">
                                                <i class="fas fa-clock"></i>
                                                Pending
                                            </span>
                                        @elseif($application->status === 'approved')
                                            <span class="status-badge status-approved">
                                                <i class="fas fa-check-circle"></i>
                                                Approved
                                            </span>
                                        @elseif($application->status === 'rejected')
                                            <span class="status-badge status-rejected">
                                                <i class="fas fa-times-circle"></i>
                                                Rejected
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <!-- Empty State -->
            <div class="empty-state-card" data-aos="fade-up" data-aos-delay="200">
                <div class="empty-icon">
                    <i class="fas fa-inbox"></i>
                </div>
                <h3 class="empty-title">No Applications Yet</h3>
                <p class="empty-description">
                    You haven't applied to any internships yet. Start exploring opportunities that match your skills!
                </p>
                <a href="{{ route('recommendations.index') }}" class="empty-cta-button">
                    <i class="fas fa-search"></i>
                    <span>Browse Internships</span>
                    <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        @endif
    </div>
</div>

<style>
/* Main Container */
.tracker-container {
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

/* Page Header Card */
.page-header-card {
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

.page-header-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
}

.header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 2rem;
}

.page-title {
    font-size: 2.5rem;
    font-weight: 800;
    color: white;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.page-title i {
    background: linear-gradient(135deg, #667eea, #764ba2);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.page-subtitle {
    color: rgba(255, 255, 255, 0.8);
    font-size: 1.1rem;
    margin-left: 3.5rem;
}

.total-badge {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    background: linear-gradient(135deg, #007bff, #0056b3);
    padding: 1rem 1.5rem;
    border-radius: 16px;
    color: white;
    font-size: 1rem;
    box-shadow: 0 10px 25px rgba(0, 123, 255, 0.3);
    white-space: nowrap;
}

.total-badge i {
    font-size: 1.5rem;
}

.total-badge strong {
    font-size: 1.5rem;
    font-weight: 800;
}

/* Summary Stats Row */
.stats-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-mini-card {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 20px;
    padding: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1.5rem;
    position: relative;
    overflow: hidden;
    transition: all 0.3s ease;
    z-index: 1;
}

.stat-mini-card::before {
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

.stat-mini-card:hover::before {
    opacity: 1;
}

.stat-mini-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
}

.stat-mini-icon {
    width: 60px;
    height: 60px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    color: white;
    flex-shrink: 0;
}

.stat-mini-card.total .stat-mini-icon {
    background: linear-gradient(135deg, #007bff, #0056b3);
}

.stat-mini-card.approved .stat-mini-icon {
    background: linear-gradient(135deg, #28a745, #1e7e34);
}

.stat-mini-card.pending .stat-mini-icon {
    background: linear-gradient(135deg, #ffc107, #e0a800);
}

.stat-mini-value {
    font-size: 2.5rem;
    font-weight: 800;
    color: white;
    line-height: 1;
    margin-bottom: 0.25rem;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.stat-mini-label {
    color: rgba(255, 255, 255, 0.8);
    font-size: 0.95rem;
    font-weight: 500;
}

/* Table Card */
.table-card {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 24px;
    padding: 2rem;
    position: relative;
    z-index: 1;
    box-shadow: 0 25px 45px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.table-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.table-title {
    color: white;
    font-size: 1.5rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.table-count {
    color: rgba(255, 255, 255, 0.7);
    font-size: 0.95rem;
    background: rgba(255, 255, 255, 0.1);
    padding: 0.5rem 1rem;
    border-radius: 12px;
}

.table-wrapper {
    overflow-x: auto;
    border-radius: 16px;
}

.applications-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
}

.applications-table thead tr {
    background: rgba(255, 255, 255, 0.05);
}

.applications-table th {
    padding: 1.25rem 1.5rem;
    text-align: left;
    color: rgba(255, 255, 255, 0.9);
    font-weight: 600;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 2px solid rgba(255, 255, 255, 0.1);
}

.applications-table th:first-child {
    border-top-left-radius: 12px;
}

.applications-table th:last-child {
    border-top-right-radius: 12px;
}

.table-row {
    background: rgba(255, 255, 255, 0.03);
    transition: all 0.3s ease;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}

.table-row:hover {
    background: rgba(255, 255, 255, 0.1);
    transform: translateX(5px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}

.table-row:last-child {
    border-bottom: none;
}

.applications-table td {
    padding: 1.5rem 1.5rem;
    color: white;
}

.title-cell {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.cell-icon {
    color: #667eea;
    font-size: 1.2rem;
}

.title-text {
    font-weight: 600;
    font-size: 1.05rem;
    color: white;
}

.org-text {
    color: rgba(255, 255, 255, 0.7);
    font-size: 0.95rem;
}

.date-cell {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.date-icon {
    color: rgba(255, 255, 255, 0.5);
    font-size: 0.9rem;
}

.date-text {
    color: rgba(255, 255, 255, 0.7);
    font-size: 0.9rem;
}

/* Status Badges */
.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.6rem 1.2rem;
    border-radius: 20px;
    font-weight: 700;
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    transition: all 0.3s ease;
}

.status-badge:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
}

.status-pending {
    background: linear-gradient(135deg, #ffc107, #e0a800);
    color: #000;
}

.status-approved {
    background: linear-gradient(135deg, #28a745, #1e7e34);
    color: white;
}

.status-rejected {
    background: linear-gradient(135deg, #dc3545, #c82333);
    color: white;
}

/* Empty State */
.empty-state-card {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 24px;
    padding: 4rem 2rem;
    text-align: center;
    position: relative;
    z-index: 1;
    box-shadow: 0 25px 45px rgba(0, 0, 0, 0.1);
}

.empty-icon {
    width: 120px;
    height: 120px;
    margin: 0 auto 2rem;
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.2), rgba(118, 75, 162, 0.2));
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid rgba(255, 255, 255, 0.2);
}

.empty-icon i {
    font-size: 4rem;
    color: rgba(255, 255, 255, 0.6);
}

.empty-title {
    color: white;
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 1rem;
}

.empty-description {
    color: rgba(255, 255, 255, 0.7);
    font-size: 1.1rem;
    line-height: 1.6;
    max-width: 500px;
    margin: 0 auto 2rem;
}

.empty-cta-button {
    display: inline-flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem 2rem;
    background: linear-gradient(135deg, #007bff, #0056b3);
    color: white;
    text-decoration: none;
    border-radius: 16px;
    font-weight: 600;
    font-size: 1.05rem;
    box-shadow: 0 10px 25px rgba(0, 123, 255, 0.3);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.empty-cta-button::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: rgba(255, 255, 255, 0.1);
    transition: left 0.3s ease;
}

.empty-cta-button:hover::before {
    left: 0;
}

.empty-cta-button:hover {
    transform: translateY(-3px);
    box-shadow: 0 15px 35px rgba(0, 123, 255, 0.4);
}

/* Responsive Design */
@media (max-width: 768px) {
    .header-content {
        flex-direction: column;
        text-align: center;
    }

    .page-title {
        font-size: 2rem;
        justify-content: center;
    }

    .page-subtitle {
        margin-left: 0;
    }

    .stats-row {
        grid-template-columns: 1fr;
    }

    .table-wrapper {
        overflow-x: scroll;
    }

    .applications-table {
        min-width: 800px;
    }

    .table-header {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
    }
}

@media (max-width: 576px) {
    .page-header-card,
    .table-card,
    .empty-state-card {
        padding: 1.5rem;
    }

    .page-title {
        font-size: 1.75rem;
    }

    .stat-mini-card {
        padding: 1rem;
    }

    .stat-mini-icon {
        width: 50px;
        height: 50px;
        font-size: 1.5rem;
    }

    .stat-mini-value {
        font-size: 2rem;
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
});
</script>
@endsection
