{{-- Label status & pembayaran pesanan. Butuh: $order --}}
@php
    $statusClass = ['masuk' => 'chip-muted', 'antre' => 'chip-warn', 'produksi' => 'chip-dark', 'selesai' => 'chip-good', 'diserahkan' => 'chip-info', 'batal' => 'chip-bad'][$order->status] ?? 'chip-muted';
    $payClass = ['lunas' => 'chip-good', 'dp' => 'chip-warn', 'belum' => 'chip-bad'][$order->paymentState()];
@endphp
@if (empty($onlyPay))<span class="chip {{ $statusClass }}">{{ $order->statusLabel() }}</span>@endif
@if ($order->status !== 'batal' && empty($onlyStatus))<span class="chip {{ $payClass }}">{{ $order->paymentLabel() }}</span>@endif
