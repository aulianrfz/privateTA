@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Verifikasi Data untuk Penjadwalan</h2>

    <div class="card mt-4">
        <div class="card-body">
            <form action="{{ route('jadwal.create.step3') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label for="venue">Data Venue</label>
                    @if(\App\Models\Venue::count() > 0)
                        <select name="venue" id="venue" class="form-control">
                            @foreach(\App\Models\Venue::all() as $venue)
                                <option value="{{ $venue->id }}">{{ $venue->name }}</option>
                            @endforeach
                        </select>
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" value="" checked disabled>
                            <label class="form-check-label">✔ Data venue tersedia</label>
                        </div>
                    @else
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="" disabled>
                            <label class="form-check-label text-danger">✘ Data venue belum tersedia</label>
                        </div>
                    @endif
                </div>

                <div class="mb-3">
                    <label for="juri">Data Juri</label>
                    @if(\App\Models\Juri::count() > 0)
                        <select name="juri" id="juri" class="form-control">
                            @foreach(\App\Models\Juri::all() as $juri)
                                <option value="{{ $juri->id }}">{{ $juri->nama }}</option>
                            @endforeach
                        </select>
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" value="" checked disabled>
                            <label class="form-check-label">✔ Data juri tersedia</label>
                        </div>
                    @else
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="" disabled>
                            <label class="form-check-label text-danger">✘ Data juri belum tersedia</label>
                        </div>
                    @endif
                </div>

                <div class="mb-3">
                    <label for="kategori_lomba">Data Kategori Lomba</label>
                    @if(\App\Models\SubKategori::count() > 0)
                        <select name="kategori_lomba" id="kategori_lomba" class="form-control">
                            @foreach(\App\Models\SubKategori::all() as $kategori)
                                <option value="{{ $kategori->id }}">{{ $kategori->name_lomba }}</option>
                            @endforeach
                        </select>
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" value="" checked disabled>
                            <label class="form-check-label">✔ Data kategori lomba tersedia</label>
                        </div>
                    @else
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="" disabled>
                            <label class="form-check-label text-danger">✘ Data kategori lomba belum tersedia</label>
                        </div>
                    @endif
                </div>

                <div class="mb-3">
                    <label for="peserta">Data Peserta</label>
                    @if(\App\Models\Peserta::count() > 0)
                        <select name="peserta" id="peserta" class="form-control">
                            @foreach(\App\Models\Peserta::all() as $peserta)
                                <option value="{{ $peserta->id }}">{{ $peserta->nama }} ({{ $peserta->nim }})</option>
                            @endforeach
                        </select>
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" value="" checked disabled>
                            <label class="form-check-label">✔ Data peserta tersedia</label>
                        </div>
                    @else
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="" disabled>
                            <label class="form-check-label text-danger">✘ Data peserta belum tersedia</label>
                        </div>
                    @endif
                </div>

                <button type="submit" class="btn btn-primary mt-3" {{ 
                    (\App\Models\Venue::count() == 0 || 
                    \App\Models\Juri::count() == 0 || 
                    \App\Models\SubKategori::count() == 0 || 
                    \App\Models\Peserta::count() == 0) ? 'disabled' : '' }}>
                    Lanjut ke Penjadwalan
                </button>

                @if(\App\Models\Venue::count() == 0 || 
                    \App\Models\Juri::count() == 0 || 
                    \App\Models\SubKategori::count() == 0 || 
                    \App\Models\Peserta::count() == 0)
                    <p class="text-danger mt-2">Lengkapi semua data sebelum melanjutkan ke penjadwalan</p>
                @endif

            </form>
        </div>
    </div>
</div>
@endsection