@extends('recruiter.layouts.app')

@section('content')
@push('styles')
<style>
    /* ── Page Header ── */
    .page-header { margin-bottom: 1.75rem; }
    .page-header h1 { color: #fff; font-size: clamp(1.4rem, 4vw, 1.9rem); font-weight: 700; line-height: 1.2; }
    .page-header p  { color: rgba(255,255,255,.6); margin-top: .3rem; font-size: .9rem; }

    /* ── Stats Grid ── */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        gap: 1rem; margin-bottom: 1.75rem;
    }
    .stat-card {
        background: rgba(255,255,255,.07);
        border: 1px solid rgba(255,255,255,.1);
        border-radius: 16px; padding: 1.25rem;
        display: flex; align-items: center; gap: .9rem;
        transition: transform .2s, box-shadow .2s;
        cursor: default;
    }
    .stat-card:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(0,0,0,.25); }
    .stat-icon {
        width: 48px; height: 48px; border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.25rem; flex-shrink: 0;
    }
    .stat-icon.blue   { background: rgba(0,123,255,.18); color: #4da3ff; }
    .stat-icon.green  { background: rgba(40,167,69,.18);  color: #6fcf97; }
    .stat-icon.yellow { background: rgba(255,193,7,.18);  color: #f2c94c; }
    .stat-icon.red    { background: rgba(233,69,96,.18);  color: #e94560; }
    .stat-value { font-size: 1.9rem; font-weight: 800; color: #fff; line-height: 1; }
    .stat-label { font-size: .8rem; color: rgba(255,255,255,.55); margin-top: .2rem; }

    /* ── Charts Grid ── */
    .charts-grid {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 1.25rem; margin-bottom: 1.75rem;
    }
    .card {
        background: rgba(255,255,255,.07);
        border: 1px solid rgba(255,255,255,.1);
        border-radius: 16px; padding: 1.5rem;
    }
    .card-title {
        color: #fff; font-size: .95rem; font-weight: 600;
        margin-bottom: 1.25rem; display: flex; align-items: center; gap: .5rem;
    }
    .metric-row {
        display: flex; justify-content: space-between; align-items: center;
        padding: .6rem 0; border-bottom: 1px solid rgba(255,255,255,.06);
    }
    .metric-row:last-child { border-bottom: none; }
    .metric-label { color: rgba(255,255,255,.65); font-size: .875rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 70%; }
    .metric-value { color: #fff; font-weight: 600; font-size: .9rem; flex-shrink: 0; margin-left: .5rem; }

    /* ── Skills ── */
    .skills-list { display: flex; flex-wrap: wrap; gap: .4rem; }
    .skill-tag {
        background: rgba(233,69,96,.12); border: 1px solid rgba(233,69,96,.25);
        color: #e94560; padding: .25rem .65rem; border-radius: 20px; font-size: .78rem; font-weight: 500;
    }

    /* ── Chart wrapper scroll ── */
    .chart-scroll { overflow-x: auto; }
    .chart-scroll canvas { min-width: 0; }

    /* ── Responsive ── */
    @media (max-width: 768px) {
        .charts-grid { grid-template-columns: 1fr; }
        .stats-grid  { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 400px) {
        .stats-grid { grid-template-columns: 1fr; }
    }
</style>
@endpush

<div class="page-header">
    <h1><i class="fas fa-tachometer-alt" style="color:#e94560;margin-right:.5rem"></i>Recruiter Dashboard</h1>
    <p>Welcome back, <strong style="color:rgba(255,255,255,.85)">{{ auth()->user()->name }}</strong></p>
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
        <div class="stat-icon red"><i class="fas fa-hourglass-half"></i></div>
        <div>
            <div class="stat-value">{{ $avgTimeToHire }}<span style="font-size:1rem">d</span></div>
            <div class="stat-label">Avg. Time to Hire</div>
        </div>
    </div>
    @endif
</div>

{{-- Charts --}}
<div class="charts-grid">

    {{-- Application Funnel --}}
    <div class="card">
        <div class="card-title"><i class="fas fa-filter"></i>Application Funnel</div>

        @php
            $funnelColors = [
                'rgba(255,193,7,1)',
                'rgba(0,123,255,1)',
                'rgba(111,66,193,1)',
                'rgba(23,162,184,1)',
                'rgba(40,167,69,1)',
                'rgba(220,53,69,1)',
            ];
            $funnelBg = [
                'rgba(255,193,7,.12)',
                'rgba(0,123,255,.12)',
                'rgba(111,66,193,.12)',
                'rgba(23,162,184,.12)',
                'rgba(40,167,69,.12)',
                'rgba(220,53,69,.12)',
            ];
            $funnelTotal = array_sum($funnel);
        @endphp

        {{-- Progress-bar rows - always readable even with 0/1 counts --}}
        <div style="display:flex;flex-direction:column;gap:.55rem;margin-bottom:1.1rem">
            @foreach($funnel as $label => $count)
                @php
                    $idx  = $loop->index;
                    $pct  = $funnelTotal > 0 ? round(($count / $funnelTotal) * 100) : 0;
                    $barW = $funnelTotal > 0 ? max(6, round(($count / $funnelTotal) * 100)) : 6;
                @endphp
                <div style="display:flex;align-items:center;gap:.65rem">
                    <div style="width:110px;flex-shrink:0;font-size:.76rem;font-weight:500;
                                color:rgba(255,255,255,.75);white-space:nowrap;overflow:hidden;
                                text-overflow:ellipsis" title="{{ $label }}">{{ $label }}</div>
                    <div style="flex:1;background:{{ $funnelBg[$idx] }};border-radius:5px;
                                height:20px;overflow:hidden;min-width:50px">
                        <div style="width:{{ $barW }}%;height:100%;background:{{ $funnelColors[$idx] }};
                                    border-radius:5px;display:flex;align-items:center;
                                    justify-content:flex-end;padding-right:5px;
                                    min-width:{{ $count > 0 ? '24px' : '0' }};
                                    transition:width .5s ease">
                            @if($count > 0)
                                <span style="font-size:.67rem;font-weight:700;color:#fff">{{ $count }}</span>
                            @endif
                        </div>
                    </div>
                    <div style="flex-shrink:0;min-width:48px;text-align:right">
                        <span style="font-size:.82rem;font-weight:700;color:#fff">{{ $count }}</span>
                        <span style="font-size:.7rem;color:rgba(255,255,255,.4)"> {{ $pct }}%</span>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Total summary --}}
        <div style="display:flex;justify-content:space-between;align-items:center;
                    border-top:1px solid rgba(255,255,255,.07);padding-top:.8rem;margin-bottom:1rem">
            <span style="font-size:.78rem;color:rgba(255,255,255,.4)">Total Applications</span>
            <span style="font-size:1.05rem;font-weight:800;color:#fff">{{ $funnelTotal }}</span>
        </div>

        {{-- Mini bar chart as secondary visual --}}
        <canvas id="funnelChart" height="80"></canvas>
    </div>

    <div class="card">
        <div class="card-title"><i class="fas fa-star"></i>Top Skills (Approved)</div>
        @if(!empty($topSkills))
            <div class="skills-list">
                @foreach($topSkills as $skill => $count)
                    <span class="skill-tag">{{ $skill }} <strong>({{ $count }})</strong></span>
                @endforeach
            </div>
        @else
            <p style="color:rgba(255,255,255,.45);font-size:.875rem;text-align:center;padding:1.5rem 0">
                <i class="fas fa-star" style="display:block;font-size:1.8rem;margin-bottom:.5rem;opacity:.3"></i>
                No approved candidates yet.
            </p>
        @endif
    </div>
</div>

{{-- Application rate per internship --}}
@if(!empty($applicationRate))
<div class="card">
    <div class="card-title"><i class="fas fa-chart-line"></i>Applications per Internship</div>
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
(function() {
    'use strict';
    const funnelData = @json($funnel);
    const canvas = document.getElementById('funnelChart');
    if (!canvas) return;
    const values  = Object.values(funnelData);
    const labels  = Object.keys(funnelData);
    const total   = values.reduce(function(a, b) { return a + b; }, 0);
    const maxVal  = Math.max.apply(null, values);

    // Short label map
    const SHORT = {
        'Pending': 'Pending', 'Under Review': 'Review',
        'Shortlisted': 'Short.', 'Interview Scheduled': 'Interv.',
        'Approved': 'Apprvd', 'Rejected': 'Reject.'
    };

    new Chart(canvas.getContext('2d'), {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Applications',
                data: values,
                backgroundColor: [
                    'rgba(255,193,7,.8)','rgba(0,123,255,.8)',
                    'rgba(111,66,193,.8)','rgba(23,162,184,.8)',
                    'rgba(40,167,69,.8)','rgba(220,53,69,.8)',
                ],
                borderRadius: 5,
                borderSkipped: false,
                minBarLength: 5,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            animation: { duration: 600 },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(15,15,40,.95)',
                    borderColor: 'rgba(255,255,255,.1)',
                    borderWidth: 1,
                    titleColor: '#fff',
                    bodyColor: 'rgba(255,255,255,.7)',
                    cornerRadius: 8,
                    padding: 9,
                    callbacks: {
                        label: function(ctx) {
                            var pct = total > 0 ? Math.round((ctx.parsed.y / total) * 100) : 0;
                            return ' ' + ctx.parsed.y + ' (' + pct + '%)';
                        }
                    }
                }
            },
            scales: {
                x: {
                    ticks: {
                        color: 'rgba(255,255,255,.5)',
                        font: { size: 9 },
                        maxRotation: 40,
                        minRotation: 30,
                        callback: function(val) {
                            var lbl = this.getLabelForValue(val);
                            return SHORT[lbl] || (lbl.length > 7 ? lbl.slice(0,6) + '…' : lbl);
                        }
                    },
                    grid: { display: false }
                },
                y: {
                    beginAtZero: true,
                    suggestedMax: Math.max(5, maxVal + 1),
                    ticks: {
                        color: 'rgba(255,255,255,.5)',
                        font: { size: 10 },
                        precision: 0,
                        stepSize: 1,
                    },
                    grid: { color: 'rgba(255,255,255,.05)' }
                }
            }
        }
    });
})();
</script>
@endpush
