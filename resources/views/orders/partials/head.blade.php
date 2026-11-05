@php $canCreate = auth()->user()->hasRole('owner', 'admin'); @endphp
<header class="page-head">
    <div>
        <div class="eyebrow">Pesanan &amp; Antrean Produksi</div>
        <h1 class="page-title">{{ $view === 'tabel' ? 'Riwayat Pesanan' : 'Alur Pesanan' }}</h1>
        <p class="page-sub">
            {{ $view === 'tabel'
                ? 'Semua pesanan, termasuk yang sudah diserahkan dan dibatalkan.'
                : 'Satu mesin, urutan pertama masuk pertama dikerjakan. Majukan pesanan dengan tombol di setiap kartu.' }}
        </p>
    </div>
    <div class="actions">
        <nav class="seg" aria-label="Tampilan">
            <a href="{{ route('orders.index', array_filter(['q' => $q])) }}" class="{{ $view === 'papan' ? 'on' : '' }}">Papan</a>
            <a href="{{ route('orders.index', array_filter(['tampilan' => 'tabel', 'q' => $q])) }}" class="{{ $view === 'tabel' ? 'on' : '' }}">Tabel</a>
        </nav>
        <form method="get" action="{{ route('orders.index') }}" role="search" style="display: contents">
            @if ($view === 'tabel')<input type="hidden" name="tampilan" value="tabel">@endif
            <label class="search">
                @include('partials.icon', ['name' => 'search'])
                <span class="sr-only">Cari pesanan</span>
                <input type="search" name="q" value="{{ $q }}" placeholder="No. pesanan atau pelanggan">
            </label>
        </form>
        @if ($canCreate)
            <a href="{{ route('orders.create') }}" class="btn btn-primary hide-m">@include('partials.icon', ['name' => 'plus', 'stroke' => 2.2])Pesanan Baru</a>
        @endif
    </div>
</header>
