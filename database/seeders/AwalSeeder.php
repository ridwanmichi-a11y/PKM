<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Data awal KOSONG untuk mulai mencoba sendiri:
 * hanya 3 akun login + daftar produk & harga.
 * Tidak ada pelanggan, pesanan, transaksi kas, maupun jejak audit.
 *
 * Jalankan: php artisan migrate:fresh --seed --seeder=AwalSeeder
 *
 * Akun (kata sandi semua: password)
 *   owner    : anggi
 *   admin    : rina
 *   operator : dedi
 */
class AwalSeeder extends Seeder
{
    public function run(): void
    {
        User::create(['name' => 'Anggi', 'username' => 'anggi', 'role' => 'owner', 'email' => 'anggi@sipzstore.local', 'password' => 'password']);
        User::create(['name' => 'Rina', 'username' => 'rina', 'role' => 'admin', 'email' => 'rina@sipzstore.local', 'password' => 'password']);
        User::create(['name' => 'Dedi', 'username' => 'dedi', 'role' => 'operator', 'email' => 'dedi@sipzstore.local', 'password' => 'password']);

        $products = [
            ['ID Card PVC cetak 2 sisi', 'ID Card', 7500, 'Ukuran CR80, laminasi'],
            ['ID Card PVC cetak 1 sisi', 'ID Card', 5000, 'Ukuran CR80'],
            ['Holder ID card', 'Holder & Lanyard', 2000, 'Holder bening / warna'],
            ['Lanyard sablon 2 cm', 'Holder & Lanyard', 8000, 'Sablon logo 1 warna'],
            ['Holder + lanyard sablon', 'Holder & Lanyard', 15000, 'Paket holder dan lanyard'],
            ['Kartu debit instan', 'Kartu Debit Instan', 5000, 'Cetak desain kartu'],
            ['Tumbler custom', 'Tumbler', 25000, 'Tumbler 500 ml, cetak UV'],
            ['Tumbler stainless premium', 'Tumbler', 45000, 'Stainless 750 ml, cetak UV'],
            ['Gantungan kunci akrilik', 'Lainnya', 6000, 'Akrilik 3 mm, cetak UV'],
        ];
        foreach ($products as [$name, $category, $price, $desc]) {
            Product::create(['name' => $name, 'category' => $category, 'price' => $price, 'description' => $desc]);
        }
    }
}
