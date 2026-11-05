<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\CashTransaction;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\CodeGenerator;
use App\Services\QueueService;
use App\Support\Fmt;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * Data contoh untuk demo: 3 akun, produk, pelanggan, dan transaksi
 * 6 bulan terakhir (tanggal menyesuaikan hari ini).
 *
 * Akun demo (kata sandi semua: password)
 *   owner    : anggi
 *   admin    : rina
 *   operator : dedi
 */
class DatabaseSeeder extends Seeder
{
    private array $users = [];

    public function run(): void
    {
        mt_srand((int) env('SEED_RANDOM', 77));
        $today = Fmt::today();

        // Gambar nota contoh untuk transaksi demo.
        Storage::disk('local')->put('nota-kas/contoh-nota.svg', '<svg xmlns="http://www.w3.org/2000/svg" width="360" height="480" viewBox="0 0 360 480">'
            .'<rect width="360" height="480" fill="#fffdf6"/><rect x="20" y="20" width="320" height="440" fill="none" stroke="#c9ced8" stroke-dasharray="6 4"/>'
            .'<text x="180" y="70" font-family="sans-serif" font-size="22" font-weight="700" text-anchor="middle" fill="#1b2a5e">NOTA CONTOH</text>'
            .'<text x="180" y="100" font-family="sans-serif" font-size="13" text-anchor="middle" fill="#5a6275">Data demo SipzStore</text>'
            .'<g font-family="monospace" font-size="13" fill="#2a3247"><text x="40" y="160">Barang ............ 1</text><text x="40" y="190">Harga ......... Rp xxx</text><text x="40" y="220">Total ......... Rp xxx</text></g>'
            .'<text x="180" y="420" font-family="sans-serif" font-size="12" text-anchor="middle" fill="#8a93a6">Ganti dengan foto nota asli saat dipakai</text></svg>');

        $this->users = [
            'owner' => User::create(['name' => 'Anggi', 'username' => 'anggi', 'role' => 'owner', 'email' => 'anggi@sipzstore.local', 'password' => 'password']),
            'admin' => User::create(['name' => 'Rina', 'username' => 'rina', 'role' => 'admin', 'email' => 'rina@sipzstore.local', 'password' => 'password']),
            'operator' => User::create(['name' => 'Dedi', 'username' => 'dedi', 'role' => 'operator', 'email' => 'dedi@sipzstore.local', 'password' => 'password']),
        ];

        $products = collect([
            ['ID Card PVC cetak 2 sisi', 'ID Card', 7500, 'Ukuran CR80, laminasi'],
            ['ID Card PVC cetak 1 sisi', 'ID Card', 5000, 'Ukuran CR80'],
            ['Holder ID card', 'Holder & Lanyard', 2000, 'Holder bening / warna'],
            ['Lanyard sablon 2 cm', 'Holder & Lanyard', 8000, 'Sablon logo 1 warna'],
            ['Holder + lanyard sablon', 'Holder & Lanyard', 15000, 'Paket holder dan lanyard'],
            ['Kartu debit instan', 'Kartu Debit Instan', 5000, 'Cetak desain kartu'],
            ['Tumbler custom', 'Tumbler', 25000, 'Tumbler 500 ml, cetak UV'],
            ['Tumbler stainless premium', 'Tumbler', 45000, 'Stainless 750 ml, cetak UV'],
            ['Gantungan kunci akrilik', 'Lainnya', 6000, 'Akrilik 3 mm, cetak UV'],
        ])->map(fn ($p) => Product::create(['name' => $p[0], 'category' => $p[1], 'price' => $p[2], 'description' => $p[3]]));

        $customers = collect([
            ['PT Sinar Kemang', 'instansi', 'Ibu Wulan (HRD)', '0812-1100-2301'],
            ['SMAN 1 Kemang', 'instansi', 'Pak Joko (TU)', '0812-1100-2210'],
            ['Klinik Pratama Sehat', 'instansi', 'dr. Maya', '0813-2200-1144'],
            ['Gym Fit Kemang', 'instansi', 'Andre', '0857-3300-7781'],
            ['Karang Taruna RW 05', 'instansi', 'Fajar', '0821-4400-1290'],
            ['CV Maju Bersama', 'instansi', 'Pak Herman', '0812-5500-6612'],
            ['Komunitas Gowes Kemang', 'instansi', 'Bang Ucok', '0838-6600-3321'],
            ['SDIT Al Hikmah', 'instansi', 'Ibu Rahma', '0812-7700-4410'],
            ['Bu Sari', 'perorangan', null, '0857-8800-1122'],
            ['Pak Hendra', 'perorangan', null, '0813-9900-5543'],
            ['Mbak Dinda', 'perorangan', null, '0812-1212-8890'],
            ['Koperasi Karyawan Bogor', 'instansi', 'Pak Rudi', '0811-2323-4545'],
        ])->map(fn ($c) => Customer::create(['name' => $c[0], 'type' => $c[1], 'contact_person' => $c[2], 'phone' => $c[3], 'address' => 'Kemang, Kab. Bogor']));

        // Setoran modal awal.
        $start = $today->copy()->startOfMonth()->subMonths(5);
        $this->cash('in', $start, 'Setoran modal pemilik', 'Modal awal operasional (rekening)', 3000000, 'bank', 'owner');
        $this->cash('in', $start, 'Setoran modal pemilik', 'Modal awal kas tunai', 2000000, 'tunai', 'owner');

        // Pesanan historis (sudah diserahkan).
        $day = $start->copy();
        $cutoff = $today->copy()->subDays(2);
        while ($day->lte($cutoff)) {
            if (! $day->isSunday()) {
                // Usaha bertumbuh: peluang pesanan naik tiap bulan.
                $chance = 32 + 5 * $start->diffInMonths($day->copy()->startOfMonth());
                $n = mt_rand(1, 100) <= $chance ? (mt_rand(1, 100) <= 20 ? 2 : 1) : 0;
                for ($i = 0; $i < $n; $i++) {
                    $this->historicOrder($day->copy(), $customers, $products, $today);
                }
            }
            $this->expensesFor($day->copy());
            $day->addDay();
        }

        // Pesanan yang sedang berjalan hari ini (sesuai alur antrean).
        $this->pipeline($today, $customers, $products);

        // Contoh transaksi untuk akuntabilitas: pending & dibatalkan.
        $this->accountabilityExamples($today);
    }

    private function historicOrder(Carbon $date, $customers, $products, Carbon $today): void
    {
        $customer = $customers->random();
        $items = $this->randomItems($products, $customer->type === 'instansi');
        $total = array_sum(array_column($items, 'subtotal'));
        $qty = array_sum(array_column($items, 'qty'));

        $cancel = mt_rand(1, 100) <= 3;
        $done = $date->copy()->addDays(mt_rand(1, 3));
        if ($done->gt($today)) {
            $done = $today->copy();
        }

        $order = Order::create([
            'code' => CodeGenerator::order($date),
            'customer_id' => $customer->id,
            'status' => $cancel ? 'batal' : 'diserahkan',
            'order_date' => $date->toDateString(),
            'due_date' => $done->toDateString(),
            'scheduled_date' => $cancel ? null : $done->copy()->subDay()->max($date)->toDateString(),
            'queued_at' => $date->copy()->setTime(9, mt_rand(0, 59)),
            'total' => $total,
            'total_qty' => $qty,
            'created_by' => $this->users['admin']->id,
            'operator_id' => $cancel ? null : $this->users['operator']->id,
            'started_at' => $cancel ? null : $done->copy()->subDay()->max($date)->setTime(10, 0),
            'finished_at' => $cancel ? null : $done->copy()->setTime(14, 0),
            'handed_over_at' => $cancel ? null : $done->copy()->setTime(16, 30),
            'cancel_reason' => $cancel ? 'Pelanggan membatalkan sebelum produksi' : null,
            'created_at' => $date->copy()->setTime(9, 0),
            'updated_at' => $done->copy()->setTime(16, 30),
        ]);
        $order->items()->createMany($items);

        if ($cancel) {
            return;
        }
        $account = ['tunai', 'bank', 'bank', 'qris'][mt_rand(0, 3)];
        if (mt_rand(1, 100) <= 65) {
            $dp = (int) (round($total * 0.5 / 1000) * 1000);
            $this->cash('in', $date, 'Pembayaran pesanan', 'DP '.$order->code.' · '.$customer->name, $dp, $account, 'admin', $order->id);
            $this->cash('in', $done, 'Pembayaran pesanan', 'Pelunasan '.$order->code.' · '.$customer->name, $total - $dp, $account, 'admin', $order->id);
        } else {
            $this->cash('in', $date, 'Pembayaran pesanan', 'Pembayaran lunas '.$order->code.' · '.$customer->name, $total, $account, 'admin', $order->id);
        }
        $order->refreshPaid();
    }

    private function randomItems($products, bool $bulk): array
    {
        $pick = $products->random(mt_rand(1, 100) <= 30 ? 2 : 1);
        $items = [];
        foreach ($pick as $p) {
            $qty = match ($p->category) {
                'Tumbler' => mt_rand(1, 4) * 6,
                'Kartu Debit Instan' => mt_rand(1, 8) * 10,
                default => $p->price >= 10000
                    ? ($bulk ? mt_rand(2, 6) * 10 : mt_rand(1, 4) * 5)
                    : ($bulk ? mt_rand(3, 16) * 10 : mt_rand(1, 6) * 5),
            };
            $items[] = [
                'product_id' => $p->id, 'name' => $p->name, 'category' => $p->category,
                'spec' => $p->description, 'qty' => $qty, 'price' => $p->price, 'subtotal' => $qty * $p->price,
            ];
        }

        return $items;
    }

    private function expensesFor(Carbon $d): void
    {
        $dow = $d->dayOfWeekIso;
        if ($dow === 1) {
            $this->cash('out', $d, 'Bahan baku', 'Blank card PVC '.(mt_rand(2, 5) * 100).' lembar', mt_rand(35, 70) * 10000, $d->weekOfYear % 2 ? 'tunai' : 'bank', 'admin', null, true);
        }
        if ($dow === 3 && mt_rand(0, 1)) {
            $this->cash('out', $d, 'Bahan baku', ['Tinta UV cyan', 'Tinta UV magenta', 'Tinta UV hitam', 'Tinta UV putih'][mt_rand(0, 3)], mt_rand(22, 32) * 10000, 'tunai', 'owner', null, true);
        }
        if ($dow === 4 && mt_rand(0, 2) === 0) {
            $this->cash('out', $d, 'Bahan baku', 'Holder & tali lanyard '.(mt_rand(1, 3) * 100).' pcs', mt_rand(15, 35) * 10000, 'bank', 'admin', null, true);
        }
        if ($dow === 5 && mt_rand(0, 3) === 0) {
            $this->cash('out', $d, 'Bahan baku', 'Tumbler polos '.(mt_rand(2, 4) * 12).' pcs', mt_rand(30, 70) * 10000, 'bank', 'admin', null, true);
        }
        if ($d->day === 3) {
            $this->cash('out', $d, 'Operasional (listrik, internet, sewa)', 'Token listrik & internet bulanan', mt_rand(45, 60) * 10000, 'bank', 'owner', null, true);
        }
        if ($d->day === 18 && mt_rand(0, 1)) {
            $this->cash('out', $d, 'Perawatan mesin', 'Pembersihan head printer UV', mt_rand(25, 45) * 10000, 'tunai', 'owner', null, true);
        }
        if ($dow === 6 && mt_rand(0, 2) === 0) {
            $this->cash('out', $d, 'Transportasi & pengiriman', 'Ongkos kirim pesanan', mt_rand(3, 9) * 10000, 'tunai', 'admin');
        }
        if ($d->isLastOfMonth() && $d->lt(Fmt::today())) {
            $this->cash('out', $d, 'Penarikan pemilik (prive)', 'Penarikan pemilik akhir bulan', 1500000, 'tunai', 'owner');
        }
    }

    private function pipeline(Carbon $today, $customers, $products): void
    {
        $c = $customers->keyBy('name');
        $p = $products->keyBy('name');
        $specs = [
            ['PT Sinar Kemang', [['ID Card PVC cetak 2 sisi', 150]], 'produksi', 1.0],
            ['Bu Sari', [['Kartu debit instan', 40]], 'antre', 1.0],
            ['SMAN 1 Kemang', [['Holder + lanyard sablon', 60]], 'antre', 0.5],
            ['Karang Taruna RW 05', [['ID Card PVC cetak 1 sisi', 120]], 'antre', 0.5],
            ['Koperasi Karyawan Bogor', [['ID Card PVC cetak 2 sisi', 200], ['Holder ID card', 200]], 'antre', 0.5],
            ['Komunitas Gowes Kemang', [['Tumbler custom', 24]], 'masuk', 0.5],
            ['CV Maju Bersama', [['ID Card PVC cetak 2 sisi', 80]], 'masuk', 0.0],
            ['Klinik Pratama Sehat', [['Holder ID card', 30], ['Lanyard sablon 2 cm', 30]], 'selesai', 0.5],
        ];

        foreach ($specs as $i => [$custName, $rows, $status, $payRatio]) {
            $customer = $c[$custName];
            $items = [];
            foreach ($rows as [$name, $qty]) {
                $prod = $p[$name];
                $items[] = ['product_id' => $prod->id, 'name' => $prod->name, 'category' => $prod->category, 'spec' => $prod->description, 'qty' => $qty, 'price' => $prod->price, 'subtotal' => $qty * $prod->price];
            }
            $date = $status === 'selesai' ? $today->copy()->subDay() : $today->copy();
            $total = array_sum(array_column($items, 'subtotal'));
            $order = new Order([
                'code' => CodeGenerator::order($date),
                'customer_id' => $customer->id,
                'status' => $status,
                'order_date' => $date->toDateString(),
                'due_date' => $today->copy()->addDays(2 + $i % 4)->toDateString(),
                'total' => $total,
                'total_qty' => array_sum(array_column($items, 'qty')),
                'created_by' => $this->users['admin']->id,
                'notes' => $customer->name === 'SMAN 1 Kemang' ? 'Lanyard hijau, logo sekolah. Desain sama dengan pesanan sebelumnya.' : null,
            ]);
            if ($status === 'produksi') {
                $order->fill(['scheduled_date' => $today->toDateString(), 'queued_at' => $today->copy()->setTime(7, 50), 'started_at' => $today->copy()->setTime(8, 10), 'operator_id' => $this->users['operator']->id]);
            } elseif ($status === 'antre') {
                $order->queued_at = $today->copy()->setTime(8, 30 + $i);
                $order->scheduled_date = QueueService::findSlot($order->total_qty);
            } elseif ($status === 'selesai') {
                $order->fill(['scheduled_date' => $date->toDateString(), 'queued_at' => $date->copy()->setTime(9, 0), 'started_at' => $date->copy()->setTime(13, 0), 'finished_at' => $date->copy()->setTime(15, 20), 'operator_id' => $this->users['operator']->id]);
            }
            $order->save();
            $order->items()->createMany($items);

            if ($payRatio > 0) {
                $amount = (int) (round($total * $payRatio / 1000) * 1000);
                $label = $payRatio >= 1 ? 'Pembayaran lunas ' : 'DP ';
                $this->cash('in', $date, 'Pembayaran pesanan', $label.$order->code.' · '.$customer->name, $amount, 'bank', 'admin', $order->id);
                $order->refreshPaid();
            }
        }
    }

    private function accountabilityExamples(Carbon $today): void
    {
        $owner = $this->users['owner'];
        $admin = $this->users['admin'];

        $dup = $this->cash('out', $today, 'Bahan baku', 'Blank card PVC 500 lembar', 650000, 'tunai', 'admin', null, true);
        $dup->update(['status' => 'batal', 'voided_by' => $owner->id, 'voided_at' => $today->copy()->setTime(8, 55), 'void_reason' => 'Nota ganda, sudah dicatat di transaksi lain']);

        $this->cash('out', $today, 'Perawatan mesin', 'Printer UV: servis head', 1200000, 'bank', 'admin', null, true, 'menunggu');
        $this->cash('out', $today, 'Bahan baku', 'Stok tumbler stainless 50 pcs', 1750000, 'bank', 'admin', null, true, 'menunggu');

        $logs = [
            [$owner, 'login', null, 'Masuk ke aplikasi', $today->copy()->setTime(7, 45)],
            [$admin, 'login', null, 'Masuk ke aplikasi', $today->copy()->setTime(7, 48)],
            [$owner, 'void', $dup, 'Membatalkan '.$dup->code.' Rp 650.000 · alasan: Nota ganda, sudah dicatat di transaksi lain', $today->copy()->setTime(8, 55)],
            [$this->users['operator'], 'status', null, 'Mulai produksi pesanan PT Sinar Kemang', $today->copy()->setTime(8, 10)],
            [$admin, 'create', null, 'Mengajukan pengeluaran Printer UV: servis head Rp 1.200.000 — menunggu persetujuan Owner', $today->copy()->setTime(9, 12)],
        ];
        foreach ($logs as [$user, $action, $subject, $desc, $at]) {
            AuditLog::create([
                'user_id' => $user->id, 'action' => $action,
                'subject_type' => $subject ? class_basename($subject) : null, 'subject_id' => $subject?->id, 'subject_label' => $subject?->code,
                'description' => $desc, 'ip_address' => '127.0.0.1', 'created_at' => $at,
            ]);
        }
    }

    private function cash(string $type, Carbon $date, string $category, string $desc, int $amount, string $account, string $by, ?int $orderId = null, bool $receipt = false, string $status = 'tercatat'): CashTransaction
    {
        $user = $this->users[$by];
        $approved = $status === 'tercatat';

        return CashTransaction::create([
            'code' => CodeGenerator::cash($type, $date),
            'type' => $type,
            'date' => $date->toDateString(),
            'category' => $category,
            'description' => $desc,
            'amount' => $amount,
            'account' => $account,
            'order_id' => $orderId,
            'receipt_path' => $receipt ? 'nota-kas/contoh-nota.svg' : null,
            'receipt_name' => $receipt ? 'nota-'.$date->format('Ymd').'.svg' : null,
            'status' => $status,
            'created_by' => $user->id,
            'approved_by' => $approved && $type === 'out' && $amount > 500000 ? $this->users['owner']->id : null,
            'approved_at' => $approved && $type === 'out' && $amount > 500000 ? $date->copy()->setTime(12, 0) : null,
            'created_at' => $date->copy()->setTime(mt_rand(8, 17), mt_rand(0, 59)),
            'updated_at' => $date->copy()->setTime(17, 0),
        ]);
    }
}
