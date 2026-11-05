<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'action', 'subject_type', 'subject_id', 'subject_label',
        'description', 'changes', 'ip_address', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'changes' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public const ACTIONS = [
        'login' => 'Masuk',
        'logout' => 'Keluar',
        'create' => 'Membuat',
        'update' => 'Mengubah',
        'delete' => 'Menghapus',
        'status' => 'Ubah status',
        'payment' => 'Pembayaran',
        'approve' => 'Menyetujui',
        'reject' => 'Menolak',
        'void' => 'Membatalkan',
        'settings' => 'Pengaturan',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function actionLabel(): string
    {
        return self::ACTIONS[$this->action] ?? $this->action;
    }

    /** Warna titik di linimasa audit. */
    public function tone(): string
    {
        return match ($this->action) {
            'void', 'reject', 'delete' => 'bad',
            'approve', 'payment' => 'good',
            'login', 'logout' => 'muted',
            default => 'info',
        };
    }
}
