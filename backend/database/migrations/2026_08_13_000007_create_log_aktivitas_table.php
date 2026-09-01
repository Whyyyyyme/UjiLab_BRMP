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
        Schema::create('log_aktivitas', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->foreignId('petugas_id')->nullable()->constrained('petugas')->onDelete('restrict');
            $blueprint->string('aksi');
            $blueprint->text('detail')->nullable();
            $blueprint->string('ip_address', 45); // IPv6 format length support
            $blueprint->string('user_agent')->nullable();
            $blueprint->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('log_aktivitas');
    }
};
