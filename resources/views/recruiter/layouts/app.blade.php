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
        /* ── Reset & Base ─────────────────────────────────────── */
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            min-height: 100vh;
            color: #e0e0e0;
            -webkit-font-smoothing: antialiased;
        }

        /* ── Top Navbar ────────────────────────────────────────── */
        .recruiter-nav {
            background: rgba(255,255,255,0.07);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255,255,255,0.1);
            position: sticky; top: 0; z-index: 1000;
        }
        .nav-inner {
            max-width: 1400px; margin: 0 auto;
            padding: 0 2rem;
            display: flex; align-items: center; justify-content: space-between;
            height: 64px;
        }
        .nav-brand { display: flex; align-items: center; gap: .75rem; text-decoration: none; flex-shrink: 0; }
        .brand-icon {
            width: 40px; height: 40px;
            background: linear-gradient(135deg, #e94560, #c62a47);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .brand-icon i { color: #fff; font-size: 1.1rem; }
        .brand-text { color: #fff; font-size: 1.3rem; font-weight: 800; }
        .brand-badge {
            background: rgba(233,69,96,0.2);
            color: #e94560;
            font-size: .6rem; font-weight: 700;
            padding: .18rem .45rem; border-radius: 20px;
            border: 1px solid rgba(233,69,96,0.3);
            text-transform: uppercase; letter-spacing: .06em;
        }

        /* Desktop nav links */
        .nav-links { display: flex; align-items: center; gap: .25rem; }
        .nav-link {
            color: rgba(255,255,255,.75); text-decoration: none;
            padding: .45rem .85rem; border-radius: 8px;
            font-size: .875rem; font-weight: 500;
            transition: all .2s; display: flex; align-items: center; gap: .4rem;
            white-space: nowrap;
        }
        .nav-link:hover, .nav-link.active {
            background: rgba(255,255,255,.1); color: #fff;
        }
        .nav-link.active { background: rgba(233,69,96,.15); color: #e94560; }
        .logout-btn {
            background: rgba(233,69,96,.12);
            border: 1px solid rgba(233,69,96,.25);
            color: #e94560; padding: .45rem .9rem;
            border-radius: 8px; font-size: .875rem; font-weight: 600;
            cursor: pointer; transition: all .2s; display: flex; align-items: center; gap: .4rem;
        }
        .logout-btn:hover { background: rgba(233,69,96,.25); }

        /* Hamburger (mobile) */
        .hamburger-btn {
            display: none;
            background: rgba(255,255,255,.08);
            border: 1px solid rgba(255,255,255,.12);
            color: #fff; width: 40px; height: 40px;
            border-radius: 10px; cursor: pointer;
            align-items: center; justify-content: center;
            font-size: 1.1rem; transition: all .2s; flex-shrink: 0;
        }
        .hamburger-btn:hover { background: rgba(255,255,255,.15); }

        /* Mobile Drawer */
        .mobile-drawer {
            display: none;
            position: fixed; inset: 0; z-index: 2000;
        }
        .mobile-drawer.open { display: block; }
        .drawer-overlay {
            position: absolute; inset: 0;
            background: rgba(0,0,0,.6); backdrop-filter: blur(4px);
            animation: fadeIn .2s ease;
        }
        .drawer-panel {
            position: absolute; top: 0; right: 0; bottom: 0;
            width: min(300px, 85vw);
            background: linear-gradient(180deg, #1e1e3a 0%, #16213e 100%);
            border-left: 1px solid rgba(255,255,255,.1);
            display: flex; flex-direction: column;
            animation: slideInRight .25s ease;
            overflow-y: auto;
        }
        .drawer-header {
            display: flex; align-items: center; justify-content: space-between;
            padding: 1.25rem 1.25rem;
            border-bottom: 1px solid rgba(255,255,255,.08);
        }
        .drawer-title { color: #fff; font-weight: 700; font-size: 1rem; }
        .drawer-close {
            background: rgba(255,255,255,.08); border: none; color: #fff;
            width: 34px; height: 34px; border-radius: 8px; cursor: pointer;
            display: flex; align-items: center; justify-content: center; font-size: 1rem;
            transition: all .2s;
        }
        .drawer-close:hover { background: rgba(255,255,255,.15); }
        .drawer-nav { padding: 1rem; flex: 1; }
        .drawer-link {
            display: flex; align-items: center; gap: .75rem;
            color: rgba(255,255,255,.75); text-decoration: none;
            padding: .75rem 1rem; border-radius: 10px;
            font-size: .9rem; font-weight: 500;
            transition: all .2s; margin-bottom: .25rem;
        }
        .drawer-link:hover, .drawer-link.active {
            background: rgba(255,255,255,.08); color: #fff;
        }
        .drawer-link.active { background: rgba(233,69,96,.15); color: #e94560; }
        .drawer-link .drawer-icon {
            width: 34px; height: 34px; border-radius: 8px;
            background: rgba(255,255,255,.06);
            display: flex; align-items: center; justify-content: center;
            font-size: .85rem; flex-shrink: 0;
        }
        .drawer-link.active .drawer-icon { background: rgba(233,69,96,.2); }
        .drawer-footer {
            padding: 1rem;
            border-top: 1px solid rgba(255,255,255,.08);
        }
        .drawer-logout {
            display: flex; align-items: center; gap: .75rem;
            width: 100%; background: rgba(233,69,96,.12);
            border: 1px solid rgba(233,69,96,.25); color: #e94560;
            padding: .75rem 1rem; border-radius: 10px;
            font-size: .9rem; font-weight: 600; cursor: pointer;
            transition: all .2s;
        }
        .drawer-logout:hover { background: rgba(233,69,96,.25); }

        /* ── Bottom Nav (mobile only) ────────────────────────── */
        .bottom-nav {
            display: none;
            position: fixed; bottom: 0; left: 0; right: 0; z-index: 900;
            background: rgba(22,33,62,0.97);
            backdrop-filter: blur(20px);
            border-top: 1px solid rgba(255,255,255,.1);
            padding: .5rem .5rem env(safe-area-inset-bottom,.5rem);
        }
        .bottom-nav-inner {
            display: flex; justify-content: space-around; align-items: center;
        }
        .bn-item {
            display: flex; flex-direction: column; align-items: center; gap: .2rem;
            color: rgba(255,255,255,.5); text-decoration: none;
            padding: .4rem .5rem; border-radius: 10px;
            font-size: .6rem; font-weight: 600; text-transform: uppercase;
            letter-spacing: .03em; transition: all .2s; min-width: 52px;
        }
        .bn-item i { font-size: 1.1rem; }
        .bn-item.active { color: #e94560; }
        .bn-item:hover { color: rgba(255,255,255,.8); }

        /* ── Main Content ─────────────────────────────────────── */
        .main-content { max-width: 1400px; margin: 0 auto; padding: 2rem; }

        /* ── Flash Messages ──────────────────────────────────── */
        .flash { padding: .85rem 1.1rem; border-radius: 10px; margin-bottom: 1.5rem; font-size: .875rem; display: flex; align-items: center; gap: .5rem; }
        .flash-success { background: rgba(40,167,69,.12); border: 1px solid rgba(40,167,69,.25); color: #6fcf97; }
        .flash-error   { background: rgba(220,53,69,.12); border: 1px solid rgba(220,53,69,.25); color: #eb5757; }

        /* ── Animations ──────────────────────────────────────── */
        @keyframes fadeIn { from { opacity:0; } to { opacity:1; } }
        @keyframes slideInRight { from { transform:translateX(100%); } to { transform:translateX(0); } }

        /* ── Responsive ──────────────────────────────────────── */
        @media (max-width: 768px) {
            .nav-inner { padding: 0 1rem; }
            .nav-links { display: none; }
            .hamburger-btn { display: flex; }
            .main-content { padding: 1rem; padding-bottom: 6rem; }
            .bottom-nav { display: block; }
        }
        @media (min-width: 769px) {
            .brand-badge { display: inline-block; }
        }
    </style>
    @stack('styles')
</head>
<body>

<!-- ── Top Navbar ──────────────────────────────────────────────── -->
<nav class="recruiter-nav">
    <div class="nav-inner">
        <a href="{{ route('recruiter.dashboard') }}" class="nav-brand">
            <div class="brand-icon"><i class="fas fa-user-tie"></i></div>
            <span class="brand-text">InternshipHub</span>
            <span class="brand-badge">Recruiter</span>
        </a>

        <!-- Desktop Links -->
        <div class="nav-links">
            <a href="{{ route('recruiter.dashboard') }}"
               class="nav-link {{ request()->routeIs('recruiter.dashboard') ? 'active' : '' }}">
                <i class="fas fa-tachometer-alt"></i>Dashboard
            </a>
            <a href="{{ route('recruiter.internships.index') }}"
               class="nav-link {{ request()->routeIs('recruiter.internships.*') ? 'active' : '' }}">
                <i class="fas fa-briefcase"></i>Internships
            </a>
            <a href="{{ route('recruiter.applications.index') }}"
               class="nav-link {{ request()->routeIs('recruiter.applications.*') ? 'active' : '' }}">
                <i class="fas fa-users"></i>Applications
            </a>
            <a href="{{ route('recruiter.analytics') }}"
               class="nav-link {{ request()->routeIs('recruiter.analytics') ? 'active' : '' }}">
                <i class="fas fa-chart-bar"></i>Analytics
            </a>
            <a href="{{ route('recruiter.profile.show') }}"
               class="nav-link {{ request()->routeIs('recruiter.profile.*') ? 'active' : '' }}">
                <i class="fas fa-user-circle"></i>Profile
            </a>
            <form method="POST" action="{{ route('logout') }}" style="margin-left:.5rem">
                @csrf
                <button type="submit" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>Logout
                </button>
            </form>
        </div>

        <!-- Mobile Hamburger -->
        <button class="hamburger-btn" id="hamburgerBtn" aria-label="Open menu">
            <i class="fas fa-bars"></i>
        </button>
    </div>
</nav>

<!-- ── Mobile Drawer ───────────────────────────────────────────── -->
<div class="mobile-drawer" id="mobileDrawer" aria-hidden="true">
    <div class="drawer-overlay" id="drawerOverlay"></div>
    <div class="drawer-panel" role="dialog" aria-label="Navigation menu">
        <div class="drawer-header">
            <div class="nav-brand" style="text-decoration:none">
                <div class="brand-icon" style="width:32px;height:32px"><i class="fas fa-user-tie" style="font-size:.9rem"></i></div>
                <span class="drawer-title">Recruiter Panel</span>
            </div>
            <button class="drawer-close" id="drawerClose" aria-label="Close menu">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <nav class="drawer-nav">
            <a href="{{ route('recruiter.dashboard') }}"
               class="drawer-link {{ request()->routeIs('recruiter.dashboard') ? 'active' : '' }}">
                <div class="drawer-icon"><i class="fas fa-tachometer-alt"></i></div>
                Dashboard
            </a>
            <a href="{{ route('recruiter.internships.index') }}"
               class="drawer-link {{ request()->routeIs('recruiter.internships.*') ? 'active' : '' }}">
                <div class="drawer-icon"><i class="fas fa-briefcase"></i></div>
                Internships
            </a>
            <a href="{{ route('recruiter.applications.index') }}"
               class="drawer-link {{ request()->routeIs('recruiter.applications.*') ? 'active' : '' }}">
                <div class="drawer-icon"><i class="fas fa-users"></i></div>
                Applications
            </a>
            <a href="{{ route('recruiter.analytics') }}"
               class="drawer-link {{ request()->routeIs('recruiter.analytics') ? 'active' : '' }}">
                <div class="drawer-icon"><i class="fas fa-chart-bar"></i></div>
                Analytics
            </a>
            <a href="{{ route('recruiter.profile.show') }}"
               class="drawer-link {{ request()->routeIs('recruiter.profile.*') ? 'active' : '' }}">
                <div class="drawer-icon"><i class="fas fa-user-circle"></i></div>
                Profile
            </a>
        </nav>
        <div class="drawer-footer">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="drawer-logout">
                    <i class="fas fa-sign-out-alt"></i>Logout
                </button>
            </form>
        </div>
    </div>
</div>

<!-- ── Main Content ────────────────────────────────────────────── -->
<main class="main-content">
    @if(session('success'))
        <div class="flash flash-success"><i class="fas fa-check-circle"></i>{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="flash flash-error"><i class="fas fa-exclamation-circle"></i>{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="flash flash-error" style="flex-direction:column;align-items:flex-start">
            <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:.3rem">
                @foreach($errors->all() as $e)
                    <li><i class="fas fa-times-circle" style="margin-right:.4rem"></i>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @yield('content')
</main>

<!-- ── Bottom Navigation (Mobile) ─────────────────────────────── -->
<nav class="bottom-nav">
    <div class="bottom-nav-inner">
        <a href="{{ route('recruiter.dashboard') }}"
           class="bn-item {{ request()->routeIs('recruiter.dashboard') ? 'active' : '' }}">
            <i class="fas fa-tachometer-alt"></i>
            <span>Home</span>
        </a>
        <a href="{{ route('recruiter.internships.index') }}"
           class="bn-item {{ request()->routeIs('recruiter.internships.*') ? 'active' : '' }}">
            <i class="fas fa-briefcase"></i>
            <span>Jobs</span>
        </a>
        <a href="{{ route('recruiter.applications.index') }}"
           class="bn-item {{ request()->routeIs('recruiter.applications.*') ? 'active' : '' }}">
            <i class="fas fa-users"></i>
            <span>Apps</span>
        </a>
        <a href="{{ route('recruiter.analytics') }}"
           class="bn-item {{ request()->routeIs('recruiter.analytics') ? 'active' : '' }}">
            <i class="fas fa-chart-bar"></i>
            <span>Stats</span>
        </a>
        <a href="{{ route('recruiter.profile.show') }}"
           class="bn-item {{ request()->routeIs('recruiter.profile.*') ? 'active' : '' }}">
            <i class="fas fa-user-circle"></i>
            <span>Profile</span>
        </a>
    </div>
</nav>

@stack('scripts')
<script>
// ── Mobile Drawer Toggle ──────────────────────────────────────
const drawer   = document.getElementById('mobileDrawer');
const overlay  = document.getElementById('drawerOverlay');
const openBtn  = document.getElementById('hamburgerBtn');
const closeBtn = document.getElementById('drawerClose');

function openDrawer()  { drawer.classList.add('open'); drawer.setAttribute('aria-hidden','false'); document.body.style.overflow='hidden'; }
function closeDrawer() { drawer.classList.remove('open'); drawer.setAttribute('aria-hidden','true'); document.body.style.overflow=''; }

openBtn.addEventListener('click', openDrawer);
closeBtn.addEventListener('click', closeDrawer);
overlay.addEventListener('click', closeDrawer);
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeDrawer(); });
</script>
</body>
</html>
