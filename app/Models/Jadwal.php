<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Jadwal extends Model
{
    use HasFactory;
    protected $table = 'jadwal';
    protected $fillable = [
        'nama_jadwal',
        'tahun',
        'sub_kategori_id',
        'waktu_mulai',
        'waktu_selesai',
        'venue_id',
        'peserta_id',
        'juri_id',
        'version',
    ];

    public function subKategori()
    {
        return $this->belongsTo(SubKategori::class, 'sub_kategori_id');
    }

    public function venue()
    {
        return $this->belongsTo(Venue::class, 'venue_id');
    }

    public function peserta()
    {
        return $this->belongsTo(Peserta::class, 'peserta_id');
    }

    public function juri()
    {
        return $this->belongsTo(Juri::class, 'juri_id');
    }
}
