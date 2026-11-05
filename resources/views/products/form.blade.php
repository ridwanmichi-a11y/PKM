@extends('layouts.app')

@php $editing = $product->exists; @endphp
@section('title', $editing ? 'Ubah Produk' : 'Produk Baru')

@section('content')
    <header>
        <a href="{{ route('products.index') }}" class="back">@include('partials.icon', ['name' => 'chevron-left', 'size' => 16, 'stroke' => 2])Produk &amp; Harga</a>
        <h1 class="page-title" style="margin-top: 8px">{{ $editing ? 'Ubah '.$product->name : 'Produk Baru' }}</h1>
    </header>

    <form method="post" action="{{ $editing ? route('products.update', $product) : route('products.store') }}" class="card form" style="max-width: 760px" novalidate>
        @csrf
        @if ($editing) @method('PUT') @endif
        <div class="field">
            <label for="name">Nama produk</label>
            <input id="name" name="name" class="input @error('name') is-invalid @enderror" value="{{ old('name', $product->name) }}" required>
            @error('name')<div class="error">{{ $message }}</div>@enderror
        </div>
        <div class="row-3">
            <div class="field">
                <label for="category">Kategori laporan</label>
                <select id="category" name="category" class="select">@foreach (config('sipz.product_categories') as $c)<option @selected(old('category', $product->category) === $c)>{{ $c }}</option>@endforeach</select>
            </div>
            <div class="field"><label for="price">Harga (Rp)</label><input id="price" name="price" class="input num @error('price') is-invalid @enderror" inputmode="numeric" value="{{ old('price', $product->price ? number_format($product->price, 0, ',', '.') : '') }}" data-money>@error('price')<div class="error">{{ $message }}</div>@enderror</div>
            <div class="field"><label for="unit">Satuan</label><input id="unit" name="unit" class="input" value="{{ old('unit', $product->unit) }}"></div>
        </div>
        <div class="field"><label for="description">Keterangan singkat</label><input id="description" name="description" class="input" value="{{ old('description', $product->description) }}" placeholder="Contoh: Ukuran CR80, laminasi"></div>
        <label class="check"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $product->is_active))>Aktif (muncul di form pesanan)</label>
        <div class="form-actions">
            <a href="{{ route('products.index') }}" class="btn btn-ghost btn-lg">Batal</a>
            <button type="submit" class="btn btn-primary btn-lg">Simpan</button>
        </div>
    </form>
@endsection
