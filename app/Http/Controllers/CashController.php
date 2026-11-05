<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\CashTransaction;
use App\Services\CashService;
use App\Services\ReportService;
use App\Support\Fmt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class CashController extends Controller
{
    public function index(Request $request)
    {
        [$from, $to, $periodLabel, $period] = ReportService::period($request->query('periode'));
        $filters = $request->only(['jenis', 'status', 'kategori', 'akun', 'q']);

        $tx = $this->query($from, $to, $filters)->with(['creator', 'approver', 'voider', 'order'])
            ->orderByDesc('date')->orderByDesc('id')
            ->paginate(25)->withQueryString();

        $sum = ReportService::summary($from, $to);

        return view('cash.index', [
            'transactions' => $tx,
            'filters' => $filters,
            'period' => $period,
            'periodLabel' => $periodLabel,
            'periods' => $this->periodOptions(),
            'summary' => $sum,
            'pendingCount' => CashTransaction::where('status', 'menunggu')->count(),
            'balance' => CashTransaction::balance(),
            'balanceByAccount' => collect(config('sipz.accounts'))->map(fn ($label, $key) => CashTransaction::balance(null, $key)),
            'audit' => AuditLog::with('user')->whereIn('action', ['approve', 'reject', 'void', 'create', 'payment'])
                ->where('subject_type', 'CashTransaction')->latest('created_at')->limit(6)->get(),
            'categories' => array_merge(config('sipz.cash_in_categories'), config('sipz.cash_out_categories')),
            'limit' => CashService::approvalLimit(),
        ]);
    }

    public function create(Request $request)
    {
        $type = $request->query('jenis') === 'masuk' ? 'in' : 'out';

        return view('cash.form', [
            'type' => $type,
            'categories' => config($type === 'in' ? 'sipz.cash_in_categories' : 'sipz.cash_out_categories'),
            'accounts' => config('sipz.accounts'),
            'limit' => CashService::approvalLimit(),
        ]);
    }

    public function store(Request $request)
    {
        $type = $request->input('type') === 'in' ? 'in' : 'out';
        $categories = config($type === 'in' ? 'sipz.cash_in_categories' : 'sipz.cash_out_categories');
        if ($type === 'in') {
            // Pembayaran pesanan dicatat dari halaman pesanan agar terhubung ke pesanannya.
            $categories = array_values(array_diff($categories, ['Pembayaran pesanan']));
        }

        $data = $request->validate([
            'type' => ['required', Rule::in(['in', 'out'])],
            'date' => ['required', 'date', 'before_or_equal:today'],
            'category' => ['required', Rule::in($categories)],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required'],
            'account' => ['required', Rule::in(array_keys(config('sipz.accounts')))],
            'receipt' => [$type === 'out' ? 'required' : 'nullable', 'file', 'max:5120', 'mimes:jpg,jpeg,png,pdf'],
        ], [
            'date.before_or_equal' => 'Tanggal tidak boleh di masa depan.',
            'description.required' => 'Keterangan wajib diisi.',
            'amount.required' => 'Jumlah wajib diisi.',
            'receipt.required' => 'Foto nota/kuitansi wajib dilampirkan untuk pengeluaran.',
            'receipt.mimes' => 'Nota harus berupa JPG, PNG, atau PDF.',
            'receipt.max' => 'Ukuran nota maksimal 5 MB.',
            'category.in' => 'Kategori tidak valid.',
        ]);

        $data['amount'] = (int) preg_replace('/\D/', '', $data['amount']);
        if ($data['amount'] <= 0) {
            return back()->withInput()->withErrors(['amount' => 'Jumlah harus lebih dari 0.']);
        }

        $tx = CashService::create($data, $request->user(), $request->file('receipt'));

        return redirect()->route('cash.index')->with('success', $tx->status === 'menunggu'
            ? $tx->code.' tersimpan dan menunggu persetujuan Owner (di atas '.Fmt::rp(CashService::approvalLimit()).').'
            : $tx->code.' tercatat di buku kas.');
    }

    public function approve(Request $request, CashTransaction $transaction)
    {
        CashService::approve($transaction, $request->user());

        return back()->with('success', $transaction->code.' disetujui dan masuk buku kas.');
    }

    public function reject(Request $request, CashTransaction $transaction)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:200']], ['reason.required' => 'Alasan penolakan wajib diisi.']);
        CashService::reject($transaction, $request->user(), $data['reason']);

        return back()->with('success', $transaction->code.' ditolak.');
    }

    public function void(Request $request, CashTransaction $transaction)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:200']], ['reason.required' => 'Alasan pembatalan wajib diisi.']);
        CashService::void($transaction, $request->user(), $data['reason']);

        return back()->with('success', $transaction->code.' dibatalkan. Riwayatnya tetap tersimpan.');
    }

    public function receipt(CashTransaction $transaction)
    {
        abort_unless($transaction->receipt_path && Storage::disk('local')->exists($transaction->receipt_path), 404, 'Nota tidak ditemukan.');

        return Storage::disk('local')->response($transaction->receipt_path, $transaction->receipt_name);
    }

    /** Ekspor buku kas ke CSV (langsung terbuka di Excel). */
    public function export(Request $request)
    {
        [$from, $to, $label] = ReportService::period($request->query('periode'));
        $rows = $this->query($from, $to, $request->only(['jenis', 'status', 'kategori', 'akun', 'q']))
            ->with('creator')->orderBy('date')->orderBy('id')->get();

        $filename = 'buku-kas-sipzstore-'.str_replace(' ', '-', strtolower($label)).'.csv';

        return response()->streamDownload(function () use ($rows, $label) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"."sep=;\n");
            fputcsv($out, ['Buku Kas SipzStore - '.$label], ';');
            fputcsv($out, ['Tanggal', 'No. Bukti', 'Jenis', 'Kategori', 'Keterangan', 'Akun', 'Masuk', 'Keluar', 'Status', 'Dicatat oleh'], ';');
            foreach ($rows as $t) {
                fputcsv($out, [
                    $t->date->format('d/m/Y'), $t->code, $t->isIn() ? 'Masuk' : 'Keluar', $t->category, $t->description,
                    $t->accountLabel(), $t->isIn() ? $t->amount : '', $t->isIn() ? '' : $t->amount,
                    $t->statusLabel(), $t->creator?->signature(),
                ], ';');
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function query($from, $to, array $f)
    {
        return CashTransaction::query()
            ->between($from->toDateString(), $to->toDateString())
            ->when(($f['jenis'] ?? null) === 'masuk', fn ($q) => $q->where('type', 'in'))
            ->when(($f['jenis'] ?? null) === 'keluar', fn ($q) => $q->where('type', 'out'))
            ->when($f['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($f['kategori'] ?? null, fn ($q, $c) => $q->where('category', $c))
            ->when($f['akun'] ?? null, fn ($q, $a) => $q->where('account', $a))
            ->when($f['q'] ?? null, fn ($q, $s) => $q->where(fn ($w) => $w->where('code', 'like', "%$s%")->orWhere('description', 'like', "%$s%")));
    }

    private function periodOptions(): array
    {
        $opts = [];
        $d = Fmt::today()->startOfMonth();
        for ($i = 0; $i < 12; $i++) {
            $opts[$d->format('Y-m')] = Fmt::date($d, 'F Y');
            $d->subMonth();
        }
        $opts[Fmt::today()->format('Y')] = 'Tahun '.Fmt::today()->format('Y');

        return $opts;
    }
}
