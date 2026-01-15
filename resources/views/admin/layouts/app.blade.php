{{-- resources/views/admin/layouts/app.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') - Admin Dashboard</title>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('css/admin.css') }}" rel="stylesheet">
    
    @yield('styles')
</head>
<body>
    <div class="admin-wrapper">
        <!-- Sidebar -->
        <nav class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-brand">
                    <i class="bi bi-briefcase-fill"></i>
                    <span>InternshipHub</span>
                </div>
                <button class="sidebar-toggle" id="sidebar-toggle">
                    <i class="bi bi-list"></i>
                </button>
            </div>

            <div class="sidebar-menu">
                <ul class="nav">
                    <li class="nav-item">
                        <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                            <i class="bi bi-speedometer2"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.internships.index') }}" class="nav-link {{ request()->routeIs('admin.internships.*') ? 'active' : '' }}">
                            <i class="bi bi-briefcase"></i>
                            <span>Internships</span>
                            <span class="badge bg-primary ms-auto">{{ \App\Models\Internship::count() }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.users.index') }}" class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                            <i class="bi bi-people"></i>
                            <span>Users</span>
                            <span class="badge bg-info ms-auto">{{ \App\Models\User::count() }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.analytics') }}" class="nav-link {{ request()->routeIs('admin.analytics') ? 'active' : '' }}">
                            <i class="bi bi-graph-up"></i>
                            <span>Analytics</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.settings') }}" class="nav-link {{ request()->routeIs('admin.settings') ? 'active' : '' }}">
                            <i class="bi bi-gear"></i>
                            <span>Settings</span>
                        </a>
                    </li>
                </ul>
            </div>

            <div class="sidebar-footer">
                <div class="admin-profile">
                    <img src="{{ auth('admin')->user()->avatar ? Storage::url(auth('admin')->user()->avatar) : asset('images/default-avatar.png') }}" alt="Admin" class="admin-avatar">
                    <div class="admin-info">
                        <div class="admin-name">{{ auth('admin')->user()->name }}</div>
                        <div class="admin-role">Administrator</div>
                    </div>
                    <div class="dropdown">
                        <button class="btn btn-link" data-bs-toggle="dropdown">
                            <i class="bi bi-three-dots-vertical"></i>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="{{ route('admin.settings') }}"><i class="bi bi-person"></i> Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form action="{{ route('admin.logout') }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-danger">
                                        <i class="bi bi-box-arrow-right"></i> Logout
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <header class="main-header">
                <div class="header-left">
                    <button class="btn btn-link sidebar-toggle-btn" id="sidebar-toggle-btn">
                        <i class="bi bi-list"></i>
                    </button>
                    <h1 class="page-title">@yield('page-title')</h1>
                </div>
                
                <div class="header-right">
                    <div class="header-actions">
                        <!-- Notifications -->
                        <div class="dropdown">
                            <button class="btn btn-link header-action" data-bs-toggle="dropdown">
                                <i class="bi bi-bell"></i>
                                <span class="badge bg-danger">5</span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end notifications-dropdown">
                                <li class="dropdown-header">Notifications</li>
                                <li><a class="dropdown-item" href="#"><i class="bi bi-info-circle text-info"></i> New user registered</a></li>
                                <li><a class="dropdown-item" href="#"><i class="bi bi-briefcase text-success"></i> New internship posted</a></li>
                                <li><a class="dropdown-item" href="#"><i class="bi bi-exclamation-triangle text-warning"></i> System alert</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-center" href="#">View all notifications</a></li>
                            </ul>
                        </div>

                        <!-- Quick Add -->
                        <div class="dropdown">
                            <button class="btn btn-primary" data-bs-toggle="dropdown">
                                <i class="bi bi-plus"></i> Add New
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="{{ route('internships.create') }}"><i class="bi bi-briefcase"></i> Internship</a></li>
                                <li><a class="dropdown-item" href="#"><i class="bi bi-person-plus"></i> Admin User</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="page-content">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle me-2"></i>
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="{{ asset('js/admin.js') }}"></script>
    
    @yield('scripts')
</body>
</html>
