<!-- @extends('layouts.app')

@section('content') -->
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Daftar Jadwal</h1>
        <a href="{{ route('jadwal.create') }}" class="btn btn-primary">Create Jadwal</a>
    </div>

    @if($jadwals->isEmpty())
        <div class="alert alert-warning">Tidak ada jadwal ditemukan.</div>
    @else
        <table class="table table-bordered"> 
            <thead> 
            <tr> 
            <th>No</th>
            <th>Nama Jadwal</th> 
            <th>Tahun</th> 
            <th>Version</th> 
            <th>Action</th> 
            </tr> 
            </thead> 
            <tbody> 
            @foreach($jadwals as $index => $jadwal) 
            <tr> 
            <td>{{ $index + 1 }}</td> 
            <td>{{ $jadwal->nama_jadwal }}</td> 
            <td>{{ $jadwal->tahun }}</td> 
            <td>{{ $jadwal->version }}</td> 
            <td> <a href="{{ route('jadwal.detail', [$jadwal->nama_jadwal, $jadwal->tahun, $jadwal->version]) }}" class="btn btn-sm btn-info">Detail</a> </td> 
            </tr> 
            @endforeach 
            </tbody> 
            </table>
    @endif
</div>
<!-- @endsection -->
