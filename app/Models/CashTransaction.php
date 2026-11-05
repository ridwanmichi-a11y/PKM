<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashTransaction extends Model
{
    public const STATUSES = [
        'tercatat' => 'Tercatat',
        'menunggu' => 'Menunggu Owner',
        'ditolak' => 'Ditolak',
        'batal' => 'Dibatalkan',
    ];

    protected $fillable = [
        'code', 'type', 'date', 'category', 'description', 'amount', 'account', 'order_id',
        'receipt_path', 'receipt_name', 'status', 'created_by', 'approved_by', 'approved_at',
        'reject_reason', 'voided_by', 'voided_at', 'void_reason',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'amount' => 'integer',
            'approved_at' => 'datetime',
            'voided_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function voider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    /** Hanya transaksi sah yang dihitung di saldo & laporan. */
    public function scopeRecorded(Builder $q): Builder
    {
        return $q->where('status', 'tercatat');
    }

    public function scopeBetween(Builder $q, $from, $to): Builder
    {
        return $q->whereDate('date', '>=', $from)->whereDate('date', '<=', $to);
    }

    public function isIn(): bool
    {
        return $this->type === 'in';
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function accountLabel(): string
    {
        return config('sipz.accounts')[$this->account] ?? $this->account;
    }

    public function signedAmount(): string
    {
        return ($this->isIn() ? '+ ' : '− ').\App\Support\Fmt::rp($this->amount);
    }

    /** Saldo kas sampai tanggal tertentu (inklusif). */
    public static function balance($until = null, ?string $account = null): int
    {
        $q = static::query()->recorded()
            ->when($until, fn ($q) => $q->whereDate('date', '<=', $until))
            ->when($account, fn ($q) => $q->where('account', $account));

        $in = (clone $q)->where('type', 'in')->sum('amount');
        $out = (clone $q)->where('type', 'out')->sum('amount');

        return (int) $in - (int) $out;
    }
}
