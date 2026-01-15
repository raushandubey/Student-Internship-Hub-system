<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Laravel') }}</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }
        
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="rgba(255,255,255,0.05)" stroke-width="1"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
            pointer-events: none;
            z-index: 1;
        }
        
        .app-container {
            position: relative;
            z-index: 2;
        }
        
        /* Navigation Styles */
        .modern-nav {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
            transition: all 0.3s ease;
        }
        
        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
        }
        
        .nav-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 80px;
        }
        
        .nav-brand {
            display: flex;
            align-items: center;
            gap: 1rem;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .nav-brand:hover {
            transform: translateY(-2px);
        }
        
        .brand-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #007bff, #0056b3);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 25px rgba(0, 123, 255, 0.3);
        }
        
        .brand-icon i {
            color: white;
            font-size: 1.5rem;
        }
        
        .brand-text {
            color: white;
            font-size: 1.75rem;
            font-weight: 800;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .nav-menu {
            display: flex;
            align-items: center;
            gap: 2rem;
        }
        
        .nav-link {
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            font-weight: 500;
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .nav-link::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.1);
            transition: left 0.3s ease;
        }
        
        .nav-link:hover::before {
            left: 0;
        }
        
        .nav-link:hover {
            color: white;
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .nav-link.active {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
        }
        
        .logout-btn {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.3);
        }
        
        .logout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(220, 53, 69, 0.4);
        }
        
        /* Mobile Menu */
        .mobile-menu-btn {
            display: none;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            padding: 0.75rem;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .mobile-menu {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding: 1rem 2rem;
            animation: slideDown 0.3s ease;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .mobile-menu.active {
            display: block;
        }
        
        .mobile-nav-link {
            display: block;
            color: white;
            text-decoration: none;
            padding: 1rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }
        
        .mobile-nav-link:hover {
            color: #007bff;
            padding-left: 1rem;
        }
        
        /* Alert Styles */
        .alert-container {
            position: fixed;
            top: 100px;
            right: 2rem;
            z-index: 2000;
            max-width: 400px;
        }
        
        .alert {
            margin-bottom: 1rem;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(10px);
            animation: slideInRight 0.5s ease;
            position: relative;
            overflow: hidden;
        }
        
        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(100px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        .alert::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
        }
        
        .alert-success {
            background: rgba(40, 167, 69, 0.9);
            border: 1px solid rgba(40, 167, 69, 0.3);
            color: white;
        }
        
        .alert-success::before {
            background: #28a745;
        }
        
        .alert-error {
            background: rgba(220, 53, 69, 0.9);
            border: 1px solid rgba(220, 53, 69, 0.3);
            color: white;
        }
        
        .alert-error::before {
            background: #dc3545;
        }
        
        .alert-content {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
        }
        
        .alert-icon {
            font-size: 1.5rem;
            flex-shrink: 0;
            margin-top: 0.1rem;
        }
        
        .alert-text {
            flex: 1;
        }
        
        .alert-text ul {
            list-style: none;
            margin: 0;
            padding: 0;
        }
        
        .alert-text li {
            margin-bottom: 0.5rem;
            position: relative;
            padding-left: 1rem;
        }
        
        .alert-text li::before {
            content: '•';
            position: absolute;
            left: 0;
            color: rgba(255, 255, 255, 0.8);
        }
        
        .alert-close {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            background: none;
            border: none;
            color: rgba(255, 255, 255, 0.8);
            cursor: pointer;
            font-size: 1.2rem;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.3s ease;
        }
        
        .alert-close:hover {
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }
        
        /* Main Content */
        .main-content {
            padding: 2rem 0;
            position: relative;
            z-index: 2;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .nav-container {
                padding: 0 1rem;
            }
            
            .nav-menu {
                display: none;
            }
            
            .mobile-menu-btn {
                display: block;
            }
            
            .brand-text {
                font-size: 1.5rem;
            }
            
            .alert-container {
                right: 1rem;
                left: 1rem;
                max-width: none;
            }
            
            .main-content {
                padding: 1rem 0;
            }
        }
        
        @media (max-width: 576px) {
            .nav-content {
                height: 70px;
            }
            
            .brand-icon {
                width: 40px;
                height: 40px;
            }
            
            .brand-icon i {
                font-size: 1.2rem;
            }
            
            .brand-text {
                font-size: 1.3rem;
            }
        }
        
        /* Scroll Effects */
        .nav-scrolled {
            background: rgba(255, 255, 255, 0.15);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        /* Loading Animation */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(102, 126, 234, 0.9);
            backdrop-filter: blur(10px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        
        .loading-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        
        .loader {
            width: 50px;
            height: 50px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-top: 3px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loader"></div>
    </div>
    
    <div class="app-container">
        <!-- Navigation -->
        <nav class="modern-nav" id="mainNav">
            <div class="nav-container">
                <div class="nav-content">
                    <a href="{{ route('dashboard') }}" class="nav-brand">
                        <div class="brand-icon">
                            <i class="fas fa-briefcase"></i>
                        </div>
                        <span class="brand-text">InternshipHub</span>
                    </a>
                    
                    @auth
                    <!-- Desktop Menu -->
                    <div class="nav-menu">
                        @if(auth()->user()->isStudent())
                            <a href="{{ route('profile.show') }}" class="nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }}">
                                <i class="fas fa-user me-2"></i>Profile
                            </a>
                            <a href="{{ route('recommendations.index') }}" class="nav-link {{ request()->routeIs('recommendations.*') ? 'active' : '' }}">
                                <i class="fas fa-star me-2"></i>Recommendations
                            </a>
                            <a href="{{ route('my-applications') }}" class="nav-link {{ request()->routeIs('my-applications') ? 'active' : '' }}">
                                <i class="fas fa-clipboard-list me-2"></i>My Applications
                            </a>
                        @endif
                        
                        @if(auth()->user()->isAdmin())
                            <a href="{{ route('internships.index') }}" class="nav-link">
                                <i class="fas fa-cogs me-2"></i>Manage Internships
                            </a>
                        @endif
                        
                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit" class="logout-btn">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </button>
                        </form>
                    </div>
                    
                    <!-- Mobile Menu Button -->
                    <button class="mobile-menu-btn" id="mobileMenuBtn">
                        <i class="fas fa-bars"></i>
                    </button>
                    @endauth
                </div>
                
                @auth
                <!-- Mobile Menu -->
                <div class="mobile-menu" id="mobileMenu">
                    @if(auth()->user()->isStudent())
                        <a href="{{ route('profile.show') }}" class="mobile-nav-link">
                            <i class="fas fa-user me-2"></i>Profile
                        </a>
                        <a href="{{ route('recommendations.index') }}" class="mobile-nav-link">
                            <i class="fas fa-star me-2"></i>Recommendations
                        </a>
                        <a href="{{ route('my-applications') }}" class="mobile-nav-link">
                            <i class="fas fa-clipboard-list me-2"></i>My Applications
                        </a>
                    @endif
                    
                    @if(auth()->user()->isAdmin())
                        <a href="{{ route('internships.index') }}" class="mobile-nav-link">
                            <i class="fas fa-cogs me-2"></i>Manage Internships
                        </a>
                    @endif
                    
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="mobile-nav-link" style="border: none; background: none; width: 100%; text-align: left;">
                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                        </button>
                    </form>
                </div>
                @endauth
            </div>
        </nav>

        <!-- Alert Container -->
        <div class="alert-container" id="alertContainer">
            @if(session('success'))
                <div class="alert alert-success">
                    <button class="alert-close" onclick="closeAlert(this.parentElement)">×</button>
                    <div class="alert-content">
                        <i class="fas fa-check-circle alert-icon"></i>
                        <div class="alert-text">{{ session('success') }}</div>
                    </div>
                </div>
            @endif
            
            @if($errors->any())
                <div class="alert alert-error">
                    <button class="alert-close" onclick="closeAlert(this.parentElement)">×</button>
                    <div class="alert-content">
                        <i class="fas fa-exclamation-circle alert-icon"></i>
                        <div class="alert-text">
                            <ul>
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Main Content -->
        <main class="main-content">
            @yield('content')
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Mobile menu toggle
            const mobileMenuBtn = document.getElementById('mobileMenuBtn');
            const mobileMenu = document.getElementById('mobileMenu');
            
            if (mobileMenuBtn) {
                mobileMenuBtn.addEventListener('click', function() {
                    mobileMenu.classList.toggle('active');
                    const icon = this.querySelector('i');
                    icon.classList.toggle('fa-bars');
                    icon.classList.toggle('fa-times');
                });
            }
            
            // Navigation scroll effect
            const mainNav = document.getElementById('mainNav');
            let lastScrollY = window.scrollY;
            
            window.addEventListener('scroll', function() {
                if (window.scrollY > 50) {
                    mainNav.classList.add('nav-scrolled');
                } else {
                    mainNav.classList.remove('nav-scrolled');
                }
                lastScrollY = window.scrollY;
            });
            
            // Auto-close alerts
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    closeAlert(alert);
                }, 5000);
            });
            
            // Loading overlay for form submissions
            const forms = document.querySelectorAll('form');
            const loadingOverlay = document.getElementById('loadingOverlay');
            
            forms.forEach(form => {
                form.addEventListener('submit', function() {
                    if (loadingOverlay) {
                        loadingOverlay.classList.add('active');
                    }
                });
            });
            
            // Smooth scrolling for internal links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });
        });
        
        // Alert functions
        function closeAlert(alert) {
            alert.style.animation = 'slideOutRight 0.3s ease forwards';
            setTimeout(() => {
                alert.remove();
            }, 300);
        }
        
        // Add slide out animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideOutRight {
                to {
                    opacity: 0;
                    transform: translateX(100px);
                }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>
