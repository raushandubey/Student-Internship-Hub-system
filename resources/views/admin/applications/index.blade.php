@extends('admin.layout')

@section('title', 'Manage Applications')

@section('content')
<div class="bg-white rounded-lg shadow p-6">
    <h2 class="text-2xl font-bold mb-6">Manage Applications</h2>

    <div class="overflow-x-auto">
        <table class="min-w-full table-auto border-collapse border border-gray-300">
            <thead class="bg-gray-200">
                <tr>
                    <th class="border border-gray-300 px-4 py-2 text-left">ID</th>
                    <th class="border border-gray-300 px-4 py-2 text-left">Student Name</th>
                    <th class="border border-gray-300 px-4 py-2 text-left">Internship Title</th>
                    <th class="border border-gray-300 px-4 py-2 text-left">Organization</th>
                    <th class="border border-gray-300 px-4 py-2 text-left">Applied Date</th>
                    <th class="border border-gray-300 px-4 py-2 text-left">Status</th>
                    <th class="border border-gray-300 px-4 py-2 text-left">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($applications as $application)
                <tr class="hover:bg-gray-50">
                    <td class="border border-gray-300 px-4 py-2">{{ $application->id }}</td>
                    <td class="border border-gray-300 px-4 py-2">{{ $application->user->name }}</td>
                    <td class="border border-gray-300 px-4 py-2">{{ $application->internship->title }}</td>
                    <td class="border border-gray-300 px-4 py-2">{{ $application->internship->organization }}</td>
                    <td class="border border-gray-300 px-4 py-2">{{ $application->created_at->format('d M Y') }}</td>
                    <td class="border border-gray-300 px-4 py-2">
                        <span class="px-2 py-1 rounded text-sm 
                            {{ $application->status == 'approved' ? 'bg-green-200 text-green-800' : '' }}
                            {{ $application->status == 'rejected' ? 'bg-red-200 text-red-800' : '' }}
                            {{ $application->status == 'pending' ? 'bg-yellow-200 text-yellow-800' : '' }}">
                            {{ ucfirst($application->status) }}
                        </span>
                    </td>
                    <td class="border border-gray-300 px-4 py-2">
                        <form action="{{ route('admin.applications.update-status', $application) }}" method="POST" class="inline-block">
                            @csrf
                            <select name="status" onchange="this.form.submit()" 
                                class="border border-gray-300 rounded px-2 py-1 text-sm">
                                <option value="pending" {{ $application->status == 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="approved" {{ $application->status == 'approved' ? 'selected' : '' }}>Approved</option>
                                <option value="rejected" {{ $application->status == 'rejected' ? 'selected' : '' }}>Rejected</option>
                            </select>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="border border-gray-300 px-4 py-2 text-center text-gray-500">
                        No applications found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $applications->links() }}
    </div>
</div>
@endsection
