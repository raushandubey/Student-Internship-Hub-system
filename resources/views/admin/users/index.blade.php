@extends('admin.layout')

@section('title', 'Manage Students')

@section('content')
<div class="bg-white rounded-lg shadow p-6">
    <h2 class="text-2xl font-bold mb-6">Manage Students</h2>

    <div class="overflow-x-auto">
        <table class="min-w-full table-auto border-collapse border border-gray-300">
            <thead class="bg-gray-200">
                <tr>
                    <th class="border border-gray-300 px-4 py-2 text-left">ID</th>
                    <th class="border border-gray-300 px-4 py-2 text-left">Name</th>
                    <th class="border border-gray-300 px-4 py-2 text-left">Email</th>
                    <th class="border border-gray-300 px-4 py-2 text-left">Registered Date</th>
                    <th class="border border-gray-300 px-4 py-2 text-left">Profile Status</th>
                    <th class="border border-gray-300 px-4 py-2 text-left">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($students as $student)
                <tr class="hover:bg-gray-50">
                    <td class="border border-gray-300 px-4 py-2">{{ $student->id }}</td>
                    <td class="border border-gray-300 px-4 py-2">{{ $student->name }}</td>
                    <td class="border border-gray-300 px-4 py-2">{{ $student->email }}</td>
                    <td class="border border-gray-300 px-4 py-2">{{ $student->created_at->format('d M Y') }}</td>
                    <td class="border border-gray-300 px-4 py-2">
                        @if($student->profile)
                            <span class="px-2 py-1 rounded text-sm bg-green-200 text-green-800">Complete</span>
                        @else
                            <span class="px-2 py-1 rounded text-sm bg-red-200 text-red-800">Incomplete</span>
                        @endif
                    </td>
                    <td class="border border-gray-300 px-4 py-2">
                        <a href="{{ route('admin.users.show', $student) }}" class="text-blue-600 hover:underline">
                            View Details
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="border border-gray-300 px-4 py-2 text-center text-gray-500">
                        No students found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $students->links() }}
    </div>
</div>
@endsection
