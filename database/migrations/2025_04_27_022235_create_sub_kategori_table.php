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
        Schema::create('sub_kategori', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('kategori_id');
            $table->unsignedBigInteger('venue_id')->nullable();
            $table->string('name_lomba');
            $table->string('jenis_lomba');
            $table->string('jurusan');
            $table->integer('maks_peserta');
            $table->string('jenis_pelaksanaan');
            $table->text('deskripsi');
            $table->integer('duration');
            $table->decimal('biaya_pendaftaran', 12, 2);
            $table->string('url_tor')->nullable();
            $table->string('foto_kompetisi')->nullable();
            $table->timestamps();

            $table->foreign('kategori_id')->references('id')->on('kategori_lomba')->onDelete('cascade');
            $table->foreign('venue_id')->references('id')->on('venue')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sub_kategori');
    }
};
