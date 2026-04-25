@extends('layouts.app-mobile')

@section('content')
<div class="px-4 py-5 max-w-lg mx-auto space-y-4" style="padding-bottom: 7rem;">

    {{-- Profile Header Card --}}
    <div class="card card-lg">
        <div class="flex items-center gap-4 mb-4">
            {{-- Avatar / Photo --}}
            <div class="w-20 h-20 rounded-full flex-shrink-0 shadow-lg overflow-hidden"
                 style="background:linear-gradient(135deg,#6366f1,#4f46e5);">
                @if($profile && $profile->getPhotoUrl())
                    <img src="{{ $profile->getPhotoUrl() }}" alt="Profile Photo"
                         style="width:100%;height:100%;object-fit:cover;">
                @else
                    <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;">
                        <span class="text-white font-bold text-3xl leading-none">
                            {{ strtoupper(substr($profile->name ?? auth()->user()->name, 0, 1)) }}
                        </span>
                    </div>
                @endif
            </div>
            {{-- Info --}}
            <div class="flex-1 min-w-0">
                <h1 class="text-xl font-bold text-gray-900 truncate">
                    {{ $profile->name ?? auth()->user()->name }}
                </h1>
                <p class="text-sm text-gray-500 truncate">{{ auth()->user()->email }}</p>
                @if($profile && $profile->location)
                    <p class="text-xs text-indigo-600 font-medium mt-0.5">
                        <i class="fas fa-map-marker-alt mr-1"></i>{{ $profile->location }}
                    </p>
                @endif

                {{-- Completion bar --}}
                @php
                    $fields = ['name', 'academic_background', 'skills', 'career_interests', 'resume_path', 'aadhaar_number', 'profile_photo', 'location'];
                    $completed = 0;
                    if ($profile) {
                        foreach ($fields as $field) {
                            if (!empty($profile->$field)) $completed++;
                        }
                    }
                    $completion = $profile ? round(($completed / count($fields)) * 100) : 0;
                @endphp
                <div class="mt-2 flex items-center gap-2">
                    <div class="flex-1 h-2 bg-gray-200 rounded-full overflow-hidden">
                        <div class="h-full rounded-full transition-all duration-700
                                    {{ $completion === 100 ? 'bg-green-500' : 'bg-primary-600' }}"
                             style="width: {{ $completion }}%"></div>
                    </div>
                    <span class="text-xs font-bold {{ $completion === 100 ? 'text-green-600' : 'text-primary-600' }} flex-shrink-0">
                        {{ $completion }}%
                    </span>
                </div>
            </div>
        </div>

        <a href="{{ route('profile.edit.mobile') }}" class="btn btn-primary w-full">
            <i class="fas fa-edit mr-2 text-xs"></i>
            Edit Profile
        </a>
    </div>

    {{-- Academic Background --}}
    @if($profile && $profile->academic_background)
        <div class="card">
            <div class="flex items-center gap-2 mb-3">
                <div class="w-8 h-8 bg-blue-100 rounded-xl flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-graduation-cap text-blue-600 text-sm"></i>
                </div>
                <h2 class="text-base font-bold text-gray-900">Academic Background</h2>
            </div>
            <p class="text-sm text-gray-700 leading-relaxed">{{ $profile->academic_background }}</p>
        </div>
    @endif

    {{-- Skills —  show max 8, "Show more" expander --}}
    @if($profile && $profile->skills)
        @php
            $skills = is_array($profile->skills)
                ? $profile->skills
                : array_map('trim', explode(',', $profile->skills));
            $skills = array_filter($skills); // remove empty
        @endphp
        @if(count($skills) > 0)
            <div class="card">
                <div class="flex items-center gap-2 mb-3">
                    <div class="w-8 h-8 bg-green-100 rounded-xl flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-code text-green-600 text-sm"></i>
                    </div>
                    <h2 class="text-base font-bold text-gray-900">Skills</h2>
                    <span class="ml-auto text-xs text-gray-400">{{ count($skills) }} total</span>
                </div>

                <div class="flex flex-wrap gap-2" id="skillsContainer">
                    @foreach($skills as $i => $skill)
                        <span class="inline-flex items-center px-3 py-1.5 bg-gray-100 text-gray-700 text-xs font-medium rounded-lg
                                     {{ $i >= 8 ? 'skill-extra hidden' : '' }}">
                            {{ trim($skill) }}
                        </span>
                    @endforeach
                </div>

                @if(count($skills) > 8)
                    <button onclick="toggleSkills(this)"
                            class="mt-3 text-xs font-semibold text-primary-600 hover:text-primary-700 transition-colors"
                            data-show-more="true">
                        <i class="fas fa-chevron-down mr-1 text-[9px]"></i>
                        Show {{ count($skills) - 8 }} more skills
                    </button>
                @endif
            </div>
        @endif
    @endif

    {{-- Career Interests --}}
    @if($profile && $profile->career_interests)
        <div class="card">
            <div class="flex items-center gap-2 mb-3">
                <div class="w-8 h-8 bg-purple-100 rounded-xl flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-briefcase text-purple-600 text-sm"></i>
                </div>
                <h2 class="text-base font-bold text-gray-900">Career Interests</h2>
            </div>
            @php
                $interestsText = is_array($profile->career_interests)
                    ? implode(', ', array_filter($profile->career_interests))
                    : ($profile->career_interests ?? '');
            @endphp
            <p class="text-sm text-gray-700 leading-relaxed">{{ $interestsText }}</p>
        </div>
    @endif

    {{-- Resume --}}
    @if($profile && $profile->resume_path)
        <div class="card">
            <div class="flex items-center gap-2 mb-3">
                <div class="w-8 h-8 bg-red-100 rounded-xl flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-file-pdf text-red-600 text-sm"></i>
                </div>
                <h2 class="text-base font-bold text-gray-900">Resume</h2>
            </div>
            <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl">
                <div class="w-10 h-10 bg-red-100 rounded-xl flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-file-pdf text-red-600"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-gray-900 truncate">
                        {{ basename($profile->resume_path) }}
                    </p>
                    <p class="text-xs text-gray-500">
                        Updated {{ $profile->updated_at->diffForHumans() }}
                    </p>
                </div>
                <a href="{{ $profile->getResumeUrl() }}"
                   target="_blank"
                   class="btn btn-sm btn-primary flex-shrink-0"
                   rel="noopener noreferrer"
                   aria-label="Download resume">
                    <i class="fas fa-download"></i>
                </a>
            </div>
        </div>
    @endif

    {{-- Aadhaar (Masked) --}}
    @if($profile && $profile->aadhaar_number)
        <div class="card">
            <div class="flex items-center gap-2 mb-3">
                <div class="w-8 h-8 bg-yellow-100 rounded-xl flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-id-card text-yellow-600 text-sm"></i>
                </div>
                <h2 class="text-base font-bold text-gray-900">Aadhaar</h2>
                <span class="ml-auto">
                    <span class="badge bg-green-100 text-green-700">
                        <i class="fas fa-shield-alt text-[9px]"></i> Verified
                    </span>
                </span>
            </div>
            <p class="text-sm text-gray-700 font-mono tracking-widest">
                {{ substr($profile->aadhaar_number, 0, 4) }} ✦✦✦✦ ✦✦✦✦ {{ substr($profile->aadhaar_number, -4) }}
            </p>
        </div>
    @endif

    {{-- Empty State --}}
    @if(!$profile || (!$profile->academic_background && !$profile->skills && !$profile->career_interests && !$profile->resume_path))
        <div class="card p-8 text-center">
            <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-user-edit text-3xl text-gray-400"></i>
            </div>
            <h3 class="text-base font-bold text-gray-900 mb-2">Complete Your Profile</h3>
            <p class="text-sm text-gray-500 mb-5">
                Add your details to get better internship recommendations
            </p>
            <a href="{{ route('profile.edit.mobile') }}" class="btn btn-primary inline-flex">
                <i class="fas fa-plus mr-2 text-xs"></i>
                Add Information
            </a>
        </div>
    @endif

    {{-- Account Section --}}
    <div class="card" style="margin-top: 0.25rem;">
        <div class="flex items-center gap-2 mb-3">
            <div class="w-8 h-8 bg-gray-100 rounded-xl flex items-center justify-center flex-shrink-0">
                <i class="fas fa-cog text-gray-600 text-sm"></i>
            </div>
            <h2 class="text-base font-bold text-gray-900">Account</h2>
        </div>

        <div class="space-y-1">
            {{-- Account info row --}}
            <div class="flex items-center gap-3 py-2.5 px-1">
                <div class="w-8 h-8 bg-indigo-50 rounded-lg flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-envelope text-indigo-500 text-xs"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-xs text-gray-400 leading-none mb-0.5">Email</p>
                    <p class="text-sm font-medium text-gray-800 truncate">{{ auth()->user()->email }}</p>
                </div>
            </div>

            <div class="border-t border-gray-100"></div>

            {{-- Member since --}}
            <div class="flex items-center gap-3 py-2.5 px-1">
                <div class="w-8 h-8 bg-green-50 rounded-lg flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-calendar-alt text-green-500 text-xs"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-xs text-gray-400 leading-none mb-0.5">Member since</p>
                    <p class="text-sm font-medium text-gray-800">{{ auth()->user()->created_at->format('M Y') }}</p>
                </div>
            </div>

            <div class="border-t border-gray-100"></div>

            {{-- Change Password --}}
            <a href="{{ route('profile.edit.mobile') }}"
               class="flex items-center gap-3 py-2.5 px-1 hover:bg-gray-50 rounded-lg transition-colors">
                <div class="w-8 h-8 bg-yellow-50 rounded-lg flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-key text-yellow-500 text-xs"></i>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-800">Edit Profile</p>
                </div>
                <i class="fas fa-chevron-right text-gray-300 text-xs"></i>
            </a>
        </div>
    </div>

    {{-- Logout Button --}}
    <form method="POST" action="{{ route('logout') }}" class="mt-1">
        @csrf
        <button type="submit"
                class="w-full flex items-center justify-center gap-2.5 py-3.5 px-4 rounded-2xl font-semibold text-sm transition-all active:scale-[0.97]"
                style="background:#fff1f2;color:#dc2626;border:1.5px solid #fecaca;">
            <i class="fas fa-sign-out-alt text-base"></i>
            Log Out
        </button>
    </form>

    {{-- App Version --}}
    <p class="text-center text-xs text-gray-300 pb-2">InternshipHub · v1.0</p>

</div>

@push('scripts')
<script>
function toggleSkills(btn) {
    const extras = document.querySelectorAll('.skill-extra');
    const isShowMore = btn.dataset.showMore === 'true';

    extras.forEach(el => el.classList.toggle('hidden', !isShowMore));
    btn.dataset.showMore = isShowMore ? 'false' : 'true';

    const hiddenCount = document.querySelectorAll('.skill-extra').length;
    if (isShowMore) {
        btn.innerHTML = '<i class="fas fa-chevron-up mr-1 text-[9px]"></i>Show less';
    } else {
        btn.innerHTML = `<i class="fas fa-chevron-down mr-1 text-[9px]"></i>Show ${hiddenCount} more skills`;
    }
}
</script>
@endpush
@endsection
