@extends('layouts.app')

@section('content')
<h1 class="heading-title">Edit Project</h1>
<form action="{{ route('projects.update', $project) }}" method="POST" class="card" style="max-width:600px; gap:10px;">
    @csrf
    @method('PUT')
    <div class="field">
        <label class="hint">Nama Project</label>
        <input class="input" type="text" name="name" value="{{ old('name', $project->name) }}" required>
    </div>
    <div class="field">
        <label class="hint">Lokasi</label>
        <input class="input" type="text" name="location" value="{{ old('location', $project->location) }}">
    </div>
    <div class="field">
        <label class="hint">Catatan</label>
        <textarea class="input" name="notes" rows="2">{{ old('notes', $project->notes) }}</textarea>
    </div>
    <div class="field">
        <label class="hint">Total Unit</label>
        <input class="input" type="number" name="total_units" min="0" value="{{ old('total_units', $project->total_units) }}">
    </div>
    <div style="display:flex; gap:10px; justify-content:flex-end;">
        <a class="btn light" href="{{ route('projects.index') }}">Batal</a>
        <button class="btn primary" type="submit">Simpan</button>
    </div>
</form>
@endsection
