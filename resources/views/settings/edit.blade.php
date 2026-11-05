@extends('layouts.app')
@section('title', 'Pengaturan')

@section('content')
    <header>
        <div class="eyebrow">Pengelolaan</div>
        <h1 class="page-title">Pengaturan Toko</h1>
        <p class="page-sub">Setiap perubahan pengaturan tercatat di jejak audit.</p>
    </header>

    <form method="post" action="{{ route('settings.update') }}" class="split" novalidate>
        @csrf @method('PUT')
        <div class="col-main">
            <section class="card form">
                <h2 class="card-title sm">Identitas toko (tampil di nota &amp; laporan)</h2>
                <div class="row-2">
                    <div class="field"><label for="store_name">Nama toko</label><input id="store_name" name="store_name" class="input" value="{{ old('store_name', $s['store_name']) }}" required></div>
                    <div class="field"><label for="store_phone">Telepon / WhatsApp</label><input id="store_phone" name="store_phone" class="input" value="{{ old('store_phone', $s['store_phone']) }}"></div>
                </div>
                <div class="field"><label for="store_tagline">Slogan</label><input id="store_tagline" name="store_tagline" class="input" value="{{ old('store_tagline', $s['store_tagline']) }}"></div>
                <div class="field"><label for="store_address">Alamat</label><input id="store_address" name="store_address" class="input" value="{{ old('store_address', $s['store_address']) }}"></div>
            </section>

            <section class="card form">
                <h2 class="card-title sm">Produksi &amp; keuangan</h2>
                <div class="row-2">
                    <div class="field">
                        <label for="daily_capacity">Kapasitas mesin per hari (pcs)</label>
                        <input id="daily_capacity" name="daily_capacity" type="number" min="1" class="input num" value="{{ old('daily_capacity', $s['daily_capacity']) }}">
                        <div class="hint">Dipakai untuk menjadwalkan antrean otomatis.</div>
                    </div>
                    <div class="field">
                        <label for="approval_limit">Batas persetujuan pengeluaran (Rp)</label>
                        <input id="approval_limit" name="approval_limit" class="input num" inputmode="numeric" value="{{ old('approval_limit', number_format((int) $s['approval_limit'], 0, ',', '.')) }}" data-money>
                        <div class="hint">Pengeluaran Admin di atas nilai ini menunggu persetujuan Owner.</div>
                    </div>
                </div>
                <label class="check"><input type="checkbox" name="closed_on_sunday" value="1" @checked(old('closed_on_sunday', $s['closed_on_sunday']))>Hari Minggu libur (antrean tidak dijadwalkan di hari Minggu)</label>
            </section>
        </div>
        <aside class="col-side">
            <section class="card">
                <button type="submit" class="btn btn-primary btn-lg btn-block">Simpan pengaturan</button>
            </section>
        </aside>
    </form>
@endsection
