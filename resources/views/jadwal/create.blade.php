<!-- resources/views/jadwal/create.blade.php -->
<!-- @extends('layouts.app')

@section('content') -->
<div class="container">
    <h2>Buat Jadwal Baru - Step 1</h2>

    <form action="{{ route('jadwal.create.step2') }}" method="POST">
        @csrf

        <div class="form-group"> 
            <label for="nama_jadwal">Nama Jadwal</label> 
            <input type="text" name="nama_jadwal" id="nama_jadwal" class="form-control" required> 
        </div>

        <div class="form-group">
            <label for="tanggal">Tanggal Jadwal</label>
            <input type="date" name="tanggal" id="tanggal" class="form-control" required>
        </div>

        <div class="form-group">
            <label for="waktu_mulai">Waktu Mulai</label>
            <input type="time" name="waktu_mulai" id="waktu_mulai" class="form-control" required>
        </div>

        <div class="form-group">
            <label for="waktu_selesai">Waktu Selesai</label>
            <input type="time" name="waktu_selesai" id="waktu_selesai" class="form-control" required>
        </div>

        <div class="d-flex justify-content-between">
            <a href="{{ route('jadwal.index') }}" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Next</button>
        </div>
    </form>
</div>
<!-- @endsection -->
