<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laporan Keuangan {{ $periodLabel }} · {{ \App\Models\Setting::get('store_name') }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/sipz.css') }}?v=1">
</head>
<body class="print-page">
@php
    use App\Support\Fmt;
    use App\Models\Setting;
@endphp
<div class="print-bar">
    <button type="button" class="btn btn-primary" onclick="window.print()">Cetak / Simpan sebagai PDF</button>
    <a href="{{ route('reports.print', ['periode' => $period, 'tab' => 'semua']) }}" class="btn">Semua laporan</a>
    <a href="{{ route('reports.index', ['periode' => $period, 'tab' => $tab]) }}" class="btn">Kembali</a>
</div>

<div class="paper">
    <div class="kop">
        <span class="brand-mark">{{ mb_substr(Setting::get('store_name'), 0, 1) }}</span>
        <div>
            <div class="n">{{ Setting::get('store_name') }}</div>
            <div class="a">{{ Setting::get('store_tagline') }}<br>{{ Setting::get('store_address') }} · {{ Setting::get('store_phone') }}</div>
        </div>
    </div>

    @include('reports.partials.statements', [
        'all' => request('tab') === 'semua',
        'signatures' => true,
    ])

    <p class="xsmall muted" style="margin-top: 24px">Dicetak oleh {{ $printedBy->signature() }} pada {{ Fmt::date(Fmt::now(), 'j F Y, H.i') }} WIB dari Aplikasi Keuangan {{ Setting::get('store_name') }}.</p>
</div>
</body>
</html>
