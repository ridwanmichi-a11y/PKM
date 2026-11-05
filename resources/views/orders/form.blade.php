@extends('layouts.app')

@php
    use App\Support\Fmt;
    $editing = $order->exists;
    $oldItems = old('items', $items);
    $selCustomer = old('customer_id', $customer?->id);
@endphp

@section('title', $editing ? 'Ubah '.$order->code : 'Pesanan Baru')

@section('content')
    <header>
        <a href="{{ $editing ? route('orders.show', $order) : route('orders.index') }}" class="back">@include('partials.icon', ['name' => 'chevron-left', 'size' => 16, 'stroke' => 2]){{ $editing ? 'Kembali ke detail pesanan' : 'Kembali ke daftar pesanan' }}</a>
        <h1 class="page-title" style="margin-top: 8px">{{ $editing ? 'Ubah Pesanan '.$order->code : 'Pesanan Baru' }}</h1>
        <p class="page-sub">{{ $editing ? 'Pesanan masih bisa diubah selama belum masuk produksi. Setiap perubahan tercatat di jejak audit.' : 'Nomor pesanan dibuat otomatis saat disimpan.' }}</p>
    </header>

    <form method="post" action="{{ $editing ? route('orders.update', $order) : route('orders.store') }}" enctype="multipart/form-data"
          id="order-form" class="split" novalidate data-mode="{{ $editing ? 'edit' : 'create' }}" data-create-url="{{ route('orders.create') }}">
        @csrf
        @if ($editing) @method('PUT') @endif
        @if ($repeat)<input type="hidden" name="repeat_of_id" value="{{ $repeat->id }}">@endif

        <div class="col-main">
            {{-- 1. Pelanggan --}}
            <section class="card" aria-labelledby="c-h">
                <h2 id="c-h" class="card-title sm" style="margin-bottom: 14px">1. Pelanggan</h2>
                <div class="field">
                    <label for="customer_id">Pilih pelanggan</label>
                    <select id="customer_id" name="customer_id" class="select @error('customer_id') is-invalid @enderror">
                        <option value="">+ Pelanggan baru</option>
                        @foreach ($customers as $c)
                            <option value="{{ $c->id }}" @selected((string) $selCustomer === (string) $c->id)>{{ $c->name }}{{ $c->phone ? ' · '.$c->phone : '' }}</option>
                        @endforeach
                    </select>
                </div>

                <div id="new-customer" class="row-3" style="margin-top: 14px" @if ($selCustomer) hidden @endif>
                    <div class="field">
                        <label for="new_customer_name">Nama pelanggan baru</label>
                        <input id="new_customer_name" name="new_customer_name" class="input @error('new_customer_name') is-invalid @enderror" value="{{ old('new_customer_name') }}" placeholder="Nama orang / instansi">
                    </div>
                    <div class="field">
                        <label for="new_customer_phone">No. WhatsApp</label>
                        <input id="new_customer_phone" name="new_customer_phone" class="input" value="{{ old('new_customer_phone') }}" placeholder="08xx-xxxx-xxxx" inputmode="tel">
                    </div>
                    <div class="field">
                        <label for="new_customer_type">Jenis</label>
                        <select id="new_customer_type" name="new_customer_type" class="select">
                            @foreach (\App\Models\Customer::TYPES as $k => $l)<option value="{{ $k }}" @selected(old('new_customer_type') === $k)>{{ $l }}</option>@endforeach
                        </select>
                    </div>
                </div>

                @if ($customer && ! $editing)
                    <div class="cust-card">
                        <span class="avatar sq">{{ $customer->initials() }}</span>
                        <span style="flex: 1; min-width: 180px">
                            <span style="display: block; font-size: 15px; font-weight: 800">{{ $customer->name }}</span>
                            <span class="small muted">{{ collect([$customer->contact_person, $customer->phone])->filter()->implode(' · ') }} · {{ $customer->orders()->where('status', '!=', 'batal')->count() }} pesanan</span>
                        </span>
                        <a href="{{ route('customers.show', $customer) }}" class="btn btn-sm">Profil</a>
                    </div>

                    @if ($history->count())
                        <h3 style="margin: 18px 0 10px; font-size: 14px; font-weight: 800">Riwayat pesanan · klik "Pesan ulang" untuk menyalin item &amp; desain</h3>
                        @foreach ($history as $h)
                            <div class="history-item">
                                <span style="flex: 1; min-width: 200px">
                                    <span style="display: block; font-size: 14px; font-weight: 700">{{ $h->itemSummary() }}</span>
                                    <span class="xsmall muted">{{ $h->code }} · {{ Fmt::date($h->order_date, 'j M Y') }} · {{ Fmt::num($h->total_qty) }} pcs</span>
                                </span>
                                <span class="num strong">{{ Fmt::rp($h->total) }}</span>
                                @if ($repeat && $repeat->id === $h->id)
                                    <span class="chip chip-warn">Sedang disalin</span>
                                @else
                                    <a href="{{ route('orders.create', ['ulang' => $h->id]) }}" class="btn btn-sm btn-outline">@include('partials.icon', ['name' => 'repeat', 'size' => 15])Pesan ulang</a>
                                @endif
                            </div>
                        @endforeach
                    @endif
                @endif
            </section>

            {{-- 2. Item --}}
            <section class="card" aria-labelledby="i-h">
                <div class="card-head">
                    <h2 id="i-h" class="card-title sm">2. Item Pesanan</h2>
                    @if ($repeat)<span class="chip chip-warn">Disalin dari {{ $repeat->code }}</span>@endif
                </div>
                <div class="table-wrap">
                    <table class="table items-table" style="min-width: 760px">
                        <thead><tr><th scope="col">Produk</th><th scope="col">Nama di nota &amp; spesifikasi</th><th scope="col">Jumlah</th><th scope="col">Harga satuan</th><th scope="col" class="right">Subtotal</th><th scope="col"><span class="sr-only">Hapus</span></th></tr></thead>
                        <tbody id="items-body">
                            @foreach ($oldItems as $i => $it)
                                @include('orders.partials.item-row', ['i' => $i, 'it' => $it])
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <template id="item-template">@include('orders.partials.item-row', ['i' => 999, 'it' => ['product_id' => null, 'name' => '', 'spec' => '', 'qty' => 1, 'price' => 0]])</template>
                <button type="button" class="btn btn-sm" data-add-item style="margin-top: 10px; border-style: dashed">@include('partials.icon', ['name' => 'plus', 'size' => 15])Tambah item</button>

                <div class="row-2" style="margin-top: 18px">
                    <div class="field">
                        <label for="due_date">Target selesai</label>
                        <input id="due_date" name="due_date" type="date" class="input" value="{{ old('due_date', optional($order->due_date)->toDateString()) }}">
                    </div>
                    <div class="field">
                        <span class="label">File desain</span>
                        <label class="dropzone" for="design">
                            @include('partials.icon', ['name' => 'upload', 'size' => 18])
                            <span>{{ $order->design_file_name || $repeat?->design_file_name ? 'Ganti file desain' : 'Pilih file desain' }}</span>
                            <input id="design" name="design" type="file" data-file-label="#design-name" accept=".pdf,.jpg,.jpeg,.png,.zip,.rar,.psd,.ai,.cdr">
                        </label>
                        <div id="design-name" class="file-name">
                            @if ($order->design_file_name) Tersimpan: {{ $order->design_file_name }}
                            @elseif ($repeat?->design_file_name) Memakai desain lama: {{ $repeat->design_file_name }}
                            @endif
                        </div>
                    </div>
                </div>
                <div class="field" style="margin-top: 14px">
                    <label for="notes">Catatan untuk operator</label>
                    <textarea id="notes" name="notes" rows="3" class="textarea" placeholder="Contoh: lanyard warna hijau, logo di tengah, laminasi doff">{{ old('notes', $order->notes ?? $repeat?->notes) }}</textarea>
                </div>
            </section>

            {{-- 3. Pembayaran awal --}}
            @unless ($editing)
                <section class="card" aria-labelledby="p-h">
                    <h2 id="p-h" class="card-title sm" style="margin-bottom: 14px">3. Pembayaran Awal</h2>
                    <fieldset>
                        <legend class="label">Uang muka</legend>
                        <div class="radio-pills">
                            <label><input type="radio" name="pay_mode" value="none" @checked(old('pay_mode', 'dp') === 'none')><span>Belum bayar</span></label>
                            <label><input type="radio" name="pay_mode" value="dp" @checked(old('pay_mode', 'dp') === 'dp')><span>DP 50%</span></label>
                            <label><input type="radio" name="pay_mode" value="full" @checked(old('pay_mode') === 'full')><span>Lunas</span></label>
                        </div>
                    </fieldset>
                    <div id="pay-extra" class="row-3" style="margin-top: 16px" @if (old('pay_mode', 'dp') === 'none') hidden @endif>
                        <div class="field">
                            <label for="pay_amount">Jumlah dibayar (Rp)</label>
                            <input id="pay_amount" name="pay_amount" class="input num" inputmode="numeric" value="{{ old('pay_amount') }}" data-money>
                        </div>
                        <div class="field">
                            <label for="pay_account">Metode</label>
                            <select id="pay_account" name="pay_account" class="select">
                                @foreach ($accounts as $k => $l)<option value="{{ $k }}" @selected(old('pay_account', 'bank') === $k)>{{ $l }}</option>@endforeach
                            </select>
                        </div>
                        <div class="field">
                            <span class="label">Bukti bayar</span>
                            <label class="dropzone" for="pay_proof" style="min-height: 46px">@include('partials.icon', ['name' => 'camera', 'size' => 16])<span>Foto / PDF</span>
                                <input id="pay_proof" name="pay_proof" type="file" accept=".jpg,.jpeg,.png,.pdf" data-file-label="#proof-name">
                            </label>
                            <div id="proof-name" class="file-name"></div>
                        </div>
                    </div>
                </section>
            @endunless
        </div>

        <aside class="col-side">
            <section class="card" aria-labelledby="s-h" style="position: sticky; top: 20px">
                <h2 id="s-h" class="card-title sm" style="margin-bottom: 12px">Ringkasan</h2>
                <div class="sumrow"><span class="muted">Jumlah barang</span><span class="num strong" data-total-qty>0 pcs</span></div>
                <div class="sumrow"><span class="muted">Total pesanan</span><span class="num strong" data-total>Rp 0</span></div>
                @if ($editing)
                    <div class="sumrow"><span class="muted">Sudah dibayar</span><span class="num strong text-in">{{ Fmt::rp($order->paid) }}</span></div>
                @else
                    <div class="sumrow total"><span>Sisa tagihan</span><span class="num text-out" style="font-size: 18px" data-rest>Rp 0</span></div>
                    <label class="check" style="margin-top: 14px"><input type="checkbox" name="enqueue" value="1" @checked(old('enqueue', '1'))>Langsung masukkan ke antrean mesin</label>
                    <div class="note" style="margin-top: 10px">
                        <strong>Penjadwalan otomatis</strong>
                        Kapasitas mesin {{ Fmt::num($capacity) }} pcs/hari. Pesanan dijadwalkan ke hari kerja terdekat yang masih muat.
                    </div>
                @endif
                <button type="submit" class="btn btn-primary btn-lg btn-block" style="margin-top: 16px">{{ $editing ? 'Simpan Perubahan' : 'Simpan Pesanan' }}</button>
                <p class="xsmall muted" style="margin: 10px 0 0; line-height: 1.5">
                    {{ $editing ? 'Total baru tidak boleh lebih kecil dari yang sudah dibayar.' : 'Pembayaran awal langsung tercatat sebagai kas masuk dengan nomor bukti otomatis dan nama pencatat.' }}
                </p>
            </section>
        </aside>
    </form>
@endsection
