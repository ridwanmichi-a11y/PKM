@extends('layouts.app')
@section('title', 'Riwayat Pesanan')

@php use App\Support\Fmt; @endphp

@section('content')
    @include('orders.partials.head')

    <section class="card tight">
        <form method="get" action="{{ route('orders.index') }}" class="row-3" style="align-items: end">
            <input type="hidden" name="tampilan" value="tabel">
            <input type="hidden" name="q" value="{{ $q }}">
            <div class="field">
                <label for="f-status">Status</label>
                <select id="f-status" name="status" class="select sm" data-autosubmit>
                    <option value="">Semua status</option>
                    @foreach (\App\Models\Order::STATUSES as $k => $l)
                        <option value="{{ $k }}" @selected(request('status') === $k)>{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="f-bayar">Pembayaran</label>
                <select id="f-bayar" name="bayar" class="select sm" data-autosubmit>
                    <option value="">Semua</option>
                    <option value="belum-lunas" @selected(request('bayar') === 'belum-lunas')>Belum lunas</option>
                </select>
            </div>
            <div>
                @if (request()->hasAny(['status', 'bayar', 'q']) && (request('status') || request('bayar') || request('q')))
                    <a href="{{ route('orders.index', ['tampilan' => 'tabel']) }}" class="btn btn-ghost btn-sm">Hapus filter</a>
                @endif
            </div>
        </form>
    </section>

    <section class="card flush">
        @if ($orders->isEmpty())
            <div class="empty"><div class="ic">@include('partials.icon', ['name' => 'board', 'size' => 24])</div><strong>Tidak ada pesanan</strong>Coba ubah filter pencarian.</div>
        @else
            <div class="table-wrap">
                <table class="table" style="min-width: 820px">
                    <thead><tr><th scope="col">Pesanan</th><th scope="col">Pelanggan</th><th scope="col">Item</th><th scope="col">Status</th><th scope="col" class="right">Total</th><th scope="col" class="right">Sisa tagihan</th></tr></thead>
                    <tbody>
                        @foreach ($orders as $o)
                            <tr>
                                <td><a href="{{ route('orders.show', $o) }}" class="t num">{{ $o->code }}</a><div class="sub">{{ Fmt::date($o->order_date, 'j M Y') }}</div></td>
                                <td><div class="t">{{ $o->customer->name }}</div><div class="sub">{{ $o->customer->phone }}</div></td>
                                <td><div>{{ $o->itemSummary() }}</div><div class="sub num">{{ Fmt::num($o->total_qty) }} pcs</div></td>
                                <td><div style="display: flex; flex-wrap: wrap; gap: 4px">@include('partials.order-chips', ['order' => $o])</div></td>
                                <td class="right num strong">{{ Fmt::rp($o->total) }}</td>
                                <td class="right num {{ $o->remaining() > 0 && $o->status !== 'batal' ? 'strong text-out' : 'muted' }}">{{ $o->status === 'batal' ? '–' : Fmt::rp($o->remaining()) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @include('partials.pager', ['paginator' => $orders])
        @endif
    </section>
@endsection
