@extends('layouts.app')
@section('title', 'Laporan Keuangan')

@php
    use App\Support\Fmt;
    $chg = function ($now, $before) {
        $p = \App\Services\ReportService::change($now, $before);
        return $p === null ? '' : (($p >= 0 ? '+' : '−').abs($p).'% dari periode sebelumnya');
    };
@endphp

@section('content')
    <header class="page-head">
        <div>
            <div class="eyebrow">Laporan Keuangan</div>
            <h1 class="page-title">{{ $periodLabel }}</h1>
            <p class="page-sub">Disusun otomatis dari buku kas. Hanya transaksi berstatus Tercatat yang dihitung, sehingga setiap angka bisa ditelusuri ke bukti transaksinya.</p>
        </div>
        <div class="actions">
            <form method="get" action="{{ route('reports.index') }}">
                <input type="hidden" name="tab" value="{{ $tab }}">
                <label class="sr-only" for="periode">Periode</label>
                <select id="periode" name="periode" class="select" style="height: 44px; font-weight: 700" data-autosubmit>
                    @foreach ($options as $k => $l)<option value="{{ $k }}" @selected($period === (string) $k)>{{ $l }}</option>@endforeach
                </select>
            </form>
            <a href="{{ route('reports.export', ['periode' => $period]) }}" class="btn">@include('partials.icon', ['name' => 'download', 'size' => 17])Ekspor Excel</a>
            <a href="{{ route('reports.print', ['periode' => $period, 'tab' => $tab]) }}" class="btn btn-primary" target="_blank" rel="noopener">@include('partials.icon', ['name' => 'printer', 'size' => 17])Cetak / PDF</a>
        </div>
    </header>

    <section class="grid-kpi">
        <div class="kpi"><div class="k">Pendapatan</div><div class="v">{{ Fmt::rp($summary['income']) }}</div><div class="d">{{ $chg($summary['income'], $prev['income']) }}</div></div>
        <div class="kpi"><div class="k">Pengeluaran</div><div class="v">{{ Fmt::rp($summary['expense']) }}</div><div class="d">{{ $chg($summary['expense'], $prev['expense']) }}</div></div>
        <div class="kpi"><div class="k">Laba bersih</div><div class="v {{ $summary['profit'] < 0 ? 'text-out' : 'text-in' }}">{{ Fmt::rp($summary['profit']) }}</div><div class="d">Margin {{ str_replace('.', ',', $summary['margin']) }}%</div></div>
        <div class="kpi"><div class="k">Pesanan diserahkan</div><div class="v">{{ $ps['completed'] }}</div><div class="d">dari {{ $ps['orders'] }} pesanan · {{ Fmt::num($ps['qty']) }} pcs</div></div>
    </section>

    <nav class="tabs" aria-label="Jenis laporan">
        @foreach (['laba-rugi' => 'Laba Rugi', 'arus-kas' => 'Arus Kas', 'produk' => 'Penjualan per Produk'] as $k => $l)
            <a href="{{ route('reports.index', ['tab' => $k, 'periode' => $period]) }}" class="{{ $tab === $k ? 'on' : '' }}" @if ($tab === $k) aria-current="page" @endif>{{ $l }}</a>
        @endforeach
    </nav>

    @include('reports.partials.statements')
@endsection
