<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin Panel') - SIH</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-gradient-to-r from-blue-600 to-blue-700 text-white shadow-lg">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center space-x-8">
                    <h1 class="text-2xl font-bold">🎓 Admin</h1>
                    <div class="hidden md:flex space-x-1">
                        <a href="{{ route('admin.dashboard') }}" 
                           class="px-4 py-2 rounded-lg hover:bg-white hover:bg-opacity-20 transition {{ request()->routeIs('admin.dashboard') ? 'bg-white bg-opacity-20' : '' }}">
                            Dashboard
                        </a>
                        <a href="{{ route('admin.internships.index') }}" 
                           class="px-4 py-2 rounded-lg hover:bg-white hover:bg-opacity-20 transition {{ request()->routeIs('admin.internships.*') ? 'bg-white bg-opacity-20' : '' }}">
                            Internships
                        </a>
                        <a href="{{ route('admin.applications.index') }}" 
                           class="px-4 py-2 rounded-lg hover:bg-white hover:bg-opacity-20 transition {{ request()->routeIs('admin.applications.*') ? 'bg-white bg-opacity-20' : '' }}">
                            Applications
                        </a>
                        <a href="{{ route('admin.users.index') }}" 
                           class="px-4 py-2 rounded-lg hover:bg-white hover:bg-opacity-20 transition {{ request()->routeIs('admin.users.*') ? 'bg-white bg-opacity-20' : '' }}">
                            Students
                        </a>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="hidden md:block text-right">
                        <p class="text-sm text-blue-100">Logged in as</p>
                        <p class="font-semibold">{{ auth()->user()->name }}</p>
                    </div>
                    <form action="{{ route('logout') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="bg-red-500 px-4 py-2 rounded-lg hover:bg-red-600 transition font-medium">
                            Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mx-auto px-4 py-8">
        <!-- Success Message -->
        @if(session('success'))
            <div class="bg-green-50 border-l-4 border-green-500 text-green-800 px-6 py-4 rounded-lg mb-6 shadow">
                <div class="flex items-center">
                    <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="font-medium">{{ session('success') }}</span>
                </div>
            </div>
        @endif

        <!-- Error Messages -->
        @if($errors->any())
            <div class="bg-red-50 border-l-4 border-red-500 text-red-800 px-6 py-4 rounded-lg mb-6 shadow">
                <div class="flex items-start">
                    <svg class="w-6 h-6 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div>
                        <p class="font-medium mb-2">Please fix the following errors:</p>
                        <ul class="list-disc list-inside space-y-1">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        @yield('content')
    </div>

    <!-- Footer -->
    <footer class="bg-white border-t mt-12 py-6">
        <div class="container mx-auto px-4 text-center text-gray-600">
            <p>&copy; {{ date('Y') }} Student Internship Hub. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
