@extends('admin.layout')

@section('title', 'Manage Recruiters')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">Recruiter Management</h2>
                <p class="text-gray-600 mt-1">Manage recruiter accounts, approvals, and activity</p>
            </div>
            @if(isset($pending_count) && $pending_count > 0)
                <span class="inline-flex items-center px-4 py-2 bg-yellow-100 text-yellow-800 rounded-full font-semibold text-sm border border-yellow-300">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    {{ $pending_count }} Pending Approval{{ $pending_count !== 1 ? 's' : '' }}
                </span>
            @endif
        </div>
    </div>

    <!-- Filters & Search -->
    <div class="bg-white rounded-lg shadow p-6">
        <form method="GET" action="{{ route('admin.recruiters.index') }}" id="filterForm">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Search -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}"
                        placeholder="Search by name, email, or organization..."
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>

                <!-- Status Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Approval Status</label>
                    <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">All Statuses</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                        <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Suspended</option>
                    </select>
                </div>

                <!-- Sort -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Sort By</label>
                    <select name="sort" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="registration_date" {{ request('sort', 'registration_date') === 'registration_date' ? 'selected' : '' }}>Registration Date</option>
                        <option value="internships_count" {{ request('sort') === 'internships_count' ? 'selected' : '' }}>Internships Count</option>
                        <option value="applications_count" {{ request('sort') === 'applications_count' ? 'selected' : '' }}>Applications Count</option>
                    </select>
                </div>
            </div>

            <div class="flex items-center gap-3 mt-4">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-lg font-medium transition">
                    Apply Filters
                </button>
                <a href="{{ route('admin.recruiters.index') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-5 py-2 rounded-lg font-medium transition">
                    Clear Filters
                </a>
            </div>
        </form>
    </div>

    <!-- Bulk Actions Bar -->
    <form method="POST" action="{{ route('admin.recruiters.bulk-action') }}" id="bulkForm">
        @csrf
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <!-- Bulk Action Bar -->
            <div class="px-6 py-3 bg-gray-50 border-b border-gray-200 flex items-center gap-4">
                <label class="flex items-center gap-2 text-sm font-medium text-gray-700">
                    <input type="checkbox" id="selectAll" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    Select All
                </label>
                <div class="flex items-center gap-2 ml-4">
                    <select name="action" id="bulkAction" class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Bulk Action...</option>
                        <option value="approve">Approve Selected</option>
                        <option value="reject">Reject Selected</option>
                        <option value="suspend">Suspend Selected</option>
                    </select>
                    <button type="button" onclick="confirmBulkAction()"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-1.5 rounded-lg text-sm font-medium transition">
                        Apply
                    </button>
                </div>
                <span id="selectedCount" class="text-sm text-gray-500 ml-2"></span>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left w-10"></th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Recruiter</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Organization</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'registration_date']) }}" class="hover:text-gray-700">
                                    Registered ↕
                                </a>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'internships_count']) }}" class="hover:text-gray-700">
                                    Internships ↕
                                </a>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'applications_count']) }}" class="hover:text-gray-700">
                                    Applications ↕
                                </a>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($recruiters as $recruiter)
                        @php
                            $status = $recruiter->recruiterProfile->approval_status ?? 'pending';
                            $statusColors = [
                                'pending'  => 'bg-yellow-100 text-yellow-800 border-yellow-300',
                                'approved' => 'bg-green-100 text-green-800 border-green-300',
                                'rejected' => 'bg-red-100 text-red-800 border-red-300',
                                'suspended'=> 'bg-gray-100 text-gray-700 border-gray-300',
                            ];
                            $badgeClass = $statusColors[$status] ?? 'bg-gray-100 text-gray-700';
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-4">
                                <input type="checkbox" name="recruiter_ids[]" value="{{ $recruiter->id }}"
                                    class="recruiter-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-9 w-9 bg-gradient-to-br from-indigo-500 to-purple-500 rounded-full flex items-center justify-center text-white font-bold text-sm">
                                        {{ strtoupper(substr($recruiter->name, 0, 1)) }}
                                    </div>
                                    <div class="ml-3">
                                        <div class="text-sm font-medium text-gray-900">{{ $recruiter->name }}</div>
                                        <div class="text-xs text-gray-500">{{ $recruiter->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                {{ $recruiter->recruiterProfile->organization ?? '—' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2.5 py-1 inline-flex text-xs leading-5 font-semibold rounded-full border {{ $badgeClass }}">
                                    {{ ucfirst($status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $recruiter->created_at->format('M d, Y') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 font-medium">
                                {{ $recruiter->internships_count ?? 0 }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 font-medium">
                                {{ $recruiter->applications_count ?? 0 }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <a href="{{ route('admin.recruiters.show', $recruiter) }}"
                                        class="text-blue-600 hover:text-blue-800 font-medium">View</a>

                                    @if($status === 'pending' || $status === 'rejected')
                                        <button type="button"
                                            onclick="submitAction('{{ route('admin.recruiters.approve', $recruiter) }}')"
                                            class="text-green-600 hover:text-green-800 font-medium">Approve</button>
                                    @endif

                                    @if($status === 'pending')
                                        <button type="button" onclick="openRejectModal({{ $recruiter->id }})"
                                            class="text-red-600 hover:text-red-800 font-medium">Reject</button>
                                    @endif

                                    @if($status === 'approved')
                                        <button type="button" onclick="openSuspendModal({{ $recruiter->id }})"
                                            class="text-orange-600 hover:text-orange-800 font-medium">Suspend</button>
                                    @endif

                                    @if($status === 'suspended')
                                        <button type="button"
                                            onclick="submitAction('{{ route('admin.recruiters.activate', $recruiter) }}')"
                                            class="text-green-600 hover:text-green-800 font-medium">Activate</button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                <svg class="mx-auto h-12 w-12 text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                <p>No recruiters found.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="px-6 py-4 border-t border-gray-200">
                {{ $recruiters->withQueryString()->links() }}
            </div>
        </div>
    </form>

    {{-- Hidden form for single-row approve/activate actions --}}
    <form id="actionForm" method="POST" style="display:none">
        @csrf
    </form>
</div>

<!-- Reject Modal -->
<div id="rejectModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Reject Recruiter</h3>
        <form id="rejectForm" method="POST">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Rejection Reason <span class="text-red-500">*</span></label>
                <textarea name="reason" rows="3" required placeholder="Provide a reason for rejection..."
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500"></textarea>
            </div>
            <div class="flex gap-3 justify-end">
                <button type="button" onclick="closeRejectModal()" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg">Cancel</button>
                <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-medium">Reject</button>
            </div>
        </form>
    </div>
</div>

<!-- Suspend Modal -->
<div id="suspendModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Suspend Recruiter</h3>
        <form id="suspendForm" method="POST">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Suspension Reason <span class="text-red-500">*</span></label>
                <textarea name="reason" rows="3" required placeholder="Provide a reason for suspension..."
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500"></textarea>
            </div>
            <div class="flex gap-3 justify-end">
                <button type="button" onclick="closeSuspendModal()" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg">Cancel</button>
                <button type="submit" class="bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded-lg font-medium">Suspend</button>
            </div>
        </form>
    </div>
</div>

<script>
// Select All
document.getElementById('selectAll').addEventListener('change', function() {
    document.querySelectorAll('.recruiter-checkbox').forEach(cb => cb.checked = this.checked);
    updateSelectedCount();
});

document.querySelectorAll('.recruiter-checkbox').forEach(cb => {
    cb.addEventListener('change', updateSelectedCount);
});

function updateSelectedCount() {
    const count = document.querySelectorAll('.recruiter-checkbox:checked').length;
    document.getElementById('selectedCount').textContent = count > 0 ? count + ' selected' : '';
}

// Submit a single-row action (approve/activate) via the hidden actionForm
function submitAction(url) {
    if (!confirm('Are you sure?')) return;
    const form = document.getElementById('actionForm');
    form.action = url;
    form.submit();
}

function confirmBulkAction() {
    const action = document.getElementById('bulkAction').value;
    const checked = document.querySelectorAll('.recruiter-checkbox:checked');
    if (!action) { alert('Please select a bulk action.'); return; }
    if (checked.length === 0) { alert('Please select at least one recruiter.'); return; }
    if (confirm(`Apply "${action}" to ${checked.length} recruiter(s)?`)) {
        document.getElementById('bulkForm').submit();
    }
}

function openRejectModal(recruiterId) {
    document.getElementById('rejectForm').action = `/admin/recruiters/${recruiterId}/reject`;
    document.getElementById('rejectModal').classList.remove('hidden');
}
function closeRejectModal() {
    document.getElementById('rejectModal').classList.add('hidden');
}

function openSuspendModal(recruiterId) {
    document.getElementById('suspendForm').action = `/admin/recruiters/${recruiterId}/suspend`;
    document.getElementById('suspendModal').classList.remove('hidden');
}
function closeSuspendModal() {
    document.getElementById('suspendModal').classList.add('hidden');
}
</script>
@endsection
