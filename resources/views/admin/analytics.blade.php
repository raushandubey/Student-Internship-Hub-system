@extends('admin.layout')

@section('title', 'Analytics Dashboard')

@section('content')
<div class="analytics-container">
    <div class="page-header">
        <h1><i class="fas fa-chart-bar"></i> Analytics Dashboard</h1>
        <p class="text-muted">Comprehensive insights into application performance and matching metrics</p>
    </div>

    <!-- Overall Stats Cards -->
    <div class="stats-row">
        <div class="stat-card primary">
            <div class="stat-icon"><i class="fas fa-file-alt"></i></div>
            <div class="stat-content">
                <div class="stat-value">{{ $overallStats['total_applications'] }}</div>
                <div class="stat-label">Total Applications</div>
            </div>
        </div>
        <div class="stat-card success">
            <div class="stat-icon"><i class="fas fa-briefcase"></i></div>
            <div class="stat-content">
                <div class="stat-value">{{ $overallStats['active_internships'] }}</div>
                <div class="stat-label">Active Internships</div>
            </div>
        </div>
        <div class="stat-card info">
            <div class="stat-icon"><i class="fas fa-users"></i></div>
            <div class="stat-content">
                <div class="stat-value">{{ $overallStats['total_students'] }}</div>
                <div class="stat-label">Registered Students</div>
            </div>
        </div>
        <div class="stat-card warning">
            <div class="stat-icon"><i class="fas fa-percentage"></i></div>
            <div class="stat-content">
                <div class="stat-value">{{ $overallStats['avg_match_score'] }}%</div>
                <div class="stat-label">Avg Match Score</div>
            </div>
        </div>
    </div>

    <div class="analytics-grid">
        <!-- Approval/Rejection Ratio -->
        <div class="analytics-card">
            <div class="card-header">
                <h3><i class="fas fa-chart-pie"></i> Approval Ratio</h3>
            </div>
            <div class="card-body">
                <div class="ratio-display">
                    <div class="ratio-bar">
                        <div class="ratio-approved" style="width: {{ $approvalRatio['approval_rate'] }}%"></div>
                        <div class="ratio-rejected" style="width: {{ $approvalRatio['rejection_rate'] }}%"></div>
                    </div>
                    <div class="ratio-labels">
                        <span class="approved-label">
                            <i class="fas fa-check-circle"></i> 
                            Approved: {{ $approvalRatio['approved'] }} ({{ $approvalRatio['approval_rate'] }}%)
                        </span>
                        <span class="rejected-label">
                            <i class="fas fa-times-circle"></i> 
                            Rejected: {{ $approvalRatio['rejected'] }} ({{ $approvalRatio['rejection_rate'] }}%)
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Status Breakdown -->
        <div class="analytics-card">
            <div class="card-header">
                <h3><i class="fas fa-tasks"></i> Status Breakdown</h3>
            </div>
            <div class="card-body">
                <div class="status-list">
                    @foreach($statusBreakdown as $status => $count)
                        <div class="status-item">
                            <span class="status-name">{{ ucfirst(str_replace('_', ' ', $status)) }}</span>
                            <span class="status-count">{{ $count }}</span>
                            <div class="status-bar">
                                <div class="status-fill" style="width: {{ $overallStats['total_applications'] > 0 ? ($count / $overallStats['total_applications']) * 100 : 0 }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Match Score Distribution -->
        <div class="analytics-card">
            <div class="card-header">
                <h3><i class="fas fa-bullseye"></i> Match Score Distribution</h3>
            </div>
            <div class="card-body">
                <div class="distribution-grid">
                    <div class="dist-item excellent">
                        <div class="dist-value">{{ $matchDistribution['excellent'] }}</div>
                        <div class="dist-label">Excellent (80%+)</div>
                        <div class="dist-bar"><div class="dist-fill" style="width: 100%"></div></div>
                    </div>
                    <div class="dist-item good">
                        <div class="dist-value">{{ $matchDistribution['good'] }}</div>
                        <div class="dist-label">Good (60-79%)</div>
                        <div class="dist-bar"><div class="dist-fill" style="width: 75%"></div></div>
                    </div>
                    <div class="dist-item fair">
                        <div class="dist-value">{{ $matchDistribution['fair'] }}</div>
                        <div class="dist-label">Fair (40-59%)</div>
                        <div class="dist-bar"><div class="dist-fill" style="width: 50%"></div></div>
                    </div>
                    <div class="dist-item low">
                        <div class="dist-value">{{ $matchDistribution['low'] }}</div>
                        <div class="dist-label">Low (&lt;40%)</div>
                        <div class="dist-bar"><div class="dist-fill" style="width: 25%"></div></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Trends -->
        <div class="analytics-card">
            <div class="card-header">
                <h3><i class="fas fa-chart-line"></i> Applications (Last 7 Days)</h3>
            </div>
            <div class="card-body">
                <div class="trends-chart">
                    @php $maxTrend = max($recentTrends) ?: 1; @endphp
                    @foreach($recentTrends as $date => $count)
                        <div class="trend-bar-container">
                            <div class="trend-bar" style="height: {{ ($count / $maxTrend) * 100 }}%">
                                <span class="trend-value">{{ $count }}</span>
                            </div>
                            <span class="trend-date">{{ \Carbon\Carbon::parse($date)->format('D') }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Top Internships Table -->
    <div class="analytics-card full-width">
        <div class="card-header">
            <h3><i class="fas fa-trophy"></i> Top Internships by Applications</h3>
        </div>
        <div class="card-body">
            <table class="analytics-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Internship</th>
                        <th>Organization</th>
                        <th>Applications</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($topInternships as $index => $item)
                        <tr>
                            <td><span class="rank-badge">{{ $index + 1 }}</span></td>
                            <td>{{ $item['title'] }}</td>
                            <td>{{ $item['organization'] }}</td>
                            <td><span class="count-badge">{{ $item['applications_count'] }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center">No data available</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Top Performing Internships -->
    <div class="analytics-card full-width">
        <div class="card-header">
            <h3><i class="fas fa-star"></i> Top Performing Internships (by Approvals)</h3>
        </div>
        <div class="card-body">
            <table class="analytics-table">
                <thead>
                    <tr>
                        <th>Internship</th>
                        <th>Organization</th>
                        <th>Total Apps</th>
                        <th>Approved</th>
                        <th>Avg Match Score</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($topPerforming as $item)
                        <tr>
                            <td>{{ $item['title'] }}</td>
                            <td>{{ $item['organization'] }}</td>
                            <td>{{ $item['total_apps'] }}</td>
                            <td><span class="approved-badge">{{ $item['approved'] }}</span></td>
                            <td><span class="score-badge">{{ $item['avg_score'] }}%</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center">No data available</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.analytics-container { padding: 20px; }
.page-header { margin-bottom: 30px; }
.page-header h1 { color: #333; margin-bottom: 5px; }
.page-header h1 i { color: #667eea; margin-right: 10px; }

.stats-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
}

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    color: white;
}

.stat-card.primary .stat-icon { background: linear-gradient(135deg, #667eea, #764ba2); }
.stat-card.success .stat-icon { background: linear-gradient(135deg, #28a745, #20c997); }
.stat-card.info .stat-icon { background: linear-gradient(135deg, #17a2b8, #6f42c1); }
.stat-card.warning .stat-icon { background: linear-gradient(135deg, #ffc107, #fd7e14); }

.stat-value { font-size: 1.8rem; font-weight: 700; color: #333; }
.stat-label { color: #666; font-size: 0.9rem; }

.analytics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.analytics-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    overflow: hidden;
}

.analytics-card.full-width { grid-column: 1 / -1; }

.card-header {
    padding: 15px 20px;
    border-bottom: 1px solid #eee;
    background: #f8f9fa;
}

.card-header h3 {
    margin: 0;
    font-size: 1rem;
    color: #333;
}

.card-header h3 i { color: #667eea; margin-right: 8px; }

.card-body { padding: 20px; }

/* Ratio Display */
.ratio-bar {
    height: 30px;
    border-radius: 15px;
    overflow: hidden;
    display: flex;
    background: #eee;
    margin-bottom: 15px;
}

.ratio-approved { background: linear-gradient(135deg, #28a745, #20c997); }
.ratio-rejected { background: linear-gradient(135deg, #dc3545, #c82333); }

.ratio-labels { display: flex; justify-content: space-between; }
.approved-label { color: #28a745; font-weight: 600; }
.rejected-label { color: #dc3545; font-weight: 600; }

/* Status List */
.status-item {
    display: grid;
    grid-template-columns: 1fr auto 100px;
    align-items: center;
    gap: 10px;
    padding: 8px 0;
    border-bottom: 1px solid #f0f0f0;
}

.status-name { font-weight: 500; text-transform: capitalize; }
.status-count { font-weight: 700; color: #667eea; }
.status-bar { height: 6px; background: #eee; border-radius: 3px; }
.status-fill { height: 100%; background: linear-gradient(135deg, #667eea, #764ba2); border-radius: 3px; }

/* Distribution Grid */
.distribution-grid { display: grid; gap: 15px; }
.dist-item { display: grid; grid-template-columns: 60px 1fr 1fr; align-items: center; gap: 10px; }
.dist-value { font-size: 1.5rem; font-weight: 700; }
.dist-label { font-size: 0.85rem; color: #666; }
.dist-bar { height: 8px; background: #eee; border-radius: 4px; }
.dist-fill { height: 100%; border-radius: 4px; }

.dist-item.excellent .dist-value { color: #28a745; }
.dist-item.excellent .dist-fill { background: #28a745; }
.dist-item.good .dist-value { color: #17a2b8; }
.dist-item.good .dist-fill { background: #17a2b8; }
.dist-item.fair .dist-value { color: #ffc107; }
.dist-item.fair .dist-fill { background: #ffc107; }
.dist-item.low .dist-value { color: #dc3545; }
.dist-item.low .dist-fill { background: #dc3545; }

/* Trends Chart */
.trends-chart {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    height: 150px;
    gap: 10px;
}

.trend-bar-container {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    height: 100%;
}

.trend-bar {
    width: 100%;
    max-width: 40px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    border-radius: 4px 4px 0 0;
    display: flex;
    align-items: flex-start;
    justify-content: center;
    min-height: 5px;
    margin-top: auto;
}

.trend-value {
    color: white;
    font-size: 0.75rem;
    font-weight: 600;
    padding-top: 5px;
}

.trend-date { font-size: 0.75rem; color: #666; margin-top: 5px; }

/* Tables */
.analytics-table {
    width: 100%;
    border-collapse: collapse;
}

.analytics-table th,
.analytics-table td {
    padding: 12px 15px;
    text-align: left;
    border-bottom: 1px solid #eee;
}

.analytics-table th {
    background: #f8f9fa;
    font-weight: 600;
    color: #333;
}

.rank-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border-radius: 50%;
    font-weight: 600;
    font-size: 0.85rem;
}

.count-badge {
    background: #e3f2fd;
    color: #1976d2;
    padding: 4px 12px;
    border-radius: 12px;
    font-weight: 600;
}

.approved-badge {
    background: #e8f5e9;
    color: #2e7d32;
    padding: 4px 12px;
    border-radius: 12px;
    font-weight: 600;
}

.score-badge {
    background: #fff3e0;
    color: #e65100;
    padding: 4px 12px;
    border-radius: 12px;
    font-weight: 600;
}
</style>
@endsection
