@extends('layouts.apk')

@section('content')
    {{-- Blok CSS dipindahkan ke sini dari @push('styles') --}}
    <style>
        .title-rundown {
            color: #3A3B7B;
            /* Warna biru tua/ungu untuk judul "Change Rundown" */
        }

        .btn-search-custom-icon {
            /* Style untuk tombol search icon agar sesuai gambar */
            background-color: #20c997;
            /* Warna teal/hijau untuk background tombol search */
            border-color: #20c997;
            /* Border dengan warna yang sama */
            color: white;
            /* Warna ikon putih */
        }

        .btn-search-custom-icon:hover {
            background-color: #1aa883;
            /* Warna hover lebih gelap */
            border-color: #1aa883;
            color: white;
        }

        /* Mengatur agar input dan tombol search menyatu dengan baik */
        .input-group>.form-control:not(:last-child) {
            /* Untuk input field */
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
        }

        .input-group>.btn-search-custom-icon {
            /* Untuk tombol search */
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
        }

        .table-header-dark-custom {
            /* Header tabel gelap - latar belakang */
            background-color: #000000 !important;
            /* Warna hitam murni (#000000) */
        }

        .table-header-dark-custom th {
            /* Header tabel gelap - teks di dalam sel header */
            font-weight: 600;
            /* Membuat teks header sedikit lebih tebal */
            text-align: center;
            /* Membuat teks header rata tengah */
        }

        /* Penyesuaian Tombol Aksi agar sesuai image_d09d74.png (latar belakang solid) */
        .btn-action-edit-custom {
            background-color: #0d6efd;
            /* Warna biru Bootstrap */
            color: white;
            /* Ikon putih */
            border: 1px solid #0d6efd;
            /* Border dengan warna yang sama */
            padding: 0.2rem 0.4rem;
            /* Padding disesuaikan */
            line-height: 1;
            /* Menyesuaikan tinggi baris ikon */
            border-radius: .2rem;
            /* Sedikit rounded corner */
        }

        .btn-action-edit-custom:hover {
            background-color: #0b5ed7;
            /* Warna biru lebih gelap saat hover */
            border-color: #0a58ca;
            color: white;
        }

        .btn-action-delete-custom {
            background-color: #dc3545;
            /* Warna merah Bootstrap */
            color: white;
            /* Ikon putih */
            border: 1px solid #dc3545;
            /* Border dengan warna yang sama */
            padding: 0.2rem 0.4rem;
            /* Padding disesuaikan */
            line-height: 1;
            border-radius: .2rem;
        }

        .btn-action-delete-custom:hover {
            background-color: #bb2d3b;
            /* Warna merah lebih gelap saat hover */
            border-color: #b02a37;
            color: white;
        }

        /* Custom styles for pagination */
        .pagination {
            justify-content: center;
            /* Center pagination links */
            margin-top: 1.5rem;
            /* Add some margin above pagination */
        }

        .pagination .page-item.active .page-link {
            background-color: #3A3B7B;
            /* Warna pagination aktif sesuai title-rundown */
            border-color: #3A3B7B;
        }

        .pagination .page-link {
            color: #3A3B7B;
            /* Warna link pagination */
        }

        .pagination .page-link:hover {
            color: #2c2d5c;
            /* Warna hover link pagination */
        }

        /* Rata tengah untuk konten tabel di tbody */
        .table-hover tbody td {
            text-align: center;
            vertical-align: middle;
            /* Untuk alignment vertikal jika konten berbeda tinggi */
        }
    </style>

    <div class="container"> {{-- Container utama --}}

        {{-- Baris Atas: Judul Utama, Pencarian, Filter, Tombol Change --}}
        <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
            {{-- Kelompokkan tombol kembali dan judul --}}
            <div class="d-flex align-items-center">
                <a href="{{ url()->previous() }}" class="btn btn-sm btn-outline-secondary me-2" title="Kembali">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <h1 class="h2 mb-0">Penjadwalan</h1> {{-- mb-0 untuk alignment vertikal dengan tombol --}}
            </div>
            <div class="d-flex align-items-center">
                {{-- Formulir Pencarian --}}
                <form class="d-flex me-2" role="search" method="GET" action="{{-- URL untuk aksi pencarian --}}">
                    <div class="input-group input-group-sm">
                        <input type="text" name="search_query" class="form-control" placeholder="Cari Sesuatu Disini..."
                            aria-label="Cari sub kategori" style="width: 200px;" value="{{ request('search_query') }}">
                        <button class="btn btn-search-custom-icon" type="submit" id="button-search"><i
                                class="fas fa-search"></i></button>
                    </div>
                </form>

                {{-- Dropdown Filter --}}
                <div class="dropdown me-2">
                    <button class="btn btn-sm btn-light border dropdown-toggle" type="button" id="filterDropdown"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        Filter by
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="filterDropdown">
                        <li><a class="dropdown-item" href="{{-- URL filter by Kategori --}}">Kategori</a></li>
                        <li><a class="dropdown-item" href="{{-- URL filter by Venue --}}">Venue</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item" href="{{-- URL clear filter --}}">Clear Filter</a></li>
                    </ul>
                </div>

                {{-- Tombol Change menjadi Dropdown --}}
                <div class="dropdown">
                    <button class="btn btn-sm btn-light border dropdown-toggle" type="button" id="changeDropdown"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        Change
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="changeDropdown">
                        <li>
                            <a class="dropdown-item"
                                href="{{ route('jadwal.switch', ['nama_jadwal' => $nama_jadwal, 'tahun' => $tahun, 'version' => $version]) }}">
                                Tukar
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item"
                                href="{{ route('jadwal.create.withDetail', [$nama_jadwal, $tahun, $version]) }}">
                                Tambah
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- Judul Konten: "Change Rundown - ..." --}}
        <div class="d-flex align-items-center mb-3">
            <h3 class="mb-0 title-rundown">Change Rundown - {{ $nama_jadwal }} <small class="text-muted">({{ $tahun }} -
                    Versi {{ $version }})</small></h3>
        </div>

        {{-- Card untuk tabel --}}
        <div class="card shadow-sm">
            <div class="card-body p-0"> {{-- Hapus padding default card-body jika tabel responsif --}}
                @if($jadwals->isEmpty() && !($jadwals instanceof \Illuminate\Pagination\AbstractPaginator)) {{-- Periksa
                    isEmpty HANYA jika bukan Paginator, karena Paginator punya method isEmpty sendiri --}}
                    <div class="alert alert-warning mb-0 mx-3 my-3">Tidak ada data untuk jadwal ini.</div> {{-- Beri margin jika
                    kosong --}}
                @elseif($jadwals instanceof \Illuminate\Pagination\AbstractPaginator && $jadwals->isEmpty()) {{-- Jika
                    Paginator dan kosong --}}
                    <div class="alert alert-warning mb-0 mx-3 my-3">Tidak ada data untuk jadwal ini.</div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0"> {{-- Hapus margin bawah tabel --}}
                            <thead class="table-header-dark-custom">
                                <tr>
                                    <th scope="col" style="width: 50px;" class="ps-3">No</th> {{-- Tambah padding start --}}
                                    <!-- <th scope="col" style="width: 10%;">Durasi</th> -->
                                    <th scope="col" style="width: 15%;">Tanggal</th>
                                    <th scope="col" style="width: 15%;">Waktu</th>
                                    <th scope="col">Kategori Lomba</th>
                                    <!-- <th scope="col">Kegiatan</th> -->
                                    <th scope="col">Venue</th>
                                    <th scope="col">Kegiatan</th>
                                    <th>Peserta/Tim</th>
                                    <th>Juri</th>
                                    <th scope="col" style="width: 100px;" class="text-center pe-3">Aksi</th> {{-- Tambah padding
                                    end --}}
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($jadwals as $jadwal)
                                    <tr>
                                        {{-- Penyesuaian nomor urut jika menggunakan pagination --}}
                                        @if ($jadwals instanceof \Illuminate\Pagination\AbstractPaginator)
                                            <td>{{ ($jadwals->currentPage() - 1) * $jadwals->perPage() + $loop->iteration }}</td>
                                        @else
                                            <td>{{ $loop->iteration }}</td>
                                        @endif
                                        <!-- <td>
                                                                @if(isset($jadwal->durasi))
                                                                    '{{ $jadwal->durasi }}
                                                                @else
                                                                    -
                                                                @endif
                                                            </td> -->
                                        <td>{{ $jadwal->tanggal ?? '-' }}</td>
                                        <td>
                                            {{ \Carbon\Carbon::parse($jadwal->waktu_mulai)->format('H.i') }} -
                                            {{ \Carbon\Carbon::parse($jadwal->waktu_selesai)->format('H.i') }}
                                        </td>
                                        <td>{{ $jadwal->mataLomba->nama_lomba ?? '-' }}</td>
                                        <!-- <td>{{ $jadwal->kegiatan ?? 'Data Kegiatan Belum Ada' }}</td> -->
                                        <td>{{ $jadwal->venue->name ?? '-' }}</td>
                                        <td>{{ $jadwal->kegiatan ?? '-' }}</td>
                                        <td>
                                            @php
                                                $adaPeserta = $jadwal->peserta && $jadwal->peserta->count();
                                                $adaTim = $jadwal->tim && $jadwal->tim->count();
                                            @endphp

                                            @if ($adaPeserta)
                                                @foreach ($jadwal->peserta as $peserta)
                                                    {{ $peserta->nama_peserta }}<br>
                                                @endforeach
                                            @endif

                                            @if ($adaTim)
                                                @foreach ($jadwal->tim as $tim)
                                                    {{ $tim->nama_tim }}<br>
                                                @endforeach
                                            @endif

                                            @if (!$adaPeserta && !$adaTim)
                                                -
                                            @endif
                                        </td>

                                        <td>{{ $jadwal->juri->nama_juri ?? '-' }}</td>
                                        <td class="text-center pe-3"> {{-- Tombol aksi sudah rata tengah --}}
                                            <a href="{{ route('jadwal.edit', $jadwal->id) }}"
                                                class="btn btn-sm btn-action-edit-custom me-1" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-action-delete-custom" title="Delete"
                                                data-bs-toggle="modal" data-bs-target="#deleteModal"
                                                data-action="{{ route('jadwal.destroy', $jadwal->id) }}"
                                                data-item-name="{{ $jadwal->kegiatan ?? ($jadwal->mataLomba->nama_lomba ?? 'item ini') }}">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Pagination Links dengan Pengecekan Tipe Objek --}}
                    @if ($jadwals instanceof \Illuminate\Pagination\AbstractPaginator && $jadwals->hasPages())
                        <div class="card-footer bg-white py-3">
                            {{ $jadwals->appends(request()->except('page'))->links() }} {{-- appends untuk mempertahankan query
                            string lain --}}
                        </div>
                    @endif

                @endif
            </div>
        </div>
    </div> {{-- Akhir container --}}

    {{-- Modal Konfirmasi Delete --}}
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="deleteForm" method="POST">
                    @csrf
                    @method('DELETE')
                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteModalLabel">Konfirmasi Hapus</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        Apakah Anda yakin ingin menghapus jadwal <strong id="itemNameModal"></strong>?
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger">Hapus</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        const deleteModalElement = document.getElementById('deleteModal');
        if (deleteModalElement) {
            deleteModalElement.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const action = button.getAttribute('data-action');
                const itemName = button.getAttribute('data-item-name');

                const form = document.getElementById('deleteForm');
                form.action = action;

                const itemNameElement = document.getElementById('itemNameModal');
                if (itemNameElement && itemName) {
                    itemNameElement.textContent = 'untuk "' + itemName + '"';
                } else if (itemNameElement) {
                    itemNameElement.textContent = 'item ini';
                }
            });
        }
    </script>

@endsection