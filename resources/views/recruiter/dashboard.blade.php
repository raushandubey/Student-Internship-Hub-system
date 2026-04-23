@extends('recruiter.layouts.app')

@section('content')
<style>
    .page-header { margin-bottom: 2rem; }
    .page-header h1 { color: #fff; font-size: 1.8rem; font-weight: 700; }
    .page-header p  { color: rgba(255,255,255,.6); margin-top: .3rem; }

    .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
    .stat-card {
        background: rgba(255,255,255,.07);
        border: 1px solid rgba(255,255,255,.1);
        border-radius: 16px; padding: 1.5rem;
        display: flex; align-items: center; gap: 1rem;
        transition: transform .2s;
    }
    .stat-card:hover { transform: translateY(-3px); }
    .stat-icon {
        width: 52px; height: 52px; border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.4rem; flex-shrink: 0;
    }
    .stat-icon.blue   { background: rgba(0,123,255,.2); color: #4da3ff; }
    .stat-icon.green  { background: rgba(40,167,69,.2);  color: #6fcf97; }
    .stat-icon.yellow { background: rgba(255,193,7,.2);  color: #f2c94c; }
    .stat-value { font-size: 2rem; font-weight: 800; color: #fff; line-height: 1; }
    .stat-label { font-size: .85rem; color: rgba(255,255,255,.6); margin-top: .25rem; }

    .charts-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; margin-bottom: 2rem; }
    .card {
        background: rgba(255,255,255,.07);
        border: 1px solid rgba(255,255,255,.1);
        border-radius: 16px; padding: 1.5rem;
    }
    .card-title { color: #fff; font-size: 1rem; font-weight: 600; margin-bottom: 1.2rem; }
    .metric-row { display: flex; justify-content: space-between; align-items: center; padding: .6rem 0; border-bottom: 1px solid rgba(255,255,255,.06); }
    .metric-row:last-child { border-bottom: none; }
    .metric-label { color: rgba(255,255,255,.7); font-size: .9rem; }
    .metric-value { color: #fff; font-weight: 600; }

    .skills-list { display: flex; flex-wrap: wrap; gap: .5rem; }
    .skill-tag {
        background: rgba(233,69,96,.15); border: 1px solid rgba(233,69,96,.3);
        color: #e94560; padding: .3rem .75rem; border-radius: 20px; font-size: .8rem; font-weight: 500;
    }
</style>

<div class="page-header">
    <h1><i class="fas fa-tachometer-alt me-2"></i>Recruiter Dashboard</h1>
    <p>Welcome back, {{ auth()->user()->name }}</p>
</div>

{{-- Stats --}}
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fas fa-briefcase"></i></div>
        <div>
            <div class="stat-value">{{ $stats['total_internships'] }}</div>
            <div class="stat-label">Total Internships</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fas fa-users"></i></div>
        <div>
            <div class="stat-value">{{ $stats['total_applicants'] }}</div>
            <div class="stat-label">Total Applicants</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon yellow"><i class="fas fa-clock"></i></div>
        <div>
            <div class="stat-value">{{ $stats['pending_applications'] }}</div>
            <div class="stat-label">Pending Review</div>
        </div>
    </div>
    @if($avgTimeToHire)
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fas fa-hourglass-half"></i></div>
        <div>
            <div class="stat-value">{{ $avgTimeToHire }}d</div>
            <div class="stat-label">Avg. Time to Hire</div>
        </div>
    </div>
    @endif
</div>

{{-- Charts --}}
<div class="charts-grid">
    <div class="card">
        <div class="card-title"><i class="fas fa-filter me-2"></i>Application Funnel</div>
        <canvas id="funnelChart" height="120"></canvas>
    </div>

    <div class="card">
        <div class="card-title"><i class="fas fa-star me-2"></i>Top Skills (Approved)</div>
        @if(!empty($topSkills))
            <div class="skills-list">
                @foreach($topSkills as $skill => $count)
                    <span class="skill-tag">{{ $skill }} ({{ $count }})</span>
                @endforeach
            </div>
        @else
            <p style="color:rgba(255,255,255,.5);font-size:.9rem">No approved candidates yet.</p>
        @endif
    </div>
</div>

{{-- Application rate per internship --}}
@if(!empty($applicationRate))
<div class="card">
    <div class="card-title"><i class="fas fa-chart-line me-2"></i>Applications per Internship</div>
    @foreach($applicationRate as $item)
        <div class="metric-row">
            <span class="metric-label">{{ $item['title'] }}</span>
            <span class="metric-value">{{ $item['count'] }}</span>
        </div>
    @endforeach
</div>
@endif
@endsection

@push('scripts')
<script>
const funnelData = @json($funnel);
const ctx = document.getElementById('funnelChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: Object.keys(funnelData),
        datasets: [{
            label: 'Applications',
            data: Object.values(funnelData),
            backgroundColor: [
                'rgba(255,193,7,.7)',
                'rgba(0,123,255,.7)',
                'rgba(111,66,193,.7)',
                'rgba(23,162,184,.7)',
                'rgba(40,167,69,.7)',
                'rgba(220,53,69,.7)',
            ],
            borderRadius: 6,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            x: { ticks: { color: 'rgba(255,255,255,.7)' }, grid: { color: 'rgba(255,255,255,.05)' } },
            y: { ticks: { color: 'rgba(255,255,255,.7)', stepSize: 1 }, grid: { color: 'rgba(255,255,255,.05)' } },
        }
    }
});
</script>
@endpush
