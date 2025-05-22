@extends('layouts.app')

@section('content')
    <div class="container">
        <h3 class="text-center font-weight-bold mb-4">Buat Jadwal</h3>

        <!-- Step Indicator -->
        <div class="d-flex justify-content-center mb-4">
            <div class="step active">1</div>
            <div class="line"></div>
            <div class="step active">2</div>
            <div class="line"></div>
            <div class="step active">3</div>
        </div>

        <div class="card p-4">
            <h5 class="mb-3 font-weight-bold">Constraint Tambahan</h5>

            <form action="{{ route('jadwal.store') }}" method="POST">
                @csrf

                @foreach ($subKategoriLomba as $lomba)
                    <div class="form-row align-items-center mb-3">
                        <div class="col-md-3">
                            <label class="mb-1">{{ $lomba['nama_sub_kategori'] }}</label>
                        </div>
                        <div class="col-md-3">
                            <select class="form-control" name="hari[{{ $lomba['sub_kategori_id'] }}]">
                                @foreach ($jadwalHarian as $index => $jadwal)
                                    <option value="{{ $jadwal['tanggal'] }}">
                                        {{ \Carbon\Carbon::parse($jadwal['tanggal'])->translatedFormat('l, d M Y') }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <input type="time" class="form-control" name="waktu_mulai[{{ $lomba['sub_kategori_id'] }}]"
                                placeholder="Waktu Mulai">
                        </div>
                        <div class="col-md-3">
                            <input type="time" class="form-control" name="waktu_selesai[{{ $lomba['sub_kategori_id'] }}]"
                                placeholder="Waktu Selesai">
                        </div>
                        <div class="col-md-3">
                            <input type="number" class="form-control" name="saving_time[{{ $lomba['sub_kategori_id'] }}]"
                                placeholder="Saving Time (menit)">
                        </div>
                    </div>
                @endforeach

                <div class="text-right mt-4">
                    <a href="{{ route('jadwal.create') }}" class="btn btn-danger">Batal</a>
                    <button type="submit" class="btn btn-success">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <style>
        .step {
            width: 30px;
            height: 30px;
            background-color: #007bff;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        .line {
            width: 60px;
            height: 4px;
            background-color: #007bff;
            margin: 0 10px;
            align-self: center;
        }
    </style>
@endsection