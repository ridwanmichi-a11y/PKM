<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\QueueService;
use App\Support\Fmt;

/**
 * Halaman kerja operator: daftar pekerjaan mesin berurutan.
 * Dirancang untuk dibuka dari ponsel.
 */
class ProductionController extends Controller
{
    public function index()
    {
        $jobs = QueueService::jobs(30);
        $today = Fmt::today();

        $doneToday = Order::query()
            ->whereIn('status', ['selesai', 'diserahkan'])
            ->whereDate('finished_at', $today->toDateString())
            ->with('customer', 'items')
            ->latest('finished_at')
            ->get();

        $todayJobs = $jobs->filter(fn ($o) => $o->scheduled_date && $o->scheduled_date->lte($today));

        return view('production.index', [
            'jobs' => $jobs,
            'running' => $jobs->firstWhere('status', 'produksi'),
            'next' => $jobs->firstWhere('status', 'antre'),
            'doneToday' => $doneToday,
            'todayTotal' => $todayJobs->count() + $doneToday->count(),
            'outlook' => QueueService::outlook(1)[0],
        ]);
    }
}
