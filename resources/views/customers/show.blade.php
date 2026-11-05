@extends('layouts.app')
@section('title', $customer->name)

@php use App\Support\Fmt; @endphp

@section('content')
    <a href="{{ route('customers.index') }}" class="back">@include('partials.icon', ['name' => 'chevron-left', 'size' => 16, 'stroke' => 2])Semua pelanggan</a>

    <header class="page-head" style="align-items: center">
        <div style="display: flex; align-items: center; gap: 16px; min-width: 0">
            <span class="avatar sq" style="width: 56px; height: 56px; font-size: 18px; border-radius: 16px">{{ $customer->initials() }}</span>
            <div style="min-width: 0">
                <h1 class="page-title" style="margin: 0">{{ $customer->name }}</h1>
                <p class="page-sub" style="margin-top: 2px">{{ \App\Models\Customer::TYPES[$customer->type] ?? '' }}{{ $customer->contact_person ? ' · '.$customer->contact_person : '' }}{{ $customer->phone ? ' · '.$customer->phone : '' }}</p>
            </div>
        </div>
        <div class="actions" style="width: auto">
            @if ($customer->whatsappUrl())<a href="{{ $customer->whatsappUrl() }}" class="btn" target="_blank" rel="noopener">@include('partials.icon', ['name' => 'whatsapp', 'size' => 17])WhatsApp</a>@endif
            <a href="{{ route('customers.edit', $customer) }}" class="btn">@include('partials.icon', ['name' => 'edit', 'size' => 17])Ubah</a>
            <a href="{{ route('orders.create', ['pelanggan' => $customer->id]) }}" class="btn btn-primary">@include('partials.icon', ['name' => 'plus', 'stroke' => 2.2])Pesanan baru</a>
        </div>
    </header>

    <section class="grid-kpi">
        <div class="kpi"><div class="k">Jumlah pesanan</div><div class="v">{{ $stats['count'] }}</div></div>
        <div class="kpi"><div class="k">Total belanja</div><div class="v">{{ Fmt::rp($stats['spent']) }}</div></div>
        <div class="kpi"><div class="k">Sisa tagihan</div><div class="v {{ $stats['owed'] ? 'text-out' : '' }}">{{ Fmt::rp($stats['owed']) }}</div></div>
    </section>

    <div class="split">
        <section class="card flush col-main" aria-labelledby="h-h">
            <div class="card-head" style="padding: 12px 12px 0"><h2 id="h-h" class="card-title">Riwayat Pesanan</h2></div>
            @if ($orders->isEmpty())
                <div class="empty"><strong>Belum ada pesanan</strong></div>
            @else
                <div class="table-wrap">
                    <table class="table" style="min-width: 640px">
                        <thead><tr><th scope="col">Pesanan</th><th scope="col">Item</th><th scope="col">Status</th><th scope="col" class="right">Total</th><th scope="col"><span class="sr-only">Aksi</span></th></tr></thead>
                        <tbody>
                            @foreach ($orders as $o)
                                <tr>
                                    <td><a href="{{ route('orders.show', $o) }}" class="t num">{{ $o->code }}</a><div class="sub">{{ Fmt::date($o->order_date, 'j M Y') }}</div></td>
                                    <td><div>{{ $o->itemSummary() }}</div><div class="sub">{{ Fmt::num($o->total_qty) }} pcs{{ $o->design_file_name ? ' · desain tersimpan' : '' }}</div></td>
                                    <td><div style="display: flex; flex-wrap: wrap; gap: 4px">@include('partials.order-chips', ['order' => $o])</div></td>
                                    <td class="right num strong">{{ Fmt::rp($o->total) }}</td>
                                    <td class="right">@if ($o->status !== 'batal')<a href="{{ route('orders.create', ['ulang' => $o->id]) }}" class="btn btn-sm btn-outline">@include('partials.icon', ['name' => 'repeat', 'size' => 15])Pesan ulang</a>@endif</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <aside class="col-side">
            <section class="card">
                <h2 class="card-title sm" style="margin-bottom: 12px">Data Pelanggan</h2>
                <div class="sumrow"><span class="muted">Email</span><span>{{ $customer->email ?? '–' }}</span></div>
                <div class="sumrow"><span class="muted">Alamat</span><span style="text-align: right">{{ $customer->address ?? '–' }}</span></div>
                <div class="sumrow"><span class="muted">Terdaftar</span><span>{{ Fmt::date($customer->created_at, 'j M Y') }}</span></div>
                @if ($customer->notes)<div class="note" style="margin-top: 12px"><strong>Catatan</strong>{{ $customer->notes }}</div>@endif
            </section>
            @if ($orders->isEmpty())
                <form method="post" action="{{ route('customers.destroy', $customer) }}" data-confirm="Hapus pelanggan {{ $customer->name }}?">
                    @csrf @method('DELETE')
                    <button class="btn btn-ghost btn-danger btn-sm btn-block" type="submit">Hapus pelanggan</button>
                </form>
            @endif
        </aside>
    </div>
@endsection
