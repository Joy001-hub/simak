@extends('layouts.app')

@section('content')
<h1 class="heading-title">Tambah Kavling</h1>
<form action="{{ route('kavling.store') }}" method="POST" class="card" style="max-width:600px; gap:10px;">
    @csrf
    <div class="field">
        <label class="hint">Project</label>
        <select class="input" name="project_id" required>
            @foreach($projects as $project)
                <option value="{{ $project->id }}">{{ $project->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="field">
        <label class="hint">Blok/Number</label>
        <input class="input" type="text" name="block_number" required>
    </div>
    <div class="field">
        <label class="hint">Luas (m²)</label>
        <input class="input" type="number" name="area" min="0">
    </div>
    <div class="field">
        <label class="hint">Harga Dasar (Rp)</label>
        <input class="input" type="number" name="base_price" min="0">
    </div>
    <div class="field">
        <label class="hint">Status</label>
        <select class="input" name="status">
            <option value="available">Available</option>
            <option value="sold">Sold</option>
            <option value="reserved">Reserved</option>
            <option value="active">Active</option>
        </select>
    </div>
    <div style="display:flex; gap:10px; justify-content:flex-end;">
        <a class="btn light" href="{{ route('kavling.index') }}">Batal</a>
        <button class="btn primary" type="submit">Simpan</button>
    </div>
</form>
@endsection
