@extends('layouts.app')

@section('content')

<div class="container"> 
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Detail Jadwal: {{ $nama_jadwal }} - {{ $tahun }} - Versi {{ $version }}</h2>
        <a href="{{ route('jadwal.switch', ['nama_jadwal' => $nama_jadwal, 'tahun' => $tahun, 'version' => $version]) }}" class="btn btn-warning">
            Switch Jadwal
        </a>
        <a href="{{ route('jadwal.create.withDetail', [$nama_jadwal, $tahun, $version]) }}" class="btn btn-success mb-3">Add Jadwal</a>
    </div>

@if($jadwals->isEmpty())
    <div class="alert alert-warning">Tidak ada data untuk jadwal ini.</div>
@else
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Sub Kategori</th>
                <th>Waktu Mulai</th>
                <th>Waktu Selesai</th>
                <th>Venue</th>
                <th>Peserta</th>
                <th>Juri</th>
                <th>Action</th> {{-- Tambahkan kolom action --}}
            </tr>
        </thead>
        <tbody>
            @foreach($jadwals as $jadwal)
                <tr>
                    <td>{{ $jadwal->subKategori->name_lomba ?? '-' }}</td>
                    <td>{{ $jadwal->waktu_mulai }}</td>
                    <td>{{ $jadwal->waktu_selesai }}</td>
                    <td>{{ $jadwal->venue->name ?? '-' }}</td>
                    <td>{{ $jadwal->peserta->nama ?? '-' }}</td>
                    <td>{{ $jadwal->juri->nama ?? '-' }}</td>
                    <td>
                        <a href="{{ route('jadwal.edit', $jadwal->id) }}" class="btn btn-sm btn-primary">Edit</a>

                        <form action="{{ route('jadwal.destroy', $jadwal->id) }}" method="POST" style="display:inline-block;" onsubmit="return confirm('Yakin ingin menghapus jadwal ini?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

</div>
@endsection
