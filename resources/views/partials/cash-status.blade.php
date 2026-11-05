@php($cls = ['tercatat' => 'chip-good', 'menunggu' => 'chip-warn', 'ditolak' => 'chip-bad', 'batal' => 'chip-muted'][$tx->status] ?? 'chip-muted')
<span class="chip {{ $cls }}">{{ $tx->statusLabel() }}</span>
