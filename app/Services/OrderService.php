<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Support\Audit;
use App\Support\Fmt;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    /**
     * Buat pesanan baru beserta item, pelanggan baru (bila perlu),
     * pembayaran awal, dan jadwal antrean.
     */
    public static function create(array $data, User $by, ?UploadedFile $design = null): Order
    {
        return DB::transaction(function () use ($data, $by, $design) {
            $customer = static::resolveCustomer($data, $by);
            $date = Fmt::today();
            $items = static::buildItems($data['items']);

            $order = new Order([
                'code' => CodeGenerator::order($date),
                'customer_id' => $customer->id,
                'status' => 'masuk',
                'order_date' => $date->toDateString(),
                'due_date' => $data['due_date'] ?? null,
                'notes' => $data['notes'] ?? null,
                'repeat_of_id' => $data['repeat_of_id'] ?? null,
                'created_by' => $by->id,
                'total' => array_sum(array_column($items, 'subtotal')),
                'total_qty' => array_sum(array_column($items, 'qty')),
            ]);

            static::applyDesign($order, $design, $data['repeat_of_id'] ?? null);

            if (! empty($data['enqueue'])) {
                $order->status = 'antre';
                QueueService::enqueue($order);
            }
            $order->save();
            $order->items()->createMany($items);

            Audit::log('create', $order, 'Membuat pesanan '.$order->code.' untuk '.$customer->name.' · '.Fmt::rp($order->total)
                .($order->repeat_of_id ? ' (pesan ulang dari '.$order->repeatOf?->code.')' : ''));

            $pay = (int) ($data['pay_amount'] ?? 0);
            if ($pay > 0) {
                CashService::recordPayment($order->fresh('customer'), $pay, $data['pay_account'] ?? 'tunai', $by, $date, $data['pay_proof'] ?? null);
            }

            return $order->fresh();
        });
    }

    public static function update(Order $order, array $data, User $by, ?UploadedFile $design = null): Order
    {
        if (! $order->canBeEdited()) {
            throw ValidationException::withMessages(['status' => 'Pesanan yang sudah diproduksi tidak bisa diubah.']);
        }

        return DB::transaction(function () use ($order, $data, $by, $design) {
            $before = $order->only(['total', 'total_qty', 'due_date', 'notes']);
            $before['due_date'] = optional($order->due_date)->toDateString();
            $items = static::buildItems($data['items']);
            $newTotal = array_sum(array_column($items, 'subtotal'));

            if ($newTotal < $order->paid) {
                throw ValidationException::withMessages(['items' => 'Total baru ('.Fmt::rp($newTotal).') lebih kecil dari yang sudah dibayar ('.Fmt::rp($order->paid).').']);
            }

            $order->fill([
                'customer_id' => static::resolveCustomer($data, $by)->id,
                'due_date' => $data['due_date'] ?? null,
                'notes' => $data['notes'] ?? null,
                'total' => $newTotal,
                'total_qty' => array_sum(array_column($items, 'qty')),
            ]);
            static::applyDesign($order, $design, null);

            if ($order->status === 'antre' && $order->isDirty('total_qty')) {
                $order->scheduled_date = QueueService::findSlot($order->total_qty, $order->id);
            }
            $order->save();
            $order->items()->delete();
            $order->items()->createMany($items);

            $after = $order->only(['total', 'total_qty', 'notes']) + ['due_date' => optional($order->due_date)->toDateString()];
            Audit::log('update', $order, 'Mengubah pesanan '.$order->code, Audit::diff($before, $after));

            return $order;
        });
    }

    /** Majukan status sesuai alur dan peran. */
    public static function advance(Order $order, User $by): Order
    {
        $step = $order->nextStep();
        if (! $step) {
            throw ValidationException::withMessages(['status' => 'Pesanan ini sudah di tahap akhir.']);
        }
        if (! $by->hasRole(...$step['roles'])) {
            throw ValidationException::withMessages(['status' => 'Peran Anda tidak boleh menjalankan "'.$step['label'].'".']);
        }

        return DB::transaction(function () use ($order, $by, $step) {
            $from = $order->status;
            $order->status = $step['next'];

            switch ($step['next']) {
                case 'antre':
                    QueueService::enqueue($order);
                    break;
                case 'produksi':
                    $running = Order::where('status', 'produksi')->where('id', '!=', $order->id)->first();
                    if ($running) {
                        throw ValidationException::withMessages(['status' => 'Mesin sedang dipakai untuk '.$running->code.'. Selesaikan dulu sebelum memulai pesanan lain.']);
                    }
                    $order->started_at = Fmt::now();
                    $order->operator_id = $by->id;
                    $order->scheduled_date = Fmt::today();
                    break;
                case 'selesai':
                    $order->finished_at = Fmt::now();
                    break;
                case 'diserahkan':
                    if (! $order->isPaidOff()) {
                        throw ValidationException::withMessages(['status' => 'Pesanan belum lunas (sisa '.Fmt::rp($order->remaining()).'). Catat pelunasan dulu sebelum diserahkan.']);
                    }
                    $order->handed_over_at = Fmt::now();
                    break;
            }
            $order->save();

            Audit::log('status', $order, $order->code.': '.Order::STATUSES[$from].' → '.$order->statusLabel()
                .($order->status === 'antre' ? ' (jadwal '.Fmt::date($order->scheduled_date, 'j M').')' : ''));

            return $order;
        });
    }

    public static function cancel(Order $order, User $by, string $reason): void
    {
        if (in_array($order->status, ['diserahkan', 'batal'], true)) {
            throw ValidationException::withMessages(['status' => 'Pesanan ini tidak bisa dibatalkan.']);
        }
        $order->update(['status' => 'batal', 'cancel_reason' => $reason, 'scheduled_date' => null]);
        Audit::log('void', $order, 'Membatalkan pesanan '.$order->code.' · alasan: '.$reason
            .($order->paid > 0 ? ' (pembayaran '.Fmt::rp($order->paid).' tetap tercatat)' : ''));
    }

    /* ---------------- bantuan ---------------- */

    private static function resolveCustomer(array $data, User $by): Customer
    {
        if (! empty($data['customer_id'])) {
            return Customer::findOrFail($data['customer_id']);
        }

        $customer = Customer::create([
            'name' => $data['new_customer_name'],
            'phone' => $data['new_customer_phone'] ?? null,
            'type' => $data['new_customer_type'] ?? 'perorangan',
        ]);
        Audit::log('create', $customer, 'Menambah pelanggan '.$customer->name.' dari form pesanan');

        return $customer;
    }

    private static function buildItems(array $rows): array
    {
        $products = Product::query()->whereIn('id', array_filter(array_column($rows, 'product_id')))->get()->keyBy('id');
        $items = [];
        foreach ($rows as $row) {
            $qty = (int) ($row['qty'] ?? 0);
            $price = (int) preg_replace('/\D/', '', (string) ($row['price'] ?? 0));
            if ($qty < 1) {
                continue;
            }
            $product = $products[$row['product_id'] ?? 0] ?? null;
            $items[] = [
                'product_id' => $product?->id,
                'name' => trim($row['name'] ?? '') ?: ($product?->name ?? 'Item'),
                'category' => $product?->category ?? 'Lainnya',
                'spec' => $row['spec'] ?? null,
                'qty' => $qty,
                'price' => $price,
                'subtotal' => $qty * $price,
            ];
        }
        if (! $items) {
            throw ValidationException::withMessages(['items' => 'Isi minimal satu item pesanan.']);
        }

        return $items;
    }

    private static function applyDesign(Order $order, ?UploadedFile $design, $repeatOfId): void
    {
        if ($design) {
            $order->design_file = $design->store('desain', 'local');
            $order->design_file_name = $design->getClientOriginalName();
        } elseif ($repeatOfId && ! $order->design_file) {
            // Pesan ulang: pakai file desain pesanan lama.
            $old = Order::find($repeatOfId);
            $order->design_file = $old?->design_file;
            $order->design_file_name = $old?->design_file_name;
        }
    }
}
