{{-- Mobile-First Application Card Component --}}
@props(['application'])

@php
    $statusColors = [
        'pending' => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-700', 'icon' => 'fa-clock'],
        'under_review' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-700', 'icon' => 'fa-eye'],
        'shortlisted' => ['bg' => 'bg-purple-100', 'text' => 'text-purple-700', 'icon' => 'fa-star'],
        'interview_scheduled' => ['bg' => 'bg-indigo-100', 'text' => 'text-indigo-700', 'icon' => 'fa-video'],
        'approved' => ['bg' => 'bg-green-100', 'text' => 'text-green-700', 'icon' => 'fa-check-circle'],
        'rejected' => ['bg' => 'bg-red-100', 'text' => 'text-red-700', 'icon' => 'fa-times-circle'],
    ];
    
    $status = $application->status->value ?? 'pending';
    $color = $statusColors[$status] ?? $statusColors['pending'];
@endphp

<div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-4 hover:shadow-md transition-all">
    {{-- Header: Company + Status --}}
    <div class="flex items-start justify-between mb-3">
        <div class="flex items-center gap-3 flex-1 min-w-0">
            <div class="w-12 h-12 bg-gradient-to-br from-gray-100 to-gray-200 rounded-xl flex items-center justify-center flex-shrink-0">
                <span class="text-gray-600 font-bold text-sm">
                    {{ strtoupper(substr($application->internship->organization, 0, 2)) }}
                </span>
            </div>
            <div class="flex-1 min-w-0">
                <h3 class="font-semibold text-base text-gray-900 truncate">
                    {{ $application->internship->title }}
                </h3>
                <p class="text-sm text-gray-600 truncate">
                    {{ $application->internship->organization }}
                </p>
            </div>
        </div>
        <div class="flex-shrink-0 ml-2">
            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium {{ $color['bg'] }} {{ $color['text'] }}">
                <i class="fas {{ $color['icon'] }} text-[10px]"></i>
                {{ ucfirst(str_replace('_', ' ', $status)) }}
            </span>
        </div>
    </div>

    {{-- Timeline/Progress --}}
    <div class="mb-3">
        <div class="flex items-center justify-between text-xs text-gray-600 mb-2">
            <span>Application Progress</span>
            <span class="font-medium">{{ $application->getProgressPercentage() ?? 25 }}%</span>
        </div>
        <div class="w-full h-2 bg-gray-100 rounded-full overflow-hidden">
            <div class="h-full {{ $status === 'approved' ? 'bg-green-500' : ($status === 'rejected' ? 'bg-red-500' : 'bg-primary-600') }} transition-all duration-500"
                 style="width: {{ $application->getProgressPercentage() ?? 25 }}%">
            </div>
        </div>
    </div>

    {{-- Details --}}
    <div class="flex flex-wrap gap-3 text-xs text-gray-600 mb-3">
        <span class="flex items-center gap-1">
            <i class="fas fa-calendar text-[10px]"></i>
            Applied {{ $application->created_at->diffForHumans() }}
        </span>
        @if($application->match_score)
            <span class="flex items-center gap-1">
                <i class="fas fa-chart-line text-[10px]"></i>
                {{ round($application->match_score) }}% match
            </span>
        @endif
        @if($application->updated_at && $application->updated_at != $application->created_at)
            <span class="flex items-center gap-1">
                <i class="fas fa-sync text-[10px]"></i>
                Updated {{ $application->updated_at->diffForHumans() }}
            </span>
        @endif
    </div>

    {{-- Action Buttons --}}
    <div class="flex gap-2">
        <a href="{{ route('applications.show', $application) }}" 
           class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-xl font-medium text-sm text-center transition-colors active:scale-95">
            <i class="fas fa-eye mr-2"></i>
            View Details
        </a>
        
        @if($status === 'pending' || $status === 'under_review')
            <button onclick="withdrawApplication({{ $application->id }})"
                    class="px-4 py-2 bg-red-50 hover:bg-red-100 text-red-600 rounded-xl font-medium text-sm transition-colors active:scale-95">
                <i class="fas fa-times"></i>
            </button>
        @endif
    </div>

    {{-- Next Steps (if applicable) --}}
    @if($application->getNextSteps())
        <div class="mt-3 p-3 bg-blue-50 rounded-xl">
            <p class="text-xs text-blue-700 font-medium mb-1">
                <i class="fas fa-info-circle"></i> Next Steps
            </p>
            <p class="text-xs text-blue-600">
                {{ $application->getNextSteps() }}
            </p>
        </div>
    @endif
</div>

<script>
function withdrawApplication(applicationId) {
    if (confirm('Are you sure you want to withdraw this application?')) {
        // Submit withdrawal form
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/applications/${applicationId}/withdraw`;
        
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = csrfToken;
        
        const methodInput = document.createElement('input');
        methodInput.type = 'hidden';
        methodInput.name = '_method';
        methodInput.value = 'DELETE';
        
        form.appendChild(csrfInput);
        form.appendChild(methodInput);
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

{{-- Primary color tokens are defined globally in layouts/app-mobile.blade.php --}}
