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
        Schema::create('log_notifikasi', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->foreignId('pengujian_id')->constrained('pengujian')->onDelete('restrict');
            $blueprint->string('email_tujuan');
            $blueprint->enum('tipe_notifikasi', ['otp', 'hasil_siap']);
            $blueprint->enum('status', ['terkirim', 'gagal', 'gagal_permanen'])->default('gagal');
            $blueprint->integer('percobaan_ke')->default(1);
            $blueprint->timestamp('waktu_percobaan_berikutnya')->nullable();
            $blueprint->text('pesan_error')->nullable();
            $blueprint->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('log_notifikasi');
    }
};
