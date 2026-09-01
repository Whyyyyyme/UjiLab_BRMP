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
        Schema::create('skm', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->foreignId('pengujian_id')->unique()->constrained('pengujian')->onDelete('restrict');
            $blueprint->tinyInteger('skor_1');
            $blueprint->tinyInteger('skor_2');
            $blueprint->tinyInteger('skor_3');
            $blueprint->tinyInteger('skor_4');
            $blueprint->tinyInteger('skor_5');
            $blueprint->tinyInteger('skor_6');
            $blueprint->tinyInteger('skor_7');
            $blueprint->tinyInteger('skor_8');
            $blueprint->tinyInteger('skor_9');
            $blueprint->text('saran')->nullable();
            $blueprint->date('tanggal_isi');
            $blueprint->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('skm');
    }
};
