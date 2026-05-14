@extends('student.layouts.app')
@section('content')
@push('styles')
<style>
:root{--accent:#e94560;--green:#6fcf97;--yellow:#f2c94c;--orange:#f2994a;--red:#eb5757;--blue:#4da3ff}

.page-header{margin-bottom:1.75rem}
.page-header h1{color:#fff;font-size:clamp(1.4rem,4vw,1.9rem);font-weight:700}
.page-header p{color:rgba(255,255,255,.55);margin-top:.3rem;font-size:.875rem}

/* ── Dashboard Grid ── */
.dash-grid{display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;margin-bottom:1.5rem}
.dash-grid.full{grid-template-columns:1fr}
.card{background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.1);border-radius:16px;padding:1.4rem}
.card-title{color:#fff;font-size:.95rem;font-weight:700;margin-bottom:1.25rem;display:flex;align-items:center;gap:.5rem}
.card-title i{color:var(--accent)}

/* ── Visibility Score ── */
.visibility-ring{width:90px;height:90px;border-radius:50%;display:flex;flex-direction:column;align-items:center;justify-content:center;border:4px solid;margin:0 auto 1rem;transition:all .4s}
.visibility-score{font-size:1.6rem;font-weight:800;color:#fff;line-height:1}
.visibility-label{font-size:.7rem;color:rgba(255,255,255,.55);margin-top:.2rem}

/* ── Profile Strength Bars ── */
.strength-row{display:flex;align-items:center;gap:.75rem;margin-bottom:.75rem}
.strength-label{color:rgba(255,255,255,.7);font-size:.82rem;min-width:100px}
.strength-bar{flex:1;height:8px;background:rgba(255,255,255,.08);border-radius:4px;overflow:hidden}
.strength-fill{height:100%;border-radius:4px;transition:width .8s ease}
.strength-pct{color:#fff;font-size:.82rem;font-weight:700;min-width:35px;text-align:right}

/* ── Recommendations ── */
.rec-item{display:flex;align-items:flex-start;gap:.75rem;padding:.85rem;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.07);border-radius:10px;margin-bottom:.6rem}
.rec-icon{font-size:1.3rem;flex-shrink:0;margin-top:.05rem}
.rec-text{color:rgba(255,255,255,.8);font-size:.84rem;line-height:1.4}
.rec-priority{display:inline-block;font-size:.65rem;font-weight:700;padding:.1rem .4rem;border-radius:4px;margin-bottom:.25rem}
.rec-priority.high{background:rgba(235,87,87,.2);color:#eb5757}
.rec-priority.medium{background:rgba(242,201,76,.2);color:#f2c94c}
.rec-priority.low{background:rgba(77,163,255,.2);color:#4da3ff}

/* ── Missing Skills ── */
.skill-chip{display:inline-block;background:rgba(235,87,87,.12);border:1px solid rgba(235,87,87,.25);color:#eb5757;padding:.25rem .65rem;border-radius:20px;font-size:.78rem;margin:.2rem}
.skill-chip.green{background:rgba(111,207,151,.12);border-color:rgba(111,207,151,.25);color:#6fcf97}

/* ── ATS Trend ── */
.trend-item{display:flex;justify-content:space-between;align-items:center;padding:.55rem 0;border-bottom:1px solid rgba(255,255,255,.06)}
.trend-item:last-child{border-bottom:none}
.trend-name{color:rgba(255,255,255,.7);font-size:.82rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:60%}
.trend-score{font-weight:700;font-size:.85rem}

/* ── Weak areas ── */
.weak-item{color:rgba(255,255,255,.65);font-size:.82rem;padding:.4rem 0;border-bottom:1px solid rgba(255,255,255,.06);display:flex;align-items:center;gap:.5rem}
.weak-item:last-child{border-bottom:none}

/* ── Quick Action ── */
.action-btn{display:inline-flex;align-items:center;gap:.5rem;padding:.6rem 1.2rem;border-radius:10px;font-size:.85rem;font-weight:600;text-decoration:none;transition:all .2s;cursor:pointer}
.action-btn.primary{background:rgba(233,69,96,.15);border:1px solid rgba(233,69,96,.3);color:#e94560}
.action-btn.primary:hover{background:rgba(233,69,96,.3)}
.action-btn.secondary{background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.12);color:rgba(255,255,255,.8)}
.action-btn.secondary:hover{background:rgba(255,255,255,.15)}

/* ── Empty ── */
.no-resume{text-align:center;padding:3rem;color:rgba(255,255,255,.4)}
.no-resume i{font-size:3rem;display:block;margin-bottom:1rem;opacity:.3}

@media(max-width:768px){
    .dash-grid{grid-template-columns:1fr}
    .strength-label{min-width:80px}
}
</style>
@endpush

<div class="page-header">
    <h1><i class="fas fa-chart-line" style="color:var(--accent);margin-right:.5rem"></i>Profile Intelligence</h1>
    <p>AI-powered analysis of your resume strength, ATS performance, and recruiter visibility</p>
</div>

@if(!$has_resume ?? false)
<div class="no-resume card">
    <i class="fas fa-file-upload"></i>
    <h3 style="color:#fff;margin-bottom:.5rem">No Resume Uploaded</h3>
    <p style="margin-bottom:1.25rem">{{ $message ?? 'Upload your resume to get AI-powered insights' }}</p>
    <a href="{{ route('profile.edit') }}" class="action-btn primary"><i class="fas fa-upload"></i>Upload Resume</a>
</div>
@else

{{-- Top Row: Visibility Score + Profile Strength --}}
<div class="dash-grid">

    {{-- Recruiter Visibility Score --}}
    <div class="card">
        <div class="card-title"><i class="fas fa-eye"></i>Recruiter Visibility</div>
        @php $vis = $visibility_score ?? ['score' => 0, 'label' => 'Unknown', 'color' => '#eb5757']; @endphp
        <div class="visibility-ring" style="border-color:{{ $vis['color'] }};background:{{ $vis['color'] }}18">
            <div class="visibility-score">{{ $vis['score'] }}</div>
            <div class="visibility-label">/ 100</div>
        </div>
        <div style="text-align:center;margin-bottom:1rem">
            <span style="color:{{ $vis['color'] }};font-weight:700;font-size:.9rem">{{ $vis['label'] }}</span>
            <div style="color:rgba(255,255,255,.45);font-size:.78rem;margin-top:.25rem">Recruiter Visibility Score</div>
        </div>
        <div style="display:flex;gap:.5rem;justify-content:center;flex-wrap:wrap">
            <a href="{{ route('profile.edit') }}" class="action-btn secondary" style="font-size:.78rem;padding:.45rem .9rem"><i class="fas fa-edit"></i>Edit Profile</a>
            <a href="{{ route('recommendations.index') }}" class="action-btn primary" style="font-size:.78rem;padding:.45rem .9rem"><i class="fas fa-search"></i>Find Jobs</a>
        </div>
    </div>

    {{-- Profile Strength --}}
    <div class="card">
        <div class="card-title"><i class="fas fa-shield-alt"></i>Profile Strength</div>
        @php $ps = $profile_strength ?? []; @endphp

        @if(!empty($ps))
        @foreach([
            'Overall ATS'        => $ps['overall'] ?? ['score' => 0, 'label' => '—'],
            'Technical Depth'    => $ps['technical_depth'] ?? ['score' => 0, 'label' => '—'],
            'Project Quality'    => $ps['project_quality'] ?? ['score' => 0, 'label' => '—'],
            'Backend Stack'      => $ps['backend'] ?? ['score' => 0, 'label' => '—'],
            'Profile Complete'   => ['score' => $ps['profile_completeness'] ?? 0, 'label' => ($ps['profile_completeness'] ?? 0).'%'],
        ] as $lbl => $data)
        @php
            $s = $data['score'] ?? 0;
            $barColor = $s >= 70 ? 'var(--green)' : ($s >= 50 ? 'var(--yellow)' : ($s >= 30 ? 'var(--orange)' : 'var(--red))'));
        @endphp
        <div class="strength-row">
            <div class="strength-label">{{ $lbl }}</div>
            <div class="strength-bar"><div class="strength-fill" style="width:{{ $s }}%;background:{{ $barColor }}"></div></div>
            <div class="strength-pct" style="color:{{ $barColor }}">{{ $data['label'] }}</div>
        </div>
        @endforeach
        @else
        <p style="color:rgba(255,255,255,.4);font-size:.85rem">Apply to internships to generate your profile strength data.</p>
        @endif
    </div>
</div>

{{-- Middle Row: Missing Skills + Weak Areas --}}
<div class="dash-grid">

    {{-- Most Missing Skills --}}
    <div class="card">
        <div class="card-title"><i class="fas fa-exclamation-triangle"></i>Top Missing Skills</div>
        @if(!empty($all_missing_skills))
            @foreach(array_slice($all_missing_skills, 0, 10) as $skill)
                <span class="skill-chip">{{ $skill }}</span>
            @endforeach
            <p style="color:rgba(255,255,255,.4);font-size:.78rem;margin-top:.75rem">Add these to your Technical Skills section to improve ATS scores across all applications.</p>
        @else
            <p style="color:#6fcf97;font-size:.85rem">✓ No recurring missing skills detected.</p>
        @endif
    </div>

    {{-- Weak Areas --}}
    <div class="card">
        <div class="card-title"><i class="fas fa-search-minus"></i>Weak Areas Detected</div>
        @if(!empty($all_weak_areas))
            @foreach(array_slice($all_weak_areas, 0, 5) as $area)
            <div class="weak-item">
                <span style="color:var(--yellow)">⚠</span> {{ $area }}
            </div>
            @endforeach
        @else
            <p style="color:#6fcf97;font-size:.85rem">✓ No major weaknesses found across your applications.</p>
        @endif
    </div>
</div>

{{-- Bottom Row: ATS Trend + Smart Recommendations --}}
<div class="dash-grid">

    {{-- ATS Score Trend --}}
    <div class="card">
        <div class="card-title"><i class="fas fa-chart-bar"></i>ATS Score by Internship</div>
        @if(!empty($score_trend))
            @foreach($score_trend as $t)
            @php $sc = $t['score']; $tc = $sc>=70?'var(--green)':($sc>=50?'var(--yellow)':'var(--red)'); @endphp
            <div class="trend-item">
                <div class="trend-name" title="{{ $t['label'] }}">{{ Str::limit($t['label'], 35) }}</div>
                <div style="display:flex;align-items:center;gap:.5rem">
                    <div style="width:60px;height:5px;background:rgba(255,255,255,.08);border-radius:3px;overflow:hidden">
                        <div style="width:{{ $sc }}%;height:100%;background:{{ $tc }};border-radius:3px"></div>
                    </div>
                    <div class="trend-score" style="color:{{ $tc }}">{{ $sc }}%</div>
                </div>
            </div>
            @endforeach
        @else
            <p style="color:rgba(255,255,255,.4);font-size:.85rem;text-align:center;padding:1.5rem 0">
                <i class="fas fa-chart-bar" style="display:block;font-size:2rem;margin-bottom:.5rem;opacity:.2"></i>
                Run Resume Optimizer on internship applications to see your ATS trend here.
            </p>
        @endif
    </div>

    {{-- Smart Recommendations --}}
    <div class="card">
        <div class="card-title"><i class="fas fa-lightbulb"></i>AI Recommendations</div>
        @if(!empty($smart_recommendations))
            @foreach(array_slice($smart_recommendations, 0, 5) as $rec)
            <div class="rec-item">
                <div class="rec-icon">{{ $rec['icon'] ?? '💡' }}</div>
                <div>
                    <div class="rec-priority {{ $rec['priority'] ?? 'low' }}">{{ strtoupper($rec['priority'] ?? 'LOW') }}</div>
                    <div class="rec-text">{{ $rec['text'] }}</div>
                </div>
            </div>
            @endforeach
        @else
            <p style="color:rgba(255,255,255,.4);font-size:.85rem">Apply to internships and run the Resume Optimizer to get personalized recommendations.</p>
        @endif
    </div>
</div>

{{-- Quick Actions --}}
<div class="card" style="margin-top:1.25rem">
    <div class="card-title"><i class="fas fa-bolt"></i>Quick Actions</div>
    <div style="display:flex;flex-wrap:wrap;gap:.75rem">
        <a href="{{ route('recommendations.index') }}" class="action-btn primary"><i class="fas fa-search"></i>Find Matching Jobs</a>
        <a href="{{ route('profile.edit') }}" class="action-btn secondary"><i class="fas fa-user-edit"></i>Update Profile</a>
        <a href="{{ route('applications.index') }}" class="action-btn secondary"><i class="fas fa-file-alt"></i>My Applications</a>
    </div>
</div>

@endif
@endsection
