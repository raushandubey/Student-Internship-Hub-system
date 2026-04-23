<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'InternshipHub') }} – Recruiter</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            min-height: 100vh;
            color: #e0e0e0;
        }
        .recruiter-nav {
            background: rgba(255,255,255,0.07);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255,255,255,0.1);
            position: sticky; top: 0; z-index: 1000;
        }
        .nav-inner {
            max-width: 1400px; margin: 0 auto;
            padding: 0 2rem;
            display: flex; align-items: center; justify-content: space-between;
            height: 70px;
        }
        .nav-brand { display: flex; align-items: center; gap: .75rem; text-decoration: none; }
        .brand-icon {
            width: 42px; height: 42px;
            background: linear-gradient(135deg, #e94560, #c62a47);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
        }
        .brand-icon i { color: #fff; font-size: 1.2rem; }
        .brand-text { color: #fff; font-size: 1.4rem; font-weight: 800; }
        .brand-badge {
            background: rgba(233,69,96,0.2);
            color: #e94560;
            font-size: .65rem; font-weight: 700;
            padding: .2rem .5rem; border-radius: 20px;
            border: 1px solid rgba(233,69,96,0.3);
            text-transform: uppercase; letter-spacing: .05em;
        }
        .nav-links { display: flex; align-items: center; gap: .5rem; }
        .nav-link {
            color: rgba(255,255,255,.8); text-decoration: none;
            padding: .5rem 1rem; border-radius: 8px;
            font-size: .9rem; font-weight: 500;
            transition: all .2s;
        }
        .nav-link:hover, .nav-link.active {
            background: rgba(255,255,255,.1); color: #fff;
        }
        .nav-link i { margin-right: .4rem; }
        .logout-btn {
            background: rgba(233,69,96,.15);
            border: 1px solid rgba(233,69,96,.3);
            color: #e94560; padding: .5rem 1rem;
            border-radius: 8px; font-size: .9rem; font-weight: 600;
            cursor: pointer; transition: all .2s;
        }
        .logout-btn:hover { background: rgba(233,69,96,.3); }

        .main-content { max-width: 1400px; margin: 0 auto; padding: 2rem; }

        /* Flash messages */
        .flash { padding: .9rem 1.2rem; border-radius: 10px; margin-bottom: 1.5rem; font-size: .9rem; }
        .flash-success { background: rgba(40,167,69,.15); border: 1px solid rgba(40,167,69,.3); color: #6fcf97; }
        .flash-error   { background: rgba(220,53,69,.15);  border: 1px solid rgba(220,53,69,.3);  color: #eb5757; }

        @media (max-width: 768px) {
            .nav-inner { padding: 0 1rem; }
            .nav-links .nav-link span { display: none; }
            .main-content { padding: 1rem; }
        }
    </style>
    @stack('styles')
</head>
<body>
<nav class="recruiter-nav">
    <div class="nav-inner">
        <a href="{{ route('recruiter.dashboard') }}" class="nav-brand">
            <div class="brand-icon"><i class="fas fa-user-tie"></i></div>
            <span class="brand-text">InternshipHub</span>
            <span class="brand-badge">Recruiter</span>
        </a>

        <div class="nav-links">
            <a href="{{ route('recruiter.dashboard') }}"
               class="nav-link {{ request()->routeIs('recruiter.dashboard') ? 'active' : '' }}">
                <i class="fas fa-tachometer-alt"></i><span>Dashboard</span>
            </a>
            <a href="{{ route('recruiter.internships.index') }}"
               class="nav-link {{ request()->routeIs('recruiter.internships.*') ? 'active' : '' }}">
                <i class="fas fa-briefcase"></i><span>Internships</span>
            </a>
            <a href="{{ route('recruiter.applications.index') }}"
               class="nav-link {{ request()->routeIs('recruiter.applications.*') ? 'active' : '' }}">
                <i class="fas fa-users"></i><span>Applications</span>
            </a>
            <a href="{{ route('recruiter.analytics') }}"
               class="nav-link {{ request()->routeIs('recruiter.analytics') ? 'active' : '' }}">
                <i class="fas fa-chart-bar"></i><span>Analytics</span>
            </a>
            <a href="{{ route('recruiter.profile.show') }}"
               class="nav-link {{ request()->routeIs('recruiter.profile.*') ? 'active' : '' }}">
                <i class="fas fa-user-circle"></i><span>Profile</span>
            </a>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </button>
            </form>
        </div>
    </div>
</nav>

<main class="main-content">
    @if(session('success'))
        <div class="flash flash-success"><i class="fas fa-check-circle me-2"></i>{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="flash flash-error"><i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="flash flash-error">
            <ul style="list-style:none;padding:0;margin:0">
                @foreach($errors->all() as $e)<li><i class="fas fa-times-circle me-1"></i>{{ $e }}</li>@endforeach
            </ul>
        </div>
    @endif

    @yield('content')
</main>

@stack('scripts')
</body>
</html>
