{{-- Mobile Bottom Navigation --}}
{{-- Height: 64px + safe area. Chatbot sits above this via bottom: calc(72px + safe-area). --}}
<nav id="bottom-nav"
     class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 z-40 md:hidden"
     style="padding-bottom: env(safe-area-inset-bottom, 0px);">
    <div class="flex items-stretch h-16">

        {{-- Home --}}
        @php $isHome = request()->routeIs('dashboard') || request()->routeIs('dashboard.mobile'); @endphp
        <a href="{{ route('dashboard') }}"
           id="nav-home"
           class="flex flex-col items-center justify-center flex-1 gap-0.5 min-h-[44px] transition-all duration-200 relative touch-action-manipulation
                  {{ $isHome ? 'text-primary-600' : 'text-gray-400 hover:text-gray-600' }}"
           aria-label="Home" aria-current="{{ $isHome ? 'page' : 'false' }}">
            @if($isHome)
                <span class="absolute top-0 left-1/2 -translate-x-1/2 w-8 h-0.5 bg-primary-600 rounded-b-full"></span>
            @endif
            <i class="fas fa-home text-lg {{ $isHome ? '' : '' }} transition-transform duration-200"></i>
            <span class="text-[10px] font-medium leading-none">Home</span>
        </a>

        {{-- Jobs --}}
        @php $isJobs = request()->routeIs('recommendations.*'); @endphp
        <a href="{{ route('recommendations.index') }}"
           id="nav-jobs"
           class="flex flex-col items-center justify-center flex-1 gap-0.5 min-h-[44px] transition-all duration-200 relative touch-action-manipulation
                  {{ $isJobs ? 'text-primary-600' : 'text-gray-400 hover:text-gray-600' }}"
           aria-label="Jobs" aria-current="{{ $isJobs ? 'page' : 'false' }}">
            @if($isJobs)
                <span class="absolute top-0 left-1/2 -translate-x-1/2 w-8 h-0.5 bg-primary-600 rounded-b-full"></span>
            @endif
            <i class="fas fa-briefcase text-lg transition-transform duration-200"></i>
            <span class="text-[10px] font-medium leading-none">Jobs</span>
        </a>

        {{-- Applications --}}
        @php $isApps = request()->routeIs('my-applications') || request()->routeIs('my-applications.*') || request()->routeIs('applications.*'); @endphp
        <a href="{{ route('my-applications') }}"
           id="nav-applications"
           class="flex flex-col items-center justify-center flex-1 gap-0.5 min-h-[44px] transition-all duration-200 relative touch-action-manipulation
                  {{ $isApps ? 'text-primary-600' : 'text-gray-400 hover:text-gray-600' }}"
           aria-label="My Applications" aria-current="{{ $isApps ? 'page' : 'false' }}">
            @if($isApps)
                <span class="absolute top-0 left-1/2 -translate-x-1/2 w-8 h-0.5 bg-primary-600 rounded-b-full"></span>
            @endif
            <i class="fas fa-clipboard-list text-lg transition-transform duration-200"></i>
            <span class="text-[10px] font-medium leading-none">Applied</span>
        </a>

        {{-- Profile --}}
        @php $isProfile = request()->routeIs('profile.*'); @endphp
        <a href="{{ route('profile.show') }}"
           id="nav-profile"
           class="flex flex-col items-center justify-center flex-1 gap-0.5 min-h-[44px] transition-all duration-200 relative touch-action-manipulation
                  {{ $isProfile ? 'text-primary-600' : 'text-gray-400 hover:text-gray-600' }}"
           aria-label="Profile" aria-current="{{ $isProfile ? 'page' : 'false' }}">
            @if($isProfile)
                <span class="absolute top-0 left-1/2 -translate-x-1/2 w-8 h-0.5 bg-primary-600 rounded-b-full"></span>
            @endif
            <i class="fas fa-user-circle text-lg transition-transform duration-200"></i>
            <span class="text-[10px] font-medium leading-none">Profile</span>
        </a>

    </div>
</nav>

<style>
/* Active icon scale */
#bottom-nav a[aria-current="page"] i {
    transform: scale(1.15);
}

/* Touch feedback */
#bottom-nav a:active {
    transform: scale(0.93);
    transition: transform 0.08s ease;
}
</style>
