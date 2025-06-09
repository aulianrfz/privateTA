<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('mata_lomba', function (Blueprint $table) {
            $table->boolean('is_semifinal')->default(false)->after('is_serentak');
            $table->integer('jumlah_sesi')->nullable()->after('is_semifinal');
            $table->boolean('tersesi_dari_awal')->default(false)->after('jumlah_sesi');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('mata_lomba', function (Blueprint $table) {
            $table->dropColumn(['is_semifinal', 'jumlah_sesi', 'tersesi_dari_awal']);
        });
    }
};
