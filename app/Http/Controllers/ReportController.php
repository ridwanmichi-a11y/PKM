<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ReportService;
use App\Support\Fmt;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        return view('reports.index', $this->data($request));
    }

    /** Versi siap cetak / simpan PDF. */
    public function print(Request $request)
    {
        return view('reports.print', $this->data($request) + [
            'printedBy' => $request->user(),
            'owner' => User::where('role', 'owner')->first(),
            'admin' => User::where('role', 'admin')->where('is_active', true)->first(),
        ]);
    }

    /** Ekspor ke CSV (terbuka di Excel). */
    public function export(Request $request)
    {
        $d = $this->data($request);
        $filename = 'laporan-keuangan-sipzstore-'.str_replace(' ', '-', strtolower($d['periodLabel'])).'.csv';

        return response()->streamDownload(function () use ($d) {
            $out = fopen('php://output', 'w');
            $row = fn (array $r) => fputcsv($out, $r, ';');
            fwrite($out, "\xEF\xBB\xBF"."sep=;\n");

            $row(['LAPORAN LABA RUGI SIPZSTORE', $d['periodLabel'], 'basis kas']);
            $row(['Pendapatan']);
            foreach ($d['pl']['revenue'] as $k => $v) $row(['', $k, $v]);
            $row(['Total pendapatan', '', $d['pl']['totalRevenue']]);
            $row(['Harga pokok produksi']);
            foreach ($d['pl']['cogs'] as $k => $v) $row(['', $k, -$v]);
            $row(['Laba kotor', '', $d['pl']['gross']]);
            $row(['Beban operasional']);
            foreach ($d['pl']['opex'] as $k => $v) $row(['', $k, -$v]);
            $row(['LABA BERSIH', '', $d['pl']['net']]);
            $row([]);

            $row(['LAPORAN ARUS KAS', $d['periodLabel']]);
            $row(['Saldo awal', '', $d['cf']['opening']]);
            foreach ($d['cf']['in'] as $k => $v) $row(['Kas masuk', $k, $v]);
            foreach ($d['cf']['out'] as $k => $v) $row(['Kas keluar', $k, -$v]);
            $row(['Saldo akhir', '', $d['cf']['closing']]);
            $row([]);

            $row(['PENJUALAN PER KATEGORI PRODUK', $d['periodLabel']]);
            $row(['Kategori', 'Jumlah (pcs)', 'Nilai (Rp)', 'Porsi (%)']);
            foreach ($d['ps']['byCategory'] as $c) $row([$c['category'], $c['qty'], $c['amount'], str_replace('.', ',', $c['share'])]);
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function data(Request $request): array
    {
        [$from, $to, $label, $value] = ReportService::period($request->query('periode', Fmt::today()->subMonth()->format('Y-m')));
        $tab = in_array($request->query('tab'), ['laba-rugi', 'arus-kas', 'produk'], true) ? $request->query('tab') : 'laba-rugi';

        // Pembanding: periode sebelumnya dengan panjang sama.
        $prevFrom = strlen($value) === 4 ? $from->copy()->subYear() : $from->copy()->subMonth();
        $prevTo = strlen($value) === 4 ? $to->copy()->subYear() : $prevFrom->copy()->endOfMonth();

        $options = [];
        $d = Fmt::today()->startOfMonth();
        for ($i = 0; $i < 12; $i++) {
            $options[$d->format('Y-m')] = Fmt::date($d, 'F Y');
            $d->subMonth();
        }
        $options[Fmt::today()->format('Y')] = 'Tahun '.Fmt::today()->format('Y');

        return [
            'tab' => $tab,
            'period' => $value,
            'periodLabel' => $label,
            'from' => $from,
            'to' => $to->greaterThan(Fmt::today()) ? Fmt::today() : $to,
            'options' => $options,
            'summary' => ReportService::summary($from, $to),
            'prev' => ReportService::summary($prevFrom, $prevTo),
            'pl' => ReportService::profitLoss($from, $to),
            'cf' => ReportService::cashFlow($from, $to),
            'ps' => ReportService::productSales($from, $to),
        ];
    }
}
