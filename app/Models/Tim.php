<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Tim extends Model
{
    use HasFactory;
    protected $table = 'tim';

    protected $fillable = [
        'nama_tim',
    ];

    public function anggota()
    {
        return $this->belongsToMany(Peserta::class, 'bergabung')->withPivot('posisi')->withTimestamps();
    }

    public function jadwal()
    {
        return $this->hasMany(Jadwal::class);
    }

}

