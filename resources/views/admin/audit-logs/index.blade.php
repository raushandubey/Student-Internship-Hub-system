@extends('admin.layout')

@section('title', 'Audit Logs')

@section('content')
<div class="container mx-auto px-4 py-8">
    <h2 class="text-2xl font-bold text-gray-800 mb-6">Admin Audit Logs</h2>

    {{-- Filters --}}
    <div class="bg-white rounded-lg shadow p-4 mb-6">
        <form method="GET" action="{{ route('admin.audit-logs.index') }}" class="flex flex-wrap gap-4 items-end">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Admin User</label>
                <select name="admin_id" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All Admins</option>
                    @foreach($admins as $admin)
                        <option value="{{ $admin->id }}" {{ ($filters['admin_id'] ?? '') == $admin->id ? 'selected' : '' }}>
                            {{ $admin->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Action Type</label>
                <select name="action_type" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All Actions</option>
                    @foreach($actionTypes as $type)
                        <option value="{{ $type }}" {{ ($filters['action_type'] ?? '') === $type ? 'selected' : '' }}>
                            {{ ucfirst(str_replace('_', ' ', $type)) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                <input type="date" name="start_date" value="{{ $filters['start_date'] ?? '' }}"
                       class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                <input type="date" name="end_date" value="{{ $filters['end_date'] ?? '' }}"
                       class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition text-sm">
                Filter
            </button>
            @if(array_filter($filters))
                <a href="{{ route('admin.audit-logs.index') }}"
                   class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 transition text-sm">
                    Clear
                </a>
            @endif
        </form>
    </div>

    {{-- Audit Log Table --}}
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Admin</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Target Recruiter</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reason</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">IP Address</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Timestamp</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($auditLogs as $log)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            {{ $log->admin?->name ?? '—' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $actionColors = [
                                    'approved' => 'bg-green-100 text-green-800',
                                    'rejected' => 'bg-red-100 text-red-800',
                                    'suspended' => 'bg-yellow-100 text-yellow-800',
                                    'activated' => 'bg-blue-100 text-blue-800',
                                    'internship_deactivated' => 'bg-orange-100 text-orange-800',
                                    'profile_edited' => 'bg-purple-100 text-purple-800',
                                ];
                                $colorClass = $actionColors[$log->action_type] ?? 'bg-gray-100 text-gray-800';
                            @endphp
                            <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $colorClass }}">
                                {{ ucfirst(str_replace('_', ' ', $log->action_type)) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                            @if($log->targetRecruiter)
                                <a href="{{ route('admin.recruiters.show', $log->target_recruiter_id) }}"
                                   class="text-blue-600 hover:underline">
                                    {{ $log->targetRecruiter->name }}
                                </a>
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600 max-w-xs truncate">
                            {{ $log->reason ?? '—' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $log->ip_address ?? '—' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $log->created_at->format('M d, Y H:i') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                            No audit log entries found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($auditLogs->hasPages())
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $auditLogs->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
