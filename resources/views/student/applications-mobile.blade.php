@extends('layouts.app-mobile')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6">
    
    {{-- Header --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 mb-2">My Applications</h1>
        <p class="text-sm text-gray-600">
            <i class="fas fa-clipboard-list mr-1"></i>
            Track your application progress
        </p>
    </div>

    {{-- Stats Summary --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
        <div class="bg-white rounded-xl p-3 border border-gray-200 text-center">
            <div class="text-2xl font-bold text-gray-900">{{ $stats['total'] ?? 0 }}</div>
            <div class="text-xs text-gray-600">Total</div>
        </div>
        <div class="bg-white rounded-xl p-3 border border-gray-200 text-center">
            <div class="text-2xl font-bold text-yellow-600">{{ $stats['pending'] ?? 0 }}</div>
            <div class="text-xs text-gray-600">Pending</div>
        </div>
        <div class="bg-white rounded-xl p-3 border border-gray-200 text-center">
            <div class="text-2xl font-bold text-blue-600">{{ $stats['under_review'] ?? 0 }}</div>
            <div class="text-xs text-gray-600">Reviewing</div>
        </div>
        <div class="bg-white rounded-xl p-3 border border-gray-200 text-center">
            <div class="text-2xl font-bold text-green-600">{{ $stats['approved'] ?? 0 }}</div>
            <div class="text-xs text-gray-600">Approved</div>
        </div>
    </div>

    {{-- Filter Tabs --}}
    <div class="flex gap-2 overflow-x-auto pb-2 -mx-4 px-4 scrollbar-hide mb-4">
        <button class="filter-tab active" data-status="all">
            All ({{ $stats['total'] ?? 0 }})
        </button>
        <button class="filter-tab" data-status="pending">
            Pending ({{ $stats['pending'] ?? 0 }})
        </button>
        <button class="filter-tab" data-status="under_review">
            Reviewing ({{ $stats['under_review'] ?? 0 }})
        </button>
        <button class="filter-tab" data-status="shortlisted">
            Shortlisted ({{ $stats['shortlisted'] ?? 0 }})
        </button>
        <button class="filter-tab" data-status="interview_scheduled">
            Interview ({{ $stats['interview_scheduled'] ?? 0 }})
        </button>
        <button class="filter-tab" data-status="approved">
            Approved ({{ $stats['approved'] ?? 0 }})
        </button>
        <button class="filter-tab" data-status="rejected">
            Rejected ({{ $stats['rejected'] ?? 0 }})
        </button>
    </div>

    @if(count($applications) > 0)
        {{-- Application Cards --}}
        <div class="space-y-4" id="applicationList">
            @foreach($applications as $application)
                <x-application-card :application="$application" />
            @endforeach
        </div>

        {{-- Pagination --}}
        @if($applications->hasPages())
            <div class="mt-6">
                {{ $applications->links() }}
            </div>
        @endif

    @else
        {{-- Empty State --}}
        <div class="bg-white rounded-2xl p-8 text-center border border-gray-200">
            <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-inbox text-3xl text-gray-400"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">No Applications Yet</h3>
            <p class="text-sm text-gray-600 mb-6">
                Start applying to internships that match your skills
            </p>
            <a href="{{ route('recommendations.index') }}" class="inline-flex items-center bg-primary-600 text-white px-6 py-3 rounded-xl font-medium text-sm hover:bg-primary-700 transition-colors">
                <i class="fas fa-search mr-2"></i>
                Browse Opportunities
            </a>
        </div>
    @endif

</div>

<style>
/* Filter Tabs */
.filter-tab {
    display: inline-flex;
    align-items: center;
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

.filter-tab:active {
    transform: scale(0.95);
}

.filter-tab.active {
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

.hover\:bg-primary-700:hover {
    background-color: #4c51bf;
}
</style>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const filterTabs = document.querySelectorAll('.filter-tab');
    const applicationList = document.getElementById('applicationList');
    const cards = applicationList?.querySelectorAll('.bg-white.rounded-2xl');

    filterTabs.forEach(tab => {
        tab.addEventListener('click', function() {
            // Remove active from all
            filterTabs.forEach(t => t.classList.remove('active'));
            
            // Add active to clicked
            this.classList.add('active');
            
            const status = this.dataset.status;
            
            // Filter cards
            cards?.forEach(card => {
                if (status === 'all') {
                    card.style.display = '';
                } else {
                    const cardStatus = card.querySelector('[class*="badge"]')?.textContent.toLowerCase().trim().replace(/\s+/g, '_');
                    card.style.display = cardStatus === status ? '' : 'none';
                }
            });
        });
    });
});
</script>
@endpush
@endsection
