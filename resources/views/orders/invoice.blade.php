<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nota {{ $order->code }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/sipz.css') }}?v=1">
</head>
<body class="print-page">
@php
    use App\Support\Fmt;
    use App\Models\Setting;
@endphp
<div class="print-bar">
    <button type="button" class="btn btn-primary" onclick="window.print()">Cetak / Simpan PDF</button>
    <a href="{{ route('orders.show', $order) }}" class="btn">Kembali</a>
</div>

<div class="paper narrow">
    <div class="kop" style="border-bottom-width: 2px">
        <span class="brand-mark" style="width: 44px; height: 44px; font-size: 22px">{{ mb_substr(Setting::get('store_name'), 0, 1) }}</span>
        <div>
            <div class="n">{{ Setting::get('store_name') }}</div>
            <div class="a">{{ Setting::get('store_address') }}<br>{{ Setting::get('store_phone') }}</div>
        </div>
    </div>

    <div style="display: flex; justify-content: space-between; gap: 12px; font-size: 13px">
        <div><div class="muted xsmall">Nota pesanan</div><div class="strong num">{{ $order->code }}</div></div>
        <div style="text-align: right"><div class="muted xsmall">Tanggal</div><div class="strong">{{ Fmt::date($order->order_date, 'j F Y') }}</div></div>
    </div>
    <div style="margin-top: 10px; font-size: 13px"><span class="muted">Pelanggan:</span> <strong>{{ $order->customer->name }}</strong>{{ $order->customer->phone ? ' · '.$order->customer->phone : '' }}</div>

    <table class="table" style="margin-top: 14px; font-size: 13px">
        <thead><tr><th scope="col">Item</th><th scope="col" class="right">Qty</th><th scope="col" class="right">Subtotal</th></tr></thead>
        <tbody>
            @foreach ($order->items as $it)
                <tr><td style="padding: 8px 4px">{{ $it->name }}<div class="sub">@ {{ Fmt::rp($it->price) }}{{ $it->spec ? ' · '.$it->spec : '' }}</div></td><td class="right num" style="padding: 8px 4px">{{ Fmt::num($it->qty) }}</td><td class="right num" style="padding: 8px 4px">{{ Fmt::rp($it->subtotal) }}</td></tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-top: 8px; font-size: 13px">
        <div class="sumrow"><span>Total</span><span class="num strong">{{ Fmt::rp($order->total) }}</span></div>
        @foreach ($order->payments as $p)
            <div class="sumrow" style="padding: 3px 0"><span class="muted">{{ Fmt::date($p->date, 'j M') }} · {{ $p->accountLabel() }} · {{ $p->code }}</span><span class="num">− {{ Fmt::rp($p->amount) }}</span></div>
        @endforeach
        <div class="sumrow total"><span>{{ $order->remaining() ? 'Sisa tagihan' : 'Status' }}</span><span class="num">{{ $order->remaining() ? Fmt::rp($order->remaining()) : 'LUNAS' }}</span></div>
    </div>

    @if ($order->due_date && ! in_array($order->status, ['diserahkan', 'batal'], true))
        <div class="note" style="margin-top: 12px; font-size: 12px">Perkiraan selesai: <b>{{ Fmt::date($order->due_date, 'l, j F Y') }}</b></div>
    @endif

    <div class="xsmall muted" style="margin-top: 16px; text-align: center; line-height: 1.6">
        Dicatat oleh {{ $order->creator?->signature() ?? '–' }} · dicetak {{ Fmt::date(Fmt::now(), 'j M Y, H.i') }}<br>
        Terima kasih telah memesan di {{ Setting::get('store_name') }}.
    </div>
</div>
</body>
</html>
