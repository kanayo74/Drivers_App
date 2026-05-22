<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NSIA Fleet — Sign in</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'DM Sans', sans-serif;
            background: #0d0f14;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        /* ── Left panel (brand) ── */
        .login-wrap {
            display: grid;
            grid-template-columns: 1fr 440px;
            width: 100%;
            max-width: 900px;
            min-height: 540px;
            border-radius: 18px;
            overflow: hidden;
            border: 1px solid #2a2f3d;
        }

        .brand-panel {
            background: #13161e;
            padding: 48px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            border-right: 1px solid #2a2f3d;
            position: relative;
            overflow: hidden;
        }

        .brand-panel::before {
            content: '';
            position: absolute;
            top: -60px; right: -60px;
            width: 280px; height: 280px;
            border-radius: 50%;
            background: rgba(79,124,255,.06);
        }
        .brand-panel::after {
            content: '';
            position: absolute;
            bottom: -40px; left: -40px;
            width: 200px; height: 200px;
            border-radius: 50%;
            background: rgba(45,212,191,.04);
        }

        .brand-logo {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .brand-logo-icon {
            width: 40px; height: 40px;
            background: #4f7cff;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .brand-logo-icon svg { width: 22px; height: 22px; fill: white; }
        .brand-logo-name { font-size: 15px; font-weight: 600; color: #e8eaf0; }
        .brand-logo-tag  { font-size: 10px; color: #5c6278; font-family: 'DM Mono', monospace; text-transform: uppercase; letter-spacing: 1px; }

        .brand-body { position: relative; z-index: 1; }
        .brand-headline {
            font-size: 28px;
            font-weight: 600;
            color: #e8eaf0;
            line-height: 1.3;
            margin-bottom: 14px;
        }
        .brand-headline span { color: #4f7cff; }
        .brand-desc {
            font-size: 14px;
            color: #9097ad;
            line-height: 1.7;
            margin-bottom: 28px;
        }

        .brand-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        .brand-stat {
            background: #1a1e28;
            border: 1px solid #2a2f3d;
            border-radius: 10px;
            padding: 12px 14px;
        }
        .brand-stat-val  { font-size: 20px; font-weight: 600; color: #e8eaf0; }
        .brand-stat-label{ font-size: 11px; color: #5c6278; margin-top: 2px; }

        .brand-footer { font-size: 12px; color: #5c6278; position: relative; z-index: 1; }

        /* ── Right panel (form) ── */
        .form-panel {
            background: #0d0f14;
            padding: 48px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .form-header { margin-bottom: 28px; }
        .form-title   { font-size: 20px; font-weight: 600; color: #e8eaf0; margin-bottom: 4px; }
        .form-subtitle{ font-size: 13px; color: #9097ad; }

        /* Error alert */
        .alert-error {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            background: rgba(240,75,75,.08);
            border: 1px solid rgba(240,75,75,.2);
            border-radius: 8px;
            padding: 11px 14px;
            font-size: 13px;
            color: #f04b4b;
            margin-bottom: 20px;
        }
        .alert-error svg { width: 16px; height: 16px; flex-shrink: 0; margin-top: 1px; }

        /* Field */
        .field       { margin-bottom: 16px; }
        .field-label {
            display: block;
            font-size: 11px;
            font-weight: 500;
            color: #5c6278;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            font-family: 'DM Mono', monospace;
            margin-bottom: 7px;
        }
        .field-wrap  { position: relative; }
        .field-icon  {
            position: absolute;
            left: 12px; top: 50%;
            transform: translateY(-50%);
            color: #5c6278;
            pointer-events: none;
            display: flex;
        }
        .field-icon svg { width: 16px; height: 16px; }

        .field-input {
            width: 100%;
            background: #13161e;
            border: 1px solid #2a2f3d;
            border-radius: 8px;
            padding: 10px 12px 10px 38px;
            font-size: 13px;
            font-family: 'DM Sans', sans-serif;
            color: #e8eaf0;
            outline: none;
            transition: border-color .15s;
        }
        .field-input::placeholder { color: #5c6278; }
        .field-input:focus { border-color: #4f7cff; }

        .field-input.has-toggle { padding-right: 40px; }

        .pw-toggle {
            position: absolute;
            right: 11px; top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #5c6278;
            padding: 0;
            display: flex;
            transition: color .15s;
        }
        .pw-toggle:hover { color: #9097ad; }
        .pw-toggle svg   { width: 16px; height: 16px; }

        /* Remember + forgot row */
        .meta-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .remember-label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: #9097ad;
            cursor: pointer;
            user-select: none;
        }
        .remember-label input[type=checkbox] {
            width: 15px; height: 15px;
            accent-color: #4f7cff;
            cursor: pointer;
        }

        /* Submit button */
        .submit-btn {
            width: 100%;
            background: #4f7cff;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 11px;
            font-size: 14px;
            font-weight: 500;
            font-family: 'DM Sans', sans-serif;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: background .15s, opacity .15s;
        }
        .submit-btn:hover    { background: #3d63cc; }
        .submit-btn:disabled { opacity: .6; cursor: not-allowed; }
        .submit-btn svg      { width: 16px; height: 16px; }

        .spin {
            animation: spin .7s linear infinite;
        }
        @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }

        /* Divider */
        .divider {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 20px 0 16px;
        }
        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #2a2f3d;
        }
        .divider span { font-size: 11px; color: #5c6278; white-space: nowrap; }

        /* Quick-login chips */
        .role-chips { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 6px; }
        .role-chip {
            background: #13161e;
            border: 1px solid #2a2f3d;
            border-radius: 8px;
            padding: 8px 10px;
            cursor: pointer;
            transition: border-color .15s, background .15s;
            text-align: left;
        }
        .role-chip:hover {
            border-color: #4f7cff;
            background: rgba(79,124,255,.06);
        }
        .role-chip-label { font-size: 10px; color: #5c6278; font-family: 'DM Mono', monospace; text-transform: uppercase; letter-spacing: 0.5px; }
        .role-chip-name  { font-size: 12px; color: #e8eaf0; font-weight: 500; margin-top: 2px; }

        .footer-note {
            text-align: center;
            font-size: 11px;
            color: #5c6278;
            margin-top: 20px;
        }

        /* Responsive */
        @media (max-width: 680px) {
            .login-wrap { grid-template-columns: 1fr; }
            .brand-panel { display: none; }
            .form-panel  { padding: 36px 28px; }
        }
    </style>
</head>
<body>

<div class="login-wrap">

    {{-- ── LEFT: BRAND PANEL ── --}}
    <div class="brand-panel">
        <div class="brand-logo">
            <div class="brand-logo-icon">
                <svg viewBox="0 0 24 24">
                    <path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 16c-.83 0-1.5-.67-1.5-1.5S5.67 13 6.5 13s1.5.67 1.5 1.5S7.33 16 6.5 16zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 11l1.5-4.5h11L19 11H5z"/>
                </svg>
            </div>
            <div>
                <div class="brand-logo-name">NSIA Fleet</div>
                <div class="brand-logo-tag">Management Portal</div>
            </div>
        </div>

        <div class="brand-body">
            <div class="brand-headline">
                Manage your fleet<br>
                with <span>full control</span>
            </div>
            <div class="brand-desc">
                Book trips, track fuel, manage drivers,
                process payments, and monitor your entire
                fleet from one place.
            </div>

            <div class="brand-stats">
                <div class="brand-stat">
                    <div class="brand-stat-val">4</div>
                    <div class="brand-stat-label">User roles</div>
                </div>
                <div class="brand-stat">
                    <div class="brand-stat-val">Real-time</div>
                    <div class="brand-stat-label">Fuel tracking</div>
                </div>
                <div class="brand-stat">
                    <div class="brand-stat-val">Auto</div>
                    <div class="brand-stat-label">Monthly awards</div>
                </div>
                <div class="brand-stat">
                    <div class="brand-stat-val">Weekend</div>
                    <div class="brand-stat-label">Driver payments</div>
                </div>
            </div>
        </div>

        <div class="brand-footer">
            NSIA Insurance Group · © {{ now()->year }}
        </div>
    </div>

    {{-- ── RIGHT: FORM PANEL ── --}}
    <div class="form-panel">
        <div class="form-header">
            <div class="form-title">Welcome back</div>
            <div class="form-subtitle">Sign in to your NSIA Fleet account</div>
        </div>

        {{-- Errors --}}
        @if($errors->any())
        <div class="alert-error">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/>
                <line x1="12" y1="8" x2="12" y2="12"/>
                <line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            {{ $errors->first() }}
        </div>
        @endif

        <form method="POST" action="{{ route('login.submit') }}" id="login-form">
            @csrf

            {{-- Email --}}
            <div class="field">
                <label class="field-label" for="email">Email address</label>
                <div class="field-wrap">
                    <span class="field-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                            <polyline points="22,6 12,13 2,6"/>
                        </svg>
                    </span>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="field-input"
                        placeholder="you@nsia.com"
                        value="{{ old('email') }}"
                        required
                        autofocus>
                </div>
            </div>

            {{-- Password --}}
            <div class="field">
                <label class="field-label" for="password">Password</label>
                <div class="field-wrap">
                    <span class="field-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                            <path d="M7 11V7a5 5 0 0110 0v4"/>
                        </svg>
                    </span>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="field-input has-toggle"
                        placeholder="••••••••"
                        required>
                    <button type="button" class="pw-toggle" id="pw-toggle" onclick="togglePassword()" aria-label="Toggle password">
                        <svg id="pw-icon-show" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                        <svg id="pw-icon-hide" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" style="display:none">
                            <path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/>
                            <line x1="1" y1="1" x2="23" y2="23"/>
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Remember + forgot --}}
            <div class="meta-row">
                <label class="remember-label">
                    <input type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                    Remember me
                </label>
            </div>

            {{-- Submit --}}
            <button type="submit" class="submit-btn" id="submit-btn">
                <span id="btn-text">Sign in</span>
                <svg id="btn-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="5" y1="12" x2="19" y2="12"/>
                    <polyline points="12 5 19 12 12 19"/>
                </svg>
                <svg id="btn-spinner" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="spin" style="display:none">
                    <path d="M21 12a9 9 0 11-6.219-8.56"/>
                </svg>
            </button>
        </form>

        {{-- Quick role select --}}
        <div class="divider"><span>quick login (demo)</span></div>
        <div class="role-chips">
            <button type="button" class="role-chip" onclick="fillCreds('admin@nsia.com')">
                <div class="role-chip-label">Admin</div>
                <div class="role-chip-name">Full control</div>
            </button>
            <button type="button" class="role-chip" onclick="fillCreds('emeka@nsia.com')">
                <div class="role-chip-label">Staff</div>
                <div class="role-chip-name">Book trips</div>
            </button>
            <button type="button" class="role-chip" onclick="fillCreds('kelechi@nsia.com')">
                <div class="role-chip-label">Driver</div>
                <div class="role-chip-name">My trips</div>
            </button>
        </div>

        <div class="footer-note">
            Contact IT Support for access issues
        </div>
    </div>
</div>

<script>
function togglePassword() {
    const inp  = document.getElementById('password');
    const show = document.getElementById('pw-icon-show');
    const hide = document.getElementById('pw-icon-hide');
    if (inp.type === 'password') {
        inp.type       = 'text';
        show.style.display = 'none';
        hide.style.display = 'block';
    } else {
        inp.type       = 'password';
        show.style.display = 'block';
        hide.style.display = 'none';
    }
}

function fillCreds(email) {
    document.getElementById('email').value    = email;
    document.getElementById('password').value = 'password';
}

document.getElementById('login-form').addEventListener('submit', function() {
    const btn     = document.getElementById('submit-btn');
    const text    = document.getElementById('btn-text');
    const arrow   = document.getElementById('btn-arrow');
    const spinner = document.getElementById('btn-spinner');
    btn.disabled        = true;
    text.textContent    = 'Signing in…';
    arrow.style.display   = 'none';
    spinner.style.display = 'block';
});
</script>

</body>
</html>
