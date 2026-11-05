<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $code }} · SipzStore</title>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/sipz.css') }}?v=1">
</head>
<body>
<main style="min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px">
    <div class="card" style="max-width: 460px; text-align: center; padding: 36px">
        <div class="brand-mark" style="margin: 0 auto 16px; width: 52px; height: 52px; font-size: 26px">S</div>
        <div class="eyebrow">Kode {{ $code }}</div>
        <h1 style="font-size: 24px; font-weight: 800; margin-top: 6px">{{ $title }}</h1>
        <p class="muted" style="margin: 8px 0 20px">{{ $message }}</p>
        <a href="{{ url('/') }}" class="btn btn-primary">Kembali ke beranda</a>
    </div>
</main>
</body>
</html>
