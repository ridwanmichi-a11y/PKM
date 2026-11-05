@extends('layouts.app')

@php
    use App\Support\Fmt;
    $in = $type === 'in';
    $cats = $in ? array_values(array_diff($categories, ['Pembayaran pesanan'])) : $categories;
@endphp
@section('title', $in ? 'Catat Kas Masuk' : 'Catat Pengeluaran')

@section('content')
    <header>
        <a href="{{ route('cash.index') }}" class="back">@include('partials.icon', ['name' => 'chevron-left', 'size' => 16, 'stroke' => 2])Kembali ke buku kas</a>
        <h1 class="page-title" style="margin-top: 8px">{{ $in ? 'Catat Kas Masuk' : 'Catat Pengeluaran' }}</h1>
        <p class="page-sub">
            {{ $in ? 'Untuk pemasukan di luar pesanan, misalnya setoran modal pemilik. Pembayaran pesanan dicatat dari halaman pesanannya.' : 'Lampirkan foto nota atau kuitansi. Nomor bukti dibuat otomatis.' }}
        </p>
    </header>

    <div class="split">
        <form method="post" action="{{ route('cash.store') }}" enctype="multipart/form-data" class="card form col-main" novalidate>
            @csrf
            <input type="hidden" name="type" value="{{ $type }}">
            <div class="row-2">
                <div class="field">
                    <label for="date">Tanggal</label>
                    <input id="date" name="date" type="date" class="input @error('date') is-invalid @enderror" value="{{ old('date', Fmt::today()->toDateString()) }}" max="{{ Fmt::today()->toDateString() }}">
                    @error('date')<div class="error">{{ $message }}</div>@enderror
                </div>
                <div class="field">
                    <label for="category">Kategori</label>
                    <select id="category" name="category" class="select @error('category') is-invalid @enderror">
                        @foreach ($cats as $c)<option @selected(old('category') === $c)>{{ $c }}</option>@endforeach
                    </select>
                </div>
            </div>
            <div class="field">
                <label for="description">Keterangan</label>
                <input id="description" name="description" class="input @error('description') is-invalid @enderror" value="{{ old('description') }}" placeholder="{{ $in ? 'Contoh: Setoran modal tambahan' : 'Contoh: Blank card PVC 500 lembar' }}" maxlength="255">
                @error('description')<div class="error">{{ $message }}</div>@enderror
            </div>
            <div class="row-2">
                <div class="field">
                    <label for="amount">Jumlah (Rp)</label>
                    <input id="amount" name="amount" class="input num @error('amount') is-invalid @enderror" inputmode="numeric" value="{{ old('amount') }}" placeholder="0" data-money>
                    @error('amount')<div class="error">{{ $message }}</div>@enderror
                </div>
                <div class="field">
                    <label for="account">{{ $in ? 'Masuk ke' : 'Dibayar dari' }}</label>
                    <select id="account" name="account" class="select">@foreach ($accounts as $k => $l)<option value="{{ $k }}" @selected(old('account', 'tunai') === $k)>{{ $l }}</option>@endforeach</select>
                </div>
            </div>
            <div class="field">
                <span class="label">Foto nota / kuitansi {{ $in ? '(opsional)' : '(wajib)' }}</span>
                <label class="dropzone @error('receipt') is-invalid @enderror" for="receipt">@include('partials.icon', ['name' => 'camera', 'size' => 18])<span>Ambil foto atau pilih file (JPG, PNG, PDF · maks. 5 MB)</span>
                    <input id="receipt" name="receipt" type="file" accept="image/*,.pdf" data-file-label="#receipt-name">
                </label>
                <div id="receipt-name" class="file-name"></div>
                @error('receipt')<div class="error">{{ $message }}</div>@enderror
            </div>
            <div class="form-actions">
                <a href="{{ route('cash.index') }}" class="btn btn-ghost btn-lg">Batal</a>
                <button type="submit" class="btn btn-primary btn-lg">Simpan</button>
            </div>
        </form>

        <aside class="col-side">
            @unless ($in)
                <div class="note warn">
                    <strong>Perlu persetujuan Owner</strong>
                    @if (auth()->user()->isOwner())
                        Anda Owner, jadi pengeluaran ini langsung tercatat.
                    @else
                        Pengeluaran di atas {{ Fmt::rp($limit) }} akan berstatus "Menunggu Owner" dan belum mengurangi saldo sampai disetujui.
                    @endif
                </div>
            @endunless
            <div class="note">
                <strong>Tidak bisa dihapus</strong>
                Jika ada salah ketik, Owner dapat membatalkan transaksi dengan alasan, lalu Anda mencatat ulang yang benar. Semua tercatat di jejak audit.
            </div>
        </aside>
    </div>
@endsection
