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
        Schema::create('pengujian', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->string('nomor_pengujian')->unique();
            $blueprint->string('nama_pemohon');
            $blueprint->string('email_pemohon');
            $blueprint->string('jenis_pengujian');
            $blueprint->enum('status', ['diproses', 'selesai'])->default('diproses');
            $blueprint->string('file_laporan')->nullable();
            $blueprint->string('file_sertifikat')->nullable();
            $blueprint->string('hash_laporan')->nullable();
            $blueprint->string('hash_sertifikat')->nullable();
            $blueprint->integer('versi')->default(1);
            $blueprint->boolean('is_deleted')->default(false);
            $blueprint->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengujian');
    }
};
