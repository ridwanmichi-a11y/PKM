<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index()
    {
        return view('users.index', ['users' => User::orderByRaw("CASE role WHEN 'owner' THEN 0 WHEN 'admin' THEN 1 ELSE 2 END")->orderBy('name')->get()]);
    }

    public function create()
    {
        return view('users.form', ['user' => new User(['role' => 'admin', 'is_active' => true])]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request, null);
        $user = User::create($data);
        Audit::log('create', $user, 'Membuat akun '.$user->username.' ('.$user->roleLabel().')');

        return redirect()->route('users.index')->with('success', 'Akun '.$user->name.' dibuat. Berikan username dan kata sandinya kepada yang bersangkutan.');
    }

    public function edit(User $user)
    {
        return view('users.form', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $data = $this->validated($request, $user);

        if ($user->id === $request->user()->id && ($data['role'] !== 'owner' || ! $data['is_active'])) {
            return back()->withInput()->withErrors(['role' => 'Anda tidak bisa menurunkan peran atau menonaktifkan akun sendiri.']);
        }

        $before = $user->only(['name', 'username', 'role', 'email', 'phone', 'is_active']);
        if (empty($data['password'])) {
            unset($data['password']);
        }
        $user->update($data);
        $changes = Audit::diff($before, $user->only(array_keys($before)));
        if (isset($data['password'])) {
            $changes['password'] = ['dari' => '•••', 'ke' => 'diatur ulang'];
        }
        Audit::log('update', $user, 'Mengubah akun '.$user->username, $changes);

        return redirect()->route('users.index')->with('success', 'Akun '.$user->name.' disimpan.');
    }

    private function validated(Request $request, ?User $user): array
    {
        $request->merge(['username' => strtolower(trim((string) $request->input('username')))]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'username' => ['required', 'alpha_dash', 'max:50', Rule::unique('users', 'username')->ignore($user?->id)],
            'role' => ['required', Rule::in(array_keys(User::ROLES))],
            'email' => ['nullable', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user?->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => [$user ? 'nullable' : 'required', 'confirmed', Password::min(8)],
        ], [
            'username.unique' => 'Username sudah dipakai.',
            'username.alpha_dash' => 'Username hanya boleh huruf, angka, strip, dan garis bawah.',
            'password.required' => 'Kata sandi wajib diisi.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak sama.',
            'password.min' => 'Kata sandi minimal 8 karakter.',
            'email.unique' => 'Email sudah dipakai.',
        ]);
        $data['username'] = strtolower($data['username']);
        $data['email'] = ($data['email'] ?? null) ?: $data['username'].'@sipzstore.local';
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
