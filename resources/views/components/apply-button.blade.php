@props(['internship'])

@php
    $hasApplied = false;
    if (auth()->check()) {
        $hasApplied = \App\Models\Application::where('user_id', auth()->id())
            ->where('internship_id', $internship->id)
            ->exists();
    }
@endphp

@auth
    @if(auth()->user()->role === 'student')
        @if($hasApplied)
            <button disabled 
                    class="w-full bg-gray-400 text-white px-6 py-3 rounded-lg font-semibold cursor-not-allowed opacity-60">
                Already Applied
            </button>
        @else
            <a href="{{ route('applications.apply.form', $internship) }}" 
               class="block w-full bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-semibold text-center transition duration-200 shadow-lg hover:shadow-xl">
                Apply Now
            </a>
        @endif
    @else
        <button disabled 
                class="w-full bg-gray-400 text-white px-6 py-3 rounded-lg font-semibold cursor-not-allowed opacity-60">
            Admin Account
        </button>
    @endif
@else
    <a href="{{ route('login') }}" 
       class="block w-full bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-semibold text-center transition duration-200 shadow-lg hover:shadow-xl">
        Login to Apply
    </a>
@endauth
