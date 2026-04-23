<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resume Not Found</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center px-4">
        <div class="max-w-md w-full bg-white rounded-lg shadow-lg p-8 text-center">
            <div class="mb-6">
                <svg class="mx-auto h-16 w-16 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
            </div>
            
            <h1 class="text-2xl font-bold text-gray-900 mb-2">Resume Not Found</h1>
            <p class="text-gray-600 mb-6">
                The resume file you're looking for could not be found. It may have been deleted or moved.
            </p>
            
            <div class="space-y-3">
                <a href="{{ route('profile.edit') }}" class="block w-full bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                    Upload New Resume
                </a>
                <a href="{{ route('dashboard') }}" class="block w-full bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 transition">
                    Go to Dashboard
                </a>
            </div>
            
            <p class="text-sm text-gray-500 mt-6">
                If you believe this is an error, please contact support.
            </p>
        </div>
    </div>
</body>
</html>
