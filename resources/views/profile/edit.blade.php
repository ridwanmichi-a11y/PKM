@extends('layouts.app')
@section('title', 'Profil')

@section('content')
    <header>
        <div class="eyebrow">Akun Saya</div>
        <h1 class="page-title">{{ $user->name }}</h1>
        <p class="page-sub">{{ $user->roleLabel() }} · username <b>{{ $user->username }}</b></p>
    </header>

    <form method="post" action="{{ route('profile.update') }}" class="card form" style="max-width: 560px" novalidate>
        @csrf @method('PUT')
        <h2 class="card-title sm">Ganti kata sandi</h2>
        <div class="field"><label for="current_password">Kata sandi saat ini</label><input id="current_password" name="current_password" type="password" class="input @error('current_password') is-invalid @enderror" autocomplete="current-password">@error('current_password')<div class="error">{{ $message }}</div>@enderror</div>
        <div class="field"><label for="password">Kata sandi baru</label><input id="password" name="password" type="password" class="input @error('password') is-invalid @enderror" autocomplete="new-password"><div class="hint">Minimal 8 karakter.</div>@error('password')<div class="error">{{ $message }}</div>@enderror</div>
        <div class="field"><label for="password_confirmation">Ulangi kata sandi baru</label><input id="password_confirmation" name="password_confirmation" type="password" class="input" autocomplete="new-password"></div>
        <div class="form-actions"><button type="submit" class="btn btn-primary btn-lg">Simpan</button></div>
    </form>

    <form method="post" action="{{ route('logout') }}" style="max-width: 560px">
        @csrf
        <button type="submit" class="btn btn-block">@include('partials.icon', ['name' => 'logout', 'size' => 17])Keluar dari aplikasi</button>
    </form>
@endsection
