@extends('layouts.app')
@section('title', 'Jejak Audit')

@php use App\Support\Fmt; @endphp

@section('content')
    <header class="page-head">
        <div>
            <div class="eyebrow">Akuntabilitas</div>
            <h1 class="page-title">Jejak Audit</h1>
            <p class="page-sub">Catatan otomatis siapa melakukan apa dan kapan: login, pesanan, pembayaran, persetujuan, pembatalan, dan perubahan data. Catatan ini tidak bisa diubah atau dihapus dari aplikasi.</p>
        </div>
    </header>

    <section class="card tight">
        <form method="get" action="{{ route('audit.index') }}" class="row-3" style="grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); align-items: end">
            <div class="field"><label for="a-u">Pengguna</label><select id="a-u" name="pengguna" class="select sm" data-autosubmit><option value="">Semua</option>@foreach ($users as $u)<option value="{{ $u->id }}" @selected(($filters['pengguna'] ?? '') == $u->id)>{{ $u->name }} ({{ $u->roleLabel() }})</option>@endforeach</select></div>
            <div class="field"><label for="a-a">Aktivitas</label><select id="a-a" name="aksi" class="select sm" data-autosubmit><option value="">Semua</option>@foreach (\App\Models\AuditLog::ACTIONS as $k => $l)<option value="{{ $k }}" @selected(($filters['aksi'] ?? '') === $k)>{{ $l }}</option>@endforeach</select></div>
            <div class="field"><label for="a-d">Dari</label><input id="a-d" type="date" name="dari" class="input sm" value="{{ $filters['dari'] ?? '' }}"></div>
            <div class="field"><label for="a-s">Sampai</label><input id="a-s" type="date" name="sampai" class="input sm" value="{{ $filters['sampai'] ?? '' }}"></div>
            <div class="field"><label for="a-q">Cari</label><input id="a-q" name="q" class="input sm" value="{{ $filters['q'] ?? '' }}" placeholder="No. bukti / kata"></div>
            <div style="display: flex; gap: 8px"><button class="btn btn-primary btn-sm" type="submit">Terapkan</button>@if (array_filter($filters))<a href="{{ route('audit.index') }}" class="btn btn-ghost btn-sm">Reset</a>@endif</div>
        </form>
    </section>

    <section class="card flush">
        @if ($logs->isEmpty())
            <div class="empty"><div class="ic">@include('partials.icon', ['name' => 'shield', 'size' => 24])</div><strong>Tidak ada catatan</strong></div>
        @else
            <div class="table-wrap">
                <table class="table" style="min-width: 760px">
                    <thead><tr><th scope="col">Waktu</th><th scope="col">Pengguna</th><th scope="col">Aktivitas</th><th scope="col">Rincian</th></tr></thead>
                    <tbody>
                        @foreach ($logs as $log)
                            <tr>
                                <td class="num"><div class="t">{{ Fmt::date($log->created_at, 'j M Y') }}</div><div class="sub">{{ Fmt::date($log->created_at, 'H.i.s') }}</div></td>
                                <td><div class="t">{{ $log->user?->name ?? 'Sistem' }}</div><div class="sub">{{ $log->user?->roleLabel() }}</div></td>
                                <td><span class="chip {{ ['bad' => 'chip-bad', 'good' => 'chip-good', 'muted' => 'chip-muted', 'info' => 'chip-info'][$log->tone()] }}">{{ $log->actionLabel() }}</span></td>
                                <td style="min-width: 300px">
                                    <div>{{ $log->description }}</div>
                                    @if ($log->changes)
                                        <div class="sub" style="margin-top: 4px">
                                            @foreach ($log->changes as $field => $c)
                                                <span style="display: block"><b>{{ $field }}</b>: {{ is_array($c) ? (($c['dari'] ?? '–') ?: '–').' → '.(($c['ke'] ?? '–') ?: '–') : $c }}</span>
                                            @endforeach
                                        </div>
                                    @endif
                                    @if ($log->ip_address)<div class="sub">IP {{ $log->ip_address }}</div>@endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @include('partials.pager', ['paginator' => $logs])
        @endif
    </section>
@endsection
