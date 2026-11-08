<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') · {{ \App\Models\Setting::get('store_name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/sipz.css') }}?v=2">
    <link rel="stylesheet" href="{{ asset('css/dashboard/theme.css') }}?v=2">
    @if (request()->routeIs('orders.index'))
    <link rel="stylesheet" href="{{ asset('css/orders-theme.css') }}?v=2">
@endif
</head>
<body>
@php
    $me = auth()->user();
    $is = fn (...$p) => request()->routeIs(...$p) ? 'on' : '';
    $activeOrders = \App\Models\Order::whereIn('status', ['masuk', 'antre', 'produksi', 'selesai'])->count();
    $pendingCash = $me->isOwner() ? \App\Models\CashTransaction::where('status', 'menunggu')->count() : 0;
    $storeName = \App\Models\Setting::get('store_name');
@endphp
<div class="app">
    <nav class="sidebar" aria-label="Navigasi utama">
        <a href="{{ route('dashboard') }}" class="brand">
            <span class="brand-mark">{{ mb_substr($storeName, 0, 1) }}</span>
            <span><span class="brand-name">{{ $storeName }}</span><span class="brand-sub">Keuangan &amp; Pesanan</span></span>
        </a>

        <div class="nav">
            @if ($me->role === 'operator')
                <div class="nav-title">Produksi</div>
                <a href="{{ route('production.index') }}" class="{{ $is('production.*') }}">@include('partials.icon', ['name' => 'printer'])Antrean Mesin</a>
                <a href="{{ route('orders.index') }}" class="{{ $is('orders.*') }}">@include('partials.icon', ['name' => 'board'])Alur Pesanan</a>
            @else
                <div class="nav-title">Operasional</div>
                <a href="{{ route('dashboard') }}" class="{{ $is('dashboard') }}">@include('partials.icon', ['name' => 'dashboard'])Dashboard</a>
                <a href="{{ route('orders.index') }}" class="{{ request()->routeIs('orders.*') && ! request()->routeIs('orders.create') ? 'on' : '' }}">@include('partials.icon', ['name' => 'board'])Pesanan &amp; Antrean @if ($activeOrders)<span class="count">{{ $activeOrders }}</span>@endif</a>
                <a href="{{ route('orders.create') }}" class="{{ $is('orders.create') }}">@include('partials.icon', ['name' => 'plus-circle'])Pesanan Baru</a>
                <a href="{{ route('production.index') }}" class="{{ $is('production.*') }}">@include('partials.icon', ['name' => 'printer'])Antrean Mesin</a>
                <a href="{{ route('customers.index') }}" class="{{ $is('customers.*') }}">@include('partials.icon', ['name' => 'users'])Pelanggan</a>

                <div class="nav-title">Keuangan</div>
                <a href="{{ route('cash.index') }}" class="{{ $is('cash.*') }}">@include('partials.icon', ['name' => 'wallet'])Kas Masuk &amp; Keluar @if ($pendingCash)<span class="count red">{{ $pendingCash }}</span>@endif</a>
                @if ($me->isOwner())
                    <a href="{{ route('reports.index') }}" class="{{ $is('reports.*') }}">@include('partials.icon', ['name' => 'chart'])Laporan</a>

                    <div class="nav-title">Pengelolaan</div>
                    <a href="{{ route('audit.index') }}" class="{{ $is('audit.*') }}">@include('partials.icon', ['name' => 'shield'])Jejak Audit</a>
                    <a href="{{ route('products.index') }}" class="{{ $is('products.*') }}">@include('partials.icon', ['name' => 'tag'])Produk &amp; Harga</a>
                    <a href="{{ route('users.index') }}" class="{{ $is('users.*') }}">@include('partials.icon', ['name' => 'user'])Pengguna</a>
                    <a href="{{ route('settings.edit') }}" class="{{ $is('settings.*') }}">@include('partials.icon', ['name' => 'settings'])Pengaturan</a>
                @endif
            @endif
        </div>

        <div class="me">
            <a href="{{ route('profile.edit') }}" class="who" title="Profil & kata sandi">
                <span class="avatar {{ $me->role === 'owner' ? '' : 'alt' }}">{{ $me->initials() }}</span>
                <span style="min-width: 0"><span class="nm">{{ $me->name }}</span><span class="rl">{{ $me->roleLabel() }}</span></span>
            </a>
            <form method="post" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="icon-btn" aria-label="Keluar" title="Keluar">@include('partials.icon', ['name' => 'logout'])</button>
            </form>
        </div>
    </nav>

    <div style="flex: 1; min-width: 0; display: flex; flex-direction: column">
        <header class="m-top">
            <a href="{{ route('dashboard') }}" class="brand">
                <span class="brand-mark">{{ mb_substr($storeName, 0, 1) }}</span>
                <span class="brand-name">{{ $storeName }}</span>
            </a>
            <a href="{{ route('profile.edit') }}" class="avatar {{ $me->role === 'owner' ? '' : 'alt' }}" style="text-decoration: none" aria-label="Profil {{ $me->name }}">{{ $me->initials() }}</a>
        </header>

        <main class="main">
            <div class="container">
                @if (session('success'))
                    <div class="flash" role="status"><span class="ic">@include('partials.icon', ['name' => 'check', 'size' => 14, 'stroke' => 2.6])</span><span>{{ session('success') }}</span></div>
                @endif
                @if ($errors->any())
                    <div class="flash err" role="alert">
                        <span class="ic">@include('partials.icon', ['name' => 'alert', 'size' => 14, 'stroke' => 2.4])</span>
                        <div>
                            @if ($errors->count() === 1)
                                {{ $errors->first() }}
                            @else
                                Ada {{ $errors->count() }} isian yang perlu diperbaiki:
                                <ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                            @endif
                        </div>
                    </div>
                @endif

                @yield('content')
            </div>
        </main>
    </div>

    {{-- Navigasi bawah (ponsel) --}}
    @if ($me->role === 'operator')
        <nav class="m-nav" aria-label="Navigasi ponsel" style="--n: 3">
            <a href="{{ route('production.index') }}" class="{{ $is('production.*') }}">@include('partials.icon', ['name' => 'printer', 'size' => 22])Antrean</a>
            <a href="{{ route('orders.index') }}" class="{{ $is('orders.*') }}">@include('partials.icon', ['name' => 'board', 'size' => 22])Pesanan</a>
            <a href="{{ route('profile.edit') }}" class="{{ $is('profile.*') }}">@include('partials.icon', ['name' => 'user', 'size' => 22])Profil</a>
        </nav>
    @else
        <nav class="m-nav" aria-label="Navigasi ponsel">
            <a href="{{ route('dashboard') }}" class="{{ $is('dashboard') }}">@include('partials.icon', ['name' => 'home', 'size' => 22])Beranda</a>
            <a href="{{ route('orders.index') }}" class="{{ request()->routeIs('orders.*') && ! request()->routeIs('orders.create') ? 'on' : '' }}">@include('partials.icon', ['name' => 'board', 'size' => 22])Pesanan</a>
            <a href="{{ route('orders.create') }}" class="{{ $is('orders.create') }}">@include('partials.icon', ['name' => 'plus-circle', 'size' => 22])Baru</a>
            <a href="{{ route('cash.index') }}" class="{{ $is('cash.*') }}">@include('partials.icon', ['name' => 'wallet', 'size' => 22])Kas</a>
            @if ($me->isOwner())
                <a href="{{ route('reports.index') }}" class="{{ $is('reports.*') }}">@include('partials.icon', ['name' => 'chart', 'size' => 22])Laporan</a>
            @else
                <a href="{{ route('customers.index') }}" class="{{ $is('customers.*') }}">@include('partials.icon', ['name' => 'users', 'size' => 22])Pelanggan</a>
            @endif
        </nav>
    @endif
</div>

<script src="{{ asset('js/sipz.js') }}?v=1"></script>
@stack('scripts')
</body>
</html>
