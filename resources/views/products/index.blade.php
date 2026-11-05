@extends('layouts.app')
@section('title', 'Produk & Harga')

@php use App\Support\Fmt; @endphp

@section('content')
    <header class="page-head">
        <div>
            <div class="eyebrow">Pengelolaan</div>
            <h1 class="page-title">Produk &amp; Harga</h1>
            <p class="page-sub">Daftar harga yang muncul saat membuat pesanan. Perubahan harga tidak mengubah pesanan lama.</p>
        </div>
        <div class="actions"><a href="{{ route('products.create') }}" class="btn btn-primary">@include('partials.icon', ['name' => 'plus', 'stroke' => 2.2])Produk</a></div>
    </header>

    <section class="card flush">
        <div class="table-wrap">
            <table class="table" style="min-width: 680px">
                <thead><tr><th scope="col">Produk</th><th scope="col">Kategori</th><th scope="col" class="right">Harga</th><th scope="col" class="right">Terjual</th><th scope="col">Status</th><th scope="col"><span class="sr-only">Aksi</span></th></tr></thead>
                <tbody>
                    @foreach ($products as $p)
                        <tr>
                            <td><div class="t">{{ $p->name }}</div><div class="sub">{{ $p->description }}</div></td>
                            <td>{{ $p->category }}</td>
                            <td class="right num strong">{{ Fmt::rp($p->price) }} <span class="sub">/ {{ $p->unit }}</span></td>
                            <td class="right num">{{ Fmt::num($p->sold ?? 0) }} {{ $p->unit }}</td>
                            <td><span class="chip {{ $p->is_active ? 'chip-good' : 'chip-muted' }}">{{ $p->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                            <td class="right"><a href="{{ route('products.edit', $p) }}" class="btn btn-sm">Ubah</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endsection
