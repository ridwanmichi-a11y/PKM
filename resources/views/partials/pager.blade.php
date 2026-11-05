@if ($paginator->hasPages())
    <nav class="pager" aria-label="Halaman">
        <span>Menampilkan {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} dari {{ $paginator->total() }}</span>
        <span class="btns">
            <a href="{{ $paginator->previousPageUrl() ?? '#' }}" class="btn btn-sm" @if ($paginator->onFirstPage()) aria-disabled="true" @endif>@include('partials.icon', ['name' => 'chevron-left', 'size' => 16])Sebelumnya</a>
            <a href="{{ $paginator->nextPageUrl() ?? '#' }}" class="btn btn-sm" @if (! $paginator->hasMorePages()) aria-disabled="true" @endif>Berikutnya @include('partials.icon', ['name' => 'chevron-right', 'size' => 16])</a>
        </span>
    </nav>
@endif
