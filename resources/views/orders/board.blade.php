@extends('layouts.app')
@section('title', 'Pesanan & Antrean')

@php
    use App\Support\Fmt;
    $me = auth()->user();
    $lanes = [
        'masuk' => ['Pesanan Masuk', '#8A93A6'],
        'antre' => ['Antre', '#F0B429'],
        'produksi' => ['Diproduksi', '#1B2A5E'],
        'selesai' => ['Siap Diambil', '#0E7A5A'],
        'diserahkan' => ['Diserahkan (7 hari)', '#C3C8D4'],
    ];
@endphp

@section('content')
    @include('orders.partials.head')

    <section class="card tight" aria-labelledby="cap-h">
        <div class="card-head">
            <h2 id="cap-h" class="card-title sm">Jadwal Mesin {{ count($outlook) }} Hari Kerja ke Depan</h2>
            <span class="small muted">Kapasitas {{ Fmt::num($outlook[0]['capacity']) }} pcs/hari @if ($me->isOwner())· <a href="{{ route('settings.edit') }}">ubah</a>@endif</span>
        </div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px">
            @foreach ($outlook as $d)
                <div>
                    <div style="display: flex; justify-content: space-between; font-size: 13px">
                        <span class="strong">{{ Fmt::date($d['date'], 'l, j M') }}</span>
                        <span class="num muted">{{ Fmt::num($d['load']) }} / {{ Fmt::num($d['capacity']) }} pcs</span>
                    </div>
                    <div class="bar {{ $d['percent'] >= 90 ? 'warn' : ($d['percent'] < 40 ? 'good' : '') }}" style="margin-top: 8px"><div style="width: {{ $d['percent'] }}%"></div></div>
                    <div class="xsmall" style="margin-top: 6px; font-weight: 700; color: {{ $d['percent'] >= 90 ? 'var(--yellow-ink)' : ($d['percent'] < 40 ? 'var(--green-ink)' : 'var(--muted)') }}">
                        {{ $d['percent'] >= 100 ? 'Penuh' : ($d['percent'] >= 90 ? 'Hampir penuh' : ($d['percent'] < 40 ? 'Masih longgar' : 'Sisa '.Fmt::num($d['left']).' pcs')) }}
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <section class="board-wrap" aria-label="Papan status pesanan">
        <div class="board">
            @foreach ($lanes as $status => [$label, $color])
                <div class="lane">
                    <div class="lane-head">
                        <span><i style="background: {{ $color }}"></i>{{ $label }}</span>
                        <span class="chip num" style="background: #fff; color: var(--ink-3)">{{ $columns[$status]->count() }}</span>
                    </div>
                    @forelse ($columns[$status] as $o)
                        @php $step = $o->nextStep(); @endphp
                        <article class="ocard {{ $o->status === 'produksi' ? 'run' : '' }}">
                            <div class="top">
                                <a href="{{ route('orders.show', $o) }}" class="code num">{{ $o->code }}</a>
                                @include('partials.order-chips', ['order' => $o, 'onlyPay' => true])
                            </div>
                            <h3><a href="{{ route('orders.show', $o) }}">{{ $o->itemSummary() }}</a></h3>
                            <div class="cust">{{ $o->customer->name }}</div>
                            <div class="meta">
                                <span class="num">{{ Fmt::num($o->total_qty) }} pcs</span>
                                <span>
                                    @switch($o->status)
                                        @case('antre') Jadwal {{ Fmt::date($o->scheduled_date, 'D j M') }} @break
                                        @case('produksi') Mulai {{ Fmt::date($o->started_at, 'H.i') }} @break
                                        @case('selesai') Selesai {{ Fmt::date($o->finished_at, 'j M H.i') }} @break
                                        @case('diserahkan') Diserahkan {{ Fmt::date($o->handed_over_at, 'j M') }} @break
                                        @default @if ($o->due_date) Target {{ Fmt::date($o->due_date, 'j M') }} @endif
                                    @endswitch
                                </span>
                            </div>
                            @if ($step && $me->hasRole(...$step['roles']))
                                <form method="post" action="{{ route('orders.advance', $o) }}">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="btn btn-sm btn-block {{ $o->status === 'produksi' ? 'btn-success' : '' }}">{{ $step['label'] }}</button>
                                </form>
                            @endif
                        </article>
                    @empty
                        <div class="lane-empty">Kosong</div>
                    @endforelse
                    @if ($status === 'diserahkan')
                        <a href="{{ route('orders.index', ['tampilan' => 'tabel', 'status' => 'diserahkan']) }}" class="btn btn-ghost btn-sm">Lihat semua riwayat</a>
                    @endif
                </div>
            @endforeach
        </div>
    </section>
@endsection
