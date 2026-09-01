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
        Schema::create('token_akses', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->foreignId('pengujian_id')->constrained('pengujian')->onDelete('restrict');
            $blueprint->string('token_hash');
            $blueprint->timestamp('expired_at');
            $blueprint->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('token_akses');
    }
};
