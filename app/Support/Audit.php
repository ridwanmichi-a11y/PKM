<?php

namespace App\Support;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

/**
 * Pencatat jejak audit: siapa melakukan apa, kapan, terhadap data apa.
 */
class Audit
{
    public static function log(string $action, ?Model $subject, string $description, ?array $changes = null, ?int $userId = null): void
    {
        AuditLog::create([
            'user_id' => $userId ?? auth()->id(),
            'action' => $action,
            'subject_type' => $subject ? class_basename($subject) : null,
            'subject_id' => $subject?->getKey(),
            'subject_label' => $subject ? static::label($subject) : null,
            'description' => $description,
            'changes' => $changes,
            'ip_address' => request()?->ip(),
            'created_at' => Fmt::now(),
        ]);
    }

    /** Selisih nilai lama dan baru, hanya kolom yang berubah. */
    public static function diff(array $old, array $new): array
    {
        $out = [];
        foreach ($new as $key => $value) {
            $before = $old[$key] ?? null;
            if ((string) $before !== (string) $value) {
                $out[$key] = ['dari' => $before, 'ke' => $value];
            }
        }

        return $out;
    }

    private static function label(Model $m): ?string
    {
        return $m->code ?? $m->username ?? $m->name ?? null;
    }
}
