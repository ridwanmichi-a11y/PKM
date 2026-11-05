<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Setting;
use App\Support\Fmt;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Penjadwalan antrean untuk SATU mesin produksi.
 * Pesanan dijadwalkan ke hari terdekat yang kapasitasnya masih cukup
 * (urutan pertama masuk, pertama dikerjakan).
 */
class QueueService
{
    public static function capacity(): int
    {
        return max(1, (int) Setting::get('daily_capacity', 500));
    }

    /** Jumlah pcs yang sudah terjadwal pada tanggal tertentu. */
    public static function load(CarbonInterface $date, ?int $exceptOrderId = null): int
    {
        return (int) Order::query()
            ->whereIn('status', Order::ACTIVE_QUEUE)
            ->whereDate('scheduled_date', $date->toDateString())
            ->when($exceptOrderId, fn ($q) => $q->where('id', '!=', $exceptOrderId))
            ->sum('total_qty');
    }

    public static function isWorkday(CarbonInterface $date): bool
    {
        return ! ((int) Setting::get('closed_on_sunday', 1) && $date->isSunday());
    }

    /** Cari tanggal produksi paling awal yang masih muat. */
    public static function findSlot(int $qty, ?int $exceptOrderId = null, ?CarbonInterface $from = null): Carbon
    {
        $cap = static::capacity();
        $date = ($from ? Carbon::parse($from->toDateString()) : Fmt::today())->copy();

        for ($i = 0; $i < 120; $i++) {
            if (static::isWorkday($date)) {
                $load = static::load($date, $exceptOrderId);
                // Muat, atau pesanan besar di hari yang masih kosong.
                if ($load + $qty <= $cap || $load === 0) {
                    return $date;
                }
            }
            $date->addDay();
        }

        return $date;
    }

    /** Masukkan pesanan ke antrean & tentukan tanggal produksinya. */
    public static function enqueue(Order $order): void
    {
        $order->scheduled_date = static::findSlot($order->total_qty, $order->id);
        $order->queued_at = Fmt::now();
    }

    /** Ringkasan beban mesin untuk N hari kerja ke depan. */
    public static function outlook(int $days = 3): array
    {
        $cap = static::capacity();
        $out = [];
        $date = Fmt::today();
        while (count($out) < $days) {
            if (static::isWorkday($date)) {
                $load = static::load($date);
                $out[] = [
                    'date' => $date->copy(),
                    'load' => $load,
                    'capacity' => $cap,
                    'percent' => min(100, (int) round($load / $cap * 100)),
                    'left' => max(0, $cap - $load),
                ];
            }
            $date->addDay();
        }

        return $out;
    }

    /** Daftar pekerjaan mesin berurutan (yang sedang diproduksi paling atas). */
    public static function jobs(int $limit = 50)
    {
        return Order::query()
            ->with(['customer', 'items', 'operator'])
            ->whereIn('status', Order::ACTIVE_QUEUE)
            ->queueOrder()
            ->limit($limit)
            ->get();
    }
}
