@extends('layouts.app')

@section('content')
<h1 class="heading-title">Tambah Salesman</h1>
<form action="{{ route('marketing.store') }}" method="POST" class="card" style="max-width:500px; gap:10px;">
    @csrf
    <div class="field">
        <label class="hint">Nama</label>
        <input class="input" type="text" name="name" required>
    </div>
    <div class="field">
        <label class="hint">Telepon</label>
        <input class="input" type="text" name="phone">
    </div>
    <div style="display:flex; gap:10px; justify-content:flex-end;">
        <a class="btn light" href="{{ route('marketing.index') }}">Batal</a>
        <button class="btn primary" type="submit">Simpan</button>
    </div>
</form>
@endsection
