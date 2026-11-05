<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk · {{ \App\Models\Setting::get('store_name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/sipz.css') }}?v=1">
</head>
<body>
@php($store = \App\Models\Setting::get('store_name'))
<div class="auth">
    <section class="auth-side" aria-label="Tentang aplikasi">
        <div class="brand" style="padding: 0">
            <span class="brand-mark" style="width: 48px; height: 48px; font-size: 24px">{{ mb_substr($store, 0, 1) }}</span>
            <span><span style="display: block; font-size: 20px; font-weight: 800">{{ $store }}</span><span style="display: block; font-size: 13px; color: #C9D1E8">{{ \App\Models\Setting::get('store_tagline') }}</span></span>
        </div>
        <div>
            <h1>Pesanan, produksi, dan kas toko dalam satu catatan.</h1>
            <ul>
                <li><span>@include('partials.icon', ['name' => 'check', 'size' => 16, 'stroke' => 2.4])</span>Setiap rupiah tercatat dengan nota dan nama pencatat</li>
                <li><span>@include('partials.icon', ['name' => 'check', 'size' => 16, 'stroke' => 2.4])</span>Antrean mesin tersusun otomatis sesuai kapasitas</li>
                <li><span>@include('partials.icon', ['name' => 'check', 'size' => 16, 'stroke' => 2.4])</span>Laporan laba rugi siap cetak setiap bulan</li>
            </ul>
        </div>
        <div class="addr" style="font-size: 13px; color: #C9D1E8">{{ \App\Models\Setting::get('store_address') }}</div>
    </section>

    <main class="auth-main">
        <form method="post" action="{{ route('login.attempt') }}" class="auth-form form" novalidate>
            @csrf
            <div>
                <h2 style="font-size: 28px; font-weight: 800; letter-spacing: -.5px">Masuk</h2>
                <p class="muted" style="margin: 6px 0 0">Gunakan akun yang dibuat oleh Owner.</p>
            </div>

            @if (session('success'))
                <div class="flash" role="status"><span class="ic">@include('partials.icon', ['name' => 'check', 'size' => 14, 'stroke' => 2.6])</span>{{ session('success') }}</div>
            @endif
            @if ($errors->any())
                <div class="flash err" role="alert"><span class="ic">@include('partials.icon', ['name' => 'alert', 'size' => 14, 'stroke' => 2.4])</span>{{ $errors->first() }}</div>
            @endif

            <div class="field">
                <label for="username">Username</label>
                <input id="username" name="username" class="input" type="text" value="{{ old('username') }}" autocomplete="username" autofocus required placeholder="contoh: rina">
            </div>
            <div class="field">
                <label for="password">Kata sandi</label>
                <input id="password" name="password" class="input" type="password" autocomplete="current-password" required>
            </div>
            <label class="check"><input type="checkbox" name="remember" value="1">Ingat saya di perangkat ini</label>
            <button type="submit" class="btn btn-primary btn-lg btn-block">Masuk</button>

            <div class="note" style="background: #fff; border: 1px solid var(--line)">
                <strong style="color: var(--ink)">Hak akses per peran</strong>
                <span style="display: block"><b>Owner</b>: semua menu, persetujuan pengeluaran, laporan, audit.</span>
                <span style="display: block"><b>Admin</b>: pesanan, pelanggan, pencatatan kas.</span>
                <span style="display: block"><b>Operator</b>: antrean mesin dan status produksi.</span>
            </div>
        </form>
    </main>
</div>
</body>
</html>
