<?php

namespace App\Services;

use App\Models\CashTransaction;
use App\Models\Order;
use Carbon\CarbonInterface;

/**
 * Nomor dokumen otomatis & berurutan (tidak bisa diketik manual).
 *  - Pesanan  : PSN-YYMMDD-NN   contoh PSN-261105-03
 *  - Kas masuk: KM-YYMM-NNNN    contoh KM-2611-0042
 *  - Kas keluar: KK-YYMM-NNNN   contoh KK-2611-0017
 * Panggil di dalam DB::transaction() agar aman dari nomor ganda.
 */
class CodeGenerator
{
    public static function order(CarbonInterface $date): string
    {
        $prefix = 'PSN-'.$date->format('ymd').'-';

        return $prefix.str_pad((string) static::next(Order::class, $prefix), 2, '0', STR_PAD_LEFT);
    }

    public static function cash(string $type, CarbonInterface $date): string
    {
        $prefix = ($type === 'in' ? 'KM-' : 'KK-').$date->format('ym').'-';

        return $prefix.str_pad((string) static::next(CashTransaction::class, $prefix), 4, '0', STR_PAD_LEFT);
    }

    private static function next(string $model, string $prefix): int
    {
        $codes = $model::query()
            ->where('code', 'like', $prefix.'%')
            ->lockForUpdate()
            ->pluck('code');

        $max = 0;
        foreach ($codes as $code) {
            $max = max($max, (int) substr($code, strlen($prefix)));
        }

        return $max + 1;
    }
}
