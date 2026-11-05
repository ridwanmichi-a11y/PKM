<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::withSum('orderItems as sold', 'qty')->orderBy('category')->orderBy('name')->get();

        return view('products.index', compact('products'));
    }

    public function create()
    {
        return view('products.form', ['product' => new Product(['unit' => 'pcs', 'is_active' => true])]);
    }

    public function store(Request $request)
    {
        $product = Product::create($this->validated($request));
        Audit::log('create', $product, 'Menambah produk '.$product->name.' · Rp '.number_format($product->price, 0, ',', '.'));

        return redirect()->route('products.index')->with('success', 'Produk '.$product->name.' ditambahkan.');
    }

    public function edit(Product $product)
    {
        return view('products.form', compact('product'));
    }

    public function update(Request $request, Product $product)
    {
        $before = $product->only(['name', 'category', 'unit', 'price', 'is_active']);
        $product->update($this->validated($request));
        Audit::log('update', $product, 'Mengubah produk '.$product->name, Audit::diff($before, $product->only(array_keys($before))));

        return redirect()->route('products.index')->with('success', 'Produk '.$product->name.' disimpan.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'category' => ['required', Rule::in(config('sipz.product_categories'))],
            'unit' => ['required', 'string', 'max:20'],
            'price' => ['required'],
            'description' => ['nullable', 'string', 'max:255'],
        ], ['name.required' => 'Nama produk wajib diisi.', 'price.required' => 'Harga wajib diisi.']);

        $data['price'] = (int) preg_replace('/\D/', '', $data['price']);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
