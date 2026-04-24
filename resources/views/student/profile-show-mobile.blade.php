@extends('layouts.app-mobile')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-6">
    
    {{-- Profile Header --}}
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-200 mb-4">
        <div class="flex items-center gap-4 mb-4">
            <div class="w-20 h-20 bg-gradient-to-br from-primary-500 to-primary-600 rounded-full flex items-center justify-center flex-shrink-0 shadow-lg">
                <span class="text-white font-bold text-3xl">
                    {{ strtoupper(substr($profile->name ?? auth()->user()->name, 0, 1)) }}
                </span>
            </div>
            <div class="flex-1 min-w-0">
                <h1 class="text-xl font-bold text-gray-900 truncate">
                    {{ $profile->name ?? auth()->user()->name }}
                </h1>
                <p class="text-sm text-gray-600">{{ auth()->user()->email }}</p>
                <div class="mt-2">
                    @php
                        $fields = ['name', 'academic_background', 'skills', 'career_interests', 'resume_path', 'aadhaar_number'];
                        $completed = 0;
                        if ($profile) {
                            foreach ($fields as $field) {
                                if (!empty($profile->$field)) $completed++;
                            }
                        }
                        $completion = round(($completed / count($fields)) * 100);
                    @endphp
                    <div class="flex items-center gap-2">
                        <div class="flex-1 h-2 bg-gray-200 rounded-full overflow-hidden">
                            <div class="h-full bg-primary-600 transition-all duration-500" style="width: {{ $completion }}%"></div>
                        </div>
                        <span class="text-xs font-medium text-gray-700">{{ $completion }}%</span>
                    </div>
                </div>
            </div>
        </div>
        
        <a href="{{ route('profile.edit.mobile') }}" class="btn btn-primary w-full">
            <i class="fas fa-edit mr-2"></i>
            Edit Profile
        </a>
    </div>

    {{-- Academic Background --}}
    @if($profile && $profile->academic_background)
        <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-200 mb-4">
            <div class="flex items-center gap-2 mb-3">
                <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-graduation-cap text-blue-600"></i>
                </div>
                <h2 class="text-lg font-semibold text-gray-900">Academic Background</h2>
            </div>
            <p class="text-sm text-gray-700 leading-relaxed">{{ $profile->academic_background }}</p>
        </div>
    @endif

    {{-- Skills --}}
    @if($profile && $profile->skills)
        <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-200 mb-4">
            <div class="flex items-center gap-2 mb-3">
                <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-code text-green-600"></i>
                </div>
                <h2 class="text-lg font-semibold text-gray-900">Skills</h2>
            </div>
            <div class="flex flex-wrap gap-2">
                @php
                    $skills = is_array($profile->skills) ? $profile->skills : explode(',', $profile->skills);
                @endphp
                @foreach($skills as $skill)
                    <span class="inline-flex items-center px-3 py-1.5 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg">
                        {{ trim($skill) }}
                    </span>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Career Interests --}}
    @if($profile && $profile->career_interests)
        <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-200 mb-4">
            <div class="flex items-center gap-2 mb-3">
                <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-briefcase text-purple-600"></i>
                </div>
                <h2 class="text-lg font-semibold text-gray-900">Career Interests</h2>
            </div>
            <p class="text-sm text-gray-700 leading-relaxed">{{ $profile->career_interests }}</p>
        </div>
    @endif

    {{-- Resume --}}
    @if($profile && $profile->resume_path)
        <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-200 mb-4">
            <div class="flex items-center gap-2 mb-3">
                <div class="w-8 h-8 bg-red-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-file-pdf text-red-600"></i>
                </div>
                <h2 class="text-lg font-semibold text-gray-900">Resume</h2>
            </div>
            <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl">
                <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-file-pdf text-red-600"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 truncate">{{ basename($profile->resume_path) }}</p>
                    <p class="text-xs text-gray-500">Uploaded {{ $profile->updated_at->diffForHumans() }}</p>
                </div>
                <a href="{{ $profile->getResumeUrl() }}" 
                   target="_blank" 
                   class="btn-sm btn-primary">
                    <i class="fas fa-download"></i>
                </a>
            </div>
        </div>
    @endif

    {{-- Aadhaar (Masked) --}}
    @if($profile && $profile->aadhaar_number)
        <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-200 mb-4">
            <div class="flex items-center gap-2 mb-3">
                <div class="w-8 h-8 bg-yellow-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-id-card text-yellow-600"></i>
                </div>
                <h2 class="text-lg font-semibold text-gray-900">Aadhaar Number</h2>
            </div>
            <p class="text-sm text-gray-700 font-mono">
                {{ substr($profile->aadhaar_number, 0, 4) }} **** **** {{ substr($profile->aadhaar_number, -4) }}
            </p>
        </div>
    @endif

    {{-- Empty State --}}
    @if(!$profile || (!$profile->academic_background && !$profile->skills && !$profile->career_interests && !$profile->resume_path))
        <div class="bg-white rounded-2xl p-8 text-center border border-gray-200">
            <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-user-edit text-3xl text-gray-400"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Complete Your Profile</h3>
            <p class="text-sm text-gray-600 mb-6">
                Add your details to get better job recommendations
            </p>
            <a href="{{ route('profile.edit.mobile') }}" class="inline-flex items-center bg-primary-600 text-white px-6 py-3 rounded-xl font-medium text-sm hover:bg-primary-700 transition-colors">
                <i class="fas fa-plus mr-2"></i>
                Add Information
            </a>
        </div>
    @endif

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

.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.625rem 1rem;
    font-size: 0.875rem;
    font-weight: 500;
    border-radius: 0.75rem;
    transition: all 0.2s;
}

.btn-primary {
    background-color: #5a67d8;
    color: white;
}

.btn-primary:hover {
    background-color: #4c51bf;
}

.btn-sm {
    padding: 0.375rem 0.75rem;
    font-size: 0.75rem;
}
</style>
@endsection
