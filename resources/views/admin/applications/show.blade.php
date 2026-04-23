@extends('admin.layout')

@section('title', 'Application Details')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex justify-between items-start">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">Application Details</h2>
                <p class="text-gray-500 mt-1">
                    {{ $application->user->name ?? 'Unknown' }} &rarr; {{ $application->internship->title ?? 'Unknown' }}
                </p>
            </div>
            <a href="{{ route('admin.applications.index') }}" class="text-gray-500 hover:text-gray-700 px-3 py-2">← Back</a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Applicant Info -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Applicant</h3>
                <div class="flex items-center gap-4">
                    <div class="h-12 w-12 bg-gradient-to-br from-blue-500 to-purple-500 rounded-full flex items-center justify-center text-white font-bold text-lg flex-shrink-0">
                        {{ strtoupper(substr($application->user->name ?? 'U', 0, 1)) }}
                    </div>
                    <div>
                        <p class="font-semibold text-gray-900">{{ $application->user->name ?? '—' }}</p>
                        <p class="text-sm text-gray-500">{{ $application->user->email ?? '—' }}</p>
                    </div>
                </div>
            </div>

            <!-- Internship Info -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Internship</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Title</p>
                        <p class="text-gray-800 mt-1 font-medium">{{ $application->internship->title ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Organization</p>
                        <p class="text-gray-800 mt-1">{{ $application->internship->organization ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Location</p>
                        <p class="text-gray-800 mt-1">{{ $application->internship->location ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Source</p>
                        @if($application->internship && $application->internship->recruiter_id)
                            <span class="mt-1 inline-block px-2 py-1 text-xs rounded-full bg-purple-100 text-purple-800 border border-purple-300 font-medium">
                                Recruiter-Posted
                            </span>
                        @else
                            <span class="mt-1 inline-block px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800 border border-blue-300 font-medium">
                                Admin-Posted
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Recruiter Context (only for recruiter-posted internships) -->
            @if($application->internship && $application->internship->recruiter_id)
                <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-6">
                    <h3 class="text-lg font-bold text-indigo-800 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                        </svg>
                        Recruiter Information
                    </h3>
                    @if($application->internship->recruiter)
                        @php $recruiter = $application->internship->recruiter; @endphp
                        <div class="flex items-center gap-4">
                            <div class="h-12 w-12 bg-gradient-to-br from-indigo-500 to-purple-500 rounded-full flex items-center justify-center text-white font-bold text-lg flex-shrink-0">
                                {{ strtoupper(substr($recruiter->name, 0, 1)) }}
                            </div>
                            <div class="flex-1">
                                <p class="font-semibold text-gray-900">{{ $recruiter->name }}</p>
                                <p class="text-sm text-gray-600">{{ $recruiter->email }}</p>
                                @if($recruiter->recruiterProfile)
                                    <p class="text-sm text-indigo-700 font-medium mt-0.5">{{ $recruiter->recruiterProfile->organization }}</p>
                                @endif
                            </div>
                            <a href="{{ route('admin.recruiters.show', $recruiter) }}"
                                class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition flex-shrink-0">
                                View Recruiter
                            </a>
                        </div>
                    @else
                        <p class="text-indigo-700 text-sm">Recruiter information not available.</p>
                    @endif
                </div>
            @endif

            <!-- Application History -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Status History</h3>
                @if(isset($statusHistory) && $statusHistory->count() > 0)
                    <div class="space-y-3">
                        @foreach($statusHistory as $log)
                        <div class="flex items-start gap-3 p-3 bg-gray-50 rounded-lg">
                            <div class="flex-shrink-0 w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-gray-800">
                                        {{ $log->from_status ? ucfirst(str_replace('_', ' ', $log->from_status)) . ' → ' : '' }}{{ ucfirst(str_replace('_', ' ', $log->to_status)) }}
                                    </span>
                                    <span class="text-xs text-gray-400">{{ \Carbon\Carbon::parse($log->created_at)->format('M d, Y H:i') }}</span>
                                </div>
                                <p class="text-xs text-gray-500 mt-0.5">By {{ ucfirst($log->actor_type ?? 'system') }}</p>
                                @if($log->notes)
                                    <p class="text-xs text-gray-600 mt-1">{{ $log->notes }}</p>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-500 text-sm">No status history available.</p>
                @endif
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Current Status -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Current Status</h3>
                @php
                    $statusValue = $application->status->value ?? $application->status;
                    $badgeColors = [
                        'pending'              => 'bg-yellow-100 text-yellow-800 border-yellow-300',
                        'under_review'         => 'bg-blue-100 text-blue-800 border-blue-300',
                        'shortlisted'          => 'bg-purple-100 text-purple-800 border-purple-300',
                        'interview_scheduled'  => 'bg-indigo-100 text-indigo-800 border-indigo-300',
                        'approved'             => 'bg-green-100 text-green-800 border-green-300',
                        'rejected'             => 'bg-red-100 text-red-800 border-red-300',
                    ];
                @endphp
                <span class="px-3 py-1.5 inline-flex text-sm font-semibold rounded-full border {{ $badgeColors[$statusValue] ?? 'bg-gray-100 text-gray-700' }}">
                    {{ ucfirst(str_replace('_', ' ', $statusValue)) }}
                </span>

                <div class="mt-4 space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Applied</span>
                        <span class="text-gray-800">{{ $application->created_at->format('M d, Y') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Last Updated</span>
                        <span class="text-gray-800">{{ $application->updated_at->format('M d, Y') }}</span>
                    </div>
                </div>
            </div>

            <!-- Update Status -->
            @php
                $isTerminal = method_exists($application->status, 'isTerminal') ? $application->status->isTerminal() : false;
                $allowedTransitions = method_exists($application->status, 'allowedTransitions') ? $application->status->allowedTransitions() : [];
            @endphp
            @if(!$isTerminal && count($allowedTransitions) > 0)
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Update Status</h3>
                <form action="{{ route('admin.applications.update-status', $application) }}" method="POST">
                    @csrf
                    <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 mb-3">
                        <option value="">Move to...</option>
                        @foreach($allowedTransitions as $transition)
                            <option value="{{ $transition->value }}">{{ $transition->label() }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
                        Update
                    </button>
                </form>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
