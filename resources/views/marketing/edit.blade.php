@extends('layouts.app')

@section('content')
<h1 class="heading-title">Edit Salesman</h1>
<form action="{{ route('marketing.update', $marketer) }}" method="POST" class="card" style="max-width:500px; gap:10px;">
    @csrf
    @method('PUT')
    <div class="field">
        <label class="hint">Nama</label>
        <input class="input" type="text" name="name" value="{{ $marketer->name }}" required>
    </div>
    <div class="field">
        <label class="hint">Telepon</label>
        <input class="input" type="text" name="phone" value="{{ $marketer->phone }}">
    </div>
    <div style="display:flex; gap:10px; justify-content:flex-end;">
        <a class="btn light" href="{{ route('marketing.index') }}">Batal</a>
        <button class="btn primary" type="submit">Simpan Perubahan</button>
    </div>
</form>
@endsection
