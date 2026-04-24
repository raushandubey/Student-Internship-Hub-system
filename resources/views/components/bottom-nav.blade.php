{{-- Mobile Bottom Navigation --}}
<nav class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 z-50 md:hidden" style="padding-bottom: env(safe-area-inset-bottom);">
    <div class="flex justify-around items-center h-16 px-2">
        {{-- Home --}}
        <a href="{{ route('dashboard') }}" 
           class="flex flex-col items-center justify-center flex-1 py-2 min-w-[60px] min-h-[44px] transition-all {{ request()->routeIs('dashboard') ? 'text-primary-600 font-semibold' : 'text-gray-500 hover:text-primary-600' }}">
            <i class="fas fa-home text-xl mb-1 {{ request()->routeIs('dashboard') ? 'scale-110' : '' }} transition-transform"></i>
            <span class="text-xs">Home</span>
        </a>

        {{-- Applications --}}
        <a href="{{ route('my-applications') }}" 
           class="flex flex-col items-center justify-center flex-1 py-2 min-w-[60px] min-h-[44px] transition-all {{ request()->routeIs('my-applications') ? 'text-primary-600 font-semibold' : 'text-gray-500 hover:text-primary-600' }}">
            <i class="fas fa-clipboard-list text-xl mb-1 {{ request()->routeIs('my-applications') ? 'scale-110' : '' }} transition-transform"></i>
            <span class="text-xs">Applications</span>
        </a>

        {{-- Recommendations --}}
        <a href="{{ route('recommendations.index') }}" 
           class="flex flex-col items-center justify-center flex-1 py-2 min-w-[60px] min-h-[44px] transition-all {{ request()->routeIs('recommendations.*') ? 'text-primary-600 font-semibold' : 'text-gray-500 hover:text-primary-600' }}">
            <i class="fas fa-star text-xl mb-1 {{ request()->routeIs('recommendations.*') ? 'scale-110' : '' }} transition-transform"></i>
            <span class="text-xs">Jobs</span>
        </a>

        {{-- Profile --}}
        <a href="{{ route('profile.show') }}" 
           class="flex flex-col items-center justify-center flex-1 py-2 min-w-[60px] min-h-[44px] transition-all {{ request()->routeIs('profile.*') ? 'text-primary-600 font-semibold' : 'text-gray-500 hover:text-primary-600' }}">
            <i class="fas fa-user text-xl mb-1 {{ request()->routeIs('profile.*') ? 'scale-110' : '' }} transition-transform"></i>
            <span class="text-xs">Profile</span>
        </a>
    </div>
</nav>

<style>
/* Tailwind custom colors */
.text-primary-600 {
    color: #5a67d8;
}

.hover\:text-primary-600:hover {
    color: #5a67d8;
}

/* Active state animation */
@keyframes bounce-subtle {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}

/* Touch feedback */
nav a:active {
    transform: scale(0.95);
}
</style>
