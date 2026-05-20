@extends('recruiter.layouts.app')

@section('content')
@push('styles')
<style>
.page-header{margin-bottom:1.5rem}
.page-header h1{color:#fff;font-size:clamp(1.3rem,4vw,1.8rem);font-weight:700}
.page-header p{color:rgba(255,255,255,.55);margin-top:.3rem;font-size:.875rem}

/* Stats Row */
.intel-stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:1rem;margin-bottom:1.5rem}
.intel-stat{background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.1);border-radius:14px;padding:1.1rem 1.25rem;text-align:center}
.intel-stat-val{font-size:1.8rem;font-weight:800;color:#fff;line-height:1}
.intel-stat-lbl{font-size:.75rem;color:rgba(255,255,255,.5);margin-top:.3rem}

/* Score Ring */
.score-ring{width:52px;height:52px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.9rem;font-weight:800;color:#fff;flex-shrink:0;border:3px solid}

/* Trust Badge */
.trust-badge{display:inline-block;padding:.18rem .55rem;border-radius:6px;font-size:.7rem;font-weight:600;white-space:nowrap}
.tb-strong  {background:rgba(111,207,151,.15);border:1px solid rgba(111,207,151,.3);color:#6fcf97}
.tb-medium  {background:rgba(242,201,76,.15); border:1px solid rgba(242,201,76,.3); color:#f2c94c}
.tb-weak    {background:rgba(242,153,74,.15); border:1px solid rgba(242,153,74,.3); color:#f2994a}
.tb-none    {background:rgba(235,87,87,.15);  border:1px solid rgba(235,87,87,.3);  color:#eb5757}

/* Rank tier */
.rank-top10 {background:rgba(111,207,151,.15);color:#6fcf97;border:1px solid rgba(111,207,151,.3)}
.rank-top25 {background:rgba(242,201,76,.15); color:#f2c94c;border:1px solid rgba(242,201,76,.3)}
.rank-avg   {background:rgba(77,163,255,.15); color:#4da3ff;border:1px solid rgba(77,163,255,.3)}
.rank-below {background:rgba(235,87,87,.15);  color:#eb5757;border:1px solid rgba(235,87,87,.3)}

/* Candidate cards */
.cand-table{width:100%;border-collapse:collapse}
.cand-table thead th{background:rgba(255,255,255,.04);color:rgba(255,255,255,.45);font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;padding:.75rem 1rem;text-align:left}
.cand-table tbody tr{border-top:1px solid rgba(255,255,255,.05);transition:background .15s}
.cand-table tbody tr:hover{background:rgba(255,255,255,.03)}
.cand-table tbody td{padding:.85rem 1rem;font-size:.84rem;color:rgba(255,255,255,.85);vertical-align:middle}

.table-wrap{background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.1);border-radius:16px;overflow:hidden}

/* Filters */
.filter-bar{display:flex;flex-wrap:wrap;gap:.75rem;margin-bottom:1.25rem;align-items:center}
.filter-chip{background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.12);color:rgba(255,255,255,.7);padding:.35rem .9rem;border-radius:20px;font-size:.78rem;cursor:pointer;transition:all .2s;text-decoration:none}
.filter-chip.active,.filter-chip:hover{background:rgba(233,69,96,.15);border-color:rgba(233,69,96,.35);color:#e94560}

/* Skill tags */
.skill-tag{display:inline-block;background:rgba(77,163,255,.12);border:1px solid rgba(77,163,255,.2);color:#4da3ff;padding:.15rem .5rem;border-radius:5px;font-size:.68rem;margin:.1rem}
.skill-tag.missing{background:rgba(235,87,87,.12);border-color:rgba(235,87,87,.2);color:#eb5757}
.skill-tag.critical{background:rgba(235,87,87,.2);border-color:rgba(235,87,87,.35);color:#eb5757;font-weight:700}

/* Expandable row */
.expand-btn{background:none;border:none;color:rgba(255,255,255,.5);cursor:pointer;padding:.2rem .4rem;border-radius:4px;transition:all .2s;font-size:.8rem}
.expand-btn:hover{color:#fff;background:rgba(255,255,255,.1)}
.detail-row{display:none;background:rgba(0,0,0,.2)}
.detail-row.open{display:table-row}
.detail-cell{padding:1rem 1.25rem}
.detail-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem}
.detail-block label{color:rgba(255,255,255,.4);font-size:.7rem;font-weight:600;text-transform:uppercase;letter-spacing:.05em;display:block;margin-bottom:.35rem}

/* Empty */
.empty-state{text-align:center;padding:3rem;color:rgba(255,255,255,.4)}
.empty-state i{font-size:2.5rem;display:block;margin-bottom:.75rem;opacity:.3}

/* Role badge */
.role-badge{background:rgba(111,66,193,.15);border:1px solid rgba(111,66,193,.3);color:#b39ddb;padding:.18rem .6rem;border-radius:6px;font-size:.7rem;font-weight:600;text-transform:capitalize}

/* Progress bar mini */
.mini-bar{height:5px;border-radius:3px;background:rgba(255,255,255,.08);overflow:hidden;width:80px;display:inline-block;vertical-align:middle;margin-left:.4rem}
.mini-bar-fill{height:100%;border-radius:3px;transition:width .5s}

@media(max-width:900px){
    .cand-table .hide-mobile{display:none}
    .intel-stats{grid-template-columns:repeat(2,1fr)}
}
@media(max-width:500px){
    .intel-stats{grid-template-columns:1fr 1fr}
}

/* ── Mobile card layout for candidate table ─────────────────────── */
@media(max-width:640px){
    /* Hide the desktop table entirely */
    .table-wrap .cand-table { display:none }

    /* Show mobile cards */
    .mobile-cards { display:block }
}
@media(min-width:641px){
    .mobile-cards { display:none }
}

.mobile-card {
    background:rgba(255,255,255,.05);
    border:1px solid rgba(255,255,255,.1);
    border-radius:14px;
    padding:1rem 1.1rem;
    margin:.75rem;
}
.mobile-card + .mobile-card {
    margin-top:.5rem;
}
.mobile-card-header {
    display:flex;
    align-items:center;
    gap:.75rem;
    margin-bottom:.75rem;
}
.mobile-card-name {
    flex:1;
    min-width:0;
}
.mobile-card-name strong {
    display:block;
    color:#fff;
    font-size:.9rem;
    font-weight:600;
    white-space:nowrap;
    overflow:hidden;
    text-overflow:ellipsis;
}
.mobile-card-name span {
    font-size:.72rem;
    color:rgba(255,255,255,.45);
    white-space:nowrap;
    overflow:hidden;
    text-overflow:ellipsis;
    display:block;
}
.mobile-card-meta {
    display:flex;
    flex-wrap:wrap;
    gap:.4rem .6rem;
    align-items:center;
    margin-bottom:.6rem;
}
.mobile-card-row {
    display:flex;
    justify-content:space-between;
    align-items:center;
    padding:.35rem 0;
    border-top:1px solid rgba(255,255,255,.06);
    font-size:.78rem;
}
.mobile-card-row-label {
    color:rgba(255,255,255,.4);
    font-size:.7rem;
    font-weight:600;
    text-transform:uppercase;
    letter-spacing:.04em;
}
.mobile-card-expand {
    width:100%;
    background:rgba(255,255,255,.06);
    border:1px solid rgba(255,255,255,.1);
    border-radius:8px;
    color:rgba(255,255,255,.6);
    padding:.4rem;
    margin-top:.6rem;
    cursor:pointer;
    font-size:.75rem;
    display:flex;
    align-items:center;
    justify-content:center;
    gap:.4rem;
    transition:background .2s;
}
.mobile-card-expand:hover { background:rgba(255,255,255,.1); color:#fff; }
.mobile-detail { display:none; margin-top:.75rem; }
.mobile-detail.open { display:block; }
</style>
@endpush

<div class="page-header">
    <div>
        <h1><i class="fas fa-brain" style="color:#e94560;margin-right:.5rem"></i>Candidate Intelligence</h1>
        <p>
            <span class="role-badge">{{ $stats['role_category'] }} Role</span>
            &nbsp;{{ $internship->title }} &mdash; {{ $internship->organization }}
        </p>
    </div>
    <div style="display:flex;gap:.6rem;flex-wrap:wrap;margin-top:.5rem">
        <a href="{{ route('recruiter.intelligence.list') }}" class="filter-chip"><i class="fas fa-arrow-left" style="margin-right:.3rem"></i>All Internships</a>
        <a href="{{ route('recruiter.applications.index') }}" class="filter-chip"><i class="fas fa-users" style="margin-right:.3rem"></i>Applications</a>
    </div>
</div>

{{-- Stats Row --}}
<div class="intel-stats">
    <div class="intel-stat">
        <div class="intel-stat-val" style="color:#4da3ff">{{ $stats['total'] }}</div>
        <div class="intel-stat-lbl">Total Applicants</div>
    </div>
    <div class="intel-stat">
        <div class="intel-stat-val">{{ $stats['avg_score'] }}%</div>
        <div class="intel-stat-lbl">Avg ATS Score</div>
    </div>
    <div class="intel-stat">
        <div class="intel-stat-val" style="color:#6fcf97">{{ $stats['high_match'] }}</div>
        <div class="intel-stat-lbl">High Match (≥70%)</div>
    </div>
    <div class="intel-stat">
        <div class="intel-stat-val" style="color:#f2c94c">{{ $stats['medium_match'] }}</div>
        <div class="intel-stat-lbl">Medium Match</div>
    </div>
    <div class="intel-stat">
        <div class="intel-stat-val" style="color:#eb5757">{{ $stats['low_match'] }}</div>
        <div class="intel-stat-lbl">Low Match</div>
    </div>
    @if(!empty($stats['required_skills']))
    <div class="intel-stat" style="grid-column:span 2;text-align:left">
        <div style="color:rgba(255,255,255,.45);font-size:.72rem;font-weight:600;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.5rem">Required Skills</div>
        <div>
            @foreach(array_slice($stats['required_skills'], 0, 6) as $skill)
                <span class="skill-tag">{{ $skill }}</span>
            @endforeach
        </div>
    </div>
    @endif
</div>

{{-- Candidate Table --}}
@if(empty($candidates))
<div class="empty-state">
    <i class="fas fa-user-slash"></i>
    <p>No applicants yet for this internship.</p>
</div>
@else

{{-- ── Desktop Table ─────────────────────────────────────────────── --}}
<div class="table-wrap">
    <table class="cand-table">
        <thead>
            <tr>
                <th style="width:30px">#</th>
                <th>Candidate</th>
                <th>ATS Score</th>
                <th class="hide-mobile">Skill Match</th>
                <th class="hide-mobile">Backend</th>
                <th class="hide-mobile">Projects</th>
                <th class="hide-mobile">Tech Depth</th>
                <th>Status</th>
                <th>Rank</th>
                <th style="width:40px"></th>
            </tr>
        </thead>
        <tbody>
        @foreach($candidates as $idx => $cand)
            @php
                $intel = $cand['intelligence'];
                $app   = $cand['application'];
                $score = $cand['overall_score'];
                $color = $score >= 70 ? '#6fcf97' : ($score >= 50 ? '#f2c94c' : '#eb5757');
                $tier  = $cand['rank_tier'] ?? 'below';
                $tierLabels = ['top10' => '🏆 Top 10%','top25' => '⭐ Top 25%','average' => '📊 Average','below' => '📉 Below Avg'];
                $tierClasses = ['top10' => 'rank-top10','top25' => 'rank-top25','average' => 'rank-avg','below' => 'rank-below'];
                $tbClass = fn($label) => match($label){
                    'Strong'=>'tb-strong','Medium'=>'tb-medium','Weak'=>'tb-weak',default=>'tb-none'
                };
            @endphp
            <tr>
                <td style="color:rgba(255,255,255,.4);font-weight:700">{{ $cand['rank'] ?? ($idx+1) }}</td>
                <td>
                    <div style="font-weight:600;color:#fff">{{ $cand['name'] }}</div>
                    <div style="font-size:.75rem;color:rgba(255,255,255,.45)">{{ $cand['email'] }}</div>
                </td>
                <td>
                    <div style="display:flex;align-items:center;gap:.5rem">
                        <div class="score-ring" style="border-color:{{ $color }};background:{{ $color }}22;font-size:.82rem">
                            {{ $score }}%
                        </div>
                    </div>
                </td>
                <td class="hide-mobile">
                    @if($intel)
                        <span>{{ $intel->skill_match_score }}%</span>
                        <span class="mini-bar"><span class="mini-bar-fill" style="width:{{ $intel->skill_match_score }}%;background:{{ $color }}"></span></span>
                    @else <span style="color:rgba(255,255,255,.3)">—</span> @endif
                </td>
                <td class="hide-mobile">
                    @if($intel)
                        <span class="trust-badge {{ $tbClass($intel->backend_match) }}">{{ $intel->backend_match }}</span>
                    @else <span style="color:rgba(255,255,255,.3)">—</span> @endif
                </td>
                <td class="hide-mobile">
                    @if($intel)
                        <span class="trust-badge {{ $tbClass($intel->project_quality) }}">{{ $intel->project_quality }}</span>
                    @else <span style="color:rgba(255,255,255,.3)">—</span> @endif
                </td>
                <td class="hide-mobile">
                    @if($intel)
                        <span class="trust-badge {{ $tbClass($intel->technical_depth) }}">{{ $intel->technical_depth }}</span>
                    @else <span style="color:rgba(255,255,255,.3)">—</span> @endif
                </td>
                <td>
                    @if($app)
                        <span class="status-badge status-{{ $app->status->value }}" style="font-size:.7rem;padding:.18rem .5rem;border-radius:5px">
                            {{ $app->status->label() }}
                        </span>
                    @else <span style="color:rgba(255,255,255,.3)">—</span> @endif
                </td>
                <td>
                    <span class="trust-badge {{ $tierClasses[$tier] ?? 'rank-below' }}" style="font-size:.68rem">
                        {{ $tierLabels[$tier] ?? '—' }}
                    </span>
                </td>
                <td>
                    <button class="expand-btn" data-row="{{ $idx }}" title="View Details">
                        <i class="fas fa-chevron-down"></i>
                    </button>
                </td>
            </tr>

            {{-- Expandable Detail Row --}}
            <tr class="detail-row" id="detail-{{ $idx }}">
                <td colspan="10" class="detail-cell">
                    <div class="detail-grid">

                        {{-- Score Breakdown --}}
                        <div class="detail-block">
                            <label>Score Breakdown</label>
                            @if($intel)
                                @foreach([
                                    'Skill Match' => $intel->skill_match_score,
                                    'Keyword Match' => $intel->keyword_score,
                                    'Experience' => $intel->experience_score,
                                    'Projects' => $intel->project_score,
                                    'Tech Depth' => $intel->technical_depth_score,
                                ] as $lbl => $val)
                                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.4rem">
                                    <span style="color:rgba(255,255,255,.6);font-size:.78rem">{{ $lbl }}</span>
                                    <div style="display:flex;align-items:center;gap:.4rem">
                                        <div style="width:60px;height:4px;background:rgba(255,255,255,.1);border-radius:2px;overflow:hidden">
                                            <div style="width:{{ $val }}%;height:100%;background:{{ $val>=70?'#6fcf97':($val>=50?'#f2c94c':'#eb5757') }};border-radius:2px"></div>
                                        </div>
                                        <span style="color:#fff;font-size:.78rem;font-weight:600;min-width:28px">{{ $val }}%</span>
                                    </div>
                                </div>
                                @endforeach
                            @else
                                <p style="color:rgba(255,255,255,.35);font-size:.8rem">No resume uploaded</p>
                            @endif
                        </div>

                        {{-- Matching Skills --}}
                        <div class="detail-block">
                            <label>Matching Skills ({{ count($intel?->matching_skills ?? []) }})</label>
                            @foreach(array_slice($intel?->matching_skills ?? [], 0, 8) as $skill)
                                <span class="skill-tag">✓ {{ $skill }}</span>
                            @endforeach
                            @if(empty($intel?->matching_skills))
                                <span style="color:rgba(255,255,255,.35);font-size:.8rem">None detected</span>
                            @endif
                        </div>

                        {{-- Critical Missing --}}
                        <div class="detail-block">
                            <label>Critical Missing ({{ count($intel?->critical_missing_skills ?? []) }})</label>
                            @foreach(array_slice($intel?->critical_missing_skills ?? [], 0, 6) as $skill)
                                <span class="skill-tag critical">✗ {{ $skill }}</span>
                            @endforeach
                            @foreach(array_slice($intel?->optional_missing_skills ?? [], 0, 4) as $skill)
                                <span class="skill-tag missing">{{ $skill }}</span>
                            @endforeach
                            @if(empty($intel?->critical_missing_skills) && empty($intel?->optional_missing_skills))
                                <span style="color:#6fcf97;font-size:.8rem">✓ All required skills present</span>
                            @endif
                        </div>

                        {{-- Weak Areas + Actions --}}
                        <div class="detail-block">
                            <label>Weak Areas</label>
                            @forelse($intel?->weak_areas ?? [] as $area)
                                <div style="color:rgba(255,255,255,.6);font-size:.78rem;margin-bottom:.25rem">⚠ {{ $area }}</div>
                            @empty
                                <span style="color:#6fcf97;font-size:.8rem">✓ No major weaknesses</span>
                            @endforelse
                            @if($app)
                            <div style="margin-top:.75rem">
                                <select class="status-select status-update-select" data-id="{{ $app->id }}" data-current="{{ $app->status->value }}" style="background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.12);border-radius:8px;color:#fff;padding:.3rem .7rem;font-size:.8rem">
                                    @foreach(\App\Enums\ApplicationStatus::cases() as $s)
                                        <option value="{{ $s->value }}" {{ $app->status->value === $s->value ? 'selected' : '' }}>{{ $s->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @endif
                        </div>
                    </div>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

{{-- ── Mobile Cards ──────────────────────────────────────────────── --}}
<div class="mobile-cards">
    @foreach($candidates as $idx => $cand)
        @php
            $intel = $cand['intelligence'];
            $app   = $cand['application'];
            $score = $cand['overall_score'];
            $color = $score >= 70 ? '#6fcf97' : ($score >= 50 ? '#f2c94c' : '#eb5757');
            $tier  = $cand['rank_tier'] ?? 'below';
            $tierLabels  = ['top10'=>'🏆 Top 10%','top25'=>'⭐ Top 25%','average'=>'📊 Average','below'=>'📉 Below Avg'];
            $tierClasses = ['top10'=>'rank-top10','top25'=>'rank-top25','average'=>'rank-avg','below'=>'rank-below'];
            $tbClass = fn($label) => match($label){
                'Strong'=>'tb-strong','Medium'=>'tb-medium','Weak'=>'tb-weak',default=>'tb-none'
            };
        @endphp
        <div class="mobile-card">
            {{-- Header: rank + name + score ring --}}
            <div class="mobile-card-header">
                <span style="color:rgba(255,255,255,.35);font-weight:700;font-size:.85rem;min-width:1.2rem">{{ $cand['rank'] ?? ($idx+1) }}</span>
                <div class="mobile-card-name">
                    <strong>{{ $cand['name'] }}</strong>
                    <span>{{ $cand['email'] }}</span>
                </div>
                <div class="score-ring" style="border-color:{{ $color }};background:{{ $color }}22;font-size:.78rem;width:46px;height:46px">
                    {{ $score }}%
                </div>
            </div>

            {{-- Badges row --}}
            <div class="mobile-card-meta">
                @if($app)
                    <span class="status-badge status-{{ $app->status->value }}" style="font-size:.68rem;padding:.15rem .45rem;border-radius:5px">
                        {{ $app->status->label() }}
                    </span>
                @endif
                <span class="trust-badge {{ $tierClasses[$tier] ?? 'rank-below' }}" style="font-size:.65rem">
                    {{ $tierLabels[$tier] ?? '—' }}
                </span>
                @if($intel)
                    <span class="trust-badge {{ $tbClass($intel->backend_match) }}" style="font-size:.65rem">BE: {{ $intel->backend_match }}</span>
                    <span class="trust-badge {{ $tbClass($intel->technical_depth) }}" style="font-size:.65rem">Tech: {{ $intel->technical_depth }}</span>
                @endif
            </div>

            {{-- Skill match bar --}}
            @if($intel)
            <div class="mobile-card-row">
                <span class="mobile-card-row-label">Skill Match</span>
                <div style="display:flex;align-items:center;gap:.4rem">
                    <span class="mini-bar" style="width:70px"><span class="mini-bar-fill" style="width:{{ $intel->skill_match_score }}%;background:{{ $color }}"></span></span>
                    <span style="color:#fff;font-size:.8rem;font-weight:600">{{ $intel->skill_match_score }}%</span>
                </div>
            </div>
            @endif

            {{-- Expand button --}}
            <button class="mobile-card-expand" data-mrow="{{ $idx }}">
                <i class="fas fa-chevron-down"></i>
                <span>View Details</span>
            </button>

            {{-- Expandable detail --}}
            <div class="mobile-detail" id="mdetail-{{ $idx }}">
                <div class="detail-grid" style="grid-template-columns:1fr">

                    {{-- Score Breakdown --}}
                    @if($intel)
                    <div class="detail-block">
                        <label>Score Breakdown</label>
                        @foreach([
                            'Skill Match' => $intel->skill_match_score,
                            'Keyword Match' => $intel->keyword_score,
                            'Experience' => $intel->experience_score,
                            'Projects' => $intel->project_score,
                            'Tech Depth' => $intel->technical_depth_score,
                        ] as $lbl => $val)
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.4rem">
                            <span style="color:rgba(255,255,255,.6);font-size:.78rem">{{ $lbl }}</span>
                            <div style="display:flex;align-items:center;gap:.4rem">
                                <div style="width:60px;height:4px;background:rgba(255,255,255,.1);border-radius:2px;overflow:hidden">
                                    <div style="width:{{ $val }}%;height:100%;background:{{ $val>=70?'#6fcf97':($val>=50?'#f2c94c':'#eb5757') }};border-radius:2px"></div>
                                </div>
                                <span style="color:#fff;font-size:.78rem;font-weight:600;min-width:28px">{{ $val }}%</span>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @endif

                    {{-- Skills --}}
                    <div class="detail-block">
                        <label>Matching Skills</label>
                        @foreach(array_slice($intel?->matching_skills ?? [], 0, 8) as $skill)
                            <span class="skill-tag">✓ {{ $skill }}</span>
                        @endforeach
                        @if(empty($intel?->matching_skills))
                            <span style="color:rgba(255,255,255,.35);font-size:.8rem">None detected</span>
                        @endif
                    </div>

                    <div class="detail-block">
                        <label>Missing Skills</label>
                        @foreach(array_slice($intel?->critical_missing_skills ?? [], 0, 6) as $skill)
                            <span class="skill-tag critical">✗ {{ $skill }}</span>
                        @endforeach
                        @if(empty($intel?->critical_missing_skills))
                            <span style="color:#6fcf97;font-size:.8rem">✓ All required skills present</span>
                        @endif
                    </div>

                    {{-- Status update --}}
                    @if($app)
                    <div class="detail-block">
                        <label>Update Status</label>
                        <select class="status-update-select" data-id="{{ $app->id }}" data-current="{{ $app->status->value }}"
                            style="width:100%;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.12);border-radius:8px;color:#fff;padding:.4rem .7rem;font-size:.82rem">
                            @foreach(\App\Enums\ApplicationStatus::cases() as $s)
                                <option value="{{ $s->value }}" {{ $app->status->value === $s->value ? 'selected' : '' }}>{{ $s->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    @endforeach
</div>

@endif

@endsection

@push('scripts')
<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

// Expand/collapse detail rows
document.querySelectorAll('.expand-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const row = document.getElementById('detail-' + this.dataset.row);
        const icon = this.querySelector('i');
        if (row.classList.toggle('open')) {
            icon.className = 'fas fa-chevron-up';
        } else {
            icon.className = 'fas fa-chevron-down';
        }
    });
});

// Mobile card expand/collapse
document.querySelectorAll('.mobile-card-expand').forEach(btn => {
    btn.addEventListener('click', function() {
        const detail = document.getElementById('mdetail-' + this.dataset.mrow);
        const icon = this.querySelector('i');
        const label = this.querySelector('span');
        if (detail.classList.toggle('open')) {
            icon.className = 'fas fa-chevron-up';
            label.textContent = 'Hide Details';
        } else {
            icon.className = 'fas fa-chevron-down';
            label.textContent = 'View Details';
        }
    });
});

// Status update (reusing existing logic)
document.querySelectorAll('.status-update-select').forEach(sel => {
    sel.addEventListener('change', function() {
        const id = this.dataset.id;
        const status = this.value;
        const cur = this.dataset.current;
        fetch(`/recruiter/applications/${id}/status`, {
            method:'POST',
            headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrfToken,'Accept':'application/json','X-Requested-With':'XMLHttpRequest'},
            body: JSON.stringify({status})
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                this.dataset.current = d.status;
                showToast('Status updated!','success');
            } else {
                this.value = cur;
                showToast(d.message || 'Failed','error');
            }
        })
        .catch(() => { this.value = cur; showToast('Error updating status','error'); });
    });
});

function showToast(msg, type) {
    const bg = type==='success'?'#6fcf97':'#eb5757';
    const t = document.createElement('div');
    t.style.cssText = `position:fixed;top:20px;right:20px;background:${bg};color:#fff;padding:.9rem 1.4rem;border-radius:12px;z-index:10000;font-size:.875rem;font-weight:600;box-shadow:0 4px 20px rgba(0,0,0,.3)`;
    t.textContent = msg;
    document.body.appendChild(t);
    setTimeout(() => { t.style.opacity='0';t.style.transition='opacity .3s';setTimeout(()=>t.remove(),300); }, 3000);
}
</script>
@endpush
