@extends('admin.layout')

@section('title', 'Internship Details')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex justify-between items-start">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">{{ $internship->title }}</h2>
                <p class="text-gray-600 mt-1">{{ $internship->organization }}</p>
            </div>
            <div class="flex items-center gap-3">
                @if($internship->recruiter_id && $internship->is_active)
                    <button onclick="document.getElementById('deactivateModal').classList.remove('hidden')"
                        class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-medium transition">
                        Deactivate
                    </button>
                @endif
                <a href="{{ route('admin.internships.edit', $internship) }}"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition">
                    Edit
                </a>
                <a href="{{ route('admin.internships.index') }}" class="text-gray-500 hover:text-gray-700 px-3 py-2">← Back</a>
            </div>
        </div>
    </div>

    <!-- Deactivation Info Banner -->
    @if(!$internship->is_active && $internship->deactivation_reason)
        <div class="bg-red-50 border-l-4 border-red-500 p-5 rounded-r-lg">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-red-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div>
                    <p class="font-semibold text-red-800">This internship has been deactivated</p>
                    <p class="text-red-700 mt-1">{{ $internship->deactivation_reason }}</p>
                    @if($internship->deactivatedBy)
                        <p class="text-red-600 text-sm mt-1">
                            Deactivated by {{ $internship->deactivatedBy->name }}
                            @if($internship->deactivated_at)
                                on {{ \Carbon\Carbon::parse($internship->deactivated_at)->format('M d, Y H:i') }}
                            @endif
                        </p>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Details -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Internship Details</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Location</p>
                        <p class="text-gray-800 mt-1">{{ $internship->location }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Duration</p>
                        <p class="text-gray-800 mt-1">{{ $internship->duration }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Work Type</p>
                        <p class="text-gray-800 mt-1">{{ $internship->work_type ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Category</p>
                        <p class="text-gray-800 mt-1">{{ $internship->category ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Status</p>
                        <span class="mt-1 inline-block px-2 py-1 text-xs rounded-full font-medium {{ $internship->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $internship->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Posted</p>
                        <p class="text-gray-800 mt-1">{{ $internship->created_at->format('M d, Y') }}</p>
                    </div>
                </div>

                <div class="mt-4">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-2">Description</p>
                    <p class="text-gray-800 leading-relaxed">{{ $internship->description }}</p>
                </div>

                @if($internship->required_skills)
                <div class="mt-4">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-2">Required Skills</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach((is_array($internship->required_skills) ? $internship->required_skills : explode(',', $internship->required_skills)) as $skill)
                            <span class="px-3 py-1 bg-blue-100 text-blue-800 text-xs rounded-full">{{ trim($skill) }}</span>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            @if($internship->recruiter_id)
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Posted By (Recruiter)</h3>
                @if($internship->recruiter)
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 bg-gradient-to-br from-indigo-500 to-purple-500 rounded-full flex items-center justify-center text-white font-bold">
                            {{ strtoupper(substr($internship->recruiter->name, 0, 1)) }}
                        </div>
                        <div>
                            <a href="{{ route('admin.recruiters.show', $internship->recruiter_id) }}" class="font-medium text-blue-600 hover:underline">
                                {{ $internship->recruiter->name }}
                            </a>
                            <p class="text-xs text-gray-500">{{ $internship->recruiter->email }}</p>
                        </div>
                    </div>
                @else
                    <p class="text-gray-500 text-sm">Recruiter not found.</p>
                @endif
            </div>
            @endif

            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-3">Quick Actions</h3>
                <div class="space-y-2">
                    <a href="{{ route('admin.internships.edit', $internship) }}"
                        class="block w-full text-center bg-blue-50 hover:bg-blue-100 text-blue-700 px-4 py-2 rounded-lg text-sm font-medium transition">
                        Edit Internship
                    </a>
                    <form action="{{ route('admin.internships.toggle-status', $internship) }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full bg-yellow-50 hover:bg-yellow-100 text-yellow-700 px-4 py-2 rounded-lg text-sm font-medium transition">
                            {{ $internship->is_active ? 'Deactivate' : 'Activate' }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Deactivate Modal -->
<div id="deactivateModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-2">Deactivate Internship</h3>
        <p class="text-gray-600 text-sm mb-4">This will deactivate the internship and notify the recruiter.</p>
        <form action="{{ route('admin.internships.deactivate', $internship) }}" method="POST">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Deactivation Reason <span class="text-red-500">*</span>
                </label>
                <textarea name="deactivation_reason" rows="3" required
                    placeholder="Provide a reason for deactivating this internship..."
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500"></textarea>
            </div>
            <div class="flex gap-3 justify-end">
                <button type="button" onclick="document.getElementById('deactivateModal').classList.add('hidden')"
                    class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg">Cancel</button>
                <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-medium">Deactivate</button>
            </div>
        </form>
    </div>
</div>
@endsection
