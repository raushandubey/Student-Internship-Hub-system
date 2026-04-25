@extends('layouts.app-mobile')

@section('content')
<div class="px-4 py-5 max-w-lg mx-auto space-y-4" style="padding-bottom: 7rem;">

    {{-- Header --}}
    <div>
        <h1 class="text-2xl font-bold text-gray-900">My Applications</h1>
        <p class="text-sm text-gray-500 mt-0.5">
            <i class="fas fa-clipboard-list mr-1"></i>
            Track your application progress
        </p>
    </div>

    {{-- Stats Summary --}}
    <div class="grid grid-cols-2 gap-3">
        <div class="card text-center py-3">
            <div class="text-2xl font-bold text-gray-900">{{ $stats['total'] ?? 0 }}</div>
            <div class="text-xs text-gray-500 font-medium mt-0.5">Total</div>
        </div>
        <div class="card text-center py-3">
            <div class="text-2xl font-bold text-yellow-600">{{ $stats['pending'] ?? 0 }}</div>
            <div class="text-xs text-gray-500 font-medium mt-0.5">Pending</div>
        </div>
        <div class="card text-center py-3">
            <div class="text-2xl font-bold text-blue-600">{{ $stats['under_review'] ?? 0 }}</div>
            <div class="text-xs text-gray-500 font-medium mt-0.5">Reviewing</div>
        </div>
        <div class="card text-center py-3">
            <div class="text-2xl font-bold text-green-600">{{ $stats['approved'] ?? 0 }}</div>
            <div class="text-xs text-gray-500 font-medium mt-0.5">Approved</div>
        </div>
    </div>

    {{-- Filter Tabs (horizontally scrollable) --}}
    <div class="flex gap-2 overflow-x-auto pb-1 -mx-4 px-4 scrollbar-hide">
        <button class="filter-tab active flex-shrink-0" data-filter="all">
            All ({{ $stats['total'] ?? 0 }})
        </button>
        <button class="filter-tab flex-shrink-0" data-filter="pending">
            Pending ({{ $stats['pending'] ?? 0 }})
        </button>
        <button class="filter-tab flex-shrink-0" data-filter="under_review">
            Reviewing ({{ $stats['under_review'] ?? 0 }})
        </button>
        <button class="filter-tab flex-shrink-0" data-filter="shortlisted">
            Shortlisted ({{ $stats['shortlisted'] ?? 0 }})
        </button>
        <button class="filter-tab flex-shrink-0" data-filter="interview_scheduled">
            Interview ({{ $stats['interview_scheduled'] ?? 0 }})
        </button>
        <button class="filter-tab flex-shrink-0" data-filter="approved">
            Approved ({{ $stats['approved'] ?? 0 }})
        </button>
        <button class="filter-tab flex-shrink-0" data-filter="rejected">
            Rejected ({{ $stats['rejected'] ?? 0 }})
        </button>
    </div>

    @if(count($applications) > 0)
        {{-- Application Cards — each card has data-status for reliable filtering --}}
        <div class="space-y-4" id="applicationList">
            @foreach($applications as $application)
                <div data-status="{{ $application->status->value ?? 'pending' }}">
                    <x-application-card :application="$application" />
                </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        @if(method_exists($applications, 'hasPages') && $applications->hasPages())
            <div class="mt-4">
                {{ $applications->links() }}
            </div>
        @endif

        {{-- No Results After Filter --}}
        <div id="noFilterResults" class="hidden card p-8 text-center">
            <div class="w-14 h-14 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                <i class="fas fa-filter text-gray-400 text-xl"></i>
            </div>
            <p class="text-sm font-semibold text-gray-700">No applications in this category</p>
            <button onclick="resetFilter()" class="mt-3 text-sm text-primary-600 font-medium">
                Show all
            </button>
        </div>

    @else
        {{-- Empty State --}}
        <div class="card p-8 text-center">
            <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-inbox text-3xl text-gray-400"></i>
            </div>
            <h3 class="text-base font-bold text-gray-900 mb-2">No Applications Yet</h3>
            <p class="text-sm text-gray-500 mb-5">
                Start applying to internships that match your skills
            </p>
            <a href="{{ route('recommendations.index') }}"
               class="btn btn-primary inline-flex">
                <i class="fas fa-search mr-2 text-xs"></i>
                Browse Opportunities
            </a>
        </div>
    @endif

</div>

@push('scripts')
<script>
(function() {
    'use strict';

    const filterTabs   = document.querySelectorAll('.filter-tab');
    const appList      = document.getElementById('applicationList');
    const noResults    = document.getElementById('noFilterResults');
    let activeFilter   = 'all';

    function applyFilter(filter) {
        activeFilter = filter;

        if (!appList) return;

        // Each application is wrapped in a div[data-status]
        const wrappers = appList.querySelectorAll('[data-status]');
        let visibleCount = 0;

        wrappers.forEach(wrapper => {
            const status = wrapper.dataset.status || '';
            const show   = (filter === 'all') || (status === filter);

            wrapper.style.display = show ? '' : 'none';
            if (show) visibleCount++;
        });

        // Show/hide "no results" state
        if (noResults) {
            noResults.classList.toggle('hidden', visibleCount > 0 || filter === 'all');
        }
    }

    filterTabs.forEach(tab => {
        tab.addEventListener('click', function() {
            filterTabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            applyFilter(this.dataset.filter);
        });
    });

    window.resetFilter = function() {
        filterTabs.forEach(t => {
            t.classList.remove('active');
            if (t.dataset.filter === 'all') t.classList.add('active');
        });
        applyFilter('all');
    };
})();
</script>
@endpush
@endsection
