{{-- Mobile-First Internship Card Component --}}
@props(['internship', 'matchScore' => null, 'matchingSkills' => [], 'missingSkills' => []])

<div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-4 hover:shadow-md transition-all group">
    {{-- Header: Company Logo + Title + Match Score --}}
    <div class="flex items-start gap-3 mb-3">
        <div class="w-12 h-12 bg-gradient-to-br from-gray-100 to-gray-200 rounded-xl flex items-center justify-center flex-shrink-0">
            <span class="text-gray-600 font-bold text-sm">
                {{ strtoupper(substr($internship->organization, 0, 2)) }}
            </span>
        </div>
        <div class="flex-1 min-w-0">
            <h3 class="font-semibold text-base text-gray-900 line-clamp-2 mb-1">
                {{ $internship->title }}
            </h3>
            <p class="text-sm text-gray-600 truncate">{{ $internship->organization }}</p>
        </div>
        @if($matchScore)
            <div class="flex-shrink-0">
                <div class="px-2.5 py-1 rounded-full text-xs font-bold
                    {{ $matchScore >= 80 ? 'bg-green-100 text-green-700' : ($matchScore >= 60 ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-700') }}">
                    {{ $matchScore }}%
                </div>
            </div>
        @endif
    </div>

    {{-- Details: Location, Duration, Posted Date --}}
    <div class="flex flex-wrap gap-3 text-xs text-gray-600 mb-3">
        <span class="flex items-center gap-1">
            <i class="fas fa-map-marker-alt text-[10px]"></i>
            {{ $internship->location ?? 'Remote' }}
        </span>
        <span class="flex items-center gap-1">
            <i class="fas fa-clock text-[10px]"></i>
            {{ $internship->duration ?? '3 months' }}
        </span>
        <span class="flex items-center gap-1">
            <i class="fas fa-calendar text-[10px]"></i>
            {{ $internship->created_at ? $internship->created_at->diffForHumans() : 'Recently' }}
        </span>
    </div>

    {{-- Skills --}}
    @if(isset($internship->required_skills) && count($internship->required_skills) > 0)
        <div class="flex flex-wrap gap-2 mb-4">
            @foreach(array_slice($internship->required_skills, 0, 3) as $skill)
                <span class="inline-flex items-center px-2 py-1 bg-gray-100 text-gray-700 text-xs font-medium rounded-lg">
                    {{ $skill }}
                </span>
            @endforeach
            @if(count($internship->required_skills) > 3)
                <span class="inline-flex items-center px-2 py-1 bg-gray-100 text-gray-500 text-xs font-medium rounded-lg">
                    +{{ count($internship->required_skills) - 3 }}
                </span>
            @endif
        </div>
    @endif

    {{-- Matching Skills (if provided) --}}
    @if(count($matchingSkills) > 0)
        <div class="mb-3 p-2 bg-green-50 rounded-lg">
            <p class="text-xs text-green-700 font-medium mb-1">
                <i class="fas fa-check-circle text-[10px]"></i> Your matching skills
            </p>
            <div class="flex flex-wrap gap-1">
                @foreach(array_slice($matchingSkills, 0, 3) as $skill)
                    <span class="text-xs text-green-600">{{ $skill }}</span>
                    @if(!$loop->last)<span class="text-green-400">•</span>@endif
                @endforeach
            </div>
        </div>
    @endif

    {{-- Action Button --}}
    <div class="flex gap-2">
        @auth
            @if(auth()->user()->role === 'student')
                @php
                    $hasApplied = \App\Models\Application::where('user_id', auth()->id())
                        ->where('internship_id', $internship->id)
                        ->exists();
                @endphp
                
                @if($hasApplied)
                    <button disabled class="flex-1 bg-gray-100 text-gray-500 px-4 py-2.5 rounded-xl font-medium text-sm cursor-not-allowed">
                        <i class="fas fa-check-circle mr-2"></i>
                        Applied
                    </button>
                @else
                    <a href="{{ route('applications.apply.form', $internship) }}" class="flex-1 bg-primary-600 hover:bg-primary-700 text-white px-4 py-2.5 rounded-xl font-medium text-sm text-center transition-colors active:scale-95 inline-block">
                        <i class="fas fa-paper-plane mr-2"></i>
                        Apply Now
                    </a>
                @endif
            @endif
        @else
            <a href="{{ route('login') }}" class="flex-1 bg-primary-600 hover:bg-primary-700 text-white px-4 py-2.5 rounded-xl font-medium text-sm text-center transition-colors">
                <i class="fas fa-sign-in-alt mr-2"></i>
                Login to Apply
            </a>
        @endauth
        
        <button class="w-10 h-10 bg-gray-100 hover:bg-gray-200 rounded-xl flex items-center justify-center transition-colors active:scale-95" 
                onclick="toggleSave(this)" 
                aria-label="Save internship">
            <i class="far fa-heart text-gray-600"></i>
        </button>
    </div>
</div>

<script>
function toggleSave(button) {
    const icon = button.querySelector('i');
    if (icon.classList.contains('far')) {
        icon.classList.remove('far');
        icon.classList.add('fas', 'text-red-500');
        button.classList.add('bg-red-50');
        button.classList.remove('bg-gray-100');
    } else {
        icon.classList.remove('fas', 'text-red-500');
        icon.classList.add('far');
        button.classList.remove('bg-red-50');
        button.classList.add('bg-gray-100');
    }
}
</script>

<style>
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
