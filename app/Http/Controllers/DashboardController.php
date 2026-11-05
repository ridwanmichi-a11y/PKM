<?php

namespace App\Http\Controllers;

use App\Models\CashTransaction;
use App\Services\QueueService;
use App\Services\ReportService;
use App\Support\Fmt;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();
        if ($user->role === 'operator') {
            return redirect()->route('production.index');
        }

        $today = Fmt::today();
        $period = in_array($request->query('periode'), ['hari', 'minggu', 'bulan'], true) ? $request->query('periode') : 'bulan';

        // Periode berjalan & pembanding dengan panjang hari yang sama.
        [$from, $prevFrom, $label] = match ($period) {
            'hari' => [$today->copy(), $today->copy()->subDay(), 'kemarin'],
            'minggu' => [$today->copy()->startOfWeek(Carbon::MONDAY), $today->copy()->startOfWeek(Carbon::MONDAY)->subWeek(), 'minggu lalu'],
            default => [$today->copy()->startOfMonth(), $today->copy()->startOfMonth()->subMonth(), Fmt::date($today->copy()->startOfMonth()->subMonth(), 'F')],
        };
        $prevTo = $prevFrom->copy()->addDays((int) $from->diffInDays($today));

        $now = ReportService::summary($from, $today);
        $prev = ReportService::summary($prevFrom, $prevTo);

        $pending = CashTransaction::with('creator')
            ->where('status', 'menunggu')
            ->when(! $user->isOwner(), fn ($q) => $q->where('created_by', $user->id))
            ->latest()->get();

        return view('dashboard', [
            'period' => $period,
            'compareLabel' => $label,
            'now' => $now,
            'changes' => [
                'income' => ReportService::change($now['income'], $prev['income']),
                'expense' => ReportService::change($now['expense'], $prev['expense']),
                'profit' => ReportService::change($now['profit'], $prev['profit']),
            ],
            'receivables' => ReportService::receivables(),
            'series' => ReportService::monthlySeries(6),
            'outlook' => QueueService::outlook(1)[0],
            'jobs' => QueueService::jobs(5),
            'pending' => $pending,
            'recent' => CashTransaction::with('creator')->latest('id')->limit(6)->get(),
            'balance' => CashTransaction::balance(),
        ]);
    }
}
