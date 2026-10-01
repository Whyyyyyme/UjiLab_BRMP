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
        Schema::table('skm', function (Blueprint $table) {
            $table->string('nama')->nullable()->after('pengujian_id');
            $table->string('jenis_kelamin')->nullable()->after('nama');
            $table->string('pendidikan')->nullable()->after('jenis_kelamin');
            $table->string('usia')->nullable()->after('pendidikan');
            $table->string('pekerjaan')->nullable()->after('usia');
            $table->string('disabilitas')->nullable()->after('pekerjaan');
            $table->integer('skor_10')->after('skor_9')->nullable();
            $table->integer('skor_11')->after('skor_10')->nullable();
            $table->integer('skor_12')->after('skor_11')->nullable();
            $table->integer('skor_13')->after('skor_12')->nullable();
            $table->integer('skor_14')->after('skor_13')->nullable();
            $table->integer('skor_15')->after('skor_14')->nullable();
            $table->integer('skor_16')->after('skor_15')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('skm', function (Blueprint $table) {
            $table->dropColumn([
                'nama',
                'jenis_kelamin',
                'pendidikan',
                'usia',
                'pekerjaan',
                'disabilitas',
                'skor_10',
                'skor_11',
                'skor_12',
                'skor_13',
                'skor_14',
                'skor_15',
                'skor_16',
            ]);
        });
    }
};
