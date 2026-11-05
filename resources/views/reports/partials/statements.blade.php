{{-- Isi laporan; dipakai halaman Laporan dan versi cetak. Butuh: $tab, $pl, $cf, $ps, $periodLabel, $from, $to --}}
@php
    use App\Support\Fmt;
    $paren = fn ($v) => '('.Fmt::rp($v, false).')';
    $range = Fmt::date($from, 'j M').' – '.Fmt::date($to, 'j M Y');
    $store = \App\Models\Setting::get('store_name');
@endphp

@if ($tab === 'laba-rugi' || ! empty($all))
    <section class="card statement" aria-label="Laporan laba rugi" @if (! empty($all)) style="margin-bottom: 28px" @endif>
        <div class="head">
            <div class="n">{{ $store }}</div>
            <div class="t">Laporan Laba Rugi</div>
            <div class="small muted">Periode {{ $range }} · basis kas</div>
        </div>
        <div class="sec">Pendapatan</div>
        @forelse ($pl['revenue'] as $k => $v)
            <div class="ln"><span>{{ $k }}</span><span class="num">{{ Fmt::rp($v, false) }}</span></div>
        @empty
            <div class="ln muted"><span>Tidak ada pendapatan</span><span>0</span></div>
        @endforelse
        <div class="ln sum"><span>Total pendapatan</span><span class="num">{{ Fmt::rp($pl['totalRevenue'], false) }}</span></div>

        <div class="sec">Harga pokok produksi</div>
        @forelse ($pl['cogs'] as $k => $v)
            <div class="ln"><span>{{ $k }}</span><span class="num">{{ $paren($v) }}</span></div>
        @empty
            <div class="ln muted"><span>Tidak ada</span><span>0</span></div>
        @endforelse
        <div class="ln sum"><span>Laba kotor</span><span class="num">{{ Fmt::rp($pl['gross'], false) }}</span></div>

        <div class="sec">Beban operasional</div>
        @forelse ($pl['opex'] as $k => $v)
            <div class="ln"><span>{{ $k }}</span><span class="num">{{ $paren($v) }}</span></div>
        @empty
            <div class="ln muted"><span>Tidak ada</span><span>0</span></div>
        @endforelse
        <div class="ln sum"><span>Total beban operasional</span><span class="num">{{ $paren($pl['totalOpex']) }}</span></div>

        <div class="ln grand {{ $pl['net'] < 0 ? 'loss' : '' }}"><span>{{ $pl['net'] < 0 ? 'RUGI BERSIH' : 'LABA BERSIH' }}</span><span class="num">{{ Fmt::rp($pl['net']) }}</span></div>
        <p class="xsmall muted" style="margin: 12px 0 0; line-height: 1.5">Pendapatan pesanan dirinci per kategori produk secara proporsional terhadap nilai item. Setoran modal dan penarikan pemilik (prive) tidak dihitung sebagai pendapatan atau beban.</p>
        @if (! empty($signatures)) @include('reports.partials.sign') @endif
    </section>
@endif

@if ($tab === 'arus-kas' || ! empty($all))
    <section class="card statement" aria-label="Laporan arus kas" @if (! empty($all)) style="margin-bottom: 28px; break-before: page" @endif>
        <div class="head">
            <div class="n">{{ $store }}</div>
            <div class="t">Laporan Arus Kas</div>
            <div class="small muted">Periode {{ $range }}</div>
        </div>
        <div class="ln sum" style="border: 0"><span>Saldo kas awal</span><span class="num">{{ Fmt::rp($cf['opening'], false) }}</span></div>
        <div class="sec">Kas masuk</div>
        @forelse ($cf['in'] as $k => $v)
            <div class="ln"><span>{{ $k }}</span><span class="num">{{ Fmt::rp($v, false) }}</span></div>
        @empty
            <div class="ln muted"><span>Tidak ada</span><span>0</span></div>
        @endforelse
        <div class="ln sum"><span>Total kas masuk</span><span class="num">{{ Fmt::rp($cf['totalIn'], false) }}</span></div>
        <div class="sec">Kas keluar</div>
        @forelse ($cf['out'] as $k => $v)
            <div class="ln"><span>{{ $k }}</span><span class="num">{{ $paren($v) }}</span></div>
        @empty
            <div class="ln muted"><span>Tidak ada</span><span>0</span></div>
        @endforelse
        <div class="ln sum"><span>Total kas keluar</span><span class="num">{{ $paren($cf['totalOut']) }}</span></div>
        <div class="ln grand neutral"><span>Saldo kas akhir</span><span class="num">{{ Fmt::rp($cf['closing']) }}</span></div>
        <div style="display: flex; flex-wrap: wrap; gap: 8px 20px; margin-top: 12px; font-size: 13px" class="muted">
            @foreach ($cf['byAccount'] as $k => $v)<span>{{ $k }}: <b class="num" style="color: var(--ink)">{{ Fmt::rp($v) }}</b></span>@endforeach
        </div>
        @if (! empty($signatures)) @include('reports.partials.sign') @endif
    </section>
@endif

@if ($tab === 'produk' || ! empty($all))
    <section class="card statement" aria-label="Penjualan per produk" @if (! empty($all)) style="break-before: page" @endif>
        <div class="head">
            <div class="n">{{ $store }}</div>
            <div class="t">Rekap Penjualan per Produk</div>
            <div class="small muted">Pesanan tanggal {{ $range }} · {{ $ps['orders'] }} pesanan · {{ Fmt::num($ps['qty']) }} pcs</div>
        </div>
        @if (empty($ps['byCategory']))
            <p class="muted">Belum ada pesanan pada periode ini.</p>
        @else
            <div style="display: flex; flex-direction: column; gap: 16px; margin-top: 16px">
                @foreach ($ps['byCategory'] as $c)
                    <div>
                        <div style="display: flex; justify-content: space-between; gap: 12px; font-size: 14px">
                            <span class="strong">{{ $c['category'] }} <span class="muted" style="font-weight: 500">· {{ Fmt::num($c['qty']) }} pcs</span></span>
                            <span class="num strong">{{ Fmt::rp($c['amount']) }} <span class="muted" style="font-weight: 600">({{ str_replace('.', ',', $c['share']) }}%)</span></span>
                        </div>
                        <div class="bar" style="margin-top: 8px; height: 12px"><div style="width: {{ $c['share'] }}%"></div></div>
                    </div>
                @endforeach
            </div>
            <h3 style="margin: 24px 0 8px; font-size: 14px; font-weight: 800">Produk terlaris</h3>
            <table class="table">
                <thead><tr><th scope="col">Produk</th><th scope="col" class="right">Jumlah</th><th scope="col" class="right">Nilai</th></tr></thead>
                <tbody>
                    @foreach ($ps['topProducts'] as $p)
                        <tr><td>{{ $p['name'] }}</td><td class="right num">{{ Fmt::num($p['qty']) }} pcs</td><td class="right num strong">{{ Fmt::rp($p['amount']) }}</td></tr>
                    @endforeach
                    <tr><td class="strong">Total</td><td class="right num strong">{{ Fmt::num($ps['qty']) }} pcs</td><td class="right num strong">{{ Fmt::rp($ps['total']) }}</td></tr>
                </tbody>
            </table>
        @endif
    </section>
@endif
