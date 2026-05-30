<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Dashboard') — Slip Gaji {{ config('company.short_name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="bg-slate-50 text-slate-800 antialiased">
    <div class="flex min-h-screen">
        {{-- Sidebar --}}
        <aside class="w-64 bg-maroon-950 text-white flex flex-col shrink-0 fixed inset-y-0 left-0 z-30">
            <div class="px-6 py-6 border-b border-white/10">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-white/10 flex items-center justify-center font-bold text-sm">{{ config('company.short_name') }}</div>
                    <div>
                        <p class="font-bold text-sm leading-tight">Slip Gaji</p>
                        <p class="text-xs text-white/60">{{ config('company.name') }}</p>
                    </div>
                </div>
            </div>

            <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
                @php
                    $navItems = [
                        ['route' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
                        ['route' => 'employees.index', 'label' => 'Data Karyawan', 'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z'],
                        ['route' => 'slip.create', 'label' => 'Input Slip Gaji', 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                        ['route' => 'review.index', 'label' => 'Review Slip Gaji', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4'],
                    ];
                @endphp

                @foreach($navItems as $item)
                    @php
                        $isActive = match($item['route']) {
                            'dashboard' => request()->routeIs('dashboard'),
                            'employees.index' => request()->routeIs('employees.*'),
                            'slip.create' => request()->routeIs('slip.*'),
                            'review.index' => request()->routeIs('review.*'),
                            default => request()->routeIs($item['route']),
                        };
                    @endphp
                    <a href="{{ route($item['route']) }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                              {{ $isActive ? 'bg-white/15 text-white' : 'text-white/70 hover:bg-white/10 hover:text-white' }}">
                        <svg class="w-5 h-5 shrink-0 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="{{ $item['icon'] }}"/>
                        </svg>
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </nav>

            <div class="px-4 py-4 border-t border-white/10">
                <div class="px-3 py-2 rounded-lg bg-white/5">
                    <p class="text-xs text-white/50">Email HRD</p>
                    <p class="text-xs font-medium text-white/90 truncate">{{ config('company.hrd_email') }}</p>
                </div>
            </div>
        </aside>

        {{-- Main --}}
        <div class="flex-1 ml-64 flex flex-col min-h-screen">
            <header class="page-header-bar bg-white border-b border-slate-200 px-8 py-4 sticky top-0 z-20">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-lg font-bold text-slate-900">@yield('page-title', 'Dashboard')</h1>
                        @hasSection('page-subtitle')
                            <p class="text-sm text-slate-500 mt-0.5">@yield('page-subtitle')</p>
                        @endif
                    </div>
                    @yield('header-actions')
                </div>
            </header>

            @if(session('success'))
                <div class="flash-alert no-print mx-8 mt-4 px-4 py-3 bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm rounded-lg">
                    {{ session('success') }}
                </div>
            @endif
            @if(session('warning'))
                <div class="flash-alert no-print mx-8 mt-4 px-4 py-3 bg-amber-50 border border-amber-200 text-amber-800 text-sm rounded-lg">
                    {{ session('warning') }}
                </div>
            @endif
            @if(session('error'))
                <div class="flash-alert no-print mx-8 mt-4 px-4 py-3 bg-red-50 border border-red-200 text-red-800 text-sm rounded-lg">
                    {{ session('error') }}
                </div>
            @endif

            <main class="flex-1 p-8">
                @yield('content')
            </main>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
