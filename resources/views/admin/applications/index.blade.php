@extends('admin.layout')

@section('title', 'Manage Applications')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">Application Management</h2>
                <p class="text-gray-600 mt-1">Review and manage student applications through the hiring pipeline</p>
            </div>
            <a href="{{ route('admin.email-logs') }}" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                </svg>
                Email Logs
            </a>
        </div>
    </div>

    <!-- Internship Source Filter -->
    <div class="bg-white rounded-lg shadow p-4">
        <form method="GET" action="{{ route('admin.applications.index') }}" class="flex flex-wrap gap-3 items-end">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Internship Source</label>
                <select name="internship_source" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All</option>
                    <option value="admin" {{ request('internship_source') === 'admin' ? 'selected' : '' }}>Admin-Posted</option>
                    <option value="recruiter" {{ request('internship_source') === 'recruiter' ? 'selected' : '' }}>Recruiter-Posted</option>
                </select>
            </div>
            <div>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium">Filter</button>
            </div>
            @if(request('internship_source'))
            <div>
                <a href="{{ route('admin.applications.index') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium">Clear</a>
            </div>
            @endif
        </form>
    </div>

    <!-- Pipeline Stats -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
        @php
            $statusCounts = [
                'pending' => $applications->filter(fn($a) => $a->status->value === 'pending')->count(),
                'under_review' => $applications->filter(fn($a) => $a->status->value === 'under_review')->count(),
                'shortlisted' => $applications->filter(fn($a) => $a->status->value === 'shortlisted')->count(),
                'interview_scheduled' => $applications->filter(fn($a) => $a->status->value === 'interview_scheduled')->count(),
                'approved' => $applications->filter(fn($a) => $a->status->value === 'approved')->count(),
                'rejected' => $applications->filter(fn($a) => $a->status->value === 'rejected')->count(),
            ];
        @endphp
        
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded-r-lg">
            <div class="text-2xl font-bold text-yellow-700">{{ $statusCounts['pending'] }}</div>
            <div class="text-sm text-yellow-600">Pending</div>
        </div>
        <div class="bg-blue-50 border-l-4 border-blue-400 p-4 rounded-r-lg">
            <div class="text-2xl font-bold text-blue-700">{{ $statusCounts['under_review'] }}</div>
            <div class="text-sm text-blue-600">Under Review</div>
        </div>
        <div class="bg-purple-50 border-l-4 border-purple-400 p-4 rounded-r-lg">
            <div class="text-2xl font-bold text-purple-700">{{ $statusCounts['shortlisted'] }}</div>
            <div class="text-sm text-purple-600">Shortlisted</div>
        </div>
        <div class="bg-indigo-50 border-l-4 border-indigo-400 p-4 rounded-r-lg">
            <div class="text-2xl font-bold text-indigo-700">{{ $statusCounts['interview_scheduled'] }}</div>
            <div class="text-sm text-indigo-600">Interview</div>
        </div>
        <div class="bg-green-50 border-l-4 border-green-400 p-4 rounded-r-lg">
            <div class="text-2xl font-bold text-green-700">{{ $statusCounts['approved'] }}</div>
            <div class="text-sm text-green-600">Approved</div>
        </div>
        <div class="bg-red-50 border-l-4 border-red-400 p-4 rounded-r-lg">
            <div class="text-2xl font-bold text-red-700">{{ $statusCounts['rejected'] }}</div>
            <div class="text-sm text-red-600">Rejected</div>
        </div>
    </div>

    <!-- Applications Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Applicant</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Internship</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Source</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Applied</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Current Stage</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($applications as $application)
                    @php 
                        $status = $application->status;
                        $statusValue = $status->value;
                        $allowedTransitions = $status->allowedTransitions();
                        $isTerminal = $status->isTerminal();
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10 bg-gradient-to-br from-blue-500 to-purple-500 rounded-full flex items-center justify-center text-white font-bold">
                                    {{ strtoupper(substr($application->user->name, 0, 1)) }}
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">{{ $application->user->name }}</div>
                                    <div class="text-sm text-gray-500">{{ $application->user->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-900">{{ $application->internship->title }}</div>
                            <div class="text-sm text-gray-500">{{ $application->internship->organization }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($application->internship->recruiter_id)
                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-purple-100 text-purple-800 border border-purple-300">
                                    Recruiter-Posted
                                </span>
                            @else
                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800 border border-blue-300">
                                    Admin-Posted
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $application->created_at->format('M d, Y') }}
                            <div class="text-xs text-gray-400">{{ $application->created_at->diffForHumans() }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $badgeColors = [
                                    'pending' => 'bg-yellow-100 text-yellow-800 border-yellow-300',
                                    'under_review' => 'bg-blue-100 text-blue-800 border-blue-300',
                                    'shortlisted' => 'bg-purple-100 text-purple-800 border-purple-300',
                                    'interview_scheduled' => 'bg-indigo-100 text-indigo-800 border-indigo-300',
                                    'approved' => 'bg-green-100 text-green-800 border-green-300',
                                    'rejected' => 'bg-red-100 text-red-800 border-red-300',
                                ];
                            @endphp
                            <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full border {{ $badgeColors[$statusValue] ?? 'bg-gray-100 text-gray-800' }}">
                                {{ $status->label() }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <div class="flex items-center gap-2">
                                @if(!$isTerminal)
                                <form action="{{ route('admin.applications.update-status', $application) }}" method="POST" class="inline-flex">
                                    @csrf
                                    <select name="status" onchange="this.form.submit()" 
                                        class="text-sm border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                        <option value="">Move to...</option>
                                        @foreach($allowedTransitions as $transition)
                                            <option value="{{ $transition->value }}">{{ $transition->label() }}</option>
                                        @endforeach
                                    </select>
                                </form>
                                @else
                                <span class="text-gray-400 text-xs italic">Final stage</span>
                                @endif
                                
                                <button onclick="showHistory({{ $application->id }})" 
                                    class="text-blue-600 hover:text-blue-800 text-xs underline">
                                    History
                                </button>
                                
                                <button onclick="openProfileModal({{ $application->id }})" 
                                    class="text-blue-600 hover:text-blue-800 text-xs underline">
                                    View Profile
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <p class="mt-2">No applications found</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $applications->links() }}
        </div>
    </div>
</div>

<!-- Profile Modal -->
<div id="profileModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4 opacity-0 transition-opacity">
    <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-hidden transform transition-all duration-300">
        <!-- Modal Header -->
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center bg-gradient-to-r from-blue-50 to-white">
            <h3 class="text-xl font-bold text-gray-800">Candidate Profile</h3>
            <button onclick="closeProfileModal()" class="text-gray-400 hover:text-gray-600 transition-colors duration-200 p-1 hover:bg-gray-100 rounded-full">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <!-- Modal Body -->
        <div class="px-4 sm:px-6 py-4 overflow-y-auto max-h-[calc(90vh-80px)]">
            <!-- Loading Spinner -->
            <div id="profileLoading" class="flex flex-col items-center justify-center py-16">
                <div class="animate-spin rounded-full h-16 w-16 border-b-4 border-blue-600 mb-4"></div>
                <p class="text-gray-600 text-sm">Loading candidate profile...</p>
            </div>
            
            <!-- Profile Content -->
            <div id="profileContent" class="hidden space-y-6">
                <!-- Profile Info Section -->
                <section class="profile-info bg-white rounded-lg border border-gray-200 p-4 sm:p-6 shadow-sm">
                    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4 mb-6">
                        <div class="flex-shrink-0 h-20 w-20 bg-gradient-to-br from-blue-500 to-purple-500 rounded-full flex items-center justify-center text-white font-bold text-3xl shadow-md">
                            <span id="profileInitial"></span>
                        </div>
                        <div class="flex-1">
                            <h4 class="text-2xl font-bold text-gray-900 mb-1" id="profileName"></h4>
                            <p class="text-gray-600 text-sm sm:text-base" id="profileEmail"></p>
                        </div>
                    </div>
                    
                    <!-- Academic Background -->
                    <div class="mb-6 pb-6 border-b border-gray-200">
                        <h5 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-3 flex items-center gap-2">
                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                            </svg>
                            Academic Background
                        </h5>
                        <p class="text-gray-800 text-sm sm:text-base leading-relaxed" id="profileAcademic"></p>
                    </div>
                    
                    <!-- Skills -->
                    <div>
                        <h5 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-3 flex items-center gap-2">
                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                            </svg>
                            Skills
                        </h5>
                        <div id="profileSkills" class="flex flex-wrap gap-2"></div>
                    </div>
                </section>
                
                <!-- Career Interests Section -->
                <section class="profile-about bg-white rounded-lg border border-gray-200 p-4 sm:p-6 shadow-sm">
                    <h5 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-3 flex items-center gap-2">
                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                        </svg>
                        Career Interests
                    </h5>
                    <p class="text-gray-800 text-sm sm:text-base leading-relaxed" id="profileCareerInterests"></p>
                </section>
                
                <!-- Projects Section -->
                <section class="profile-projects bg-white rounded-lg border border-gray-200 p-4 sm:p-6 shadow-sm">
                    <h5 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-3 flex items-center gap-2">
                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                        </svg>
                        Projects
                    </h5>
                    <div id="profileProjects" class="text-gray-800 text-sm sm:text-base leading-relaxed"></div>
                </section>
                
                <!-- AI Summary Section -->
                <section class="ai-summary bg-gradient-to-r from-blue-50 to-indigo-50 border-l-4 border-blue-500 p-4 sm:p-6 rounded-r-lg shadow-sm">
                    <h5 class="text-base font-bold text-blue-900 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                        </svg>
                        AI-Generated Candidate Summary
                    </h5>
                    <div id="aiSummaryContent" class="space-y-4">
                        <div class="bg-white rounded-lg p-4 border border-green-200">
                            <h6 class="text-sm font-semibold text-green-700 mb-2 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Strengths
                            </h6>
                            <ul id="aiStrengths" class="list-disc list-inside text-sm text-gray-700 space-y-1 ml-2"></ul>
                        </div>
                        <div class="bg-white rounded-lg p-4 border border-orange-200">
                            <h6 class="text-sm font-semibold text-orange-700 mb-2 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                </svg>
                                Areas for Development
                            </h6>
                            <ul id="aiWeaknesses" class="list-disc list-inside text-sm text-gray-700 space-y-1 ml-2"></ul>
                        </div>
                        <div class="bg-white rounded-lg p-4 border border-blue-200">
                            <h6 class="text-sm font-semibold text-blue-700 mb-2 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                </svg>
                                Overall Assessment
                            </h6>
                            <p id="aiAssessment" class="text-sm text-gray-700 leading-relaxed"></p>
                        </div>
                    </div>
                </section>
                
                <!-- Resume Viewer Section -->
                <section class="resume-viewer bg-white rounded-lg border border-gray-200 p-4 sm:p-6 shadow-sm">
                    <h5 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-4 flex items-center gap-2">
                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Resume
                    </h5>
                    <div id="resumeContent">
                        <div id="resumePreview" class="hidden">
                            <iframe id="resumeIframe" class="w-full h-96 border-2 border-gray-300 rounded-lg mb-3 shadow-inner"></iframe>
                            <a id="resumeDownload" href="#" download class="inline-flex items-center gap-2 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white px-4 py-2 rounded-lg text-sm font-medium shadow-md transition-all duration-200 transform hover:scale-105">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                </svg>
                                Download Resume
                            </a>
                        </div>
                        <div id="resumeNotFound" class="hidden text-gray-500 text-sm italic bg-gray-50 p-4 rounded-lg border border-gray-200 text-center">
                            <svg class="w-8 h-8 text-gray-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            No resume uploaded
                        </div>
                    </div>
                </section>
            </div>
            
            <!-- Error Message -->
            <div id="profileError" class="hidden text-center py-16">
                <div class="bg-red-50 rounded-lg p-8 border border-red-200 inline-block">
                    <svg class="mx-auto h-16 w-16 text-red-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p class="text-red-700 font-semibold text-lg mb-2">Unable to Load Profile</p>
                    <p class="text-red-600 text-sm" id="profileErrorMessage"></p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- History Modal -->
<div id="historyModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-xl max-w-lg w-full mx-4 max-h-[80vh] overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h3 class="text-lg font-semibold text-gray-800">Status History</h3>
            <button onclick="closeHistory()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <div id="historyContent" class="px-6 py-4 overflow-y-auto max-h-96">
            <div class="text-center text-gray-500">Loading...</div>
        </div>
    </div>
</div>

<script>
function showHistory(applicationId) {
    document.getElementById('historyModal').classList.remove('hidden');
    document.getElementById('historyContent').innerHTML = '<div class="text-center text-gray-500">Loading...</div>';
    
    fetch(`/admin/applications/${applicationId}/history`)
        .then(response => response.json())
        .then(data => {
            if (data.length === 0) {
                document.getElementById('historyContent').innerHTML = '<p class="text-gray-500 text-center">No history available</p>';
                return;
            }
            
            let html = '<div class="space-y-4">';
            data.forEach((log, index) => {
                html += `
                    <div class="flex items-start gap-3 ${index > 0 ? 'border-t pt-4' : ''}">
                        <div class="flex-shrink-0 w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <div class="text-sm font-medium text-gray-900">
                                ${log.from_status ? log.from_status + ' → ' : ''}${log.to_status}
                            </div>
                            <div class="text-xs text-gray-500 mt-1">
                                By ${log.actor_type} • ${log.created_at}
                            </div>
                            ${log.notes ? `<div class="text-xs text-gray-600 mt-1">${log.notes}</div>` : ''}
                        </div>
                    </div>
                `;
            });
            html += '</div>';
            document.getElementById('historyContent').innerHTML = html;
        })
        .catch(() => {
            document.getElementById('historyContent').innerHTML = '<p class="text-red-500 text-center">Failed to load history</p>';
        });
}

function closeHistory() {
    document.getElementById('historyModal').classList.add('hidden');
}

// Track if modal is currently loading to prevent multiple rapid clicks
let isModalLoading = false;

function openProfileModal(applicationId) {
    // Prevent multiple modals from opening on rapid clicks
    if (isModalLoading) {
        return;
    }
    
    const modal = document.getElementById('profileModal');
    const loading = document.getElementById('profileLoading');
    const content = document.getElementById('profileContent');
    const error = document.getElementById('profileError');
    
    // Set loading flag
    isModalLoading = true;
    
    // Show modal overlay (remove hidden first)
    modal.classList.remove('hidden');
    
    // Prevent background scrolling
    document.body.style.overflow = 'hidden';
    
    // Trigger fade-in animation (300ms ease-out)
    setTimeout(() => {
        modal.style.opacity = '1';
        modal.style.transition = 'opacity 300ms ease-out';
    }, 10);
    
    // Show loading spinner, hide content and error
    loading.classList.remove('hidden');
    content.classList.add('hidden');
    error.classList.add('hidden');
    
    // Fetch profile data via AJAX
    fetch(`/admin/applications/${applicationId}/profile`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                renderProfileData(data.data);
            } else {
                showProfileError(data.message || 'Unable to load profile data');
            }
        })
        .catch(err => {
            console.error('Profile fetch error:', err);
            showProfileError('Unable to connect. Please try again.');
        })
        .finally(() => {
            // Reset loading flag after request completes
            isModalLoading = false;
        });
}

function renderProfileData(data) {
    const loading = document.getElementById('profileLoading');
    const content = document.getElementById('profileContent');
    
    // Hide loading spinner
    loading.classList.add('hidden');
    
    // Populate profile information
    const user = data.user;
    const profile = data.profile;
    const aiSummary = data.ai_summary;
    
    // User basic info
    document.getElementById('profileInitial').textContent = user.name.charAt(0).toUpperCase();
    document.getElementById('profileName').textContent = user.name;
    document.getElementById('profileEmail').textContent = user.email;
    
    // Academic background
    document.getElementById('profileAcademic').textContent = profile.academic_background || 'Not provided';
    
    // Skills
    const skillsContainer = document.getElementById('profileSkills');
    skillsContainer.innerHTML = '';
    if (profile.skills && profile.skills.length > 0) {
        profile.skills.forEach(skill => {
            const skillBadge = document.createElement('span');
            skillBadge.className = 'px-3 py-1 bg-blue-100 text-blue-800 text-xs font-medium rounded-full border border-blue-200';
            skillBadge.textContent = skill;
            skillsContainer.appendChild(skillBadge);
        });
    } else {
        skillsContainer.innerHTML = '<span class="text-gray-500 text-sm italic">No skills listed</span>';
    }
    
    // Career interests
    document.getElementById('profileCareerInterests').textContent = profile.career_interests || 'Not provided';
    
    // Projects (assuming it's stored as text or could be JSON)
    const projectsContainer = document.getElementById('profileProjects');
    if (profile.projects) {
        projectsContainer.textContent = profile.projects;
    } else {
        projectsContainer.innerHTML = '<span class="text-gray-500 text-sm italic">No projects listed</span>';
    }
    
    // AI Summary
    const aiSummarySection = document.querySelector('.ai-summary');
    if (aiSummary) {
        // Strengths
        const strengthsList = document.getElementById('aiStrengths');
        strengthsList.innerHTML = '';
        if (aiSummary.strengths && aiSummary.strengths.length > 0) {
            aiSummary.strengths.forEach(strength => {
                const li = document.createElement('li');
                li.textContent = strength;
                strengthsList.appendChild(li);
            });
        }
        
        // Weaknesses
        const weaknessesList = document.getElementById('aiWeaknesses');
        weaknessesList.innerHTML = '';
        if (aiSummary.weaknesses && aiSummary.weaknesses.length > 0) {
            aiSummary.weaknesses.forEach(weakness => {
                const li = document.createElement('li');
                li.textContent = weakness;
                weaknessesList.appendChild(li);
            });
        }
        
        // Overall assessment
        document.getElementById('aiAssessment').textContent = aiSummary.overall_assessment || '';
        
        aiSummarySection.classList.remove('hidden');
    } else {
        // Hide AI summary section if not available
        aiSummarySection.classList.add('hidden');
    }
    
    // Resume
    const resumePreview = document.getElementById('resumePreview');
    const resumeNotFound = document.getElementById('resumeNotFound');
    
    if (profile.has_resume && profile.resume_path) {
        const resumeIframe = document.getElementById('resumeIframe');
        const resumeDownload = document.getElementById('resumeDownload');
        
        // Append #toolbar=0 for cleaner PDF display in browsers
        resumeIframe.src = profile.resume_path + '#toolbar=0';
        resumeDownload.href = profile.resume_path;
        resumeDownload.download = profile.resume_path.split('/').pop();

        // Handle iframe load errors gracefully
        resumeIframe.onerror = function() {
            resumeIframe.style.display = 'none';
            resumeDownload.insertAdjacentHTML('beforebegin', 
                '<p class="text-sm text-gray-500 italic mb-2">Preview unavailable — use the download button below.</p>');
        };
        
        resumePreview.classList.remove('hidden');
        resumeNotFound.classList.add('hidden');
    } else {
        resumePreview.classList.add('hidden');
        resumeNotFound.classList.remove('hidden');
    }
    
    // Show content
    content.classList.remove('hidden');
}

function showProfileError(message) {
    const loading = document.getElementById('profileLoading');
    const error = document.getElementById('profileError');
    const errorMessage = document.getElementById('profileErrorMessage');
    
    // Hide loading spinner
    loading.classList.add('hidden');
    
    // Show error message
    errorMessage.textContent = message;
    error.classList.remove('hidden');
}

function closeProfileModal() {
    const modal = document.getElementById('profileModal');
    
    // Trigger fade-out animation (200ms ease-in)
    modal.style.opacity = '0';
    modal.style.transition = 'opacity 200ms ease-in';
    
    // Wait for animation to complete before hiding
    setTimeout(() => {
        modal.classList.add('hidden');
        document.body.style.overflow = '';
        
        // Reset loading flag when modal is closed
        isModalLoading = false;
    }, 200);
}

document.getElementById('historyModal').addEventListener('click', function(e) {
    if (e.target === this) closeHistory();
});

document.getElementById('profileModal').addEventListener('click', function(e) {
    if (e.target === this) closeProfileModal();
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeProfileModal();
        closeHistory();
    }
});
</script>
@endsection
