@extends('layouts.app')

@php $editing = $customer->exists; @endphp
@section('title', $editing ? 'Ubah Pelanggan' : 'Pelanggan Baru')

@section('content')
    <header>
        <a href="{{ $editing ? route('customers.show', $customer) : route('customers.index') }}" class="back">@include('partials.icon', ['name' => 'chevron-left', 'size' => 16, 'stroke' => 2])Kembali</a>
        <h1 class="page-title" style="margin-top: 8px">{{ $editing ? 'Ubah '.$customer->name : 'Pelanggan Baru' }}</h1>
    </header>

    <form method="post" action="{{ $editing ? route('customers.update', $customer) : route('customers.store') }}" class="card form" style="max-width: 760px" novalidate>
        @csrf
        @if ($editing) @method('PUT') @endif
        <div class="row-2">
            <div class="field">
                <label for="name">Nama pelanggan / instansi</label>
                <input id="name" name="name" class="input @error('name') is-invalid @enderror" value="{{ old('name', $customer->name) }}" required autofocus>
                @error('name')<div class="error">{{ $message }}</div>@enderror
            </div>
            <div class="field">
                <label for="type">Jenis</label>
                <select id="type" name="type" class="select">
                    @foreach (\App\Models\Customer::TYPES as $k => $l)<option value="{{ $k }}" @selected(old('type', $customer->type) === $k)>{{ $l }}</option>@endforeach
                </select>
            </div>
        </div>
        <div class="row-3">
            <div class="field"><label for="contact_person">Nama kontak</label><input id="contact_person" name="contact_person" class="input" value="{{ old('contact_person', $customer->contact_person) }}" placeholder="Untuk instansi"></div>
            <div class="field"><label for="phone">No. WhatsApp</label><input id="phone" name="phone" class="input" inputmode="tel" value="{{ old('phone', $customer->phone) }}" placeholder="08xx-xxxx-xxxx"></div>
            <div class="field"><label for="email">Email</label><input id="email" name="email" type="email" class="input @error('email') is-invalid @enderror" value="{{ old('email', $customer->email) }}">@error('email')<div class="error">{{ $message }}</div>@enderror</div>
        </div>
        <div class="field"><label for="address">Alamat</label><textarea id="address" name="address" rows="2" class="textarea">{{ old('address', $customer->address) }}</textarea></div>
        <div class="field"><label for="notes">Catatan</label><textarea id="notes" name="notes" rows="3" class="textarea" placeholder="Contoh: selalu minta laminasi doff, logo versi 2026">{{ old('notes', $customer->notes) }}</textarea></div>
        <div class="form-actions">
            <a href="{{ $editing ? route('customers.show', $customer) : route('customers.index') }}" class="btn btn-ghost btn-lg">Batal</a>
            <button type="submit" class="btn btn-primary btn-lg">Simpan</button>
        </div>
    </form>
@endsection
