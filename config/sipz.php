<?php

return [

    // Zona waktu toko.
    'timezone' => env('SIPZ_TIMEZONE', 'Asia/Jakarta'),

    // Nilai bawaan pengaturan. Owner bisa mengubahnya dari menu Pengaturan.
    'defaults' => [
        'store_name' => 'SipzStore',
        'store_tagline' => 'Spesialis Percetakan ID Card & UV Printing',
        'store_address' => 'Jl. Green Kemang Residence Blok L5, Pabuaran, Kemang, Kab. Bogor 16310',
        'store_phone' => '0857-xxxx-xxxx',
        'daily_capacity' => 500,      // kapasitas mesin per hari (pcs)
        'approval_limit' => 500000,   // pengeluaran di atas nilai ini wajib disetujui Owner
        'closed_on_sunday' => 1,      // 1 = Minggu libur, antrean tidak dijadwalkan di hari Minggu
    ],

    // Kategori kas.
    'cash_in_categories' => [
        'Pembayaran pesanan',
        'Pendapatan lain-lain',
        'Setoran modal pemilik',
    ],

    'cash_out_categories' => [
        'Bahan baku',
        'Perawatan mesin',
        'Operasional (listrik, internet, sewa)',
        'Gaji & upah',
        'Transportasi & pengiriman',
        'Pemasaran',
        'Lain-lain',
        'Penarikan pemilik (prive)',
    ],

    // Kategori yang TIDAK dihitung sebagai pendapatan/beban di laporan laba rugi
    // (transaksi pemilik, bukan hasil usaha).
    'non_operating' => [
        'Setoran modal pemilik',
        'Penarikan pemilik (prive)',
    ],

    // Kategori pengeluaran yang dihitung sebagai Harga Pokok Produksi.
    'cogs_categories' => ['Bahan baku'],

    'product_categories' => ['ID Card', 'Holder & Lanyard', 'Kartu Debit Instan', 'Tumbler', 'Lainnya'],

    'accounts' => [
        'tunai' => 'Tunai',
        'bank' => 'Bank',
        'qris' => 'QRIS',
    ],
];
