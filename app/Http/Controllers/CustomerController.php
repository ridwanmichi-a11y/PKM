<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q'));
        $customers = Customer::query()
            ->withCount(['orders' => fn ($o) => $o->where('status', '!=', 'batal')])
            ->withSum(['orders as total_spent' => fn ($o) => $o->where('status', '!=', 'batal')], 'total')
            ->withMax('orders as last_order', 'order_date')
            ->when($q, fn ($w) => $w->where(fn ($s) => $s->where('name', 'like', "%$q%")
                ->orWhere('phone', 'like', "%$q%")->orWhere('contact_person', 'like', "%$q%")))
            ->orderByDesc('last_order')
            ->orderBy('name')
            ->paginate(20)->withQueryString();

        return view('customers.index', compact('customers', 'q'));
    }

    public function show(Customer $customer)
    {
        $orders = $customer->orders()->with('items')->latest('order_date')->latest('id')->get();

        return view('customers.show', [
            'customer' => $customer,
            'orders' => $orders,
            'stats' => [
                'count' => $orders->where('status', '!=', 'batal')->count(),
                'spent' => $orders->where('status', '!=', 'batal')->sum('total'),
                'owed' => $orders->where('status', '!=', 'batal')->sum(fn ($o) => $o->remaining()),
            ],
        ]);
    }

    public function create()
    {
        return view('customers.form', ['customer' => new Customer(['type' => 'instansi'])]);
    }

    public function store(Request $request)
    {
        $customer = Customer::create($this->validated($request));
        Audit::log('create', $customer, 'Menambah pelanggan '.$customer->name);

        return redirect()->route('customers.show', $customer)->with('success', 'Pelanggan '.$customer->name.' ditambahkan.');
    }

    public function edit(Customer $customer)
    {
        return view('customers.form', compact('customer'));
    }

    public function update(Request $request, Customer $customer)
    {
        $before = $customer->only(['name', 'type', 'contact_person', 'phone', 'email', 'address']);
        $customer->update($this->validated($request));
        Audit::log('update', $customer, 'Mengubah data pelanggan '.$customer->name,
            Audit::diff($before, $customer->only(array_keys($before))));

        return redirect()->route('customers.show', $customer)->with('success', 'Data pelanggan disimpan.');
    }

    public function destroy(Customer $customer)
    {
        if ($customer->orders()->exists()) {
            return back()->withErrors(['customer' => 'Pelanggan yang punya riwayat pesanan tidak bisa dihapus, agar riwayat tetap utuh.']);
        }
        Audit::log('delete', $customer, 'Menghapus pelanggan '.$customer->name);
        $customer->delete();

        return redirect()->route('customers.index')->with('success', 'Pelanggan dihapus.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'type' => ['required', Rule::in(array_keys(Customer::TYPES))],
            'contact_person' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
            'address' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'name.required' => 'Nama pelanggan wajib diisi.',
            'email.email' => 'Format email tidak valid.',
        ]);
    }
}
