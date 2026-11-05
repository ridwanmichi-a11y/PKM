<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    public const TYPES = ['perorangan' => 'Perorangan', 'instansi' => 'Instansi / Perusahaan'];

    protected $fillable = ['name', 'type', 'contact_person', 'phone', 'email', 'address', 'notes'];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function initials(): string
    {
        $words = preg_split('/\s+/', trim(preg_replace('/[^\pL\pN\s]/u', '', $this->name)));

        return mb_strtoupper(mb_substr($words[0] ?? '?', 0, 1).(isset($words[1]) ? mb_substr($words[1], 0, 1) : ''));
    }

    /** Tautan WhatsApp dari nomor HP Indonesia. */
    public function whatsappUrl(): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $this->phone);
        if (strlen($digits) < 9) {
            return null;
        }
        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        }

        return 'https://wa.me/'.$digits;
    }
}
