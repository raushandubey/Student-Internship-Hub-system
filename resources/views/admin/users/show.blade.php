@extends('admin.layout')

@section('title', 'Student Details')

@section('content')
<div class="bg-white rounded-lg shadow p-6 max-w-3xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold">Student Details</h2>
        <a href="{{ route('admin.users.index') }}" class="text-blue-600 hover:underline">← Back to List</a>
    </div>

    <div class="space-y-4">
        <div class="border-b pb-4">
            <h3 class="text-lg font-semibold mb-2">Basic Information</h3>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-gray-600">Name:</p>
                    <p class="font-semibold">{{ $user->name }}</p>
                </div>
                <div>
                    <p class="text-gray-600">Email:</p>
                    <p class="font-semibold">{{ $user->email }}</p>
                </div>
                <div>
                    <p class="text-gray-600">Registered:</p>
                    <p class="font-semibold">{{ $user->created_at->format('d M Y') }}</p>
                </div>
                <div>
                    <p class="text-gray-600">Email Verified:</p>
                    <p class="font-semibold">{{ $user->email_verified_at ? 'Yes' : 'No' }}</p>
                </div>
            </div>
        </div>

        @if($user->profile)
        <div class="border-b pb-4">
            <h3 class="text-lg font-semibold mb-2">Profile Information</h3>
            <div class="space-y-3">
                @if($user->profile->name)
                <div>
                    <p class="text-gray-600">Full Name:</p>
                    <p class="font-semibold">{{ $user->profile->name }}</p>
                </div>
                @endif

                @if($user->profile->academic_background)
                <div>
                    <p class="text-gray-600">Academic Background:</p>
                    <p class="font-semibold">{{ $user->profile->academic_background }}</p>
                </div>
                @endif

                @if($user->profile->skills)
                <div>
                    <p class="text-gray-600">Skills:</p>
                    <div class="flex flex-wrap gap-2 mt-1">
                        @foreach(is_array($user->profile->skills) ? $user->profile->skills : explode(',', $user->profile->skills) as $skill)
                            <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded text-sm">{{ trim($skill) }}</span>
                        @endforeach
                    </div>
                </div>
                @endif

                @if($user->profile->career_interests)
                <div>
                    <p class="text-gray-600">Career Interests:</p>
                    <p class="font-semibold">{{ $user->profile->career_interests }}</p>
                </div>
                @endif

                @if($user->profile->resume_path)
                <div>
                    <p class="text-gray-600">Resume:</p>
                    <a href="{{ Storage::url($user->profile->resume_path) }}" target="_blank" 
                        class="text-blue-600 hover:underline">View Resume</a>
                </div>
                @endif

                @if($user->profile->aadhaar_number)
                <div>
                    <p class="text-gray-600">Aadhaar Number:</p>
                    <p class="font-semibold">{{ $user->profile->aadhaar_number }}</p>
                </div>
                @endif
            </div>
        </div>
        @else
        <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded">
            This student has not completed their profile yet.
        </div>
        @endif
    </div>
</div>
@endsection
