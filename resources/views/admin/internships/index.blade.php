@extends('admin.layout')

@section('title', 'Manage Internships')

@section('content')
<div class="bg-white rounded-lg shadow p-6">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold">Manage Internships</h2>
        <a href="{{ route('admin.internships.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
            Add New Internship
        </a>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full table-auto border-collapse border border-gray-300">
            <thead class="bg-gray-200">
                <tr>
                    <th class="border border-gray-300 px-4 py-2 text-left">ID</th>
                    <th class="border border-gray-300 px-4 py-2 text-left">Title</th>
                    <th class="border border-gray-300 px-4 py-2 text-left">Organization</th>
                    <th class="border border-gray-300 px-4 py-2 text-left">Location</th>
                    <th class="border border-gray-300 px-4 py-2 text-left">Duration</th>
                    <th class="border border-gray-300 px-4 py-2 text-left">Status</th>
                    <th class="border border-gray-300 px-4 py-2 text-left">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($internships as $internship)
                <tr class="hover:bg-gray-50">
                    <td class="border border-gray-300 px-4 py-2">{{ $internship->id }}</td>
                    <td class="border border-gray-300 px-4 py-2">{{ $internship->title }}</td>
                    <td class="border border-gray-300 px-4 py-2">{{ $internship->organization }}</td>
                    <td class="border border-gray-300 px-4 py-2">{{ $internship->location }}</td>
                    <td class="border border-gray-300 px-4 py-2">{{ $internship->duration }}</td>
                    <td class="border border-gray-300 px-4 py-2">
                        <span class="px-2 py-1 rounded text-sm {{ $internship->is_active ? 'bg-green-200 text-green-800' : 'bg-red-200 text-red-800' }}">
                            {{ $internship->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td class="border border-gray-300 px-4 py-2">
                        <div class="flex space-x-2">
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
                    <td colspan="7" class="border border-gray-300 px-4 py-2 text-center text-gray-500">
                        No internships found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $internships->links() }}
    </div>
</div>
@endsection
