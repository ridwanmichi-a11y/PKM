<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Support\Audit;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    private const KEYS = ['store_name', 'store_tagline', 'store_address', 'store_phone', 'daily_capacity', 'approval_limit', 'closed_on_sunday'];

    public function edit()
    {
        $values = [];
        foreach (self::KEYS as $k) {
            $values[$k] = Setting::get($k);
        }

        return view('settings.edit', ['s' => $values]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'store_name' => ['required', 'string', 'max:100'],
            'store_tagline' => ['nullable', 'string', 'max:150'],
            'store_address' => ['nullable', 'string', 'max:255'],
            'store_phone' => ['nullable', 'string', 'max:30'],
            'daily_capacity' => ['required', 'integer', 'min:1', 'max:100000'],
            'approval_limit' => ['required'],
        ], [
            'daily_capacity.required' => 'Kapasitas harian wajib diisi.',
            'daily_capacity.integer' => 'Kapasitas harus berupa angka.',
        ]);
        $data['approval_limit'] = (int) preg_replace('/\D/', '', $data['approval_limit']);
        $data['closed_on_sunday'] = $request->boolean('closed_on_sunday') ? 1 : 0;

        $before = [];
        foreach (self::KEYS as $k) {
            $before[$k] = Setting::get($k);
        }
        Setting::put($data);
        Audit::log('settings', null, 'Mengubah pengaturan toko', Audit::diff($before, $data));

        return back()->with('success', 'Pengaturan disimpan.');
    }
}
