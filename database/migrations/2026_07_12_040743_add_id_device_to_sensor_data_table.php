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
        Schema::table('sensor_data', function (Blueprint $table) {
            // Menambahkan kolom id_device tipe string setelah kolom id (atau di awal tabel)
            // nullable() diberikan agar data lama yang sudah ada tidak error saat di-migrate
            $table->string('id_device')->nullable()->after('id');

            // Opsional: Tambahkan index jika tabel ini nantinya akan menampung jutaan data 
            // agar proses query GroupBy / Where id_device di Livewire Anda menjadi sangat cepat.
            $table->index('id_device');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sensor_data', function (Blueprint $table) {
            // Menghapus index dan kolom jika migration di-rollback
            $table->dropIndex(['id_device']);
            $table->dropColumn('id_device');
        });
    }
};
