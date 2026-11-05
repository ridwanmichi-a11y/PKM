@extends('layouts.app')
@section('title', $order->code)

@php
    use App\Support\Fmt;
    $me = auth()->user();
    $step = $order->nextStep();
    $flow = ['masuk', 'antre', 'produksi', 'selesai', 'diserahkan'];
    $nowIdx = array_search($order->status, $flow, true);
    $canManage = $me->hasRole('owner', 'admin');
@endphp

@section('content')
    <nav class="small muted" aria-label="Jejak halaman" style="display: flex; flex-wrap: wrap; gap: 8px">
        <a href="{{ route('orders.index') }}" style="color: inherit; text-decoration: none">Pesanan</a><span aria-hidden="true">/</span><strong style="color: var(--ink)">{{ $order->code }}</strong>
    </nav>

    <header class="page-head" style="align-items: flex-start">
        <div style="min-width: 0">
            <div style="display: flex; flex-wrap: wrap; gap: 6px">@include('partials.order-chips', ['order' => $order])
                @if ($order->repeat_of_id)<a href="{{ route('orders.show', $order->repeat_of_id) }}" class="chip chip-info" style="text-decoration: none">Pesan ulang dari {{ $order->repeatOf?->code }}</a>@endif
            </div>
            <h1 class="page-title" style="margin-top: 10px">{{ $order->customer->name }}</h1>
            <p class="page-sub"><span class="num strong" style="color: var(--ink)">{{ $order->code }}</span> · dibuat {{ Fmt::date($order->created_at, 'j M Y, H.i') }} oleh {{ $order->creator?->name ?? '–' }}</p>
        </div>
        <div class="actions" style="width: auto">
            @if ($canManage)
                <a href="{{ route('orders.invoice', $order) }}" class="btn" target="_blank" rel="noopener">@include('partials.icon', ['name' => 'printer', 'size' => 17])Cetak nota</a>
                @if ($order->canBeEdited())
                    <a href="{{ route('orders.edit', $order) }}" class="btn">@include('partials.icon', ['name' => 'edit', 'size' => 17])Ubah</a>
                @endif
            @endif
            @if ($step && $me->hasRole(...$step['roles']))
                <form method="post" action="{{ route('orders.advance', $order) }}">
                    @csrf @method('PATCH')
                    <button type="submit" class="btn {{ $order->status === 'produksi' ? 'btn-success' : 'btn-primary' }}">{{ $step['label'] }}</button>
                </form>
            @endif
        </div>
    </header>

    @if ($order->status === 'batal')
        <div class="note warn"><strong>Pesanan dibatalkan</strong>Alasan: {{ $order->cancel_reason }}</div>
    @else
        <section class="card tight" aria-label="Tahapan pesanan">
            <ol class="steps">
                @foreach ($flow as $i => $s)
                    <li class="{{ $i < $nowIdx ? 'done' : ($i === $nowIdx ? 'now' : '') }}"><i></i><span>{{ \App\Models\Order::STATUSES[$s] }}</span></li>
                @endforeach
            </ol>
        </section>
    @endif

    <section class="kv" aria-label="Informasi pesanan">
        <div><div class="k">Tanggal pesan</div><div class="v">{{ Fmt::date($order->order_date, 'l, j M Y') }}</div></div>
        <div><div class="k">Target selesai</div><div class="v">{{ $order->due_date ? Fmt::date($order->due_date, 'l, j M Y') : '–' }}</div></div>
        <div>
            <div class="k">Jadwal mesin</div>
            <div class="v">
                @if ($order->status === 'produksi') Sedang diproduksi
                @elseif ($order->status === 'antre') {{ Fmt::date($order->scheduled_date, 'D, j M') }}{{ $position ? ' · urutan '.$position : '' }}
                @elseif ($order->finished_at) Selesai {{ Fmt::date($order->finished_at, 'j M, H.i') }}
                @else Belum masuk antrean
                @endif
            </div>
        </div>
        <div><div class="k">Operator</div><div class="v">{{ $order->operator?->name ?? '–' }}</div></div>
    </section>

    <div class="split">
        <div class="col-main">
            <section class="card flush" aria-labelledby="it-h">
                <div class="card-head" style="padding: 12px 12px 0"><h2 id="it-h" class="card-title">Item Pesanan</h2><span class="small muted num">{{ Fmt::num($order->total_qty) }} pcs</span></div>
                <div class="table-wrap">
                    <table class="table" style="min-width: 560px">
                        <thead><tr><th scope="col">Item</th><th scope="col" class="right">Jumlah</th><th scope="col" class="right">Harga</th><th scope="col" class="right">Subtotal</th></tr></thead>
                        <tbody>
                            @foreach ($order->items as $it)
                                <tr>
                                    <td><div class="t">{{ $it->name }}</div><div class="sub">{{ $it->category }}{{ $it->spec ? ' · '.$it->spec : '' }}</div></td>
                                    <td class="right num">{{ Fmt::num($it->qty) }}</td>
                                    <td class="right num">{{ Fmt::rp($it->price) }}</td>
                                    <td class="right num strong">{{ Fmt::rp($it->subtotal) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($order->notes || $order->design_file)
                    <div style="padding: 4px 12px 8px; display: flex; flex-direction: column; gap: 10px">
                        @if ($order->notes)<div class="note"><strong>Catatan untuk operator</strong>{{ $order->notes }}</div>@endif
                        @if ($order->design_file)
                            <a href="{{ route('orders.design', $order) }}" class="btn btn-sm" style="align-self: flex-start">@include('partials.icon', ['name' => 'download', 'size' => 15])File desain: {{ $order->design_file_name }}</a>
                        @endif
                    </div>
                @endif
            </section>

            <section class="card" aria-labelledby="pay-h">
                <div class="card-head"><h2 id="pay-h" class="card-title">Riwayat Pembayaran</h2></div>
                @if ($order->payments->isEmpty())
                    <p class="small muted" style="margin: 0">Belum ada pembayaran.</p>
                @else
                    <div class="table-wrap">
                        <table class="table" style="min-width: 520px">
                            <thead><tr><th scope="col">Tanggal / Bukti</th><th scope="col">Keterangan</th><th scope="col">Status</th><th scope="col" class="right">Jumlah</th></tr></thead>
                            <tbody>
                                @foreach ($order->payments as $p)
                                    <tr class="{{ $p->status === 'batal' ? 'row-void' : '' }}">
                                        <td><div class="t num">{{ Fmt::date($p->date, 'j M Y') }}</div><div class="sub num">{{ $p->code }}</div></td>
                                        <td><div>{{ $p->description }}</div><div class="sub">{{ $p->accountLabel() }} · dicatat {{ $p->creator?->name }}</div></td>
                                        <td>@include('partials.cash-status', ['tx' => $p])</td>
                                        <td class="right num strong text-in amt">{{ Fmt::rp($p->amount) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>

            <section class="card" aria-labelledby="log-h">
                <h2 id="log-h" class="card-title" style="margin-bottom: 14px">Jejak Pesanan</h2>
                <ul class="timeline">
                    @forelse ($history as $log)
                        <li><span class="dot {{ $log->tone() }}"></span><span><strong>{{ $log->user?->name ?? 'Sistem' }}</strong> {{ lcfirst($log->description) }}<br><span class="muted xsmall">{{ Fmt::date($log->created_at, 'j M Y, H.i') }}</span></span></li>
                    @empty
                        <li class="muted">Belum ada catatan.</li>
                    @endforelse
                </ul>
            </section>
        </div>

        <aside class="col-side">
            <section class="card" aria-labelledby="sum-h">
                <h2 id="sum-h" class="card-title sm" style="margin-bottom: 10px">Tagihan</h2>
                <div class="sumrow"><span class="muted">Total</span><span class="num strong">{{ Fmt::rp($order->total) }}</span></div>
                <div class="sumrow"><span class="muted">Sudah dibayar</span><span class="num strong text-in">{{ Fmt::rp($order->paid) }}</span></div>
                <div class="sumrow total"><span>Sisa tagihan</span><span class="num {{ $order->remaining() ? 'text-out' : 'text-in' }}" style="font-size: 18px">{{ $order->remaining() ? Fmt::rp($order->remaining()) : 'Lunas' }}</span></div>

                @if ($canManage && $order->remaining() > 0 && $order->status !== 'batal')
                    <form method="post" action="{{ route('orders.pay', $order) }}" enctype="multipart/form-data" class="form" style="margin-top: 16px; padding-top: 16px; border-top: 1px solid var(--line-soft)">
                        @csrf
                        <div class="field">
                            <label for="amount">Catat pembayaran (Rp)</label>
                            <input id="amount" name="amount" class="input num" inputmode="numeric" value="{{ old('amount', number_format($order->remaining(), 0, ',', '.')) }}" data-money>
                        </div>
                        <div class="row-2" style="grid-template-columns: 1fr 1fr">
                            <div class="field">
                                <label for="account">Metode</label>
                                <select id="account" name="account" class="select">@foreach ($accounts as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select>
                            </div>
                            <div class="field">
                                <span class="label">Bukti</span>
                                <label class="dropzone" for="proof" style="min-height: 46px">@include('partials.icon', ['name' => 'camera', 'size' => 16])<span>Foto</span><input id="proof" name="proof" type="file" accept=".jpg,.jpeg,.png,.pdf" data-file-label="#proof-n"></label>
                            </div>
                        </div>
                        <div id="proof-n" class="file-name"></div>
                        <button type="submit" class="btn btn-primary btn-block">Simpan pembayaran</button>
                    </form>
                @endif

                @if ($order->status === 'selesai' && $order->remaining() > 0)
                    <div class="note warn" style="margin-top: 14px"><strong>Belum bisa diserahkan</strong>Pesanan harus lunas sebelum diserahkan ke pelanggan.</div>
                @endif
            </section>

            <section class="card" aria-labelledby="cust-h">
                <h2 id="cust-h" class="card-title sm" style="margin-bottom: 12px">Pelanggan</h2>
                <div style="display: flex; gap: 12px; align-items: center">
                    <span class="avatar sq">{{ $order->customer->initials() }}</span>
                    <span style="min-width: 0">
                        <span style="display: block; font-weight: 800">{{ $order->customer->name }}</span>
                        <span class="small muted">{{ collect([$order->customer->contact_person, $order->customer->phone])->filter()->implode(' · ') ?: 'Tanpa kontak' }}</span>
                    </span>
                </div>
                <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-top: 14px">
                    @if ($order->customer->whatsappUrl())
                        <a href="{{ $order->customer->whatsappUrl() }}?text={{ rawurlencode('Halo '.$order->customer->name.', pesanan '.$order->code.' di SipzStore '.($order->status === 'selesai' ? 'sudah selesai dan siap diambil.' : 'sedang kami proses.')) }}" class="btn btn-sm" target="_blank" rel="noopener">@include('partials.icon', ['name' => 'whatsapp', 'size' => 15])Kirim WhatsApp</a>
                    @endif
                    @if ($canManage)
                        <a href="{{ route('customers.show', $order->customer) }}" class="btn btn-sm">Riwayat</a>
                        <a href="{{ route('orders.create', ['ulang' => $order->id]) }}" class="btn btn-sm btn-outline">@include('partials.icon', ['name' => 'repeat', 'size' => 15])Pesan ulang</a>
                    @endif
                </div>
            </section>

            @if ($canManage && ! in_array($order->status, ['diserahkan', 'batal'], true))
                <form method="post" action="{{ route('orders.cancel', $order) }}" data-prompt="Alasan membatalkan {{ $order->code }}:" data-prompt-field="reason">
                    @csrf @method('PATCH')
                    <input type="hidden" name="reason">
                    <button type="submit" class="btn btn-ghost btn-danger btn-sm btn-block">@include('partials.icon', ['name' => 'ban', 'size' => 16])Batalkan pesanan</button>
                </form>
            @endif
        </aside>
    </div>
@endsection
