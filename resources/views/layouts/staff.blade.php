<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'NSIA Fleet') — {{ ucfirst(auth()->user()->role ?? 'Staff') }}</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @stack('styles')
</head>
<body class="layout-admin">

<aside class="sidebar">
    <div class="sidebar-logo">
        <div class="logo-mark">
            <div class="logo-icon">
                <svg viewBox="0 0 24 24" fill="white" width="18" height="18">
                    <path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99z"/>
                </svg>
            </div>
            <div>
                <div class="logo-text">NSIA Fleet</div>
                <div class="logo-sub">{{ ucfirst(auth()->user()->role ?? 'Staff') }} Portal</div>
            </div>
        </div>
    </div>

    <nav class="nav">
        <div class="nav-section">Trips</div>
        <a href="{{ route('staff.trips.index') }}"
            class="nav-item {{ request()->routeIs('staff.trips.*') ? 'active' : '' }}">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M9 17H5a2 2 0 01-2-2V5a2 2 0 012-2h11a2 2 0 012 2v3"/>
                <path d="M13 21l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2M8 7h3m-3 4h2"/>
            </svg>
            My Bookings
            @php $pendingCount = \App\Models\Trip::where('passenger_id',auth()->id())->where('status','pending')->count(); @endphp
            @if($pendingCount)
                <span class="nav-badge amber">{{ $pendingCount }}</span>
            @endif
        </a>

        <div class="nav-section">Account</div>
        <a href="{{ route('staff.notifications') }}"
            class="nav-item {{ request()->routeIs('staff.notifications') ? 'active' : '' }}">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                <path d="M13.73 21a2 2 0 01-3.46 0"/>
            </svg>
            Notifications
            @if(auth()->user()->unreadNotifications->count())
                <span class="nav-badge">{{ auth()->user()->unreadNotifications->count() }}</span>
            @endif
        </a>
    </nav>

    <div class="sidebar-footer">
        <div class="avatar">{{ strtoupper(substr(auth()->user()->name, 0, 2)) }}</div>
        <div class="footer-info">
            <div class="name">{{ auth()->user()->name }}</div>
            <div class="role">{{ ucfirst(auth()->user()->role) }}</div>
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
                <div class="page-title">@yield('page-title', 'My Trips')</div>
                <div class="page-sub">{{ now()->format('l, d M Y') }}</div>
            </div>
        </div>
        <div class="topbar-right">
            <a href="{{ route('staff.notifications') }}" class="notif-btn">
                @if(auth()->user()->unreadNotifications->count())
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

    <div class="content">@yield('content')</div>
</main>

<style>
.btn-link { background:none;border:none;color:var(--accent);font-size:11px;cursor:pointer;padding:0;text-decoration:underline;font-family:'DM Sans',sans-serif; }
.btn-link:hover { color:var(--accent2); }
</style>

<script src="{{ asset('js/app.js') }}"></script>
@stack('scripts')
</body>
</html>
