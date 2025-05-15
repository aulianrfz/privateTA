<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\KategoriLomba;
use App\Models\Venue;
use App\Models\Peserta;
use App\Models\Jadwal;
use App\Models\SubKategori;
use App\Models\Juri;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;



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
        $jadwals = Jadwal::with(['subKategori', 'venue', 'peserta', 'juri'])
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
        // Validasi input dari step 1
        $validated = $request->validate([
            'nama_jadwal' => 'required|string|max:255',
            'tanggal' => 'required|date',
            'waktu_mulai' => 'required|date_format:H:i',
            'waktu_selesai' => 'required|date_format:H:i|after:waktu_mulai',
        ]);

        // Simpan ke session / lanjut ke halaman selanjutnya

        session([
            'jadwal_nama' => $validated['nama_jadwal'],
            'jadwal_tanggal' => [$validated['tanggal']],
            'jadwal_waktu_mulai' => $validated['waktu_mulai'],
            'jadwal_waktu_selesai' => $validated['waktu_selesai'],
        ]);
        // Atau langsung simpan dan redirect
        // Untuk sekarang, contoh langsung redirect ke step 2 (buat view-nya jika perlu)
        return view('jadwal.create-step2', ['data' => $validated]);
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

        $tanggal = session('jadwal_tanggal', []);
        // dd(session('jadwal_tanggal'));
        $jumlahHari = is_array($tanggal) ? count($tanggal) : 1;

        $subKategoriLomba = $this->processSubKategoriLomba();

        return view('jadwal.create-step3', compact('venue', 'juri', 'kategori', 'peserta', 'subKategoriLomba', 'jumlahHari'));
    }

    public function store(Request $request)
    {
        // Validasi form constraint tambahan jika diperlukan
        $validated = $request->validate([
            'hari' => 'nullable|array',
            'waktu_mulai' => 'nullable|array',
            'waktu_selesai' => 'nullable|array',
        ]);

        $hari = $request->input('hari', []);
        $waktuMulai = $request->input('waktu_mulai', []);
        $waktuSelesai = $request->input('waktu_selesai', []);

        $constraint = [];

        // Gabungkan menjadi array terstruktur per sub_kategori_id
        foreach ($hari as $subKategoriId => $value) {
            $constraint[$subKategoriId] = [
                'hari' => $value,
                'waktu_mulai' => $waktuMulai[$subKategoriId] ?? null,
                'waktu_selesai' => $waktuSelesai[$subKategoriId] ?? null,
            ];
        }

        // Simpan ke session
        session(['constraint_lomba' => $constraint]);

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

        foreach ($variabelX as $var) {
            $nameLomba = $var['kategori_lomba'];
            $nim = $var['nim'];
            $key = $nameLomba . '-' . $nim;

            $pesertaLomba = collect([$var]);

            // Debug: Check each participant's category and their respective subcategory
            Log::info("Processing peserta: $nim, lomba: $nameLomba");

            $subKategori = SubKategori::where('name_lomba', $nameLomba)->first();
            if (!$subKategori) {
                Log::warning("SubKategori not found for lomba: $nameLomba");
                continue;
            }

            $subKategoriId = $subKategori->id;

            $juriList = Juri::whereHas('subKategori', function ($query) use ($subKategoriId) {
                $query->where('sub_kategori_id', $subKategoriId);
            })->get();

            $durasi = $subKategori->durasi ?? 30;

            $availableSlots = $this->generateTimeSlots($startTime, $endTime, $durasi);

            // Debug: Check the generated available slots
            Log::info("Available slots for lomba $nameLomba: ", $availableSlots);

            $validSlots = [];

            foreach ($availableSlots as $slot) {
                foreach ($juriList as $juri) {
                    $conflict = Jadwal::where(function ($query) use ($juri, $slot) {
                        $query->where('juri_id', $juri->id)
                            ->where(function ($q) use ($slot) {
                                $q->whereBetween('waktu_mulai', [$slot['waktu_mulai'], $slot['waktu_selesai']])
                                    ->orWhereBetween('waktu_selesai', [$slot['waktu_mulai'], $slot['waktu_selesai']]);
                            });
                    })->exists();

                    if (!$conflict) {
                        // Debug: Valid slot found, add to valid slots
                        Log::info("Valid slot found for juri: {$juri->nama} at waktu: {$slot['waktu_mulai']} - {$slot['waktu_selesai']}");

                        $validSlots[] = [
                            'waktu_mulai' => $slot['waktu_mulai'],
                            'waktu_selesai' => $slot['waktu_selesai'],
                            'juri' => $juri->nama,
                            'kategori_lomba' => $nameLomba,
                            'peserta' => $nim,
                        ];
                    }
                }
            }

            $domain[$key] = $validSlots;
        }

        return $domain;
    }


    private function generateTimeSlots($startTime, $endTime, $durasi)
    {
        $slots = [];
        $current = Carbon::parse($startTime);
        $end = Carbon::parse($endTime);

        while ($current->addMinutes(0) < $end) {
            $slotStart = $current->copy();
            $slotEnd = $current->copy()->addMinutes($durasi);

            if ($slotEnd > $end)
                break;

            $slots[] = [
                'waktu_mulai' => $slotStart->toDateTimeString(),
                'waktu_selesai' => $slotEnd->toDateTimeString(),
            ];

            $current->addMinutes($durasi);
        }

        return $slots;
    }



    public function prosesJadwal(Request $request)
    {
        $startTime = session('jadwal_waktu_mulai', '08:00');
        $endTime = session('jadwal_waktu_selesai', '17:00');

        // Debug: Request received and processing participants
        Log::info('Received request for scheduling. Start time: ' . $startTime . ', End time: ' . $endTime);

        // INI GANTI
        $variabelX = $this->processPesertaKategoriLomba();
        $pesertaKategori = $variabelX;

        $domain = $this->constraintPropagation($startTime, $endTime, $variabelX, $pesertaKategori);

        // Debug: Check generated domain
        Log::info('Generated domain: ', $domain);

        $jadwalValid = $this->backtrack($domain);

        if ($jadwalValid) {
            foreach ($jadwalValid as $jadwal) {
                $juri = Juri::where('nama', $jadwal['juri'])->first();
                $peserta = Peserta::where('nim', $jadwal['peserta'])->first();
                $subKategori = SubKategori::where('name_lomba', $jadwal['kategori_lomba'])->first();

                // Debug: Scheduling a valid jadwal
                Log::info("Scheduling jadwal for peserta: {$jadwal['peserta']} with juri: {$jadwal['juri']} from {$jadwal['waktu_mulai']} to {$jadwal['waktu_selesai']}");

                Jadwal::create([
                    'nama_jadwal' => session('jadwal_nama', 'Jadwal Otomatis'),
                    'tahun' => now()->year,
                    'sub_kategori_id' => $subKategori->id ?? null,
                    'waktu_mulai' => $jadwal['waktu_mulai'],
                    'waktu_selesai' => $jadwal['waktu_selesai'],
                    'venue_id' => $subKategori->venue_id ?? null,
                    'peserta_id' => $peserta->id ?? null,
                    'juri_id' => $juri->id ?? null,
                    'version' => 1,
                ]);
            }

            session()->forget([
                'jadwal_nama',
                'jadwal_waktu_mulai',
                'jadwal_waktu_selesai',
                'constraint_lomba',
            ]);

            // Redirect ke halaman sukses
            return view('jadwal.berhasil', [
                'message' => 'Jadwal berhasil dibuat!',
                'link' => route('jadwal.index')
            ]);
        }

        return response()->json([
            'status' => 'failed',
            'message' => 'Tidak ada jadwal valid.',
        ]);
    }



    public function backtrack($domain)
    {
        $kategoriKeys = array_keys($domain);
        $solution = [];

        $backtrackRecursive = function ($depth) use (&$backtrackRecursive, $domain, $kategoriKeys, &$solution) {
            if ($depth === count($kategoriKeys)) {
                return true;
            }

            $currentKey = $kategoriKeys[$depth];
            $slots = $domain[$currentKey];

            Log::info("Backtracking at depth: $depth, key: $currentKey, available slots: ", $slots);

            foreach ($slots as $slot) {
                // Pastikan venue tersedia, bisa diambil dari SubKategori
                $subKategori = SubKategori::where('name_lomba', $slot['kategori_lomba'])->first();
                $slot['venue'] = $subKategori->venue_id ?? null;

                if ($this->isConsistent($slot, $solution)) {
                    $solution[] = $slot;

                    if ($backtrackRecursive($depth + 1)) {
                        return true;
                    }

                    array_pop($solution);
                }
            }

            return false;
        };

        if ($backtrackRecursive(0)) {
            return $solution;
        }

        return null;
    }




    private function isConsistent($slot, $assignment)
    {
        $start = Carbon::parse($slot['waktu_mulai']);
        $end = Carbon::parse($slot['waktu_selesai']);

        foreach ($assignment as $key => $assignedSlot) {
            $assignedStart = Carbon::parse($assignedSlot['waktu_mulai']);
            $assignedEnd = Carbon::parse($assignedSlot['waktu_selesai']);

            $overlap = $start->lt($assignedEnd) && $end->gt($assignedStart);

            if ($overlap) {
                // Constraint 1: juri bentrok
                if ($slot['juri'] === $assignedSlot['juri']) {
                    Log::debug("🚫 Bentrok JURI dengan $key pada waktu {$slot['waktu_mulai']} - {$slot['waktu_selesai']}");
                    return false;
                }

                // Constraint 2: peserta bentrok
                if ($slot['peserta'] === $assignedSlot['peserta']) {
                    Log::debug("🚫 Bentrok PESERTA dengan $key pada waktu {$slot['waktu_mulai']} - {$slot['waktu_selesai']}");
                    return false;
                }

                // Constraint 3: venue bentrok
                if ($slot['venue'] === $assignedSlot['venue']) {
                    Log::debug("🚫 Bentrok VENUE dengan $key pada waktu {$slot['waktu_mulai']} - {$slot['waktu_selesai']}");
                    return false;
                }
            }
        }

        return true; // Tidak ada bentrok
    }

    public function generateVariabelX()
    {
        // Ambil kombinasi peserta dan kategori lomba
        $variabelX = $this->processPesertaKategoriLomba();

        // Log hasilnya
        Log::info('Variabel X berhasil dibuat (berdasarkan peserta dan kategori)', ['variabelX' => $variabelX]);

        return response()->json([
            'status' => 'success',
            'message' => 'Variabel X berhasil dibuat',
            'data' => $variabelX
        ]);
    }

    private function processSubKategoriLomba()
    {
        // 1. Ambil semua peserta dan relasi sub_kategori
        $peserta = Peserta::with('subKategori')->get();

        // 2. Ambil semua sub_kategori_id dari peserta
        $subKategoriIds = $peserta->pluck('sub_kategori_id');

        // 3. Kelompokkan berdasarkan sub_kategori_id (otomatis unik)
        $grouped = $subKategoriIds->unique()->values();

        // 4. Simpan hasil sebagai daftar sub kategori lomba
        $subKategoriLomba = [];

        foreach ($grouped as $id) {
            // Mengambil nama sub kategori (nama lomba) dari relasi 'subKategori'
            $subKategori = $peserta->firstWhere('sub_kategori_id', $id)->subKategori;
            $subKategoriLomba[] = [
                'sub_kategori_id' => $id,
                'nama_sub_kategori' => $subKategori ? $subKategori->name_lomba : 'Tidak Diketahui'
            ];
        }

        // Kembalikan hasil sub kategori lomba
        return $subKategoriLomba;
    }

    public function processPesertaKategoriLomba()
    {
        // Ambil semua peserta dan relasi kategori lomba (asumsi sudah ada relasi)
        $peserta = Peserta::with('subKategori')->get();

        // Proses data untuk mengambil nim dan kategori lomba yang diikuti
        $dataPesertaKategoriLomba = $peserta->map(function ($pesertaItem) {
            // Ambil nim peserta dan nama kategori lomba
            $subKategori = $pesertaItem->subKategori; // Mengambil relasi subKategori

            return [
                'nim' => $pesertaItem->nim,
                'kategori_lomba' => $subKategori ? $subKategori->name_lomba : 'Tidak Diketahui',
            ];
        });

        // Kembalikan hasilnya
        return $dataPesertaKategoriLomba;

        // return response()->json([
        //     'status' => 'success',
        //     'message' => 'Data peserta dan kategori lomba berhasil diambil',
        //     'data' => $dataPesertaKategoriLomba
        // ]);
    }


}