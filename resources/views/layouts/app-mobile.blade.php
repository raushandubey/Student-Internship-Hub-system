<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, user-scalable=yes">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#5a67d8">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="description" content="InternshipHub — Find your perfect internship opportunity">

    <title>{{ config('app.name', 'InternshipHub') }}</title>

    <!-- Preconnect -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">

    <!-- Inter Font -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /* ============================================================
           MOBILE DESIGN SYSTEM — Single Source of Truth
           All mobile-specific utilities live here.
           ============================================================ */

        *, *::before, *::after {
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }

        html {
            scroll-behavior: smooth;
            /* Prevent horizontal overflow globally */
            overflow-x: hidden;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            background-color: #f3f4f6;
            /* Prevent horizontal overflow */
            overflow-x: hidden;
            max-width: 100vw;
        }

        /* Prevent zoom on input focus (iOS) */
        input, select, textarea {
            font-size: 16px !important;
        }

        /* Safe area support for notched phones */
        @supports (padding: env(safe-area-inset-bottom)) {
            .pb-safe {
                padding-bottom: env(safe-area-inset-bottom);
            }
            .bottom-nav-safe {
                padding-bottom: calc(env(safe-area-inset-bottom));
            }
        }

        /* ============================================================
           COLOR SYSTEM — Primary (Indigo)
           ============================================================ */
        :root {
            --primary-50:  #eef2ff;
            --primary-100: #e0e7ff;
            --primary-200: #c7d2fe;
            --primary-400: #818cf8;
            --primary-500: #6366f1;
            --primary-600: #5a67d8;
            --primary-700: #4c51bf;
            --primary-800: #3730a3;
        }

        .text-primary-600   { color: var(--primary-600); }
        .text-primary-700   { color: var(--primary-700); }
        .text-primary-100   { color: #e0e7ff; }
        .bg-primary-50      { background-color: var(--primary-50); }
        .bg-primary-100     { background-color: var(--primary-100); }
        .bg-primary-500     { background-color: #667eea; }
        .bg-primary-600     { background-color: var(--primary-600); }
        .bg-primary-700     { background-color: var(--primary-700); }
        .border-primary-200 { border-color: var(--primary-200); }
        .border-primary-600 { border-color: var(--primary-600); }
        .from-primary-500   { --tw-gradient-from: #667eea; }
        .from-primary-600   { --tw-gradient-from: var(--primary-600); }
        .to-primary-600     { --tw-gradient-to: var(--primary-600); }
        .to-primary-700     { --tw-gradient-to: var(--primary-700); }
        .ring-primary-500   { --tw-ring-color: var(--primary-500); }
        .hover\:text-primary-600:hover { color: var(--primary-600); }
        .hover\:bg-primary-700:hover   { background-color: var(--primary-700); }
        .focus\:ring-primary-500:focus { --tw-ring-color: var(--primary-500); }

        /* ============================================================
           BUTTON SYSTEM
           ============================================================ */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.625rem 1.25rem;
            font-size: 0.875rem;
            font-weight: 600;
            border-radius: 0.75rem;
            transition: all 0.2s ease;
            cursor: pointer;
            border: none;
            text-decoration: none;
            -webkit-user-select: none;
            user-select: none;
        }

        .btn:active {
            transform: scale(0.97);
        }

        .btn-primary {
            background-color: var(--primary-600);
            color: #ffffff;
        }

        .btn-primary:hover {
            background-color: var(--primary-700);
        }

        .btn-secondary {
            background-color: #f3f4f6;
            color: #374151;
            border: 1px solid #e5e7eb;
        }

        .btn-secondary:hover {
            background-color: #e5e7eb;
        }

        .btn-secondary:disabled,
        .btn[disabled] {
            opacity: 0.4;
            cursor: not-allowed;
            pointer-events: none;
        }

        .btn-danger {
            background-color: #fee2e2;
            color: #dc2626;
        }

        .btn-danger:hover {
            background-color: #fecaca;
        }

        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.75rem;
            border-radius: 0.5rem;
        }

        .btn-lg {
            padding: 0.875rem 1.5rem;
            font-size: 1rem;
        }

        /* ============================================================
           FORM SYSTEM
           ============================================================ */
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.375rem;
        }

        .form-label {
            font-size: 0.875rem;
            font-weight: 600;
            color: #374151;
        }

        .form-input,
        .form-textarea,
        .form-select {
            width: 100%;
            padding: 0.75rem 1rem;
            background-color: #ffffff;
            border: 1.5px solid #d1d5db;
            border-radius: 0.75rem;
            font-size: 16px; /* Prevents iOS zoom */
            color: #111827;
            transition: border-color 0.15s, box-shadow 0.15s;
            outline: none;
            font-family: inherit;
        }

        .form-input:focus,
        .form-textarea:focus,
        .form-select:focus {
            border-color: var(--primary-600);
            box-shadow: 0 0 0 3px rgba(90, 103, 216, 0.15);
        }

        .form-textarea {
            resize: vertical;
            min-height: 100px;
        }

        .form-hint {
            font-size: 0.75rem;
            color: #6b7280;
        }

        .form-error {
            font-size: 0.75rem;
            color: #dc2626;
            font-weight: 500;
        }

        .form-error.hidden {
            display: none;
        }

        /* ============================================================
           STEP WIZARD SYSTEM
           ============================================================ */
        .step-item {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .step-circle {
            width: 2rem;
            height: 2rem;
            border-radius: 50%;
            background-color: #e5e7eb;
            color: #9ca3af;
            font-size: 0.875rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .step-item.active .step-circle {
            background-color: var(--primary-600);
            color: #ffffff;
            box-shadow: 0 0 0 4px rgba(90, 103, 216, 0.2);
        }

        .step-item.completed .step-circle {
            background-color: #10b981;
            color: #ffffff;
        }

        .step-line {
            flex: 1;
            height: 2px;
            background-color: #e5e7eb;
            border-radius: 1px;
            transition: background-color 0.3s ease;
        }

        .step-line.completed {
            background-color: #10b981;
        }

        /* Form step display */
        .form-step {
            display: none;
        }

        .form-step.active {
            display: block;
        }

        /* ============================================================
           CARD SYSTEM
           ============================================================ */
        .card {
            background-color: #ffffff;
            border-radius: 1rem;
            padding: 1rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
            border: 1px solid #e5e7eb;
        }

        .card-lg {
            padding: 1.5rem;
        }

        /* ============================================================
           BADGE / CHIP SYSTEM
           ============================================================ */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.25rem 0.625rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .filter-chip, .filter-tab {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.5rem 1rem;
            background-color: white;
            border: 1.5px solid #e5e7eb;
            border-radius: 0.75rem;
            font-size: 0.875rem;
            font-weight: 500;
            color: #6b7280;
            white-space: nowrap;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .filter-chip:active,
        .filter-tab:active {
            transform: scale(0.95);
        }

        .filter-chip.active,
        .filter-tab.active {
            background-color: var(--primary-600);
            color: white;
            border-color: var(--primary-600);
        }

        /* ============================================================
           SCROLLBAR UTILITIES
           ============================================================ */
        .scrollbar-hide {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        .scrollbar-hide::-webkit-scrollbar {
            display: none;
        }

        /* ============================================================
           TOAST NOTIFICATIONS
           ============================================================ */
        .toast {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            background-color: #ffffff;
            border-radius: 0.875rem;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
            padding: 1rem;
            border-left: 4px solid transparent;
            animation: toastIn 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .toast-success { border-left-color: #10b981; }
        .toast-success i { color: #10b981; font-size: 1.125rem; }
        .toast-error { border-left-color: #ef4444; }
        .toast-error i { color: #ef4444; font-size: 1.125rem; }

        @keyframes toastIn {
            from {
                opacity: 0;
                transform: translateY(-12px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        @keyframes toastOut {
            from {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
            to {
                opacity: 0;
                transform: translateY(-12px) scale(0.95);
            }
        }

        /* ============================================================
           LOADING OVERLAY
           ============================================================ */
        #loading-overlay {
            display: none;
        }

        #loading-overlay.show {
            display: flex;
        }

        /* ============================================================
           UTILITY CLASSES
           ============================================================ */
        .truncate-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .touch-action-manipulation {
            touch-action: manipulation;
        }

        /* Line clamp utility */
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        /* Prevent content overflow */
        .safe-content {
            max-width: 100%;
            overflow-x: hidden;
            word-break: break-word;
        }
    </style>

    @stack('styles')
</head>
<body class="bg-gray-100 antialiased safe-content">

    {{-- Toast Container --}}
    <div id="toast-container" class="fixed top-4 right-4 left-4 z-50 space-y-2 pointer-events-none">
        @if(session('success'))
            <div class="toast toast-success pointer-events-auto">
                <i class="fas fa-check-circle"></i>
                <span class="text-sm font-medium text-gray-800">{{ session('success') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div class="toast toast-error pointer-events-auto">
                <i class="fas fa-exclamation-circle"></i>
                <span class="text-sm font-medium text-gray-800">{{ session('error') }}</span>
            </div>
        @endif

        @if($errors->any())
            <div class="toast toast-error pointer-events-auto">
                <i class="fas fa-exclamation-circle flex-shrink-0"></i>
                <div class="flex-1 min-w-0">
                    @foreach($errors->all() as $error)
                        <div class="text-sm text-gray-800">{{ $error }}</div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    {{-- Main Content — pb-20 clears 80px bottom nav --}}
    <main class="min-h-screen pb-24">
        @yield('content')
    </main>

    {{-- Bottom Navigation (Mobile — Students only) --}}
    @auth
        @if(auth()->user()->isStudent())
            @include('components.bottom-nav')
        @endif
    @endauth

    {{-- ShreeRam AI Chatbot (Students only) --}}
    @auth
        @if(auth()->user()->isStudent())
            @include('components.chatbot')
        @endif
    @endauth

    {{-- Loading Overlay --}}
    <div id="loading-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-[100] flex items-center justify-center">
        <div class="bg-white rounded-2xl p-6 flex flex-col items-center gap-3 mx-4 shadow-2xl">
            <div class="w-12 h-12 border-4 border-gray-200 border-t-primary-600 rounded-full animate-spin"></div>
            <span class="text-gray-700 font-semibold text-sm">Please wait...</span>
        </div>
    </div>

    <script>
        // Toast auto-dismiss
        document.addEventListener('DOMContentLoaded', function() {
            const toasts = document.querySelectorAll('.toast');
            toasts.forEach(toast => {
                setTimeout(() => {
                    toast.style.animation = 'toastOut 0.3s ease-out forwards';
                    setTimeout(() => toast.remove(), 300);
                }, 4500);
            });
        });

        // Loading overlay helpers
        window.showLoading = function() {
            document.getElementById('loading-overlay').classList.add('show');
        };

        window.hideLoading = function() {
            document.getElementById('loading-overlay').classList.remove('show');
        };

        // Auto-show loading on form submit (skip data-no-loading forms)
        document.addEventListener('submit', function(e) {
            if (!e.target.hasAttribute('data-no-loading')) {
                showLoading();
            }
        });
    </script>

    @stack('scripts')
</body>
</html>
