<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    /** Urutan alur pesanan. */
    public const STATUSES = [
        'masuk' => 'Pesanan Masuk',
        'antre' => 'Antre',
        'produksi' => 'Diproduksi',
        'selesai' => 'Siap Diambil',
        'diserahkan' => 'Diserahkan',
        'batal' => 'Dibatalkan',
    ];

    /** Status berikutnya + label tombol + peran yang boleh menjalankan. */
    public const FLOW = [
        'masuk' => ['next' => 'antre', 'label' => 'Masukkan ke antrean', 'roles' => ['owner', 'admin']],
        'antre' => ['next' => 'produksi', 'label' => 'Mulai produksi', 'roles' => ['owner', 'operator']],
        'produksi' => ['next' => 'selesai', 'label' => 'Tandai selesai', 'roles' => ['owner', 'operator']],
        'selesai' => ['next' => 'diserahkan', 'label' => 'Serahkan ke pelanggan', 'roles' => ['owner', 'admin']],
    ];

    /** Status yang masih membebani mesin. */
    public const ACTIVE_QUEUE = ['antre', 'produksi'];

    protected $fillable = [
        'code', 'customer_id', 'status', 'order_date', 'due_date', 'scheduled_date', 'queued_at',
        'total', 'paid', 'total_qty', 'design_file', 'design_file_name', 'notes', 'repeat_of_id',
        'created_by', 'operator_id', 'started_at', 'finished_at', 'handed_over_at', 'cancel_reason',
    ];

    protected function casts(): array
    {
        return [
            'order_date' => 'date',
            'due_date' => 'date',
            'scheduled_date' => 'date',
            'queued_at' => 'datetime',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'handed_over_at' => 'datetime',
            'total' => 'integer',
            'paid' => 'integer',
            'total_qty' => 'integer',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(CashTransaction::class)->where('type', 'in');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operator_id');
    }

    public function repeatOf(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'repeat_of_id');
    }

    public function scopeQueueOrder(Builder $q): Builder
    {
        return $q->orderByRaw("CASE WHEN status = 'produksi' THEN 0 ELSE 1 END")
            ->orderBy('scheduled_date')
            ->orderBy('queued_at')
            ->orderBy('id');
    }

    /* ---------- Label & status ---------- */

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function remaining(): int
    {
        return max(0, $this->total - $this->paid);
    }

    public function isPaidOff(): bool
    {
        return $this->total > 0 && $this->paid >= $this->total;
    }

    /** lunas | dp | belum */
    public function paymentState(): string
    {
        if ($this->isPaidOff()) {
            return 'lunas';
        }

        return $this->paid > 0 ? 'dp' : 'belum';
    }

    public function paymentLabel(): string
    {
        return match ($this->paymentState()) {
            'lunas' => 'Lunas',
            'dp' => 'DP '.($this->total ? round($this->paid / $this->total * 100) : 0).'%',
            default => 'Belum bayar',
        };
    }

    public function nextStep(): ?array
    {
        return self::FLOW[$this->status] ?? null;
    }

    public function canBeEdited(): bool
    {
        return in_array($this->status, ['masuk', 'antre'], true);
    }

    public function itemSummary(): string
    {
        $items = $this->relationLoaded('items') ? $this->items : $this->items()->get();
        $first = $items->first();
        if (! $first) {
            return '–';
        }

        return $first->name.($items->count() > 1 ? ' +'.($items->count() - 1).' item' : '');
    }

    /** Hitung ulang total pembayaran dari kas masuk yang sah. */
    public function refreshPaid(): void
    {
        $this->paid = (int) $this->payments()->where('status', 'tercatat')->sum('amount');
        $this->save();
    }
}
