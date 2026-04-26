@extends('recruiter.layouts.app')

@section('content')
@push('styles')
<style>
    /* ── Page Header ── */
    .page-header { margin-bottom: 1.75rem; }
    .page-header h1 { color: #fff; font-size: clamp(1.3rem, 4vw, 1.8rem); font-weight: 700; }
    .page-header p  { color: rgba(255,255,255,.5); margin-top: .3rem; font-size: .875rem; }

    /* ── Grid layout ── */
    .analytics-top-row {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 1.25rem; margin-bottom: 1.25rem;
    }
    .card {
        background: rgba(255,255,255,.07); border: 1px solid rgba(255,255,255,.1);
        border-radius: 16px; padding: 1.4rem;
    }
    .card-title {
        color: #fff; font-size: .95rem; font-weight: 600;
        margin-bottom: 1.25rem; display: flex; align-items: center; gap: .5rem;
    }
    .card + .card { margin-bottom: 1.25rem; }

    /* ── Big metric ── */
    .metric-big { text-align: center; padding: 1.5rem 0; }
    .metric-big .value { font-size: 3.5rem; font-weight: 800; color: #e94560; line-height: 1; }
    .metric-big .label { color: rgba(255,255,255,.45); font-size: .875rem; margin-top: .5rem; }

    /* ── Rate bars ── */
    .rate-row {
        display: flex; align-items: center; gap: .75rem;
        padding: .6rem 0; border-bottom: 1px solid rgba(255,255,255,.05);
    }
    .rate-row:last-child { border-bottom: none; }
    .rate-label { color: rgba(255,255,255,.7); font-size: .85rem; min-width: 0; flex: 1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .rate-bar-wrap { flex: 1; min-width: 50px; background: rgba(255,255,255,.07); border-radius: 4px; height: 6px; }
    .rate-bar { height: 6px; border-radius: 4px; background: linear-gradient(90deg, #e94560, #c62a47); }
    .rate-value { color: #fff; font-weight: 600; font-size: .85rem; flex-shrink: 0; min-width: 2rem; text-align: right; }

    /* ── Skills ── */
    .skills-grid { display: flex; flex-wrap: wrap; gap: .4rem; }
    .skill-tag {
        background: rgba(233,69,96,.12); border: 1px solid rgba(233,69,96,.25);
        color: #e94560; padding: .25rem .65rem; border-radius: 20px; font-size: .8rem; font-weight: 500;
    }
    .empty-msg { color: rgba(255,255,255,.4); font-size: .875rem; text-align: center; padding: 1.5rem 0; }

    /* ── Chart wrapper ── */
    .chart-container {
        position: relative; width: 100%;
        overflow-x: auto;
    }
    .chart-container canvas {
        min-height: 180px;
    }

    /* ── Stat highlight cards ── */
    .stat-row {
        display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
        gap: 1rem; margin-bottom: 1.25rem;
    }
    .stat-mini {
        background: rgba(255,255,255,.06); border: 1px solid rgba(255,255,255,.08);
        border-radius: 12px; padding: 1rem; text-align: center;
    }
    .stat-mini .val { color: #fff; font-size: 1.6rem; font-weight: 800; line-height: 1; }
    .stat-mini .lbl { color: rgba(255,255,255,.45); font-size: .75rem; margin-top: .3rem; }

    /* ── Responsive ── */
    @media (max-width: 768px) {
        .analytics-top-row { grid-template-columns: 1fr; }
    }
</style>
@endpush

<div class="page-header">
    <h1><i class="fas fa-chart-bar" style="color:#e94560;margin-right:.5rem"></i>Analytics</h1>
    <p>Insights into your recruitment funnel</p>
</div>

{{-- Top stats --}}
<div class="stat-row">
    @php
        $totalApps = array_sum($funnel ?? []);
        $approved  = $funnel['approved'] ?? 0;
        $rejected  = $funnel['rejected'] ?? 0;
        $convRate  = $totalApps > 0 ? round(($approved / $totalApps) * 100) : 0;
    @endphp
    <div class="stat-mini">
        <div class="val">{{ $totalApps }}</div>
        <div class="lbl">Total Applications</div>
    </div>
    <div class="stat-mini">
        <div class="val" style="color:#6fcf97">{{ $approved }}</div>
        <div class="lbl">Approved</div>
    </div>
    <div class="stat-mini">
        <div class="val" style="color:#e94560">{{ $rejected }}</div>
        <div class="lbl">Rejected</div>
    </div>
    <div class="stat-mini">
        <div class="val" style="color:#f2c94c">{{ $convRate }}%</div>
        <div class="lbl">Conversion Rate</div>
    </div>
</div>

{{-- Funnel chart + Time to Hire --}}
<div class="analytics-top-row">
    <div class="card">
        <div class="card-title"><i class="fas fa-filter"></i>Application Funnel</div>
        @if(!empty($funnel))
            <div class="chart-container">
                <canvas id="funnelChart"></canvas>
            </div>
        @else
            <div class="empty-msg">
                <i class="fas fa-chart-bar" style="font-size:2rem;opacity:.3;display:block;margin-bottom:.5rem"></i>
                No application data yet.
            </div>
        @endif
    </div>

    <div class="card">
        <div class="card-title"><i class="fas fa-hourglass-half"></i>Avg. Time to Hire</div>
        @if($avgTimeToHire)
            <div class="metric-big">
                <div class="value">{{ $avgTimeToHire }}</div>
                <div class="label">days from application<br>to approval</div>
            </div>
        @else
            <div class="empty-msg">
                <i class="fas fa-hourglass" style="font-size:2rem;opacity:.3;display:block;margin-bottom:.5rem"></i>
                No approved candidates yet.
            </div>
        @endif
    </div>
</div>

{{-- Application Rate per Internship --}}
@if(!empty($applicationRate))
<div class="card" style="margin-bottom:1.25rem">
    <div class="card-title"><i class="fas fa-chart-line"></i>Applications per Internship</div>
    @php $maxCount = max(array_column($applicationRate, 'count')) ?: 1; @endphp
    @foreach($applicationRate as $item)
        <div class="rate-row">
            <span class="rate-label" title="{{ $item['title'] }}">{{ $item['title'] }}</span>
            <div class="rate-bar-wrap">
                <div class="rate-bar" style="width:{{ round(($item['count'] / $maxCount) * 100) }}%"></div>
            </div>
            <span class="rate-value">{{ $item['count'] }}</span>
        </div>
    @endforeach
</div>
@endif

{{-- Top Skills --}}
<div class="card">
    <div class="card-title"><i class="fas fa-star"></i>Top Skills from Approved Candidates</div>
    @if(!empty($topSkills))
        <div class="skills-grid">
            @foreach($topSkills as $skill => $count)
                <span class="skill-tag">{{ $skill }} <strong>({{ $count }})</strong></span>
            @endforeach
        </div>
    @else
        <div class="empty-msg">
            <i class="fas fa-star" style="font-size:2rem;opacity:.3;display:block;margin-bottom:.5rem"></i>
            No approved candidates yet.
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
(function() {
    'use strict';
    @if(!empty($funnel))
    const labels = @json(array_keys($funnel));
    const values = @json(array_values($funnel));
    const canvas = document.getElementById('funnelChart');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Applications',
                data: values,
                backgroundColor: [
                    'rgba(255,193,7,.75)',
                    'rgba(0,123,255,.75)',
                    'rgba(111,66,193,.75)',
                    'rgba(23,162,184,.75)',
                    'rgba(40,167,69,.75)',
                    'rgba(220,53,69,.75)',
                ],
                borderRadius: 7,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(26,26,46,.95)',
                    borderColor: 'rgba(255,255,255,.1)',
                    borderWidth: 1,
                    titleColor: '#fff',
                    bodyColor: 'rgba(255,255,255,.7)',
                    cornerRadius: 8,
                    padding: 10,
                }
            },
            scales: {
                x: {
                    ticks: { color: 'rgba(255,255,255,.6)', font: { size: 11 } },
                    grid: { color: 'rgba(255,255,255,.04)' }
                },
                y: {
                    ticks: { color: 'rgba(255,255,255,.6)', stepSize: 1, font: { size: 11 } },
                    grid: { color: 'rgba(255,255,255,.04)' },
                    beginAtZero: true,
                },
            }
        }
    });
    @endif
})();
</script>
@endpush
