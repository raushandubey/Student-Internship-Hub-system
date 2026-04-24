@extends('layouts.app-mobile')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6">
    
    {{-- Header --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 mb-2">Job Recommendations</h1>
        <p class="text-sm text-gray-600">
            <i class="fas fa-brain mr-1"></i>
            Personalized matches based on your skills
        </p>
    </div>

    {{-- Search & Filter --}}
    <div class="mb-4 space-y-3">
        {{-- Search Bar --}}
        <div class="relative">
            <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
            <input type="text" 
                   id="searchInput"
                   placeholder="Search internships..." 
                   class="w-full pl-10 pr-4 py-3 bg-white border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent">
        </div>

        {{-- Filter Chips --}}
        <div class="flex gap-2 overflow-x-auto pb-2 -mx-4 px-4 scrollbar-hide">
            <button class="filter-chip active" data-filter="all">
                <i class="fas fa-th-large text-xs"></i>
                All
            </button>
            <button class="filter-chip" data-filter="high-match">
                <i class="fas fa-star text-xs"></i>
                High Match
            </button>
            <button class="filter-chip" data-filter="recent">
                <i class="fas fa-clock text-xs"></i>
                Recent
            </button>
            <button class="filter-chip" data-filter="remote">
                <i class="fas fa-home text-xs"></i>
                Remote
            </button>
        </div>
    </div>

    @if(count($recommendations) > 0)
        {{-- Stats Summary --}}
        <div class="grid grid-cols-3 gap-3 mb-6">
            <div class="bg-white rounded-xl p-3 border border-gray-200 text-center">
                <div class="text-2xl font-bold text-gray-900">{{ count($recommendations) }}</div>
                <div class="text-xs text-gray-600">Total Matches</div>
            </div>
            <div class="bg-white rounded-xl p-3 border border-gray-200 text-center">
                @php $avgScore = round(collect($recommendations)->avg(fn($r) => $r['score'] * 100)); @endphp
                <div class="text-2xl font-bold text-gray-900">{{ $avgScore }}%</div>
                <div class="text-xs text-gray-600">Avg Match</div>
            </div>
            <div class="bg-white rounded-xl p-3 border border-gray-200 text-center">
                @php $highMatches = collect($recommendations)->filter(fn($r) => $r['score'] >= 0.75)->count(); @endphp
                <div class="text-2xl font-bold text-gray-900">{{ $highMatches }}</div>
                <div class="text-xs text-gray-600">Premium</div>
            </div>
        </div>

        {{-- Internship Cards --}}
        <div class="space-y-4" id="internshipList">
            @foreach($recommendations as $rec)
                @php
                    $internship = $rec['internship'];
                    $matchScore = round($rec['score'] * 100);
                @endphp
                
                <x-internship-card 
                    :internship="$internship"
                    :matchScore="$matchScore"
                    :matchingSkills="$rec['matching_skills'] ?? []"
                    :missingSkills="$rec['missing_skills'] ?? []"
                />
            @endforeach
        </div>

        {{-- Load More --}}
        @if(count($recommendations) >= 10)
            <div class="mt-6 text-center">
                <button id="loadMoreBtn" class="bg-white border border-gray-200 text-gray-700 px-6 py-3 rounded-xl font-medium text-sm hover:bg-gray-50 transition-colors">
                    <i class="fas fa-plus-circle mr-2"></i>
                    Load More
                </button>
            </div>
        @endif

    @else
        {{-- Empty State --}}
        <div class="bg-white rounded-2xl p-8 text-center border border-gray-200">
            <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-search text-3xl text-gray-400"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">No Recommendations Yet</h3>
            <p class="text-sm text-gray-600 mb-6">
                Complete your profile to get personalized job matches
            </p>
            <a href="{{ route('profile.edit') }}" class="inline-flex items-center bg-primary-600 text-white px-6 py-3 rounded-xl font-medium text-sm hover:bg-primary-700 transition-colors">
                <i class="fas fa-user-edit mr-2"></i>
                Complete Profile
            </a>
        </div>
    @endif

</div>

<style>
/* Filter Chips */
.filter-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.5rem 1rem;
    background-color: white;
    border: 1px solid #e5e7eb;
    border-radius: 0.75rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: #6b7280;
    white-space: nowrap;
    transition: all 0.2s;
}

.filter-chip:active {
    transform: scale(0.95);
}

.filter-chip.active {
    background-color: #5a67d8;
    color: white;
    border-color: #5a67d8;
}

/* Hide scrollbar but keep functionality */
.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}

.scrollbar-hide::-webkit-scrollbar {
    display: none;
}

/* Primary colors */
.bg-primary-600 {
    background-color: #5a67d8;
}

.bg-primary-700 {
    background-color: #4c51bf;
}

.text-primary-600 {
    color: #5a67d8;
}

.ring-primary-500 {
    --tw-ring-color: #667eea;
}

.focus\:ring-primary-500:focus {
    --tw-ring-color: #667eea;
}
</style>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Search functionality
    const searchInput = document.getElementById('searchInput');
    const internshipList = document.getElementById('internshipList');
    const cards = internshipList?.querySelectorAll('.bg-white.rounded-2xl');

    searchInput?.addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase();
        
        cards?.forEach(card => {
            const text = card.textContent.toLowerCase();
            if (text.includes(searchTerm)) {
                card.style.display = '';
            } else {
                card.style.display = 'none';
            }
        });
    });

    // Filter functionality
    const filterChips = document.querySelectorAll('.filter-chip');
    
    filterChips.forEach(chip => {
        chip.addEventListener('click', function() {
            // Remove active from all
            filterChips.forEach(c => c.classList.remove('active'));
            
            // Add active to clicked
            this.classList.add('active');
            
            const filter = this.dataset.filter;
            
            // Apply filter logic here
            cards?.forEach(card => {
                if (filter === 'all') {
                    card.style.display = '';
                } else if (filter === 'high-match') {
                    const matchScore = card.querySelector('[class*="bg-green-100"]');
                    card.style.display = matchScore ? '' : 'none';
                } else if (filter === 'recent') {
                    // Show all for now (implement date filtering if needed)
                    card.style.display = '';
                } else if (filter === 'remote') {
                    const text = card.textContent.toLowerCase();
                    card.style.display = text.includes('remote') ? '' : 'none';
                }
            });
        });
    });

    // Load more functionality
    const loadMoreBtn = document.getElementById('loadMoreBtn');
    loadMoreBtn?.addEventListener('click', function() {
        this.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Loading...';
        
        // Simulate loading (replace with actual AJAX call)
        setTimeout(() => {
            this.innerHTML = '<i class="fas fa-plus-circle mr-2"></i>Load More';
            // Add more cards here
        }, 1000);
    });
});
</script>
@endpush
@endsection
