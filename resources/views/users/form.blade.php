@extends('layouts.app')

@php $editing = $user->exists; @endphp
@section('title', $editing ? 'Ubah Pengguna' : 'Pengguna Baru')

@section('content')
    <header>
        <a href="{{ route('users.index') }}" class="back">@include('partials.icon', ['name' => 'chevron-left', 'size' => 16, 'stroke' => 2])Pengguna</a>
        <h1 class="page-title" style="margin-top: 8px">{{ $editing ? 'Ubah Akun '.$user->name : 'Pengguna Baru' }}</h1>
    </header>

    <form method="post" action="{{ $editing ? route('users.update', $user) : route('users.store') }}" class="card form" style="max-width: 760px" novalidate>
        @csrf
        @if ($editing) @method('PUT') @endif
        <div class="row-2">
            <div class="field"><label for="name">Nama</label><input id="name" name="name" class="input @error('name') is-invalid @enderror" value="{{ old('name', $user->name) }}" required></div>
            <div class="field"><label for="username">Username (untuk login)</label><input id="username" name="username" class="input @error('username') is-invalid @enderror" value="{{ old('username', $user->username) }}" autocapitalize="none" required>@error('username')<div class="error">{{ $message }}</div>@enderror</div>
        </div>
        <fieldset>
            <legend class="label">Peran</legend>
            <div class="radio-pills">
                @foreach (\App\Models\User::ROLES as $k => $l)
                    <label><input type="radio" name="role" value="{{ $k }}" @checked(old('role', $user->role) === $k)><span>{{ $l }}</span></label>
                @endforeach
            </div>
            @error('role')<div class="error">{{ $message }}</div>@enderror
        </fieldset>
        <div class="row-2">
            <div class="field"><label for="email">Email (opsional)</label><input id="email" name="email" type="email" class="input @error('email') is-invalid @enderror" value="{{ old('email', str_ends_with((string) $user->email, '@sipzstore.local') ? '' : $user->email) }}">@error('email')<div class="error">{{ $message }}</div>@enderror</div>
            <div class="field"><label for="phone">No. HP</label><input id="phone" name="phone" class="input" value="{{ old('phone', $user->phone) }}"></div>
        </div>
        <div class="row-2">
            <div class="field"><label for="password">{{ $editing ? 'Kata sandi baru (kosongkan jika tetap)' : 'Kata sandi' }}</label><input id="password" name="password" type="password" class="input @error('password') is-invalid @enderror" autocomplete="new-password">@error('password')<div class="error">{{ $message }}</div>@enderror</div>
            <div class="field"><label for="password_confirmation">Ulangi kata sandi</label><input id="password_confirmation" name="password_confirmation" type="password" class="input" autocomplete="new-password"></div>
        </div>
        <label class="check"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $user->is_active ?? true))>Akun aktif (bisa login)</label>
        <div class="form-actions">
            <a href="{{ route('users.index') }}" class="btn btn-ghost btn-lg">Batal</a>
            <button type="submit" class="btn btn-primary btn-lg">Simpan</button>
        </div>
    </form>
@endsection
