<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Login') — Slip Gaji {{ config('company.short_name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="auth-body antialiased">
    <div class="auth-shell auth-shell--minimal">
        <div class="auth-main-inner">
            <div class="auth-logo-wrap">
                <img src="{{ asset('images/logo_m.png') }}" alt="{{ config('company.short_name') }}" class="auth-logo">
            </div>

            <div class="auth-card auth-card--minimal">
                <div class="auth-card-header">
                    <h2 class="auth-card-title">@yield('heading', 'Masuk')</h2>
                    @hasSection('subheading')
                        <p class="auth-card-subtitle">@yield('subheading')</p>
                    @endif
                </div>

                @if(session('success'))
                    <div class="auth-alert auth-alert--success">{{ session('success') }}</div>
                @endif
                @if(session('error'))
                    <div class="auth-alert auth-alert--error">{{ session('error') }}</div>
                @endif

                @yield('content')
            </div>

            @hasSection('footer')
                <div class="auth-footer">@yield('footer')</div>
            @endif
        </div>
    </div>
</body>
</html>
