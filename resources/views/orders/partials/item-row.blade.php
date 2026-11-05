<tr data-item>
    <td style="min-width: 190px">
        <label class="sr-only" for="p-{{ $i }}">Produk</label>
        <select id="p-{{ $i }}" name="items[{{ $i }}][product_id]" class="select" data-product>
            <option value="">Lainnya (ketik manual)</option>
            @foreach ($products->groupBy('category') as $cat => $list)
                <optgroup label="{{ $cat }}">
                    @foreach ($list as $p)
                        <option value="{{ $p->id }}" data-unit-price="{{ $p->price }}" data-product-name="{{ $p->name }}" @selected((string) ($it['product_id'] ?? '') === (string) $p->id)>{{ $p->name }} · {{ \App\Support\Fmt::rp($p->price) }}</option>
                    @endforeach
                </optgroup>
            @endforeach
        </select>
    </td>
    <td style="min-width: 220px">
        <label class="sr-only" for="n-{{ $i }}">Nama item</label>
        <input id="n-{{ $i }}" name="items[{{ $i }}][name]" class="input" value="{{ $it['name'] ?? '' }}" placeholder="Nama item" data-name>
        <label class="sr-only" for="s-{{ $i }}">Spesifikasi</label>
        <input id="s-{{ $i }}" name="items[{{ $i }}][spec]" class="input" value="{{ $it['spec'] ?? '' }}" placeholder="Spesifikasi: ukuran, warna, sisi cetak" style="margin-top: 6px; height: 38px; font-size: 13px">
    </td>
    <td class="qty">
        <label class="sr-only" for="q-{{ $i }}">Jumlah</label>
        <input id="q-{{ $i }}" name="items[{{ $i }}][qty]" class="input num" type="number" min="1" value="{{ $it['qty'] ?? 1 }}" data-qty>
    </td>
    <td class="price">
        <label class="sr-only" for="h-{{ $i }}">Harga satuan</label>
        <input id="h-{{ $i }}" name="items[{{ $i }}][price]" class="input num" inputmode="numeric" value="{{ number_format((int) preg_replace('/\D/', '', (string) ($it['price'] ?? 0)), 0, ',', '.') }}" data-price>
    </td>
    <td class="subtotal num" data-subtotal>Rp 0</td>
    <td style="width: 44px; padding-top: 10px">
        <button type="button" class="icon-btn" data-remove aria-label="Hapus item">@include('partials.icon', ['name' => 'x', 'size' => 16])</button>
    </td>
</tr>
