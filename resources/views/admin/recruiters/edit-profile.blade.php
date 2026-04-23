@extends('admin.layout')

@section('title', 'Edit Recruiter Profile')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">Edit Recruiter Profile</h2>
                <p class="text-gray-500 mt-1">{{ $recruiter->name }} &mdash; {{ $recruiter->email }}</p>
            </div>
            <a href="{{ route('admin.recruiters.show', $recruiter) }}" class="text-gray-500 hover:text-gray-700">← Back</a>
        </div>
    </div>

    <!-- Form -->
    <div class="bg-white rounded-lg shadow p-6">
        <form action="{{ route('admin.recruiters.update-profile', $recruiter) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <!-- Organization -->
            <div class="mb-5">
                <label for="organization" class="block text-sm font-medium text-gray-700 mb-1">
                    Organization Name <span class="text-red-500">*</span>
                </label>
                <input type="text" id="organization" name="organization"
                    value="{{ old('organization', $recruiter->recruiterProfile->organization ?? '') }}"
                    required
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('organization') border-red-500 @enderror">
                @error('organization')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Description -->
            <div class="mb-5">
                <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea id="description" name="description" rows="4"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('description') border-red-500 @enderror"
                    placeholder="Brief description of the organization...">{{ old('description', $recruiter->recruiterProfile->description ?? '') }}</textarea>
                @error('description')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Website -->
            <div class="mb-5">
                <label for="website" class="block text-sm font-medium text-gray-700 mb-1">Website</label>
                <input type="url" id="website" name="website"
                    value="{{ old('website', $recruiter->recruiterProfile->website ?? '') }}"
                    placeholder="https://example.com"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('website') border-red-500 @enderror">
                @error('website')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Logo Upload -->
            <div class="mb-6">
                <label for="logo" class="block text-sm font-medium text-gray-700 mb-1">Logo</label>
                @if($recruiter->recruiterProfile && $recruiter->recruiterProfile->logo)
                    <div class="mb-3">
                        <img src="{{ asset('storage/' . $recruiter->recruiterProfile->logo) }}"
                            alt="Current Logo" class="h-16 w-16 rounded-full object-cover border border-gray-200">
                        <p class="text-xs text-gray-500 mt-1">Current logo — upload a new one to replace it</p>
                    </div>
                @endif
                <input type="file" id="logo" name="logo" accept="image/*"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('logo') border-red-500 @enderror">
                <p class="text-xs text-gray-500 mt-1">Accepted formats: JPG, PNG, GIF. Max size: 2MB.</p>
                @error('logo')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex gap-3">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium transition">
                    Save Changes
                </button>
                <a href="{{ route('admin.recruiters.show', $recruiter) }}"
                    class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-6 py-2 rounded-lg font-medium transition">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
