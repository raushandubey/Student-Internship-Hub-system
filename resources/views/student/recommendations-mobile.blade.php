@extends('layouts.app-mobile')

@section('content')
<div class="px-4 py-5 max-w-lg mx-auto space-y-4" style="padding-bottom: 7rem;">

    {{-- Header --}}
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Job Matches</h1>
        <p class="text-sm text-gray-500 mt-0.5">
            <i class="fas fa-brain mr-1"></i>
            Personalized to your skills &amp; interests
        </p>
    </div>

    {{-- Search + Filter --}}
    <div class="space-y-3">
        {{-- Search Bar --}}
        <div class="relative">
            <i class="fas fa-search absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm pointer-events-none"></i>
            <input type="text"
                   id="searchInput"
                   placeholder="Search internships..."
                   class="w-full pl-10 pr-4 py-3 bg-white border border-gray-200 rounded-xl text-sm
                          focus:outline-none focus:ring-2 focus:border-transparent transition-all"
                   style="--tw-ring-color: #5a67d8;">
        </div>

        {{-- Filter Chips --}}
        <div class="flex gap-2 overflow-x-auto pb-1 -mx-4 px-4 scrollbar-hide">
            <button class="filter-chip active flex-shrink-0" data-filter="all">
                <i class="fas fa-th-large text-xs"></i> All
            </button>
            <button class="filter-chip flex-shrink-0" data-filter="high-match">
                <i class="fas fa-star text-xs"></i> High Match
            </button>
            <button class="filter-chip flex-shrink-0" data-filter="recent">
                <i class="fas fa-clock text-xs"></i> Recent
            </button>
            <button class="filter-chip flex-shrink-0" data-filter="remote">
                <i class="fas fa-home text-xs"></i> Remote
            </button>
        </div>
    </div>

    @if(count($recommendations) > 0)
        {{-- Stats Summary --}}
        <div class="grid grid-cols-3 gap-3">
            <div class="card text-center py-3">
                <div class="text-xl font-bold text-gray-900">{{ count($recommendations) }}</div>
                <div class="text-[10px] text-gray-500 font-medium mt-0.5">Total</div>
            </div>
            <div class="card text-center py-3">
                @php $avgScore = round(collect($recommendations)->avg(fn($r) => $r['score'] * 100)); @endphp
                <div class="text-xl font-bold text-gray-900">{{ $avgScore }}%</div>
                <div class="text-[10px] text-gray-500 font-medium mt-0.5">Avg Match</div>
            </div>
            <div class="card text-center py-3">
                @php $highMatches = collect($recommendations)->filter(fn($r) => $r['score'] >= 0.75)->count(); @endphp
                <div class="text-xl font-bold text-primary-600">{{ $highMatches }}</div>
                <div class="text-[10px] text-gray-500 font-medium mt-0.5">Premium</div>
            </div>
        </div>

        {{-- No search results state --}}
        <div id="noSearchResults" class="hidden card p-8 text-center">
            <div class="w-14 h-14 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                <i class="fas fa-search text-gray-400 text-xl"></i>
            </div>
            <p class="text-sm font-semibold text-gray-700">No internships match your search</p>
            <button onclick="clearSearch()" class="mt-2 text-sm text-primary-600 font-medium">Clear search</button>
        </div>

        {{-- Internship Cards --}}
        <div class="space-y-4" id="internshipList">
            @foreach($recommendations as $rec)
                @php
                    $internship  = $rec['internship'];
                    $matchScore  = round($rec['score'] * 100);
                    $isRemote    = stripos($internship->location ?? '', 'remote') !== false;
                    $isRecent    = $internship->created_at && $internship->created_at->gt(now()->subDays(7));
                @endphp

                <div data-match="{{ $matchScore }}"
                     data-remote="{{ $isRemote ? 'true' : 'false' }}"
                     data-recent="{{ $isRecent ? 'true' : 'false' }}"
                     class="internship-item">
                <x-internship-card
                        :internship="$internship"
                        :matchScore="$matchScore"
                        :matchingSkills="$rec['matching_skills'] ?? []"
                        :missingSkills="$rec['missing_skills'] ?? []"
                        :locationFitLabel="$rec['location_fit_label'] ?? null"
                    />
                </div>
            @endforeach
        </div>

        @if(count($recommendations) >= 10)
            <div class="text-center">
                <button id="loadMoreBtn"
                        class="btn btn-secondary w-full">
                    <i class="fas fa-plus-circle mr-2 text-xs"></i>
                    Load More
                </button>
            </div>
        @endif

    @else
        {{-- Empty State --}}
        <div class="card p-8 text-center">
            <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-search text-3xl text-gray-400"></i>
            </div>
            <h3 class="text-base font-bold text-gray-900 mb-2">No Recommendations Yet</h3>
            <p class="text-sm text-gray-500 mb-5">
                Complete your profile to unlock personalized job matches
            </p>
            <a href="{{ route('profile.edit.mobile') }}" class="btn btn-primary inline-flex">
                <i class="fas fa-user-edit mr-2 text-xs"></i>
                Complete Profile
            </a>
        </div>
    @endif

</div>

@push('scripts')
<script>
(function() {
    'use strict';

    const searchInput   = document.getElementById('searchInput');
    const internshipList = document.getElementById('internshipList');
    const noResults     = document.getElementById('noSearchResults');
    const filterChips   = document.querySelectorAll('.filter-chip');
    let activeFilter    = 'all';
    let searchTerm      = '';

    function getItems() {
        return internshipList ? internshipList.querySelectorAll('.internship-item') : [];
    }

    function applyFilters() {
        const items = getItems();
        let visibleCount = 0;

        items.forEach(item => {
            const text      = item.textContent.toLowerCase();
            const matchScore = parseInt(item.dataset.match || '0', 10);
            const isRemote  = item.dataset.remote === 'true';
            const isRecent  = item.dataset.recent === 'true';

            // Search filter
            const matchesSearch = !searchTerm || text.includes(searchTerm);

            // Category filter
            let matchesFilter = true;
            if (activeFilter === 'high-match') {
                matchesFilter = matchScore >= 75;
            } else if (activeFilter === 'remote') {
                matchesFilter = isRemote;
            } else if (activeFilter === 'recent') {
                matchesFilter = isRecent;
            }

            const show = matchesSearch && matchesFilter;
            item.style.display = show ? '' : 'none';
            if (show) visibleCount++;
        });

        // Show no-results state
        if (noResults) {
            noResults.classList.toggle('hidden', visibleCount > 0);
        }
    }

    // Search
    let searchDebounce;
    searchInput?.addEventListener('input', function(e) {
        clearTimeout(searchDebounce);
        searchDebounce = setTimeout(() => {
            searchTerm = e.target.value.toLowerCase().trim();
            applyFilters();
        }, 200);
    });

    // Filter chips
    filterChips.forEach(chip => {
        chip.addEventListener('click', function() {
            filterChips.forEach(c => c.classList.remove('active'));
            this.classList.add('active');
            activeFilter = this.dataset.filter;
            applyFilters();
        });
    });

    // Clear search
    window.clearSearch = function() {
        if (searchInput) searchInput.value = '';
        searchTerm = '';
        applyFilters();
    };

    // Load More (placeholder — implement server-side pagination if needed)
    const loadMoreBtn = document.getElementById('loadMoreBtn');
    loadMoreBtn?.addEventListener('click', function() {
        const btn = this;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2 text-xs"></i>Loading...';
        btn.disabled = true;
        // Restore after timeout (replace with actual AJAX if pagination is added)
        setTimeout(() => {
            btn.innerHTML = '<i class="fas fa-plus-circle mr-2 text-xs"></i>Load More';
            btn.disabled = false;
        }, 1000);
    });
})();
</script>
@endpush
@endsection
