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
            <form action="{{ route('applications.apply', $internship) }}" method="POST" class="w-full">
                @csrf
                <button type="submit" 
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-semibold transition duration-200 shadow-lg hover:shadow-xl">
                    Apply Now
                </button>
            </form>
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
