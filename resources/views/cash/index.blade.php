@extends('layouts.app')
@section('title', 'Kas Masuk & Keluar')

@php
    use App\Support\Fmt;
    $me = auth()->user();
    $tabs = ['' => 'Semua', 'masuk' => 'Masuk', 'keluar' => 'Keluar'];
@endphp

@section('content')
    <header class="page-head">
        <div>
            <div class="eyebrow">Buku Kas · {{ $periodLabel }}</div>
            <h1 class="page-title">Kas Masuk &amp; Keluar</h1>
            <p class="page-sub">Setiap transaksi bernomor bukti, berlampiran nota, dan tidak bisa dihapus, hanya dibatalkan dengan alasan.</p>
        </div>
        <div class="actions">
            <a href="{{ route('cash.export', request()->query()) }}" class="btn">@include('partials.icon', ['name' => 'download', 'size' => 17])Ekspor Excel</a>
            <a href="{{ route('cash.create', ['jenis' => 'masuk']) }}" class="btn">@include('partials.icon', ['name' => 'cash-in', 'size' => 17])Kas masuk</a>
            <a href="{{ route('cash.create') }}" class="btn btn-primary">@include('partials.icon', ['name' => 'cash-out', 'size' => 17])Catat pengeluaran</a>
        </div>
    </header>

    <section class="grid-kpi" aria-label="Saldo">
        <div class="kpi dark">
            <div class="k">Saldo kas saat ini</div>
            <div class="v">{{ Fmt::rp($balance) }}</div>
            <div class="d">@foreach ($balanceByAccount as $k => $v){{ config('sipz.accounts')[$k] }} {{ Fmt::rp($v) }}@if (! $loop->last) · @endif @endforeach</div>
        </div>
        <div class="kpi"><div class="k">Pendapatan {{ $periodLabel }}</div><div class="v text-in">{{ Fmt::rp($summary['income']) }}</div><div class="d">{{ $summary['count_in'] }} transaksi usaha</div></div>
        <div class="kpi"><div class="k">Pengeluaran {{ $periodLabel }}</div><div class="v text-out">{{ Fmt::rp($summary['expense']) }}</div><div class="d">{{ $summary['count_out'] }} transaksi usaha</div></div>
        <a class="kpi" href="{{ route('cash.index', ['status' => 'menunggu', 'periode' => $period]) }}" style="text-decoration: none; color: inherit">
            <div class="k">Menunggu persetujuan @if ($pendingCount)<span class="chip chip-warn">{{ $pendingCount }}</span>@endif</div>
            <div class="v">{{ $pendingCount }}</div>
            <div class="d">Pengeluaran di atas {{ Fmt::rp($limit) }}</div>
        </a>
    </section>

    <div class="split">
        <section class="card flush col-main" aria-labelledby="t-h">
            <div style="padding: 12px 12px 4px; display: flex; flex-direction: column; gap: 12px">
                <div class="card-head" style="margin: 0">
                    <h2 id="t-h" class="card-title">Daftar Transaksi</h2>
                    <nav class="seg" aria-label="Jenis transaksi">
                        @foreach ($tabs as $k => $l)
                            <a href="{{ route('cash.index', array_filter(['jenis' => $k, 'periode' => $period])) }}" class="{{ ($filters['jenis'] ?? '') === $k && empty($filters['status']) ? 'on' : '' }}">{{ $l }}</a>
                        @endforeach
                        <a href="{{ route('cash.index', ['status' => 'menunggu', 'periode' => $period]) }}" class="{{ ($filters['status'] ?? '') === 'menunggu' ? 'on' : '' }}">Menunggu</a>
                    </nav>
                </div>
                <form method="get" action="{{ route('cash.index') }}" class="row-3" style="align-items: end">
                    @foreach (['jenis', 'status'] as $keep)@if (! empty($filters[$keep]))<input type="hidden" name="{{ $keep }}" value="{{ $filters[$keep] }}">@endif @endforeach
                    <div class="field"><label for="f-per">Periode</label><select id="f-per" name="periode" class="select sm" data-autosubmit>@foreach ($periods as $k => $l)<option value="{{ $k }}" @selected($period === (string) $k)>{{ $l }}</option>@endforeach</select></div>
                    <div class="field"><label for="f-kat">Kategori</label><select id="f-kat" name="kategori" class="select sm" data-autosubmit><option value="">Semua kategori</option>@foreach ($categories as $c)<option @selected(($filters['kategori'] ?? '') === $c)>{{ $c }}</option>@endforeach</select></div>
                    <div class="field"><label for="f-q">Cari</label><input id="f-q" name="q" class="input sm" value="{{ $filters['q'] ?? '' }}" placeholder="No. bukti / keterangan"></div>
                </form>
            </div>

            @if ($transactions->isEmpty())
                <div class="empty"><div class="ic">@include('partials.icon', ['name' => 'wallet', 'size' => 24])</div><strong>Tidak ada transaksi</strong>Ubah periode atau filter.</div>
            @else
                <div class="table-wrap">
                    <table class="table" style="min-width: 760px">
                        <thead><tr><th scope="col">Tanggal / Bukti</th><th scope="col">Keterangan</th><th scope="col">Pencatat</th><th scope="col">Status</th><th scope="col" class="right">Jumlah</th></tr></thead>
                        <tbody>
                            @foreach ($transactions as $t)
                                <tr class="{{ $t->status === 'batal' || $t->status === 'ditolak' ? 'row-void' : ($t->status === 'menunggu' ? 'row-wait' : '') }}">
                                    <td><div class="t num">{{ Fmt::date($t->date, 'j M Y') }}</div><div class="sub num">{{ $t->code }}</div></td>
                                    <td style="min-width: 240px">
                                        <div class="t">{{ $t->description }}</div>
                                        <div class="sub">
                                            {{ $t->category }} · {{ $t->accountLabel() }}
                                            @if ($t->order) · <a href="{{ route('orders.show', $t->order) }}">{{ $t->order->code }}</a>@endif
                                            @if ($t->receipt_path) · <a href="{{ route('cash.receipt', $t) }}" target="_blank" rel="noopener">lihat nota</a>@endif
                                        </div>
                                        @if ($t->status === 'batal')<div class="xsmall text-out" style="margin-top: 4px">Dibatalkan {{ $t->voider?->name }} {{ Fmt::date($t->voided_at, 'j M H.i') }}: {{ $t->void_reason }}</div>@endif
                                        @if ($t->status === 'ditolak')<div class="xsmall text-out" style="margin-top: 4px">Ditolak {{ $t->approver?->name }}: {{ $t->reject_reason }}</div>@endif
                                        @if ($t->status === 'tercatat' && $t->approver && $t->approved_by !== $t->created_by)<div class="xsmall muted" style="margin-top: 4px">Disetujui {{ $t->approver->name }} · {{ Fmt::date($t->approved_at, 'j M H.i') }}</div>@endif

                                        @if ($me->isOwner() && $t->status === 'menunggu')
                                            <div style="display: flex; flex-wrap: wrap; gap: 6px; margin-top: 8px">
                                                <form method="post" action="{{ route('cash.approve', $t) }}">@csrf<button class="btn btn-primary btn-sm" type="submit">Setujui</button></form>
                                                <form method="post" action="{{ route('cash.reject', $t) }}" data-prompt="Alasan menolak {{ $t->code }}:" data-prompt-field="reason">@csrf<input type="hidden" name="reason"><button class="btn btn-sm" type="submit">Tolak</button></form>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="small muted" style="white-space: nowrap">{{ $t->creator?->signature() }}<div class="xsmall">{{ Fmt::date($t->created_at, 'j M, H.i') }}</div></td>
                                    <td>
                                        @include('partials.cash-status', ['tx' => $t])
                                        @if ($me->isOwner() && $t->status === 'tercatat')
                                            <form method="post" action="{{ route('cash.void', $t) }}" data-prompt="Alasan membatalkan {{ $t->code }} ({{ Fmt::rp($t->amount) }}). Transaksi tidak dihapus, hanya ditandai batal:" data-prompt-field="reason" style="margin-top: 6px">@csrf<input type="hidden" name="reason"><button class="link-btn" type="submit" aria-label="Batalkan {{ $t->code }}">Batalkan</button></form>
                                        @endif
                                    </td>
                                    <td class="right num strong amt {{ $t->isIn() ? 'text-in' : 'text-out' }}">{{ $t->signedAmount() }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @include('partials.pager', ['paginator' => $transactions])
            @endif
        </section>

        <aside class="col-side">
            <section class="card" aria-labelledby="rule-h">
                <h2 id="rule-h" class="card-title sm" style="margin-bottom: 10px">Aturan Akuntabilitas</h2>
                <ul class="timeline small">
                    <li><span class="dot info"></span><span>Nomor bukti dibuat otomatis dan berurutan (KM = kas masuk, KK = kas keluar).</span></li>
                    <li><span class="dot info"></span><span>Pengeluaran wajib melampirkan foto nota.</span></li>
                    <li><span class="dot good"></span><span>Pengeluaran di atas {{ Fmt::rp($limit) }} oleh Admin menunggu persetujuan Owner.</span></li>
                    <li><span class="dot bad"></span><span>Transaksi tidak bisa dihapus. Kesalahan dibatalkan Owner dengan alasan, dan tetap tampil di riwayat.</span></li>
                </ul>
            </section>

            <section class="card" aria-labelledby="a-h">
                <div class="card-head" style="margin-bottom: 12px">
                    <h2 id="a-h" class="card-title sm">Jejak Audit Kas</h2>
                    @if ($me->isOwner())<a href="{{ route('audit.index') }}" class="card-link">Semua</a>@endif
                </div>
                <ul class="timeline">
                    @forelse ($audit as $log)
                        <li><span class="dot {{ $log->tone() }}"></span><span><strong>{{ $log->user?->name ?? 'Sistem' }}</strong> {{ lcfirst($log->description) }}<br><span class="muted xsmall">{{ Fmt::date($log->created_at, 'j M, H.i') }}</span></span></li>
                    @empty
                        <li class="muted small">Belum ada aktivitas.</li>
                    @endforelse
                </ul>
            </section>
        </aside>
    </div>
@endsection
