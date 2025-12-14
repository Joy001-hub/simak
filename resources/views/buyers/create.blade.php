@extends('layouts.app')

@section('content')
    <h1 class="heading-title">Tambah Buyer</h1>
    <form action="{{ route('buyers.store') }}" method="POST" class="card" style="max-width:600px; gap:10px;">
        @csrf
        @if(isset($fromSale))
            <input type="hidden" name="from_sale" value="{{ $fromSale }}">
        @endif
        <div class="field">
            <label class="hint">Nama</label>
            <input class="input" type="text" name="name" required>
        </div>
        <div class="field">
            <label class="hint">Telepon</label>
            <input class="input" type="text" name="phone">
        </div>
        <div class="field">
            <label class="hint">Email</label>
            <input class="input" type="email" name="email">
        </div>
        <div class="field">
            <label class="hint">Alamat</label>
            <textarea class="input" name="address" rows="2"></textarea>
        </div>
        <div style="display:flex; gap:10px; justify-content:flex-end;">
            <a class="btn light" href="{{ route('buyers.index') }}">Batal</a>
            <button class="btn primary" type="submit">Simpan</button>
        </div>
    </form>
@endsection