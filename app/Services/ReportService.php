<?php

namespace App\Services;

use App\Models\CashTransaction;
use App\Models\Order;
use App\Models\OrderItem;
use App\Support\Fmt;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Semua angka laporan dihitung dari buku kas berstatus "tercatat"
 * (basis kas), sehingga laporan selalu bisa ditelusuri ke bukti transaksinya.
 */
class ReportService
{
    private static function nonOperating(): array
    {
        return config('sipz.non_operating', []);
    }

    /** Pendapatan & beban usaha (tanpa setoran modal / prive). */
    public static function summary(CarbonInterface $from, CarbonInterface $to): array
    {
        $base = CashTransaction::query()->recorded()->between($from->toDateString(), $to->toDateString())
            ->whereNotIn('category', static::nonOperating());

        $income = (int) (clone $base)->where('type', 'in')->sum('amount');
        $expense = (int) (clone $base)->where('type', 'out')->sum('amount');

        return [
            'income' => $income,
            'expense' => $expense,
            'profit' => $income - $expense,
            'margin' => $income > 0 ? round(($income - $expense) / $income * 100, 1) : 0,
            'count_in' => (clone $base)->where('type', 'in')->count(),
            'count_out' => (clone $base)->where('type', 'out')->count(),
        ];
    }

    /** Persentase perubahan; null bila pembanding nol. */
    public static function change(int $now, int $before): ?float
    {
        return $before > 0 ? round(($now - $before) / $before * 100) : null;
    }

    /** Laporan laba rugi. */
    public static function profitLoss(CarbonInterface $from, CarbonInterface $to): array
    {
        $tx = CashTransaction::query()->recorded()
            ->between($from->toDateString(), $to->toDateString())
            ->whereNotIn('category', static::nonOperating())
            ->get();

        // Pendapatan dari pesanan dirinci per kategori produk, dibagi proporsional subtotal item.
        $revenue = [];
        $orderIds = $tx->where('type', 'in')->pluck('order_id')->filter()->unique();
        $itemsByOrder = OrderItem::query()->whereIn('order_id', $orderIds)->get()->groupBy('order_id');

        foreach ($tx->where('type', 'in') as $t) {
            $items = $t->order_id ? ($itemsByOrder[$t->order_id] ?? collect()) : collect();
            $sum = $items->sum('subtotal');
            if ($sum > 0) {
                foreach ($items->groupBy('category') as $cat => $rows) {
                    $label = 'Penjualan '.$cat;
                    $revenue[$label] = ($revenue[$label] ?? 0) + $t->amount * $rows->sum('subtotal') / $sum;
                }
            } else {
                $revenue[$t->category] = ($revenue[$t->category] ?? 0) + $t->amount;
            }
        }
        $revenue = array_map(fn ($v) => (int) round($v), $revenue);
        arsort($revenue);

        $cogsCats = config('sipz.cogs_categories', []);
        $out = $tx->where('type', 'out');
        $cogs = $out->whereIn('category', $cogsCats)->groupBy('category')->map->sum('amount')->sortDesc()->all();
        $opex = $out->whereNotIn('category', $cogsCats)->groupBy('category')->map->sum('amount')->sortDesc()->all();

        $totalRevenue = array_sum($revenue);
        $totalCogs = array_sum($cogs);
        $totalOpex = array_sum($opex);
        $gross = $totalRevenue - $totalCogs;
        $net = $gross - $totalOpex;

        return compact('revenue', 'cogs', 'opex', 'totalRevenue', 'totalCogs', 'totalOpex', 'gross', 'net')
            + ['margin' => $totalRevenue > 0 ? round($net / $totalRevenue * 100, 1) : 0];
    }

    /** Laporan arus kas (semua transaksi tercatat, termasuk modal & prive). */
    public static function cashFlow(CarbonInterface $from, CarbonInterface $to): array
    {
        $opening = CashTransaction::balance($from->copy()->subDay()->toDateString());
        $tx = CashTransaction::query()->recorded()->between($from->toDateString(), $to->toDateString())->get();

        $in = $tx->where('type', 'in')->groupBy('category')->map->sum('amount')->sortDesc()->all();
        $out = $tx->where('type', 'out')->groupBy('category')->map->sum('amount')->sortDesc()->all();
        $totalIn = array_sum($in);
        $totalOut = array_sum($out);

        $byAccount = [];
        foreach (config('sipz.accounts') as $key => $label) {
            $byAccount[$label] = CashTransaction::balance($to->toDateString(), $key);
        }

        return [
            'opening' => $opening,
            'in' => $in,
            'out' => $out,
            'totalIn' => $totalIn,
            'totalOut' => $totalOut,
            'closing' => $opening + $totalIn - $totalOut,
            'byAccount' => $byAccount,
        ];
    }

    /** Penjualan per kategori produk berdasarkan tanggal pesanan (tidak termasuk yang batal). */
    public static function productSales(CarbonInterface $from, CarbonInterface $to): array
    {
        $rows = OrderItem::query()
            ->whereHas('order', fn ($q) => $q->where('status', '!=', 'batal')
                ->whereDate('order_date', '>=', $from->toDateString())
                ->whereDate('order_date', '<=', $to->toDateString()))
            ->get();

        $total = (int) $rows->sum('subtotal');
        $byCategory = $rows->groupBy('category')->map(fn ($g, $cat) => [
            'category' => $cat,
            'qty' => (int) $g->sum('qty'),
            'amount' => (int) $g->sum('subtotal'),
            'share' => $total > 0 ? round($g->sum('subtotal') / $total * 100, 1) : 0,
        ])->sortByDesc('amount')->values()->all();

        $topProducts = $rows->groupBy('name')->map(fn ($g, $name) => [
            'name' => $name,
            'qty' => (int) $g->sum('qty'),
            'amount' => (int) $g->sum('subtotal'),
        ])->sortByDesc('amount')->take(8)->values()->all();

        $orders = Order::query()->where('status', '!=', 'batal')
            ->whereDate('order_date', '>=', $from->toDateString())
            ->whereDate('order_date', '<=', $to->toDateString());

        return [
            'total' => $total,
            'qty' => (int) $rows->sum('qty'),
            'orders' => (clone $orders)->count(),
            'completed' => (clone $orders)->where('status', 'diserahkan')->count(),
            'byCategory' => $byCategory,
            'topProducts' => $topProducts,
        ];
    }

    /** Pendapatan & pengeluaran usaha N bulan terakhir (untuk grafik). */
    public static function monthlySeries(int $months = 6): array
    {
        $out = [];
        $start = Fmt::today()->startOfMonth()->subMonths($months - 1);
        for ($i = 0; $i < $months; $i++) {
            $from = $start->copy()->addMonths($i);
            $to = $from->copy()->endOfMonth();
            $s = static::summary($from, $to);
            $out[] = [
                'label' => Fmt::date($from, 'M'),
                'full' => Fmt::date($from, 'F Y'),
                'income' => $s['income'],
                'expense' => $s['expense'],
                'partial' => $to->greaterThan(Fmt::today()),
            ];
        }

        return $out;
    }

    /** Total piutang (sisa tagihan) dari pesanan yang belum lunas. */
    public static function receivables(): array
    {
        $orders = Order::query()->where('status', '!=', 'batal')->whereColumn('paid', '<', 'total')->get();

        return ['amount' => (int) $orders->sum(fn ($o) => $o->remaining()), 'count' => $orders->count()];
    }

    /** Rentang tanggal dari "YYYY-MM" atau "YYYY". */
    public static function period(?string $value): array
    {
        $today = Fmt::today();
        if ($value && preg_match('/^\d{4}$/', $value)) {
            $from = Carbon::create((int) $value, 1, 1);

            return [$from, $from->copy()->endOfYear(), 'Tahun '.$value, $value];
        }
        if ($value && preg_match('/^\d{4}-\d{2}$/', $value)) {
            $from = Carbon::createFromFormat('Y-m-d', $value.'-01')->startOfDay();
        } else {
            $from = $today->copy()->startOfMonth();
            $value = $from->format('Y-m');
        }

        return [$from, $from->copy()->endOfMonth(), Fmt::date($from, 'F Y'), $value];
    }
}
