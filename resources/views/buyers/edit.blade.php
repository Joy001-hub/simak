@extends('layouts.app')

@section('content')
<h1 class="heading-title">Edit Buyer</h1>
<form action="{{ route('buyers.update', $buyer) }}" method="POST" class="card" style="max-width:600px; gap:10px;">
    @csrf
    @method('PUT')
    <div class="field">
        <label class="hint">Nama</label>
        <input class="input" type="text" name="name" value="{{ $buyer->name }}" required>
    </div>
    <div class="field">
        <label class="hint">Telepon</label>
        <input class="input" type="text" name="phone" value="{{ $buyer->phone }}">
    </div>
    <div class="field">
        <label class="hint">Email</label>
        <input class="input" type="email" name="email" value="{{ $buyer->email }}">
    </div>
    <div class="field">
        <label class="hint">Alamat</label>
        <textarea class="input" name="address" rows="2">{{ $buyer->address }}</textarea>
    </div>
    <div style="display:flex; gap:10px; justify-content:flex-end;">
        <a class="btn light" href="{{ route('buyers.index') }}">Batal</a>
        <button class="btn primary" type="submit">Simpan Perubahan</button>
    </div>
</form>
@endsection
