<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Juri extends Model
{
    use HasFactory;

    protected $table = 'juri'; 
    protected $fillable = ['nama', 'jabatan', 'sub_kategori_id'];

    public function subKategori()
    {
        return $this->belongsTo(SubKategori::class);
    }
}