@extends('admin.layout')

@section('title', 'Recruiter Analytics')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Recruiter Analytics</h2>
        <a href="{{ route('admin.recruiter-analytics.report', request()->query()) }}"
           class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition flex items-center gap-2">
            <i class="fas fa-download"></i> Generate Report
        </a>
    </div>

    {{-- Date Range Filter --}}
    <div class="bg-white rounded-lg shadow p-4 mb-6">
        <form method="GET" action="{{ route('admin.recruiter-analytics.index') }}" class="flex flex-wrap gap-4 items-end">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                <input type="date" name="start_date" value="{{ $startDate }}"
                       class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                <input type="date" name="end_date" value="{{ $endDate }}"
                       class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition text-sm">
                Apply Filter
            </button>
            @if($startDate || $endDate)
                <a href="{{ route('admin.recruiter-analytics.index') }}"
                   class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 transition text-sm">
                    Clear
                </a>
            @endif
        </form>
    </div>

    {{-- Analytics Table --}}
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Recruiter</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Organization</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Internships</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Applications</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Approval Rate</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Avg Response Time</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Fill Rate</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($analyticsData as $row)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <a href="{{ route('admin.recruiters.show', $row['recruiter_id']) }}"
                               class="text-blue-600 hover:underline font-medium">
                                {{ $row['recruiter_name'] }}
                            </a>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                            {{ $row['organization'] ?? '—' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm">
                            {{ $row['total_internships'] }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-semibold">
                            {{ $row['total_applications'] }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm">
                            {{ $row['approval_rate'] }}%
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm">
                            @if($row['avg_response_time'] !== null)
                                <span class="{{ $row['exceeds_response_threshold'] ? 'text-red-600 font-semibold' : 'text-gray-700' }}">
                                    {{ $row['avg_response_time'] }} days
                                    @if($row['exceeds_response_threshold'])
                                        <span class="ml-1 text-xs bg-red-100 text-red-700 px-1 rounded">Slow</span>
                                    @endif
                                </span>
                            @else
                                <span class="text-gray-400">N/A</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm">
                            {{ $row['fill_rate'] }}%
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                            No recruiter data available for the selected period.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
