@extends('layouts.app')
@section('title', 'Pengguna')

@php use App\Support\Fmt; @endphp

@section('content')
    <header class="page-head">
        <div>
            <div class="eyebrow">Pengelolaan</div>
            <h1 class="page-title">Pengguna &amp; Hak Akses</h1>
            <p class="page-sub">Setiap orang memakai akunnya sendiri, supaya setiap catatan jelas siapa pencatatnya.</p>
        </div>
        <div class="actions"><a href="{{ route('users.create') }}" class="btn btn-primary">@include('partials.icon', ['name' => 'plus', 'stroke' => 2.2])Pengguna</a></div>
    </header>

    <section class="card flush">
        <div class="table-wrap">
            <table class="table" style="min-width: 680px">
                <thead><tr><th scope="col">Nama</th><th scope="col">Username</th><th scope="col">Peran</th><th scope="col">Login terakhir</th><th scope="col">Status</th><th scope="col"><span class="sr-only">Aksi</span></th></tr></thead>
                <tbody>
                    @foreach ($users as $u)
                        <tr>
                            <td><div style="display: flex; align-items: center; gap: 12px"><span class="avatar {{ $u->role === 'owner' ? '' : 'alt' }}" style="width: 34px; height: 34px; font-size: 12px">{{ $u->initials() }}</span><span class="t">{{ $u->name }}</span></div></td>
                            <td class="num">{{ $u->username }}</td>
                            <td><span class="chip {{ ['owner' => 'chip-dark', 'admin' => 'chip-info', 'operator' => 'chip-warn'][$u->role] ?? 'chip-muted' }}">{{ $u->roleLabel() }}</span></td>
                            <td class="muted small">{{ $u->last_login_at ? Fmt::date($u->last_login_at, 'j M Y, H.i') : 'Belum pernah' }}</td>
                            <td><span class="chip {{ $u->is_active ? 'chip-good' : 'chip-muted' }}">{{ $u->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                            <td class="right"><a href="{{ route('users.edit', $u) }}" class="btn btn-sm">Ubah</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <section class="card">
        <h2 class="card-title sm" style="margin-bottom: 12px">Hak akses per peran</h2>
        <div class="table-wrap">
            <table class="table" style="min-width: 560px">
                <thead><tr><th scope="col">Fitur</th><th scope="col">Owner</th><th scope="col">Admin</th><th scope="col">Operator</th></tr></thead>
                <tbody>
                    @foreach ([
                        ['Dashboard keuangan', 1, 1, 0],
                        ['Membuat & mengubah pesanan, pelanggan', 1, 1, 0],
                        ['Konfirmasi pesanan ke antrean, serahkan pesanan', 1, 1, 0],
                        ['Mulai & selesaikan produksi', 1, 0, 1],
                        ['Mencatat kas masuk/keluar', 1, 1, 0],
                        ['Menyetujui / menolak / membatalkan transaksi', 1, 0, 0],
                        ['Laporan, jejak audit, produk, pengguna, pengaturan', 1, 0, 0],
                    ] as [$f, $o, $a, $op])
                        <tr><td>{{ $f }}</td>@foreach ([$o, $a, $op] as $v)<td>@if ($v)<span class="text-in">@include('partials.icon', ['name' => 'check', 'size' => 18, 'stroke' => 2.4])</span><span class="sr-only">Ya</span>@else<span class="muted">–</span>@endif</td>@endforeach</tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endsection
