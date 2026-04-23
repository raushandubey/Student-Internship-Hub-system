@extends('admin.layout')

@section('title', 'Manage Internships')

@section('content')
<div class="space-y-6">
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-2xl font-bold text-gray-800">Manage Internships</h2>
            <a href="{{ route('admin.internships.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 font-medium">
                Add New Internship
            </a>
        </div>

        <!-- Filters -->
        <form method="GET" action="{{ route('admin.internships.index') }}" class="flex flex-wrap gap-3 items-end">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Posted By</label>
                <select name="posted_by" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All</option>
                    <option value="admin" {{ request('posted_by') === 'admin' ? 'selected' : '' }}>Admin</option>
                    <option value="recruiter" {{ request('posted_by') === 'recruiter' ? 'selected' : '' }}>Recruiter</option>
                </select>
            </div>
            <div>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium">Filter</button>
            </div>
            @if(request('posted_by'))
            <div>
                <a href="{{ route('admin.internships.index') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium">Clear</a>
            </div>
            @endif
        </form>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Organization</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Recruiter</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Location</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Duration</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($internships as $internship)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm text-gray-600">{{ $internship->id }}</td>
                        <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $internship->title }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700">{{ $internship->organization }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700">
                            @if($internship->recruiter_id && $internship->recruiter)
                                <a href="{{ route('admin.recruiters.show', $internship->recruiter_id) }}" class="text-blue-600 hover:underline text-xs">
                                    {{ $internship->recruiter->name ?? '—' }}
                                </a>
                            @else
                                <span class="text-xs text-gray-400">Admin</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700">{{ $internship->location }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700">{{ $internship->duration }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 rounded text-xs font-medium {{ $internship->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $internship->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm">
                            <div class="flex items-center gap-2 flex-wrap">
                                <a href="{{ route('admin.internships.edit', $internship) }}" class="text-blue-600 hover:underline">Edit</a>

                                <form action="{{ route('admin.internships.toggle-status', $internship) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="text-yellow-600 hover:underline">
                                        {{ $internship->is_active ? 'Deactivate' : 'Activate' }}
                                    </button>
                                </form>

                                <form action="{{ route('admin.internships.destroy', $internship) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:underline">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-4 py-10 text-center text-gray-500">
                            No internships found.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-6 py-4 border-t border-gray-200">
            {{ $internships->withQueryString()->links() }}
        </div>
    </div>
</div>
@endsection
