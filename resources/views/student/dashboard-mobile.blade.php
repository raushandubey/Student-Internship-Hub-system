@extends('layouts.app-mobile')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6 space-y-4">
    
    {{-- Welcome Header (Collapsed) --}}
    <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-200">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 bg-gradient-to-br from-primary-500 to-primary-600 rounded-full flex items-center justify-center flex-shrink-0 shadow-lg">
                <span class="text-white font-bold text-lg">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </span>
            </div>
            <div class="flex-1 min-w-0">
                <h1 class="text-lg font-semibold text-gray-900 truncate">
                    Hi, {{ explode(' ', auth()->user()->name)[0] }}! 👋
                </h1>
                <p class="text-sm text-gray-500">Welcome back</p>
            </div>
            <div class="flex-shrink-0">
                <div class="w-12 h-12 relative">
                    <svg class="w-full h-full transform -rotate-90">
                        <circle cx="24" cy="24" r="20" stroke="#e5e7eb" stroke-width="4" fill="none"/>
                        <circle cx="24" cy="24" r="20" 
                                stroke="#5a67d8" 
                                stroke-width="4" 
                                fill="none"
                                stroke-dasharray="125.6"
                                stroke-dashoffset="{{ 125.6 - (125.6 * ($profileCompletion ?? 75) / 100) }}"
                                stroke-linecap="round"/>
                    </svg>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <span class="text-xs font-bold text-gray-700">{{ $profileCompletion ?? 75 }}%</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Primary CTA --}}
    @if(($profileCompletion ?? 75) < 100)
        <a href="{{ route('profile.edit') }}" class="block group">
            <div class="bg-gradient-to-r from-primary-600 to-primary-700 rounded-2xl p-6 shadow-lg transform transition-transform group-hover:scale-[1.02] group-active:scale-[0.98]">
                <div class="flex items-center justify-between text-white">
                    <div>
                        <h2 class="text-xl font-bold mb-1">Complete Your Profile</h2>
                        <p class="text-primary-100 text-sm">Get better job matches</p>
                    </div>
                    <i class="fas fa-arrow-right text-2xl transition-transform group-hover:translate-x-1"></i>
                </div>
            </div>
        </a>
    @else
        <a href="{{ route('recommendations.index') }}" class="block group">
            <div class="bg-gradient-to-r from-green-600 to-green-700 rounded-2xl p-6 shadow-lg transform transition-transform group-hover:scale-[1.02] group-active:scale-[0.98]">
                <div class="flex items-center justify-between text-white">
                    <div>
                        <h2 class="text-xl font-bold mb-1">Find Your Next Opportunity</h2>
                        <p class="text-green-100 text-sm">{{ $recommendations ?? 0 }} jobs waiting</p>
                    </div>
                    <i class="fas fa-briefcase text-2xl transition-transform group-hover:translate-x-1"></i>
                </div>
            </div>
        </a>
    @endif

    {{-- Key Metrics --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        {{-- Applications --}}
        <a href="{{ route('my-applications') }}" class="block group">
            <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-200 hover:shadow-md transition-all group-active:scale-95">
                <div class="flex items-center justify-between mb-2">
                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-file-alt text-blue-600"></i>
                    </div>
                    <span class="text-2xl font-bold text-gray-900">{{ $appliedJobs ?? 0 }}</span>
                </div>
                <p class="text-sm text-gray-600">Applications</p>
                <div class="mt-2 flex items-center text-xs text-blue-600">
                    <span>View all</span>
                    <i class="fas fa-chevron-right ml-1 text-[10px]"></i>
                </div>
            </div>
        </a>

        {{-- Interviews --}}
        <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-200">
            <div class="flex items-center justify-between mb-2">
                <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-video text-green-600"></i>
                </div>
                <span class="text-2xl font-bold text-gray-900">{{ $interviews ?? 0 }}</span>
            </div>
            <p class="text-sm text-gray-600">Interviews</p>
            <div class="mt-2 text-xs text-gray-400">
                <span>Scheduled</span>
            </div>
        </div>

        {{-- Profile Views --}}
        <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-200">
            <div class="flex items-center justify-between mb-2">
                <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-eye text-purple-600"></i>
                </div>
                <span class="text-2xl font-bold text-gray-900">{{ $profileViews ?? 0 }}</span>
            </div>
            <p class="text-sm text-gray-600">Profile Views</p>
            <div class="mt-2 text-xs text-gray-400">
                <span>Last 7 days</span>
            </div>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <a href="{{ route('recommendations.index') }}" class="block group">
            <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-200 hover:shadow-md transition-all group-active:scale-95">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-star text-yellow-600 text-xl"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="font-semibold text-gray-900">Job Matches</h3>
                        <p class="text-sm text-gray-500">{{ $recommendations ?? 0 }} available</p>
                    </div>
                    <i class="fas fa-chevron-right text-gray-400 transition-transform group-hover:translate-x-1"></i>
                </div>
            </div>
        </a>

        <a href="{{ route('my-applications') }}" class="block group">
            <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-200 hover:shadow-md transition-all group-active:scale-95">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-clipboard-list text-blue-600 text-xl"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="font-semibold text-gray-900">Track Applications</h3>
                        <p class="text-sm text-gray-500">{{ $appliedJobs ?? 0 }} in progress</p>
                    </div>
                    <i class="fas fa-chevron-right text-gray-400 transition-transform group-hover:translate-x-1"></i>
                </div>
            </div>
        </a>
    </div>

    {{-- Recent Activity (Collapsed) --}}
    <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-200">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-gray-900">Recent Activity</h2>
            <button class="text-sm text-primary-600 font-medium hover:text-primary-700">View All</button>
        </div>
        <div class="space-y-3">
            {{-- Show 3 recent activities max --}}
            @if(isset($recentActivities) && count($recentActivities) > 0)
                @foreach(array_slice($recentActivities, 0, 3) as $activity)
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-{{ $activity['icon'] ?? 'info-circle' }} text-blue-600 text-sm"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-gray-900 font-medium">{{ $activity['title'] }}</p>
                            <p class="text-xs text-gray-500">{{ $activity['time'] }}</p>
                        </div>
                    </div>
                @endforeach
            @else
                <div class="flex items-start gap-3">
                    <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-paper-plane text-blue-600 text-sm"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm text-gray-900 font-medium">Welcome to InternshipHub!</p>
                        <p class="text-xs text-gray-500">Start by completing your profile</p>
                    </div>
                </div>
            @endif
        </div>
    </div>

</div>

<style>
.bg-primary-500 {
    background-color: #667eea;
}

.bg-primary-600 {
    background-color: #5a67d8;
}

.bg-primary-700 {
    background-color: #4c51bf;
}

.text-primary-100 {
    color: #ebf0ff;
}

.text-primary-600 {
    color: #5a67d8;
}

.text-primary-700 {
    color: #4c51bf;
}

.from-primary-600 {
    --tw-gradient-from: #5a67d8;
}

.to-primary-700 {
    --tw-gradient-to: #4c51bf;
}
</style>
@endsection
