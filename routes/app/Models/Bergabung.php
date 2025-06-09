<?php

// app/Models/Bergabung.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bergabung extends Model
{
    use HasFactory;

    protected $table = 'bergabung';

    protected $fillable = [
        'tim_id',
        'peserta_id',
        'posisi',
    ];

    public function tim()
    {
        return $this->belongsTo(Tim::class);
    }

    public function peserta()
    {
        return $this->belongsTo(Peserta::class);
    }
}
