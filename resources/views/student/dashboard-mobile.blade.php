@extends('layouts.app-mobile')

@section('content')
<div class="px-4 py-5 space-y-4 max-w-lg mx-auto" style="padding-bottom: 7rem;">

    {{-- Welcome Header --}}
    <div class="card">
        <div class="flex items-center gap-3">
            {{-- Avatar: show photo if uploaded, else initials --}}
            @php $photoUrl = $profile?->getPhotoUrl(); @endphp
            <div class="w-12 h-12 rounded-full flex-shrink-0 shadow-md overflow-hidden"
                 style="background: linear-gradient(135deg, #667eea, #4c51bf);">
                @if($photoUrl)
                    <img src="{{ $photoUrl }}" alt="{{ auth()->user()->name }}"
                         style="width:100%;height:100%;object-fit:cover;display:block;">
                @else
                    <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;">
                        <span class="text-white font-bold text-lg leading-none">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </span>
                    </div>
                @endif
            </div>
            {{-- Greeting --}}
            <div class="flex-1 min-w-0">
                <h1 class="text-base font-bold text-gray-900 truncate">
                    Hi, {{ explode(' ', auth()->user()->name)[0] }}! 👋
                </h1>
                <p class="text-xs text-gray-500 mt-0.5">Welcome back to InternshipHub</p>
            </div>
            {{-- Profile Completion Ring --}}
            <div class="flex-shrink-0 w-12 h-12 relative">
                <svg class="w-full h-full -rotate-90" viewBox="0 0 48 48">
                    <circle cx="24" cy="24" r="20" stroke="#e5e7eb" stroke-width="4" fill="none"/>
                    <circle cx="24" cy="24" r="20"
                            stroke="#5a67d8"
                            stroke-width="4"
                            fill="none"
                            stroke-dasharray="125.6"
                            stroke-dashoffset="{{ 125.6 - (125.6 * ($profileCompletion ?? 0) / 100) }}"
                            stroke-linecap="round"/>
                </svg>
                <div class="absolute inset-0 flex items-center justify-center">
                    <span class="text-[10px] font-bold text-gray-700">{{ $profileCompletion ?? 0 }}%</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Primary CTA Banner --}}
    @if(($profileCompletion ?? 0) < 100)
        <a href="{{ route('profile.edit.mobile') }}" class="block group active:scale-[0.98] transition-transform">
            <div style="background: linear-gradient(135deg, #5a67d8 0%, #4c51bf 100%); border-radius: 1rem; padding: 1.25rem; box-shadow: 0 4px 14px rgba(90,103,216,0.35);">
                <div style="display:flex; align-items:center; justify-content:space-between;">
                    <div>
                        <h2 style="color:#ffffff; font-size:0.9375rem; font-weight:700; margin:0 0 0.2rem;">Complete Your Profile</h2>
                        <p style="color:rgba(255,255,255,0.8); font-size:0.75rem; margin:0;">Get better job matches — {{ 100 - ($profileCompletion ?? 0) }}% remaining</p>
                    </div>
                    <div style="width:2.5rem; height:2.5rem; background:rgba(255,255,255,0.2); border-radius:0.75rem; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                        <i class="fas fa-arrow-right" style="color:#ffffff;"></i>
                    </div>
                </div>
                {{-- Mini progress bar --}}
                <div style="margin-top:0.75rem; height:0.375rem; background:rgba(255,255,255,0.25); border-radius:9999px; overflow:hidden;">
                    <div style="height:100%; background:#ffffff; border-radius:9999px; width:{{ $profileCompletion ?? 0 }}%; transition:width 0.7s ease;"></div>
                </div>
            </div>
        </a>
    @else
        <a href="{{ route('recommendations.index') }}" class="block group active:scale-[0.98] transition-transform">
            <div style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); border-radius: 1rem; padding: 1.25rem; box-shadow: 0 4px 14px rgba(16,185,129,0.35);">
                <div style="display:flex; align-items:center; justify-content:space-between;">
                    <div>
                        <h2 style="color:#ffffff; font-size:0.9375rem; font-weight:700; margin:0 0 0.2rem;">Find Your Next Opportunity</h2>
                        <p style="color:rgba(255,255,255,0.8); font-size:0.75rem; margin:0;">{{ $recommendations ?? 0 }} personalized jobs waiting</p>
                    </div>
                    <div style="width:2.5rem; height:2.5rem; background:rgba(255,255,255,0.2); border-radius:0.75rem; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                        <i class="fas fa-briefcase" style="color:#ffffff;"></i>
                    </div>
                </div>
            </div>
        </a>
    @endif

    {{-- Key Metrics Grid --}}
    <div class="grid grid-cols-3 gap-3">
        {{-- Applications --}}
        <a href="{{ route('my-applications') }}" class="block active:scale-95 transition-transform">
            <div class="card text-center py-4">
                <div style="width:2.25rem; height:2.25rem; background:#dbeafe; border-radius:0.75rem; display:flex; align-items:center; justify-content:center; margin:0 auto 0.5rem;">
                    <i class="fas fa-file-alt" style="color:#2563eb; font-size:0.875rem;"></i>
                </div>
                <div style="font-size:1.25rem; font-weight:700; color:#111827;">{{ $appliedJobs ?? 0 }}</div>
                <div style="font-size:0.6875rem; color:#6b7280; margin-top:0.125rem; font-weight:500;">Applied</div>
            </div>
        </a>

        {{-- Interviews --}}
        <div class="card text-center py-4">
            <div style="width:2.25rem; height:2.25rem; background:#d1fae5; border-radius:0.75rem; display:flex; align-items:center; justify-content:center; margin:0 auto 0.5rem;">
                <i class="fas fa-video" style="color:#059669; font-size:0.875rem;"></i>
            </div>
            <div style="font-size:1.25rem; font-weight:700; color:#111827;">{{ $interviews ?? 0 }}</div>
            <div style="font-size:0.6875rem; color:#6b7280; margin-top:0.125rem; font-weight:500;">Interviews</div>
        </div>

        {{-- Job Matches --}}
        <a href="{{ route('recommendations.index') }}" class="block active:scale-95 transition-transform">
            <div class="card text-center py-4">
                <div style="width:2.25rem; height:2.25rem; background:#fef3c7; border-radius:0.75rem; display:flex; align-items:center; justify-content:center; margin:0 auto 0.5rem;">
                    <i class="fas fa-star" style="color:#d97706; font-size:0.875rem;"></i>
                </div>
                <div style="font-size:1.25rem; font-weight:700; color:#111827;">{{ $recommendations ?? 0 }}</div>
                <div style="font-size:0.6875rem; color:#6b7280; margin-top:0.125rem; font-weight:500;">Matches</div>
            </div>
        </a>
    </div>

    {{-- Quick Actions --}}
    <div class="grid grid-cols-2 gap-3">
        <a href="{{ route('recommendations.index') }}" class="block active:scale-95 transition-transform">
            <div class="card" style="box-shadow: 0 1px 3px rgba(0,0,0,0.06);">
                <div style="display:flex; align-items:center; gap:0.75rem;">
                    <div style="width:2.5rem; height:2.5rem; background:#fef3c7; border-radius:0.75rem; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                        <i class="fas fa-star" style="color:#d97706;"></i>
                    </div>
                    <div style="min-width:0;">
                        <p style="font-size:0.875rem; font-weight:600; color:#111827; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; margin:0;">Browse Jobs</p>
                        <p style="font-size:0.75rem; color:#6b7280; margin:0;">{{ $recommendations ?? 0 }} available</p>
                    </div>
                </div>
            </div>
        </a>

        <a href="{{ route('profile.edit.mobile') }}" class="block active:scale-95 transition-transform">
            <div class="card" style="box-shadow: 0 1px 3px rgba(0,0,0,0.06);">
                <div style="display:flex; align-items:center; gap:0.75rem;">
                    <div style="width:2.5rem; height:2.5rem; background:#e0e7ff; border-radius:0.75rem; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                        <i class="fas fa-user-edit" style="color:#5a67d8;"></i>
                    </div>
                    <div style="min-width:0;">
                        <p style="font-size:0.875rem; font-weight:600; color:#111827; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; margin:0;">Edit Profile</p>
                        <p style="font-size:0.75rem; color:#6b7280; margin:0;">{{ $profileCompletion ?? 0 }}% complete</p>
                    </div>
                </div>
            </div>
        </a>
    </div>

    {{-- Recent Activity --}}
    <div class="card" id="mobile-activity-card">
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1rem;">
            <div style="display:flex; align-items:center; gap:0.5rem;">
                <h2 style="font-size:0.9375rem; font-weight:700; color:#111827; margin:0;">Recent Activity</h2>
                <span id="mobile-live-dot" style="display:inline-block;width:7px;height:7px;background:#10b981;border-radius:50%;animation:activityPulse 2s infinite;"></span>
            </div>
            <a href="{{ route('my-applications') }}"
               style="font-size:0.75rem; font-weight:600; color:#5a67d8; text-decoration:none;">
                View All <i class="fas fa-chevron-right" style="font-size:0.5625rem;"></i>
            </a>
        </div>

        <div id="mobile-activity-list" style="display:flex; flex-direction:column; gap:0.75rem;">
            {{-- Skeleton --}}
            @for($i = 0; $i < 3; $i++)
            <div style="display:flex;align-items:flex-start;gap:0.75rem;opacity:0.45;">
                <div style="width:2rem;height:2rem;background:#e5e7eb;border-radius:0.75rem;flex-shrink:0;"></div>
                <div style="flex:1;">
                    <div style="height:12px;background:#e5e7eb;border-radius:4px;width:65%;margin-bottom:6px;"></div>
                    <div style="height:10px;background:#f3f4f6;border-radius:4px;width:40%;"></div>
                </div>
            </div>
            @endfor
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
(function() {
    'use strict';

    const POLL_INTERVAL = 30000;
    const ENDPOINT = '{{ route("dashboard.recent-activity") }}';
    const list = document.getElementById('mobile-activity-list');
    let lastUpdated = null;
    let pollTimer = null;

    function esc(str) {
        const d = document.createElement('div');
        d.textContent = str;
        return d.innerHTML;
    }

    function buildItem(act) {
        return `<div style="display:flex;align-items:flex-start;gap:0.75rem;animation:activitySlideIn 0.35s ease both;">
            <div style="width:2rem;height:2rem;background:${act.color}22;border-radius:0.75rem;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:0.125rem;">
                <i class="fas fa-${act.icon}" style="color:${act.color};font-size:0.75rem;"></i>
            </div>
            <div style="flex:1;min-width:0;">
                <p style="font-size:0.875rem;font-weight:500;color:#111827;margin:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${esc(act.title)}</p>
                <p style="font-size:0.75rem;color:#9ca3af;margin:0.125rem 0 0;">${esc(act.time)}</p>
            </div>
        </div>`;
    }

    function buildEmpty() {
        return `<div style="display:flex;align-items:flex-start;gap:0.75rem;">
            <div style="width:2rem;height:2rem;background:#e0e7ff;border-radius:0.75rem;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas fa-rocket" style="color:#5a67d8;font-size:0.75rem;"></i>
            </div>
            <div style="flex:1;">
                <p style="font-size:0.875rem;font-weight:600;color:#111827;margin:0;">No activity yet</p>
                <p style="font-size:0.75rem;color:#6b7280;margin:0.125rem 0 0;">Apply to internships to get started</p>
            </div>
        </div>`;
    }

    async function fetchAndRender() {
        try {
            const res = await fetch(ENDPOINT, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                credentials: 'same-origin'
            });
            if (!res.ok) return;
            const data = await res.json();

            if (lastUpdated === data.last_updated && list.querySelector('[style*="activitySlideIn"]')) return;
            lastUpdated = data.last_updated;

            list.innerHTML = data.count === 0
                ? buildEmpty()
                : data.activities.slice(0, 3).map(buildItem).join('');
        } catch(e) {}
    }

    fetchAndRender();
    pollTimer = setInterval(fetchAndRender, POLL_INTERVAL);

    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            clearInterval(pollTimer);
        } else {
            fetchAndRender();
            pollTimer = setInterval(fetchAndRender, POLL_INTERVAL);
        }
    });
})();
</script>
<style>
@verbatim
@keyframes activityPulse {
    0%, 100% { opacity:1; transform:scale(1); }
    50%       { opacity:0.4; transform:scale(1.4); }
}
@keyframes activitySlideIn {
    from { opacity:0; transform:translateY(6px); }
    to   { opacity:1; transform:translateY(0); }
}
@endverbatim
</style>
@endpush
