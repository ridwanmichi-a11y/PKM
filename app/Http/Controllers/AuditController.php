<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    public function index(Request $request)
    {
        $logs = AuditLog::with('user')
            ->when($request->query('pengguna'), fn ($q, $u) => $q->where('user_id', $u))
            ->when($request->query('aksi'), fn ($q, $a) => $q->where('action', $a))
            ->when($request->query('dari'), fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($request->query('sampai'), fn ($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->when($request->query('q'), fn ($q, $s) => $q->where(fn ($w) => $w->where('description', 'like', "%$s%")->orWhere('subject_label', 'like', "%$s%")))
            ->latest('created_at')->latest('id')
            ->paginate(30)->withQueryString();

        return view('audit.index', [
            'logs' => $logs,
            'users' => User::orderBy('name')->get(['id', 'name', 'role']),
            'filters' => $request->only(['pengguna', 'aksi', 'dari', 'sampai', 'q']),
        ]);
    }
}
