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
        Schema::create('petugas', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->string('nama');
            $blueprint->string('username')->unique();
            $blueprint->string('email')->unique();
            $blueprint->string('password');
            $blueprint->enum('role', ['admin', 'petugas_lab'])->default('petugas_lab');
            $blueprint->boolean('wajib_ganti_password')->default(true);
            $blueprint->integer('percobaan_login_gagal')->default(0);
            $blueprint->timestamp('terkunci_hingga')->nullable();
            $blueprint->rememberToken();
            $blueprint->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('petugas');
    }
};
