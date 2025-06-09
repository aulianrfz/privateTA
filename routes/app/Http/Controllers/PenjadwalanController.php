<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\KategoriLomba;
use App\Models\Venue;
use App\Models\Tim;
use App\Models\Peserta;
use App\Models\Jadwal;
use App\Models\SubKategori;
use App\Models\Juri;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Jobs\ProsesPenjadwalanJob;



class PenjadwalanController extends Controller
{

    // public function index()
    // {
    //     $jadwals = Jadwal::with(['subKategori', 'venue', 'peserta', 'juri'])->get();
    //     return view('jadwal.index', compact('jadwals'));
    // }

    public function index()
    {
        $jadwals = DB::table('jadwal')
            ->select('nama_jadwal', 'tahun', 'version')
            ->distinct()
            ->get();
        return view('jadwal.index', compact('jadwals'));
    }

    public function detail($nama_jadwal, $tahun, $version)
    {
        $jadwals = Jadwal::with(['subKategori', 'venue', 'peserta', 'juri', 'tim'])
            ->where('nama_jadwal', $nama_jadwal)
            ->where('tahun', $tahun)
            ->where('version', $version)
            ->get();
        return view('jadwal.detail', compact('jadwals', 'nama_jadwal', 'tahun', 'version'));
    }

    public function create()
    {
        return view('jadwal.create'); // buat view ini
    }

    public function edit($id)
    {
        $jadwal = Jadwal::findOrFail($id);
        $sub_kategori = SubKategori::all();
        $venue = Venue::all();
        $peserta = Peserta::all();
        $juri = Juri::all();

        return view('jadwal.edit', compact('jadwal', 'sub_kategori', 'venue', 'peserta', 'juri'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'sub_kategori_id' => 'required|exists:sub_kategori,id',
            'waktu_mulai' => 'required|date_format:H:i',
            'waktu_selesai' => 'required|date_format:H:i|after:waktu_mulai',
            'venue_id' => 'required|exists:venue,id',
            'peserta_id' => 'required|exists:peserta,id',
            'juri_id' => 'required|exists:juri,id',
        ]);

        $force = $request->input('force');
        $jadwal = Jadwal::findOrFail($id);
        $waktuMulai = $request->waktu_mulai;
        $waktuSelesai = $request->waktu_selesai;

        $bentrokJadwal = Jadwal::where('venue_id', $request->venue_id)
            ->where('id', '!=', $id)
            ->where(function ($query) use ($waktuMulai, $waktuSelesai) {
                $query->whereBetween('waktu_mulai', [$waktuMulai, $waktuSelesai])
                    ->orWhereBetween('waktu_selesai', [$waktuMulai, $waktuSelesai])
                    ->orWhere(function ($q) use ($waktuMulai, $waktuSelesai) {
                        $q->where('waktu_mulai', '<=', $waktuMulai)
                            ->where('waktu_selesai', '>=', $waktuSelesai);
                    });
            })
            ->exists();

        $bentrokPeserta = Jadwal::where('peserta_id', $request->peserta_id)
            ->where('id', '!=', $id)
            ->where(function ($query) use ($waktuMulai, $waktuSelesai) {
                $query->whereBetween('waktu_mulai', [$waktuMulai, $waktuSelesai])
                    ->orWhereBetween('waktu_selesai', [$waktuMulai, $waktuSelesai])
                    ->orWhere(function ($q) use ($waktuMulai, $waktuSelesai) {
                        $q->where('waktu_mulai', '<=', $waktuMulai)
                            ->where('waktu_selesai', '>=', $waktuSelesai);
                    });
            })
            ->exists();

        $bentrokJuri = Jadwal::where('juri_id', $request->juri_id)
            ->where('id', '!=', $id)
            ->where(function ($query) use ($waktuMulai, $waktuSelesai) {
                $query->whereBetween('waktu_mulai', [$waktuMulai, $waktuSelesai])
                    ->orWhereBetween('waktu_selesai', [$waktuMulai, $waktuSelesai])
                    ->orWhere(function ($q) use ($waktuMulai, $waktuSelesai) {
                        $q->where('waktu_mulai', '<=', $waktuMulai)
                            ->where('waktu_selesai', '>=', $waktuSelesai);
                    });
            })
            ->exists();

        if (($bentrokJadwal || $bentrokPeserta || $bentrokJuri) && !$force) {
            return back()->withInput()->with('error_force', 'Terjadi bentrok jadwal. Apakah Anda yakin ingin melanjutkan perubahan?');
        }

        // Update data
        $jadwal->update([
            'sub_kategori_id' => $request->sub_kategori_id,
            'waktu_mulai' => $waktuMulai,
            'waktu_selesai' => $waktuSelesai,
            'venue_id' => $request->venue_id,
            'peserta_id' => $request->peserta_id,
            'juri_id' => $request->juri_id,
        ]);

        return redirect()->route('jadwal.index')->with('success', 'Jadwal berhasil diperbarui.');
    }




    public function createStep2(Request $request)
    {
        $validated = $request->validate([
            'nama_jadwal' => 'required|string|max:255',
            'tanggal_awal' => 'required|date',
            'tanggal_akhir' => 'required|date|after_or_equal:tanggal_awal',
            'tanggal' => 'required|array',
            'waktu_mulai' => 'required|array',
            'waktu_selesai' => 'required|array',
            'waktu_mulai.*' => 'required|date_format:H:i',
            'waktu_selesai.*' => 'required|date_format:H:i',
        ]);

        // Validasi waktu_mulai < waktu_selesai per tanggal
        foreach ($request->tanggal as $i => $tanggal) {
            if ($request->waktu_mulai[$i] >= $request->waktu_selesai[$i]) {
                return back()->withErrors(["Waktu selesai harus lebih besar dari waktu mulai pada tanggal $tanggal"]);
            }
        }

        // Format data per tanggal
        $jadwal_per_tanggal = [];
        foreach ($request->tanggal as $i => $tanggal) {
            $jadwal_per_tanggal[] = [
                'tanggal' => $tanggal,
                'waktu_mulai' => $request->waktu_mulai[$i],
                'waktu_selesai' => $request->waktu_selesai[$i],
            ];
        }

        // Simpan ke session
        session([
            'jadwal_nama' => $request->nama_jadwal,
            'jadwal_tanggal_rentang' => [
                'tanggal_awal' => $request->tanggal_awal,
                'tanggal_akhir' => $request->tanggal_akhir,
            ],
            'jadwal_harian' => $jadwal_per_tanggal,
        ]);

        return view('jadwal.create-step2', [
            'jadwal_nama' => $request->nama_jadwal,
            'jadwal_rentang' => $request->only(['tanggal_awal', 'tanggal_akhir']),
            'jadwal_harian' => $jadwal_per_tanggal,
        ]);
    }


    public function createStep3(Request $request)
    {
        $validated = $request->validate([
            'venue' => 'required|exists:venue,id',
            'juri' => 'required|exists:juri,id',
            'kategori_lomba' => 'required|exists:sub_kategori,id',
            'peserta' => 'required|exists:peserta,id',
        ]);

        $venue = Venue::find($validated['venue']);
        $juri = Juri::find($validated['juri']);
        $kategori = SubKategori::find($validated['kategori_lomba']);
        $peserta = Peserta::find($validated['peserta']);

        // Ambil dari session
        $jadwalHarian = session('jadwal_harian', []);
        $subKategoriLomba = $this->processSubKategoriLomba();

        return view('jadwal.create-step3', compact(
            'venue',
            'juri',
            'kategori',
            'peserta',
            'subKategoriLomba',
            'jadwalHarian'
        ));
    }


    public function store(Request $request)
    {
        Log::debug('Memulai proses store constraint lomba.');

        // Validasi form constraint tambahan jika diperlukan
        $validated = $request->validate([
            'hari' => 'nullable|array',
            'waktu_mulai' => 'nullable|array',
            'waktu_selesai' => 'nullable|array',
            'saving_time' => 'nullable|array',
        ]);

        Log::debug('Data yang divalidasi:', $validated);

        $hari = $request->input('hari', []);
        $waktuMulai = $request->input('waktu_mulai', []);
        $waktuSelesai = $request->input('waktu_selesai', []);
        $savingTime = $request->input('saving_time', []);

        Log::debug('Input hari:', $hari);
        Log::debug('Input waktu_mulai:', $waktuMulai);
        Log::debug('Input waktu_selesai:', $waktuSelesai);

        $constraint = [];

        foreach ($hari as $subKategoriId => $value) {
            $constraint[$subKategoriId] = [
                'hari' => is_array($value) ? $value : [$value],
                'waktu_mulai' => $waktuMulai[$subKategoriId] ?? null,
                'waktu_selesai' => $waktuSelesai[$subKategoriId] ?? null,
                'saving_time' => $savingTime[$subKategoriId] ?? null,
            ];

            Log::debug("Constraint untuk sub_kategori_id {$subKategoriId}:", $constraint[$subKategoriId]);
        }

        // Simpan ke session
        session(['constraint_lomba' => $constraint]);

        Log::debug('Constraint disimpan ke session:', $constraint);

        // Lanjutkan ke proses penjadwalan
        return $this->prosesJadwal($request);
    }

    public function switchJadwal($nama_jadwal, $tahun, $version)
    {
        $jadwals = Jadwal::with(['subKategori', 'venue', 'peserta', 'juri'])
            ->where('nama_jadwal', $nama_jadwal)
            ->where('tahun', $tahun)
            ->where('version', $version)
            ->get();

        // Dapatkan semua jadwal yang bisa dipilih untuk switch
        $availableJadwals = Jadwal::with(['peserta'])
            ->where('nama_jadwal', '!=', $nama_jadwal)
            ->where('tahun', $tahun)
            ->get();

        return view('jadwal.switch', compact('jadwals', 'availableJadwals', 'nama_jadwal', 'tahun', 'version'));
    }

    public function createWithDetail($nama_jadwal, $tahun, $version)
    {
        $sub_kategori = SubKategori::all();
        $venue = Venue::all();
        $peserta = Peserta::all();
        $juri = Juri::all();

        return view('jadwal.add', compact('sub_kategori', 'venue', 'peserta', 'juri', 'nama_jadwal', 'tahun', 'version'));
    }

    public function add(Request $request)
    {
        $request->validate([
            'sub_kategori_id' => 'required|exists:sub_kategori,id',
            'waktu_mulai' => 'required|date_format:H:i',
            'waktu_selesai' => 'required|date_format:H:i|after:waktu_mulai',
            'venue_id' => 'required|exists:venue,id',
            'peserta_id' => 'required|exists:peserta,id',
            'juri_id' => 'required|exists:juri,id',
            'nama_jadwal' => 'required|string|max:255',
            'tahun' => 'required|integer',
            'version' => 'required|integer',
        ]);

        $force = $request->input('force', false);
        $waktuMulai = $request->waktu_mulai;
        $waktuSelesai = $request->waktu_selesai;

        $bentrokJadwal = Jadwal::where('venue_id', $request->venue_id)
            ->where(function ($query) use ($waktuMulai, $waktuSelesai) {
                $query->whereBetween('waktu_mulai', [$waktuMulai, $waktuSelesai])
                    ->orWhereBetween('waktu_selesai', [$waktuMulai, $waktuSelesai])
                    ->orWhere(function ($q) use ($waktuMulai, $waktuSelesai) {
                        $q->where('waktu_mulai', '<=', $waktuMulai)
                            ->where('waktu_selesai', '>=', $waktuSelesai);
                    });
            })
            ->exists();

        $bentrokPeserta = Jadwal::where('peserta_id', $request->peserta_id)
            ->where(function ($query) use ($waktuMulai, $waktuSelesai) {
                $query->whereBetween('waktu_mulai', [$waktuMulai, $waktuSelesai])
                    ->orWhereBetween('waktu_selesai', [$waktuMulai, $waktuSelesai])
                    ->orWhere(function ($q) use ($waktuMulai, $waktuSelesai) {
                        $q->where('waktu_mulai', '<=', $waktuMulai)
                            ->where('waktu_selesai', '>=', $waktuSelesai);
                    });
            })
            ->exists();

        $bentrokJuri = Jadwal::where('juri_id', $request->juri_id)
            ->where(function ($query) use ($waktuMulai, $waktuSelesai) {
                $query->whereBetween('waktu_mulai', [$waktuMulai, $waktuSelesai])
                    ->orWhereBetween('waktu_selesai', [$waktuMulai, $waktuSelesai])
                    ->orWhere(function ($q) use ($waktuMulai, $waktuSelesai) {
                        $q->where('waktu_mulai', '<=', $waktuMulai)
                            ->where('waktu_selesai', '>=', $waktuSelesai);
                    });
            })
            ->exists();

        if (($bentrokJadwal || $bentrokPeserta || $bentrokJuri) && !$force) {
            return back()->withInput()->with('error_force', 'Terjadi bentrok jadwal. Apakah Anda yakin ingin melanjutkan penambahan data?');
        }

        // Jika tidak bentrok atau force == true, simpan data
        Jadwal::create([
            'sub_kategori_id' => $request->sub_kategori_id,
            'waktu_mulai' => $request->waktu_mulai,
            'waktu_selesai' => $request->waktu_selesai,
            'venue_id' => $request->venue_id,
            'peserta_id' => $request->peserta_id,
            'juri_id' => $request->juri_id,
            'nama_jadwal' => $request->nama_jadwal,
            'tahun' => $request->tahun,
            'version' => $request->version,
        ]);

        return redirect()->route('jadwal.index')->with('success', 'Jadwal berhasil ditambahkan.');
    }

    public function prosesSwitch(Request $request)
    {
        $selectedIds = $request->input('selected_ids', []);

        Log::info('Switch request initiated', ['selected_ids' => $selectedIds]);

        if (count($selectedIds) !== 2) {
            Log::warning('Jumlah jadwal yang dipilih tidak sama dengan 2');
            return redirect()->back()->with('error', 'Anda harus memilih tepat 2 jadwal untuk ditukar.');
        }

        [$id1, $id2] = $selectedIds;
        $jadwal1 = Jadwal::with('peserta', 'juri')->find($id1);
        $jadwal2 = Jadwal::with('peserta', 'juri')->find($id2);

        Log::info('Jadwal ditemukan', [
            'jadwal1' => $jadwal1,
            'jadwal2' => $jadwal2
        ]);

        if (!$jadwal1 || !$jadwal2) {
            Log::error('Salah satu jadwal tidak ditemukan', compact('jadwal1', 'jadwal2'));
            return redirect()->back()->with('error', 'Jadwal tidak ditemukan.');
        }

        // Pengecekan bentrok peserta dan juri
        $conflict1 = Jadwal::where('id', '!=', $jadwal1->id)
            ->where(function ($q) use ($jadwal1, $jadwal2) {
                $q->where('peserta_id', $jadwal1->peserta_id);

                // Juri dicek hanya jika nama_jadwal berbeda
                if ($jadwal1->sub_kategori_id !== $jadwal2->sub_kategori_id) {
                    $q->orWhere('juri_id', $jadwal1->juri_id);
                }
            })
            ->where(function ($q) use ($jadwal2) {
                $q->whereBetween('waktu_mulai', [$jadwal2->waktu_mulai, $jadwal2->waktu_selesai])
                    ->orWhereBetween('waktu_selesai', [$jadwal2->waktu_mulai, $jadwal2->waktu_selesai]);
            })->exists();

        $conflict2 = Jadwal::where('id', '!=', $jadwal2->id)
            ->where(function ($q) use ($jadwal1, $jadwal2) {
                $q->where('peserta_id', $jadwal2->peserta_id);

                // Juri dicek hanya jika nama_jadwal berbeda
                if ($jadwal1->sub_kategori_id !== $jadwal2->sub_kategori_id) {
                    $q->orWhere('juri_id', $jadwal2->juri_id);
                }
            })
            ->where(function ($q) use ($jadwal1) {
                $q->whereBetween('waktu_mulai', [$jadwal1->waktu_mulai, $jadwal1->waktu_selesai])
                    ->orWhereBetween('waktu_selesai', [$jadwal1->waktu_mulai, $jadwal1->waktu_selesai]);
            })->exists();

        Log::info('Hasil pengecekan konflik', [
            'conflict1' => $conflict1,
            'conflict2' => $conflict2,
        ]);

        if ($conflict1 || $conflict2) {
            $detail = [];
            if ($conflict1)
                $detail[] = "Peserta/Juri pada jadwal pertama bentrok jika ditukar.";
            if ($conflict2)
                $detail[] = "Peserta/Juri pada jadwal kedua bentrok jika ditukar.";

            Log::warning('Terdapat bentrok saat proses switch', $detail);
            return redirect()->back()->with('error', 'Terdapat bentrok: ' . implode(' ', $detail));
        }

        Log::info('Sebelum switch', [
            'jadwal1_mulai' => $jadwal1->waktu_mulai,
            'jadwal1_selesai' => $jadwal1->waktu_selesai,
            'jadwal2_mulai' => $jadwal2->waktu_mulai,
            'jadwal2_selesai' => $jadwal2->waktu_selesai,
        ]);

        DB::transaction(function () use ($jadwal1, $jadwal2) {
            [$jadwal1->waktu_mulai, $jadwal2->waktu_mulai] = [$jadwal2->waktu_mulai, $jadwal1->waktu_mulai];
            [$jadwal1->waktu_selesai, $jadwal2->waktu_selesai] = [$jadwal2->waktu_selesai, $jadwal1->waktu_selesai];

            $jadwal1->save();
            $jadwal2->save();
        });

        Log::info('Berhasil tukar waktu jadwal', [
            'jadwal1_new_mulai' => $jadwal1->waktu_mulai,
            'jadwal1_new_selesai' => $jadwal1->waktu_selesai,
            'jadwal2_new_mulai' => $jadwal2->waktu_mulai,
            'jadwal2_new_selesai' => $jadwal2->waktu_selesai,
        ]);

        return redirect()->route('jadwal.index')->with('success', 'Waktu mulai dan selesai berhasil ditukar.');
    }




    public function constraintPropagation($startTime, $endTime, $variabelX, $pesertaKategori)
    {
        $domain = [];
        $constraints = session('constraint_lomba', []);
        $jadwalHarian = session('jadwal_harian', []);

        foreach ($variabelX as $var) {
            $kategoriLomba = $var['kategori_lomba'];
            $anggotaList = $var['anggota']; // array of NIM
            $namaTim = $var['nama_tim']; // null kalau individu

            // Log::info("=== Mulai proses kategori lomba: $kategoriLomba ===");

            $subKategori = SubKategori::where('name_lomba', $kategoriLomba)->first();
            if (!$subKategori) {
                Log::warning("SubKategori tidak ditemukan untuk lomba: $kategoriLomba");
                continue;
            }

            $subKategoriId = $subKategori->id;
            $venueId = $subKategori->venue_id;
            if (!$venueId) {
                $randomVenue = Venue::inRandomOrder()->first();
                $venueId = $randomVenue?->id;
                // Log::warning("SubKategori $kategoriLomba tidak memiliki venue_id, memilih venue acak: " . ($venueId ?? 'none'));
            }

            $durasi = $subKategori->durasi ?? 30;
            $savingTime = 0;

            $constraint = $constraints[$subKategoriId] ?? null;
            if ($constraint && isset($constraint['saving_time']) && is_numeric($constraint['saving_time'])) {
                $savingTime = (int) $constraint['saving_time'];
            }

            $allSlots = [];
            foreach ($jadwalHarian as $jadwal) {
                $tanggal = $jadwal['tanggal'];
                $mulai = $jadwal['waktu_mulai'];
                $selesai = $jadwal['waktu_selesai'];

                $startDateTime = Carbon::parse("$tanggal $mulai");
                $endDateTime = Carbon::parse("$tanggal $selesai");

                $slots = $this->generateTimeSlots($startDateTime, $endDateTime, $durasi, $savingTime);

                $allSlots = array_merge($allSlots, $slots);
            }

            // Log::debug("Total slot yang dihasilkan: " . count($allSlots));

            $filteredSlots = $this->filterSlotsByConstraint($allSlots, $constraint);
            // Log::info("Jumlah slot setelah filter constraint: " . count($filteredSlots));
            // Log::debug("Detail slot setelah filter constraint:", $filteredSlots);

            // buat key tergantung tim atau individu
            if ($namaTim) {
                // domain untuk tim: satu key per tim
                $key = $kategoriLomba . '-' . $namaTim;
                $domain[$key] = [];

                foreach ($filteredSlots as $slot) {
                    $domain[$key][] = [
                        'tanggal' => $slot['tanggal'],
                        'waktu_mulai' => $slot['waktu_mulai'],
                        'waktu_selesai' => $slot['waktu_selesai'],
                        'kategori_lomba' => $kategoriLomba,
                        'venue' => $venueId,
                        'peserta' => $anggotaList, // array anggota tim
                    ];
                }

                // Log::info("Tim $namaTim pada kategori $kategoriLomba mendapatkan " . count($domain[$key]) . " slot.");
            } else {
                // domain untuk peserta individu: key per nim
                foreach ($anggotaList as $nim) {
                    $key = $kategoriLomba . '-' . $nim;
                    $domain[$key] = [];

                    foreach ($filteredSlots as $slot) {
                        $domain[$key][] = [
                            'tanggal' => $slot['tanggal'],
                            'waktu_mulai' => $slot['waktu_mulai'],
                            'waktu_selesai' => $slot['waktu_selesai'],
                            'kategori_lomba' => $kategoriLomba,
                            'venue' => $venueId,
                            'peserta' => [$nim], // satu anggota dalam array supaya konsisten
                        ];
                    }

                    // Log::info("Peserta $nim pada kategori $kategoriLomba mendapatkan " . count($domain[$key]) . " slot.");
                }
            }
        }

        return $domain;
    }


    private function filterSlotsByConstraint(array $slots, ?array $constraint): array
    {
        if (!$constraint) {
            // Log::debug("Tidak ada constraint, semua slot dikembalikan.");
            return $slots;
        }

        $hariConstraint = $constraint['hari'] ?? null; // ['Monday', 'Wednesday']
        $waktuMulaiConstraint = $constraint['waktu_mulai'] ?? null;
        $waktuSelesaiConstraint = $constraint['waktu_selesai'] ?? null;

        // Log::debug("Memulai filter slot dengan constraint: hari=" . json_encode($hariConstraint) . ", mulai=$waktuMulaiConstraint, selesai=$waktuSelesaiConstraint");

        $filtered = [];

        foreach ($slots as $index => $slot) {
            $slotStart = Carbon::parse($slot['waktu_mulai']);
            $slotEnd = Carbon::parse($slot['waktu_selesai']);

            $slotDate = Carbon::parse($slot['waktu_mulai'])->format('Y-m-d');

            $startTime = $slotStart->format('H:i');
            $endTime = $slotEnd->format('H:i');

            // Log::debug("Memeriksa slot ke-$index: {$slotStart} - {$slotEnd} (Hari: $slotDate , $startTime - $endTime)");

            // Cek hari
            if ($hariConstraint && !in_array($slotDate, $hariConstraint)) {
                // Log::debug("❌ Slot ditolak karena tanggal $slotDate tidak termasuk dalam constraint.");
                continue;
            }

            // Cek waktu mulai
            if ($waktuMulaiConstraint && $startTime < $waktuMulaiConstraint) {
                // Log::debug("❌ Slot ditolak karena mulai $startTime < batas constraint mulai $waktuMulaiConstraint");
                continue;
            }

            // Cek waktu selesai
            if ($waktuSelesaiConstraint && $endTime > $waktuSelesaiConstraint) {
                // Log::debug("❌ Slot ditolak karena selesai $endTime > batas constraint selesai $waktuSelesaiConstraint");
                continue;
            }

            // Log::debug("✅ Slot diterima.");
            $filtered[] = $slot;
        }

        // Log::info("Total slot setelah filter constraint: " . count($filtered));
        return $filtered;
    }



    private function generateTimeSlots($startTime, $endTime, $durasi, $savingTime = 0)
    {
        $slots = [];
        $current = Carbon::parse($startTime);
        $end = Carbon::parse($endTime);

        while ($current < $end) {
            $slotStart = $current->copy();
            $slotEnd = $slotStart->copy()->addMinutes($durasi);

            if ($slotEnd > $end) {
                break;
            }

            $slots[] = [
                'tanggal' => $slotStart->toDateString(),          // tetap simpan tanggal terpisah
                'waktu_mulai' => $slotStart->format('H:i'),      // hanya jam:menit
                'waktu_selesai' => $slotEnd->format('H:i'),      // hanya jam:menit
            ];

            $current = $slotEnd->copy()->addMinutes($savingTime);
        }

        return $slots;
    }

    public function prosesJadwal(Request $request)
    {
        Log::info("Memanggil prosesJadwal");
        
        $startTime = session('jadwal_waktu_mulai', '08:00');
        $endTime = session('jadwal_waktu_selesai', '17:00');
        $variabelX = $this->processPesertaKategoriLomba();
        $pesertaKategori = $variabelX;

        ProsesPenjadwalanJob::dispatch($startTime, $endTime, $variabelX, $pesertaKategori);

        return response()->json([
            'status' => 'success',
            'message' => 'Penjadwalan sedang diproses di background.',
        ]);

    }


    // public function prosesJadwal(Request $request)
    // {
    //     $startTime = session('jadwal_waktu_mulai', '08:00');
    //     $endTime = session('jadwal_waktu_selesai', '17:00');

    //     Log::info('Received request for scheduling. Start time: ' . $startTime . ', End time: ' . $endTime);

    //     $variabelX = $this->processPesertaKategoriLomba();
    //     $pesertaKategori = $variabelX;

    //     set_time_limit(0);

    //     $domain = $this->constraintPropagation($startTime, $endTime, $variabelX, $pesertaKategori);

    //     Log::info('Generated domain: ', $domain);

    //     $jadwalValid = $this->backtrack($domain);

    //     if ($jadwalValid) {
    //         foreach ($jadwalValid as $jadwal) {
    //             if (count($jadwal['peserta']) === 1) {
    //                 // individu
    //                 $peserta = Peserta::where('nim', $jadwal['peserta'][0])->first();
    //                 $pesertaId = $peserta?->id;
    //                 $timId = null;
    //             } else {
    //                 // tim
    //                 $tim = Tim::whereHas('anggota', function ($query) use ($jadwal) {
    //                     $query->whereIn('nim', $jadwal['peserta']);
    //                 }, '=', count($jadwal['peserta']))->first();

    //                 $pesertaId = null;
    //                 $timId = $tim?->id;
    //             }

    //             $subKategori = SubKategori::where('name_lomba', $jadwal['kategori_lomba'])->first();

    //             Jadwal::create([
    //                 'nama_jadwal' => session('jadwal_nama', 'Jadwal Otomatis'),
    //                 'tahun' => now()->year,
    //                 'tanggal' => $jadwal['tanggal'],
    //                 'sub_kategori_id' => $subKategori->id ?? null,
    //                 'waktu_mulai' => $jadwal['waktu_mulai'],
    //                 'waktu_selesai' => $jadwal['waktu_selesai'],
    //                 'venue_id' => $subKategori->venue_id ?? null,
    //                 'peserta_id' => $pesertaId ?? null,
    //                 'tim_id' => $timId ?? null,
    //                 'juri_id' => $juri->id ?? null,
    //                 'version' => 1,
    //             ]);
    //         }

    //         session()->forget([
    //             'jadwal_nama',
    //             'jadwal_waktu_mulai',
    //             'jadwal_waktu_selesai',
    //             'constraint_lomba',
    //         ]);

    //         return view('jadwal.berhasil', [
    //             'message' => 'Jadwal berhasil dibuat!',
    //             'link' => route('jadwal.index')
    //         ]);
    //     }

    //     return response()->json([
    //         'status' => 'failed',
    //         'message' => 'Tidak ada jadwal valid.',
    //     ]);
    // }


    public function backtrack($domain)
    {
        $kategoriKeys = array_keys($domain);
        $solution = [];

        $backtrackRecursive = function ($depth) use (&$backtrackRecursive, $domain, $kategoriKeys, &$solution) {
            if ($depth === count($kategoriKeys)) {
                // Log::info("Solusi lengkap ditemukan!");
                return true;
            }

            $currentKey = $kategoriKeys[$depth];
            $slots = $domain[$currentKey];

            // Log::debug("Depth $depth | Proses key: $currentKey | Total slot tersedia: " . count($slots));

            foreach ($slots as $index => $slot) {
                // Log::debug("Coba slot ke-$index: peserta [" . implode(',', $slot['peserta']) . "] | lomba {$slot['kategori_lomba']} | waktu {$slot['waktu_mulai']} - {$slot['waktu_selesai']} | venue_id {$slot['venue']}");

                if ($this->checkConstraint($slot, $solution)) {
                    // Log::debug("Slot konsisten. Menambahkan ke solusi.");
                    $solution[] = $slot;

                    if ($backtrackRecursive($depth + 1)) {
                        return true;
                    }

                    // Log::debug("Backtrack: menghapus slot terakhir dari solusi.");
                    array_pop($solution);
                } else {
                    // Log::debug("Slot tidak konsisten. Lewati.");
                }
            }

            // Log::debug("Tidak ada slot valid pada depth $depth untuk key $currentKey");
            return false;
        };

        if ($backtrackRecursive(0)) {
            // Log::info("Proses backtracking berhasil. Solusi akhir:");
            // Log::info($solution);
            return $solution;
        }

        // Log::warning("Backtracking gagal: tidak ditemukan solusi valid.");
        return null;
    }


    private function checkConstraint($slot, $assignment)
    {
        $start = Carbon::parse($slot['waktu_mulai']);
        $end = Carbon::parse($slot['waktu_selesai']);

        foreach ($assignment as $key => $assignedSlot) {
            $assignedStart = Carbon::parse($assignedSlot['waktu_mulai']);
            $assignedEnd = Carbon::parse($assignedSlot['waktu_selesai']);

            $overlap = $start->lt($assignedEnd) && $end->gt($assignedStart);

            if ($overlap) {
                // Bentrok peserta: cek irisan anggota peserta
                $pesertaIntersect = array_intersect($slot['peserta'], $assignedSlot['peserta']);
                if (!empty($pesertaIntersect)) {
                    // Log::debug("Bentrok PESERTA anggota [" . implode(',', $pesertaIntersect) . "] dengan $key pada waktu {$slot['waktu_mulai']} - {$slot['waktu_selesai']}");
                    return false;
                }

                // Bentrok venue
                if ($slot['venue'] === $assignedSlot['venue']) {
                    // Log::debug("Bentrok VENUE dengan $key pada waktu {$slot['waktu_mulai']} - {$slot['waktu_selesai']}");
                    return false;
                }
            }
        }

        return true;
    }

    public function generateVariabelX()
    {
        $variabelX = $this->processPesertaKategoriLomba();

        Log::info('Variabel X berhasil dibuat (berdasarkan peserta dan kategori)', ['variabelX' => $variabelX]);

        return response()->json([
            'status' => 'success',
            'message' => 'Variabel X berhasil dibuat',
            'data' => $variabelX
        ]);
    }

    private function processSubKategoriLomba()
    {
        $peserta = Peserta::with('subKategori')->get();

        $subKategoriIds = $peserta->pluck('sub_kategori_id');

        $grouped = $subKategoriIds->unique()->values();

        $subKategoriLomba = [];

        foreach ($grouped as $id) {
            $subKategori = $peserta->firstWhere('sub_kategori_id', $id)->subKategori;
            $subKategoriLomba[] = [
                'sub_kategori_id' => $id,
                'nama_sub_kategori' => $subKategori ? $subKategori->name_lomba : 'Tidak Diketahui'
            ];
        }

        return $subKategoriLomba;
    }

    public function processPesertaKategoriLomba()
    {
        $result = [];
        $timMap = [];

        // === 1. Kelompok ===
        $timList = Tim::with(['anggota.subKategori'])->get();
        Log::info('Total tim ditemukan: ' . count($timList));

        foreach ($timList as $tim) {
            Log::info("Proses tim: {$tim->nama_tim}");

            foreach ($tim->anggota as $anggota) {
                $subKategori = $anggota->subKategori;
                if (!$subKategori) {
                    Log::warning("Subkategori tidak ditemukan untuk anggota NIM: {$anggota->nim}");
                    continue;
                }

                $key = $subKategori->name_lomba . '-' . $tim->nama_tim;

                $timMap[$key]['kategori_lomba'] = $subKategori->name_lomba;
                $timMap[$key]['nama_tim'] = $tim->nama_tim;
                $timMap[$key]['anggota'][] = $anggota->nim;

                Log::info("Anggota {$anggota->nim} ditambahkan ke tim {$tim->nama_tim} untuk lomba {$subKategori->name_lomba}");
            }
        }

        // === 2. Individu ===
        $pesertaIndividu = Peserta::with('subKategori')
            ->whereDoesntHave('tim')
            ->get();
        Log::info('Total peserta individu: ' . count($pesertaIndividu));

        foreach ($pesertaIndividu as $pesertaItem) {
            $subKategori = $pesertaItem->subKategori;
            if (!$subKategori) {
                Log::warning("Subkategori tidak ditemukan untuk peserta individu NIM: {$pesertaItem->nim}");
                continue;
            }

            $key = $subKategori->name_lomba . '-' . $pesertaItem->nim;

            $timMap[$key] = [
                'kategori_lomba' => $subKategori->name_lomba,
                'nama_tim' => null,
                'anggota' => [$pesertaItem->nim],
            ];

            Log::info("Peserta individu {$pesertaItem->nim} ditambahkan ke lomba {$subKategori->name_lomba}");
        }

        $result = array_values($timMap);
        Log::info('Total hasil peserta-kategori: ' . count($result));

        return $result;
    }


    public function destroy($id)
    {
        $jadwal = Jadwal::findOrFail($id);
        $jadwal->delete();

        return redirect()->back()->with('success', 'Jadwal berhasil dihapus.');
    }



}