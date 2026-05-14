{{--
    AI Resume Optimizer Modal
    Props:
        $internship  — Internship model (required)
    Usage:
        <x-resume-optimizer-modal :internship="$internship" />
    Called from: apply.blade.php, apply-mobile.blade.php, internship-card.blade.php
--}}
@props(['internship'])

@auth
@if(auth()->user()->role === 'student')

{{-- ════════════════════════════════════════════════════════════════ --}}
{{-- MODAL OVERLAY                                                   --}}
{{-- ════════════════════════════════════════════════════════════════ --}}
<div id="resume-optimizer-modal-{{ $internship->id }}"
     class="rom-overlay fixed inset-0 z-[200] hidden items-center justify-center p-4"
     role="dialog" aria-modal="true"
     aria-labelledby="rom-title-{{ $internship->id }}">

    {{-- Backdrop --}}
    <div class="rom-backdrop absolute inset-0 bg-black/60 backdrop-blur-sm"
         onclick="ResumeOptimizer.close({{ $internship->id }})"></div>

    {{-- Modal Panel --}}
    <div class="rom-panel relative w-full max-w-lg max-h-[90vh] overflow-y-auto rounded-3xl shadow-2xl z-10">

        {{-- Header --}}
        <div class="rom-header sticky top-0 z-20 px-6 py-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="rom-icon-wrap">
                    <i class="fas fa-robot text-white text-lg"></i>
                </div>
                <div>
                    <h2 id="rom-title-{{ $internship->id }}" class="rom-title">AI Resume Optimizer</h2>
                    <p class="rom-subtitle">{{ Str::limit($internship->title, 40) }}</p>
                </div>
            </div>
            <button onclick="ResumeOptimizer.close({{ $internship->id }})"
                    class="rom-close-btn" aria-label="Close">
                <i class="fas fa-times"></i>
            </button>
        </div>

        {{-- Body --}}
        <div class="px-6 pb-6 space-y-5">

            {{-- ── STEP 1: Loading State ──────────────────────────── --}}
            <div id="rom-loading-{{ $internship->id }}" class="rom-loading-state py-8 text-center">
                <div class="rom-spinner mx-auto mb-4"></div>
                <p class="rom-loading-text">Analysing your resume against this job…</p>
                <p class="text-sm text-gray-400 mt-1">This takes just a moment</p>
            </div>

            {{-- ── STEP 2: Score Panel ────────────────────────────── --}}
            <div id="rom-score-panel-{{ $internship->id }}" class="hidden space-y-4">

                {{-- Overall Score Ring --}}
                <div class="rom-score-card text-center py-5">
                    <div class="relative inline-flex items-center justify-center mb-3">
                        <svg class="rom-score-ring" viewBox="0 0 120 120">
                            <circle cx="60" cy="60" r="50" fill="none" stroke="#e5e7eb" stroke-width="10"/>
                            <circle id="rom-score-circle-{{ $internship->id }}"
                                    cx="60" cy="60" r="50" fill="none"
                                    stroke-width="10" stroke-linecap="round"
                                    stroke-dasharray="314"
                                    stroke-dashoffset="314"
                                    class="rom-score-arc transition-all duration-1000"
                                    transform="rotate(-90 60 60)"/>
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                            <span id="rom-score-value-{{ $internship->id }}" class="rom-score-num">0%</span>
                            <span class="text-xs text-gray-500 font-medium">Match</span>
                        </div>
                    </div>
                    <div id="rom-gate-badge-{{ $internship->id }}" class="rom-gate-badge inline-flex items-center gap-1.5 px-4 py-1.5 rounded-full text-sm font-semibold mb-1"></div>
                    <p id="rom-gate-message-{{ $internship->id }}" class="text-xs text-gray-500 mt-1"></p>
                </div>

                {{-- Score Breakdown Bars --}}
                <div class="rom-breakdown-card space-y-3">
                    <h3 class="rom-section-label">Score Breakdown</h3>

                    <div>
                        <div class="flex justify-between text-xs mb-1">
                            <span class="font-medium text-gray-700">Skill Match</span>
                            <span id="rom-skill-pct-{{ $internship->id }}" class="font-bold text-indigo-600">0%</span>
                        </div>
                        <div class="rom-bar-track">
                            <div id="rom-skill-bar-{{ $internship->id }}" class="rom-bar-fill bg-indigo-500" style="width:0%"></div>
                        </div>
                    </div>

                    <div>
                        <div class="flex justify-between text-xs mb-1">
                            <span class="font-medium text-gray-700">Keyword Coverage</span>
                            <span id="rom-kw-pct-{{ $internship->id }}" class="font-bold text-purple-600">0%</span>
                        </div>
                        <div class="rom-bar-track">
                            <div id="rom-kw-bar-{{ $internship->id }}" class="rom-bar-fill bg-purple-500" style="width:0%"></div>
                        </div>
                    </div>

                    <div>
                        <div class="flex justify-between text-xs mb-1">
                            <span class="font-medium text-gray-700">ATS Formatting</span>
                            <span id="rom-fmt-pct-{{ $internship->id }}" class="font-bold text-teal-600">0%</span>
                        </div>
                        <div class="rom-bar-track">
                            <div id="rom-fmt-bar-{{ $internship->id }}" class="rom-bar-fill bg-teal-500" style="width:0%"></div>
                        </div>
                    </div>
                </div>

                {{-- Missing Skills --}}
                <div id="rom-missing-wrap-{{ $internship->id }}" class="hidden rom-missing-card">
                    <h3 class="rom-section-label mb-2">❌ Missing Skills</h3>
                    <div id="rom-missing-skills-{{ $internship->id }}" class="flex flex-wrap gap-2"></div>
                </div>

                {{-- Issues --}}
                <div id="rom-issues-wrap-{{ $internship->id }}" class="hidden rom-issues-card">
                    <h3 class="rom-section-label mb-2">⚠️ Issues Found</h3>
                    <ul id="rom-issues-list-{{ $internship->id }}" class="space-y-1.5"></ul>
                </div>

                {{-- Strengths --}}
                <div id="rom-strengths-wrap-{{ $internship->id }}" class="hidden rom-strengths-card">
                    <h3 class="rom-section-label mb-2">✅ Strengths</h3>
                    <ul id="rom-strengths-list-{{ $internship->id }}" class="space-y-1.5"></ul>
                </div>

                {{-- Action Buttons --}}
                <div class="grid grid-cols-1 gap-3 pt-1">
                    {{-- Apply Anyway --}}
                    <form method="POST" action="{{ route('applications.apply', $internship) }}" id="rom-apply-form-{{ $internship->id }}">
                        @csrf
                        <button type="submit" id="rom-apply-btn-{{ $internship->id }}"
                                class="rom-btn-apply w-full">
                            <i class="fas fa-paper-plane mr-2"></i>Apply Anyway
                        </button>
                    </form>
                    {{-- Improve Resume --}}
                    <button type="button"
                            onclick="ResumeOptimizer.rewrite({{ $internship->id }})"
                            id="rom-improve-btn-{{ $internship->id }}"
                            class="rom-btn-improve w-full">
                        <i class="fas fa-magic mr-2"></i>✨ Improve Resume (Recommended)
                    </button>
                </div>
            </div>

            {{-- ── STEP 3: Rewriting State ───────────────────────── --}}
            <div id="rom-rewriting-{{ $internship->id }}" class="hidden py-8 text-center">
                <div class="rom-spinner mx-auto mb-4" style="border-top-color:#7c3aed"></div>
                <p class="rom-loading-text" style="color:#7c3aed">AI is rewriting your resume…</p>
                <p class="text-sm text-gray-400 mt-1">Optimising for {{ $internship->title }}</p>
            </div>

            {{-- ── STEP 4: Before/After Comparison ─────────────────  --}}
            <div id="rom-comparison-{{ $internship->id }}" class="hidden space-y-4">

                <h3 class="text-center font-bold text-gray-800 text-base">📊 Resume Improvement</h3>

                {{-- Score Comparison Cards --}}
                <div class="grid grid-cols-2 gap-3">
                    <div class="rom-compare-card rom-compare-before text-center py-4">
                        <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Before</p>
                        <p id="rom-before-score-{{ $internship->id }}" class="text-3xl font-black text-red-500">0%</p>
                        <p class="text-xs text-gray-500 mt-1">Match Score</p>
                    </div>
                    <div class="rom-compare-card rom-compare-after text-center py-4">
                        <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">After</p>
                        <p id="rom-after-score-{{ $internship->id }}" class="text-3xl font-black text-green-600">0%</p>
                        <p class="text-xs text-gray-500 mt-1">Match Score</p>
                    </div>
                </div>

                {{-- Improvements List --}}
                <div class="rom-improvements-card">
                    <h4 class="rom-section-label mb-2">🚀 Improvements Made</h4>
                    <ul id="rom-improvements-list-{{ $internship->id }}" class="space-y-1.5"></ul>
                </div>

                {{-- Rewritten Preview --}}
                <div class="rom-preview-card">
                    <div class="flex items-center justify-between mb-2">
                        <h4 class="rom-section-label">📄 Optimised Resume Preview</h4>
                        <button onclick="ResumeOptimizer.copyResume({{ $internship->id }})"
                                class="text-xs text-indigo-600 hover:text-indigo-800 font-medium flex items-center gap-1">
                            <i class="fas fa-copy"></i> Copy
                        </button>
                    </div>
                    <pre id="rom-preview-text-{{ $internship->id }}"
                         class="rom-preview-text whitespace-pre-wrap text-xs text-gray-700 max-h-48 overflow-y-auto"></pre>
                </div>

                {{-- Final CTA --}}
                <div class="grid grid-cols-1 gap-3">
                    {{-- Download PDF button (href updated by JS with version_id) --}}
                    <a id="rom-download-btn-{{ $internship->id }}"
                       href="/resume-optimizer/download/{{ $internship->id }}"
                       target="_blank"
                       class="rom-btn-download w-full text-center">
                        <i class="fas fa-file-pdf mr-2"></i>⬇ Download Optimised PDF
                    </a>
                    <form method="POST" action="{{ route('applications.apply', $internship) }}" id="rom-apply-optimized-form-{{ $internship->id }}">
                        @csrf
                        {{-- JS populates this with the AI version ID after rewrite completes --}}
                        <input type="hidden" name="resume_version_id" id="rom-version-id-{{ $internship->id }}" value="">
                        <button type="submit" class="rom-btn-apply-green w-full">
                            <i class="fas fa-rocket mr-2"></i>Apply with Optimised Resume
                        </button>
                    </form>
                    <button onclick="ResumeOptimizer.backToScore({{ $internship->id }})"
                            class="rom-btn-secondary w-full">
                        <i class="fas fa-arrow-left mr-2"></i>Back to Score
                    </button>
                </div>
            </div>

            {{-- ── Error State ──────────────────────────────────────── --}}
            <div id="rom-error-{{ $internship->id }}" class="hidden py-6 text-center">
                <div class="w-14 h-14 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-3">
                    <i class="fas fa-exclamation-triangle text-red-500 text-xl"></i>
                </div>
                <p id="rom-error-msg-{{ $internship->id }}" class="text-sm text-red-600 font-medium mb-4">Something went wrong.</p>
                <button onclick="ResumeOptimizer.close({{ $internship->id }})"
                        class="rom-btn-secondary">
                    <i class="fas fa-times mr-2"></i>Close
                </button>
            </div>

        </div>{{-- /body --}}
    </div>{{-- /panel --}}
</div>{{-- /overlay --}}

@endif
@endauth

{{-- ════════════════════════════════════════════════════════════════ --}}
{{-- STYLES (scoped via .rom- prefix)                                --}}
{{-- ════════════════════════════════════════════════════════════════ --}}
<style>
/* Overlay */
.rom-overlay { display: none; }
.rom-overlay.rom-open { display: flex; animation: romFadeIn .25s ease; }
@keyframes romFadeIn { from { opacity:0; } to { opacity:1; } }

/* Panel */
.rom-panel {
    background: linear-gradient(135deg, #ffffff 0%, #f8f7ff 100%);
    border: 1px solid rgba(124,58,237,.12);
}

/* Header */
.rom-header {
    background: linear-gradient(135deg, #7c3aed 0%, #4f46e5 100%);
    border-radius: 1.5rem 1.5rem 0 0;
}
.rom-icon-wrap {
    width: 2.5rem; height: 2.5rem;
    background: rgba(255,255,255,.2);
    border-radius: .75rem;
    display: flex; align-items: center; justify-content: center;
}
.rom-title   { color:#fff; font-size:1rem; font-weight:700; line-height:1.2; }
.rom-subtitle { color:rgba(255,255,255,.8); font-size:.7rem; }
.rom-close-btn {
    width:2rem; height:2rem; border-radius:.5rem;
    background:rgba(255,255,255,.2); color:#fff;
    display:flex; align-items:center; justify-content:center;
    transition: background .2s;
}
.rom-close-btn:hover { background:rgba(255,255,255,.35); }

/* Loading */
.rom-loading-state { }
.rom-spinner {
    width:3rem; height:3rem; border-radius:50%;
    border:4px solid #e5e7eb;
    border-top-color:#7c3aed;
    animation: romSpin .8s linear infinite;
}
@keyframes romSpin { to { transform: rotate(360deg); } }
.rom-loading-text { color:#7c3aed; font-weight:600; font-size:.95rem; }

/* Score ring */
.rom-score-ring { width:120px; height:120px; }
.rom-score-num  { font-size:1.6rem; font-weight:900; color:#1e1b4b; }
.rom-score-arc  { stroke:#7c3aed; }

/* Cards */
.rom-score-card, .rom-breakdown-card, .rom-missing-card,
.rom-issues-card, .rom-strengths-card, .rom-improvements-card,
.rom-compare-card, .rom-preview-card {
    background:#fff;
    border-radius:1rem;
    padding:1rem;
    border:1px solid #e5e7eb;
}
.rom-section-label { font-size:.78rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:#6b7280; }

/* Bars */
.rom-bar-track  { background:#f3f4f6; border-radius:999px; height:8px; overflow:hidden; }
.rom-bar-fill   { height:100%; border-radius:999px; transition: width 1s ease; }

/* Skills/Issues/Strengths chips */
.rom-skill-chip {
    padding:.25rem .75rem; border-radius:999px; font-size:.72rem; font-weight:600;
}
.rom-missing-chip  { background:#fee2e2; color:#b91c1c; }
.rom-issue-item    { font-size:.78rem; color:#374151; display:flex; align-items:flex-start; gap:.4rem; }
.rom-strength-item { font-size:.78rem; color:#374151; display:flex; align-items:flex-start; gap:.4rem; }

/* Gate badge */
.rom-gate-badge-high   { background:#dcfce7; color:#15803d; }
.rom-gate-badge-medium { background:#fef9c3; color:#92400e; }
.rom-gate-badge-low    { background:#fee2e2; color:#b91c1c; }

/* Comparison */
.rom-compare-before { border:2px solid #fca5a5; }
.rom-compare-after  { border:2px solid #86efac; }
.rom-improvement-item { font-size:.78rem; color:#374151; display:flex; gap:.4rem; }

/* Preview */
.rom-preview-text {
    background:#f9fafb; border-radius:.5rem; padding:.75rem;
    font-family: 'Courier New', monospace;
    border:1px solid #e5e7eb;
}

/* Buttons */
.rom-btn-apply {
    padding:.75rem 1.5rem; border-radius:.875rem; font-weight:600;
    font-size:.88rem; transition: all .2s;
    background:#f3f4f6; color:#374151; border:2px solid #e5e7eb;
    cursor:pointer;
}
.rom-btn-apply:hover { background:#e5e7eb; }

.rom-btn-improve {
    padding:.75rem 1.5rem; border-radius:.875rem; font-weight:700;
    font-size:.88rem; transition: all .2s;
    background: linear-gradient(135deg,#7c3aed 0%,#4f46e5 100%);
    color:#fff; border:none; cursor:pointer;
    box-shadow:0 4px 15px rgba(124,58,237,.35);
}
.rom-btn-improve:hover { transform: translateY(-2px); box-shadow:0 6px 20px rgba(124,58,237,.45); }

.rom-btn-apply-green {
    padding:.75rem 1.5rem; border-radius:.875rem; font-weight:700;
    font-size:.88rem; transition: all .2s;
    background: linear-gradient(135deg,#059669 0%,#0d9488 100%);
    color:#fff; border:none; cursor:pointer;
    box-shadow:0 4px 15px rgba(5,150,105,.3);
    display:block;
}
.rom-btn-apply-green:hover { transform: translateY(-2px); }

.rom-btn-download {
    padding:.75rem 1.5rem; border-radius:.875rem; font-weight:700;
    font-size:.88rem; transition: all .2s;
    background: linear-gradient(135deg,#dc2626 0%,#b91c1c 100%);
    color:#fff; border:none; cursor:pointer;
    box-shadow:0 4px 15px rgba(220,38,38,.3);
    display:block; text-decoration:none;
}
.rom-btn-download:hover { transform: translateY(-2px); box-shadow:0 6px 20px rgba(220,38,38,.4); color:#fff; }

.rom-btn-secondary {
    padding:.65rem 1.25rem; border-radius:.875rem; font-weight:600;
    font-size:.85rem; background:#f3f4f6; color:#374151;
    border:2px solid #e5e7eb; cursor:pointer; transition: all .2s;
}
.rom-btn-secondary:hover { background:#e5e7eb; }
</style>
