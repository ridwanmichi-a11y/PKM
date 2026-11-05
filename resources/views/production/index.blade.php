@extends('layouts.app')
@section('title', 'Antrean Mesin')

@php
    use App\Support\Fmt;
    $me = auth()->user();
    $canRun = $me->hasRole('owner', 'operator');
    $doneCount = $doneToday->count();
    $pct = $todayTotal ? round($doneCount / $todayTotal * 100) : 0;
@endphp

@section('content')
    <div class="prod-wrap">
        <header>
            <div class="eyebrow">{{ Fmt::date(Fmt::today(), 'l, j F Y') }}</div>
            <h1 class="page-title">Antrean Mesin</h1>
            <p class="page-sub">Kerjakan berurutan dari atas. Pesanan berikutnya bisa dimulai setelah yang sedang dicetak selesai.</p>
        </header>

        <section class="card tight" aria-label="Progres hari ini">
            <div style="display: flex; justify-content: space-between; font-size: 13px">
                <span class="strong">Selesai hari ini</span><span class="num muted">{{ $doneCount }} dari {{ $todayTotal }} pesanan</span>
            </div>
            <div class="bar good" style="margin-top: 8px"><div style="width: {{ $pct }}%"></div></div>
            <div class="xsmall muted" style="margin-top: 6px">Terjadwal hari ini {{ Fmt::num($outlook['load']) }} / {{ Fmt::num($outlook['capacity']) }} pcs</div>
        </section>

        @forelse ($jobs as $i => $o)
            @php
                $isRun = $o->status === 'produksi';
                $isNext = ! $running && $next && $next->id === $o->id;
            @endphp
            <article class="job {{ $isRun ? 'run' : '' }}">
                <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px">
                    <span style="display: flex; align-items: center; gap: 8px">
                        <span class="qno num" style="{{ $isRun ? 'background: var(--navy); color: #fff' : '' }}">{{ $i + 1 }}</span>
                        <a href="{{ route('orders.show', $o) }}" class="num strong small" style="color: var(--ink-3); text-decoration: none">{{ $o->code }}</a>
                    </span>
                    @if ($isRun)
                        <span class="chip chip-dark">Sedang dicetak</span>
                    @elseif ($isNext)
                        <span class="chip chip-warn">Berikutnya</span>
                    @else
                        <span class="chip chip-muted">{{ Fmt::date($o->scheduled_date, 'D j M') }}</span>
                    @endif
                </div>
                <h2 style="margin: 10px 0 2px; font-size: 17px; font-weight: 800">{{ $o->itemSummary() }}</h2>
                <div class="small" style="color: var(--ink-3)">{{ $o->customer->name }} · <span class="num">{{ Fmt::num($o->total_qty) }} pcs</span></div>
                <div class="spec">
                    @foreach ($o->items as $it)
                        <div><b>{{ Fmt::num($it->qty) }}×</b> {{ $it->name }}{{ $it->spec ? ' — '.$it->spec : '' }}</div>
                    @endforeach
                    @if ($o->notes)<div style="margin-top: 6px"><b>Catatan:</b> {{ $o->notes }}</div>@endif
                </div>
                @if ($o->design_file)
                    <a href="{{ route('orders.design', $o) }}" class="btn btn-sm" style="margin-top: 10px">@include('partials.icon', ['name' => 'download', 'size' => 15])Unduh desain</a>
                @endif
                @if ($canRun && ($isRun || $isNext))
                    <form method="post" action="{{ route('orders.advance', $o) }}">
                        @csrf @method('PATCH')
                        <button type="submit" class="btn btn-lg btn-block {{ $isRun ? 'btn-success' : 'btn-outline' }}">{{ $isRun ? 'Tandai selesai' : 'Mulai produksi' }}</button>
                    </form>
                @endif
            </article>
        @empty
            <div class="card empty"><div class="ic">@include('partials.icon', ['name' => 'printer', 'size' => 24])</div><strong>Antrean kosong</strong>Belum ada pesanan yang perlu dicetak.</div>
        @endforelse

        @if ($doneToday->count())
            <h2 class="card-title sm" style="margin-top: 10px">Selesai hari ini</h2>
            @foreach ($doneToday as $o)
                <article class="job done">
                    <div style="display: flex; justify-content: space-between; gap: 8px; align-items: center">
                        <span class="num strong small">{{ $o->code }}</span>
                        <span class="chip chip-good">Selesai {{ Fmt::date($o->finished_at, 'H.i') }}</span>
                    </div>
                    <div class="strong" style="margin-top: 6px">{{ $o->itemSummary() }}</div>
                    <div class="small muted">{{ $o->customer->name }} · {{ Fmt::num($o->total_qty) }} pcs</div>
                </article>
            @endforeach
        @endif
    </div>
@endsection
