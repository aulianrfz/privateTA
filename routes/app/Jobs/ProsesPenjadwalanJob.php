<?php

namespace App\Jobs;

use App\Models\Peserta;
use App\Models\Tim;
use App\Models\Jadwal;
use App\Models\SubKategori;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Carbon\Carbon;
use App\Http\Controllers\PenjadwalanController;

class ProsesPenjadwalanJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $startTime, $endTime, $variabelX, $pesertaKategori;

    public function __construct($startTime, $endTime, $variabelX, $pesertaKategori)
    {
        $this->startTime = $startTime;
        $this->endTime = $endTime;
        $this->variabelX = $variabelX;
        $this->pesertaKategori = $pesertaKategori;

        Log::info("Job dispatched with data: " . json_encode([
            'startTime' => $startTime,
            'endTime' => $endTime,
        ]));
    }

    public function handle()
    {
        Log::info("Running job handle");
        
        $penjadwal = new PenjadwalanController();
        $domain = $penjadwal->constraintPropagation($this->startTime, $this->endTime, $this->variabelX, $this->pesertaKategori);

        Log::info('Generated domain: ' . json_encode($domain));

        $jadwalValid = $penjadwal->backtrack($domain);

        if ($jadwalValid) {
            foreach ($jadwalValid as $jadwal) {
                if (count($jadwal['peserta']) === 1) {
                    $peserta = Peserta::where('nim', $jadwal['peserta'][0])->first();
                    $pesertaId = $peserta?->id;
                    $timId = null;
                } else {
                    $tim = Tim::whereHas('anggota', function ($query) use ($jadwal) {
                        $query->whereIn('nim', $jadwal['peserta']);
                    }, '=', count($jadwal['peserta']))->first();

                    $pesertaId = null;
                    $timId = $tim?->id;
                }

                $subKategori = SubKategori::where('name_lomba', $jadwal['kategori_lomba'])->first();

                Jadwal::create([
                    'nama_jadwal' => session('jadwal_nama', 'Jadwal Otomatis'),
                    'tahun' => now()->year,
                    'tanggal' => $jadwal['tanggal'],
                    'sub_kategori_id' => $subKategori->id ?? null,
                    'waktu_mulai' => $jadwal['waktu_mulai'],
                    'waktu_selesai' => $jadwal['waktu_selesai'],
                    'venue_id' => $jadwal['venue'],
                    'peserta_id' => $pesertaId,
                    'tim_id' => $timId,
                    'status' => 'dijadwalkan',
                ]);
            }
            Log::info("Penjadwalan selesai.");
        } else {
            Log::warning("Gagal melakukan penjadwalan. Tidak ada jadwal valid ditemukan.");
        }
    }
}
