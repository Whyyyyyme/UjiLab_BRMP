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
        Schema::create('otp_verifikasi', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->foreignId('pengujian_id')->constrained('pengujian')->onDelete('restrict');
            $blueprint->string('kode_hash');
            $blueprint->enum('status', ['aktif', 'kadaluarsa', 'terpakai'])->default('aktif');
            $blueprint->integer('percobaan_gagal')->default(0);
            $blueprint->timestamp('expired_at');
            $blueprint->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('otp_verifikasi');
    }
};
