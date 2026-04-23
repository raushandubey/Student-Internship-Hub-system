@extends('admin.layout')

@section('title', 'Recruiter Details')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex justify-between items-start">
            <div class="flex items-center gap-4">
                <div class="h-16 w-16 bg-gradient-to-br from-indigo-500 to-purple-500 rounded-full flex items-center justify-center text-white font-bold text-2xl flex-shrink-0">
                    @if($recruiter->recruiterProfile && $recruiter->recruiterProfile->logo)
                        <img src="{{ asset('storage/' . $recruiter->recruiterProfile->logo) }}" alt="Logo" class="h-16 w-16 rounded-full object-cover">
                    @else
                        {{ strtoupper(substr($recruiter->name, 0, 1)) }}
                    @endif
                </div>
                <div>
                    <h2 class="text-2xl font-bold text-gray-800">{{ $recruiter->name }}</h2>
                    <p class="text-gray-500">{{ $recruiter->email }}</p>
                    @if($recruiter->recruiterProfile)
                        <p class="text-gray-600 font-medium">{{ $recruiter->recruiterProfile->organization }}</p>
                    @endif
                </div>
            </div>
            <div class="flex items-center gap-3 flex-wrap justify-end">
                @php $status = $recruiter->recruiterProfile->approval_status ?? 'pending'; @endphp

                @if($status === 'approved')
                    <button onclick="openSuspendModal()" class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-lg font-medium transition">
                        Suspend
                    </button>
                @endif

                @if($status === 'suspended')
                    <form action="{{ route('admin.recruiters.activate', $recruiter) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium transition"
                            onclick="return confirm('Activate this recruiter?')">Activate</button>
                    </form>
                @endif

                @if($status === 'pending' || $status === 'rejected')
                    <form action="{{ route('admin.recruiters.approve', $recruiter) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium transition"
                            onclick="return confirm('Approve this recruiter?')">Approve</button>
                    </form>
                @endif

                <a href="{{ route('admin.recruiters.edit-profile', $recruiter) }}"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition">
                    Edit Profile
                </a>
                <a href="{{ route('admin.audit-logs.index', ['recruiter_id' => $recruiter->id]) }}"
                    class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg font-medium transition">
                    View Audit Log
                </a>
                <a href="{{ route('admin.recruiters.index') }}" class="text-gray-500 hover:text-gray-700 px-3 py-2">← Back</a>
            </div>
        </div>
    </div>

    <!-- Status Alerts -->
    @if($recruiter->recruiterProfile && $recruiter->recruiterProfile->rejection_reason)
        <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-r-lg">
            <p class="font-semibold text-red-800">Rejection Reason</p>
            <p class="text-red-700 mt-1">{{ $recruiter->recruiterProfile->rejection_reason }}</p>
        </div>
    @endif

    @if($recruiter->recruiterProfile && $recruiter->recruiterProfile->suspension_reason)
        <div class="bg-orange-50 border-l-4 border-orange-500 p-4 rounded-r-lg">
            <p class="font-semibold text-orange-800">Suspension Reason</p>
            <p class="text-orange-700 mt-1">{{ $recruiter->recruiterProfile->suspension_reason }}</p>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Profile Info -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Profile Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Organization</p>
                        <p class="text-gray-800 mt-1">{{ $recruiter->recruiterProfile->organization ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Website</p>
                        @if($recruiter->recruiterProfile && $recruiter->recruiterProfile->website)
                            <a href="{{ $recruiter->recruiterProfile->website }}" target="_blank" class="text-blue-600 hover:underline mt-1 block">
                                {{ $recruiter->recruiterProfile->website }}
                            </a>
                        @else
                            <p class="text-gray-500 mt-1">—</p>
                        @endif
                    </div>
                    <div class="md:col-span-2">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Description</p>
                        <p class="text-gray-800 mt-1">{{ $recruiter->recruiterProfile->description ?? '—' }}</p>
                    </div>
                </div>
            </div>

            <!-- Activity Metrics -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Activity Metrics</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="bg-blue-50 rounded-lg p-4 text-center">
                        <p class="text-3xl font-bold text-blue-700">{{ $metrics['total_internships'] ?? 0 }}</p>
                        <p class="text-sm text-blue-600 mt-1">Total Internships</p>
                    </div>
                    <div class="bg-green-50 rounded-lg p-4 text-center">
                        <p class="text-3xl font-bold text-green-700">{{ $metrics['active_internships'] ?? 0 }}</p>
                        <p class="text-sm text-green-600 mt-1">Active Internships</p>
                    </div>
                    <div class="bg-purple-50 rounded-lg p-4 text-center">
                        <p class="text-3xl font-bold text-purple-700">{{ $metrics['total_applications'] ?? 0 }}</p>
                        <p class="text-sm text-purple-600 mt-1">Total Applications</p>
                    </div>
                    <div class="bg-indigo-50 rounded-lg p-4 text-center">
                        <p class="text-3xl font-bold text-indigo-700">{{ $metrics['approved_applications'] ?? 0 }}</p>
                        <p class="text-sm text-indigo-600 mt-1">Approved Applications</p>
                    </div>
                </div>
            </div>

            <!-- Internships List -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Posted Internships</h3>
                @if($recruiter->internships && $recruiter->internships->count() > 0)
                    <div class="space-y-3">
                        @foreach($recruiter->internships as $internship)
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-200">
                            <div>
                                <a href="{{ route('admin.internships.edit', $internship) }}" class="font-medium text-blue-600 hover:underline">
                                    {{ $internship->title }}
                                </a>
                                <p class="text-xs text-gray-500 mt-0.5">{{ $internship->organization }} · {{ $internship->created_at->format('M d, Y') }}</p>
                            </div>
                            <span class="px-2 py-1 text-xs rounded-full {{ $internship->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                {{ $internship->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-500 text-sm">No internships posted yet.</p>
                @endif
            </div>

            <!-- Recent Applications -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Recent Applications (Last 10)</h3>
                @if(isset($recent_applications) && $recent_applications->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Applicant</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Internship</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach($recent_applications as $app)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3">{{ $app->user->name ?? '—' }}</td>
                                    <td class="px-4 py-3">{{ $app->internship->title ?? '—' }}</td>
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-0.5 rounded-full text-xs bg-blue-100 text-blue-700">
                                            {{ ucfirst(str_replace('_', ' ', $app->status->value ?? $app->status)) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-gray-500">{{ $app->created_at->format('M d, Y') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-gray-500 text-sm">No recent applications.</p>
                @endif
            </div>

            <!-- Audit Log -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Audit History</h3>
                @if(isset($audit_logs) && $audit_logs->count() > 0)
                    <div class="space-y-3">
                        @foreach($audit_logs as $log)
                        <div class="flex items-start gap-3 p-3 bg-gray-50 rounded-lg">
                            <div class="flex-shrink-0 w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-gray-800">{{ ucfirst(str_replace('_', ' ', $log->action_type)) }}</span>
                                    <span class="text-xs text-gray-400">{{ $log->created_at->format('M d, Y H:i') }}</span>
                                </div>
                                <p class="text-xs text-gray-500 mt-0.5">By {{ $log->admin->name ?? 'Admin' }}</p>
                                @if($log->reason)
                                    <p class="text-xs text-gray-600 mt-1">{{ $log->reason }}</p>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-500 text-sm">No audit history found.</p>
                @endif
            </div>
        </div>

        <!-- Right Column -->
        <div class="space-y-6">
            <!-- Account Status -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Account Status</h3>
                <div class="space-y-3">
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Approval Status</p>
                        @php
                            $statusColors = [
                                'pending'  => 'bg-yellow-100 text-yellow-800 border-yellow-300',
                                'approved' => 'bg-green-100 text-green-800 border-green-300',
                                'rejected' => 'bg-red-100 text-red-800 border-red-300',
                                'suspended'=> 'bg-gray-100 text-gray-700 border-gray-300',
                            ];
                        @endphp
                        <span class="mt-1 inline-block px-3 py-1 text-sm font-semibold rounded-full border {{ $statusColors[$status] ?? 'bg-gray-100 text-gray-700' }}">
                            {{ ucfirst($status) }}
                        </span>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Registration Date</p>
                        <p class="text-gray-800 mt-1">{{ $recruiter->created_at->format('M d, Y') }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Last Login</p>
                        <p class="text-gray-800 mt-1">{{ $recruiter->last_login_at ? \Carbon\Carbon::parse($recruiter->last_login_at)->format('M d, Y H:i') : '—' }}</p>
                    </div>
                    @if($recruiter->recruiterProfile && $recruiter->recruiterProfile->approved_at)
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Approved At</p>
                        <p class="text-gray-800 mt-1">{{ \Carbon\Carbon::parse($recruiter->recruiterProfile->approved_at)->format('M d, Y') }}</p>
                    </div>
                    @endif
                    @if($recruiter->recruiterProfile && $recruiter->recruiterProfile->approvedBy)
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Approved By</p>
                        <p class="text-gray-800 mt-1">{{ $recruiter->recruiterProfile->approvedBy->name }}</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Activity Timeline -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Activity Timeline</h3>
                <div id="timelineContainer">
                    <div class="text-center py-6">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto mb-2"></div>
                        <p class="text-sm text-gray-500">Loading timeline...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Suspend Modal -->
<div id="suspendModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Suspend Recruiter</h3>
        <form action="{{ route('admin.recruiters.suspend', $recruiter) }}" method="POST">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Suspension Reason <span class="text-red-500">*</span></label>
                <textarea name="reason" rows="3" required placeholder="Provide a reason for suspension..."
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500"></textarea>
            </div>
            <div class="flex gap-3 justify-end">
                <button type="button" onclick="document.getElementById('suspendModal').classList.add('hidden')"
                    class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg">Cancel</button>
                <button type="submit" class="bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded-lg font-medium">Suspend</button>
            </div>
        </form>
    </div>
</div>

<script>
function openSuspendModal() {
    document.getElementById('suspendModal').classList.remove('hidden');
}

// Load activity timeline via AJAX
fetch('{{ route('admin.recruiters.activity-timeline', $recruiter) }}')
    .then(r => r.json())
    .then(data => {
        const container = document.getElementById('timelineContainer');
        if (!data.events || data.events.length === 0) {
            container.innerHTML = '<p class="text-sm text-gray-500 text-center">No timeline events found.</p>';
            return;
        }
        let html = '<div class="relative">';
        data.events.forEach((event, i) => {
            html += `
                <div class="flex gap-3 ${i < data.events.length - 1 ? 'mb-4' : ''}">
                    <div class="flex-shrink-0 w-2 h-2 bg-blue-500 rounded-full mt-2"></div>
                    <div>
                        <p class="text-sm font-medium text-gray-800">${event.type}</p>
                        <p class="text-xs text-gray-500">${event.timestamp}</p>
                        ${event.details ? `<p class="text-xs text-gray-600 mt-0.5">${event.details}</p>` : ''}
                    </div>
                </div>`;
        });
        html += '</div>';
        container.innerHTML = html;
    })
    .catch(() => {
        document.getElementById('timelineContainer').innerHTML = '<p class="text-sm text-gray-500 text-center">Unable to load timeline.</p>';
    });
</script>
@endsection
