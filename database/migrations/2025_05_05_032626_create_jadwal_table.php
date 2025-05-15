<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('jadwal', function (Blueprint $table) {
            $table->id();
            $table->string('nama_jadwal')->nullable();
            $table->year('tahun')->nullable();
            
            $table->unsignedBigInteger('sub_kategori_id')->nullable();
            $table->foreign('sub_kategori_id')->references('id')->on('sub_kategori')->onDelete('set null');
        
            $table->time('waktu_mulai');
            $table->time('waktu_selesai');
        
            $table->unsignedBigInteger('venue_id')->nullable();
            $table->foreign('venue_id')->references('id')->on('venue')->onDelete('set null');
        
            $table->unsignedBigInteger('peserta_id')->nullable();
            $table->foreign('peserta_id')->references('id')->on('peserta')->onDelete('set null');
        
            $table->unsignedBigInteger('juri_id')->nullable();
            $table->foreign('juri_id')->references('id')->on('juri')->onDelete('set null');
        
            $table->integer('version')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jadwal');
    }
};
