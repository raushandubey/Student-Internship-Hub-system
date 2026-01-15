@extends('admin.layout')

@section('title', 'Edit Internship')

@section('content')
<div class="bg-white rounded-lg shadow p-6 max-w-3xl mx-auto">
    <h2 class="text-2xl font-bold mb-6">Edit Internship</h2>

    <form action="{{ route('admin.internships.update', $internship) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mb-4">
            <label class="block text-gray-700 font-semibold mb-2">Title *</label>
            <input type="text" name="title" value="{{ old('title', $internship->title) }}" required
                class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:border-blue-500">
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 font-semibold mb-2">Organization *</label>
            <input type="text" name="organization" value="{{ old('organization', $internship->organization) }}" required
                class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:border-blue-500">
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 font-semibold mb-2">Description *</label>
            <textarea name="description" rows="4" required
                class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:border-blue-500">{{ old('description', $internship->description) }}</textarea>
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 font-semibold mb-2">Required Skills (comma-separated) *</label>
            <input type="text" name="required_skills" 
                value="{{ old('required_skills', is_array($internship->required_skills) ? implode(', ', $internship->required_skills) : $internship->required_skills) }}" 
                required
                placeholder="e.g., Python, Django, PostgreSQL"
                class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:border-blue-500">
            <p class="text-sm text-gray-500 mt-1">Enter skills separated by commas</p>
        </div>

        <div class="grid grid-cols-2 gap-4 mb-4">
            <div>
                <label class="block text-gray-700 font-semibold mb-2">Duration *</label>
                <input type="text" name="duration" value="{{ old('duration', $internship->duration) }}" required
                    placeholder="e.g., 6 months"
                    class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:border-blue-500">
            </div>

            <div>
                <label class="block text-gray-700 font-semibold mb-2">Location *</label>
                <input type="text" name="location" value="{{ old('location', $internship->location) }}" required
                    placeholder="e.g., Mumbai"
                    class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:border-blue-500">
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4 mb-4">
            <div>
                <label class="block text-gray-700 font-semibold mb-2">Work Type</label>
                <select name="work_type" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:border-blue-500">
                    <option value="">Select</option>
                    <option value="Remote" {{ old('work_type', $internship->work_type) == 'Remote' ? 'selected' : '' }}>Remote</option>
                    <option value="On-site" {{ old('work_type', $internship->work_type) == 'On-site' ? 'selected' : '' }}>On-site</option>
                    <option value="Hybrid" {{ old('work_type', $internship->work_type) == 'Hybrid' ? 'selected' : '' }}>Hybrid</option>
                </select>
            </div>

            <div>
                <label class="block text-gray-700 font-semibold mb-2">Category</label>
                <input type="text" name="category" value="{{ old('category', $internship->category) }}"
                    placeholder="e.g., Technology"
                    class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:border-blue-500">
            </div>
        </div>

        <div class="flex space-x-4">
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">
                Update Internship
            </button>
            <a href="{{ route('admin.internships.index') }}" class="bg-gray-300 text-gray-700 px-6 py-2 rounded hover:bg-gray-400">
                Cancel
            </a>
        </div>
    </form>
</div>
@endsection
