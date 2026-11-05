<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Services\CashService;
use App\Services\OrderService;
use App\Services\QueueService;
use App\Support\Fmt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    /** Papan alur pesanan (kanban) atau tabel riwayat. */
    public function index(Request $request)
    {
        $view = $request->query('tampilan') === 'tabel' ? 'tabel' : 'papan';
        $q = trim((string) $request->query('q'));

        $search = function ($query) use ($q) {
            $query->when($q, fn ($w) => $w->where(fn ($s) => $s->where('code', 'like', "%$q%")
                ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%$q%")->orWhere('phone', 'like', "%$q%"))));
        };

        if ($view === 'tabel') {
            $orders = Order::with(['customer', 'items'])
                ->tap($search)
                ->when($request->query('status'), fn ($w, $s) => $w->where('status', $s))
                ->when($request->query('bayar') === 'belum-lunas', fn ($w) => $w->whereColumn('paid', '<', 'total')->where('status', '!=', 'batal'))
                ->latest('order_date')->latest('id')
                ->paginate(20)->withQueryString();

            return view('orders.table', compact('orders', 'view', 'q'));
        }

        // Papan: semua yang aktif + yang diserahkan 7 hari terakhir.
        $orders = Order::with(['customer', 'items'])
            ->tap($search)
            ->where(fn ($w) => $w->whereIn('status', ['masuk', 'antre', 'produksi', 'selesai'])
                ->orWhere(fn ($d) => $d->where('status', 'diserahkan')->where('handed_over_at', '>=', Fmt::today()->subDays(7))))
            ->queueOrder()
            ->get();

        $columns = [];
        foreach (['masuk', 'antre', 'produksi', 'selesai', 'diserahkan'] as $status) {
            $items = $orders->where('status', $status)->values();
            if ($status === 'diserahkan') {
                $items = $items->sortByDesc('handed_over_at')->take(6)->values();
            }
            $columns[$status] = $items;
        }

        return view('orders.board', [
            'columns' => $columns,
            'outlook' => QueueService::outlook(3),
            'view' => $view,
            'q' => $q,
        ]);
    }

    public function create(Request $request)
    {
        $repeat = $request->filled('ulang') ? Order::with('items', 'customer')->find($request->query('ulang')) : null;
        $customerId = $repeat?->customer_id ?? $request->query('pelanggan');
        $customer = $customerId ? Customer::find($customerId) : null;

        return view('orders.form', [
            'order' => new Order(['due_date' => Fmt::today()->addDays(3)]),
            'items' => $repeat
                ? $repeat->items->map(fn ($i) => $i->only(['product_id', 'name', 'spec', 'qty', 'price']))->all()
                : [['product_id' => null, 'name' => '', 'spec' => '', 'qty' => 1, 'price' => 0]],
            'customer' => $customer,
            'history' => $customer ? $customer->orders()->with('items')->where('status', '!=', 'batal')->latest('order_date')->limit(5)->get() : collect(),
            'repeat' => $repeat,
        ] + $this->formData());
    }

    public function store(Request $request)
    {
        $data = $this->validated($request, true);
        $data['pay_proof'] = $request->file('pay_proof');
        $order = OrderService::create($data, $request->user(), $request->file('design'));

        return redirect()->route('orders.show', $order)
            ->with('success', 'Pesanan '.$order->code.' tersimpan'.($order->status === 'antre' ? ' dan dijadwalkan produksi '.Fmt::date($order->scheduled_date, 'l, j M').'.' : '.'));
    }

    public function show(Order $order)
    {
        $order->load(['customer', 'items', 'creator', 'operator', 'repeatOf', 'payments.creator']);
        $position = null;
        if (in_array($order->status, Order::ACTIVE_QUEUE, true)) {
            $position = QueueService::jobs(200)->search(fn ($o) => $o->id === $order->id);
            $position = $position === false ? null : $position + 1;
        }

        return view('orders.show', [
            'order' => $order,
            'position' => $position,
            'history' => \App\Models\AuditLog::with('user')
                ->where('subject_type', 'Order')->where('subject_id', $order->id)
                ->orWhere(fn ($q) => $q->where('subject_type', 'CashTransaction')->whereIn('subject_id', $order->payments->pluck('id')))
                ->latest('created_at')->get(),
            'accounts' => config('sipz.accounts'),
        ]);
    }

    public function edit(Order $order)
    {
        abort_unless($order->canBeEdited(), 403, 'Pesanan yang sudah diproduksi tidak bisa diubah.');
        $order->load('items', 'customer');

        return view('orders.form', [
            'order' => $order,
            'items' => $order->items->map(fn ($i) => $i->only(['product_id', 'name', 'spec', 'qty', 'price']))->all(),
            'customer' => $order->customer,
            'history' => collect(),
            'repeat' => null,
        ] + $this->formData());
    }

    public function update(Request $request, Order $order)
    {
        $data = $this->validated($request, false);
        OrderService::update($order, $data, $request->user(), $request->file('design'));

        return redirect()->route('orders.show', $order)->with('success', 'Perubahan pesanan '.$order->code.' disimpan.');
    }

    public function advance(Request $request, Order $order)
    {
        OrderService::advance($order, $request->user());

        $msg = $order->code.' sekarang berstatus "'.$order->statusLabel().'"'
            .($order->status === 'antre' ? ', dijadwalkan '.Fmt::date($order->scheduled_date, 'l, j M') : '').'.';

        return back()->with('success', $msg);
    }

    public function pay(Request $request, Order $order)
    {
        $data = $request->validate([
            'amount' => ['required'],
            'account' => ['required', Rule::in(array_keys(config('sipz.accounts')))],
            'proof' => ['nullable', 'file', 'max:5120', 'mimes:jpg,jpeg,png,pdf'],
        ], ['amount.required' => 'Jumlah pembayaran wajib diisi.']);

        $amount = (int) preg_replace('/\D/', '', $data['amount']);
        CashService::recordPayment($order->load('customer'), $amount, $data['account'], $request->user(), null, $request->file('proof'));

        return back()->with('success', 'Pembayaran '.Fmt::rp($amount).' tercatat di kas masuk.');
    }

    public function cancel(Request $request, Order $order)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:200']], ['reason.required' => 'Alasan pembatalan wajib diisi.']);
        OrderService::cancel($order, $request->user(), $data['reason']);

        return back()->with('success', 'Pesanan '.$order->code.' dibatalkan.');
    }

    public function invoice(Order $order)
    {
        $order->load(['customer', 'items', 'payments' => fn ($q) => $q->where('status', 'tercatat'), 'creator']);

        return view('orders.invoice', compact('order'));
    }

    public function design(Order $order)
    {
        abort_unless($order->design_file && Storage::disk('local')->exists($order->design_file), 404, 'File desain tidak ditemukan.');

        return Storage::disk('local')->download($order->design_file, $order->design_file_name);
    }

    /* ---------------------------------------------------------------- */

    private function formData(): array
    {
        return [
            'customers' => Customer::orderBy('name')->get(['id', 'name', 'phone']),
            'products' => Product::active()->orderBy('category')->orderBy('name')->get(),
            'accounts' => config('sipz.accounts'),
            'capacity' => QueueService::capacity(),
        ];
    }

    private function validated(Request $request, bool $creating): array
    {
        $rules = [
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'new_customer_name' => ['nullable', 'string', 'max:150', 'required_without:customer_id'],
            'new_customer_phone' => ['nullable', 'string', 'max:30'],
            'new_customer_type' => ['nullable', Rule::in(array_keys(Customer::TYPES))],
            'due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', 'integer', 'exists:products,id'],
            'items.*.name' => ['nullable', 'string', 'max:150'],
            'items.*.spec' => ['nullable', 'string', 'max:255'],
            'items.*.qty' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'items.*.price' => ['nullable'],
            'design' => ['nullable', 'file', 'max:20480', 'mimes:pdf,jpg,jpeg,png,zip,rar,psd,ai,cdr'],
        ];
        if ($creating) {
            $rules += [
                'repeat_of_id' => ['nullable', 'integer', 'exists:orders,id'],
                'enqueue' => ['nullable', 'boolean'],
                'pay_amount' => ['nullable'],
                'pay_account' => ['nullable', Rule::in(array_keys(config('sipz.accounts')))],
                'pay_proof' => ['nullable', 'file', 'max:5120', 'mimes:jpg,jpeg,png,pdf'],
            ];
        }

        $data = $request->validate($rules, [
            'new_customer_name.required_without' => 'Pilih pelanggan atau isi nama pelanggan baru.',
            'items.required' => 'Isi minimal satu item pesanan.',
            'design.max' => 'File desain maksimal 20 MB.',
            'design.mimes' => 'File desain harus PDF, gambar, ZIP/RAR, PSD, AI, atau CDR.',
            'pay_proof.mimes' => 'Bukti bayar harus JPG, PNG, atau PDF.',
        ]);

        if ($creating) {
            $data['pay_amount'] = $request->input('pay_mode') === 'none'
                ? 0
                : (int) preg_replace('/\D/', '', (string) ($data['pay_amount'] ?? '0'));
            $data['enqueue'] = $request->boolean('enqueue');
        }

        return $data;
    }
}
