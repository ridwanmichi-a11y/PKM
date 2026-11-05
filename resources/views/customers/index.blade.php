@extends('layouts.app')
@section('title', 'Pelanggan')

@php use App\Support\Fmt; @endphp

@section('content')
    <header class="page-head">
        <div>
            <div class="eyebrow">Data Pelanggan</div>
            <h1 class="page-title">Pelanggan</h1>
            <p class="page-sub">Riwayat setiap pelanggan tersimpan, jadi pesanan ulang tidak perlu mencari chat WhatsApp lama.</p>
        </div>
        <div class="actions">
            <form method="get" action="{{ route('customers.index') }}" role="search" style="display: contents">
                <label class="search">@include('partials.icon', ['name' => 'search'])<span class="sr-only">Cari pelanggan</span><input type="search" name="q" value="{{ $q }}" placeholder="Nama, kontak, atau no. HP"></label>
            </form>
            <a href="{{ route('customers.create') }}" class="btn btn-primary">@include('partials.icon', ['name' => 'plus', 'stroke' => 2.2])Pelanggan</a>
        </div>
    </header>

    <section class="card flush">
        @if ($customers->isEmpty())
            <div class="empty"><div class="ic">@include('partials.icon', ['name' => 'users', 'size' => 24])</div><strong>Belum ada pelanggan</strong>{{ $q ? 'Tidak ada yang cocok dengan pencarian.' : 'Tambahkan pelanggan pertama.' }}</div>
        @else
            <div class="table-wrap">
                <table class="table" style="min-width: 760px">
                    <thead><tr><th scope="col">Pelanggan</th><th scope="col">Kontak</th><th scope="col" class="right">Pesanan</th><th scope="col" class="right">Total belanja</th><th scope="col">Terakhir pesan</th><th scope="col"><span class="sr-only">Aksi</span></th></tr></thead>
                    <tbody>
                        @foreach ($customers as $c)
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 12px">
                                        <span class="avatar" style="width: 34px; height: 34px; font-size: 12px">{{ $c->initials() }}</span>
                                        <span><a href="{{ route('customers.show', $c) }}" class="t">{{ $c->name }}</a><div class="sub">{{ \App\Models\Customer::TYPES[$c->type] ?? $c->type }}</div></span>
                                    </div>
                                </td>
                                <td><div>{{ $c->contact_person ?? '–' }}</div><div class="sub">{{ $c->phone }}</div></td>
                                <td class="right num">{{ $c->orders_count }}</td>
                                <td class="right num strong">{{ Fmt::rp($c->total_spent ?? 0) }}</td>
                                <td class="muted small">{{ $c->last_order ? Fmt::date($c->last_order, 'j M Y') : '–' }}</td>
                                <td class="right"><a href="{{ route('orders.create', ['pelanggan' => $c->id]) }}" class="btn btn-sm">Pesanan baru</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @include('partials.pager', ['paginator' => $customers])
        @endif
    </section>
@endsection
