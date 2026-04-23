@extends('recruiter.layouts.app')

@section('content')
<style>
    .page-header { margin-bottom: 2rem; }
    .page-header h1 { color: #fff; font-size: 1.8rem; font-weight: 700; }
    .page-header p  { color: rgba(255,255,255,.5); margin-top: .3rem; }

    .analytics-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
    .card {
        background: rgba(255,255,255,.07); border: 1px solid rgba(255,255,255,.1);
        border-radius: 16px; padding: 1.5rem;
    }
    .card-title { color: #fff; font-size: 1rem; font-weight: 600; margin-bottom: 1.2rem; }

    .metric-big { text-align: center; padding: 1rem 0; }
    .metric-big .value { font-size: 3rem; font-weight: 800; color: #e94560; line-height: 1; }
    .metric-big .label { color: rgba(255,255,255,.5); font-size: .9rem; margin-top: .5rem; }

    .rate-row { display: flex; justify-content: space-between; align-items: center; padding: .6rem 0; border-bottom: 1px solid rgba(255,255,255,.06); }
    .rate-row:last-child { border-bottom: none; }
    .rate-label { color: rgba(255,255,255,.7); font-size: .9rem; }
    .rate-value { color: #fff; font-weight: 600; }
    .rate-bar-wrap { flex: 1; margin: 0 1rem; background: rgba(255,255,255,.08); border-radius: 4px; height: 6px; }
    .rate-bar { height: 6px; border-radius: 4px; background: linear-gradient(90deg, #e94560, #c62a47); }

    .skills-grid { display: flex; flex-wrap: wrap; gap: .5rem; }
    .skill-tag {
        background: rgba(233,69,96,.15); border: 1px solid rgba(233,69,96,.3);
        color: #e94560; padding: .3rem .75rem; border-radius: 20px; font-size: .82rem; font-weight: 500;
    }
    .empty-msg { color: rgba(255,255,255,.4); font-size: .9rem; text-align: center; padding: 1.5rem 0; }
</style>

<div class="page-header">
    <h1><i class="fas fa-chart-bar me-2"></i>Analytics</h1>
    <p>Insights into your recruitment funnel</p>
</div>

<div class="analytics-grid">
    {{-- Conversion Funnel --}}
    <div class="card" style="grid-column: span 2">
        <div class="card-title"><i class="fas fa-filter me-2"></i>Application Funnel</div>
        @if(!empty($funnel))
            <canvas id="funnelChart" height="100"></canvas>
        @else
            <div class="empty-msg">No application data yet.</div>
        @endif
    </div>

    {{-- Time to Hire --}}
    <div class="card">
        <div class="card-title"><i class="fas fa-hourglass-half me-2"></i>Avg. Time to Hire</div>
        @if($avgTimeToHire)
            <div class="metric-big">
                <div class="value">{{ $avgTimeToHire }}</div>
                <div class="label">days from application to approval</div>
            </div>
        @else
            <div class="empty-msg">No approved candidates yet.</div>
        @endif
    </div>
</div>

{{-- Application Rate per Internship --}}
@if(!empty($applicationRate))
<div class="card" style="margin-bottom:1.5rem">
    <div class="card-title"><i class="fas fa-chart-line me-2"></i>Applications per Internship</div>
    @php $maxCount = max(array_column($applicationRate, 'count')) ?: 1; @endphp
    @foreach($applicationRate as $item)
        <div class="rate-row">
            <span class="rate-label">{{ $item['title'] }}</span>
            <div class="rate-bar-wrap">
                <div class="rate-bar" style="width: {{ round(($item['count'] / $maxCount) * 100) }}%"></div>
            </div>
            <span class="rate-value">{{ $item['count'] }}</span>
        </div>
    @endforeach
</div>
@endif

{{-- Top Skills --}}
<div class="card">
    <div class="card-title"><i class="fas fa-star me-2"></i>Top Skills from Approved Candidates</div>
    @if(!empty($topSkills))
        <div class="skills-grid">
            @foreach($topSkills as $skill => $count)
                <span class="skill-tag">{{ $skill }} <strong>({{ $count }})</strong></span>
            @endforeach
        </div>
    @else
        <div class="empty-msg">No approved candidates yet.</div>
    @endif
</div>
@endsection

@push('scripts')
<script>
@if(!empty($funnel))
const ctx = document.getElementById('funnelChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: @json(array_keys($funnel)),
        datasets: [{
            label: 'Applications',
            data: @json(array_values($funnel)),
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
@endif
</script>
@endpush
