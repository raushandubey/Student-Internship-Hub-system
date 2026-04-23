@extends('admin.layout')

@section('title', 'Admin Dashboard')

@section('content')
<div class="space-y-6">
    <!-- Welcome Section -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-3xl font-bold text-gray-800">Welcome, {{ auth()->user()->name }}!</h2>
        <p class="text-gray-600 mt-2">Here's an overview of your Student Internship Hub system.</p>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Total Students Card -->
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-100 text-sm font-medium">Total Students</p>
                    <h3 class="text-4xl font-bold mt-2">{{ $stats['total_students'] }}</h3>
                </div>
                <div class="bg-white bg-opacity-20 rounded-full p-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                </div>
            </div>
            <div class="mt-4 flex items-center text-sm">
                <span class="text-blue-100">Registered users</span>
            </div>
        </div>

        <!-- Total Internships Card -->
        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-lg shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-green-100 text-sm font-medium">Total Internships</p>
                    <h3 class="text-4xl font-bold mt-2">{{ $stats['total_internships'] }}</h3>
                </div>
                <div class="bg-white bg-opacity-20 rounded-full p-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                </div>
            </div>
            <div class="mt-4 flex items-center text-sm">
                <span class="text-green-100">Active: {{ $stats['active_internships'] }}</span>
            </div>
        </div>

        <!-- Total Applications Card -->
        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-purple-100 text-sm font-medium">Total Applications</p>
                    <h3 class="text-4xl font-bold mt-2">{{ $stats['total_applications'] }}</h3>
                </div>
                <div class="bg-white bg-opacity-20 rounded-full p-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
            </div>
            <div class="mt-4 flex items-center text-sm">
                <span class="text-purple-100">All submissions</span>
            </div>
        </div>

        <!-- Pending Applications Card -->
        <div class="bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-lg shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-yellow-100 text-sm font-medium">Pending Review</p>
                    <h3 class="text-4xl font-bold mt-2">{{ $stats['pending_applications'] }}</h3>
                </div>
                <div class="bg-white bg-opacity-20 rounded-full p-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
            <div class="mt-4 flex items-center text-sm">
                <span class="text-yellow-100">Needs attention</span>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-xl font-bold text-gray-800 mb-4">Quick Actions</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <a href="{{ route('admin.internships.create') }}" 
               class="flex items-center p-4 bg-blue-50 hover:bg-blue-100 rounded-lg transition duration-200 border border-blue-200">
                <div class="bg-blue-500 rounded-full p-3 mr-4">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                </div>
                <div>
                    <h4 class="font-semibold text-gray-800">Add Internship</h4>
                    <p class="text-sm text-gray-600">Create new opportunity</p>
                </div>
            </a>

            <a href="{{ route('admin.applications.index') }}" 
               class="flex items-center p-4 bg-green-50 hover:bg-green-100 rounded-lg transition duration-200 border border-green-200">
                <div class="bg-green-500 rounded-full p-3 mr-4">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                    </svg>
                </div>
                <div>
                    <h4 class="font-semibold text-gray-800">Review Applications</h4>
                    <p class="text-sm text-gray-600">Manage submissions</p>
                </div>
            </a>

            <a href="{{ route('admin.users.index') }}" 
               class="flex items-center p-4 bg-purple-50 hover:bg-purple-100 rounded-lg transition duration-200 border border-purple-200">
                <div class="bg-purple-500 rounded-full p-3 mr-4">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
                <div>
                    <h4 class="font-semibold text-gray-800">View Students</h4>
                    <p class="text-sm text-gray-600">Browse user profiles</p>
                </div>
            </a>
        </div>
    </div>

    <!-- Recruiter Statistics -->
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-xl font-bold text-gray-800 mb-4">Recruiter Overview</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <!-- Approved Recruiters -->
            <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-lg p-5 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-emerald-100 text-sm font-medium">Approved Recruiters</p>
                        <h4 class="text-3xl font-bold mt-1">{{ $approved_recruiters ?? 0 }}</h4>
                    </div>
                    <div class="bg-white bg-opacity-20 rounded-full p-3">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
                <a href="{{ route('admin.recruiters.index', ['status' => 'approved']) }}" class="text-emerald-100 text-xs mt-2 block hover:text-white">View approved →</a>
            </div>

            <!-- Pending Approvals -->
            <div class="bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-lg p-5 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-yellow-100 text-sm font-medium">Pending Approvals</p>
                        <h4 class="text-3xl font-bold mt-1">{{ $pending_recruiters ?? 0 }}</h4>
                    </div>
                    <div class="bg-white bg-opacity-20 rounded-full p-3">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
                <a href="{{ route('admin.recruiters.index', ['status' => 'pending']) }}" class="text-yellow-100 text-xs mt-2 block hover:text-white">Review pending →</a>
            </div>

            <!-- Suspended Recruiters -->
            <div class="bg-gradient-to-br from-gray-500 to-gray-600 rounded-lg p-5 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-100 text-sm font-medium">Suspended Recruiters</p>
                        <h4 class="text-3xl font-bold mt-1">{{ $suspended_recruiters ?? 0 }}</h4>
                    </div>
                    <div class="bg-white bg-opacity-20 rounded-full p-3">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
                        </svg>
                    </div>
                </div>
                <a href="{{ route('admin.recruiters.index', ['status' => 'suspended']) }}" class="text-gray-100 text-xs mt-2 block hover:text-white">View suspended →</a>
            </div>

            <!-- Total Recruiter Internships -->
            <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg p-5 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-blue-100 text-sm font-medium">Recruiter Internships</p>
                        <h4 class="text-3xl font-bold mt-1">{{ $total_recruiter_internships ?? 0 }}</h4>
                    </div>
                    <div class="bg-white bg-opacity-20 rounded-full p-3">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                </div>
                <p class="text-blue-100 text-xs mt-2">Active: {{ $active_recruiter_internships ?? 0 }}</p>
            </div>

            <!-- Active Recruiter Internships -->
            <div class="bg-gradient-to-br from-teal-500 to-teal-600 rounded-lg p-5 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-teal-100 text-sm font-medium">Active Recruiter Internships</p>
                        <h4 class="text-3xl font-bold mt-1">{{ $active_recruiter_internships ?? 0 }}</h4>
                    </div>
                    <div class="bg-white bg-opacity-20 rounded-full p-3">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
                        </svg>
                    </div>
                </div>
                <p class="text-teal-100 text-xs mt-2">Currently live</p>
            </div>

            <!-- Applications to Recruiter Internships -->
            <div class="bg-gradient-to-br from-violet-500 to-violet-600 rounded-lg p-5 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-violet-100 text-sm font-medium">Recruiter Applications</p>
                        <h4 class="text-3xl font-bold mt-1">{{ $applications_to_recruiter_internships ?? 0 }}</h4>
                    </div>
                    <div class="bg-white bg-opacity-20 rounded-full p-3">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                </div>
                <p class="text-violet-100 text-xs mt-2">To recruiter-posted internships</p>
            </div>
        </div>
    </div>

    <!-- Recent Activity Summary -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- System Status -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">System Status</h3>
            <div class="space-y-3">
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
                    <span class="text-gray-700">Active Internships</span>
                    <span class="font-semibold text-green-600">{{ $stats['active_internships'] }}</span>
                </div>
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
                    <span class="text-gray-700">Inactive Internships</span>
                    <span class="font-semibold text-gray-600">{{ $stats['total_internships'] - $stats['active_internships'] }}</span>
                </div>
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
                    <span class="text-gray-700">Approval Rate</span>
                    <span class="font-semibold text-blue-600">
                        {{ $stats['total_applications'] > 0 ? round((($stats['total_applications'] - $stats['pending_applications']) / $stats['total_applications']) * 100) : 0 }}%
                    </span>
                </div>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Quick Stats</h3>
            <div class="space-y-3">
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
                    <span class="text-gray-700">Avg. Applications per Internship</span>
                    <span class="font-semibold text-purple-600">
                        {{ $stats['total_internships'] > 0 ? round($stats['total_applications'] / $stats['total_internships'], 1) : 0 }}
                    </span>
                </div>
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
                    <span class="text-gray-700">Students with Profiles</span>
                    <span class="font-semibold text-blue-600">{{ $stats['total_students'] }}</span>
                </div>
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
                    <span class="text-gray-700">Pending Reviews</span>
                    <span class="font-semibold text-yellow-600">{{ $stats['pending_applications'] }}</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
