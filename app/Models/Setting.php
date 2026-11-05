<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Pengaturan toko yang bisa diubah Owner dari aplikasi.
 * Nilai bawaan ada di config/sipz.php.
 */
class Setting extends Model
{
    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];

    public static function get(string $key, $default = null)
    {
        $all = Cache::rememberForever('sipz.settings', function () {
            try {
                return Schema::hasTable('settings') ? static::query()->pluck('value', 'key')->all() : [];
            } catch (\Throwable) {
                return [];
            }
        });

        return $all[$key] ?? config('sipz.defaults.'.$key, $default);
    }

    public static function put(array $values): void
    {
        foreach ($values as $key => $value) {
            static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        }
        Cache::forget('sipz.settings');
    }
}
