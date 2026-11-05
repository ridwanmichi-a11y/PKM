<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Format angka, rupiah, tanggal berbahasa Indonesia.
 */
class Fmt
{
    public static function rp($amount, bool $prefix = true): string
    {
        $n = number_format((float) $amount, 0, ',', '.');

        return $prefix ? 'Rp '.$n : $n;
    }

    /** Angka singkat untuk grafik: 11,3 (juta). */
    public static function juta($amount): string
    {
        return number_format($amount / 1000000, 1, ',', '.');
    }

    public static function num($n): string
    {
        return number_format((float) $n, 0, ',', '.');
    }

    public static function date($date, string $format = 'j M Y'): string
    {
        if (! $date) {
            return '–';
        }
        $d = $date instanceof CarbonInterface ? $date->copy() : Carbon::parse($date);

        return $d->locale('id')->translatedFormat($format);
    }

    public static function now(): Carbon
    {
        return Carbon::now(config('sipz.timezone'));
    }

    public static function today(): Carbon
    {
        return static::now()->startOfDay();
    }

    public static function greeting(): string
    {
        $h = static::now()->hour;

        return match (true) {
            $h < 11 => 'Selamat pagi',
            $h < 15 => 'Selamat siang',
            $h < 18 => 'Selamat sore',
            default => 'Selamat malam',
        };
    }

    /** "2026-10" → "Oktober 2026" */
    public static function month(string $ym): string
    {
        return static::date(Carbon::createFromFormat('Y-m-d', $ym.'-01'), 'F Y');
    }
}
