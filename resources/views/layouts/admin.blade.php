<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'NSIA Fleet') — Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @stack('styles')
</head>
<body class="layout-admin">

<aside class="sidebar" id="sidebar">
    <div class="sidebar-logo">
        <div class="logo-mark">
            <div class="logo-icon">
                <svg viewBox="0 0 24 24" fill="white" width="18" height="18">
                    <path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99z"/>
                </svg>
            </div>
            <div>
                <div class="logo-text">NSIA Fleet</div>
                <div class="logo-sub">Admin Portal</div>
            </div>
        </div>
    </div>

    <nav class="nav">
        <div class="nav-section">Overview</div>
        <a href="{{ route('admin.dashboard') }}"
            class="nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="3" width="7" height="9"/><rect x="14" y="3" width="7" height="5"/>
                <rect x="14" y="12" width="7" height="9"/><rect x="3" y="16" width="7" height="5"/>
            </svg>
            Dashboard
        </a>

        <div class="nav-section">Operations</div>

        <a href="{{ route('admin.trips.index') }}"
            class="nav-item {{ request()->routeIs('admin.trips.*') ? 'active' : '' }}">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M9 17H5a2 2 0 01-2-2V5a2 2 0 012-2h11a2 2 0 012 2v3"/>
                <path d="M13 21l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2M8 7h3m-3 4h2"/>
            </svg>
            Trip Bookings
            @php $pendingCount = \App\Models\Trip::where('status','pending')->count(); @endphp
            @if($pendingCount > 0)
                <span class="nav-badge">{{ $pendingCount }}</span>
            @endif
        </a>

        <a href="{{ route('admin.drivers.index') }}"
            class="nav-item {{ request()->routeIs('admin.drivers.*') ? 'active' : '' }}">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="8" r="4"/>
                <path d="M20 21a8 8 0 10-16 0"/>
            </svg>
            Drivers
        </a>

        <a href="{{ route('admin.vehicles.index') }}"
            class="nav-item {{ request()->routeIs('admin.vehicles.*') ? 'active' : '' }}">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="1" y="3" width="15" height="13"/>
                <path d="M16 8h4l3 3v5h-7V8z"/>
                <circle cx="5.5" cy="18.5" r="2.5"/>
                <circle cx="18.5" cy="18.5" r="2.5"/>
            </svg>
            Fleet / Vehicles
        </a>

        <a href="{{ route('admin.fuel.index') }}"
            class="nav-item {{ request()->routeIs('admin.fuel.*') ? 'active' : '' }}">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M3 22V7l4-4h7l4 4v15"/>
                <line x1="3" y1="11" x2="17" y2="11"/>
                <path d="M17 7h2a2 2 0 012 2v7a1 1 0 001 1h0a1 1 0 001-1V9.83a2 2 0 00-.59-1.42L21 7"/>
            </svg>
            Fuel Management
            @php $fuelCount = \App\Models\FuelRequest::where('status','pending')->count(); @endphp
            @if($fuelCount > 0)
                <span class="nav-badge amber">{{ $fuelCount }}</span>
            @endif
        </a>

        <div class="nav-section">Finance</div>

        <a href="{{ route('admin.payments.index') }}"
            class="nav-item {{ request()->routeIs('admin.payments.*') ? 'active' : '' }}">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="2" y="5" width="20" height="14" rx="2"/>
                <line x1="2" y1="10" x2="22" y2="10"/>
            </svg>
            Driver Payments
        </a>

        <div class="nav-section">Management</div>

        <a href="{{ route('admin.maintenance.index') }}"
            class="nav-item {{ request()->routeIs('admin.maintenance.*') ? 'active' : '' }}">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M14.7 6.3a1 1 0 000 1.4l1.6 1.6a1 1 0 001.4 0l3.77-3.77a6 6 0 01-7.94 7.94l-6.91 6.91a2.12 2.12 0 01-3-3l6.91-6.91a6 6 0 017.94-7.94l-3.76 3.76z"/>
            </svg>
            Maintenance
        </a>

        <a href="{{ route('admin.training.index') }}"
            class="nav-item {{ request()->routeIs('admin.training.*') ? 'active' : '' }}">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M22 10v6M2 10l10-5 10 5-10 5z"/>
                <path d="M6 12v5c3 3 9 3 12 0v-5"/>
            </svg>
            Training
        </a>

        <a href="{{ route('admin.awards.index') }}"
            class="nav-item {{ request()->routeIs('admin.awards.*') ? 'active' : '' }}">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="8" r="6"/>
                <path d="M15.477 12.89L17 22l-5-3-5 3 1.523-9.11"/>
            </svg>
            Awards
        </a>

        <a href="{{ route('admin.notifications') }}"
            class="nav-item {{ request()->routeIs('admin.notifications*') ? 'active' : '' }}">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                <path d="M13.73 21a2 2 0 01-3.46 0"/>
            </svg>
            Notifications
            @if(auth()->user()->unreadNotifications->count() > 0)
                <span class="nav-badge">{{ auth()->user()->unreadNotifications->count() }}</span>
            @endif
        </a>
    </nav>

    <div class="sidebar-footer">
        <div class="avatar">{{ strtoupper(substr(auth()->user()->name, 0, 2)) }}</div>
        <div class="footer-info">
            <div class="name">{{ auth()->user()->name }}</div>
            <div class="role">Administrator</div>
        </div>
        <form method="POST" action="{{ route('logout') }}" class="ml-auto">
            @csrf
            <button type="submit" class="icon-btn" title="Logout">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9"/>
                </svg>
            </button>
        </form>
    </div>
</aside>

<main class="main">
    <div class="topbar">
        <div class="topbar-left">
            <div>
                <div class="page-title">@yield('page-title', 'Dashboard')</div>
                <div class="page-sub">{{ now()->format('l, d M Y') }}</div>
            </div>
        </div>
        <div class="topbar-right">
            <a href="{{ route('admin.notifications') }}" class="notif-btn">
                @if(auth()->user()->unreadNotifications->count() > 0)
                    <div class="notif-dot"></div>
                @endif
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                    <path d="M13.73 21a2 2 0 01-3.46 0"/>
                </svg>
            </a>
            @yield('topbar-actions')
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-green mx-6 mt-4">✓ {{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-red mx-6 mt-4">✕ {{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-red mx-6 mt-4">
            <ul style="list-style:none;padding:0;margin:0">
                @foreach($errors->all() as $error)
                    <li>✕ {{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="content">
        @yield('content')
    </div>
</main>

<script src="{{ asset('js/app.js') }}"></script>
@stack('scripts')
</body>
</html>
