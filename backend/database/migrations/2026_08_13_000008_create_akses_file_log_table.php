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
        Schema::create('akses_file_log', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->foreignId('pengujian_id')->constrained('pengujian')->onDelete('restrict');
            $blueprint->enum('tipe_file', ['laporan', 'sertifikat']);
            $blueprint->enum('akses_oleh', ['publik', 'petugas']);
            $blueprint->foreignId('petugas_id')->nullable()->constrained('petugas')->onDelete('restrict');
            $blueprint->string('ip_address', 45);
            $blueprint->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('akses_file_log');
    }
};
