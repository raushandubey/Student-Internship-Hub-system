<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, user-scalable=yes">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#5a67d8">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    
    <title>{{ config('app.name', 'InternshipHub') }}</title>
    
    <!-- Preconnect to external resources -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <style>
        /* Mobile-first base styles */
        * {
            -webkit-tap-highlight-color: transparent;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        
        /* Prevent zoom on input focus (iOS) */
        input, select, textarea {
            font-size: 16px !important;
        }
        
        /* Safe area support */
        @supports (padding: env(safe-area-inset-bottom)) {
            .pb-safe {
                padding-bottom: env(safe-area-inset-bottom);
            }
        }
    </style>
    
    @stack('styles')
</head>
<body class="bg-gray-50 antialiased">
    {{-- Toast Container --}}
    <div id="toast-container" class="fixed top-4 right-4 left-4 z-50 space-y-2 pointer-events-none">
        @if(session('success'))
            <div class="toast toast-success pointer-events-auto">
                <i class="fas fa-check-circle"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif
        
        @if(session('error'))
            <div class="toast toast-error pointer-events-auto">
                <i class="fas fa-exclamation-circle"></i>
                <span>{{ session('error') }}</span>
            </div>
        @endif
        
        @if($errors->any())
            <div class="toast toast-error pointer-events-auto">
                <i class="fas fa-exclamation-circle"></i>
                <div>
                    @foreach($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    {{-- Main Content --}}
    <main class="min-h-screen pb-20">
        {{-- pb-20 = 80px clearance for bottom nav --}}
        @yield('content')
    </main>

    {{-- Bottom Navigation (Mobile Only) --}}
    @auth
        @if(auth()->user()->isStudent())
            @include('components.bottom-nav')
        @endif
    @endauth

    {{-- Loading Overlay --}}
    <div id="loading-overlay" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
        <div class="bg-white rounded-2xl p-6 flex flex-col items-center gap-3">
            <div class="w-12 h-12 border-4 border-primary-200 border-t-primary-600 rounded-full animate-spin"></div>
            <span class="text-gray-700 font-medium">Loading...</span>
        </div>
    </div>

    <script>
        // Toast auto-dismiss
        document.addEventListener('DOMContentLoaded', function() {
            const toasts = document.querySelectorAll('.toast');
            toasts.forEach(toast => {
                setTimeout(() => {
                    toast.style.animation = 'slideOut 0.3s ease-out forwards';
                    setTimeout(() => toast.remove(), 300);
                }, 5000);
            });
        });

        // Loading overlay helper
        window.showLoading = function() {
            document.getElementById('loading-overlay').classList.remove('hidden');
        };

        window.hideLoading = function() {
            document.getElementById('loading-overlay').classList.add('hidden');
        };

        // Show loading on form submit
        document.addEventListener('submit', function(e) {
            if (!e.target.hasAttribute('data-no-loading')) {
                showLoading();
            }
        });
    </script>

    <style>
        /* Toast Styles */
        .toast {
            @apply flex items-center gap-3 bg-white rounded-xl shadow-lg p-4 border-l-4;
            animation: slideIn 0.3s ease-out;
        }

        .toast-success {
            @apply border-green-500;
        }

        .toast-success i {
            @apply text-green-500 text-xl;
        }

        .toast-error {
            @apply border-red-500;
        }

        .toast-error i {
            @apply text-red-500 text-xl;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(100%);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes slideOut {
            from {
                opacity: 1;
                transform: translateX(0);
            }
            to {
                opacity: 0;
                transform: translateX(100%);
            }
        }

        /* Primary color utilities */
        .bg-primary-600 {
            background-color: #5a67d8;
        }

        .text-primary-600 {
            color: #5a67d8;
        }

        .border-primary-600 {
            border-color: #5a67d8;
        }

        .border-primary-200 {
            border-color: #d6e0ff;
        }
    </style>

    @stack('scripts')
</body>
</html>
