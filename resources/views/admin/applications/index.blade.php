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

document.getElementById('historyModal').addEventListener('click', function(e) {
    if (e.target === this) closeHistory();
});
</script>
@endsection
